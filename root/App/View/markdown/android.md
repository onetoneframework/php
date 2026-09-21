# Android platform (`res/Platform/Android`)

Gradle **Kotlin** application under **`CloverFramework/`**. Application id **`com.cloverframework.library`**: one launcher activity (`main.activity.MainActivity`) and Kotlin helpers under **`main.common`** (location, network, device utilities, audio, Bluetooth, etc.). Paths below follow **`res/Platform/Android/CloverFramework`**.

## Table of contents

- [Version catalog and SDK](#version-catalog-and-sdk)
- [Layout](#layout)
- [Manifest: permissions and entry activity](#manifest-permissions-and-entry-activity)
- [Application behavior (MainActivity)](#application-behavior-mainactivity)
- [Kotlin helper packages](#kotlin-helper-packages)
- [Build, test, run](#build-test-run)
- [ProGuard and release](#proguard-and-release)

---

## Version catalog and SDK

`gradle/libs.versions.toml` pins, among others:

| Key | Version |
|-----|---------|
| Android Gradle Plugin (`agp`) | `8.10.0` |
| Kotlin | `2.0.21` |
| `androidx.core:core-ktx` | `1.10.1` |
| AppCompat | `1.6.1` |
| Material | `1.10.0` |
| Play services location | `21.3.0` |
| JUnit (unit) | `4.13.2` |
| AndroidX JUnit / Espresso | `1.1.5` / `3.5.1` |

`app/build.gradle.kts` (current tree):

- **`namespace`** / **`applicationId`**: `com.cloverframework.library`
- **`compileSdk`**: `36`
- **`minSdk`**: `35`
- **`targetSdk`**: `36`
- **Java** `sourceCompatibility` / **Kotlin** `jvmTarget`: **11**
- **Dependencies** (implementation): `androidx.core:core-ktx`, `appcompat`, `material`, `play-services-location`, plus `junit` / `androidx.junit` / `espresso` for tests.

So: **very new SDK floor** (`minSdk 35`) with **JVM 11** bytecode for app code.

---

## Layout

```
res/Platform/Android/CloverFramework/
  build.gradle.kts
  settings.gradle.kts
  gradle.properties
  gradle/wrapper/...
  gradle/libs.versions.toml
  gradlew, gradlew.bat
  app/
    build.gradle.kts
    proguard-rules.pro
    src/main/
      AndroidManifest.xml
      kotlin/main/activity/MainActivity.kt
      kotlin/main/common/*.kt
      res/...
    src/test/java/.../ExampleUnitTest.java
    src/androidTest/java/.../ExampleInstrumentedTest.java
```

Package segments in source are literally **`main.activity`**, **`main.common`** (no `com.cloverframework` prefix in the Kotlin path; the **namespace** in Gradle is `com.cloverframework.library`).

---

## Manifest: permissions and entry activity

`app/src/main/AndroidManifest.xml` declares:

| Permission | Purpose (typical) |
|------------|-------------------|
| `INTERNET` | Network I/O |
| `POST_NOTIFICATIONS` | Android 13+ notification channel prompts |
| `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION`, `ACCESS_BACKGROUND_LOCATION` | Location (including background if granted) |
| `RECORD_AUDIO` | Microphone |
| `ACCESS_NETWORK_STATE` | Connectivity checks |
| `BLUETOOTH`, `BLUETOOTH_ADMIN`, `BLUETOOTH_CONNECT` | Classic / BLE / Android 12+ connect |

**Application** element: `Theme.CloverFramework`, backup rules `@xml/backup_rules`, data extraction rules `@xml/data_extraction_rules`, launcher icons `ic_launcher` / `ic_launcher_round`.

**Single activity** `main.activity.MainActivity` — `android:exported="true"`, `MAIN` / `LAUNCHER` intent filter.

---

## Application behavior (MainActivity)

`MainActivity` (`main.activity.MainActivity`):

- Extends **`AppCompatActivity`** and implements **`OnLocationUpdateListener`** (from `LocationHelper`).
- On **`onCreate`**: inflates `R.layout.activity_main`, constructs **`LocationHelper`**, and if fine/coarse location is not yet granted, launches **`RequestMultiplePermissions`** for `ACCESS_FINE_LOCATION` and `ACCESS_COARSE_LOCATION`.
- On grant: **`locationHelper.startLocationUpdates(this)`**; on deny: a short **Toast** “Location permission is required.”
- **`onResume`**: if permission already held, starts location updates again.
- The class is documented in-source as continuously receiving **location updates while the Activity is in the foreground** (as implemented in `LocationHelper`).

This is the **primary user-facing flow** in the current tree: **permission gating** + **foreground location**.

---

## Kotlin helper packages

Under `app/src/main/kotlin/main/common/` the repository currently includes (file names):

| File | Role (from name / typical use) |
|------|----------------------------------|
| `LocationHelper.kt` | Fused / Play services location, used by `MainActivity` |
| `NetworkClient.kt` | HTTP / API client surface |
| `DeviceUtility.kt` | Device info / capabilities |
| `AudioRecorderHelper.kt` | Audio capture |
| `BluetoothHelper.kt` | Bluetooth |
| `GrantUtility.kt` | Permission / system grant helpers |
| `GlideUtility.kt` | Image loading (Glide) |
| `Firebase.kt` | Firebase integration stub or wiring |
| `Common.kt` | Shared app helpers |
| `SetTouchWithDuration.kt`, `SetTouchWithDuraration.kt` | Touch timing utilities (note duplicate spellings in filenames) |

Treat these as **in-app utilities**, not a published SDK, unless the team packages an AAR separately.

---

## Build, test, run

**Working directory**

```text
res/Platform/Android/CloverFramework
```

**Assemble debug APK**

```bash
./gradlew assembleDebug
```

**Windows**

```bat
gradlew.bat assembleDebug
```

**Unit tests (JVM)**

```bash
./gradlew test
```

**Instrumented tests** on device/emulator: use Android Studio or `./gradlew connectedDebugAndroidTest` when the `android` block exposes the androidTest default config (module has `src/androidTest` with example tests).

**Install on device** (typical)

```bash
./gradlew installDebug
```

Requires `adb` and a device with USB debugging; `minSdk 35` means only **API 35+** devices (or emulators with that system image) run this build without manifest overrides.

---

## ProGuard and release

`app/build.gradle.kts` `release` build type: `isMinifyEnabled = false`, ProGuard files `proguard-android-optimize.txt` + `app/proguard-rules.pro`. Turning on R8/ProGuard for release would require **rules** for Play services, Firebase, Glide, etc.—do not enable until those rules exist and tests pass.

**Signing** for release is **not** stored in this doc; use Android Studio or CI with **injected** keystore credentials. Never commit keystores or passwords.
