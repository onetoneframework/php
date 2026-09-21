/*!
 * Onetone Livewire client runtime
 *
 * A tiny (no-build) browser counterpart to Clover\Framework\Livewire.
 * Responsibilities:
 *   - discover every `[wire:id]` root on the page
 *   - intercept wire:click / wire:submit / wire:model interactions
 *   - debounce state changes and POST a single batched update
 *   - swap in the new server-rendered HTML while preserving focus
 *
 * The runtime is intentionally dependency-free. If an application
 * wants Alpine-like reactivity on top it can layer that separately;
 * the Livewire contract only needs server-authoritative rendering.
 */
(function () {
    "use strict";

    var ENDPOINT = (window.LivewireConfig && window.LivewireConfig.endpoint) || "/livewire/update";
    var components = new Map();
    var seq = 0;

    function parseSnapshot(value) {
        if (!value) {
            return null;
        }

        try {
            // Attribute round-trip: the server writes JSON as an HTML
            // attribute (htmlspecialchars), the browser already decoded it.
            return JSON.parse(value);
        } catch (e) {
            console.error("[livewire] failed to parse wire:snapshot", e, value);
            return null;
        }
    }

    function registerRoot(root) {
        var id = root.getAttribute("wire:id");
        if (!id) {
            return;
        }

        if (components.has(id)) {
            return;
        }

        var snapshot = parseSnapshot(root.getAttribute("wire:snapshot"));
        if (!snapshot) {
            return;
        }

        var state = {
            id: id,
            name: root.getAttribute("wire:component") || snapshot.name,
            root: root,
            snapshot: snapshot,
            pendingUpdates: {},
            pendingCalls: [],
            debounceTimer: null
        };
        components.set(id, state);
        bindRoot(state);
    }

    function scan(target) {
        (target || document).querySelectorAll("[wire\\:id]").forEach(registerRoot);
    }

    function closestComponent(el) {
        while (el && el !== document) {
            if (el.hasAttribute && el.hasAttribute("wire:id")) {
                return components.get(el.getAttribute("wire:id"));
            }
            el = el.parentNode;
        }
        return null;
    }

    // ---------------------------------------------------------------
    // Event delegation
    // ---------------------------------------------------------------
    function bindRoot(state) {
        // Delegated handlers live on the root so re-rendered children
        // keep working without rebinding.
        state.root.addEventListener("click", onClick);
        state.root.addEventListener("input", onInput);
        state.root.addEventListener("change", onInput);
        state.root.addEventListener("submit", onSubmit);
        state.root.addEventListener("keydown", onKeydown);
    }

    function parseCall(expression) {
        // Accept "method", "method()" or "method('a', 2)".
        expression = (expression || "").trim();
        if (!expression) {
            return null;
        }

        var match = expression.match(/^([A-Za-z_][A-Za-z0-9_]*)\s*(?:\(([\s\S]*)\))?$/);
        if (!match) return {
            method: expression,
            params: []
        };

        var method = match[1];
        var raw = (match[2] || "").trim();
        if (!raw) return {
            method: method,
            params: []
        };

        try {
            return {
                method: method,
                params: JSON.parse("[" + raw + "]")
            };
        } catch (_) {
            // Tolerate single-quoted arguments in the template: 'hello'.
            try {
                return {
                    method: method,
                    params: JSON.parse("[" + raw.replace(/'/g, '"') + "]")
                };
            } catch (e) {
                console.warn("[livewire] unable to parse action params", expression, e);
                return {
                    method: method,
                    params: []
                };
            }
        }
    }

    function onClick(event) {
        var target = event.target.closest("[wire\\:click]");
        if (!target) {
            return;
        }

        var state = closestComponent(target);
        if (!state) {
            return;
        }

        event.preventDefault();
        var call = parseCall(target.getAttribute("wire:click"));
        if (call) {
            enqueueCall(state, call);
        }
    }

    function onSubmit(event) {
        var target = event.target.closest("[wire\\:submit]");
        if (!target) {
            return;
        }

        var state = closestComponent(target);
        if (!state) {
            return;
        }

        event.preventDefault();
        var expr = target.getAttribute("wire:submit");
        // Collect bound fields into updates before the action fires.
        target.querySelectorAll("[wire\\:model]").forEach(function (el) {
            applyModelChange(state, el);
        });
        var call = parseCall(expr);
        if (call) {
            enqueueCall(state, call);
        }
    }

    function onKeydown(event) {
        // Support wire:keydown.enter on inputs as a convenience.
        if (event.key !== "Enter") {
            return;
        }

        var target = event.target.closest("[wire\\:keydown\\.enter]");
        if (!target) {
            return;
        }

        var state = closestComponent(target);
        if (!state) {
            return;
        }

        event.preventDefault();
        var call = parseCall(target.getAttribute("wire:keydown.enter"));
        if (call) {
            enqueueCall(state, call);
        }
    }

    function onInput(event) {
        var target = event.target.closest("[wire\\:model]");
        if (!target) {
            return;
        }

        var state = closestComponent(target);
        if (!state) {
            return;
        }

        applyModelChange(state, target);

        // `wire:model.lazy="prop"` defers the round-trip until `change`.
        var lazy = target.hasAttribute("wire:model.lazy");
        if (!lazy && event.type === "input") {
            scheduleFlush(state);
        }
        if (lazy && event.type === "change") {
            scheduleFlush(state);
        }
    }

    function applyModelChange(state, el) {
        var prop = el.getAttribute("wire:model") || el.getAttribute("wire:model.lazy");
        if (!prop) {
            return;
        }

        var value;
        if (el.type === "checkbox") {
            value = el.checked;
        } else if (el.type === "number" || el.type === "range") {
            value = el.value === "" ? null : Number(el.value);
        } else if (el.tagName === "SELECT" && el.multiple) {
            value = Array.prototype.filter.call(el.options, function (o) { 
                return o.selected; 
            }).map(function (o) { 
                return o.value; 
            });
        } else {
            value = el.value;
        }
        state.pendingUpdates[prop] = value;
        state.snapshot.data[prop] = value;
    }

    // ---------------------------------------------------------------
    // Flushing
    // ---------------------------------------------------------------
    function enqueueCall(state, call) {
        state.pendingCalls.push(call);
        scheduleFlush(state, true);
    }

    function scheduleFlush(state, immediate) {
        if (state.debounceTimer) {
            clearTimeout(state.debounceTimer);
        }
        var delay = immediate ? 0 : 150;
        state.debounceTimer = setTimeout(function () {
            flush(state);
        }, delay);
    }

    function flush(state) {
        state.debounceTimer = null;
        var body = {
            components: [
                {
                    snapshot: state.snapshot,
                    updates: state.pendingUpdates,
                    calls: state.pendingCalls
                }
            ]
        };
        state.pendingUpdates = {};
        state.pendingCalls = [];
        var token = ++seq;
        state._inFlight = token;

        fetch(ENDPOINT, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-Livewire": "1"
            },
            body: JSON.stringify(body)
        })
            .then(function (res) {
                if (!res.ok) {
                    return res.json().then(function (j) {
                        throw new Error(j.error || res.statusText);
                    });
                }

                return res.json();
            })
            .then(function (payload) {
                // Discard stale responses — a newer flush has already started.
                if (state._inFlight !== token) {
                    return;
                }

                applyServerResponse(state, payload);
            })
            .catch(function (err) {
                console.error("[livewire] update failed", err);
                dispatch(state.root, "livewire:error", { error: String(err) });
            });
    }

    // ---------------------------------------------------------------
    // Applying server state
    // ---------------------------------------------------------------
    function applyServerResponse(state, payload) {
        if (!payload || !Array.isArray(payload.components)) {
            return;
        }

        payload.components.forEach(function (slice) {
            if (!slice || !slice.snapshot) {
                return;
            }

            var target = components.get(slice.snapshot.id) || state;
            target.snapshot = slice.snapshot;
            if (slice.effects) {
                if (typeof slice.effects.html === "string") {
                    patch(target, slice.effects.html);
                }
                if (Array.isArray(slice.effects.dispatches)) {
                    slice.effects.dispatches.forEach(function (evt) {
                        dispatch(target.root, evt.name, evt.params || []);
                    });
                }
            }
        });
    }

    function patch(state, html) {
        // Parse the new root element and swap in place.
        var fragment = document.createElement("template");
        fragment.innerHTML = html.trim();
        var incoming = fragment.content.firstElementChild;
        if (!incoming) {
            return;
        }

        // Preserve focus on the currently focused element by its stable path.
        var active = document.activeElement;
        var selectionStart = active && "selectionStart" in active ? active.selectionStart : null;
        var selectionEnd = active && "selectionEnd" in active ? active.selectionEnd : null;
        var activePath = active && state.root.contains(active) ? computePath(state.root, active) : null;

        morph(state.root, incoming);

        // Re-attach snapshot attribute on the root (defensive).
        if (state.snapshot) {
            state.root.setAttribute(
                "wire:snapshot",
                escapeHtml(JSON.stringify(state.snapshot))
            );
        }

        if (activePath) {
            var restored = resolvePath(state.root, activePath);
            if (restored && typeof restored.focus === "function") {
                restored.focus();
                if (selectionStart !== null && "setSelectionRange" in restored) {
                    try {
                        restored.setSelectionRange(selectionStart, selectionEnd);
                    } catch (_) { /* ignore */ }
                }
            }
        }
    }

    /**
     * Minimal DOM morph: overwrite children, shallow-merge attributes.
     * This trades Livewire's surgical morphing for simplicity — good
     * enough for forms and counters, not for animated lists.
     */
    function morph(existing, incoming) {
        // Merge attributes (skip wire:id so delegated listeners stay
        // attached to the original node).
        var incomingAttrs = incoming.attributes;
        for (var i = 0; i < incomingAttrs.length; i++) {
            var attr = incomingAttrs[i];
            if (attr.name === "wire:id") {
                continue;
            }
            existing.setAttribute(attr.name, attr.value);
        }
        // Drop stale attributes.
        var toRemove = [];
        for (var j = 0; j < existing.attributes.length; j++) {
            var name = existing.attributes[j].name;
            if (name === "wire:id") {
                continue;
            }
            if (!incoming.hasAttribute(name)) {
                toRemove.push(name);
            }
        }
        toRemove.forEach(function (n) {
            existing.removeAttribute(n);
        });

        existing.innerHTML = incoming.innerHTML;
    }

    function computePath(root, el) {
        var path = [];
        while (el && el !== root) {
            var parent = el.parentNode;
            if (!parent) {
                break;
            }
            var index = Array.prototype.indexOf.call(parent.children, el);
            path.unshift(index);
            el = parent;
        }
        return path;
    }

    function resolvePath(root, path) {
        var el = root;
        for (var i = 0; i < path.length; i++) {
            if (!el || !el.children) {
                return null;
            }
            el = el.children[path[i]];
        }
        return el;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function dispatch(el, name, params) {
        el.dispatchEvent(new CustomEvent("livewire:" + name, {
            detail: params,
            bubbles: true
        }));
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------
    var Livewire = {
        scan: scan,
        components: components,
        call: function (id, method, params) {
            var state = components.get(id);
            if (!state) {
                throw new Error("No component with id " + id);
            }
            enqueueCall(state, { 
                method: method, 
                params: params || [] 
            });
        },
        set: function (id, prop, value) {
            var state = components.get(id);
            if (!state) {
                throw new Error("No component with id " + id);
            }
            state.snapshot.data[prop] = value;
            state.pendingUpdates[prop] = value;
            scheduleFlush(state);
        }
    };
    window.Livewire = Livewire;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            scan();
        });
    } else {
        scan();
    }
})();
