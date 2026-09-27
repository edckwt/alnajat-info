/*
 * الإعدادات ← صناديق الرئيسية والنشرة:
 * ترتيب بالسحب، ملخص كل صندوق، اختيار القالب من المعرض، والمعاينة الحية.
 */
(function () {
  'use strict';

  var modal = document.getElementById('box-preview');
  if (!modal || !window.UI) return;

  var frame = modal.querySelector('[data-preview-frame]');
  var loading = modal.querySelector('[data-preview-loading]');
  var title = modal.querySelector('[data-preview-title]');
  var newTab = modal.querySelector('[data-preview-newtab]');
  var activeRow = null; // الصندوق الذي فُتح المعرض لاختيار قالبه

  var $ = function (root, sel) { return root.querySelector(sel); };
  var optionText = function (select) {
    return select && select.value ? select.options[select.selectedIndex].text : '';
  };

  /* ---------------- الملخص والترقيم ---------------- */
  function kindOf(row) {
    var checked = $(row, 'input[type=radio]:checked');
    return checked ? checked.value : 'empty';
  }

  function summarize(row) {
    var out = $(row, '[data-box-summary]');
    var kind = kindOf(row);
    var text = '';
    var warn = false;

    if (kind === 'news') {
      var cat = optionText($(row, '[data-box-category]'));
      var limit = ($(row, '[data-box-limit]') || {}).value || '';
      warn = !cat;
      text = cat ? cat + ' · ' + limit + ' أخبار · ' + $(row, '[data-tpl-name]').textContent.trim() : 'اختر القسم';
    } else if (kind === 'banner') {
      var banner = optionText($(row, '[data-box-banner]'));
      warn = !banner;
      text = banner ? 'بانر: ' + banner : 'اختر البانر';
    } else if (kind === 'code') {
      var code = ($(row, 'textarea') || {}).value || '';
      warn = !code.trim();
      text = code.trim() ? 'كود مخصص (' + code.length + ' حرفاً)' : 'اكتب الكود';
    } else {
      text = 'لا يظهر';
    }

    out.textContent = text;
    out.classList.toggle('text-warning-600', warn);
  }

  function renumber(list) {
    list.querySelectorAll('[data-box]').forEach(function (row, i) {
      $(row, '[data-box-num]').textContent = i + 1;
      $(row, '[data-box-position]').value = i + 1;
    });
  }

  document.querySelectorAll('[data-boxes]').forEach(function (list) {
    list.querySelectorAll('[data-box]').forEach(function (row) {
      summarize(row);
      row.addEventListener('change', function () { summarize(row); });
      row.addEventListener('input', function () { summarize(row); });
    });

    if (window.Sortable) {
      Sortable.create(list, {
        handle: '[data-box-handle]',
        animation: 160,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onSort: function () { renumber(list); },
      });
    }
  });

  /* ---------------- المعاينة ---------------- */
  function openPreview(context, categoryId, type, limit, label) {
    var params = new URLSearchParams({ context: context, category_id: categoryId, type: type, limit: limit || 5 });
    var url = modal.dataset.previewUrl + '?' + params.toString();

    title.textContent = label;
    newTab.href = url;
    frame.classList.add('invisible');
    loading.hidden = false;
    frame.src = url;
    UI.openModal(modal);
  }

  frame.addEventListener('load', function () {
    if (frame.src && frame.src !== 'about:blank') {
      frame.classList.remove('invisible');
      loading.hidden = true;
    }
  });

  document.addEventListener('modal:close', function (e) {
    if (e.detail.modal === modal) frame.src = 'about:blank';
  });

  // Escape يغلق المعاينة أولاً إن كانت فوق المعرض
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      e.stopImmediatePropagation();
      UI.closeModal(modal);
    }
  }, true);

  function previewRow(row) {
    var category = $(row, '[data-box-category]');
    if (!category.value) {
      category.classList.add('is-invalid');
      category.focus();
      setTimeout(function () { category.classList.remove('is-invalid'); }, 1600);
      return;
    }
    var label = optionText(category) + ' — ' + $(row, '[data-tpl-name]').textContent.trim();
    openPreview(row.dataset.context, category.value, $(row, '[data-box-type]').value, $(row, '[data-box-limit]').value, label);
  }

  /* ---------------- معرض القوالب ---------------- */
  function gallery(context) {
    return document.querySelector('[data-tpl-gallery="' + context + '"]');
  }

  function openGallery(context, row) {
    var g = gallery(context);
    if (!g) return;
    activeRow = row || null;

    var type = row ? $(row, '[data-box-type]').value : null;
    g.querySelectorAll('[data-tpl-option]').forEach(function (opt) {
      opt.classList.toggle('is-selected', opt.dataset.tplOption === type);
    });

    var catSelect = $(g, '[data-tpl-preview-category]');
    var rowCat = row ? $(row, '[data-box-category]').value : '';
    if (rowCat) { if (window.Plugins) Plugins.setValue(catSelect, rowCat); else catSelect.value = rowCat; }

    $(g, '[data-tpl-gallery-hint]').textContent = row
      ? 'اختر قالباً للصندوق ' + $(row, '[data-box-num]').textContent + '، أو اضغط «معاينة» لتراه بأخبار القسم.'
      : 'اضغط «معاينة» لترى أي قالب بأخبار القسم المختار.';

    UI.openModal(g);
  }

  function chooseTemplate(option) {
    if (!activeRow) return false;
    var type = option.dataset.tplOption;
    $(activeRow, '[data-box-type]').value = type;
    $(activeRow, '[data-tpl-name]').textContent = option.dataset.tplLabel;
    $(activeRow, '[data-tpl-thumb]').innerHTML = $(option, 'svg').outerHTML;
    summarize(activeRow);
    UI.closeModal(option.closest('.modal'));
    return true;
  }

  document.addEventListener('click', function (e) {
    var t;

    if ((t = e.target.closest('[data-box-preview]'))) {
      previewRow(t.closest('[data-box]'));
    } else if ((t = e.target.closest('[data-tpl-open]'))) {
      var row = t.closest('[data-box]');
      openGallery(row.dataset.context, row);
    } else if ((t = e.target.closest('[data-tpl-gallery-open]'))) {
      openGallery(t.dataset.tplGalleryOpen, null);
    } else if ((t = e.target.closest('[data-tpl-choose]'))) {
      var option = t.closest('[data-tpl-option]');
      // من زر «معرض القوالب» (بلا صندوق): الضغط على القالب يعرض معاينته
      if (!chooseTemplate(option)) previewOption(option);
    } else if ((t = e.target.closest('[data-tpl-live]'))) {
      previewOption(t.closest('[data-tpl-option]'));
    } else {
      return;
    }
    e.preventDefault();
  });

  function previewOption(option) {
    var g = option.closest('[data-tpl-gallery]');
    var catSelect = $(g, '[data-tpl-preview-category]');
    var limit = activeRow ? $(activeRow, '[data-box-limit]').value : 4;
    openPreview(g.dataset.tplGallery, catSelect.value, option.dataset.tplOption, limit,
      optionText(catSelect) + ' — ' + option.dataset.tplLabel);
  }
})();
