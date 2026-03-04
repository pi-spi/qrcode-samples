import json
from django.shortcuts import render
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt

from bceao_pispi_qrcode.pispi_qr_payload import PispiQrPayload
from bceao_pispi_qrcode.pispi_qr_generator import PispiQrGenerator
from bceao_pispi_qrcode.models.enums import PispiQrCountry, PispiQrType
from bceao_pispi_qrcode.models.models import PispiQrPayloadInput
from bceao_pispi_qrcode.models.exceptions import (
    PispiQrPayloadInputException,
    PispiQrPayloadDecodeException,
    PispiQrGeneratorException,
)


COUNTRIES = [
    ('BJ', 'Bénin'),
    ('BF', 'Burkina Faso'),
    ('CI', "Côte d'Ivoire"),
    ('GW', 'Guinée-Bissau'),
    ('ML', 'Mali'),
    ('NE', 'Niger'),
    ('SN', 'Sénégal'),
    ('TG', 'Togo'),
]

QR_TYPES = [
    ('STATIC', 'Statique'),
    ('DYNAMIC', 'Dynamique'),
]


def index(request):
    return render(request, 'qrapp/index.html', {
        'countries': COUNTRIES,
        'qr_types': QR_TYPES,
    })


@csrf_exempt
def generate_qr(request):
    if request.method != 'POST':
        return JsonResponse({'error': 'Méthode non autorisée'}, status=405)

    try:
        data = json.loads(request.body)

        qr_type_str = data.get('qr_type', 'DYNAMIC')
        merchant_id = data.get('merchant_id', '').strip()
        country_code = data.get('country', 'SN')
        amount_raw = data.get('amount', '')
        reference = data.get('reference', '').strip()

        # Validation manuelle avant d'appeler la lib
        if not merchant_id:
            return JsonResponse({'error': 'L\'identifiant marchand est requis.'}, status=400)

        try:
            qr_type = PispiQrType[qr_type_str]
        except KeyError:
            return JsonResponse({'error': f'Type de QR invalide : {qr_type_str}'}, status=400)

        try:
            country = PispiQrCountry[country_code]
        except KeyError:
            return JsonResponse({'error': f'Code pays invalide : {country_code}'}, status=400)

        amount = float(amount_raw) if amount_raw else None

        payload_input = PispiQrPayloadInput(
            qr_type,
            merchant_id,
            country,
            amount=amount,
            reference_label=reference or None,
        )

    except json.JSONDecodeError:
        return JsonResponse({'error': 'Corps de la requête JSON invalide.'}, status=400)
    except ValueError as e:
        return JsonResponse({'error': f'Valeur invalide : {e}'}, status=400)

    # Encodage
    try:
        payload = PispiQrPayload.encode(payload_input)
    except PispiQrPayloadInputException as e:
        code = f' (code : {e.error.value})' if e.error else ''
        return JsonResponse({
            'error': f'Erreur d\'encodage : {e.message}{code}',
            'step': 'encode',
        }, status=422)

    # Décodage de vérification
    try:
        decoded = PispiQrPayload.decode(payload)
    except PispiQrPayloadDecodeException as e:
        code = f' (code : {e.error.value})' if e.error else ''
        return JsonResponse({
            'error': f'Erreur de décodage : {e.message}{code}',
            'step': 'decode',
        }, status=422)

    # Génération SVG
    try:
        svg = PispiQrGenerator.svg(
            payload,
            margin=10,
            size=330,
            logo_size=60,
            background_color='white')
    except PispiQrGeneratorException as e:
        code = f' (code : {e.error.value})' if e.error else ''
        return JsonResponse({
            'error': f'Erreur de génération SVG : {e.message}{code}',
            'step': 'svg',
        }, status=422)

    return JsonResponse({
        'payload': payload,
        'decoded': decoded.to_dict(),
        'svg': svg,
    })


@csrf_exempt
def decode_qr(request):
    if request.method != 'POST':
        return JsonResponse({'error': 'Méthode non autorisée'}, status=405)

    try:
        data = json.loads(request.body)
    except json.JSONDecodeError:
        return JsonResponse({'error': 'Corps de la requête JSON invalide.'}, status=400)

    payload = data.get('payload', '').strip()
    if not payload:
        return JsonResponse({'error': 'Le payload est requis.'}, status=400)

    try:
        decoded = PispiQrPayload.decode(payload)
        return JsonResponse({'decoded': decoded.to_dict()})
    except PispiQrPayloadDecodeException as e:
        code = f' (code : {e.error.value})' if e.error else ''
        return JsonResponse({
            'error': f'Payload invalide : {e.message}{code}',
            'step': 'decode',
        }, status=422)