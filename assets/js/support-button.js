(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('spb-toggle');
    var menu   = document.getElementById('spb-menu');

    if (!toggle || !menu) return;

    function open() {
      menu.setAttribute('aria-hidden', 'false');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'بستن منوی پشتیبانی');
    }

    function close() {
      menu.setAttribute('aria-hidden', 'true');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'باز کردن منوی پشتیبانی');
    }

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      toggle.getAttribute('aria-expanded') === 'true' ? close() : open();
    });

    document.addEventListener('click', function (e) {
      if (!document.getElementById('spb-widget').contains(e.target)) {
        close();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  });
})();
