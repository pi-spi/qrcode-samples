# Exemple Gradle - SDK PI-SPI QR Code

Projet Gradle autonome montrant comment utiliser le SDK `int.bceao.pispi:qrcode`.

## Prérequis

- Java 21+
- Gradle 8.x (ou utiliser le wrapper `./gradlew`)
- Le SDK installé localement ou publié sur Maven Central

## Exécuter

```bash
gradle run
```

Ou avec le wrapper (si disponible) :

```bash
./gradlew run
```

## Dépendance

### Groovy DSL (`build.gradle`)

```groovy
dependencies {
    implementation 'int.bceao.pispi:qrcode:0.1.2'
}
```

### Kotlin DSL (`build.gradle.kts`)

```kotlin
dependencies {
    implementation("int.bceao.pispi:qrcode:0.1.2")
}
```

## Structure

```
gradle-example/
├── build.gradle
├── settings.gradle
├── README.md
└── src/main/java/com/example/Main.java
```

## Fonctionnalités démontrées

1. Génération d'une payload STATIC avec montant
2. Validation d'une payload et extraction des données
3. Génération d'une payload DYNAMIC sans montant

## Générer le wrapper Gradle

Pour distribuer le projet avec un wrapper :

```bash
gradle wrapper
```

Cela crée `gradlew`, `gradlew.bat` et le dossier `gradle/wrapper/`.
