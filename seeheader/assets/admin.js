(function($){
  'use strict';
  let actionIndex=0,navIndex=0,pickerTarget=null,pickerTrigger=null,category='همه';
  const icons=(window.SeeHeaderIconPack&&SeeHeaderIconPack.icons)||[];
  const base=(window.SeeHeaderIconPack&&SeeHeaderIconPack.baseUrl)||'';
  const palettes=(window.SeeHeaderIconPack&&SeeHeaderIconPack.palettes)||{};
  const urlFor=path=>base+String(path).split('/').map(encodeURIComponent).join('/');
  $('.sh-action-row').each(function(){const m=this.outerHTML.match(/items\]\[(\d+)\]/);if(m)actionIndex=Math.max(actionIndex,parseInt(m[1],10)+1);});
  navIndex=$('.sh-nav-row').length;
  $('.sh-add-action').on('click',function(){const html=$('#sh-action-template').html().replace(/__INDEX__/g,String(actionIndex++));$('.sh-action-list').append(html);});
  $(document).on('click','.sh-remove',function(){$(this).closest('.sh-action-row').remove();});
  $('.sh-add-nav-item').on('click',function(){addNavItem();});
  $(document).on('click','.sh-add-child',function(){const row=$(this).closest('.sh-nav-row'),id=row.find('[data-field="id"]').val();row.find('.sh-nav-more').prop('open',true);addNavItem(id);});
  $(document).on('click','.sh-nav-remove',function(){const row=$(this).closest('.sh-nav-row');const id=row.find('[data-field="id"]').val();row.remove();$('.sh-nav-row').each(function(){const select=$(this).find('[data-field="parent"]');if(select.val()===id)select.val('');});refreshParents();renumberNav();updatePreview();});
  $(document).on('input','[data-nav-label]',function(){const row=$(this).closest('.sh-nav-row');row.find('[data-row-title]').text(this.value||'آیتم منو');refreshParents();});
  $(document).on('change','[data-field="parent"]',function(){const row=$(this).closest('.sh-nav-row'),id=row.find('[data-field="id"]').val(),parent=this.value;if(wouldCycle(id,parent)){this.value='';window.alert('این انتخاب باعث ایجاد چرخه در منو می‌شود.');}});
  $(document).on('change','input[name="seeheader_options[theme]"]',function(){$('.sh-theme-card').removeClass('is-selected');$(this).closest('.sh-theme-card').addClass('is-selected');updatePreview();});
  $(document).on('input','input[type=range]',function(){$(this).siblings('output').text(this.value+'px');updatePreview();});
  $(document).on('input change','#sh-settings input,#sh-settings select',updatePreview);
  $(document).on('click','[data-sh-device]',function(){$('[data-sh-device]').removeClass('is-current');$(this).addClass('is-current');$('[data-sh-preview]').toggleClass('is-mobile',$(this).data('sh-device')==='mobile');});
  $(document).on('click','[data-sh-media]',function(){if(!window.wp||!wp.media)return;const target=$('#'+$(this).data('sh-media'));const frame=wp.media({title:'انتخاب تصویر',button:{text:'استفاده از تصویر'},multiple:false,library:{type:'image'}});frame.on('select',function(){target.val(frame.state().get('selection').first().toJSON().url).trigger('input').trigger('change');});frame.open();});
  $(document).on('click','.sh-icon-select',function(){pickerTarget=$(this).closest('[data-sh-icon-control]');pickerTrigger=this;openPicker();});
  $(document).on('click','.sh-icon-close',closePicker);
  $(document).on('click','.sh-icon-picker',function(e){if(e.target===this)closePicker();});
  $(document).on('keydown',function(e){if(e.key==='Escape')closePicker();});
  $(document).on('input','.sh-icon-search',renderIcons);
  $(document).on('click','.sh-icon-category',function(){category=$(this).data('category');$('.sh-icon-category').removeClass('is-active');$(this).addClass('is-active');renderIcons();});
  $(document).on('click','.sh-icon-choice',function(){if(!pickerTarget)return;const path=$(this).data('path')||'',input=pickerTarget.find('[data-field="icon"]'),preview=pickerTarget.find('[data-icon-preview]');input.val(path).trigger('change');preview.attr('src',path?urlFor(path):'').prop('hidden',!path);pickerTarget.find('.sh-icon-select b').text(path?path.split('/').pop():'انتخاب از پک');closePicker();});
  if($.fn.sortable&&$('.sh-nav-items').length){$('.sh-nav-items').sortable({handle:'.sh-drag',axis:'y',update:function(){renumberNav();refreshParents();}});}
  $('.sh-section-nav a').on('click',function(e){const selector=this.getAttribute('href'),target=selector&&document.querySelector(selector);if(target){e.preventDefault();$('html,body').stop(true).animate({scrollTop:$(target).offset().top-100},240);history.replaceState(null,'',selector);}});
  if('IntersectionObserver' in window){const sectionObserver=new IntersectionObserver(entries=>{entries.forEach(entry=>{if(entry.isIntersecting){const link=document.querySelector('.sh-section-nav a[href="#'+entry.target.id+'"]');if(link){$('.sh-section-nav a').removeClass('is-current').removeAttr('aria-current');$(link).addClass('is-current').attr('aria-current','location');}}});},{rootMargin:'-110px 0px -65% 0px'});$('.sh-section-nav a[href^="#"]').each(function(){const target=document.querySelector(this.getAttribute('href'));if(target)sectionObserver.observe(target);});}
  $('#sh-menu-source').on('change',toggleSource);toggleSource();
  if(new URLSearchParams(window.location.search).get('settings-updated')==='true'){$('.sh-save-status').addClass('is-saved').each(function(){const textNode=$(this).contents().filter(function(){return this.nodeType===3&&this.nodeValue.trim();}).last()[0];if(textNode)textNode.nodeValue=' ذخیره شد';});}
  function addNavItem(parentId){const index=navIndex++;const html=$('#sh-nav-item-template').html().replace(/__INDEX__/g,String(index)).replace(/__ITEMID__/g,'nav'+Date.now().toString(36)+Math.random().toString(36).slice(2,6));const row=$(html).appendTo('.sh-nav-items');row.find('[data-field="parent"]').val(parentId||'');renumberNav();refreshParents();updatePreview();row.find('[data-nav-label]').trigger('focus');}
  function renumberNav(){$('.sh-nav-row').each(function(i){$(this).find('[name]').each(function(){this.name=this.name.replace(/\[nav_items\]\[\d+\]/,'[nav_items]['+i+']');});});}
  function refreshParents(){const rows=$('.sh-nav-row').toArray();rows.forEach(row=>{const $row=$(row),select=$row.find('[data-field="parent"]'),current=select.val(),id=$row.find('[data-field="id"]').val();select.empty().append($('<option>',{value:'',text:'آیتم سطح اول'}));rows.forEach(parent=>{const $p=$(parent),pid=$p.find('[data-field="id"]').val();if(pid&&pid!==id&&!wouldCycle(id,pid))select.append($('<option>',{value:pid,text:$p.find('[data-nav-label]').val()||'آیتم منو'}));});if(select.find('option[value="'+current+'"]').length)select.val(current);});}
  function wouldCycle(id,parent){let cursor=parent,steps=0;while(cursor&&steps++<50){if(cursor===id)return true;const row=$('.sh-nav-row').filter(function(){return $(this).find('[data-field="id"]').val()===cursor;}).first();if(!row.length)return false;cursor=row.find('[data-field="parent"]').val();}return steps>=50;}
  function toggleSource(){const builder=$('#sh-menu-source').val()==='builder';$('.sh-builder-source').toggle(builder);$('.sh-wp-source-field').toggle(!builder);}
  function openPicker(){if(!pickerTarget)return;category='همه';$('.sh-icon-search').val('');const categories=['همه'].concat([...new Set(icons.map(x=>x.category))]);$('.sh-icon-categories').html(categories.map(x=>'<button type="button" class="sh-icon-category'+(x==='همه'?' is-active':'')+'" data-category="'+escapeHtml(x)+'">'+escapeHtml(x)+'</button>').join(''));$('.sh-icon-picker').prop('hidden',false).attr('aria-hidden','false');$('body').addClass('sh-picker-open');renderIcons();$('.sh-icon-search').trigger('focus');}
  function closePicker(){if(!$('.sh-icon-picker').length)return;$('.sh-icon-picker').prop('hidden',true).attr('aria-hidden','true');$('body').removeClass('sh-picker-open');pickerTarget=null;if(pickerTrigger&&document.contains(pickerTrigger))pickerTrigger.focus();pickerTrigger=null;}
  function renderIcons(){const query=$('.sh-icon-search').val().toLowerCase();const found=icons.filter(x=>(category==='همه'||x.category===category)&&(!query||(x.name+' '+x.category+' '+x.path).toLowerCase().includes(query)));const clear='<button type="button" class="sh-icon-choice sh-icon-clear" data-path="">بدون آیکن</button>';$('.sh-icon-grid').html(clear+(found.map(x=>'<button type="button" class="sh-icon-choice" data-path="'+escapeHtml(x.path)+'" title="'+escapeHtml(x.category+' / '+x.name)+'"><img src="'+escapeHtml(urlFor(x.path))+'" alt=""><span>'+escapeHtml(x.name)+'</span></button>').join('')||'<p class="sh-icon-empty">آیکنی با این عبارت پیدا نشد.</p>'));}
  function escapeHtml(text){return String(text).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function updatePreview(){
    const root=$('[data-sh-preview]');if(!root.length)return;
    const val=name=>$('#sh-settings [name="seeheader_options['+name+']"]');
    const theme=val('theme').filter(':checked').val()||'classic';root.attr('data-theme',theme);
    const custom=val('palette_mode').val()==='custom';$('.sh-colors').toggleClass('is-inactive',!custom);
    const colors=custom?{accent:val('accent').val(),surface:val('surface').val(),text:val('text').val(),border:val('border').val()}:(palettes[theme]||palettes.classic||{});
    Object.keys(colors).forEach(k=>root[0].style.setProperty('--preview-'+k,colors[k]));
    root[0].style.setProperty('--preview-width',(val('width').val()||1240)+'px');
    root[0].style.setProperty('--preview-logo-height',(val('logo_height').val()||52)+'px');
    const url=val('logo').val();root.find('[data-sh-preview-logo]').html(url?'<img src="'+String(url).replace(/["<>]/g,'')+'" alt="">':'لوگوی سایت');
    root.toggleClass('has-topbar',val('topbar').is(':checked'));
    root.find('[data-sh-preview-account]').toggle(val('account').is(':checked'));
    root.find('[data-sh-preview-search]').toggle(val('search').is(':checked'));
    let labels=[];
    if(val('menu_source').val()==='builder')labels=$('.sh-nav-row').filter(function(){return !$(this).find('[data-field="parent"]').val();}).map(function(){return $(this).find('[data-nav-label]').val().trim();}).get().filter(Boolean).slice(0,4);
    else {const name=val('menu').find('option:selected').text();if(name)labels=[name];}
    if(!labels.length)labels=['صفحه اصلی'];
    root.find('[data-sh-preview-menu]').empty().append(labels.map(label=>$('<span>').text(label)));
  }
  updatePreview();
})(jQuery);
