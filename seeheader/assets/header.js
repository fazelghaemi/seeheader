(function () {
  'use strict';
  const breakpoint = Number(window.SeeHeaderConfig && window.SeeHeaderConfig.breakpoint) || 782;
  const isMobile = () => window.innerWidth <= breakpoint;
  let previousMobile = isMobile();

  function closeMega(item) {
    item.classList.remove('is-open');
    const link = item.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
    if (link) link.setAttribute('aria-expanded', 'false');
    const button = item.querySelector(':scope > .sh-submenu-toggle');
    if (button) button.setAttribute('aria-expanded', 'false');
    item.querySelectorAll('li.is-open').forEach(child => { child.classList.remove('is-open'); child.querySelector(':scope > .sh-submenu-toggle')?.setAttribute('aria-expanded', 'false'); });
  }

  function syncMobileState(header) {
    if (!header) return;
    const root = header.querySelector('.sh-menu > li.is-open');
    header.classList.toggle('sh-mobile-depth', !!root && isMobile());
    const back = header.querySelector('.sh-mobile-back');
    if (back && root) {
      const active = [...root.querySelectorAll('li.is-open')].pop() || root;
      const label = active.querySelector(':scope > a > span:not(.sh-menu-icon):not(.sh-menu-thumbnail),:scope > .sh-menu-heading-toggle > span')?.textContent.trim() || 'فهرست';
      back.querySelector('span').textContent = label;
    }
  }

  function toggleBranch(item) {
    const opening = !item.classList.contains('is-open');
    [...item.parentElement.children].filter(sibling => sibling !== item && sibling.matches('li.is-open')).forEach(closeMega);
    if (opening) item.classList.add('is-open'); else closeMega(item);
    const link = item.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
    const button = item.querySelector(':scope > .sh-submenu-toggle');
    if (link) link.setAttribute('aria-expanded', String(opening));
    if (button) button.setAttribute('aria-expanded', String(opening));
    syncMobileState(item.closest('.sh-header'));
  }

  document.querySelectorAll('.sh-header').forEach(header => {
    const nav = header.querySelector('.sh-nav');
    if (!nav) return;
    const back = document.createElement('button');
    back.type = 'button'; back.className = 'sh-mobile-back';
    back.setAttribute('aria-label', 'بازگشت به فهرست');
    back.innerHTML = '<span>فهرست</span>';
    nav.prepend(back);
    header.querySelectorAll('.sh-menu li').forEach(item => {
      if (!item.querySelector(':scope > .sub-menu,:scope > .sh-mega-panel')) return;
      const control = item.querySelector(':scope > a,:scope > .sh-menu-heading-toggle');
      if (!control) return;
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'sh-submenu-toggle';
      button.setAttribute('aria-label', 'باز کردن زیرمنوی ' + control.textContent.trim());
      button.setAttribute('aria-expanded', 'false');
      item.insertBefore(button, control.nextSibling);
    });
    header.querySelectorAll('.sh-mega-column > .sh-menu-heading-toggle').forEach(button => button.setAttribute('aria-expanded', 'true'));
  });

  function closeHeader(header) {
    header.classList.remove('sh-open');
    header.classList.remove('sh-mobile-depth');
    document.body.classList.remove('sh-mobile-menu-open');
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

    const back = event.target.closest('.sh-header .sh-mobile-back');
    if (back) {
      const header = back.closest('.sh-header');
      const open = [...header.querySelectorAll('.sh-menu li.is-open')].pop();
      if (open) closeMega(open);
      syncMobileState(header);
      if (!header.classList.contains('sh-mobile-depth')) header.querySelector('.sh-menu > li > a')?.focus();
      return;
    }
    const branchButton = event.target.closest('.sh-header .sh-submenu-toggle');
    if (branchButton) { event.preventDefault(); toggleBranch(branchButton.parentElement); return; }

    const groupButton = event.target.closest('.sh-header .sh-mega-column > .sh-menu-heading-toggle');
    if (groupButton) {
      event.preventDefault();
      const collapsed = groupButton.parentElement.classList.toggle('is-collapsed');
      groupButton.setAttribute('aria-expanded', String(!collapsed));
      return;
    }

    const toggle = event.target.closest('.sh-header .sh-toggle');
    if (toggle) {
      const header = toggle.closest('.sh-header');
      const open = header.classList.toggle('sh-open');
      const top = header.querySelector('.sh-main')?.getBoundingClientRect().bottom || 58;
      header.style.setProperty('--sh-mobile-top', Math.max(0, top) + 'px');
      document.body.classList.toggle('sh-mobile-menu-open', open && isMobile());
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
      if (!open) header.querySelectorAll('.sh-has-mega.is-open,.sh-has-submenu.is-open,.menu-item-has-children.is-open').forEach(closeMega);
      syncMobileState(header);
      return;
    }

    const megaLink = event.target.closest('.sh-has-mega > a,.sh-has-mega > .sh-menu-heading-toggle,.sh-has-submenu > a,.sh-has-submenu > .sh-menu-heading-toggle,.sh-menu .menu-item-has-children > a');
    if (megaLink) {
      const item = megaLink.parentElement;
      const placeholder = megaLink.tagName === 'BUTTON' || megaLink.getAttribute('href') === '#';
      if (placeholder) {
        event.preventDefault();
        toggleBranch(item);
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
    const mobile = isMobile();
    if (mobile !== previousMobile) {
      document.querySelectorAll('.sh-header').forEach(header => {
        header.querySelectorAll('.sh-menu li.is-open').forEach(closeMega);
        header.querySelectorAll('.sh-menu li > [aria-expanded="true"]').forEach(control => control.setAttribute('aria-expanded', 'false'));
        header.classList.remove('sh-mobile-depth');
      });
      previousMobile = mobile;
    }
    if (!isMobile()) document.querySelectorAll('.sh-header.sh-open').forEach(closeHeader);
    else document.querySelectorAll('.sh-header.sh-open').forEach(header => { header.style.setProperty('--sh-mobile-top', Math.max(0, header.querySelector('.sh-main')?.getBoundingClientRect().bottom || 58) + 'px'); syncMobileState(header); });
  });
})();
