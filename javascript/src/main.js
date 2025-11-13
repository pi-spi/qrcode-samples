import './style.css';
import {
  buildPayloadString,
  generateQrCodeSvg,
  PISPI_QRCODE_LOGO_DATA_URL,
} from '@pi-spi/qrcode';

const form = document.querySelector('#qr-form');
const qrContainer = document.querySelector('#qr-container');
const payloadOutput = document.querySelector('#payload');
const downloadButton = document.querySelector('#download');
const advancedToggle = document.querySelector('#advanced-toggle');
const advancedPanel = document.querySelector('#advanced-panel');
const copyButton = document.querySelector('#copy-payload');

async function renderQrFromForm() {
  const formData = new FormData(form);
  const alias = formData.get('alias').toString().trim();
  const country = formData.get('country').toString().trim().toUpperCase();
  const reference = formData.get('reference').toString().trim();
  const qrType = formData.get('qrType')?.toString() === 'DYNAMIC' ? 'DYNAMIC' : 'STATIC';
  const amountRaw = formData.get('amount').toString().trim();
  const amount = amountRaw === '' ? undefined : Number(amountRaw);

  const payload = buildPayloadString({
    alias,
    countryCode: country,
    qrType,
    referenceLabel: reference,
    ...(amount !== undefined && !Number.isNaN(amount) ? { amount } : {}),
  });

  const svg = await generateQrCodeSvg(
    {
      alias,
      countryCode: country,
      qrType,
      referenceLabel: reference,
      ...(amount !== undefined && !Number.isNaN(amount) ? { amount } : {}),
    },
    {
      size: 320,
      margin: 0,
      logoDataUrl: PISPI_QRCODE_LOGO_DATA_URL,
      logoPaddingRatio: 0
    }
  );

  qrContainer.innerHTML = svg;
  payloadOutput.textContent = payload;

  downloadButton.disabled = false;
  downloadButton.dataset.payload = svg;
  payloadOutput.dataset.payload = payload;
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  try {
    downloadButton.disabled = true;
    await renderQrFromForm();
  } catch (error) {
    console.error('Erreur lors de la génération du QR Code', error);
    payloadOutput.textContent = `Erreur: ${error?.message ?? error}`;
  }
});

downloadButton.addEventListener('click', () => {
  const svg = downloadButton.dataset.payload;
  if (!svg) {
    return;
  }

  const blob = new Blob([svg], { type: 'image/svg+xml' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'pi-spi-qr.svg';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
});

copyButton.addEventListener('click', async () => {
  const payload = payloadOutput.dataset.payload;
  if (!payload) {
    return;
  }
  try {
    await navigator.clipboard.writeText(payload);
    copyButton.textContent = 'Payload copiée';
    setTimeout(() => {
      copyButton.textContent = 'Copier la payload';
    }, 1500);
  } catch (error) {
    console.error('Erreur lors de la copie', error);
  }
});

advancedToggle.addEventListener('click', () => {
  advancedPanel.classList.toggle('hidden');
});

renderQrFromForm().catch((error) => {
  console.error('Erreur initiale de génération', error);
});
