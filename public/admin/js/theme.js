/* ==========================================================================
   theme.js — إدارة الثيم (اللون / الوضع الليلي / نمط القائمة الجانبية / الاتجاه)
   يبث الحدث "theme:change" ليعيد باقي المكوّنات (الرسوم مثلًا) رسم نفسها.
   ========================================================================== */
(function (global) {
  'use strict';

  const KEY = 'rubick.ui';

  const PALETTES = [
    { id: 'blue',    label: { ar: 'أزرق',     en: 'Blue' },    hex: '#2563eb' },
    { id: 'indigo',  label: { ar: 'نيلي',     en: 'Indigo' },  hex: '#4f46e5' },
    { id: 'violet',  label: { ar: 'بنفسجي',   en: 'Violet' },  hex: '#7c3aed' },
    { id: 'emerald', label: { ar: 'أخضر',     en: 'Emerald' }, hex: '#059669' },
    { id: 'teal',    label: { ar: 'فيروزي',   en: 'Teal' },    hex: '#0d9488' },
    { id: 'amber',   label: { ar: 'كهرماني',  en: 'Amber' },   hex: '#d97706' },
    { id: 'rose',    label: { ar: 'وردي',     en: 'Rose' },    hex: '#e11d48' },
    { id: 'slate',   label: { ar: 'رمادي',    en: 'Slate' },   hex: '#475569' },
  ];

  const SIDEBARS = [
    { id: 'colored', label: { ar: 'بلون الثيم', en: 'Themed' } },
    { id: 'dark',    label: { ar: 'داكنة',      en: 'Dark' } },
    { id: 'light',   label: { ar: 'فاتحة',      en: 'Light' } },
  ];

  const FRAMES = [
    { id: 'framed', label: { ar: 'بتباعد', en: 'Framed' } },
    { id: 'flush',  label: { ar: 'ملتصق',  en: 'Flush' } },
  ];

  const DEFAULTS = {
    theme: 'blue',
    mode: 'light',        // light | dark
    sidebar: 'colored',   // colored | dark | light
    layout: 'side',       // side | mini
    frame: 'framed',      // framed | flush
    locale: 'ar',         // ar | en
  };

  /* ---------- تخزين آمن ---------- */
  function read() {
    try {
      return Object.assign({}, DEFAULTS, JSON.parse(localStorage.getItem(KEY) || '{}'));
    } catch (e) {
      return Object.assign({}, DEFAULTS);
    }
  }
  function write(state) {
    try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) { /* وضع التصفح الخاص */ }
  }

  let state = read();

  /* ---------- التطبيق على الـ DOM ---------- */
  function apply(silent) {
    const root = document.documentElement;
    root.setAttribute('data-theme', state.theme);
    root.setAttribute('data-sidebar', state.sidebar);
    root.setAttribute('data-layout', state.layout);
    root.setAttribute('data-frame', state.frame);
    root.classList.toggle('dark', state.mode === 'dark');

    // لون شريط المتصفح على الجوال
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
      const cs = getComputedStyle(root);
      const v = cs.getPropertyValue('--c-sidebar').trim();
      if (v) meta.setAttribute('content', 'rgb(' + v + ')');
    }

    syncControls();
    if (!silent) {
      document.dispatchEvent(new CustomEvent('theme:change', { detail: Object.assign({}, state) }));
    }
  }

  /* ---------- مزامنة عناصر لوحة الإعدادات ---------- */
  function syncControls() {
    document.querySelectorAll('[data-theme-swatch]').forEach((el) => {
      el.classList.toggle('is-active', el.dataset.themeSwatch === state.theme);
      el.setAttribute('aria-pressed', String(el.dataset.themeSwatch === state.theme));
    });
    document.querySelectorAll('[data-sidebar-option]').forEach((el) => {
      el.classList.toggle('is-active', el.dataset.sidebarOption === state.sidebar);
      el.setAttribute('aria-pressed', String(el.dataset.sidebarOption === state.sidebar));
    });
    document.querySelectorAll('[data-frame-option]').forEach((el) => {
      el.classList.toggle('is-active', el.dataset.frameOption === state.frame);
      el.setAttribute('aria-pressed', String(el.dataset.frameOption === state.frame));
    });
    document.querySelectorAll('[data-layout-option]').forEach((el) => {
      el.classList.toggle('is-active', el.dataset.layoutOption === state.layout);
    });
    document.querySelectorAll('[data-mode-option]').forEach((el) => {
      el.classList.toggle('is-active', el.dataset.modeOption === state.mode);
    });
    document.querySelectorAll('[data-mode-toggle]').forEach((el) => {
      if (el.type === 'checkbox') el.checked = state.mode === 'dark';
    });
  }

  /* ---------- الواجهة العامة ---------- */
  const Theme = {
    palettes: PALETTES,
    sidebars: SIDEBARS,
    frames: FRAMES,
    get state() { return Object.assign({}, state); },

    set(patch, opts) {
      state = Object.assign({}, state, patch || {});
      write(state);
      apply(opts && opts.silent);
      return Theme;
    },
    setTheme(id) { return Theme.set({ theme: id }); },
    setSidebar(id) { return Theme.set({ sidebar: id }); },
    setLayout(id) { return Theme.set({ layout: id }); },
    setFrame(id) { return Theme.set({ frame: id }); },
    setMode(mode) { return Theme.set({ mode: mode }); },
    toggleMode() { return Theme.set({ mode: state.mode === 'dark' ? 'light' : 'dark' }); },
    isDark() { return state.mode === 'dark'; },

    /** يقرأ لون CSS variable ويعيده بصيغة hex/rgb لاستخدامه في الرسوم */
    color(name, alpha) {
      const v = getComputedStyle(document.documentElement).getPropertyValue('--c-' + name).trim();
      if (!v) return '#000000';
      const rgb = v.split(/\s+/).join(',');
      return alpha === undefined ? 'rgb(' + rgb + ')' : 'rgba(' + rgb + ',' + alpha + ')';
    },

    /** يبني لوحة ألوان متناسقة للرسوم البيانية اعتمادًا على الثيم الحالي */
    chartPalette() {
      return [
        Theme.color('primary-600'),
        Theme.color('warning-500'),
        Theme.color('primary-300'),
        Theme.color('success-500'),
        Theme.color('info-500'),
        Theme.color('danger-500'),
        Theme.color('primary-800'),
      ];
    },

    apply: apply,
    reset() { state = Object.assign({}, DEFAULTS); write(state); apply(); },
  };

  /* ---------- ربط الأحداث ---------- */
  function bind() {
    document.addEventListener('click', (e) => {
      const swatch = e.target.closest('[data-theme-swatch]');
      if (swatch) { Theme.setTheme(swatch.dataset.themeSwatch); return; }

      const side = e.target.closest('[data-sidebar-option]');
      if (side) { Theme.setSidebar(side.dataset.sidebarOption); return; }

      const layout = e.target.closest('[data-layout-option]');
      if (layout) { Theme.setLayout(layout.dataset.layoutOption); return; }

      const frame = e.target.closest('[data-frame-option]');
      if (frame) { Theme.setFrame(frame.dataset.frameOption); return; }

      const modeOpt = e.target.closest('[data-mode-option]');
      if (modeOpt) { Theme.setMode(modeOpt.dataset.modeOption); return; }

      const modeBtn = e.target.closest('[data-mode-btn]');
      if (modeBtn) { Theme.toggleMode(); return; }

      const resetBtn = e.target.closest('[data-theme-reset]');
      if (resetBtn) { Theme.reset(); return; }
    });

    document.addEventListener('change', (e) => {
      const t = e.target.closest('[data-mode-toggle]');
      if (t && t.type === 'checkbox') Theme.setMode(t.checked ? 'dark' : 'light');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { bind(); apply(true); });
  } else {
    bind(); apply(true);
  }

  global.Theme = Theme;
})(window);
