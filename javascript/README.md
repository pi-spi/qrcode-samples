# PI-SPI QR Code Demo (HTML / Tailwind)

Petite application Vite + Tailwind qui consomme la librairie `@pi-spi/qrcode` locale pour générer un QR Code PI-SPI en motif de points avec le logo officiel.

## Scripts

```bash
npm install        # installe les dépendances (inclut @pi-spi/qrcode via file:../qrcode)
npm run dev        # lance le serveur Vite (http://localhost:5173)
npm run build      # génère la version statique dans dist/
npm run preview    # prévisualise la build
```

## Fonctionnalités

- Formulaire alias/pays/référence/montant
- Génération du QR via `generateQrCodeSvg`
- Affichage de la payload EMV brute
- Téléchargement du SVG
- Tailwind pour la mise en forme

> **Note**: le projet se base sur la build locale de la librairie (`../qrcode/dist/index.mjs`). Pensez à lancer `npm run build` dans `../qrcode` avant de démarrer la démo.
