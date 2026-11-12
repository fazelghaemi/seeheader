(function($){
  'use strict';
  const pack=window.SeeHeaderIconPack||{};
  const icons=Array.isArray(pack.icons)?pack.icons:[];
  let pickerIcons=icons;
  let iconLimit=48;
  const base=pack.baseUrl||'';
  const palettes=pack.palettes||{};
  let navIndex=$('.sh-nav-row').length,actionIndex=$('.sh-action-row').length;
  let pickerTarget=null,pickerTrigger=null,category='همه';
  const urlFor=path=>base+String(path).split('/').map(encodeURIComponent).join('/');
  const rowId=row=>$(row).find('[data-field="id"]').val();
  const parentOf=row=>$(row).find('[data-field="parent"]').val()||'';
  const rows=()=>$('.sh-nav-row').toArray();
  const byId=id=>rows().find(row=>rowId(row)===id);
  const escapeHtml=value=>String(value).replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));

  // Keep the flat form fields in tree order, while retaining the saved parent IDs.
  function treeOrder(){
    const current=rows(),ordered=[],seen=new Set();
    function visit(parent,depth){
      current.filter(row=>parentOf(row)===parent&&!seen.has(row)).forEach(row=>{
        seen.add(row);row.dataset.depth=String(depth);ordered.push(row);visit(rowId(row),Math.min(depth+1,8));
      });
    }
    visit('',0);
    current.filter(row=>!seen.has(row)).forEach(row=>{row.querySelector('[data-field="parent"]').value='';row.dataset.depth='0';ordered.push(row);});
    $('.sh-nav-items').append(ordered);
    if($.fn.sortable&&$('.sh-nav-items').sortable('instance'))$('.sh-nav-items').sortable('refresh');
    ordered.forEach((row,index)=>{
      $(row).find('[name]').each(function(){this.name=this.name.replace(/\[nav_items\]\[[^\]]+\]/,'[nav_items]['+index+']');});
      const depth=Number(row.dataset.depth)||0;
      row.style.setProperty('--sh-depth',depth);
      const count=current.filter(child=>parentOf(child)===rowId(row)).length;
      $(row).find('[data-child-count]').text(count?count+' زیرآیتم':depth?'زیرآیتم':'آیتم اصلی');
      $(row).toggleClass('is-child',depth>0);
    });
    updatePreview();
  }
  function isDescendant(id,ancestor){
    const visited=new Set();let cursor=id;
    while(cursor&&!visited.has(cursor)){if(cursor===ancestor)return true;visited.add(cursor);const row=byId(cursor);cursor=row?parentOf(row):'';}
    return false;
  }
  function refreshParents(desired){
    const current=rows(),values=new Map(current.map(row=>[rowId(row),parentOf(row)]));
    if(desired)values.set(desired.id,desired.parent);
    current.forEach(row=>{
      const id=rowId(row),select=$(row).find('[data-field="parent"]'),parent=values.get(id)||'';
      select.empty().append($('<option>',{value:'',text:'آیتم اصلی'}));
      current.forEach(candidate=>{const target=rowId(candidate);if(target!==id&&!isDescendant(target,id))select.append($('<option>',{value:target,text:$(candidate).find('[data-nav-label]').val()||'بدون عنوان'}));});
      select.val(parent&&select.find('option').filter(function(){return this.value===parent;}).length?parent:'');
    });
    treeOrder();
  }
  function addNavItem(parentId){
    const index=navIndex++,id='nav'+Date.now().toString(36)+Math.random().toString(36).slice(2,7);
    const html=$('#sh-nav-item-template').html().replace(/__INDEX__/g,String(index)).replace(/__ITEMID__/g,id);
    const row=$(html).appendTo('.sh-nav-items');
    syncOptionalColors(row);
    refreshParents({id,parent:parentId||''});
    row.find('[data-nav-label]').trigger('focus');
  }
  function removeNav(row){
    const id=rowId(row),descendants=rows().filter(candidate=>candidate!==row&&isDescendant(rowId(candidate),id));
    if(descendants.length&&!window.confirm('این آیتم و '+descendants.length+' زیرآیتم آن حذف شوند؟'))return;
    descendants.forEach(candidate=>candidate.remove());row.remove();refreshParents();
  }
  function changeDepth(row,direction){
    const all=rows(),position=all.indexOf(row),parent=parentOf(row);
    if(direction==='in'){
      const previous=all[position-1];
      if(!previous||isDescendant(rowId(previous),rowId(row)))return;
      refreshParents({id:rowId(row),parent:rowId(previous)});
    }else if(parent){
      const parentRow=byId(parent);refreshParents({id:rowId(row),parent:parentRow?parentOf(parentRow):''});
    }
  }
  $('.sh-nav-row').each(function(){
    $(this).find('>header').append('<span class="sh-node-meta" data-child-count></span><button type="button" class="sh-indent" title="زیرمجموعه آیتم قبلی" aria-label="تو بردن آیتم">↳</button><button type="button" class="sh-outdent" title="بازگشت به سطح بالاتر" aria-label="بیرون آوردن آیتم">↰</button>');
  });
  // The template is inert HTML; add the same controls before cloning it.
  const template=$('#sh-nav-item-template');
  template.html(template.html().replace('</header>','<span class="sh-node-meta" data-child-count></span><button type="button" class="sh-indent" title="زیرمجموعه آیتم قبلی" aria-label="تو بردن آیتم">↳</button><button type="button" class="sh-outdent" title="بازگشت به سطح بالاتر" aria-label="بیرون آوردن آیتم">↰</button></header>'));
  $('.sh-add-nav-item').on('click',()=>addNavItem(''));
  $(document).on('click','.sh-add-child',function(){addNavItem(rowId($(this).closest('.sh-nav-row')));});
  $(document).on('click','.sh-nav-remove',function(){removeNav($(this).closest('.sh-nav-row')[0]);});
  $(document).on('click','.sh-indent',function(){changeDepth($(this).closest('.sh-nav-row')[0],'in');});
  $(document).on('click','.sh-outdent',function(){changeDepth($(this).closest('.sh-nav-row')[0],'out');});
  $(document).on('input','[data-nav-label]',function(){$(this).closest('.sh-nav-row').find('[data-row-title]').text(this.value||'آیتم منو');refreshParents();});
  $(document).on('change','[data-field="parent"]',function(){const row=$(this).closest('.sh-nav-row')[0];if(isDescendant(this.value,rowId(row)))this.value='';refreshParents();});
  if($.fn.sortable)$('.sh-nav-items').sortable({handle:'.sh-drag',cancel:'input,textarea,select,option',axis:'y',placeholder:'sh-sort-placeholder',tolerance:'pointer',stop:()=>treeOrder()});
  refreshParents();

  $('.sh-action-row').each(function(){const match=this.outerHTML.match(/\[items\]\[(\d+)\]/);if(match)actionIndex=Math.max(actionIndex,Number(match[1])+1);});
  $('.sh-add-action').on('click',function(){$('.sh-action-list').append($('#sh-action-template').html().replace(/__INDEX__/g,String(actionIndex++)));});
  $(document).on('click','.sh-remove',function(){$(this).closest('.sh-action-row').remove();});
  $('#sh-menu-source').on('change',function(){const builder=this.value==='builder';$('.sh-builder-source').toggle(builder);$('.sh-wp-source-field').toggle(!builder);updatePreview();}).trigger('change');
  $(document).on('change','input[name="seeheader_options[theme]"]',function(){$('.sh-theme-card').removeClass('is-selected');$(this).closest('.sh-theme-card').addClass('is-selected');});
  $(document).on('input','input[type=range]',function(){$(this).siblings('output').text(this.value+'px');});
  $(document).on('input change','#sh-settings input,#sh-settings select',updatePreview);
  $(document).on('click','[data-sh-device]',function(){$('[data-sh-device]').removeClass('is-current');$(this).addClass('is-current');$('[data-sh-preview]').toggleClass('is-mobile',$(this).data('sh-device')==='mobile');});
  $(document).on('click','[data-sh-media]',function(){if(!window.wp||!wp.media)return;const target=$('#'+$(this).data('sh-media'));const frame=wp.media({title:'انتخاب تصویر',button:{text:'استفاده از تصویر'},multiple:false,library:{type:'image'}});frame.on('select',()=>target.val(frame.state().get('selection').first().toJSON().url).trigger('input'));frame.open();});
  $(document).on('input','.sh-menu-image-field input[type=url]',function(){$(this).closest('.sh-menu-image-field').find('.sh-image-preview').attr('src',this.value).prop('hidden',!this.value);});
  $('.sh-section-nav a').on('click',function(event){const target=document.querySelector(this.getAttribute('href'));if(!target)return;event.preventDefault();target.scrollIntoView({behavior:'smooth',block:'start'});history.replaceState(null,'',this.getAttribute('href'));});

  // Replace the unwieldy account icon select with the same visual picker as menu items.
  const accountSelect=$('[name="seeheader_options[account_icon]"]');
  if(accountSelect.length){
    const selected=accountSelect.val();
    const control=$('<span class="sh-account-icon-control" data-sh-icon-control><button type="button" class="sh-icon-select" aria-label="انتخاب آیکن حساب"><img data-icon-preview alt=""><b>انتخاب آیکن</b></button></span>');
    accountSelect.after(control).addClass('sh-visually-hidden-select');
    control.find('img').attr('src',selected?urlFor(selected):'').prop('hidden',!selected);
    control.find('b').text(selected?selected.split('/').pop():'انتخاب آیکن');
    control.data('iconSelect',accountSelect);
  }
  $(document).on('click','.sh-icon-select',function(){pickerTarget=$(this).closest('[data-sh-icon-control]');pickerTrigger=this;openPicker();});
  $(document).on('click','.sh-icon-close',closePicker);
  $(document).on('click','.sh-icon-picker',function(event){if(event.target===this)closePicker();});
  $(document).on('keydown',event=>{if(event.key==='Escape')closePicker();});
  $(document).on('input','.sh-icon-search',function(){iconLimit=48;renderIcons();});
  $(document).on('click','.sh-icon-category',function(){category=$(this).data('category');iconLimit=48;$('.sh-icon-category').removeClass('is-active');$(this).addClass('is-active');renderIcons();});
  $(document).on('click','.sh-icon-more',function(){const previous=iconLimit;iconLimit+=48;renderIcons();$('.sh-icon-choice:not(.sh-icon-clear)').eq(previous).trigger('focus');});
  $(document).on('click','.sh-icon-choice',function(){
    if(!pickerTarget)return;
    const path=$(this).data('path')||'',input=pickerTarget.data('iconSelect')||pickerTarget.find('[data-field="icon"]');
    input.val(path).trigger('change');pickerTarget.find('[data-icon-preview]').attr('src',path?urlFor(path):'').prop('hidden',!path);
    pickerTarget.find('.sh-icon-select b').text(path?path.split('/').pop():'انتخاب آیکن');
    closePicker();
  });
  function openPicker(){
    pickerIcons=pickerTarget&&pickerTarget.is('[data-sh-arrow-control]')?icons.filter(icon=>['Arrows','Arrows Action'].includes(icon.category)):icons;
    iconLimit=48;
    category='همه';$('.sh-icon-search').val('');
    const categories=['همه',...new Set(pickerIcons.map(icon=>icon.category))];
    $('.sh-icon-categories').html(categories.map(name=>'<button type="button" class="sh-icon-category'+(name==='همه'?' is-active':'')+'" data-category="'+escapeHtml(name)+'">'+escapeHtml(name)+'</button>').join(''));
    $('.sh-icon-picker').prop('hidden',false).attr('aria-hidden','false');$('body').addClass('sh-picker-open');renderIcons();$('.sh-icon-search').trigger('focus');
  }
  function closePicker(){if(!$('.sh-icon-picker:visible').length)return;$('.sh-icon-picker').prop('hidden',true).attr('aria-hidden','true');$('body').removeClass('sh-picker-open');pickerTarget=null;if(pickerTrigger&&document.contains(pickerTrigger))pickerTrigger.focus();pickerTrigger=null;}
  function renderIcons(){
    const query=$('.sh-icon-search').val().trim().toLowerCase();
    const found=pickerIcons.filter(icon=>(category==='همه'||icon.category===category)&&(!query||(icon.name+' '+icon.category+' '+icon.path).toLowerCase().includes(query)));
    const visible=found.slice(0,iconLimit);
    const clear=pickerTarget&&pickerTarget.is('[data-sh-arrow-control]')?'':'<button type="button" class="sh-icon-choice sh-icon-clear" data-path="" title="بدون آیکن"><span aria-hidden="true">×</span><small>بدون آیکن</small></button>';
    $('.sh-icon-grid').html(clear+visible.map(icon=>'<button type="button" class="sh-icon-choice" data-path="'+escapeHtml(icon.path)+'" title="'+escapeHtml(icon.category+' / '+icon.name)+'" aria-label="'+escapeHtml(icon.category+' / '+icon.name)+'"><img loading="lazy" src="'+escapeHtml(urlFor(icon.path))+'" alt=""><small>'+escapeHtml(icon.name)+'</small></button>').join('')+(found.length>iconLimit?'<button type="button" class="sh-icon-more">نمایش بیشتر</button>':''));
    $('.sh-icon-empty').remove();if(!found.length)$('.sh-icon-grid').append('<p class="sh-icon-empty">آیکنی پیدا نشد.</p>');
    $('.sh-icon-result-count').text(Math.min(iconLimit,found.length)+' از '+found.length+' آیکن');
  }
  $('.sh-icon-search').before('<span class="sh-icon-result-count" aria-live="polite"></span>');

  function syncOptionalColors(scope){
    $(scope).find('[data-color-optional]').each(function(){
      const color=$(this),auto=color.closest('label').find('input[type=checkbox]').first();
      function sync(){color.prop('disabled',auto.is(':checked')).toggleClass('is-disabled',auto.is(':checked'));updatePreview();}
      auto.off('change.shColor').on('change.shColor',sync);sync();
    });
  }
  syncOptionalColors(document);
  function updatePreview(){
    const root=$('[data-sh-preview]');if(!root.length)return;
    const val=name=>$('#sh-settings [name="seeheader_options['+name+']"]');
    const theme=val('theme').filter(':checked').val()||'classic',custom=val('palette_mode').val()==='custom';
    root.attr('data-theme',theme);$('.sh-colors').first().toggleClass('is-inactive',!custom);
    const colors=custom?{accent:val('accent').val(),surface:val('surface').val(),text:val('text').val(),border:val('border').val()}:(palettes[theme]||palettes.classic||{});
    Object.keys(colors).forEach(key=>root[0].style.setProperty('--preview-'+key,colors[key]));
    root[0].style.setProperty('--preview-width',(val('width').val()||1240)+'px');
    root[0].style.setProperty('--preview-logo-height',(val('logo_height').val()||52)+'px');
    const logo=val('logo').val();root.find('[data-sh-preview-logo]').empty().append(logo?$('<img>',{src:logo,alt:''}):document.createTextNode('لوگوی سایت'));
    root.toggleClass('has-topbar',val('topbar').is(':checked'));
    root.find('[data-sh-preview-account]').toggle(val('account').is(':checked'));
    root.find('[data-sh-preview-search]').toggle(val('search').is(':checked'));
    let labels=[];
    if(val('menu_source').val()==='builder')labels=rows().filter(row=>!parentOf(row)).map(row=>$(row).find('[data-nav-label]').val().trim()).filter(Boolean).slice(0,5);
    else{const name=val('menu').find('option:selected').text();if(name)labels=[name];}
    root.find('[data-sh-preview-menu]').empty().append((labels.length?labels:['صفحه اصلی']).map(label=>$('<span>').text(label)));
  }
  updatePreview();
})(jQuery);
