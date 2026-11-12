<?php

/**
 * Plugin Name: SeeHeader — هدر ساز
 * Description: هدر ساز واکنش‌گرا با طرح‌های متنوع، منوهای وردپرس و ویجت Elementor.
 * Version: 1.0.0
 * Author: ReadyStudio
 * Text Domain: seeheader
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('SEEHEADER_VERSION', '1.0.0');
define('SEEHEADER_DIR', plugin_dir_path(__FILE__));
define('SEEHEADER_URL', plugin_dir_url(__FILE__));

function seeheader_defaults()
{
  return ['enabled' => 0, 'theme' => 'classic', 'logo' => '', 'menu' => 0, 'menu_icons' => [], 'search' => 1, 'account' => 1, 'account_url' => '', 'account_label' => 'ورود', 'sticky' => 0, 'items' => [], 'accent' => '#e5442d', 'surface' => '#ffffff', 'text' => '#201d1b', 'border' => '#dedbd8', 'width' => 1240, 'logo_height' => 52, 'desktop' => 1, 'mobile' => 1, 'shadow' => 0, 'topbar' => 0, 'topbar_text' => '', 'topbar_url' => '', 'mobile_breakpoint' => 782];
}
function seeheader_options()
{
  return wp_parse_args(get_option('seeheader_options', []), seeheader_defaults());
}
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('seeheader', SEEHEADER_URL . 'assets/header.css', [], SEEHEADER_VERSION);
  $o = seeheader_options();
  $bp = min(1024, max(640, absint($o['mobile_breakpoint'] ?? 782)));
  wp_add_inline_style('seeheader', '@media(max-width:' . $bp . 'px){.sh-header .sh-toggle{display:flex}.sh-header .sh-nav{display:none}.sh-header.sh-open .sh-nav{display:block}.sh-header .sh-search{display:none}.sh-header.sh-hide-mobile{display:none!important}}@media(min-width:' . ($bp + 1) . 'px){.sh-header .sh-toggle{display:none}.sh-header .sh-nav{display:block}.sh-header.sh-hide-desktop{display:none!important}}');
  wp_enqueue_script('seeheader', SEEHEADER_URL . 'assets/header.js', [], SEEHEADER_VERSION, true);
});
add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'toplevel_page_seeheader') {
    wp_enqueue_media();
    wp_enqueue_style('seeheader-admin', SEEHEADER_URL . 'assets/admin.css', [], SEEHEADER_VERSION);
    wp_enqueue_script('seeheader-admin', SEEHEADER_URL . 'assets/admin.js', ['jquery'], SEEHEADER_VERSION, true);
  }
});

add_action('admin_menu', function () {
  add_menu_page('سازنده هدر', 'هدر ساز', 'manage_options', 'seeheader', 'seeheader_settings_page', 'dashicons-layout', 58);
});
add_action('admin_init', function () {
  register_setting('seeheader_group', 'seeheader_options', ['sanitize_callback' => 'seeheader_sanitize']);
});
function seeheader_sanitize($in)
{
  $out = seeheader_defaults();
  $in = is_array($in) ? $in : [];
  $out['enabled'] = empty($in['enabled']) ? 0 : 1;
  $out['theme'] = in_array(($in['theme'] ?? ''), ['classic', 'centered', 'split', 'glass', 'dark', 'minimal', 'editorial', 'commerce', 'floating'], true) ? $in['theme'] : 'classic';
  $out['logo'] = esc_url_raw($in['logo'] ?? '');
  $out['menu'] = absint($in['menu'] ?? 0);
  $out['search'] = empty($in['search']) ? 0 : 1;
  $out['account'] = empty($in['account']) ? 0 : 1;
  $out['account_url'] = esc_url_raw($in['account_url'] ?? '');
  $out['account_label'] = sanitize_text_field($in['account_label'] ?? 'ورود');
  $out['sticky'] = empty($in['sticky']) ? 0 : 1;
  $out['topbar'] = empty($in['topbar']) ? 0 : 1;
  $out['topbar_text'] = sanitize_text_field($in['topbar_text'] ?? '');
  $out['topbar_url'] = esc_url_raw($in['topbar_url'] ?? '');
  $out['mobile_breakpoint'] = min(1024, max(640, absint($in['mobile_breakpoint'] ?? 782)));
  $allowed_icons=['Users/user.svg','Users/user-plus.svg','Shopping/cart.svg','Shopping/bag.svg','Call/phone.svg','Essentional/bookmark.svg','Essentional/hamburger-menu.svg','Notifications/bell.svg','Essentional/help.svg','Essentional/chat.svg'];
  $out['menu_icons']=[]; if(!empty($in['menu_icons'])&&is_array($in['menu_icons']))foreach($in['menu_icons'] as $menu_item_id=>$icon){$menu_item_id=absint($menu_item_id);if($menu_item_id&&in_array($icon,$allowed_icons,true))$out['menu_icons'][$menu_item_id]=$icon;}
  foreach (['accent', 'surface', 'text', 'border'] as $color) {
    $value = sanitize_hex_color($in[$color] ?? '');
    $out[$color] = $value ?: seeheader_defaults()[$color];
  }
  $out['width'] = min(1920, max(760, absint($in['width'] ?? 1240)));
  $out['logo_height'] = min(100, max(24, absint($in['logo_height'] ?? 52)));
  foreach (['desktop', 'mobile', 'shadow'] as $flag) $out[$flag] = empty($in[$flag]) ? 0 : 1;
  $out['items'] = [];
  if (!empty($in['items']) && is_array($in['items'])) foreach (array_slice($in['items'], 0, 8) as $item) {
    $label = sanitize_text_field($item['label'] ?? '');
    $url = esc_url_raw($item['url'] ?? '');
    $icon = sanitize_text_field($item['icon'] ?? '');
    if ($label && $url) $out['items'][] = ['label' => $label, 'url' => $url, 'icon' => $icon];
  }
  return $out;
}
function seeheader_settings_page()
{
  if (!current_user_can('manage_options')) return;
  $o = seeheader_options();
  $menus = wp_get_nav_menus();
  $themes = ['classic' => 'ReadyStudio', 'centered' => 'لوگو در مرکز', 'split' => 'دو بخش نامتقارن', 'glass' => 'شیشه‌ای', 'dark' => 'تیره', 'minimal' => 'مینیمال', 'editorial' => 'مجله‌ای', 'commerce' => 'فروشگاهی', 'floating' => 'شناور'];
  $icons = ['Users/user.svg' => 'کاربر', 'Users/user-plus.svg' => 'عضویت', 'Shopping/cart.svg' => 'سبد خرید', 'Shopping/bag.svg' => 'کیف', 'Call/phone.svg' => 'تماس', 'Essentional/bookmark.svg' => 'نشانک', 'Essentional/hamburger-menu.svg' => 'منو', 'Notifications/bell.svg' => 'اعلان', 'Essentional/help.svg' => 'راهنما', 'Essentional/chat.svg' => 'گفتگو'];
  $menu_items = $o['menu'] ? wp_get_nav_menu_items($o['menu']) : [];
?> <div class="wrap seeheader-admin" dir="rtl">
    <header class="sh-admin-head">
      <div><span class="sh-kicker">READY STUDIO · HEADER BUILDER</span>
        <h1>سازنده هدر</h1>
        <p>چیدمان، پوسته و رفتار هدر را برای دسکتاپ و موبایل تنظیم کنید.</p>
      </div>
      <div class="sh-head-actions"><span class="sh-save-status"><i></i><?php echo $o['enabled'] ? 'در سایت فعال' : 'پیش‌نویس'; ?></span><label class="sh-publish"><input type="checkbox" name="seeheader_options[enabled]" value="1" form="sh-settings" <?php checked($o['enabled']); ?>> <span>انتشار هدر</span></label></div>
    </header>
    <form id="sh-settings" method="post" action="options.php"><?php settings_fields('seeheader_group'); ?><nav class="sh-section-nav" aria-label="بخش‌های تنظیمات"><a href="#sh-themes">پوسته</a><a href="#sh-layout">ساختار</a><a href="#sh-actions">پیوندها</a><a href="#sh-topbar">نوار اعلان</a><a href="#sh-responsive">واکنش‌گرایی</a></nav>
      <div class="sh-builder">
        <main class="sh-workspace">
          <section class="sh-section sh-themes" id="sh-themes">
            <div class="sh-section-title">
              <div><small>01 / ظاهر</small>
                <h2>یک زبان بصری انتخاب کنید</h2>
              </div><span>۹ پوسته</span>
            </div>
            <div class="sh-theme-grid"><?php foreach ($themes as $key => $title): ?><label class="sh-theme-card <?php echo $o['theme'] === $key ? 'is-selected' : ''; ?>"><input type="radio" name="seeheader_options[theme]" value="<?php echo esc_attr($key); ?>" <?php checked($o['theme'], $key); ?>><span class="sh-mini sh-mini-<?php echo esc_attr($key); ?>"><i></i><i></i><i></i></span><b><?php echo esc_html($title); ?></b></label><?php endforeach; ?></div>
          </section>
          <section class="sh-section" id="sh-layout">
            <div class="sh-section-title">
              <div><small>02 / ساختار</small>
                <h2>محتوای هدر</h2>
              </div><span class="sh-section-note">لوگو، منو و ابزارها</span>
            </div>
            <div class="sh-field-grid"><label>لوگو <span class="sh-media-field"><input type="url" id="sh-logo" name="seeheader_options[logo]" value="<?php echo esc_attr($o['logo']); ?>" placeholder="لوگوی پوسته یا لوگوی پیش‌فرض"><button type="button" class="button" data-sh-media="sh-logo">انتخاب تصویر</button></span></label><label>منوی اصلی<select name="seeheader_options[menu]">
                  <option value="0">انتخاب منوی وردپرس</option><?php foreach ($menus as $m) printf('<option value="%d" %s>%s</option>', $m->term_id, selected($o['menu'], $m->term_id, false), esc_html($m->name)); ?>
                </select></label><label>پیوند حساب کاربری<input type="url" name="seeheader_options[account_url]" value="<?php echo esc_attr($o['account_url']); ?>" placeholder="خالی = ورود / پیشخوان"></label><label>متن دکمه حساب<input type="text" name="seeheader_options[account_label]" value="<?php echo esc_attr($o['account_label']); ?>" placeholder="ورود"></label><label>عرض محتوای هدر <span class="sh-range"><input type="range" min="760" max="1920" step="20" name="seeheader_options[width]" value="<?php echo esc_attr($o['width']); ?>"><output><?php echo esc_html($o['width']); ?>px</output></span></label></div>
            <div class="sh-menu-icons"><details><summary>تنظیم آیکن برای آیتم‌های منو</summary><?php if($menu_items): ?><div class="sh-menu-icon-grid"><?php foreach($menu_items as $menu_item): ?><label><?php echo esc_html($menu_item->title); ?><select name="seeheader_options[menu_icons][<?php echo absint($menu_item->ID); ?>]"><option value="">بدون آیکن</option><?php foreach($icons as $path=>$icon_label)printf('<option value="%s" %s>%s</option>',esc_attr($path),selected($o['menu_icons'][$menu_item->ID]??'',$path,false),esc_html($icon_label)); ?></select></label><?php endforeach; ?></div><?php else: ?><p>ابتدا یک منوی اصلی انتخاب و تنظیمات را ذخیره کنید تا آیتم‌های منو برای انتخاب آیکن نمایش داده شوند.</p><?php endif; ?></details></div>
            <div class="sh-switches"><label><input type="checkbox" name="seeheader_options[search]" value="1" <?php checked($o['search']); ?>><span><b>جست‌وجوی سایت</b><small>فرم جست‌وجوی اصلی وردپرس</small></span></label><label><input type="checkbox" name="seeheader_options[account]" value="1" <?php checked($o['account']); ?>><span><b>حساب کاربری</b><small>ورود و پیوند حساب کاربر</small></span></label><label><input type="checkbox" name="seeheader_options[sticky]" value="1" <?php checked($o['sticky']); ?>><span><b>هدر چسبان</b><small>هنگام پیمایش بالای صفحه بماند</small></span></label><label><input type="checkbox" name="seeheader_options[shadow]" value="1" <?php checked($o['shadow']); ?>><span><b>سایه‌ی ظریف</b><small>جداسازی هدر از محتوای صفحه</small></span></label></div>
          </section>
          <section class="sh-section" id="sh-actions">
            <div class="sh-section-title">
              <div><small>03 / اکشن‌ها</small>
                <h2>پیوندها و دکمه‌ها</h2>
                <p>برای تماس، عضویت، فروشگاه یا هر مقصد دلخواه.</p>
              </div><button type="button" class="button sh-add-action">+ افزودن پیوند</button>
            </div>
            <div class="sh-action-list"><?php foreach ($o['items'] as $i => $item) seeheader_admin_action_row($i, $item, $icons); ?></div><template id="sh-action-template"><?php seeheader_admin_action_row('__INDEX__', [], $icons); ?></template>
          </section>
          <section class="sh-section" id="sh-topbar">
            <div class="sh-section-title">
              <div><small>04 / نوار اطلاع‌رسانی</small>
                <h2>پیام بالای هدر</h2>
                <p>اعلان کوتاه، زمان ارسال یا پیوند کمپین.</p>
              </div><label class="sh-inline-check"><input type="checkbox" name="seeheader_options[topbar]" value="1" <?php checked($o['topbar']); ?>> فعال</label>
            </div>
            <div class="sh-field-grid"><label>متن اعلان<input type="text" name="seeheader_options[topbar_text]" value="<?php echo esc_attr($o['topbar_text']); ?>" placeholder="ارسال رایگان برای سفارش‌های بالای ..."></label><label>پیوند اعلان<input type="url" name="seeheader_options[topbar_url]" value="<?php echo esc_attr($o['topbar_url']); ?>" placeholder="اختیاری"></label></div>
          </section>
        </main>
        <aside class="sh-sidebar">
          <section class="sh-side-card sh-live-card">
            <div class="sh-live-title">
              <div><small>پیش‌نمایش</small>
                <h2>نمای هدر</h2>
              </div>
              <div class="sh-device-switch"><button type="button" class="is-current" data-sh-device="desktop" aria-label="نمای دسکتاپ">▰</button><button type="button" data-sh-device="mobile" aria-label="نمای موبایل">▯</button></div>
            </div>
            <div class="sh-live-frame" data-sh-preview>
              <div class="sh-live-head"><span data-sh-preview-logo><?php echo $o['logo'] ? '<img src="' . esc_url($o['logo']) . '" alt="">' : 'لوگوی سایت'; ?></span><i></i><i></i></div>
              <div class="sh-live-nav"><i></i><i></i><i></i><i></i></div>
            </div>
            <p class="sh-preview-caption">تغییرات ظاهری را پیش از ذخیره ببینید.</p>
          </section>
          <section class="sh-side-card"><small>05 / هویت بصری</small>
            <h2>رنگ‌ها</h2>
            <div class="sh-colors"><?php foreach (['accent' => 'رنگ تأکیدی', 'surface' => 'پس‌زمینه', 'text' => 'رنگ متن', 'border' => 'خط جداکننده'] as $key => $label): ?><label><?php echo esc_html($label); ?><input type="color" name="seeheader_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($o[$key]); ?>"></label><?php endforeach; ?></div>
          </section>
          <section class="sh-side-card" id="sh-responsive"><small>06 / نمایشگرها</small>
            <h2>رفتار واکنش‌گرا</h2><label class="sh-check"><input type="checkbox" name="seeheader_options[desktop]" value="1" <?php checked($o['desktop']); ?>> نمایش در دسکتاپ</label><label class="sh-check"><input type="checkbox" name="seeheader_options[mobile]" value="1" <?php checked($o['mobile']); ?>> نمایش در موبایل</label><label>ارتفاع لوگو <span class="sh-range"><input type="range" min="24" max="100" name="seeheader_options[logo_height]" value="<?php echo esc_attr($o['logo_height']); ?>"><output><?php echo esc_html($o['logo_height']); ?>px</output></span></label><label>نقطه شکست موبایل <span class="sh-range"><input type="range" min="640" max="1024" step="16" name="seeheader_options[mobile_breakpoint]" value="<?php echo esc_attr($o['mobile_breakpoint']); ?>"><output><?php echo esc_html($o['mobile_breakpoint']); ?>px</output></span></label>
          </section>
          <section class="sh-side-card sh-help"><small>نحوه نمایش</small>
            <h2>در سایت قرار دهید</h2>
            <p>هدر خودکار، ویجت Elementor یا شورت‌کد <code>[seeheader]</code>.</p>
          </section>
        </aside>
      </div><?php submit_button('ذخیره و انتشار', 'primary sh-save'); ?>
    </form>
  </div><?php
      }
      function seeheader_admin_action_row($index, $item, $icons)
      { ?><div class="sh-action-row"><label>عنوان<input type="text" name="seeheader_options[items][<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="مثلاً تماس با ما"></label><label>پیوند<input type="url" name="seeheader_options[items][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($item['url'] ?? ''); ?>" placeholder="https://"></label><label>آیکن<select name="seeheader_options[items][<?php echo esc_attr($index); ?>][icon]">
        <option value="">بدون آیکن</option><?php foreach ($icons as $v => $label) printf('<option value="%s" %s>%s</option>', esc_attr($v), selected($item['icon'] ?? '', $v, false), esc_html($label)); ?>
      </select></label><button type="button" class="sh-remove" aria-label="حذف پیوند">×</button></div><?php }

                                                                                                    function seeheader_logo($o)
                                                                                                    {
                                                                                                      $url = $o['logo'] ?? '';
                                                                                                      if (!$url && has_custom_logo()) return get_custom_logo();
                                                                                                      if (!$url && is_file(SEEHEADER_DIR . 'assets/readystudio-logo.svg')) $url = SEEHEADER_URL . 'assets/readystudio-logo.svg';
                                                                                                      if (!$url) $url = get_site_icon_url(64);
                                                                                                      if ($url) return '<a class="sh-logo" href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                                                                                      return '<a class="sh-logo sh-site-name" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>';
                                                                                                    }
                                                                                                    function seeheader_icon($path)
                                                                                                    {
                                                                                                      if (!$path) return '';
                                                                                                      $path = str_replace('\\', '/', $path);
                                                                                                      if (strpos($path, '..') !== false) return '';
                                                                                                      $full = SEEHEADER_DIR . 'assets/icons/' . $path;
                                                                                                      if (!is_file($full)) return '';
                                                                                                      return '<img class="sh-action-icon" src="' . esc_url(SEEHEADER_URL . 'assets/icons/' . implode('/', array_map('rawurlencode', explode('/', $path)))) . '" alt="" aria-hidden="true">';
                                                                                                    }
function seeheader_menu_title_with_icon($title, $item, $args, $depth)
{
  if (empty($args->menu_class) || strpos($args->menu_class, 'sh-menu') === false) return $title;
  $options=seeheader_options(); $icon=$options['menu_icons'][$item->ID]??'';
  return $icon ? '<span class="sh-menu-icon">'.seeheader_icon($icon).'</span>'.$title : $title;
}
function seeheader_menu_markup($menu_id)
{
  add_filter('nav_menu_item_title','seeheader_menu_title_with_icon',10,4);
  $markup=wp_nav_menu(['menu'=>(int)$menu_id,'container'=>false,'menu_class'=>'sh-menu','depth'=>2,'fallback_cb'=>false,'echo'=>false]);
  remove_filter('nav_menu_item_title','seeheader_menu_title_with_icon',10);
  return $markup;
}
                                                                                                    function seeheader_render($args = [])
                                                                                                    {
                                                                                                      $o = wp_parse_args($args, seeheader_options());
                                                                                                      $theme = in_array($o['theme'], ['classic', 'centered', 'split', 'glass', 'dark', 'minimal', 'editorial', 'commerce', 'floating'], true) ? $o['theme'] : 'classic';
                                                                                                      // Give dark and commerce themes useful first-run palettes while preserving any colors the site owner has chosen.
                                                                                                      if ($theme === 'dark') {
                                                                                                        if ($o['surface'] === '#ffffff') $o['surface'] = '#201e1c';
                                                                                                        if ($o['text'] === '#201d1b') $o['text'] = '#f7f5f2';
                                                                                                        if ($o['border'] === '#dedbd8') $o['border'] = '#393633';
                                                                                                      }
                                                                                                      if ($theme === 'commerce') {
                                                                                                        if ($o['surface'] === '#ffffff') $o['surface'] = '#153c32';
                                                                                                        if ($o['text'] === '#201d1b') $o['text'] = '#f6f1e5';
                                                                                                        if ($o['accent'] === '#e5442d') $o['accent'] = '#e0bd73';
                                                                                                        if ($o['border'] === '#dedbd8') $o['border'] = '#31594f';
                                                                                                      }
                                                                                                      $cls = 'sh-header sh-theme-' . $theme . (!empty($o['sticky']) ? ' sh-sticky' : '') . (!empty($o['shadow']) ? ' sh-shadow' : '') . (empty($o['desktop']) ? ' sh-hide-desktop' : '') . (empty($o['mobile']) ? ' sh-hide-mobile' : '');
                                                                                                      $id = 'sh-nav-' . wp_unique_id();
                                                                                                      $search_id = 'sh-search-' . wp_unique_id();
                                                                                                      $vars = sprintf('--sh-accent:%s;--sh-surface:%s;--sh-text:%s;--sh-border:%s;--sh-width:%dpx;--sh-logo-height:%dpx', esc_attr($o['accent']), esc_attr($o['surface']), esc_attr($o['text']), esc_attr($o['border']), absint($o['width']), absint($o['logo_height']));
                                                                                                      ob_start(); ?>
  <header class="<?php echo esc_attr($cls); ?>" style="<?php echo esc_attr($vars); ?>" dir="rtl">
    <?php if (!empty($o['topbar']) && !empty($o['topbar_text'])): ?><div class="sh-topbar"><?php if (!empty($o['topbar_url'])): ?><a href="<?php echo esc_url($o['topbar_url']); ?>"><?php echo esc_html($o['topbar_text']); ?><span aria-hidden="true">←</span></a><?php else: ?><span><?php echo esc_html($o['topbar_text']); ?></span><?php endif; ?></div><?php endif; ?>
    <div class="sh-main"><button class="sh-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($id); ?>" aria-label="باز کردن منو"><span></span><span></span></button>
      <?php echo seeheader_logo($o); ?><div class="sh-tools"><?php if (!empty($o['account'])): $account = $o['account_url'] ?: (is_user_logged_in() ? admin_url('profile.php') : wp_login_url()); ?><a class="sh-account" href="<?php echo esc_url($account); ?>" aria-label="<?php echo is_user_logged_in() ? 'حساب کاربری' : esc_attr($o['account_label'] ?: 'ورود'); ?>"><?php echo seeheader_icon('Users/user.svg'); ?><span class="sh-tool-label"><?php echo is_user_logged_in() ? 'حساب کاربری' : esc_html($o['account_label'] ?: 'ورود'); ?></span></a><?php endif; ?>
        <?php if (!empty($o['search'])): ?><form class="sh-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>"><label class="screen-reader-text" for="<?php echo esc_attr($search_id); ?>">جستجو</label><input id="<?php echo esc_attr($search_id); ?>" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="جستجو..."><button type="submit" aria-label="جستجو"><span class="sh-search-icon"></span></button></form><?php endif; ?></div>
    </div>
    <nav class="sh-nav" id="<?php echo esc_attr($id); ?>" aria-label="فهرست اصلی">
      <div class="sh-nav-inner"><?php if (!empty($o['menu'])) echo seeheader_menu_markup($o['menu']);
                                                                                                      else echo '<ul class="sh-menu"><li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('صفحه اصلی', 'seeheader') . '</a></li></ul>'; ?>
        <?php foreach ((array)$o['items'] as $item): ?><a class="sh-custom-link" href="<?php echo esc_url($item['url']); ?>"><?php echo seeheader_icon($item['icon']); ?><span><?php echo esc_html($item['label']); ?></span></a><?php endforeach; ?></div>
    </nav>
  </header><?php return ob_get_clean();
                                                                                                    }
                                                                                                    add_shortcode('seeheader', 'seeheader_render');
                                                                                                    add_action('wp_body_open', function () {
                                                                                                      $o = seeheader_options();
                                                                                                      if ($o['enabled']) echo seeheader_render();
                                                                                                    }, 5);
                                                                                                    add_action('elementor/widgets/register', function ($manager) {
                                                                                                      if (!class_exists('Elementor\\Widget_Base')) return;
                                                                                                      $manager->register(new class extends \Elementor\Widget_Base {
                                                                                                        public function get_name()
                                                                                                        {
                                                                                                          return 'seeheader';
                                                                                                        }
                                                                                                        public function get_title()
                                                                                                        {
                                                                                                          return 'SeeHeader';
                                                                                                        }
                                                                                                        public function get_icon()
                                                                                                        {
                                                                                                          return 'eicon-header';
                                                                                                        }
                                                                                                        public function get_categories()
                                                                                                        {
                                                                                                          return ['general'];
                                                                                                        }
                                                                                                        protected function register_controls()
                                                                                                        {
                                                                                                          $this->start_controls_section('content', ['label' => 'هدر']);
                                                                                                          $this->add_control('theme', ['label' => 'پوسته', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => ['settings' => 'استفاده از تنظیمات افزونه', 'classic' => 'ReadyStudio', 'centered' => 'لوگوی وسط', 'split' => 'دو بخشی', 'glass' => 'شیشه‌ای', 'dark' => 'تیره', 'minimal' => 'مینیمال', 'editorial' => 'مجله‌ای', 'commerce' => 'فروشگاهی', 'floating' => 'شناور']]);
                                                                                                          $this->add_control('logo', ['label' => 'تصویر لوگو', 'type' => \Elementor\Controls_Manager::MEDIA]);
                                                                                                          $this->add_control('search', ['label' => 'نمایش جستجو', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
                                                                                                          $this->add_control('account', ['label' => 'نمایش ورود', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
                                                                                                          $this->end_controls_section();
                                                                                                        }
                                                                                                        protected function render()
                                                                                                        {
                                                                                                          $s = $this->get_settings_for_display();
                                                                                                          $o = seeheader_options();
                                                                                                          if ($s['theme'] !== 'settings') $o['theme'] = $s['theme'];
                                                                                                          if (!empty($s['logo']['url'])) $o['logo'] = $s['logo']['url'];
                                                                                                          $o['search'] = $s['search'] === 'yes';
                                                                                                          $o['account'] = $s['account'] === 'yes';
                                                                                                          echo seeheader_render($o);
                                                                                                        }
                                                                                                      });
                                                                                                    });
