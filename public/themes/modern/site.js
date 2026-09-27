/* الواجهة الجديدة: القائمة على الجوال، الصوتيات، فيديو يوتيوب عند الطلب، نسخ الرابط، نافذة الاشتراك. */
(function () {
  'use strict';

  // قائمة الأقسام على الجوال
  var menu = document.querySelector('[data-menu]');
  var nav = document.getElementById('site-nav');
  if (menu && nav) {
    menu.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      menu.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // الصوتيات: مشغّل واحد في كل مرة مع شريط تقدّم
  var current = null;
  document.querySelectorAll('[data-audio]').forEach(function (row) {
    var audio = row.querySelector('audio');
    var btn = row.querySelector('[data-audio-toggle]');
    var bar = row.querySelector('.audio-bar span');
    if (!audio || !btn) return;
    btn.addEventListener('click', function () {
      if (audio.paused) {
        if (current && current !== audio) current.pause();
        audio.play();
      } else {
        audio.pause();
      }
    });
    audio.addEventListener('play', function () { current = audio; row.classList.add('is-playing'); btn.setAttribute('aria-pressed', 'true'); });
    audio.addEventListener('pause', function () { row.classList.remove('is-playing'); btn.setAttribute('aria-pressed', 'false'); });
    audio.addEventListener('ended', function () { row.classList.remove('is-playing'); if (bar) bar.style.width = '0'; });
    audio.addEventListener('timeupdate', function () {
      if (bar && audio.duration) bar.style.width = (audio.currentTime / audio.duration * 100) + '%';
    });
  });

  // يوتيوب: صورة الفيديو أولاً، والمشغّل يُحمَّل عند النقر فقط (أخف للصفحة)
  document.querySelectorAll('[data-youtube]').forEach(function (frame) {
    frame.addEventListener('click', function () {
      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(frame.getAttribute('data-youtube')) + '?autoplay=1&rel=0';
      iframe.title = frame.getAttribute('aria-label') || 'فيديو';
      iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
      iframe.allowFullscreen = true;
      frame.replaceChildren(iframe);
      frame.removeAttribute('data-youtube');
    }, { once: true });
  });

  // نسخ رابط الخبر
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () {
        btn.classList.add('is-copied');
        btn.setAttribute('aria-label', 'تم النسخ');
        setTimeout(function () { btn.classList.remove('is-copied'); btn.setAttribute('aria-label', 'نسخ الرابط'); }, 1800);
      };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () {});
    });
  });

  // نافذة الاشتراك بالبريد
  document.querySelectorAll('[data-dialog-open]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var dialog = document.getElementById(link.getAttribute('data-dialog-open'));
      if (!dialog || typeof dialog.showModal !== 'function') return;
      e.preventDefault();
      dialog.showModal();
    });
  });
  document.querySelectorAll('dialog').forEach(function (dialog) {
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
  });
})();
