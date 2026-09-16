# PI-SPI QR, demo PHP

Interface web pour générer et décoder des QR codes PI-SPI (zone UEMOA), en PHP
sans framework. Même écran et mêmes endpoints que la démo Django, pour que les
deux se comparent directement.

## Le SDK utilisé

BCEAO publie des SDK PI-SPI en JavaScript, Python, Flutter et Java. Il n'y en a
pas en PHP, alors qu'une boutique WooCommerce ou un backend Laravel tourne déjà
sous PHP et doit sinon assembler le payload EMV et le CRC16-CCITT à la main.

Cette démo s'appuie donc sur [`catidegla/pi-spi-qr`](https://packagist.org/packages/catidegla/pi-spi-qr),
un paquet MIT indépendant, sans dépendance, qui construit, relit et valide les
payloads PI-SPI. La logique tient dans [`src/Qr.php`](src/Qr.php), donc si un SDK
PHP officiel arrive, il y a un seul fichier à changer.

Le rendu de l'image passe par `bacon/bacon-qr-code`. Le paquet PI-SPI ne dessine
pas le QR : dessiner un QR est un problème déjà résolu en PHP, le profil PI-SPI
ne l'est pas. Remplacez-le par la bibliothèque QR que votre projet utilise déjà.

## Installation

```bash
# Se placer dans le dossier de la démo
cd php

# 1. Installer les dépendances
composer install

# 2. Lancer le serveur
composer start
```

Ouvrir **http://localhost:8000** dans votre navigateur.

Prérequis : PHP 8.2 ou plus récent, Composer 2, et l'extension `iconv` que
`bacon/bacon-qr-code` demande. Aucune base de données, aucun framework.

## Fonctionnalités

- ✦ **Générer** un QR code PI-SPI (statique ou dynamique)
- ⌁ **Décoder** un payload existant
- Affichage du QR code en SVG
- Copie du payload en un clic
- Support des 8 pays UEMOA

## Endpoints

Identiques à ceux de la démo Django, au même contrat JSON.

| Méthode | Chemin      | Corps                                                  | Réponse                       |
| :------ | :---------- | :----------------------------------------------------- | :---------------------------- |
| `GET`   | `/`         |                                                         | l'interface                   |
| `POST`  | `/generate` | `qr_type`, `merchant_id`, `country`, `amount`, `reference` | `{payload, decoded, svg}`  |
| `POST`  | `/decode`   | `payload`                                               | `{decoded}`                   |

Une erreur rend `{error}`, plus `{step}` valant `encode`, `decode` ou `svg`
quand la saisie est valide mais que le traitement échoue. `400` pour une entrée
refusée avant le SDK, `422` pour un refus du SDK.

Le décodage renvoie **tous** les problèmes d'un payload plutôt que de s'arrêter
au premier, parce que celui qui tient un QR qui ne passe pas veut la liste
entière et non un aller-retour par faute :

```json
{
  "error": "Payload invalide : missing the scheme identifier in 36-00, so this is not a PI-SPI QR ; checksum does not match: the payload carries FFFF, its contents give 106D ; missing the alias in 36-01",
  "step": "decode"
}
```

## Les montants sont des francs entiers

Le XOF n'a pas de sous-unité. `1500` vaut mille cinq cents francs, pas quinze.
La démo refuse un montant fractionnaire au lieu de l'arrondir en silence, parce
qu'un `amount` divisé par 100 quelque part en amont produit un QR qui scanne,
paraît correct et débite cent fois trop peu.

## Structure

```
php/
├── composer.json
├── public/
│   ├── index.php          routeur, 3 chemins
│   └── images/
├── src/
│   ├── Qr.php             génération, décodage, rendu SVG
│   └── InvalidInput.php   une entrée refusée, avec son code HTTP et son étape
└── templates/
    └── index.php          l'interface, reprise de la démo Django
```
