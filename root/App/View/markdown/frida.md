# Frida tooling (`res/Platform/Python/frida`)

**Layout:** `frida_attach.py` drives attach/spawn, loads `scripts/hook_script.js`, and implements **`ProcessHooker`** with **`on_message`** for JS → Python messages; an alternate path targets Android over USB. **`hook_script.js`** is large and includes IL2CPP memory helpers and native interposition.

**Flow:** `frida.re` injects a JS runtime into the target process. Run sequence: select device → attach or spawn → `create_script` / `load` → `post` config into the agent → handle `send` / `error` in Python until stdin closes or the session detaches.

**Defaults in `frida_attach.py` (current tree):** `DEFAULT_TARGET_PROCESS_NAME` (e.g. Windows image name), `DEFAULT_HOOK_SCRIPT_PATH` (`scripts/hook_script.js`), `DEFAULT_TARGET_PACKAGE_NAME` (Android label used in the spawn path). Change these or pass `--process-name` / `--script`.

## Table of contents

- [What you get in this folder](#what-you-get-in-this-folder)
- [Python dependencies](#python-dependencies)
- [Windows: local process attach](#windows-local-process-attach)
- [Android / USB path (spawn)](#android--usb-path-spawn)
- [Configuration from Python to JavaScript](#configuration-from-python-to-javascript)
- [Message protocol (Python `on_message`)](#message-protocol-python-on_message)
- [JavaScript agents and IL2CPP helpers](#javascript-agents-and-il2cpp-helpers)
- [Errors, detach, and cleanup](#errors-detach-and-cleanup)
- [Optional: `frida-compile` (build.cmd)](#optional-frida-compile-buildcmd)
- [Troubleshooting](#troubleshooting)

---

<a id="what-you-get-in-this-folder"></a>
## What you get in this folder

| Path | Role |
|------|------|
| `frida_attach.py` | Main CLI: parse args, attach or spawn, create script, wire `on_message`, `post` config, block on stdin. |
| `scripts/hook_script.js` | Large Frida/JS bundle: IL2CPP memory helpers, `Interceptor` hooks, `Module` resolution, and `send()` to Python. |
| `scripts/hook_keyboard.js` | Optional keyboard-related hooks; merge or load separately if you split concerns. |
| `scripts/common.js` | Small shared exports (e.g. string readers) for reuse across agents. |
| `scripts/build.cmd` | Invokes **frida-compile** to produce a single compiled agent (see [below](#optional-frida-compile-buildcmd)). |

**Working directory:** paths like `scripts/hook_script.js` are **relative to the process cwd**. Safest: `cd res/Platform/Python/frida` then run, or pass an **absolute** `--script` path.

---

<a id="python-dependencies"></a>
## Python dependencies

- **`frida`** — must match the **frida-server** (or local device) version on the target.
- **`psutil`** — used to check whether a Windows process name is running before attach.
- **`termcolor`** — colored terminal output for `log` / `status` / `hook` lines.
- **Target-specific:** for Android, USB debugging + `frida-server` on device; for Windows, local Frida with permissions to inject.

Install (example):

```bash
cd res/Platform/Python/frida
python -m pip install frida psutil termcolor
```

---

<a id="windows-local-process-attach"></a>
## Windows: local process attach

`ProcessHooker.attach_windows_application()` (and the main `if __name__` path) use:

1. `frida.get_local_device()` — the local machine.
2. Optional process enumeration to confirm the **executable name** exists.
3. `frida.attach(self.process_name)` — inject into a **running** process.
4. `read_script(self.script_path)` — load JS source.
5. `session.create_script(script_code)` + `script.on('message', self.on_message)`.
6. `session.on('detached', self.on_detached)` and `script.on('destroyed', self.on_destroyed)`.
7. `script.load()`.
8. `script.post({'type': 'config', 'config': self.hook_config})` — see [Configuration](#configuration-from-python-to-javascript).
9. Block on **`sys.stdin.readline()`** (and in some code paths `sys.stdin.read()`) until you press Enter, then **detach** in `finally`.

**Default** process name in this repo is **`siprj_x.exe`** — change with **`--process-name` / `-p`**.

**Example**

```bash
cd res/Platform/Python/frida
python frida_attach.py --process-name YourGame.exe --script scripts/hook_script.js
```

| Argument | Short | Meaning |
|----------|-------|---------|
| `--process-name` | `-p` | EXE / image name as seen in Task Manager. |
| `--script` | `-s` | Frida agent `.js` path. |
| `--enable-il2cpp-target-method` | (flag) | Pushed into `hook_config` for the JS side (IL2CPP hook toggle). |

**Permission errors:** run the terminal **as Administrator** if Frida cannot inject. **Anti-cheat** or other protections may block Frida entirely — that is a policy/runtime issue, not this script’s bug.

---

<a id="android--usb-path-spawn"></a>
## Android / USB path (spawn)

`ProcessHooker.android_application()` (in the same file) demonstrates another flow:

- Waits for **USB** (`frida.get_usb_device(1)`) or falls back to **remote** (`frida.get_remote_device()`).
- **`device.spawn(package_or_app_name)`** to start the app, then **create_script** and **load** on the spawned process.

The exact API usage in the repo may be mixed with `hook_script.js` (path sometimes hardcoded as `hook_script.js` in that branch) — when you copy this pattern, align **package name**, **spawn vs attach**, and **script path** with your app. The **message protocol** in Python remains the same.

---

<a id="configuration-from-python-to-javascript"></a>
## Configuration from Python to JavaScript

After `script.load()`, Python sends:

```python
self.script.post({'type': 'config', 'config': self.hook_config})
```

The **`hook_config`** dict is built in `main` from CLI args, e.g.:

```python
hook_config = {
    'enable_il2cpp_target_method': args.enable_il2cpp_target_method,
}
```

**In your JS**, listen for `recv` (or a single `message` handler if you merge Frida’s patterns) and branch on `message.type === 'config'` to set globals / enable specific `Interceptor` hooks. Adding a new feature flag:

1. Add `parser.add_argument(...)` in Python.
2. Add a key to `hook_config`.
3. In JS, read `message.payload` or the equivalent structure you define — **keep Python and JS keys in sync**.

---

<a id="message-protocol-python-on_message"></a>
## Message protocol (Python `on_message`)

`on_message(message, data)` in `frida_attach.py` is the contract between JS and Python. Design new hooks so they **fit** these branches or add explicit `elif` paths.

| Frida `message['type']` | Handling |
|-------------------------|----------|
| `'send'` | `payload` should be a **dict** (or you get the “raw” branch). Inside `payload`, branch on `payload.get('type')`: |
| → `'log'` | Prints `JS Log` line in cyan. |
| → `'status'` | Short status in blue. |
| → `'hook'` | **Structured hook data** — the sample handles `hook_type == 'GetHashName'` with `original_name` and `hash_value`; unknown types print generic hook output + `colored_json_print` of `hook_data`. |
| `'error'` | Prints Frida script error: `description`, `stack`, `fileName`, `lineNumber`, `columnNumber` in red. |
| (other) | Treated as unknown: dumps JSON. |

**Binary `data`**: the second parameter to `on_message` is used when the script sends **bytes**; extend `on_message` if you add file/dump channels.

**Minimal JS send shape (conceptual)**

```javascript
send({
  type: 'hook',
  payload: {
    type: 'hook',
    payload: {
      hook_type: 'GetHashName',
      data: { original_name: 'x', hash_value: 'y' }
    }
  }
});
```

Align the nesting with what `on_message` expects, or **simplify both sides** in your fork to a flatter object.

---

<a id="javascript-agents-and-il2cpp-helpers"></a>
## JavaScript agents and IL2CPP helpers

`hook_script.js` includes **IL2CPP-oriented** utilities (examples):

- `readIl2CppByteArray` — read byte arrays from managed/native pointers.
- `readIl2CppArray` / `readIl2CppList` — walk structures and elements.

It also uses **`Interceptor.attach`** (and related APIs) to tap native methods. **Offsets and symbol names** are **game- and build-specific**; when the game updates, re-verify signatures.

**Keyboard script:** `hook_keyboard.js` is a separate file — load it with `--script` or merge into the main agent if you want one process.

---

<a id="errors-detach-and-cleanup"></a>
## Errors, detach, and cleanup

| Exception / condition | What to do |
|----------------------|------------|
| `frida.ProcessNotFoundError` | Start the process first; check spelling of `-p` name. |
| `frida.PermissionDeniedError` | Administrator shell; close AV briefly for testing. |
| `self._process_terminated` / `on_detached` | Skip double-detach; session may already be invalid (`frida.InvalidSessionError` caught in `finally`). |
| `script` errors | Read the **`error`** branch in `on_message` — line numbers point into the **generated** Frida context. |

**Cleanup:** the `finally` in `attach_windows_application` calls `session.detach()` when the session is still valid, swallowing `InvalidSessionError` if the process already exited.

---

<a id="optional-frida-compile-buildcmd"></a>
## Optional: `frida-compile` (build.cmd)

`scripts/build.cmd` runs (example):

```cmd
frida-compile hook_script_agent.js -o hook_script_.js
```

Use this when you split **agents** or need a **single file** for deployment. After compiling, run Python with `--script` pointing to the **output** `hook_script_.js` (or whatever you named). Keep **source** files in git; add compiled outputs to `.gitignore` if they are large.

---

<a id="troubleshooting"></a>
## Troubleshooting

| Problem | Things to check |
|--------|------------------|
| `Process not found` | Exact EXE name; 32 vs 64-bit; process not elevated vs Frida. |
| Script loads, no output | `send` never called; wrong module offset; `config` disables hooks. |
| `error` in `on_message` | Syntax in JS; Frida version mismatch; pointer misuse in IL2CPP read. |
| Frida version mismatch | `frida` pip package **major.minor** should match the gadget/server. |
| Anti-cheat | Many games block Frida; no repo-side fix. |

---

Upstream **Frida** reference: [frida.re/docs](https://frida.re/docs/home/).
