# Exemples PI-SPI QR Code

Ce workspace regroupe des projets clés en main montrant comment consommer le SDK [`@pi-spi/qrcode`](https://www.npmjs.com/package/@pi-spi/qrcode) dans différents environnements. Chaque démo propose la même expérience utilisateur afin de comparer facilement les implémentations.

## Prérequis

- Node.js 18 LTS ou plus récent (Node 20 LTS recommandé)
- npm 9 ou supérieur

> ℹ️ Certains outils signalent les versions impaires de Node. Pour la production ou la CI, privilégiez une version LTS.

## Structure du dépôt

- `javascript/` : application HTML/JS/Tailwind propulsée par Vite.
- `react/` : application Next.js 16 (React 19) montrant l’usage du SDK dans un stack React moderne.
- `angular/` : application Angular 17 standalone avec Tailwind et un design aligné sur la démo React.

## Tâches communes

Clonez le dépôt principal, puis depuis la racine :

```bash
cd SDKs/qrcode-samples
```

Installez les dépendances propres à chaque démo avant de la lancer.

### JavaScript (Vite)

```bash
cd javascript
npm install
npm run dev        # démarre le serveur de développement
npm run build      # génère le bundle statique dans dist/
```

Le serveur de développement affiche l’URL d’accès (par défaut : http://localhost:5173).

### React (Next.js)

```bash
cd react
npm install
npm run dev        # lance Next.js en mode dev (http://localhost:3000)
npm run build      # produit un build optimisé
```

> Le projet utilise Tailwind CSS et embarque un script `scripts/postinstall.js` pour maintenir la compatibilité de Turbopack avec le SDK.

### Angular

```bash
cd angular
npm install
npm run start      # équivalent à ng serve --open
npm run build      # génère les bundles SSR dans dist/qrcode
```

La démo Angular inclut Tailwind CSS, le support SSR et une configuration `ng build` autorisant les dépendances CommonJS utilisées par `@pi-spi/qrcode`.

## Mettre à jour le SDK

Toutes les démos dépendent du paquet publié `@pi-spi/qrcode`. Après la publication d’une nouvelle version :

1. Modifiez la version du SDK dans chaque `package.json` (JavaScript, React, Angular).
2. Exécutez `npm install` dans chacun des sous-projets.
3. Relancez un build (`npm run build`) pour vérifier le bon fonctionnement.

## Dépannage

- **Avertissements CommonJS** : Angular signale les modules CommonJS (`qrcode`, `dijkstrajs`, `pngjs`). Ils sont déjà listés dans `angular.json` via `allowedCommonJsDependencies`.
- **Accès presse-papiers** : Les navigateurs peuvent bloquer l’accès au presse-papiers hors contexte HTTP/HTTPS (ex. protocole `file://`). Utilisez toujours un serveur local.
- **Version de Node** : Mettez à niveau vers une version LTS si le CLI se plaint de votre runtime Node.

## Licence

Ces exemples font partie du matériel sandbox PI-SPI. Adaptez-les librement pour vos expérimentations ou démonstrations internes. Pour un usage en production, assurez-vous de respecter les directives d’intégration PI-SPI.
