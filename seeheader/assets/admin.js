(function($){
  'use strict';
  let next=0;
  $('.sh-action-row').each(function(){const m=this.outerHTML.match(/items\]\[(\d+)\]/);if(m)next=Math.max(next,parseInt(m[1],10)+1);});
  $('.sh-add-action').on('click',function(){const html=$('#sh-action-template').html().replace(/__INDEX__/g,String(next++));$('.sh-action-list').append(html);});
  $(document).on('click','.sh-remove',function(){$(this).closest('.sh-action-row').remove();});
  $(document).on('change','input[name="seeheader_options[theme]"]',function(){$('.sh-theme-card').removeClass('is-selected');$(this).closest('.sh-theme-card').addClass('is-selected');updatePreview();});
  $(document).on('input','input[type=range]',function(){$(this).siblings('output').text(this.value+'px');updatePreview();});
  $(document).on('input change','#sh-settings input,#sh-settings select',updatePreview);
  $(document).on('click','[data-sh-device]',function(){$('[data-sh-device]').removeClass('is-current');$(this).addClass('is-current');$('[data-sh-preview]').toggleClass('is-mobile',$(this).data('sh-device')==='mobile');});
  $(document).on('click','[data-sh-media]',function(){const target=$('#'+$(this).data('sh-media'));const frame=wp.media({title:'انتخاب لوگوی هدر',button:{text:'استفاده از تصویر'},multiple:false,library:{type:'image'}});frame.on('select',function(){target.val(frame.state().get('selection').first().toJSON().url).trigger('input').trigger('change');});frame.open();});
  $('.sh-section-nav a').on('click',function(e){const target=$($(this).attr('href'));if(target.length){e.preventDefault();$('html,body').animate({scrollTop:target.offset().top-100},240);}});
  if(new URLSearchParams(window.location.search).get('settings-updated')==='true'){$('.sh-save-status').addClass('is-saved').contents().last()[0].textContent='ذخیره شد';}
  function updatePreview(){
    const root=$('[data-sh-preview]');if(!root.length)return;
    const val=name=>$('#sh-settings [name="seeheader_options['+name+']"]');
    const theme=val('theme').filter(':checked').val()||'classic';root.attr('data-theme',theme);
    const colors={accent:val('accent').val(),surface:val('surface').val(),text:val('text').val(),border:val('border').val()};Object.keys(colors).forEach(k=>root[0].style.setProperty('--preview-'+k,colors[k]));
    root[0].style.setProperty('--preview-width',(val('width').val()||1240)+'px');root[0].style.setProperty('--preview-logo-height',(val('logo_height').val()||52)+'px');
    const url=val('logo').val();const logo=$('[data-sh-preview-logo]');logo.html(url?'<img src="'+String(url).replace(/["<>]/g,'')+'" alt="">':'لوگوی سایت');
    root.toggleClass('has-topbar',val('topbar').is(':checked'));
  }
  updatePreview();
})(jQuery);
