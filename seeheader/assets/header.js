(function () {
  'use strict';
  const breakpoint = Number(window.SeeHeaderConfig && window.SeeHeaderConfig.breakpoint) || 782;
  const isMobile = () => window.innerWidth <= breakpoint;

  function closeMega(item) {
    item.classList.remove('is-open');
    const link = item.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
    if (link) link.setAttribute('aria-expanded', 'false');
  }

  function closeHeader(header) {
    header.classList.remove('sh-open');
    const toggle = header.querySelector('.sh-toggle');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'باز کردن منو');
    }
    header.querySelectorAll('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open').forEach(closeMega);
  }

  function activateTab(tab, focus) {
    const panel = tab.closest('.sh-mega-panel');
    if (!panel) return;
    const key = tab.dataset.megaTab;
    panel.querySelectorAll('.sh-mega-category').forEach(candidate => {
      const active = candidate === tab;
      candidate.classList.toggle('is-active', active);
      candidate.setAttribute('aria-selected', String(active));
      candidate.tabIndex = active ? 0 : -1;
    });
    panel.querySelectorAll('.sh-mega-category-panel').forEach(candidate => {
      const active = candidate.dataset.megaPanel === key;
      candidate.classList.toggle('is-active', active);
      candidate.setAttribute('aria-hidden', String(!active));
    });
    if (focus) tab.focus();
  }

  document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const tab = event.target.closest('.sh-mega-category[data-mega-tab]');
    if (tab) { activateTab(tab, false); return; }

    const toggle = event.target.closest('.sh-header .sh-toggle');
    if (toggle) {
      const header = toggle.closest('.sh-header');
      const open = header.classList.toggle('sh-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
      if (!open) header.querySelectorAll('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open').forEach(closeMega);
      return;
    }

    const megaLink = event.target.closest('.sh-has-mega > a,.sh-has-mega > .sh-menu-heading-toggle,.sh-has-submenu > a,.sh-has-submenu > .sh-menu-heading-toggle,.sh-menu .menu-item-has-children > a');
    if (megaLink) {
      const item = megaLink.parentElement;
      const placeholder = megaLink.tagName === 'BUTTON' || megaLink.getAttribute('href') === '#';
      if (placeholder || isMobile()) {
        if (isMobile() && item.classList.contains('is-open') && !placeholder) return;
        event.preventDefault();
        const open = !item.classList.contains('is-open');
        item.closest('.sh-header').querySelectorAll('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open').forEach(closeMega);
        item.classList.toggle('is-open', open);
        megaLink.setAttribute('aria-expanded', String(open));
        return;
      }
    }
    if (!event.target.closest('.sh-header')) {
      document.querySelectorAll('.sh-header.sh-open').forEach(closeHeader);
      document.querySelectorAll('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open').forEach(closeMega);
    }
  });

  document.addEventListener('pointerover', event => {
    if (isMobile() || !(event.target instanceof Element)) return;
    const parent = event.target.closest('.sh-has-mega,.sh-has-submenu,.sh-menu .menu-item-has-children');
    if (parent) {
      const control = parent.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
      if (control) control.setAttribute('aria-expanded', 'true');
    }
    const tab = event.target.closest('.sh-theme-digikala .sh-mega-category[data-mega-tab]');
    if (tab && !tab.classList.contains('is-active')) activateTab(tab, false);
  });

  document.addEventListener('pointerout', event => {
    if (isMobile() || !(event.target instanceof Element)) return;
    const parent = event.target.closest('.sh-has-mega,.sh-has-submenu,.sh-menu .menu-item-has-children');
    if (parent && !parent.contains(event.relatedTarget) && !parent.classList.contains('is-open') && !parent.matches(':focus-within')) {
      const control = parent.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
      if (control) control.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('focusin', event => {
    if (isMobile() || !(event.target instanceof Element)) return;
    const parent = event.target.closest('.sh-has-mega,.sh-has-submenu,.sh-menu .menu-item-has-children');
    if (parent) {
      const control = parent.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
      if (control) control.setAttribute('aria-expanded', 'true');
    }
  });
  document.addEventListener('focusout', event => {
    if (isMobile() || !(event.target instanceof Element)) return;
    const parent = event.target.closest('.sh-has-mega,.sh-has-submenu,.sh-menu .menu-item-has-children');
    if (parent && !parent.contains(event.relatedTarget) && !parent.classList.contains('is-open')) {
      const control = parent.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
      if (control) control.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', event => {
    if (!(event.target instanceof Element)) return;
    if (event.key === 'Escape') {
      const openItem = event.target.closest('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open');
      const header = event.target.closest('.sh-header');
      if (openItem) {
        const link = openItem.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
        closeMega(openItem);
        if (link) link.focus();
      } else if (header && header.classList.contains('sh-open')) {
        const toggle = header.querySelector('.sh-toggle');
        closeHeader(header);
        if (toggle) toggle.focus();
      }
      return;
    }
    const tab = event.target.closest('.sh-mega-category[data-mega-tab]');
    if (!tab || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    const tabs = Array.from(tab.closest('.sh-mega-categories').querySelectorAll('.sh-mega-category'));
    const index = tabs.indexOf(tab);
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowLeft' ? 1 : -1) + tabs.length) % tabs.length;
    event.preventDefault();
    activateTab(tabs[next], true);
  });

  window.addEventListener('resize', () => {
    if (!isMobile()) document.querySelectorAll('.sh-header.sh-open').forEach(closeHeader);
  });
})();
