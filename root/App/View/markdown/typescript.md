# TypeScript platform — build system and `src` layout

This page documents **`res/Platform/Typescript`**: a **private npm package** (`@onetone/typescript-platform`) that ships a **library** of TypeScript/TSX modules for browser use (React hooks, Three.js-related code, service classes, etc.). The repo runs **three separate bundlers** (Vite, esbuild, Webpack) from the same `src/` tree and writes outputs under **`dist/vite`**, **`dist/esbuild`**, and **`dist/webpack`**. The legacy **`prev/`** apps are **excluded** from the root `tsconfig` and must be built from their own directories.

## Table of contents

- [Why three bundlers?](#why-three-bundlers)
- [package.json, exports, and the main entry](#packagejson-exports-and-the-main-entry)
- [npm scripts](#npm-scripts)
- [How entry points are discovered (build-utils)](#how-entry-points-are-discovered-build-utils)
- [TypeScript and path aliases](#typescript-and-path-aliases)
- [Per-tool behavior (Vite, esbuild, Webpack)](#per-tool-behavior-vite-esbuild-webpack)
- [What lives in `src/` (overview)](#what-lives-in-src-overview)
- [Working on disk](#working-on-disk)
- [prev/ and other frontends](#prev-and-other-frontends)

---

<a id="why-three-bundlers"></a>
## Why three bundlers?

The monorepo **legacy** allowed different consumers to pick **Vite** (dev UX + Rollup), **esbuild** (fast, minimal transform), or **Webpack** (older pipelines, explicit externals) without duplicating the whole `src/` tree. The **default** `main` / `module` in `package.json` points to **`dist/esbuild/index.js`**; pick **`dist/vite/...` or `dist/webpack/...`** if your app’s bundler or loader expects that shape.

**Rule of thumb**

- **esbuild output** — fastest rebuild; `bundle: false` means **one output file per source entry** (see below), not a single app bundle.
- **Vite** — Rollup; treats non-relative, non-aliased imports as **external**; emits chunks for shared code.
- **Webpack** — ESM `output` with a custom `externals` function similar in spirit to Vite’s `external` callback.

---

<a id="packagejson-exports-and-the-main-entry"></a>
## package.json, exports, and the main entry

The checked-in `package.json` in `res/Platform/Typescript` (trimmed) looks like this:

```json
{
	"name": "@onetone/typescript-platform",
	"version": "1.0.0",
	"private": true,
	"type": "module",
	"main": "dist/esbuild/index.js",
	"module": "dist/esbuild/index.js",
	"types": "dist/types/index.d.ts",
	"exports": {
		"./*": {
			"types": "./dist/types/*.d.ts",
			"default": "./dist/esbuild/*.js"
		}
	},
	"files": [ "dist" ],
	"scripts": {
		"build": "npm run build:vite && npm run build:esbuild && npm run build:webpack",
		"build:vite": "vite build --config vite.config.mjs",
		"build:esbuild": "node esbuild.config.mjs",
		"build:webpack": "webpack --config webpack.config.mjs",
		"build:types": "tsc -p tsconfig.json --emitDeclarationOnly"
	}
}
```

- **`"type": "module"`** — ESM in `.js` outputs.
- **`main` / `module`**: `dist/esbuild/index.js` — this is the **barrel** built from `src/index.ts` (re-exports a subset of the library; see [src overview](#what-lives-in-src-overview)).
- **`types`**: `dist/types/index.d.ts` after **`build:types`**.
- **`exports`**: a **wildcard** map from `./*` to `dist/types/*.d.ts` and `dist/esbuild/*.js`, so deep imports can resolve to matching files under `dist/esbuild` after build.

**Important:** `build-utils.mjs` still **emits every `.ts`/`.tsx` under `src/`** as its **own** entry in `dist/{vite,esbuild,webpack}`. The barrel `index.ts` is for convenient imports like `@onetone/typescript-platform` (package root), not the only build artifact.

---

<a id="npm-scripts"></a>
## npm scripts

| Script | What it runs | When to use |
|--------|----------------|-------------|
| `npm run build` | `build:vite` **then** `build:esbuild` **then** `build:webpack` | CI or before publishing **all** dist variants. |
| `npm run build:vite` | `vite build --config vite.config.mjs` | When you need **Vite/Rollup** output in `dist/vite/`. |
| `npm run build:esbuild` | `node esbuild.config.mjs` | Default-aligned output in `dist/esbuild/`; **no bundling** of the whole app — per-file ESM. |
| `npm run build:webpack` | `webpack --config webpack.config.mjs` | Webpack ESM output in `dist/webpack/`. |
| `npm run build:types` | `tsc -p tsconfig.json --emitDeclarationOnly` | **Declarations only** → `dist/types/`. |

Run `build:types` when you need `.d.ts` for consumers but do not need to re-run all JS emitters. Run **`build`** when you need **parity** across all three JS pipelines.

---

<a id="how-entry-points-are-discovered-build-utils"></a>
## How entry points are discovered (build-utils)

`build-utils.mjs` exports **`collectSourceEntries(rootDirectory)`**, which:

1. Walks `src/` recursively.
2. **Skips** directory names: `prev`, `dist`, `node_modules`, `assets`.
3. Collects every file ending in **`.ts` or `.tsx`**, except **`.d.ts`**.
4. Builds a map of **extensionless path → absolute file path** (e.g. `class/EventEmitter` → `.../src/class/EventEmitter.ts`).

Vite, esbuild, and Webpack all call this helper so the **same set of entry files** drives all three. That is why you see many parallel files under `dist/esbuild/`, not a single `bundle.js` from esbuild.

---

<a id="typescript-and-path-aliases"></a>
## TypeScript and path aliases

`tsconfig.json` (high level):

- **Target** `ES2020`, **module** `ESNext`, **moduleResolution** `Bundler`, **JSX** `react-jsx`, **strict** off (inherited; tighten per module if you migrate).
- **`baseUrl`**: `.`
- **`paths`**: see table below. Bundlers **mirror** these in `vite.config.mjs`, `webpack.config.mjs` (Vite/Webpack do not use `tsconfig` paths automatically without tooling — here they are **duplicated in config** to match `tsconfig`).

| Alias | Resolves to |
|-------|-------------|
| `@assets/*` | `assets/*` |
| `@css/*` | `src/css/*` |
| `@hooks/*` | `src/hooks/react/*` and `src/hooks/*` |
| `@component/*` | `src/component/react/*` and `src/component/*` |
| `@enum/*` | `src/enum/*` |
| `@threejs/*` | `src/*` (broad: entire `src` tree) |
| `clovercorejs` | `src/index` (barrel) |

**`exclude`:** `prev`, `dist`, `node_modules` — the **root** `tsc` and IDEs will not typecheck `prev/*` with this file unless you add a separate `tsconfig`.

---

<a id="per-tool-behavior-vite-esbuild-webpack"></a>
## Per-tool behavior (Vite, esbuild, Webpack)

**esbuild** (`esbuild.config.mjs`):

- **`bundle: false`** — transpile-only style: each entry is emitted with preserved module structure under `outdir: dist/esbuild`, `outbase: src`, **`format: "esm"`**, **`platform: "browser"`**, **`target: "es2020"`**.
- Loaders: `.ts` → `ts`, `.tsx` → `tsx`.
- **No** minification in the snipped config — suitable for further bundling by the consumer or for fast iteration.

**Vite** (`vite.config.mjs`):

- **`resolve.alias`**: same logical aliases as above (`@assets`, `@css`, `@hooks`, `@component`, `@enum`, `@threejs`).
- **`build.outDir`**: `dist/vite`, `emptyOutDir: true`.
- **`rollupOptions.input`**: the `collectSourceEntries` map.
- **`external`**: if an import is **not** relative/absolute and **not** starting with an internal alias prefix, it is **external** (e.g. `react`, `three` resolved by the app that consumes this package).

**Webpack** (`webpack.config.mjs`):

- **Output** `path: dist/webpack`, **`library.type: "module"`**, `experiments.outputModule: true`.
- **`externals`**: same idea as Vite — only local **aliases** and relative paths are **bundled**; bare specifiers like `react` are external.
- **Resolve**: `.ts`, `.tsx`, `.js`, `.jsx`, `.mjs` and the same `alias` map as Vite.

---

<a id="what-lives-in-src-overview"></a>
## What lives in `src/` (overview)

`src/index.ts` **re-exports** a small public surface, for example:

- `MediaPlayer`, `MultiMediaRecorder`, `Pagination`, `Visualizer`, …

**Most modules** (classes, React components, hooks, `types/threejs/*`, filters, shaders, etc.) are still **separate files**; the multi-entry build emits each as its own `dist/{tooling}/path/file.js`. **Dependents** can import from:

- the package root (barrel), or
- **subpath** exports if your `package.json` `exports` and built files align (e.g. `@onetone/typescript-platform/hooks/...` once published).

Typical **top-level groupings** under `src/` (illustrative, not exhaustive):

| Area | Role |
|------|------|
| `class/` | Services (DOM, media, geolocation, validation, WebGL helpers, etc.). |
| `hooks/react/` | React hooks (`common/`, `threejs/`, `kakao/`, `naver/`, `google/`, …). |
| `component/react/` | Reusable components (maps, players, etc.). |
| `types/threejs/` | TypeScript/TSX types and helpers for Three.js-style usage. |
| `shaders/threejs/` | Shader code / TSX bridges. |
| `enum/`, `contract/`, `interface/`, `filter/` | Shared enums, contracts, filters. |
| `loaders/` | e.g. GLTF-related helpers. |

**Assets** live under `assets/` at the package root and are referenced through **`@assets/*`**.

---

<a id="working-on-disk"></a>
## Working on disk

From the monorepo:

```bash
cd res/Platform/Typescript
npm install
npm run build
```

Selective:

```bash
npm run build:esbuild
npm run build:types
```

**Node** version: use a current LTS that matches your **TypeScript 5.9+** and **Vite 6** toolchain. On Windows, use PowerShell, cmd, or Git Bash; avoid hard-coded `/` only paths in local scripts you add.

**Do not** hand-edit `dist/*`; always regenerate. Add `src/` tests or consumers in a separate app package if you introduce breaking API changes.

---

<a id="prev-and-other-frontends"></a>
## prev/ and other frontends

- **`prev/frontend`**, **`prev/corejs`**: have their own **`package.json`**, `webpack`/`vite` config, and **`prev/frontend/AGENTS.md`**. Building them does **not** go through the root `npm run build` in `res/Platform/Typescript` — **`cd` into the subproject** and follow that folder’s scripts.
- **`res/frontend`** (if present at repo root) may be a **different** app — not the same as this platform package.
