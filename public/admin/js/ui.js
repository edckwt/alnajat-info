/* ==========================================================================
   ui.js — مكوّنات الواجهة التفاعلية
   Dropdown · Modal · Tabs · Accordion · Toast · Tooltip · Sidebar · Copy
   كلها تعمل بالـ data-attributes بدون الحاجة لأي كود إضافي.
   ========================================================================== */
(function (global) {
  'use strict';

  const UI = {};
  const isRTL = () => document.documentElement.getAttribute('dir') === 'rtl';

  /* ======================================================================
     1) القوائم المنسدلة
     <div class="dropdown">
       <button data-dropdown-toggle>...</button>
       <div class="dropdown-menu end-0" data-dropdown-menu hidden>...</div>
     </div>
     ====================================================================== */
  function closeAllDropdowns(except) {
    document.querySelectorAll('[data-dropdown-menu]:not([hidden])').forEach((menu) => {
      if (menu === except) return;
      menu.hidden = true;
      const trigger = menu.parentElement.querySelector('[data-dropdown-toggle]');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }

  UI.toggleDropdown = function (trigger) {
    const wrap = trigger.closest('.dropdown');
    if (!wrap) return;
    const menu = wrap.querySelector('[data-dropdown-menu]');
    if (!menu) return;
    const willOpen = menu.hidden;
    closeAllDropdowns(menu);
    menu.hidden = !willOpen;
    trigger.setAttribute('aria-expanded', String(willOpen));
    if (willOpen) {
      // إبقاء القائمة داخل الشاشة
      menu.style.removeProperty('inset-inline-start');
      const r = menu.getBoundingClientRect();
      if (r.right > window.innerWidth - 8) menu.style.insetInlineStart = 'auto';
    }
  };

  /* ======================================================================
     2) النوافذ المنبثقة (Modal)
     <button data-modal-open="#demo">فتح</button>
     <div id="demo" class="modal" hidden> ... <button data-modal-close>
     ====================================================================== */
  UI.openModal = function (selector) {
    const modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    modal.classList.add('is-open');
    const focusable = modal.querySelector('[autofocus], button, input, a[href]');
    if (focusable) setTimeout(() => focusable.focus(), 60);
    document.dispatchEvent(new CustomEvent('modal:open', { detail: { modal } }));
  };

  UI.closeModal = function (modal) {
    modal = modal || document.querySelector('.modal.is-open');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.hidden = true;
    if (!document.querySelector('.modal.is-open')) document.body.style.overflow = '';
    document.dispatchEvent(new CustomEvent('modal:close', { detail: { modal } }));
  };

  /* ======================================================================
     3) التبويبات
     <div data-tabs>
       <div class="tabs"><button class="tab" data-tab="#p1" aria-selected="true">..</button></div>
       <div id="p1" data-tab-panel>..</div>
     </div>
     ====================================================================== */
  UI.selectTab = function (btn) {
    const scope = btn.closest('[data-tabs]') || document;
    scope.querySelectorAll('[data-tab]').forEach((b) => b.setAttribute('aria-selected', 'false'));
    scope.querySelectorAll('[data-tab-panel]').forEach((p) => { p.hidden = true; });
    btn.setAttribute('aria-selected', 'true');
    const panel = scope.querySelector(btn.dataset.tab);
    if (panel) {
      panel.hidden = false;
      panel.classList.remove('animate-fade-in');
      void panel.offsetWidth;
      panel.classList.add('animate-fade-in');
    }
    document.dispatchEvent(new CustomEvent('tab:change', { detail: { tab: btn.dataset.tab } }));
  };

  /* ======================================================================
     4) الأكورديون
     ====================================================================== */
  UI.toggleAccordion = function (trigger) {
    const item = trigger.closest('[data-accordion-item]');
    const group = trigger.closest('[data-accordion]');
    const body = item.querySelector('[data-accordion-body]');
    const open = item.classList.contains('is-open');

    if (group && group.hasAttribute('data-accordion-single')) {
      group.querySelectorAll('[data-accordion-item].is-open').forEach((el) => {
        if (el === item) return;
        el.classList.remove('is-open');
        const b = el.querySelector('[data-accordion-body]');
        if (b) b.style.maxHeight = '0px';
        const tg = el.querySelector('[data-accordion-trigger]');
        if (tg) tg.setAttribute('aria-expanded', 'false');
      });
    }

    item.classList.toggle('is-open', !open);
    trigger.setAttribute('aria-expanded', String(!open));
    if (body) body.style.maxHeight = open ? '0px' : body.scrollHeight + 'px';
  };

  /* ======================================================================
     5) الإشعارات المنبثقة (Toast)
     UI.toast({ type:'success', title:'تم', message:'...', duration:4000 })
     ====================================================================== */
  const ICONS = {
    success: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    danger: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    warning: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>',
    info: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
  };
  const TONE = {
    success: 'text-success-600 bg-success-500/12',
    danger: 'text-danger-600 bg-danger-500/12',
    warning: 'text-warning-600 bg-warning-500/15',
    info: 'text-info-600 bg-info-500/12',
    primary: 'text-primary-600 bg-primary-500/12',
  };

  function toastHost() {
    let host = document.getElementById('toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'toast-host';
      host.className = 'fixed z-toast top-4 end-4 flex flex-col gap-3 pointer-events-none';
      document.body.appendChild(host);
    }
    return host;
  }

  UI.toast = function (opts) {
    opts = opts || {};
    const type = opts.type || 'info';
    const duration = opts.duration === undefined ? 4500 : opts.duration;

    const el = document.createElement('div');
    el.className = 'toast relative';
    el.setAttribute('role', 'status');
    el.innerHTML =
      '<span class="shrink-0 w-9 h-9 rounded-xl grid place-items-center ' + (TONE[type] || TONE.info) + '">' +
        (ICONS[type] || ICONS.info) +
      '</span>' +
      '<div class="flex-1 min-w-0 pe-2">' +
        (opts.title ? '<p class="font-bold text-sm text-ink">' + opts.title + '</p>' : '') +
        (opts.message ? '<p class="text-xs text-muted mt-0.5 leading-relaxed">' + opts.message + '</p>' : '') +
      '</div>' +
      '<button class="text-faint hover:text-ink transition shrink-0" data-toast-close aria-label="close">' +
        '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
      '</button>';

    el.style.setProperty('--slide-from', isRTL() ? '-1rem' : '1rem');
    toastHost().appendChild(el);

    const remove = () => {
      el.style.transition = 'opacity .2s ease, transform .2s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(() => el.remove(), 200);
    };
    el.querySelector('[data-toast-close]').addEventListener('click', remove);
    if (duration > 0) setTimeout(remove, duration);
    return { close: remove, el };
  };

  /* ======================================================================
     6) التلميحات (Tooltip)  — <button data-tooltip="نص">
     ====================================================================== */
  let tipEl = null;
  function showTip(target) {
    const text = target.getAttribute('data-tooltip');
    if (!text) return;
    hideTip();
    tipEl = document.createElement('div');
    tipEl.className =
      'fixed z-toast px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-slate-900 text-white ' +
      'shadow-pop pointer-events-none animate-fade-in max-w-[16rem] text-center';
    tipEl.textContent = text;
    document.body.appendChild(tipEl);

    const r = target.getBoundingClientRect();
    const tr = tipEl.getBoundingClientRect();
    const pos = target.getAttribute('data-tooltip-pos') || 'top';
    let top = r.top - tr.height - 8;
    let left = r.left + r.width / 2 - tr.width / 2;
    if (pos === 'bottom') top = r.bottom + 8;
    if (pos === 'start') { top = r.top + r.height / 2 - tr.height / 2; left = isRTL() ? r.right + 8 : r.left - tr.width - 8; }
    if (pos === 'end') { top = r.top + r.height / 2 - tr.height / 2; left = isRTL() ? r.left - tr.width - 8 : r.right + 8; }
    tipEl.style.top = Math.max(6, top) + 'px';
    tipEl.style.left = Math.min(Math.max(6, left), window.innerWidth - tr.width - 6) + 'px';
  }
  function hideTip() { if (tipEl) { tipEl.remove(); tipEl = null; } }

  /* ======================================================================
     7) القائمة الجانبية
     ====================================================================== */
  UI.toggleSidebar = function (force) {
    const open = force !== undefined ? force : !document.body.classList.contains('sidebar-open');
    document.body.classList.toggle('sidebar-open', open);
  };

  function initNavGroups() {
    // فتح المجموعة التي تحتوي على العنصر النشط
    document.querySelectorAll('.nav-link.is-active').forEach((link) => {
      let g = link.closest('.nav-group');
      while (g) { g.classList.add('is-open'); g = g.parentElement.closest('.nav-group'); }
    });
  }

  /* ======================================================================
     8) أشرطة التقدّم المتحركة عند الظهور
     ====================================================================== */
  function initProgress() {
    const bars = document.querySelectorAll('[data-progress]');
    if (!bars.length) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        en.target.style.width = en.target.dataset.progress + '%';
        io.unobserve(en.target);
      });
    }, { threshold: .3 });
    bars.forEach((b) => { b.style.width = '0%'; io.observe(b); });
  }

  /* ======================================================================
     9) العدّادات الرقمية
     ====================================================================== */
  function initCounters() {
    const els = document.querySelectorAll('[data-count-to]');
    if (!els.length) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        const el = en.target;
        const to = parseFloat(el.dataset.countTo);
        const dec = parseInt(el.dataset.countDecimals || '0', 10);
        const prefix = el.dataset.countPrefix || '';
        const suffix = el.dataset.countSuffix || '';
        const start = performance.now();
        const dur = 1100;
        const step = (now) => {
          const p = Math.min(1, (now - start) / dur);
          const eased = 1 - Math.pow(1 - p, 3);
          const v = to * eased;
          el.textContent = prefix + v.toLocaleString(
            document.documentElement.lang === 'ar' ? 'ar-EG' : 'en-US',
            { minimumFractionDigits: dec, maximumFractionDigits: dec }
          ) + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        io.unobserve(el);
      });
    }, { threshold: .4 });
    els.forEach((e) => io.observe(e));
  }

  /* ======================================================================
     10) ربط كل الأحداث
     ====================================================================== */
  function bind() {
    document.addEventListener('click', (e) => {
      const dd = e.target.closest('[data-dropdown-toggle]');
      if (dd) { e.preventDefault(); e.stopPropagation(); UI.toggleDropdown(dd); return; }

      if (!e.target.closest('[data-dropdown-menu]')) closeAllDropdowns();

      const open = e.target.closest('[data-modal-open]');
      if (open) { e.preventDefault(); UI.openModal(open.dataset.modalOpen); return; }

      const close = e.target.closest('[data-modal-close]');
      if (close) { e.preventDefault(); UI.closeModal(close.closest('.modal')); return; }

      if (e.target.matches('.modal-backdrop')) { UI.closeModal(e.target.closest('.modal')); return; }

      const tab = e.target.closest('[data-tab]');
      if (tab) { e.preventDefault(); UI.selectTab(tab); return; }

      const acc = e.target.closest('[data-accordion-trigger]');
      if (acc) { e.preventDefault(); UI.toggleAccordion(acc); return; }

      const navGroup = e.target.closest('.nav-group > .nav-link');
      if (navGroup) {
        e.preventDefault();
        navGroup.parentElement.classList.toggle('is-open');
        return;
      }

      const sb = e.target.closest('[data-sidebar-toggle]');
      if (sb) { e.preventDefault(); UI.toggleSidebar(); return; }

      if (e.target.closest('[data-sidebar-backdrop]')) { UI.toggleSidebar(false); return; }

      const mini = e.target.closest('[data-sidebar-collapse]');
      if (mini) {
        e.preventDefault();
        const cur = document.documentElement.getAttribute('data-layout');
        if (global.Theme) global.Theme.setLayout(cur === 'mini' ? 'side' : 'mini');
        return;
      }

      const copy = e.target.closest('[data-copy]');
      if (copy) {
        e.preventDefault();
        const text = copy.dataset.copy || (copy.previousElementSibling && copy.previousElementSibling.value) || '';
        navigator.clipboard && navigator.clipboard.writeText(text).then(() => {
          UI.toast({ type: 'success', title: global.t ? global.t('common.copied') : 'تم النسخ' });
        });
        return;
      }

      /* إظهار/إخفاء كلمة المرور */
      const eye = e.target.closest('[data-password-toggle]');
      if (eye) {
        e.preventDefault();
        const wrap = eye.closest('.password-field') || eye.parentElement;
        const input = wrap && wrap.querySelector('input');
        if (input) {
          const show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          eye.classList.toggle('is-visible', show);
          eye.setAttribute('aria-pressed', String(show));
          input.focus({ preventScroll: true });
        }
        return;
      }

      const dismiss = e.target.closest('[data-dismiss-target]');
      if (dismiss) {
        e.preventDefault();
        const el = document.querySelector(dismiss.dataset.dismissTarget);
        if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { closeAllDropdowns(); UI.closeModal(); hideTip(); }
    });

    document.addEventListener('mouseover', (e) => {
      const tip = e.target.closest('[data-tooltip]');
      if (tip) showTip(tip);
    });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest('[data-tooltip]')) hideTip();
    });
    window.addEventListener('scroll', hideTip, { passive: true });
  }

  function boot() {
    bind();
    initNavGroups();
    initProgress();
    initCounters();
    // إعادة ضبط الأكورديون المفتوح مسبقًا
    document.querySelectorAll('[data-accordion-item].is-open [data-accordion-body]').forEach((b) => {
      b.style.maxHeight = b.scrollHeight + 'px';
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  document.addEventListener('locale:change', () => { hideTip(); closeAllDropdowns(); });

  global.UI = UI;
})(window);
