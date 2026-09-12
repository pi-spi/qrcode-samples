# PI-SPI QR, démo PHP

Interface web pour générer et décoder des QR Codes PI-SPI (zone UEMOA), en PHP, sans framework.

## Installation

```bash
# Se placer dans le dossier
cd php

# 1. Installer les dépendances
composer install

# 2. Lancer le serveur
composer serve
```

Ouvrir **http://localhost:8000** dans votre navigateur.

## Fonctionnalités

- **Générer** un QR statique, dynamique ou de transfert
- **Décoder** un payload existant, avec la liste des règles enfreintes quand il y en a
- Affichage du QR en SVG, logo PI-SPI au centre
- Affichage du payload EMV brut et des champs relus
- Les 8 pays de l'UEMOA

## Structure

```
php/
├── composer.json
├── assets/
│   └── pi-spi-logo.png
├── public/
│   └── index.php          # routage: GET /, POST /generate, POST /decode
├── src/
│   └── Demo.php           # génération, décodage, rendu SVG
└── templates/
    └── index.php          # le formulaire et l'affichage
```

## Les deux bibliothèques

Il n'existe pas de SDK PI-SPI officiel en PHP, donc cette démo en combine deux.

- **[catidegla/pi-spi-qr](https://packagist.org/packages/catidegla/pi-spi-qr)** construit et relit le payload EMV: les quatre canaux, le CRC16-CCITT, la validation des alias et des pays, et `problems()` qui dit ce qui ne va pas plutôt que de répondre seulement « invalide ». Sans dépendance.
- **[bacon/bacon-qr-code](https://packagist.org/packages/bacon/bacon-qr-code)** dessine l'image.

Le partage est volontaire. Le profil PI-SPI est la partie spécifique au rail, dessiner un QR ne l'est pas.

## Trois détails qui comptent

**Le montant est un entier.** Le XOF n'a pas de sous-unité, son exposant ISO 4217 vaut 0. La démo refuse `1500,50` au lieu de l'arrondir en silence, parce qu'un arrondi invisible ici est un écart qui n'apparaît qu'au rapprochement.

**Correction d'erreur au niveau H.** Poser un logo au centre détruit des modules. H est le seul niveau qui en reconstruit assez, environ 30 pour cent, pour que le code scanne quand même. La marge reste à 4 modules, le minimum imposé pour que le lecteur trouve les bords.

**Le payload est relu avant d'être renvoyé.** `generate` décode ce qu'il vient d'encoder et échoue si le résultat ne se relit pas. Un payload qui ne se relit pas ici est un QR qu'un téléphone refusera à la caisse.

## Endpoints

Le formulaire les utilise, et ils s'appellent aussi directement.

```bash
curl -X POST http://localhost:8000/generate \
  -H 'Content-Type: application/json' \
  -d '{"qr_type":"DYNAMIC","alias":"00000000000000000000000000000000SN01","country":"SN","amount":"1500","reference":"TX-001"}'

curl -X POST http://localhost:8000/decode \
  -H 'Content-Type: application/json' \
  -d '{"payload":"00020136560012int.bceao.pi..."}'
```

`generate` renvoie `payload`, `decoded` et `svg`. `decode` renvoie `valid`, `problems` et `decoded`.

## Prérequis

PHP 8.2 ou plus récent, avec `ext-dom` et `ext-iconv`, et Composer 2. Aucune extension image n'est nécessaire, le rendu est en SVG.

## Laravel

`catidegla/pi-spi-qr` embarque un pont Laravel facultatif: un service provider, une façade, une règle de validation pour les payloads scannés et une commande artisan. Cette démo ne s'en sert pas, pour rester lisible sans framework.
