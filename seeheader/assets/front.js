(() => {
  let lastTrigger = null;
  const dialog = document.createElement('dialog');
  dialog.className = 'seenav-dialog';
  dialog.dir = 'rtl';
  dialog.setAttribute('aria-labelledby', 'seenav-dialog-title');
  dialog.innerHTML = '<div class="seenav-dialog__head"><h2 class="seenav-dialog__title" id="seenav-dialog-title"></h2><button type="button" class="seenav-dialog__close" aria-label="بستن">×</button></div><div class="seenav-dialog__body"></div>';
  document.body.appendChild(dialog);
  const title = dialog.querySelector('.seenav-dialog__title');
  const body = dialog.querySelector('.seenav-dialog__body');
  dialog.querySelector('.seenav-dialog__close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('close', () => { lastTrigger?.focus(); body.replaceChildren(); });

  function openDialog(trigger, heading, content) {
    lastTrigger = trigger;
    title.textContent = heading;
    body.replaceChildren(content);
    dialog.showModal();
    const search = dialog.querySelector('input[type="search"]');
    if (search) search.focus();
  }

  document.addEventListener('click', async event => {
    const trigger = event.target.closest('.seenav__item[data-sn-action]');
    if (!trigger) return;
    const nav = trigger.closest('.seenav');
    const action = trigger.dataset.snAction;
    if (action === 'search') {
      const form = document.createElement('form');
      form.className = 'seenav-search';
      form.role = 'search';
      form.method = 'get';
      form.action = nav.dataset.snHome;
      const input = document.createElement('input');
      input.type = 'search'; input.name = 's'; input.placeholder = 'در سایت جستجو کنید…'; input.required = true;
      input.setAttribute('aria-label', 'عبارت جستجو');
      const submit = document.createElement('button');
      submit.type = 'submit'; submit.textContent = 'جستجو';
      form.append(input, submit);
      openDialog(trigger, trigger.getAttribute('aria-label'), form);
    } else if (action === 'menu_panel') {
      const template = [...nav.querySelectorAll('template[data-sn-panel]')].find(node => node.dataset.snPanel === trigger.dataset.snPanel);
      if (template) openDialog(trigger, trigger.getAttribute('aria-label'), template.content.cloneNode(true));
      else announce('این منو در دسترس نیست');
    } else if (action === 'scroll_top') {
      window.scrollTo({top:0, behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
    } else if (action === 'share') {
      if (navigator.share) {
        try { await navigator.share({title:document.title,url:location.href}); } catch (_) { /* Visitor cancelled. */ }
      } else {
        const panel = document.createElement('div');
        panel.className = 'seenav-share';
        const input = document.createElement('input');
        input.type = 'url'; input.value = location.href; input.readOnly = true;
        input.setAttribute('aria-label', 'لینک صفحه');
        input.addEventListener('focus', () => input.select());
        const copy = document.createElement('button');
        copy.type = 'button'; copy.textContent = 'کپی لینک';
        copy.addEventListener('click', async () => {
          try { await navigator.clipboard.writeText(location.href); announce('لینک صفحه کپی شد'); dialog.close(); }
          catch (_) { input.focus(); input.select(); announce('لینک را به‌صورت دستی کپی کنید'); }
        });
        panel.append(input, copy);
        openDialog(trigger, 'اشتراک‌گذاری', panel);
      }
    } else if (action === 'trigger') {
      try {
        const target = document.querySelector(trigger.dataset.snTarget);
        if (target && !target.closest('.seenav')) target.click();
        else announce('عنصر موردنظر در این صفحه پیدا نشد');
      } catch (_) { announce('انتخابگر عنصر معتبر نیست'); }
    }
  });

  function announce(message) {
    let status = document.getElementById('seenav-status');
    if (!status) {
      status = document.createElement('div'); status.id = 'seenav-status'; status.className = 'seenav-sr-only';
      status.setAttribute('role', 'status'); document.body.appendChild(status);
    }
    status.textContent = message;
  }
})();
