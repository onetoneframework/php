# Python platform (`res/Platform/Python`)

**`res/Platform/Python`** holds mixed tooling: **`lib/`** (60+ thin modules, many names mirror third-party packages—see [name collision](#lib--name-collision-warning)), **`classes/`** (13 utility modules), **`example/`** (including Addressables-style tools), feature directories (`voice_vtl/`, `iharmonic/`, `small_llm/`, `mood/`, …), **`frida/`** (see [Frida](/documentation/frida)), and **`mcp/server.py`** (MCP server skeleton; [below](#mcpserverpy--model-context-protocol-server)).

## Table of contents

- [Top-level map](#top-level-map)
- [`lib/` — name collision warning](#lib--name-collision-warning)
- [`classes/` — the 13 modules](#classes--the-13-modules)
- [`mcp/server.py` — Model Context Protocol server](#mcpserverpy--model-context-protocol-server)
- [`frida/`](#frida-subdirectory)
- [`framework.py` (root)](#frameworkpy-root)
- [Feature-sized directories (examples)](#feature-sized-directories-examples)
- [`example/AddressablesToolsPy`](#exampleaddressablestoolspy)
- [Virtualenv and PYTHONPATH](#virtualenv-and-pythonpath)

---

## Top-level map

| Path | What it is |
|------|------------|
| `lib/` | **62+** small modules named after ecosystems (`torch.py`, `pandas.py`, `cv2.py`, …). Many **shadow** the real stdlib or PyPI name when you `import` from the wrong `sys.path` — see [warning](#lib--name-collision-warning). |
| `classes/` | **13** “utility” Python files (TCP, DB, image, audio viz, etc.) — [list below](#classes--the-13-modules). |
| `example/` | Demos, including **`AddressablesToolsPy/`** (a real package with `pyproject.toml`). |
| `frida/` | `frida_attach.py` + `scripts/hook_script.js` — [§ `frida/`](#frida-subdirectory), [Frida doc](/documentation/frida). |
| `mcp/` | `server.py` implements **`MCPServer`**, `Tool`, `Resource`, `Prompt` — [full detail](#mcpserverpy--model-context-protocol-server). |
| `framework.py` | **Large** scratch / experiment module at tree root; imports `frida`, `Crypto`, and many **commented** ML deps — [below](#frameworkpy-root). |
| `voice_vtl/`, `iharmonic/`, `small_llm/`, `mood/`, `transformer/`, `girlscreation/`, etc. | Self-contained experiments; each may need its own venv and tens of optional deps. |

There is **no** single `pip install res-platform-python` story—treat every subdirectory as an opt-in project.

---

## `lib/` — name collision warning

The directory **`lib/`** uses filenames like `json.py`, `socket.py`, `csv.py`, `thread.py` alongside `torch.py`, `sklearn.py`, `opencv.py`. If you run scripts with

```text
cd res/Platform/Python
export PYTHONPATH="$PWD"   # or set PYTHONPATH on Windows
```

a naive `import json` may import **`lib/json.py`** instead of the **standard library** `json` module, depending on path order. **Best practice:** run from a **subdirectory** (e.g. a single project) with a **dedicated venv** and do **not** put `res/Platform/Python` on `PYTHONPATH` root unless you understand the collision risk. Prefer `python path/to/script.py` with **no** parent `lib` shadowing.

The intent of `lib/` is **convenience wrappers** around heavy stacks (Keras, PyTorch, scapy, win32, Android tooling, etc.) in one place for this monorepo’s scripts.

---

## `classes/` — the 13 modules

| File | Likely focus |
|------|--------------|
| `audio_visualizer.py` | Audio / waveform visualization |
| `date_utility.py` | Date/time helpers |
| `database_utility.py` | DB access |
| `directory_utility.py` | Directory walk / cleanup |
| `image_utility.py` | Image ops |
| `monochrome_utility.py` | Grayscale / mono processing |
| `multi_processing.py` | Process pools / workers |
| `repeated_timer.py` | Timed callbacks |
| `request_utility.py` | HTTP request helpers |
| `response_handler.py` | HTTP response side |
| `screen_selector.py` | Screen / region selection |
| `tcp_client_utility.py` | TCP client |
| `tcp_server_utility.py` | TCP server |

Open each file to see the **actual** API—there is no shared package `__init__.py` exporting them as one library.

---

## `mcp/server.py` — Model Context Protocol server

`res/Platform/Python/mcp/server.py` defines a small **MCP** server skeleton in pure Python (no Frida here):

- **`MessageType` enum** — `INITIALIZE`, `INITIALIZED`, `TOOLS_LIST`, `TOOLS_CALL`, `RESOURCES_LIST`, `RESOURCES_READ`, `PROMPTS_LIST`, `PROMPTS_GET` (string values are the standard MCP method names).
- **`@dataclass Tool`** — `name`, `description`, `input_schema` (JSON-schema-shaped dict).
- **`@dataclass Resource`** — `uri`, `name`, `mime_type`, optional `description`.
- **`@dataclass Prompt`** — `name`, `description`, optional `arguments` list of dicts.
- **`class MCPServer`**
  - `__init__(name, version)` — empty registries for tools, resources, prompts, and per-item **handlers** (`Dict[str, Any]`).
  - `add_tool(name, description, input_schema, handler)` — stores `Tool` + `tool_handlers[name] = handler`
  - `add_resource(uri, name, mime_type, description, handler)` — idem for resources
  - `add_prompt(name, description, arguments, handler)` — idem for prompts
  - (Rest of the file) JSON message loop / dispatch — read the source for the wire protocol the author implemented.

This is a **concrete, readable** place to add MCP tools that call back into this repo (PHP route smoke, file reads, etc.) if you register handlers.

---

<a id="frida-subdirectory"></a>
## `frida/`

- **Python:** `frida_attach.py` — `psutil`, `termcolor`, class **`ProcessHooker`**, **`on_message`** for `log` / `status` / `hook`, **`script.post({ type: 'config', config: hook_config })`**, CLI flags `--process-name` / `--script` / `enable_il2cpp_target_method`.
- **JavaScript:** `scripts/hook_script.js` (IL2CPP helpers, native hooks).
- **Build:** `scripts/build.cmd` → `frida-compile` output for a single-file agent.

Further detail: [Frida](/documentation/frida).

---

## `framework.py` (root)

`framework.py` at the **root** of `res/Platform/Python` is a **monolithic** script: it imports `frida`, `Crypto.Cipher.AES`, `itertools`, `pathlib`, etc., and includes **dozens of commented imports** (`torch`, `transformers`, `librosa`, `webrtcvad`, …) so the file can be used as a **REPL / scratchpad** for experiments. Many functions (e.g. `get_pretrained_autotokenizer`) will **NameError** unless the commented imports are re-enabled and dependencies are installed. **Do not** treat it as a stable public API; copy out the pieces you need into a new module with a pinned venv.

---

## Feature-sized directories (examples)

| Directory | What you will find (high level) |
|-----------|-----------------------------------|
| `iharmonic/` | Harmonic / score JSON under `src/iharmonic/`, styles, many generated score demo files. |
| `mood/` | `predict.py`, `classifier.py` — ML mood classification. |
| `small_llm/` | `model.py`, `evaluator.py`, `prepare_data.py` — small LM experiments. |
| `transformer/` | e.g. `trans.py`, `full_transformer_faithful.py` — transformer code paths. |
| `acoustic_analysis_service/` | FastAPI application and container boundary for bounded WAV analysis. |
| `voice_vtl/` | `get_pitch.py`, `parse_pitch.py`, `formants_*.txt` — vocal-tract / formant work. |
| `girlscreation/` | `scripts/cryptos.py`, `merge.py` — project-specific script folder. |
| `mcp/` | [above](#mcpserverpy--model-context-protocol-server). |

Each may ship **large** JSON, CSV, or demo assets—treat as **data-heavy**; do not commit new multi‑MB outputs unless the team agrees.

---

## `example/AddressablesToolsPy`

A **packaged** Python tree under `example/AddressablesToolsPy/` with `pyproject.toml` and `AddressablesTools` package (Unity Addressables–style JSON parsing and catalog classes). To hack it, use a **dedicated** venv and `pip install -e .` from **that** directory only, not the whole `res/Platform/Python` parent.

---

<a id="virtualenv-and-pythonpath"></a>
## Virtualenv and PYTHONPATH

1. **One venv per major subproject** (`python -m venv .venv` inside `mood/`, `small_llm/`, etc. as needed).
2. **Never** set `PYTHONPATH` to the entire `res/Platform/Python` root in global shell profiles.
3. **Read** the `import` block at the top of the script you run, then `pip install` only those packages in that venv.
4. **Large** artifacts, checkpoints, secrets: keep **out of git** unless the task explicitly needs them in the tree.
