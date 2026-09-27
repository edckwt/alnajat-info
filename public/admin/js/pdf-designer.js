/* ==========================================================================
   pdf-designer.js — محرر قوالب النشرة بالسحب والإفلات.
   التصميم JSON بالمليمتر (نفس ما يطبعه mPDF): ثلاث صفحات (cover / section / last)،
   لكل صفحة خلفية وعناصر (نص، صورة، مستطيل، خط، QR)، وللصفحة المتكررة منطقة محتوى.
   بلا مكتبات: سحب وتحجيم بـ Pointer Events، وحفظ ومعاينة بـ fetch.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-designer]');
  if (!root) return;

  var design = JSON.parse(root.querySelector('[data-designer-design]').textContent);
  var config = JSON.parse(root.querySelector('[data-designer-config]').textContent);
  var readonly = !!config.readonly;
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

  var stage = root.querySelector('[data-designer-stage]');
  var pageEl = root.querySelector('[data-designer-page]');
  var props = root.querySelector('[data-designer-props]');
  var layers = root.querySelector('[data-designer-layers]');
  var statusEl = root.querySelector('[data-designer-status]');

  var PT = 0.3528; // مم لكل نقطة
  var state = { page: 'cover', selected: null, scale: 3, zoom: 'fit', dirty: false, previewCat: null };
  var history = [], future = [];

  var PAGE_LABELS = { cover: 'الغلاف', section: 'الصفحة المتكررة', last: 'الختام' };
  var TYPE_LABELS = { text: 'نص', image: 'صورة', rect: 'مستطيل', line: 'خط', qr: 'رمز QR' };

  /* ------------------------------------------------------------ أدوات */
  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') node.textContent = attrs[k];
      else if (k === 'html') node.innerHTML = attrs[k];
      else if (k.slice(0, 2) === 'on') node.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] !== null && attrs[k] !== undefined && attrs[k] !== false) node.setAttribute(k, attrs[k] === true ? '' : attrs[k]);
    });
    (children || []).forEach(function (c) { if (c) node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return node;
  }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function round(n) { return Math.round(n * 10) / 10; }
  function uid() { return 'e' + Math.random().toString(36).slice(2, 9); }
  function pageSize() {
    var f = config.formats[design.page.format] || [210, 297];
    return design.page.orientation === 'L' ? [f[1], f[0]] : [f[0], f[1]];
  }
  function current() { return design.pages[state.page]; }
  function selected() {
    return current().elements.find(function (e) { return e.id === state.selected; }) || null;
  }
  function assetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    path = path.replace(/^\/+/, '');
    // المرفوعات تبقى «upload/…» في التصميم، وملفها في مجلد الرفع (/storage/upload/…)
    if (config.urls.uploads && path.indexOf('upload/') === 0) return config.urls.uploads + '/' + path.slice(7);
    return config.urls.asset + '/' + path;
  }
  function fill(text) {
    return String(text || '').replace(/\{\{\s*([a-z_]+)\s*\}\}/g, function (_, k) {
      if (k === 'category_name' && state.previewCat && config.categories[state.previewCat]) return config.categories[state.previewCat];
      return config.sample[k] != null ? config.sample[k] : '';
    });
  }

  /* ------------------------------------------------------------ التاريخ (تراجع/إعادة) */
  function snapshot() {
    history.push(JSON.stringify(design));
    if (history.length > 80) history.shift();
    future = [];
    markDirty();
  }
  function undo() {
    if (!history.length) return;
    future.push(JSON.stringify(design));
    design = JSON.parse(history.pop());
    state.selected = null;
    markDirty(); render();
  }
  function redo() {
    if (!future.length) return;
    history.push(JSON.stringify(design));
    design = JSON.parse(future.pop());
    state.selected = null;
    markDirty(); render();
  }
  function markDirty() {
    state.dirty = true;
    if (statusEl) statusEl.textContent = 'تغييرات غير محفوظة';
  }

  /* ------------------------------------------------------------ الرسم */
  function render() {
    renderPage();
    renderLayers();
    renderProps();
  }

  function fitScale() {
    var size = pageSize();
    if (state.zoom !== 'fit') return 3 * parseFloat(state.zoom);
    var w = stage.clientWidth - 48, h = stage.clientHeight - 48;
    return Math.max(1, Math.min(w / size[0], h / size[1], 5));
  }

  function renderPage() {
    var size = pageSize();
    var s = state.scale = fitScale();
    var page = current();

    pageEl.innerHTML = '';
    pageEl.style.width = size[0] * s + 'px';
    pageEl.style.height = size[1] * s + 'px';
    pageEl.style.backgroundColor = page.background.color || '#fff';
    var bgImage = page.background.image;
    if (state.page === 'section' && state.previewCat && page.categoryBackgrounds && page.categoryBackgrounds[state.previewCat]) {
      bgImage = page.categoryBackgrounds[state.previewCat];
    }
    pageEl.style.backgroundImage = bgImage ? 'url("' + assetUrl(bgImage) + '")' : 'none';
    pageEl.style.backgroundSize = page.background.fit === 'width' ? '100% auto' : '100% 100%';
    pageEl.style.backgroundRepeat = 'no-repeat';
    pageEl.style.backgroundPosition = 'top center';

    if (state.page === 'section') {
      var c = page.content;
      var box = el('div', { class: 'dz-content' + (state.selected === '__content' ? ' is-selected' : ''), 'data-id': '__content', title: 'منطقة المحتوى: هنا تتدفق الأخبار' },
        [el('span', { text: 'منطقة المحتوى — هنا تُطبع الأخبار بقوالب صناديقها' })]);
      place(box, c);
      pageEl.appendChild(box);
      if (state.selected === '__content' && !readonly) addHandles(box);
    }

    page.elements.forEach(function (item) {
      var node = el('div', { class: 'dz-el dz-' + item.type + (item.id === state.selected ? ' is-selected' : ''), 'data-id': item.id });
      place(node, item);
      paint(node, item);
      pageEl.appendChild(node);
      if (item.id === state.selected && !readonly) addHandles(node);
    });
  }

  function place(node, box) {
    var s = state.scale;
    node.style.left = box.x * s + 'px';
    node.style.top = box.y * s + 'px';
    node.style.width = box.w * s + 'px';
    node.style.height = (box.type === 'line' ? Math.max(1, (box.style.borderWidth || 0.3) * s) : box.h * s) + 'px';
  }

  function paint(node, item) {
    var s = state.scale, st = item.style;
    if (item.type === 'text') {
      node.textContent = fill(item.text);
      node.style.fontFamily = '"pdf-' + (st.font === 'inherit' ? design.page.font : st.font) + '", "IBM Plex Sans Arabic", sans-serif';
      node.style.fontSize = st.size * PT * s + 'px';
      node.style.lineHeight = st.lineHeight;
      node.style.color = st.color;
      node.style.textAlign = st.align;
      node.style.fontWeight = st.bold ? '700' : '400';
      node.style.background = st.bg || 'transparent';
      node.style.borderRadius = st.radius * s + 'px';
      node.style.padding = st.padding * s + 'px';
    } else if (item.type === 'image') {
      if (item.src) {
        var img = el('img', { src: assetUrl(item.src), alt: '', draggable: 'false' });
        if (item.fit === 'width') img.style.height = 'auto';
        node.appendChild(img);
      } else {
        node.appendChild(el('span', { class: 'dz-empty', text: 'اسحب صورة إلى خصائص العنصر' }));
      }
    } else if (item.type === 'rect') {
      node.style.background = st.bg || 'transparent';
      node.style.borderRadius = st.radius * s + 'px';
      node.style.border = st.borderWidth > 0 ? Math.max(1, st.borderWidth * s) + 'px solid ' + st.borderColor : '1px dashed rgba(100,116,139,.4)';
    } else if (item.type === 'line') {
      node.style.background = st.color;
    } else if (item.type === 'qr') {
      node.appendChild(el('span', { class: 'dz-qr', text: 'QR' }));
    }
  }

  function addHandles(node) {
    ['n', 's', 'e', 'w', 'ne', 'nw', 'se', 'sw'].forEach(function (dir) {
      node.appendChild(el('span', { class: 'dz-handle dz-' + dir, 'data-handle': dir }));
    });
  }

  function renderLayers() {
    layers.innerHTML = '';
    var list = current().elements.slice().reverse();
    if (!list.length) layers.appendChild(el('li', { class: 'text-xs text-muted', text: 'لا عناصر في هذه الصفحة بعد.' }));
    list.forEach(function (item) {
      var label = item.name || (item.type === 'text' ? fill(item.text).slice(0, 24) : '') || TYPE_LABELS[item.type];
      var row = el('li', { class: 'dz-layer' + (item.id === state.selected ? ' is-selected' : '') }, [
        el('button', { type: 'button', class: 'dz-layer-name', text: TYPE_LABELS[item.type] + ' · ' + label, onclick: function () { select(item.id); } }),
        readonly ? null : el('button', { type: 'button', class: 'btn btn-icon btn-xs btn-ghost', title: 'للأمام', 'aria-label': 'للأمام', text: '▲', onclick: function () { move(item.id, 1); } }),
        readonly ? null : el('button', { type: 'button', class: 'btn btn-icon btn-xs btn-ghost', title: 'للخلف', 'aria-label': 'للخلف', text: '▼', onclick: function () { move(item.id, -1); } }),
      ]);
      layers.appendChild(row);
    });
  }

  function move(id, dir) {
    var els = current().elements;
    var i = els.findIndex(function (e) { return e.id === id; });
    var j = i + dir;
    if (i < 0 || j < 0 || j >= els.length) return;
    snapshot();
    var t = els[i]; els[i] = els[j]; els[j] = t;
    render();
  }

  function select(id) {
    state.selected = id;
    render();
  }

  /* ------------------------------------------------------------ لوحة الخصائص */
  function field(label, input, hint) {
    return el('label', { class: 'dz-field' }, [el('span', { class: 'form-label', text: label }), input, hint ? el('span', { class: 'form-hint', text: hint }) : null]);
  }
  function numberInput(value, onchange, step) {
    return el('input', { type: 'number', class: 'form-input form-input-sm', value: value, step: step || '0.5', disabled: readonly,
      onchange: function (e) { onchange(parseFloat(e.target.value) || 0); } });
  }
  function colorInput(value, onchange, allowNone) {
    var wrap = el('span', { class: 'flex items-center gap-2' });
    var input = el('input', { type: 'color', class: 'dz-color', value: value || '#ffffff', disabled: readonly, oninput: function (e) { onchange(e.target.value); } });
    wrap.appendChild(input);
    wrap.appendChild(el('code', { class: 'text-xs text-muted', dir: 'ltr', text: value || 'بلا لون' }));
    if (allowNone && !readonly) wrap.appendChild(el('button', { type: 'button', class: 'btn btn-xs btn-ghost', text: 'بلا', onclick: function () { onchange(null); } }));
    return wrap;
  }
  function selectInput(value, options, onchange) {
    var s = el('select', { class: 'form-select form-input-sm', disabled: readonly, onchange: function (e) { onchange(e.target.value); } });
    Object.keys(options).forEach(function (k) { s.appendChild(el('option', { value: k, text: options[k], selected: String(k) === String(value) })); });
    return s;
  }
  function update(mutator, rerender) {
    snapshot();
    mutator();
    rerender === false ? renderPage() : render();
  }
  function fontOptions(withDefault) {
    var o = {};
    if (withDefault) o.inherit = 'خط القالب';
    config.fonts.forEach(function (f) { o[f.key] = f.label; });
    return o;
  }

  function renderProps() {
    props.innerHTML = '';
    var item = selected();
    if (state.selected === '__content') return contentProps();
    if (!item) return pageProps();

    var st = item.style;
    var head = el('div', { class: 'designer-section flex items-center justify-between gap-2' }, [
      el('p', { class: 'designer-title !mb-0', text: 'خصائص: ' + TYPE_LABELS[item.type] }),
      readonly ? null : el('span', { class: 'flex gap-1' }, [
        el('button', { type: 'button', class: 'btn btn-xs btn-ghost', text: 'تكرار', title: 'Ctrl+D', onclick: duplicate }),
        el('button', { type: 'button', class: 'btn btn-xs btn-ghost text-danger-600', text: 'حذف', title: 'Delete', onclick: remove }),
      ]),
    ]);
    props.appendChild(head);

    var box = el('div', { class: 'designer-section grid grid-cols-2 gap-2' });
    box.appendChild(field('الاسم', el('input', { class: 'form-input form-input-sm', value: item.name || '', disabled: readonly, placeholder: TYPE_LABELS[item.type],
      onchange: function (e) { update(function () { item.name = e.target.value; }); } })));
    box.appendChild(el('span'));
    [['x', 'من اليسار'], ['y', 'من الأعلى'], ['w', 'العرض'], ['h', 'الارتفاع']].forEach(function (p) {
      if (item.type === 'line' && p[0] === 'h') return;
      box.appendChild(field(p[1] + ' (مم)', numberInput(item[p[0]], function (v) { update(function () { item[p[0]] = v; }); })));
    });
    props.appendChild(box);

    var sec = el('div', { class: 'designer-section space-y-3' });
    if (item.type === 'text') {
      var ta = el('textarea', { class: 'form-textarea text-sm', rows: 3, disabled: readonly, text: item.text,
        onchange: function (e) { update(function () { item.text = e.target.value; }); } });
      sec.appendChild(field('النص', ta, 'اكتب نصاً ثابتاً، وأضف المتغيرات من القائمة.'));
      sec.appendChild(variableChips(function (token) {
        var start = ta.selectionStart || ta.value.length;
        update(function () { item.text = ta.value.slice(0, start) + token + ta.value.slice(start); });
      }));
      var g = el('div', { class: 'grid grid-cols-2 gap-2' });
      g.appendChild(field('الخط', selectInput(st.font, fontOptions(true), function (v) { update(function () { st.font = v; }); })));
      g.appendChild(field('الحجم (pt)', numberInput(st.size, function (v) { update(function () { st.size = v; }); }, '1')));
      g.appendChild(field('تباعد الأسطر', numberInput(st.lineHeight, function (v) { update(function () { st.lineHeight = v; }); }, '0.1')));
      g.appendChild(field('الحشوة (مم)', numberInput(st.padding, function (v) { update(function () { st.padding = v; }); })));
      sec.appendChild(g);
      var align = el('div', { class: 'seg' });
      [['right', 'يمين'], ['center', 'وسط'], ['left', 'يسار'], ['justify', 'ضبط']].forEach(function (a) {
        align.appendChild(el('label', {}, [el('input', { type: 'radio', name: 'dz-align', value: a[0], checked: st.align === a[0], disabled: readonly,
          onchange: function () { update(function () { st.align = a[0]; }); } }), el('span', { text: a[1] })]));
      });
      sec.appendChild(field('المحاذاة', align));
      sec.appendChild(checkbox('عريض', st.bold, function (v) { st.bold = v; }));
      sec.appendChild(checkbox('تصغير النص ليناسب الإطار', st.shrink, function (v) { st.shrink = v; }));
      sec.appendChild(field('لون النص', colorInput(st.color, function (v) { update(function () { st.color = v; }, false); })));
      sec.appendChild(field('الخلفية', colorInput(st.bg, function (v) { update(function () { st.bg = v; }, false); }, true)));
      sec.appendChild(field('استدارة الزوايا (مم)', numberInput(st.radius, function (v) { update(function () { st.radius = v; }); })));
      sec.appendChild(linkField(item));
    } else if (item.type === 'image') {
      sec.appendChild(imageDrop(item.src, function (path) { update(function () { item.src = path; }); }));
      sec.appendChild(field('الحجم', selectInput(item.fit, { stretch: 'ملء الإطار', width: 'بعرض الإطار (بنسبة الصورة)' }, function (v) { update(function () { item.fit = v; }); })));
      sec.appendChild(linkField(item));
    } else if (item.type === 'rect') {
      sec.appendChild(field('اللون', colorInput(st.bg, function (v) { update(function () { st.bg = v; }, false); }, true)));
      sec.appendChild(field('استدارة الزوايا (مم)', numberInput(st.radius, function (v) { update(function () { st.radius = v; }); })));
      sec.appendChild(field('سماكة الإطار (مم)', numberInput(st.borderWidth, function (v) { update(function () { st.borderWidth = v; }); }, '0.1')));
      sec.appendChild(field('لون الإطار', colorInput(st.borderColor, function (v) { update(function () { st.borderColor = v; }, false); })));
    } else if (item.type === 'line') {
      sec.appendChild(field('اللون', colorInput(st.color, function (v) { update(function () { st.color = v; }, false); })));
      sec.appendChild(field('السماكة (مم)', numberInput(st.borderWidth || 0.3, function (v) { update(function () { st.borderWidth = v; }); }, '0.1')));
    } else if (item.type === 'qr') {
      var qr = el('input', { class: 'form-input form-input-sm', dir: 'ltr', value: item.qr, disabled: readonly,
        onchange: function (e) { update(function () { item.qr = e.target.value; }); } });
      sec.appendChild(field('محتوى الرمز', qr, 'رابط أو متغير، مثل {{publication_url}}.'));
      sec.appendChild(variableChips(function (token) { update(function () { item.qr = token; }); }, ['publication_url', 'site_url', 'archive_url', 'whatsapp']));
    }
    props.appendChild(sec);
  }

  function checkbox(label, value, set) {
    return el('label', { class: 'form-check text-sm' }, [
      el('input', { type: 'checkbox', class: 'form-checkbox', checked: !!value, disabled: readonly, onchange: function (e) { update(function () { set(e.target.checked); }); } }),
      el('span', { text: label }),
    ]);
  }

  function linkField(item) {
    var input = el('input', { class: 'form-input form-input-sm', dir: 'ltr', value: item.link || '', disabled: readonly, placeholder: 'https://… أو {{archive_url}}',
      onchange: function (e) { update(function () { item.link = e.target.value; }); } });
    var wrap = el('div', { class: 'space-y-2' }, [field('رابط عند النقر (اختياري)', input)]);
    wrap.appendChild(variableChips(function (token) { update(function () { item.link = token; }); }, ['archive_url', 'publication_url', 'site_url', 'other_file_url']));
    return wrap;
  }

  function variableChips(onpick, only) {
    var box = el('div', { class: 'flex flex-wrap gap-1' });
    if (readonly) return box;
    Object.keys(config.variables).forEach(function (key) {
      if (only && only.indexOf(key) === -1) return;
      if (key === 'category_name' && state.page !== 'section' && !only) return;
      box.appendChild(el('button', { type: 'button', class: 'dz-chip', title: config.variables[key], text: config.variables[key].split(' (')[0],
        onclick: function () { onpick('{{' + key + '}}'); } }));
    });
    return box;
  }

  function imageDrop(path, onchange, label) {
    var zone = el('div', { class: 'dz-drop' + (readonly ? ' is-readonly' : ''), tabindex: readonly ? null : '0' });
    var input = el('input', { type: 'file', accept: 'image/*', class: 'sr-only' });
    function draw(p) {
      zone.innerHTML = '';
      if (p) zone.appendChild(el('img', { src: assetUrl(p), alt: '' }));
      zone.appendChild(el('span', { class: 'text-xs text-muted', text: readonly ? (p ? '' : 'بلا صورة') : (p ? 'اسحب صورة أخرى للاستبدال' : (label || 'اسحب صورة وأفلتها هنا، أو اضغط للاختيار')) }));
      zone.appendChild(input);
    }
    function upload(file) {
      if (!file || !/^image\//.test(file.type)) return;
      zone.classList.add('is-busy');
      var data = new FormData();
      data.append('image', file);
      fetch(config.urls.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: data })
        .then(function (r) { return r.ok ? r.json() : r.json().then(function (j) { throw new Error(j.message || 'تعذّر الرفع'); }); })
        .then(function (j) { onchange(j.path); })
        .catch(function (e) { toast('danger', e.message); })
        .finally(function () { zone.classList.remove('is-busy'); });
    }
    draw(path);
    if (!readonly) {
      zone.addEventListener('click', function () { input.click(); });
      zone.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
      input.addEventListener('change', function () { upload(input.files[0]); });
      ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-over'); }); });
      zone.addEventListener('drop', function (e) { upload(e.dataTransfer.files[0]); });
    }
    return zone;
  }

  function pageProps() {
    var page = current();
    props.appendChild(el('div', { class: 'designer-section' }, [
      el('p', { class: 'designer-title', text: 'خصائص الصفحة: ' + PAGE_LABELS[state.page] }),
      el('p', { class: 'text-xs text-muted', text: 'اختر عنصراً من الصفحة أو من الطبقات لتعديله.' }),
    ]));

    var bg = el('div', { class: 'designer-section space-y-3' });
    bg.appendChild(field('صورة الخلفية', imageDrop(page.background.image, function (p) { update(function () { page.background.image = p; }); }, 'اسحب صورة الخلفية بحجم الصفحة')));
    if (page.background.image && !readonly) {
      bg.appendChild(el('button', { type: 'button', class: 'btn btn-xs btn-ghost text-danger-600', text: 'إزالة الخلفية', onclick: function () { update(function () { page.background.image = null; }); } }));
    }
    bg.appendChild(field('حجم الصورة', selectInput(page.background.fit || 'fill', { fill: 'ملء الصفحة كاملة', width: 'بعرض الصفحة من أعلاها' }, function (v) { update(function () { page.background.fit = v; }); })));
    bg.appendChild(field('لون الخلفية', colorInput(page.background.color, function (v) { update(function () { page.background.color = v || '#ffffff'; }, false); })));
    props.appendChild(bg);

    if (state.page === 'section') {
      var cats = el('div', { class: 'designer-section space-y-2' }, [
        el('p', { class: 'designer-title', text: 'خلفية خاصة لقسم' }),
        el('p', { class: 'text-xs text-muted', text: 'اختياري: خلفية مختلفة لصفحات قسم معيّن بدل الخلفية العامة.' }),
      ]);
      var previewSel = el('select', { class: 'form-select form-input-sm', 'aria-label': 'معاينة الصفحة كقسم', onchange: function (e) { state.previewCat = e.target.value || null; renderPage(); } },
        [el('option', { value: '', text: 'معاينة بالخلفية العامة' })]);
      Object.keys(config.categories).forEach(function (id) {
        previewSel.appendChild(el('option', { value: id, text: 'معاينة كقسم: ' + config.categories[id], selected: String(state.previewCat) === String(id) }));
      });
      cats.appendChild(previewSel);
      Object.keys(config.categories).forEach(function (id) {
        var has = page.categoryBackgrounds && page.categoryBackgrounds[id];
        var row = el('details', { class: 'dz-cat', open: !!has }, [el('summary', { text: config.categories[id] + (has ? ' ✓' : '') })]);
        row.appendChild(imageDrop(has, function (p) { update(function () { page.categoryBackgrounds = page.categoryBackgrounds || {}; page.categoryBackgrounds[id] = p; }); }));
        if (has && !readonly) row.appendChild(el('button', { type: 'button', class: 'btn btn-xs btn-ghost text-danger-600', text: 'إزالة', onclick: function () { update(function () { delete page.categoryBackgrounds[id]; }); } }));
        cats.appendChild(row);
      });
      props.appendChild(cats);
    }

    var g = el('div', { class: 'designer-section grid grid-cols-2 gap-2' }, [el('p', { class: 'designer-title col-span-2', text: 'إعدادات القالب (كل الصفحات)' })]);
    var formats = {}; Object.keys(config.formats).forEach(function (k) { formats[k] = k; });
    g.appendChild(field('الورق', selectInput(design.page.format, formats, function (v) { update(function () { design.page.format = v; }); })));
    g.appendChild(field('الاتجاه', selectInput(design.page.orientation, { P: 'طولي', L: 'عرضي' }, function (v) { update(function () { design.page.orientation = v; }); })));
    var font = field('خط القالب', selectInput(design.page.font, fontOptions(false), function (v) { update(function () { design.page.font = v; }); }));
    font.classList.add('col-span-2');
    g.appendChild(font);
    props.appendChild(g);
  }

  function contentProps() {
    var c = current().content;
    var sec = el('div', { class: 'designer-section grid grid-cols-2 gap-2' }, [
      el('p', { class: 'designer-title col-span-2', text: 'منطقة المحتوى' }),
      el('p', { class: 'text-xs text-muted col-span-2', text: 'هنا تُطبع أخبار كل صفحة بقالب صندوقها (من «أقسام ملف الـ PDF» في الإعدادات). اسحبها وغيّر حجمها على الصفحة.' }),
    ]);
    [['x', 'من اليسار'], ['y', 'من الأعلى'], ['w', 'العرض'], ['h', 'الارتفاع']].forEach(function (p) {
      sec.appendChild(field(p[1] + ' (مم)', numberInput(c[p[0]], function (v) { update(function () { c[p[0]] = v; }); })));
    });
    props.appendChild(sec);
  }

  /* ------------------------------------------------------------ إضافة وحذف */
  function newElement(type, x, y) {
    var size = pageSize();
    var base = { id: uid(), type: type, name: '', x: 0, y: 0, w: 80, h: 14, link: '',
      style: { font: 'inherit', size: 16, lineHeight: 1.4, bold: false, align: 'right', color: '#13232e', bg: null, radius: 0, padding: 0, borderWidth: 0, borderColor: '#13232e', shrink: false } };
    if (type === 'text') { base.text = 'نص جديد'; }
    if (type === 'variable') { base.type = 'text'; base.text = '{{date_long}}'; base.style.align = 'center'; }
    if (type === 'image') { base.w = 60; base.h = 40; base.src = null; base.fit = 'stretch'; }
    if (type === 'rect') { base.w = 60; base.h = 30; base.style.bg = '#0b4f6c'; base.style.radius = 3; }
    if (type === 'line') { base.w = 120; base.h = 1; base.style.color = '#13232e'; base.style.borderWidth = 0.4; }
    if (type === 'qr') { base.w = 30; base.h = 30; base.qr = '{{publication_url}}'; }
    base.x = round(Math.max(0, Math.min(size[0] - base.w, (x == null ? size[0] / 2 : x) - base.w / 2)));
    base.y = round(Math.max(0, Math.min(size[1] - base.h, (y == null ? size[1] / 2 : y) - base.h / 2)));
    return base;
  }
  function add(type, x, y) {
    if (readonly) return;
    snapshot();
    var item = newElement(type, x, y);
    current().elements.push(item);
    state.selected = item.id;
    render();
  }
  function remove() {
    if (readonly || !selected()) return;
    snapshot();
    current().elements = current().elements.filter(function (e) { return e.id !== state.selected; });
    state.selected = null;
    render();
  }
  function duplicate() {
    var item = selected();
    if (readonly || !item) return;
    snapshot();
    var copy = clone(item);
    copy.id = uid(); copy.x = round(copy.x + 4); copy.y = round(copy.y + 4);
    current().elements.push(copy);
    state.selected = copy.id;
    render();
  }

  /* ------------------------------------------------------------ السحب والتحجيم */
  var drag = null;

  pageEl.addEventListener('pointerdown', function (e) {
    var node = e.target.closest('[data-id]');
    if (!node) { state.selected = null; render(); return; }
    var id = node.getAttribute('data-id');
    if (state.selected !== id) { state.selected = id; render(); node = pageEl.querySelector('[data-id="' + id + '"]'); }
    if (readonly) return;

    var target = id === '__content' ? current().content : selected();
    drag = { id: id, handle: e.target.getAttribute('data-handle'), startX: e.clientX, startY: e.clientY, orig: { x: target.x, y: target.y, w: target.w, h: target.h }, moved: false };
    pageEl.setPointerCapture(e.pointerId);
    e.preventDefault();
  });

  pageEl.addEventListener('pointermove', function (e) {
    if (!drag) return;
    var s = state.scale;
    var dx = (e.clientX - drag.startX) / s, dy = (e.clientY - drag.startY) / s;
    // اتجاه الصفحة LTR في الإحداثيات دائماً (x من اليسار)، مثل mPDF
    if (!drag.moved) { if (Math.abs(dx) + Math.abs(dy) < 0.5) return; snapshot(); drag.moved = true; }

    var target = drag.id === '__content' ? current().content : selected();
    var o = drag.orig, snap = e.altKey ? function (v) { return round(v); } : function (v) { return Math.round(v * 2) / 2; };
    var h = drag.handle;

    if (!h) {
      target.x = snap(o.x + dx); target.y = snap(o.y + dy);
    } else {
      if (h.indexOf('e') !== -1) target.w = Math.max(2, snap(o.w + dx));
      if (h.indexOf('s') !== -1) target.h = Math.max(1, snap(o.h + dy));
      if (h.indexOf('w') !== -1) { var nw = Math.max(2, snap(o.w - dx)); target.x = snap(o.x + o.w - nw); target.w = nw; }
      if (h.indexOf('n') !== -1) { var nh = Math.max(1, snap(o.h - dy)); target.y = snap(o.y + o.h - nh); target.h = nh; }
    }
    var node = pageEl.querySelector('[data-id="' + drag.id + '"]');
    if (node) place(node, target.type ? target : Object.assign({ style: {} }, target));
  });

  function endDrag() {
    if (!drag) return;
    var moved = drag.moved;
    drag = null;
    if (moved) render();
  }
  pageEl.addEventListener('pointerup', endDrag);
  pageEl.addEventListener('pointercancel', endDrag);

  /* ------------------------------------------------------------ لوحة العناصر (سحب وإفلات) */
  root.querySelectorAll('[data-designer-tool]').forEach(function (tool) {
    tool.addEventListener('click', function () { add(tool.getAttribute('data-designer-tool')); });
    tool.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/x-designer-tool', tool.getAttribute('data-designer-tool')); e.dataTransfer.effectAllowed = 'copy'; });
  });
  pageEl.addEventListener('dragover', function (e) { if (!readonly) { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; } });
  pageEl.addEventListener('drop', function (e) {
    var type = e.dataTransfer.getData('text/x-designer-tool');
    var rect = pageEl.getBoundingClientRect();
    var x = (e.clientX - rect.left) / state.scale, y = (e.clientY - rect.top) / state.scale;
    if (type) { e.preventDefault(); add(type, x, y); return; }
    // صورة من الجهاز مباشرة على الصفحة: عنصر صورة في موضع الإفلات
    var file = e.dataTransfer.files && e.dataTransfer.files[0];
    if (file && /^image\//.test(file.type) && !readonly) {
      e.preventDefault();
      var data = new FormData(); data.append('image', file);
      fetch(config.urls.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: data })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (!j.path) throw new Error(j.message || 'تعذّر الرفع'); add('image', x, y); selected().src = j.path; render(); })
        .catch(function (err) { toast('danger', err.message); });
    }
  });

  /* ------------------------------------------------------------ الصفحات والتكبير */
  root.querySelectorAll('[data-designer-pages] input').forEach(function (radio) {
    radio.addEventListener('change', function () { state.page = radio.value; state.selected = null; render(); });
  });
  var zoom = root.querySelector('[data-designer-zoom]');
  zoom.addEventListener('change', function () { state.zoom = zoom.value; renderPage(); });
  window.addEventListener('resize', function () { if (state.zoom === 'fit') renderPage(); });

  /* ------------------------------------------------------------ لوحة المفاتيح */
  document.addEventListener('keydown', function (e) {
    var typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
    var mod = e.ctrlKey || e.metaKey;
    if (mod && e.key.toLowerCase() === 's') { e.preventDefault(); save(); return; }
    if (typing || readonly) return;
    if (mod && e.key.toLowerCase() === 'z') { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
    if (mod && e.key.toLowerCase() === 'y') { e.preventDefault(); redo(); return; }
    if (mod && e.key.toLowerCase() === 'd') { e.preventDefault(); duplicate(); return; }
    var item = state.selected === '__content' ? current().content : selected();
    if (!item) return;
    if (e.key === 'Delete' || e.key === 'Backspace') { if (state.selected !== '__content') { e.preventDefault(); remove(); } return; }
    var step = e.shiftKey ? 5 : 1;
    var moves = { ArrowLeft: ['x', -step], ArrowRight: ['x', step], ArrowUp: ['y', -step], ArrowDown: ['y', step] };
    if (moves[e.key]) { e.preventDefault(); update(function () { item[moves[e.key][0]] = round(item[moves[e.key][0]] + moves[e.key][1]); }); }
  });

  /* ------------------------------------------------------------ الحفظ والمعاينة */
  var nameInput = root.querySelector('[data-designer-name]');
  if (nameInput) nameInput.addEventListener('input', markDirty);

  function toast(type, title) { if (window.UI && UI.toast) UI.toast({ type: type, title: title }); }

  function save() {
    if (readonly) return;
    if (statusEl) statusEl.textContent = 'جارٍ الحفظ…';
    fetch(config.urls.save, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ name: nameInput.value, design: design }),
    })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'تعذّر الحفظ'); return j; }); })
      .then(function (j) {
        design = j.design; state.dirty = false;
        if (statusEl) statusEl.textContent = 'حُفظ ' + (j.savedAt || '');
        toast('success', 'تم حفظ القالب');
        render();
      })
      .catch(function (e) { if (statusEl) statusEl.textContent = 'لم يُحفظ'; toast('danger', e.message); });
  }
  var saveBtn = root.querySelector('[data-designer-save]');
  if (saveBtn) saveBtn.addEventListener('click', save);
  var undoBtn = root.querySelector('[data-designer-undo]'), redoBtn = root.querySelector('[data-designer-redo]');
  if (undoBtn) undoBtn.addEventListener('click', undo);
  if (redoBtn) redoBtn.addEventListener('click', redo);

  var modal = document.getElementById('designer-preview');
  var frame = modal.querySelector('[data-preview-frame]');
  var loading = modal.querySelector('[data-preview-loading]');
  var lastUrl = null;

  root.querySelector('[data-designer-preview]').addEventListener('click', function () {
    loading.hidden = false; loading.textContent = 'جارٍ توليد المعاينة…';
    frame.classList.add('invisible');
    UI.openModal(modal);
    var publication = root.querySelector('[data-designer-publication]').value;
    fetch(config.urls.preview, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/pdf, application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ design: design, publication: publication || null }),
    })
      .then(function (r) { if (!r.ok) throw new Error('تعذّر توليد المعاينة (' + r.status + ')'); return r.blob(); })
      .then(function (blob) {
        if (lastUrl) URL.revokeObjectURL(lastUrl);
        lastUrl = URL.createObjectURL(blob);
        frame.src = lastUrl;
        frame.onload = function () { frame.classList.remove('invisible'); loading.hidden = true; };
      })
      .catch(function (e) { loading.textContent = e.message; });
  });

  window.addEventListener('beforeunload', function (e) { if (state.dirty && !readonly) { e.preventDefault(); e.returnValue = ''; } });

  render();
})();
