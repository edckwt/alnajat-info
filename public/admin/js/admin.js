/* ==========================================================================
   admin.js — ربط قالب روبيك بلوحة النجاة.
   القائمة الجانبية والترويسة تُرسمان من Blade (بدل js/layout.js في روبيك)،
   ويبقى هنا فقط ما يعتمد على المتصفح: قائمة ألوان الثيم وطبقة التعتيم في الجوال.
   ========================================================================== */
(function () {
  'use strict';

  // اللوحة عربية فقط.
  var L = { color: 'لون الثيم', sidebar: 'نمط القائمة الجانبية', frame: 'الإطار', layout: 'التخطيط', side: 'كاملة', mini: 'مصغّرة', reset: 'استعادة الافتراضي' };

  function label(o) { return (o && o.ar) || ''; }

  function themeMenu() {
    var T = window.Theme;
    var box = document.getElementById('themeMenu');
    if (!T || !box) return;

    var swatches = T.palettes.map(function (p) {
      return '<button type="button" class="swatch" data-theme-swatch="' + p.id + '" title="' + label(p.label) + '" aria-pressed="false">' +
        '<span class="absolute inset-0" style="background:' + p.hex + '"></span>' +
        '<span class="absolute inset-y-0 start-0 w-1/3 bg-black/25"></span></button>';
    }).join('');
    var sides = T.sidebars.map(function (s) {
      return '<button type="button" class="swatch-option" data-sidebar-option="' + s.id + '">' + label(s.label) + '</button>';
    }).join('');
    var frames = T.frames.map(function (s) {
      return '<button type="button" class="swatch-option" data-frame-option="' + s.id + '">' + label(s.label) + '</button>';
    }).join('');

    box.innerHTML =
      '<p class="dropdown-header !px-0 !pt-0">' + L.color + '</p><div class="grid grid-cols-4 gap-2">' + swatches + '</div>' +
      '<p class="dropdown-header !px-0">' + L.sidebar + '</p><div class="flex gap-1.5">' + sides + '</div>' +
      '<p class="dropdown-header !px-0">' + L.frame + '</p><div class="flex gap-1.5">' + frames + '</div>' +
      '<p class="dropdown-header !px-0">' + L.layout + '</p><div class="flex gap-1.5">' +
        '<button type="button" class="swatch-option" data-layout-option="side">' + L.side + '</button>' +
        '<button type="button" class="swatch-option" data-layout-option="mini">' + L.mini + '</button></div>' +
      '<div class="dropdown-divider !mt-4"></div>' +
      '<button type="button" class="btn btn-secondary btn-sm btn-block" data-theme-reset>' + L.reset + '</button>';

    T.apply(true);
  }

  function backdrop() {
    var bd = document.getElementById('sidebarBackdrop');
    if (!bd) return;
    new MutationObserver(function () {
      bd.classList.toggle('hidden', !document.body.classList.contains('sidebar-open'));
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
  }

  function init() { themeMenu(); backdrop(); }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

/* ==========================================================================
   نافذة التأكيد (بدل window.confirm)
   <form data-confirm="حذف «الخبر»؟" [data-confirm-title] [data-confirm-ok] [data-confirm-tone="danger|warning|primary"] [data-confirm-note]>
   النماذج بـ DELETE تأخذ تلقائياً: أحمر، «تأكيد الحذف»، زر «حذف».
   ومن JavaScript: UI.confirm({ title, message, ok, tone }).then(function (yes) { … })
   ========================================================================== */
(function () {
  'use strict';

  var ICONS = {
    danger: '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/><path d="M10 11v6M14 11v6"/>',
    warning: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    primary: '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>',
  };
  var root, lastFocus, resolver;

  function build() {
    root = document.createElement('div');
    root.className = 'confirm';
    root.hidden = true;
    root.setAttribute('role', 'alertdialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-labelledby', 'confirm-title');
    root.setAttribute('aria-describedby', 'confirm-message');
    root.innerHTML =
      '<div class="confirm-backdrop" data-confirm-dismiss></div>' +
      '<div class="confirm-dialog">' +
        '<button type="button" class="confirm-close" data-confirm-dismiss aria-label="إغلاق">' +
          '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
        '<div class="confirm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"></svg></div>' +
        '<h2 class="confirm-title" id="confirm-title"></h2>' +
        '<p class="confirm-message" id="confirm-message"></p>' +
        '<p class="confirm-note" hidden></p>' +
        '<div class="confirm-actions">' +
          '<button type="button" class="btn btn-secondary" data-confirm-dismiss>إلغاء</button>' +
          '<button type="button" class="btn" data-confirm-accept></button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);

    root.addEventListener('click', function (e) {
      if (e.target.closest('[data-confirm-dismiss]')) close(false);
      else if (e.target.closest('[data-confirm-accept]')) close(true);
    });
    root.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { e.preventDefault(); close(false); }
      if (e.key === 'Tab') { // إبقاء التركيز داخل النافذة
        var items = root.querySelectorAll('button:not([disabled])');
        var first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
  }

  /** «…» في الرسالة تُعرض بخط عريض (بلا HTML من المستخدم). */
  function setMessage(el, text) {
    el.textContent = '';
    String(text || '').split(/(«[^»]*»)/).forEach(function (part) {
      if (!part) return;
      if (part.charAt(0) === '«') { var b = document.createElement('strong'); b.textContent = part; el.appendChild(b); }
      else el.appendChild(document.createTextNode(part));
    });
  }

  function open(opts) {
    if (!root) build();
    if (resolver) resolver(false);
    var tone = ICONS[opts.tone] ? opts.tone : 'primary';
    root.setAttribute('data-tone', tone);
    root.querySelector('.confirm-icon svg').innerHTML = ICONS[tone];
    root.querySelector('.confirm-title').textContent = opts.title || 'تأكيد';
    setMessage(root.querySelector('.confirm-message'), opts.message);
    var note = root.querySelector('.confirm-note');
    note.hidden = !opts.note;
    note.textContent = opts.note || '';
    var ok = root.querySelector('[data-confirm-accept]');
    ok.className = 'btn ' + (tone === 'danger' ? 'btn-danger' : tone === 'warning' ? 'btn-warning' : 'btn-primary');
    ok.disabled = false;
    ok.textContent = opts.ok || 'تأكيد';

    lastFocus = document.activeElement;
    root.classList.remove('is-closing');
    root.hidden = false;
    document.body.style.overflow = 'hidden';
    // الحذف: التركيز على «إلغاء» حتى لا يحذف Enter بالخطأ
    (tone === 'danger' ? root.querySelector('.confirm-actions [data-confirm-dismiss]') : ok).focus();

    return new Promise(function (resolve) { resolver = resolve; });
  }

  function close(result) {
    if (!resolver) return;
    var done = resolver;
    resolver = null;
    if (result && root.dataset.busy !== undefined) {
      // يبقى مفتوحاً مع مؤشر انتظار حتى تنتقل الصفحة
      var ok = root.querySelector('[data-confirm-accept]');
      ok.disabled = true;
      ok.innerHTML = '<span class="confirm-spinner"></span><span>' + ok.textContent + '…</span>';
      root.querySelectorAll('[data-confirm-dismiss]').forEach(function (b) { b.disabled = true; });
      done(true);
      return;
    }
    root.classList.add('is-closing');
    setTimeout(function () {
      root.hidden = true;
      root.classList.remove('is-closing');
      root.querySelectorAll('[data-confirm-dismiss]').forEach(function (b) { b.disabled = false; });
      document.body.style.overflow = '';
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }, 150);
    done(!!result);
  }

  window.UI = window.UI || {};
  window.UI.confirm = function (opts) { if (root) delete root.dataset.busy; return open(opts || {}); };

  // النماذج: <form data-confirm="...">
  document.addEventListener('submit', function (e) {
    var form = e.target.closest ? e.target.closest('form[data-confirm]') : null;
    if (!form || form.dataset.confirmed === '1') return;
    e.preventDefault();
    e.stopImmediatePropagation();

    var method = form.querySelector('input[name="_method"]');
    var deleting = method && method.value.toUpperCase() === 'DELETE';
    var submitter = e.submitter || null;
    var tone = form.getAttribute('data-confirm-tone') || (deleting ? 'danger' : 'primary');

    if (!root) build();
    root.dataset.busy = '';
    open({
      tone: tone,
      title: form.getAttribute('data-confirm-title') || (deleting ? 'تأكيد الحذف' : 'تأكيد'),
      message: form.getAttribute('data-confirm'),
      note: form.getAttribute('data-confirm-note') || (deleting ? 'لا يمكن التراجع عن هذا الإجراء.' : ''),
      ok: form.getAttribute('data-confirm-ok') || (deleting ? 'حذف' : 'تأكيد'),
    }).then(function (yes) {
      if (!yes) return;
      form.dataset.confirmed = '1';
      if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
      else form.submit();
    });
  }, true);

  // الرجوع بزر المتصفح يعيد الصفحة من الذاكرة: أغلق النافذة وأعد النماذج لحالتها
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-confirmed]').forEach(function (f) { delete f.dataset.confirmed; });
    if (root && !root.hidden) { resolver = null; root.hidden = true; document.body.style.overflow = ''; }
  });
})();

/* ==========================================================================
   السحب والإفلات لكل حقول الرفع: <div data-dropzone> (components/admin/dropzone)
   الملفات تُوضع في حقل الملف المخفي داخل المنطقة، وتُرسل مع النموذج.
   ========================================================================== */
(function () {
  'use strict';

  function humanSize(b) {
    var u = ['B', 'KB', 'MB', 'GB'], i = b ? Math.floor(Math.log(b) / Math.log(1024)) : 0;
    return (b / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + u[i];
  }

  function accepts(input, file) {
    var accept = (input.getAttribute('accept') || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    if (!accept.length) return true;
    return accept.some(function (rule) {
      if (rule.endsWith('/*')) return file.type.indexOf(rule.slice(0, -1)) === 0;
      if (rule[0] === '.') return file.name.toLowerCase().endsWith(rule.toLowerCase());
      return file.type === rule;
    });
  }

  function setup(root) {
    var zone = root.querySelector('[data-dz-zone]');
    var input = root.querySelector('[data-dz-input]');
    var list = root.querySelector('[data-dz-list]');
    var current = root.querySelector('[data-dz-current]');
    var maxBytes = parseFloat(root.getAttribute('data-max-mb') || '10') * 1024 * 1024;
    // محرر الصور (image-editor.js): نسبة قص مقترحة، وأقصى عرض قبل التصغير التلقائي
    var editorOpts = { aspect: root.getAttribute('data-aspect') || null, maxWidth: parseInt(root.getAttribute('data-max-width') || '2400', 10) };
    var Editor = window.ImageEditor;
    var notes = new Map();
    var previews = [];

    function render() {
      previews.forEach(function (el) { el.remove(); });
      previews = [];
      var files = Array.prototype.slice.call(input.files || []);
      if (current) current.classList.toggle('opacity-40', files.length > 0);

      files.forEach(function (file, index) {
        var row = document.createElement('div');
        row.className = 'flex items-center gap-3 rounded-xl border border-primary-500/40 bg-primary-500/5 p-2';
        var thumb = document.createElement(file.type.indexOf('image/') === 0 ? 'img' : 'span');
        thumb.className = 'w-14 h-14 rounded-lg object-cover bg-surface-2 grid place-items-center text-xs text-muted shrink-0';
        if (thumb.tagName === 'IMG') { thumb.src = URL.createObjectURL(file); thumb.alt = ''; }
        else { thumb.textContent = (file.name.split('.').pop() || '').toUpperCase(); }
        var info = document.createElement('div');
        info.className = 'flex-1 min-w-0';
        info.innerHTML = '<p class="text-sm font-semibold truncate" dir="ltr"></p><p class="text-xs text-muted"></p>';
        info.firstChild.textContent = file.name;
        info.lastChild.textContent = humanSize(file.size) + ' · ' + (notes.get(file) || 'سيُرفع عند الحفظ');
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-icon btn-sm btn-ghost text-danger-600';
        remove.setAttribute('aria-label', 'إزالة');
        remove.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        remove.addEventListener('click', function () { removeAt(index); });
        row.appendChild(thumb); row.appendChild(info);
        if (Editor && Editor.canEdit(file)) {
          var edit = document.createElement('button');
          edit.type = 'button';
          edit.className = 'btn btn-sm btn-soft gap-1.5 shrink-0';
          edit.title = 'قص، تدوير، تصغير، ضغط';
          edit.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg><span>تحرير</span>';
          edit.addEventListener('click', function () {
            Editor.open(file, editorOpts).then(function (out) {
              if (!out) return;
              notes.set(out, 'عُدّلت في المحرر · سيُرفع عند الحفظ');
              state[index] = out;
              sync();
            });
          });
          row.appendChild(edit);
        }
        row.appendChild(remove);
        list.appendChild(row);
        previews.push(row);
      });
    }

    var state = [];

    function sync() {
      var dt = new DataTransfer();
      state.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
      render();
      // لمن يريد معاينة الملف المختار خارج المنطقة (مثل الصورة الشخصية)
      root.dispatchEvent(new CustomEvent('dz:change', { detail: { files: state.slice() } }));
    }

    function setFiles(fileList) {
      var incoming = Array.prototype.slice.call(fileList).filter(function (file) {
        if (!accepts(input, file)) { alertMsg('نوع الملف غير مسموح: ' + file.name); return false; }
        // الصور الكبيرة تُصغَّر أولاً، فلا يُرفض حجمها قبل التصغير
        if (file.size > maxBytes && !(Editor && Editor.canResize(file))) { alertMsg('الملف أكبر من المسموح: ' + file.name); return false; }
        return true;
      });
      prepare(incoming).then(function (files) {
        files = files.filter(function (file) {
          if (file.size > maxBytes) { alertMsg('الملف أكبر من المسموح حتى بعد التصغير: ' + file.name); return false; }
          return true;
        });
        state = input.multiple ? state.concat(files) : files.slice(0, 1).concat(files.length ? [] : state);
        sync();
      });
    }

    /** تصغير الصور الأعرض من data-max-width قبل رفعها (بنفس الصيغة وجودة 90%). */
    function prepare(files) {
      if (!Editor || !editorOpts.maxWidth) return Promise.resolve(files);
      return Promise.all(files.map(function (file) {
        if (!Editor.canResize(file)) return file;
        return Editor.fit(file, editorOpts.maxWidth).then(function (r) {
          if (!r) return file;
          notes.set(r.file, 'صُغّرت تلقائياً من ' + r.from.join('×') + ' إلى ' + r.to.join('×'));
          return r.file;
        });
      }));
    }

    // تحرير الصورة الحالية المحفوظة: تُفتح في المحرر ويُرفع الناتج بدلها عند الحفظ
    var currentImg = current && !input.multiple ? current.querySelector('img') : null;
    if (currentImg && Editor) {
      var editCurrent = document.createElement('button');
      editCurrent.type = 'button';
      editCurrent.className = 'btn btn-sm btn-soft gap-1.5 shrink-0';
      editCurrent.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg><span>تحرير</span>';
      editCurrent.title = 'تحرير الصورة الحالية (يُرفع الناتج بدلها عند الحفظ)';
      editCurrent.addEventListener('click', function () {
        editCurrent.disabled = true;
        fetch(sameOrigin(currentImg.src), { credentials: 'same-origin' })
          .then(function (r) { if (!r.ok) throw new Error(); return r.blob(); })
          .then(function (blob) {
            var name = decodeURIComponent((currentImg.src.split('?')[0].split('/').pop()) || 'image.jpg');
            var file = new File([blob], name, { type: blob.type || 'image/jpeg' });
            if (!Editor.canEdit(file)) throw new Error();
            editCurrent.disabled = false;
            return Editor.open(file, editorOpts);
          })
          .then(function (out) {
            if (!out) return;
            notes.set(out, 'نسخة معدّلة من الصورة الحالية · تُرفع عند الحفظ');
            state = [out];
            sync();
          })
          .catch(function () {
            editCurrent.disabled = false;
            alertMsg('تعذّر فتح الصورة الحالية للتحرير. اسحب نسخة منها إلى المنطقة لتحريرها.');
          });
      });
      var badge = current.querySelector('.badge');
      current.insertBefore(editCurrent, badge || null);
    }

    /**
     * رابط الصورة الحالية من نفس أصل الصفحة: رابط لنفس الموقع بصيغة أخرى (www أو http/https) يُقرأ
     * بمساره فقط، وإلا يمنعه المتصفح (CORS) ولا يمكن تحريره.
     */
    function sameOrigin(src) {
      try {
        var url = new URL(src, location.href);
        var bare = function (h) { return h.replace(/^www\./i, '').toLowerCase(); };
        if (url.origin !== location.origin && bare(url.hostname) === bare(location.hostname)) {
          return location.origin + url.pathname + url.search;
        }
        return url.href;
      } catch (e) { return src; }
    }

    function removeAt(index) {
      state.splice(index, 1);
      sync();
    }

    function alertMsg(text) {
      if (window.UI && UI.toast) UI.toast({ type: 'danger', title: 'لم يُضف الملف', message: text });
      else window.alert(text);
    }

    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-dragover'); });
    });
    zone.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) setFiles(e.dataTransfer.files); });
    zone.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    zone.addEventListener('paste', function (e) { if (e.clipboardData && e.clipboardData.files.length) { e.preventDefault(); setFiles(e.clipboardData.files); } });
    input.addEventListener('change', function () {
      // المتصفح يستبدل الملفات عند الاختيار بالنقر؛ نعيد بناء القائمة من state
      var chosen = Array.prototype.slice.call(input.files);
      if (chosen.length) setFiles(chosen); else sync();
    });
  }

  function init() { document.querySelectorAll('[data-dropzone]').forEach(setup); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
