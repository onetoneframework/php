<!-- License - Onetone Framework (AGPL-3.0) template -->
<div class="glass-layer"></div>

<!--
<div class="menu-bar">
  <div class="menu-left">
    <span>Installer</span>
    <span style="opacity:.6">Edit</span>
    <span style="opacity:.6">Window</span>
    <span style="opacity:.6">Help</span>
  </div>
  <div class="menu-right">
    <span style="opacity:.7">Wi‑Fi</span>
    <span style="opacity:.7">🔊</span>
    <span class="time" id="time"></span>
  </div>
</div>
-->



<div class="welcome-wrap">
  <div class="welcome">
    <h1><?= $subject; ?></h1>
    <div class="subtitle"><?= $description; ?></div>
    <div class="word" id="welcomeWord">Welcome</div>
  </div>
</div>

<div class="dock" aria-label="Dock">
  <div class="dock-item" title="Finder">
    <svg viewBox="0 0 24 24" fill="none">
      <path d="M4 4h16v16H4z" fill="#8ec6ff" />
      <path d="M9 9c1.2 0 2 .8 2 2s-.8 2-2 2-2-.8-2-2 .8-2 2-2zm6 0c1.2 0 2 .8 2 2s-.8 2-2 2-2-.8-2-2 .8-2 2-2z"
        fill="#124" />
    </svg>
  </div>
  <div class="dock-item" title="Safari">
    <svg viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" fill="#e6f4ff" />
      <path d="M12 12l6-2-2 6-4-4z" fill="#0a84ff" />
      <path d="M12 12l-6 2 2-6 4 4z" fill="#ff375f" />
    </svg>
  </div>
  <div class="dock-item" title="System Settings">
    <svg viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" fill="#f0f0f3" />
      <path d="M7 12a5 5 0 1010 0 5 5 0 10-10 0z" stroke="#6b6b6e" stroke-width="2" />
      <circle cx="12" cy="12" r="2.4" fill="#6b6b6e" />
    </svg>
  </div>
  <div class="dock-item" title="Trash">
    <svg viewBox="0 0 24 24" fill="none">
      <rect x="7" y="8" width="10" height="11" rx="2" fill="#dcdce6" />
      <rect x="9" y="4" width="6" height="3" rx="1.5" fill="#b7b7c7" />
      <path d="M10 10v6M14 10v6" stroke="#7a7a8a" stroke-width="1.6" />
    </svg>
  </div>
</div>