# Java platform (`res/Platform/Java`)

A **single Maven module** at **`CloverFramework/`** with coordinates **`org.cloverframework:CloverFramework:1.0-SNAPSHOT`**. The tree is a **JVM library** of static helper classes under **`org.cloverframework.library`**, built with **Gson**, **Javacv (OpenCV)**, **Webcam Capture**, **Apache PDFBox**, **MariaDB JDBC**, and more. The snippets below are taken from the **actual** `pom.xml` and `*.java` files in this repo.

## Table of contents

- [Maven metadata and Java level](#maven-metadata-and-java-level)
- [Dependencies (complete list from pom.xml)](#dependencies-complete-list-from-pomxml)
- [Build plugins](#build-plugins)
- [Package `org.cloverframework.library` — what each class is for](#package-orgcloverframeworklibrary--what-each-class-is-for)
- [Code sketches from the source](#code-sketches-from-the-source)
- [Commands](#commands)

---

## Maven metadata and Java level

`CloverFramework/pom.xml` declares:

```xml
<groupId>org.cloverframework</groupId>
<artifactId>CloverFramework</artifactId>
<version>1.0-SNAPSHOT</version>
```

**Properties**

- `maven.compiler.source` and `maven.compiler.target`: **21** (in `<properties>`)
- `project.build.sourceEncoding`: **UTF-8**

**Important:** the same POM also configures `maven-compiler-plugin` with **`<source>11</source>` and `<target>11</target>`** (version `3.8.1`). In practice the **plugin block often overrides** the property pair—after checkout, run `mvn -q help:effective-pom` or your IDE’s effective POM to see which level **actually** compiles. The physical `.java` files in `src/main/java` are written to work as **plain Java 11+** feature-wise.

`Main.java` at `org.cloverframework` exists and currently has an **empty** `public static void main` (imports `BinaryEncryptor` and `Scheduler` but no body) — a stub entrypoint for local experiments.

---

## Dependencies (complete list from pom.xml)

| GroupId | ArtifactId | Version (pinned) | Typical use in this project |
|---------|------------|------------------|-----------------------------|
| org.bytedeco | javacv-platform | 1.5.5 | OpenCV / Javacv in `OpenCV` |
| org.apache.pdfbox | pdfbox | 3.0.5 | `PDF` |
| com.github.sarxos | webcam-capture | 0.3.12 | `WebcamDevice` |
| org.mariadb.jdbc | mariadb-java-client | 3.3.3 | `Mysql` (JDBC) |
| com.google.code.gson | gson | 2.8.9 | `Json` |
| javax.xml.bind | jaxb-api | 2.3.1 | XML/JAXB-era APIs if used |

All are **compile** dependencies; there is no `test` scope block in the excerpted POM for JUnit in the `dependencies` section of the file we read—if you add tests, add JUnit 5 or 4 in `pom.xml` first.

---

## Build plugins

- **`maven-compiler-plugin`**: `3.8.1`, with explicit `source`/`target` (see [above](#maven-metadata-and-java-level)).
- Packaging defaults to **JAR**; `mvn package` produces `target/CloverFramework-1.0-SNAPSHOT.jar` (exact final name may include classifier depending on POM—check `target/` after a build).

---

## Package `org.cloverframework.library` — class inventory

There are **14** source files; each is one public API surface:

| Class | Role |
|-------|------|
| `Array` | Array helpers |
| `BinaryEncryptor` | Binary/encryption |
| `Common` | `isNumeric`, `cutString`, etc. — [sketch below](#code-sketches-from-the-source) |
| `DateTime` | Date/time utilities |
| `FileSystem` | File I/O helpers |
| `HttpURL` | URL/HTTP helpers |
| `Json` | Gson encode/decode/JSON validity — [sketch below](#code-sketches-from-the-source) |
| `Mysql` | MariaDB/JDBC access |
| `Network` | Site reachability, file/image download, ping, local IP — [sketch below](#code-sketches-from-the-source) |
| `OpenCV` | Javacv / OpenCV |
| `PDF` | Apache PDFBox |
| `Reflection` | Reflection utilities |
| `Scheduler` | `ScheduledExecutorService` wrappers — [sketch below](#code-sketches-from-the-source) |
| `WebcamDevice` | Webcam (Sarxos) |

`org.cloverframework.Main` is a **separate** top-level class (not under `library`) with a **stub** `main` you can use to call the above from the command line.

---

## Code sketches from the source

**`Common.java`**

- `isNumeric(String)` — true iff every char is a digit.
- `cutString(text, max, prefix, usePrefix)` — truncates with optional suffix when `text.length() > max` and `max != -1`.

**`Json.java`**

- `encodeWithObject(Object)` / `encode(String)` using **Gson** `toJson`.
- `decodeWithObject(String, Object)` — `fromJson` to the given object’s class.
- `isValidJson(String)` — `JsonParser.parseString` in a try/catch; false on `JsonSyntaxException`.

**`Network.java` (partial)**

- `isWebsiteAvailable(url)` — `HttpURLConnection` **HEAD**, 3s timeouts, success for status **200–399**.
- `downloadImage(url, path)` — `ImageIO.read` + `ImageIO.write` as PNG.
- `downloadFile(url, path)` — stream copy to `FileOutputStream` with 1024-byte buffer.
- `ping(host)` — `InetAddress.getByName(host).isReachable(3000)`.
- `getLocalIpAddress()` — `InetAddress.getLocalHost().getHostAddress()`.

**`Scheduler.java`**

- Private `runTask(Runnable, period, TimeUnit)` using **`Executors.newScheduledThreadPool(1)`** and `scheduleAtFixedRate(task, 0, period, unit)`.
- Public one-liners: `runTaskEverySeconds`, `runTaskEveryMinutes`, `runTaskEveryHours`, `runTaskEveryDays` delegating to `runTask`.

Extending behavior means **editing** `CloverFramework/src/main/java/org/cloverframework/library/*.java` (or adding classes there) and rebuilding with Maven.

---

## Commands

**Working directory**

```text
res/Platform/Java/CloverFramework
```

```bash
mvn clean test
mvn package
mvn install
```

After `mvn package`, on classpath:

```bash
java -cp target/CloverFramework-1.0-SNAPSHOT.jar org.cloverframework.Main
```

(Adjust JAR name if the build adds classifiers; list `target/*.jar` first.)
