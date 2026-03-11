package com.example;

import bceao.pispi.qrcode.*;

/**
 * Exemple Gradle d'utilisation du SDK PI-SPI QR Code.
 * Exécuter avec : gradle run (ou ./gradlew run)
 */
public class Main {

    public static void main(String[] args) {
        System.out.println("=== Exemple Gradle - SDK PI-SPI QR Code ===\n");

        // 1. Générer une payload STATIC avec montant
        String payload = PispiQrCode.buildPayloadString(
                QrPayloadInput.builder()
                        .alias("3497a720-ab11-4973-9619-534e04f263a1")
                        .countryCode("CI")
                        .qrType(QrType.STATIC)
                        .referenceLabel("CAISSE_A01")
                        .amount(1500L)
                        .build());
        System.out.println("1. Payload STATIC (1500 XOF):");
        System.out.println("   " + payload);
        System.out.println();

        // 2. Valider la payload
        QrValidationResult result = PispiQrCode.isValidPispiQrPayload(payload);
        System.out.println("2. Validation: " + (result.valid() ? "OK" : "ERREUR"));
        if (result.valid() && result.data() != null) {
            System.out.println("   Alias:    " + result.data().alias());
            System.out.println("   Pays:     " + result.data().countryCode());
            System.out.println("   Type:     " + result.data().qrType());
            System.out.println("   Montant:  " + result.data().amount());
        }
        System.out.println();

        // 3. Générer une payload DYNAMIC sans montant
        String payload2 = PispiQrCode.buildPayloadString(
                QrPayloadInput.builder()
                        .alias("3497a720-ab11-4973-9619-534e04f263a1")
                        .countryCode("SN")
                        .qrType(QrType.DYNAMIC)
                        .referenceLabel("Tx-20251112-055052-001")
                        .build());
        System.out.println("3. Payload DYNAMIC (sans montant):");
        System.out.println("   " + payload2);
    }
}
