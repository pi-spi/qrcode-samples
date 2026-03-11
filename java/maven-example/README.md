# Exemple Maven - SDK PI-SPI QR Code

Projet Maven autonome montrant comment utiliser le SDK `int.bceao.pispi:qrcode`.

## Prérequis

- Java 21+
- Maven 3.x
- Le SDK installé localement ou publié sur Maven Central

## Exécuter

```bash
mvn compile exec:java
```

## Dépendance

```xml
<dependency>
    <groupId>int.bceao.pispi</groupId>
    <artifactId>qrcode</artifactId>
    <version>0.1.2</version>
</dependency>
```

## Structure

```
maven-example/
├── pom.xml
├── README.md
└── src/main/java/com/example/Main.java
```

## Fonctionnalités démontrées

1. Génération d'une payload STATIC avec montant
2. Validation d'une payload et extraction des données
3. Génération d'une payload DYNAMIC sans montant
