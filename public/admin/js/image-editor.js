/* ==========================================================================
   image-editor.js — محرر الصور قبل الرفع: قص بنسب جاهزة، تدوير وقلب، تصغير، صيغة وجودة.
   يعمل على الملف في المتصفح ثم يعيد ملفاً جديداً يُرفع مع النموذج كالمعتاد.

   ImageEditor.open(file, { aspect, maxWidth })  → Promise<File|null>   (null = إلغاء)
   ImageEditor.fit(file, maxWidth)               → Promise<{ file, from, to } | null>
   ImageEditor.canEdit(file)                     → هل هو نوع صورة يمكن تحريره

   Cropper.js (MIT) محفوظ محلياً في public/admin/vendor/cropper ويُحمَّل عند أول فتح فقط.
   ========================================================================== */
(function (global) {
  'use strict';

  var script = document.currentScript;
  var VENDOR = script ? script.src.replace(/js\/image-editor\.js.*$/, 'vendor/cropper/') : '/admin/vendor/cropper/';
  var EDITABLE = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
  var RESIZABLE = ['image/jpeg', 'image/png', 'image/webp'];
  var TYPES = { 'image/jpeg': ['JPEG', 'jpg'], 'image/webp': ['WebP', 'webp'], 'image/png': ['PNG', 'png'] };
  var RATIOS = [
    ['حر', NaN], ['الأصل', 'orig'], ['1:1', 1], ['4:3', 4 / 3], ['16:9', 16 / 9], ['3:4', 3 / 4], ['A4', 210 / 297],
  ];
  var ICONS = {
    rotL: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    rotR: '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
    flipH: '<path d="M12 3v18"/><path d="m16 7 4 5-4 5V7ZM8 7l-4 5 4 5V7Z"/>',
    flipV: '<path d="M3 12h18"/><path d="m7 8 5-4 5 4H7ZM7 16l5 4 5-4H7Z"/>',
    zoomIn: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>',
    zoomOut: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8 11h6"/>',
    fit: '<path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/>',
    move: '<path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/>',
  };

  function icon(name) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICONS[name] + '</svg>';
  }
  function humanSize(b) {
    var u = ['B', 'KB', 'MB', 'GB'], i = b ? Math.floor(Math.log(b) / Math.log(1024)) : 0;
    return (b / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + u[i];
  }
  function baseName(name) { return String(name || 'image').replace(/\.[^.]+$/, ''); }
  function canvasToFile(canvas, type, quality, name) {
    return new Promise(function (resolve) {
      canvas.toBlob(function (blob) {
        if (!blob) { resolve(null); return; }
        resolve(new File([blob], baseName(name) + '.' + TYPES[type][1], { type: type, lastModified: Date.now() }));
      }, type, quality);
    });
  }
  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () { resolve({ img: img, url: url }); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
      img.src = url;
    });
  }

  /* ---------- تحميل Cropper.js عند الحاجة ---------- */
  var cropperReady = null;
  function loadCropper() {
    if (global.Cropper) return Promise.resolve();
    if (cropperReady) return cropperReady;
    cropperReady = new Promise(function (resolve, reject) {
      var css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = VENDOR + 'cropper.min.css';
      document.head.appendChild(css);
      var js = document.createElement('script');
      js.src = VENDOR + 'cropper.min.js';
      js.onload = function () { resolve(); };
      js.onerror = function () { cropperReady = null; reject(new Error('cropper')); };
      document.head.appendChild(js);
    });
    return cropperReady;
  }

  /* ---------- تصغير تلقائي للصور الكبيرة ---------- */
  function fit(file, maxWidth) {
    if (!file || RESIZABLE.indexOf(file.type) === -1 || !maxWidth) return Promise.resolve(null);
    return loadImage(file).then(function (loaded) {
      var w = loaded.img.naturalWidth, h = loaded.img.naturalHeight;
      if (w <= maxWidth) { URL.revokeObjectURL(loaded.url); return null; }
      var nw = Math.round(maxWidth), nh = Math.round(h * maxWidth / w);
      var canvas = document.createElement('canvas');
      canvas.width = nw; canvas.height = nh;
      var ctx = canvas.getContext('2d');
      if (file.type === 'image/jpeg') { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, nw, nh); }
      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(loaded.img, 0, 0, nw, nh);
      URL.revokeObjectURL(loaded.url);
      return canvasToFile(canvas, file.type, 0.9, file.name).then(function (out) {
        // إن لم يصغر الحجم فعلاً نُبقي الأصل
        return out && out.size < file.size ? { file: out, from: [w, h], to: [nw, nh] } : null;
      });
    }).catch(function () { return null; });
  }

  /* ---------- المحرر ---------- */
  var ui = null, cropper = null, state = null, resolver = null, lastFocus = null;

  function build() {
    ui = document.createElement('div');
    ui.className = 'imged';
    ui.hidden = true;
    ui.setAttribute('role', 'dialog');
    ui.setAttribute('aria-modal', 'true');
    ui.setAttribute('aria-labelledby', 'imged-title');

    var ratios = RATIOS.map(function (r, i) {
      return '<button type="button" class="imged-chip" data-ratio="' + i + '" aria-pressed="false">' + r[0] + '</button>';
    }).join('');
    var types = Object.keys(TYPES).map(function (t) { return '<option value="' + t + '">' + TYPES[t][0] + '</option>'; }).join('');

    ui.innerHTML =
      '<div class="imged-backdrop"></div>' +
      '<div class="imged-dialog">' +
        '<header class="imged-head">' +
          '<div class="min-w-0"><h2 class="imged-title" id="imged-title">تحرير الصورة</h2><p class="imged-file" data-ie="file"></p></div>' +
          '<button type="button" class="btn btn-icon btn-sm btn-ghost" data-ie="cancel" aria-label="إغلاق">' +
            '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
        '</header>' +
        '<div class="imged-body">' +
          '<div class="imged-stage" dir="ltr"><img alt="" data-ie="img"><div class="imged-loading" data-ie="loading">جارٍ تحميل الصورة…</div></div>' +
          '<aside class="imged-side">' +
            '<p class="imged-note" data-ie="note" hidden></p>' +
            '<div class="imged-section"><p class="imged-label">القص</p><div class="imged-chips">' + ratios + '</div></div>' +
            '<div class="imged-section"><p class="imged-label">التدوير والقلب</p>' +
              '<div class="imged-tools">' +
                '<button type="button" class="imged-tool" data-act="rotL" title="تدوير 90° عكس الساعة" aria-label="تدوير لليسار">' + icon('rotL') + '</button>' +
                '<button type="button" class="imged-tool" data-act="rotR" title="تدوير 90° مع الساعة" aria-label="تدوير لليمين">' + icon('rotR') + '</button>' +
                '<button type="button" class="imged-tool" data-act="flipH" title="قلب أفقي" aria-label="قلب أفقي" aria-pressed="false">' + icon('flipH') + '</button>' +
                '<button type="button" class="imged-tool" data-act="flipV" title="قلب عمودي" aria-label="قلب عمودي" aria-pressed="false">' + icon('flipV') + '</button>' +
              '</div>' +
              '<div class="imged-row"><input type="range" dir="ltr" class="imged-range" min="-45" max="45" step="1" value="0" data-ie="angle" aria-label="تدوير دقيق">' +
              '<span class="imged-dims w-10 text-center" data-ie="angle-label" dir="ltr">0°</span></div>' +
            '</div>' +
            '<div class="imged-section"><p class="imged-label">العرض</p>' +
              '<div class="imged-tools">' +
                '<button type="button" class="imged-tool" data-act="zoomIn" title="تكبير" aria-label="تكبير">' + icon('zoomIn') + '</button>' +
                '<button type="button" class="imged-tool" data-act="zoomOut" title="تصغير" aria-label="تصغير">' + icon('zoomOut') + '</button>' +
                '<button type="button" class="imged-tool" data-act="fit" title="ملاءمة الصورة" aria-label="ملاءمة">' + icon('fit') + '</button>' +
                '<button type="button" class="imged-tool" data-act="move" title="سحب الصورة بدل إطار القص" aria-label="تحريك الصورة" aria-pressed="false">' + icon('move') + '</button>' +
              '</div>' +
            '</div>' +
            '<div class="imged-section"><p class="imged-label">حجم الصورة الناتجة</p>' +
              '<div class="imged-row"><input type="number" class="form-input" min="16" step="1" data-ie="width" aria-label="العرض بالبكسل" dir="ltr">' +
              '<span class="text-muted">×</span><input type="number" class="form-input" data-ie="height" aria-label="الارتفاع بالبكسل" dir="ltr" readonly tabindex="-1"></div>' +
              '<div class="imged-chips" data-ie="presets"></div>' +
              '<p class="imged-dims">لا تُكبَّر الصورة فوق حجم القص الأصلي.</p>' +
            '</div>' +
            '<div class="imged-section"><p class="imged-label">الصيغة والجودة</p>' +
              '<div class="imged-row"><select class="form-select" data-ie="type" aria-label="الصيغة">' + types + '</select></div>' +
              '<div class="imged-row" data-ie="quality-row"><input type="range" dir="ltr" class="imged-range" min="50" max="100" step="1" value="88" data-ie="quality" aria-label="الجودة">' +
              '<span class="imged-dims w-10 text-center" data-ie="quality-label" dir="ltr">88%</span></div>' +
            '</div>' +
          '</aside>' +
        '</div>' +
        '<footer class="imged-foot">' +
          '<span class="imged-info" data-ie="info"></span>' +
          '<div class="flex gap-2 ms-auto">' +
            '<button type="button" class="btn btn-ghost btn-sm" data-ie="reset">إعادة الضبط</button>' +
            '<button type="button" class="btn btn-secondary btn-sm" data-ie="cancel">إلغاء</button>' +
            '<button type="button" class="btn btn-primary btn-sm" data-ie="apply">تطبيق</button>' +
          '</div>' +
        '</footer>' +
      '</div>';
    document.body.appendChild(ui);

    ui.addEventListener('click', function (e) {
      var t = e.target.closest('button');
      if (!t || !cropper && !t.matches('[data-ie="cancel"]')) return;
      if (t.matches('[data-ie="cancel"]')) close(null);
      else if (t.matches('[data-ie="apply"]')) apply();
      else if (t.matches('[data-ie="reset"]')) reset();
      else if (t.hasAttribute('data-ratio')) setRatio(+t.getAttribute('data-ratio'));
      else if (t.hasAttribute('data-width')) setWidth(+t.getAttribute('data-width'));
      else if (t.hasAttribute('data-act')) act(t.getAttribute('data-act'), t);
    });
    ui.querySelector('.imged-backdrop').addEventListener('click', function () { close(null); });
    ui.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { e.preventDefault(); close(null); }
      if ((e.key === 'Enter') && (e.ctrlKey || e.metaKey)) { e.preventDefault(); apply(); }
    });
    q('angle').addEventListener('input', function () {
      var v = +this.value;
      q('angle-label').textContent = v + '°';
      if (cropper) cropper.rotateTo(state.baseRotate + v);
    });
    q('width').addEventListener('input', function () { state.width = Math.max(16, +this.value || 0); state.userWidth = true; updateInfo(); });
    q('type').addEventListener('change', function () { state.type = this.value; updateInfo(); });
    q('quality').addEventListener('input', function () { state.quality = +this.value; q('quality-label').textContent = this.value + '%'; });
  }

  function q(name) { return ui.querySelector('[data-ie="' + name + '"]'); }

  function cropSize() {
    var d = cropper.getData(true);
    return [Math.max(1, d.width), Math.max(1, d.height)];
  }

  function outputSize() {
    var c = cropSize();
    var w = Math.min(state.width || c[0], c[0]);
    return [Math.round(w), Math.max(1, Math.round(c[1] * w / c[0]))];
  }

  function updateInfo() {
    if (!cropper) return;
    var c = cropSize();
    if (!state.userWidth) state.width = Math.min(c[0], state.maxWidth || c[0]);
    var out = outputSize();
    q('width').value = out[0];
    q('width').max = c[0];
    q('height').value = out[1];
    q('quality-row').hidden = state.type === 'image/png';
    q('info').innerHTML = 'الأصل <strong dir="ltr">' + state.natural[0] + '×' + state.natural[1] + '</strong> · ' + humanSize(state.file.size) +
      ' ← الناتج <strong dir="ltr">' + out[0] + '×' + out[1] + '</strong> ' + TYPES[state.type][0];

    var presets = [['الأصل', c[0]]];
    [2400, 1600, 1200, 800].forEach(function (w) { if (w < c[0]) presets.push([String(w), w]); });
    q('presets').innerHTML = presets.map(function (p) {
      return '<button type="button" class="imged-chip" data-width="' + p[1] + '" aria-pressed="' + (out[0] === Math.round(p[1]) ? 'true' : 'false') + '" dir="ltr">' + p[0] + '</button>';
    }).join('');
  }

  function setRatio(index) {
    var r = RATIOS[index][1];
    state.ratioIndex = index;
    cropper.setAspectRatio(r === 'orig' ? state.natural[0] / state.natural[1] : r);
    ui.querySelectorAll('[data-ratio]').forEach(function (b) { b.setAttribute('aria-pressed', +b.getAttribute('data-ratio') === index ? 'true' : 'false'); });
  }

  function setWidth(w) { state.width = w; state.userWidth = true; updateInfo(); }

  function act(name, btn) {
    switch (name) {
      case 'rotL': state.baseRotate -= 90; cropper.rotateTo(state.baseRotate + (+q('angle').value)); break;
      case 'rotR': state.baseRotate += 90; cropper.rotateTo(state.baseRotate + (+q('angle').value)); break;
      case 'flipH': state.scaleX = -state.scaleX; cropper.scaleX(state.scaleX); btn.setAttribute('aria-pressed', state.scaleX < 0 ? 'true' : 'false'); break;
      case 'flipV': state.scaleY = -state.scaleY; cropper.scaleY(state.scaleY); btn.setAttribute('aria-pressed', state.scaleY < 0 ? 'true' : 'false'); break;
      case 'zoomIn': cropper.zoom(0.1); break;
      case 'zoomOut': cropper.zoom(-0.1); break;
      case 'fit': cropper.reset(); cropper.rotateTo(state.baseRotate + (+q('angle').value)); break;
      case 'move':
        state.moveMode = !state.moveMode;
        cropper.setDragMode(state.moveMode ? 'move' : 'crop');
        btn.setAttribute('aria-pressed', state.moveMode ? 'true' : 'false');
        break;
    }
  }

  function reset() {
    state.baseRotate = 0; state.scaleX = 1; state.scaleY = 1; state.userWidth = false; state.moveMode = false;
    q('angle').value = 0; q('angle-label').textContent = '0°';
    ui.querySelectorAll('[data-act]').forEach(function (b) { if (b.hasAttribute('aria-pressed')) b.setAttribute('aria-pressed', 'false'); });
    cropper.reset();
    cropper.setDragMode('crop');
    setRatio(state.defaultRatio);
    updateInfo();
  }

  function apply() {
    if (!cropper || state.busy) return;
    state.busy = true;
    var btn = q('apply');
    btn.disabled = true;
    btn.textContent = 'جارٍ الحفظ…';
    var out = outputSize();
    var canvas = cropper.getCroppedCanvas({
      width: out[0], height: out[1],
      fillColor: state.type === 'image/jpeg' ? '#ffffff' : 'transparent',
      imageSmoothingEnabled: true, imageSmoothingQuality: 'high',
    });
    canvasToFile(canvas, state.type, state.quality / 100, state.file.name).then(function (file) {
      state.busy = false;
      btn.disabled = false;
      btn.textContent = 'تطبيق';
      close(file);
    });
  }

  function destroy() {
    if (cropper) { cropper.destroy(); cropper = null; }
    if (state && state.url) URL.revokeObjectURL(state.url);
  }

  function close(result) {
    if (!resolver) return;
    var done = resolver;
    resolver = null;
    destroy();
    ui.hidden = true;
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
    done(result || null);
  }

  function open(file, opts) {
    opts = opts || {};
    if (!ui) build();
    if (resolver) close(null);

    var type = TYPES[file.type] ? file.type : 'image/jpeg';
    var aspect = opts.aspect ? +opts.aspect : NaN;
    var defaultRatio = 0;
    RATIOS.forEach(function (r, i) { if (typeof r[1] === 'number' && Math.abs(r[1] - aspect) < 0.01) defaultRatio = i; });

    state = {
      file: file, type: type, quality: 88, baseRotate: 0, scaleX: 1, scaleY: 1, width: 0, userWidth: false,
      maxWidth: +opts.maxWidth || 0, defaultRatio: defaultRatio, natural: [0, 0], moveMode: false,
    };
    q('file').textContent = file.name + ' · ' + humanSize(file.size);
    q('type').value = type;
    q('quality').value = 88; q('quality-label').textContent = '88%';
    q('angle').value = 0; q('angle-label').textContent = '0°';
    q('info').textContent = '';
    q('loading').hidden = false;
    var note = q('note');
    note.hidden = file.type !== 'image/gif';
    note.textContent = 'الصور المتحركة (GIF) تُحفظ صورة ثابتة بعد التحرير.';
    ui.querySelectorAll('[data-act]').forEach(function (b) { if (b.hasAttribute('aria-pressed')) b.setAttribute('aria-pressed', 'false'); });

    lastFocus = document.activeElement;
    ui.hidden = false;
    document.body.style.overflow = 'hidden';
    q('apply').focus();

    var promise = new Promise(function (resolve) { resolver = resolve; });

    Promise.all([loadCropper(), loadImage(file)]).then(function (res) {
      if (!resolver) { URL.revokeObjectURL(res[1].url); return; }
      var loaded = res[1];
      state.url = loaded.url;
      state.natural = [loaded.img.naturalWidth, loaded.img.naturalHeight];
      var img = q('img');
      img.src = loaded.url;
      cropper = new global.Cropper(img, {
        viewMode: 1, dragMode: 'crop', autoCropArea: 1, responsive: true, restore: false,
        checkOrientation: false, background: true, toggleDragModeOnDblclick: false,
        ready: function () {
          q('loading').hidden = true;
          setRatio(defaultRatio);
          updateInfo();
        },
        crop: function () { if (state) updateInfo(); },
      });
    }).catch(function () {
      q('loading').textContent = 'تعذّر فتح الصورة أو تحميل أداة التحرير.';
    });

    return promise;
  }

  global.ImageEditor = {
    open: open,
    fit: fit,
    canEdit: function (file) { return !!file && EDITABLE.indexOf(file.type) !== -1; },
    canResize: function (file) { return !!file && RESIZABLE.indexOf(file.type) !== -1; },
  };
})(window);
