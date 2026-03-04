# PISPI QR — Django Demo

Interface web pour générer et décoder des QR codes BCEAO PISPI (zone UEMOA).

## Installation

# Se placer à la racine du projet
cd django

```bash
# 1. Créer un environnement virtuel
python3 -m venv venv

source venv/bin/activate

# 2. Installer les dépendances
pip install -r requirements.txt

# 3. Lancer le serveur
python manage.py runserver
```

Ouvrir **http://localhost:8000** dans votre navigateur.

## Fonctionnalités

- ✦ **Générer** un QR code PISPI (statique ou dynamique)
- ⌁ **Décoder** un payload existant
- Affichage du QR code en SVG
- Copie du payload en un clic
- Support des 8 pays UEMOA

## Structure

```
django/
├── manage.py
├── requirements.txt
├── pispi_demo/
│   ├── settings.py
│   └── urls.py
└── qrapp/
    ├── views.py
    ├── urls.py
    └── templates/qrapp/
        └── index.html
```
