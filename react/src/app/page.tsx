'use client';

import { ChangeEvent, FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import {
  buildPayloadString,
  generateQrCodeSvg,
  PISPI_QRCODE_LOGO_DATA_URL,
  QrType,
} from '@pi-spi/qrcode';

const DEFAULT_FORM = {
  alias: '3497a720-ab11-4973-9619-534e04f263a1',
  countryCode: 'CI',
  qrType: 'STATIC' as QrType,
  referenceLabel: 'SANDBOX_DEMO',
  amount: '2500',
};

const COUNTRIES = [
  { code: 'BJ', label: 'Bénin (BJ)' },
  { code: 'BF', label: 'Burkina Faso (BF)' },
  { code: 'CI', label: 'Côte d’Ivoire (CI)' },
  { code: 'ML', label: 'Mali (ML)' },
  { code: 'NE', label: 'Niger (NE)' },
  { code: 'SN', label: 'Sénégal (SN)' },
  { code: 'TG', label: 'Togo (TG)' },
  { code: 'GW', label: 'Guinée-Bissau (GW)' },
];

function sanitizeAmount(input: string): string | undefined {
  const trimmed = input.trim();
  return trimmed === '' ? undefined : trimmed;
}

export default function Home() {
  const [form, setForm] = useState(DEFAULT_FORM);
  const [payload, setPayload] = useState('');
  const [svg, setSvg] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showAdvanced, setShowAdvanced] = useState(false);
  const [copied, setCopied] = useState(false);

  const amountValue = useMemo(() => sanitizeAmount(form.amount), [form.amount]);

  const regenerate = useCallback(
    async (nextForm: typeof form) => {
      setIsLoading(true);
      setError(null);
      try {
        const amount = sanitizeAmount(nextForm.amount);
        const input = {
          alias: nextForm.alias.trim(),
          countryCode: nextForm.countryCode.trim().toUpperCase(),
          qrType: nextForm.qrType,
          referenceLabel: nextForm.referenceLabel.trim(),
          ...(amount ? { amount } : {}),
        };

        const [generatedSvg, generatedPayload] = await Promise.all([
          generateQrCodeSvg(input, {
            size: 320,
            margin: 0,
            logoDataUrl: PISPI_QRCODE_LOGO_DATA_URL,
            logoPaddingRatio: 0,
          }),
          Promise.resolve(buildPayloadString(input)),
        ]);

        setSvg(generatedSvg);
        setPayload(generatedPayload);
      } catch (generationError: any) {
        console.error('Erreur lors de la génération du QR Code', generationError);
        setError(generationError?.message ?? String(generationError));
        setSvg(null);
        setPayload('');
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  useEffect(() => {
    void regenerate(DEFAULT_FORM);
  }, [regenerate]);

  const handleSubmit = useCallback(
    async (event: FormEvent<HTMLFormElement>) => {
      event.preventDefault();
      await regenerate(form);
    },
    [form, regenerate]
  );

  const handleInputChange = useCallback(
    (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      const { name, value } = event.target;
      setForm((previous) => {
        if (name === 'qrType') {
          const nextType = value as QrType;
          return {
            ...previous,
            qrType: nextType,
            referenceLabel: nextType === 'DYNAMIC' ? '' : previous.referenceLabel || 'SANDBOX_DEMO',
          };
        }
        return { ...previous, [name]: value };
      });
      setCopied(false);
    },
    []
  );

  const handleCopyPayload = useCallback(async () => {
    if (!payload) {
      return;
    }
    try {
      await navigator.clipboard.writeText(payload);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch (copyError) {
      console.error('Erreur lors de la copie de la payload', copyError);
    }
  }, [payload]);

  const handleDownloadSvg = useCallback(() => {
    if (!svg) {
      return;
    }
    const blob = new Blob([svg], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'pi-spi-qr.svg';
    link.click();
    URL.revokeObjectURL(url);
  }, [svg]);

  return (
    <div className="min-h-screen bg-zinc-100 py-16 font-sans text-zinc-900 dark:bg-zinc-900 dark:text-zinc-50">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-12 rounded-3xl bg-white px-6 py-12 shadow-xl dark:bg-zinc-950 sm:px-10 lg:px-16">
        <header className="space-y-4">
          <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
            Générateur de QR Code PI-SPI
          </h1>
          <p className="max-w-3xl text-lg text-zinc-600 dark:text-zinc-400">
            Cette application Next.js utilise le SDK <code>@pi-spi/qrcode</code> pour
            construire la payload EMV et produire un QR Code SVG. Renseignez vos
            paramètres, visualisez le rendu, copiez la charge utile ou téléchargez
            l&apos;image prête à être imprimée.
          </p>
        </header>

        <section className="grid gap-10 lg:grid-cols-[minmax(0,420px),1fr]">
          <form
            onSubmit={handleSubmit}
            className="flex flex-col gap-6 rounded-3xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900"
          >
            <div className="space-y-2">
              <label className="text-sm font-medium" htmlFor="countryCode">
                Pays (UEMOA)
              </label>
              <select
                id="countryCode"
                name="countryCode"
                value={form.countryCode}
                onChange={handleInputChange}
                className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50"
              >
                {COUNTRIES.map((country) => (
                  <option key={country.code} value={country.code}>
                    {country.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium" htmlFor="alias">
                Alias (UUID v4)
              </label>
              <input
                id="alias"
                name="alias"
                type="text"
                value={form.alias}
                onChange={handleInputChange}
                className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50"
                required
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium" htmlFor="qrType">
                Type de QR Code
              </label>
              <select
                id="qrType"
                name="qrType"
                value={form.qrType}
                onChange={handleInputChange}
                className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50"
              >
                <option value="STATIC">Statique</option>
                <option value="DYNAMIC">Dynamique</option>
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium" htmlFor="referenceLabel">
                {form.qrType === 'DYNAMIC'
                  ? 'Identifiant unique de la transaction (txId)'
                  : 'Reference Label'}
              </label>
              <input
                id="referenceLabel"
                name="referenceLabel"
                type="text"
                value={form.referenceLabel}
                onChange={handleInputChange}
                className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50"
                placeholder={
                  form.qrType === 'DYNAMIC'
                    ? 'C-2025-11-12-125253-001'
                    : 'Identifiant caisse, transaction, etc.'
                }
                required
              />
              {form.qrType !== 'DYNAMIC' && (
                <p className="text-xs text-zinc-500 dark:text-zinc-400">
                  Utilisez un identifiant métier (caisse, boutique, transaction...).
                </p>
              )}
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium" htmlFor="amount">
                Montant (optionnel)
              </label>
              <input
                id="amount"
                name="amount"
                type="text"
                inputMode="numeric"
                value={form.amount}
                onChange={handleInputChange}
                className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50"
                placeholder="Ex. 2500"
              />
            </div>
            <button
              type="submit"
              className="mt-2 inline-flex items-center justify-center rounded-full bg-amber-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-zinc-50 disabled:cursor-not-allowed disabled:bg-amber-300 dark:focus:ring-offset-zinc-950"
              disabled={isLoading}
            >
              {isLoading ? 'Génération...' : 'Générer le QR Code'}
            </button>

            {error && (
              <p className="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                Erreur : {error}
              </p>
            )}


          </form>

          <div className="flex flex-col gap-6">
            <div className="flex flex-col items-center gap-4 rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-md dark:border-zinc-800 dark:bg-zinc-900">
              <div className="text-sm uppercase tracking-[0.3em] text-amber-500">
                Aperçu QR Code
              </div>
              <div className="flex flex-col items-center justify-center" style={{ border: '4px solid #542D00' }}>
                <div
                  className=" w-[350px]  flex items-center justify-center p-2"
                  style={{ backgroundColor: '#542D00' }}
                >
                  <img
                    src="/logo.png"
                    alt="Logo PI-SPI"
                    className="h-10"
                  />
                </div>
                <div
                  className="flex h-[320px] w-[320px] items-center justify-center bg-white p-4 "
                  dangerouslySetInnerHTML={{ __html: svg ?? '' }}
                />
              </div>
              <div className="flex flex-wrap items-center justify-center gap-3">
                <button
                  type="button"
                  onClick={handleDownloadSvg}
                  disabled={!svg}
                  className="inline-flex items-center rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-600 transition hover:border-amber-500 hover:text-amber-500 disabled:cursor-not-allowed disabled:border-zinc-200 disabled:text-zinc-300 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-amber-400 dark:hover:text-amber-400"
                >
                  Télécharger SVG
                </button>
                <button
                  type="button"
                  onClick={handleCopyPayload}
                  disabled={!payload}
                  className="inline-flex items-center rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-600 transition hover:border-amber-500 hover:text-amber-500 disabled:cursor-not-allowed disabled:border-zinc-200 disabled:text-zinc-300 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-amber-400 dark:hover:text-amber-400"
                >
                  {copied ? 'Payload copiée' : 'Copier la payload'}
                </button>
              </div>
            </div>


          </div>
        </section>

        <button
          type="button"
          onClick={() => setShowAdvanced((previous) => !previous)}
          className="w-full inline-flex items-center justify-center rounded-full border border-zinc-300 px-4 py-2 text-xs font-medium uppercase tracking-[0.3em] text-zinc-600 transition hover:border-amber-500 hover:text-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-amber-400 dark:hover:text-amber-400 dark:focus:ring-offset-zinc-950"
        >
          Mode avancé {showAdvanced ? '−' : '+'}
        </button>
        <div
          className={`grid w-full max-w-full gap-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-6 text-sm text-zinc-700 transition dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 ${showAdvanced ? 'opacity-100 mt-6' : 'opacity-0 pointer-events-none hidden'
            }`}
        >
          <div className="space-y-1">
            <h2 className="text-base font-semibold text-zinc-900 dark:text-zinc-50">
              Paramètres actuels
            </h2>
            <p className="text-xs text-zinc-500 dark:text-zinc-400">
              Utiles pour vérifier les valeurs envoyées au SDK.
            </p>
          </div>
          <ul className="grid gap-1 text-xs">
            <li>
              <span className="font-medium text-zinc-900 dark:text-zinc-100">
                Alias&nbsp;:
              </span>{' '}
              {form.alias}
            </li>
            <li>
              <span className="font-medium text-zinc-900 dark:text-zinc-100">
                Pays&nbsp;:
              </span>{' '}
              {form.countryCode}
            </li>
            <li>
              <span className="font-medium text-zinc-900 dark:text-zinc-100">
                Type&nbsp;:
              </span>{' '}
              {form.qrType}
            </li>
            <li>
              <span className="font-medium text-zinc-900 dark:text-zinc-100">
                Référence&nbsp;:
              </span>{' '}
              {form.referenceLabel}
            </li>
            <li>
              <span className="font-medium text-zinc-900 dark:text-zinc-100">
                Montant&nbsp;:
              </span>{' '}
              {amountValue ?? 'Non défini'}
            </li>
          </ul>

          <div className="rounded-2xl border border-zinc-200 bg-black text-green-400 shadow-inner dark:border-zinc-700 w-full overflow-x-auto ">
            <div className="flex items-center justify-between border-b border-zinc-800 px-5 py-3 text-xs uppercase tracking-[0.3em] text-zinc-400">
              Payload EMV
            </div>
            <pre className="max-h-64 px-5 py-4 text-xs leading-6">
              <code>{payload || '—'}</code>
            </pre>
          </div>
        </div>

        <footer className="rounded-2xl border border-dashed border-amber-400 bg-amber-50 px-6 py-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
          Cette page est un exemple d'application React/ Next.js qui met en oeuvre le SDK <a href="https://www.npmjs.com/package/@pi-spi/qrcode" target="_blank" rel="noopener noreferrer">@pi-spi/qrcode</a>. Vous pouvez l&apos;adapter ou l&apos;intégrer dans
          votre propre application React.
        </footer>
      </div>
    </div>
  );
}
