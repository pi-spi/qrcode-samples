import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import {
  buildPayloadString,
  generateQrCodeSvg,
  PISPI_QRCODE_LOGO_DATA_URL,
  QrType,
} from '@pi-spi/qrcode';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-qr-generator',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './qr-generator.component.html',
})
export class QrGeneratorComponent implements OnInit, OnDestroy {
  private readonly fb = inject(FormBuilder);
  private readonly sanitizer = inject(DomSanitizer);
  private readonly subscriptions = new Subscription();
  private copyTimeout: ReturnType<typeof setTimeout> | null = null;

  readonly countries = [
    { code: 'BJ', label: 'Bénin (BJ)' },
    { code: 'BF', label: 'Burkina Faso (BF)' },
    { code: 'CI', label: 'Côte d’Ivoire (CI)' },
    { code: 'ML', label: 'Mali (ML)' },
    { code: 'NE', label: 'Niger (NE)' },
    { code: 'SN', label: 'Sénégal (SN)' },
    { code: 'TG', label: 'Togo (TG)' },
    { code: 'GW', label: 'Guinée-Bissau (GW)' },
  ];

  readonly form = this.fb.group({
    countryCode: ['CI', Validators.required],
    alias: [
      '3497a720-ab11-4973-9619-534e04f263a1',
      [Validators.required, Validators.maxLength(36)],
    ],
    qrType: ['STATIC' as QrType, Validators.required],
    referenceLabel: ['SANDBOX_DEMO', [Validators.required, Validators.maxLength(25)]],
    amount: ['2500', [Validators.pattern(/^\d*$/)]],
  });

  svg: SafeHtml | null = null;
  payload = '';
  loading = false;
  error: string | null = null;
  showAdvanced = false;
  copied = false;

  ngOnInit(): void {
    const qrTypeControl = this.form.get('qrType');
    if (qrTypeControl) {
      this.subscriptions.add(
        qrTypeControl.valueChanges.subscribe((type) => {
          if (type === 'DYNAMIC') {
            this.form.patchValue({ referenceLabel: '' }, { emitEvent: false });
          } else if (!this.form.get('referenceLabel')?.value) {
            this.form.patchValue({ referenceLabel: 'SANDBOX_DEMO' }, { emitEvent: false });
          }
        })
      );
    }

    this.subscriptions.add(
      this.form.valueChanges.subscribe(() => {
        this.copied = false;
      })
    );

    void this.submit();
  }

  ngOnDestroy(): void {
    this.clearCopyTimeout();
    this.subscriptions.unsubscribe();
  }

  get referencePlaceholder(): string {
    return this.form.get('qrType')?.value === 'DYNAMIC'
      ? 'C-2025-11-12-125253-001'
      : 'Identifiant caisse, transaction, etc.';
  }

  get amountDisplay(): string {
    const raw = this.form.get('amount')?.value ?? '';
    return raw.trim() !== '' ? raw.trim() : 'Non défini';
  }

  async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const { alias, countryCode, qrType, referenceLabel, amount } = this.form.getRawValue();
    const sanitizedAmount = amount?.trim() ? amount.trim() : undefined;

    const input = {
      alias: alias!.trim(),
      countryCode: countryCode!.trim().toUpperCase(),
      qrType: qrType as QrType,
      referenceLabel: referenceLabel!.trim(),
      ...(sanitizedAmount ? { amount: sanitizedAmount } : {}),
    };

    this.loading = true;
    this.copied = false;
    this.error = null;

    try {
      const [svg, payload] = await Promise.all([
        generateQrCodeSvg(input, {
          size: 320,
          margin: 0,
          logoDataUrl: PISPI_QRCODE_LOGO_DATA_URL,
          logoPaddingRatio: 0,
        }),
        Promise.resolve(buildPayloadString(input)),
      ]);

      this.svg = this.sanitizer.bypassSecurityTrustHtml(svg);
      this.payload = payload;
    } catch (err: any) {
      console.error('Erreur génération QR', err);
      this.error = err?.message ?? 'Erreur inattendue';
      this.svg = null;
      this.payload = '';
    } finally {
      this.loading = false;
    }
  }

  async copyPayload(): Promise<void> {
    if (!this.payload) {
      return;
    }
    try {
      await navigator.clipboard.writeText(this.payload);
      this.copied = true;
      this.resetCopyFeedback();
    } catch (err) {
      console.error('Erreur copie payload', err);
    }
  }

  downloadSvg(): void {
    if (!this.svg) {
      return;
    }
    const svgString = (this.svg as unknown as { changingThisBreaksApplicationSecurity: string })
      .changingThisBreaksApplicationSecurity;
    const blob = new Blob([svgString], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'pi-spi-qr.svg';
    link.click();
    URL.revokeObjectURL(url);
  }

  toggleAdvanced(): void {
    this.showAdvanced = !this.showAdvanced;
  }

  private resetCopyFeedback(): void {
    this.clearCopyTimeout();
    this.copyTimeout = setTimeout(() => {
      this.copied = false;
      this.copyTimeout = null;
    }, 1500);
  }

  private clearCopyTimeout(): void {
    if (this.copyTimeout) {
      clearTimeout(this.copyTimeout);
      this.copyTimeout = null;
    }
  }
}


