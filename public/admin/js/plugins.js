/* ==========================================================================
   plugins.js — منتقي التاريخ (flatpickr) والقوائم بالبحث (Tom Select، بديل Select2 بلا jQuery)
   لكل حقول اللوحة تلقائياً، بتنسيق ثيم روبيك (الأنماط في resources/admin/css/app.css).

   التاريخ: كل <input type="date"> و [data-datepicker] (القيمة المرسلة Y-m-d كما هي، والظاهر «25 سبتمبر 2026»).
   القوائم: كل <select> في الصفحة، إلا [data-native] أو ما يُنشأ لاحقاً بـ JavaScript (المصمم، المحرر).
   لحقول تُضاف لاحقاً: Plugins.init(عنصر)، ولتغيير قيمة قائمة من JS: Plugins.setValue(select, قيمة).
   المكتبات محفوظة محلياً في public/admin/vendor.
   ========================================================================== */
(function (global) {
  'use strict';

  var Plugins = { pickers: [], selects: [] };

  /* ------------------------------ منتقي التاريخ ------------------------------ */
  function initPickers(root) {
    if (!global.flatpickr) return;
    var arabic = global.flatpickr.l10ns && global.flatpickr.l10ns.ar;

    (root || document).querySelectorAll('input[type="date"], input[type="datetime-local"], input[data-datepicker]').forEach(function (el) {
      if (el._flatpickr || el.hasAttribute('data-native')) return;

      var withTime = el.type === 'datetime-local' || el.dataset.enableTime === 'true';
      var required = el.required;
      var labelFor = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
      // الحقل الأصلي يصبح مخفياً ويحمل القيمة المرسلة (Y-m-d)؛ حقل العرض يأخذ نفس التنسيق والأخطاء
      if (el.type !== 'text' && el.type !== 'hidden') el.type = 'text';

      var fp = global.flatpickr(el, {
        locale: arabic || 'default',
        dateFormat: el.dataset.format || (withTime ? 'Y-m-d H:i' : 'Y-m-d'),
        altInput: true,
        altFormat: el.dataset.altFormat || (withTime ? 'j F Y · H:i' : 'l j F Y'),
        enableTime: withTime,
        time_24hr: true,
        mode: el.dataset.mode || 'single',
        minDate: el.min || el.dataset.minDate || undefined,
        maxDate: el.max || el.dataset.maxDate || undefined,
        disableMobile: true,
        monthSelectorType: 'static',
        position: 'auto right',
        onReady: function (_, __, instance) {
          var alt = instance.altInput;
          if (!alt) return;
          alt.id = (el.id || 'date-' + Math.random().toString(36).slice(2)) + '-display';
          alt.setAttribute('autocomplete', 'off');
          alt.setAttribute('dir', 'rtl');
          if (labelFor) labelFor.htmlFor = alt.id;
          alt.required = required;
          addClear(instance, required);
        },
      });
      Plugins.pickers.push(fp);
    });
  }

  /** زر مسح للتواريخ الاختيارية (مثل تصفية الأخبار بالتاريخ). */
  function addClear(fp, required) {
    var alt = fp.altInput;
    var wrap = document.createElement('span');
    wrap.className = 'datepicker-wrap';
    alt.parentNode.insertBefore(wrap, alt);
    wrap.appendChild(alt);

    var icon = document.createElement('span');
    icon.className = 'datepicker-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
    wrap.appendChild(icon);

    if (required) return;
    var clear = document.createElement('button');
    clear.type = 'button';
    clear.className = 'datepicker-clear';
    clear.setAttribute('aria-label', 'مسح التاريخ');
    clear.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
    clear.addEventListener('click', function () { fp.clear(); fp.input.dispatchEvent(new Event('change', { bubbles: true })); });
    wrap.appendChild(clear);
    var sync = function () { wrap.classList.toggle('has-value', !!fp.input.value); };
    fp.config.onChange.push(sync);
    sync();
  }

  /* ------------------------------ القوائم بالبحث ------------------------------ */
  function initSelects(root) {
    if (!global.TomSelect) return;
    (root || document).querySelectorAll('select').forEach(function (el) {
      if (el.tomselect || el.hasAttribute('data-native') || el.closest('[data-native-selects]')) return;

      var placeholderOption = el.querySelector('option[value=""]');
      var settings = {
        create: el.dataset.create === 'true',
        maxItems: el.multiple ? (parseInt(el.dataset.maxItems || '0', 10) || null) : 1,
        maxOptions: null,
        plugins: el.multiple ? ['remove_button'] : ['dropdown_input'],
        placeholder: el.getAttribute('placeholder') || el.dataset.placeholder || (placeholderOption ? placeholderOption.textContent.trim() : 'اختر…'),
        allowEmptyOption: true,
        hidePlaceholder: false,
        searchField: ['text'],
        diacritics: true,
        copyClassesToDropdown: false,
        render: {
          no_results: function () { return '<div class="no-results px-3 py-2.5 text-xs text-muted">لا توجد نتائج</div>'; },
          option_create: function (data, escape) { return '<div class="create">إضافة <strong>' + escape(data.input) + '</strong>…</div>'; },
        },
        onInitialize: function () {
          // أخطاء التحقق والأحجام الصغيرة تنتقل إلى الواجهة الجديدة
          var wrapper = this.wrapper;
          ['is-invalid', 'form-input-sm'].forEach(function (c) { if (el.classList.contains(c)) wrapper.classList.add(c); });
          if (el.id) {
            var label = document.querySelector('label[for="' + el.id + '"]');
            if (label && this.control_input) { this.control_input.id = el.id + '-ts'; label.htmlFor = this.control_input.id; }
          }
        },
      };
      if (el.dataset.dropdownInput === 'false') settings.plugins = settings.plugins.filter(function (p) { return p !== 'dropdown_input'; });

      try { Plugins.selects.push(new global.TomSelect(el, settings)); } catch (e) { /* يبقى الحقل الأصلي يعمل */ }
    });
  }

  Plugins.init = function (root) { initPickers(root); initSelects(root); };

  /** تغيير قيمة قائمة من JavaScript مع تحديث واجهتها. */
  Plugins.setValue = function (select, value, silent) {
    if (!select) return;
    if (select.tomselect) select.tomselect.setValue(value, silent !== false);
    else select.value = value;
  };

  function boot() { Plugins.init(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  global.Plugins = Plugins;
})(window);
