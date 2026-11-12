<?php

/**
 * Plugin Name: SeeHeader — هدر ساز
 * Description: هدر ساز واکنش‌گرا با طرح‌های متنوع، منوهای وردپرس و ویجت Elementor.
 * Version: 1.6.0
 * Author: ReadyStudio
 * Text Domain: seeheader
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('SEEHEADER_VERSION', '1.6.0');
define('SEEHEADER_DIR', plugin_dir_path(__FILE__));
define('SEEHEADER_URL', plugin_dir_url(__FILE__));

function seeheader_defaults()
{
  return ['enabled' => 0, 'theme' => 'classic', 'logo' => '', 'menu' => 0, 'menu_source' => 'builder', 'nav_items' => [], 'menu_icons' => [], 'search' => 1, 'account' => 1, 'account_url' => '', 'account_label' => 'ورود', 'account_icon' => 'Users/user.svg', 'menu_arrow_icon' => 'Arrows/alt-arrow-down.svg', 'submenu_arrow_icon' => 'Arrows/alt-arrow-left.svg', 'back_arrow_icon' => 'Arrows/alt-arrow-right.svg', 'topbar_arrow_icon' => 'Arrows/alt-arrow-left.svg', 'sticky' => 0, 'items' => [], 'palette_mode' => 'theme', 'accent' => '#e5442d', 'surface' => '#ffffff', 'text' => '#201d1b', 'border' => '#dedbd8', 'menu_text' => '', 'menu_hover' => '', 'icon_color' => '', 'icon_hover' => '', 'button_bg' => '', 'button_text' => '', 'button_hover_bg' => '', 'button_hover_text' => '', 'button_border' => '', 'button_style' => 'solid', 'button_padding' => 11, 'button_radius' => 10, 'width' => 1240, 'logo_height' => 52, 'main_height' => '', 'search_width' => '', 'nav_gap' => '', 'control_radius' => '', 'nav_alignment' => '', 'desktop' => 1, 'mobile' => 1, 'shadow' => 0, 'topbar' => 0, 'topbar_text' => '', 'topbar_url' => '', 'mobile_breakpoint' => 782];
}
function seeheader_scalar($value, $fallback = '')
{
  return is_scalar($value) ? (string) $value : $fallback;
}
function seeheader_theme_palette($theme)
{
  $palettes = [
    'classic' => ['#e5442d', '#ffffff', '#201d1b', '#dedbd8'],
    'centered' => ['#e5442d', '#ffffff', '#201d1b', '#dedbd8'],
    'split' => ['#e5442d', '#ffffff', '#201d1b', '#dedbd8'],
    'glass' => ['#4b72a1', '#f8fbff', '#25374d', '#d7e3ef'],
    'dark' => ['#e5442d', '#201e1c', '#f7f5f2', '#393633'],
    'minimal' => ['#201d1b', '#ffffff', '#201d1b', '#e7e7e7'],
    'editorial' => ['#8b5337', '#f7f3ec', '#28231e', '#c8bcb0'],
    'commerce' => ['#e0bd73', '#153c32', '#f6f1e5', '#31594f'],
    'floating' => ['#675ab8', '#ffffff', '#25213a', '#dfd9ee'],
    'arvancloud' => ['#08a6aa', '#ffffff', '#18334e', '#d9e3eb'],
    'digikala' => ['#ef4056', '#ffffff', '#23254a', '#e0e0e2'],
  ];
  return array_combine(['accent', 'surface', 'text', 'border'], $palettes[$theme] ?? $palettes['classic']);
}
function seeheader_icon_catalog()
{
  static $icons = null;
  if ($icons !== null) return $icons;
  $icons = [];
  $base = SEEHEADER_DIR . 'assets/icons/';
  if (!is_dir($base)) return $icons;
  try {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $file) {
      if (!$file->isFile() || strtolower($file->getExtension()) !== 'svg') continue;
      $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base)));
      $icons[] = ['path' => $relative, 'category' => dirname($relative) === '.' ? 'General' : dirname($relative), 'name' => ucwords(str_replace(['-', '_'], ' ', pathinfo($relative, PATHINFO_FILENAME)))];
    }
  } catch (UnexpectedValueException $e) {
    // A partially uploaded icon pack must not take down the settings page.
  }
  usort($icons, static function ($a, $b) {
    return [$a['category'], $a['name']] <=> [$b['category'], $b['name']];
  });
  return $icons;
}
function seeheader_options()
{
  $saved = get_option('seeheader_options', []);
  if (!is_array($saved)) $saved = [];
  $defaults = seeheader_defaults();
  foreach ($defaults as $key => $default) {
    if (!array_key_exists($key, $saved)) continue;
    if (is_array($default) ? !is_array($saved[$key]) : !is_scalar($saved[$key])) $saved[$key] = $default;
  }
  if (!isset($saved['menu_source'])) $saved['menu_source'] = !empty($saved['menu']) ? 'wordpress' : 'builder';
  if (!isset($saved['palette_mode'])) {
    $default_colors = seeheader_theme_palette('classic');
    $saved['palette_mode'] = 'theme';
    foreach ($default_colors as $key => $value) if (isset($saved[$key]) && is_string($saved[$key]) && strtolower($saved[$key]) !== $value) $saved['palette_mode'] = 'custom';
  }
  $saved['nav_items'] = array_values(array_filter($saved['nav_items'] ?? [], 'is_array'));
  $saved['items'] = array_values(array_filter($saved['items'] ?? [], 'is_array'));
  foreach (['nav_items', 'items'] as $list) foreach ($saved[$list] as &$row) foreach ($row as $key => $value) if (!is_scalar($value)) unset($row[$key]);
  unset($row);
  $saved['menu_icons'] = array_filter($saved['menu_icons'] ?? [], 'is_string');
  return wp_parse_args($saved, $defaults);
}
add_action('wp_enqueue_scripts', function () {
  $options = seeheader_options();
  $breakpoint = min(1024, max(640, absint($options['mobile_breakpoint'] ?? 782)));
  wp_enqueue_style('seeheader', SEEHEADER_URL . 'assets/header.css', [], SEEHEADER_VERSION);
  $responsive_file = SEEHEADER_DIR . 'assets/header-responsive.css';
  if (is_readable($responsive_file)) {
    $responsive = file_get_contents($responsive_file);
    if ($responsive !== false) {
      $responsive = str_replace(['__SH_MOBILE_MAX__', '__SH_DESKTOP_MIN__'], [$breakpoint . 'px', ($breakpoint + 1) . 'px'], $responsive);
      wp_add_inline_style('seeheader', $responsive);
    }
  }
  wp_enqueue_script('seeheader', SEEHEADER_URL . 'assets/header.js', [], SEEHEADER_VERSION, true);
  wp_localize_script('seeheader', 'SeeHeaderConfig', ['breakpoint' => $breakpoint]);
});
add_action('admin_enqueue_scripts', function ($hook) {
  if (empty($GLOBALS['seeheader_admin_hook']) || $hook !== $GLOBALS['seeheader_admin_hook']) return;
  wp_enqueue_media();
  wp_enqueue_script('jquery-ui-sortable');
  wp_enqueue_style('seeheader-admin', SEEHEADER_URL . 'assets/admin.css', [], SEEHEADER_VERSION);
  wp_enqueue_script('seeheader-admin', SEEHEADER_URL . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], SEEHEADER_VERSION, true);
  $themes = ['classic', 'centered', 'split', 'glass', 'dark', 'minimal', 'editorial', 'commerce', 'floating', 'arvancloud', 'digikala'];
  wp_localize_script('seeheader-admin', 'SeeHeaderIconPack', ['icons' => seeheader_icon_catalog(), 'baseUrl' => SEEHEADER_URL . 'assets/icons/', 'palettes' => array_combine($themes, array_map('seeheader_theme_palette', $themes))]);
});
function seeheader_sanitize($in)
{
  $out = seeheader_defaults();
  $in = is_array($in) ? $in : [];
  $out['enabled'] = empty($in['enabled']) ? 0 : 1;
  $out['theme'] = in_array(($in['theme'] ?? ''), ['classic', 'centered', 'split', 'glass', 'dark', 'minimal', 'editorial', 'commerce', 'floating', 'arvancloud', 'digikala'], true) ? $in['theme'] : 'classic';
  $out['logo'] = esc_url_raw(seeheader_scalar($in['logo'] ?? ''));
  $out['menu'] = absint(seeheader_scalar($in['menu'] ?? 0));
  $menu_source = $in['menu_source'] ?? 'builder';
  $out['menu_source'] = in_array($menu_source, ['builder', 'wordpress'], true) ? $menu_source : 'builder';
  $out['search'] = empty($in['search']) ? 0 : 1;
  $out['account'] = empty($in['account']) ? 0 : 1;
  $out['account_url'] = esc_url_raw(seeheader_scalar($in['account_url'] ?? ''));
  $out['account_label'] = sanitize_text_field(seeheader_scalar($in['account_label'] ?? 'ورود'));
  $out['sticky'] = empty($in['sticky']) ? 0 : 1;
  $out['topbar'] = empty($in['topbar']) ? 0 : 1;
  $out['topbar_text'] = sanitize_text_field(seeheader_scalar($in['topbar_text'] ?? ''));
  $out['topbar_url'] = esc_url_raw(seeheader_scalar($in['topbar_url'] ?? ''));
  $out['mobile_breakpoint'] = min(1024, max(640, absint(seeheader_scalar($in['mobile_breakpoint'] ?? 782))));
  $valid_icon_paths = array_column(seeheader_icon_catalog(), 'path');
  $out['account_icon'] = in_array($in['account_icon'] ?? '', $valid_icon_paths, true) ? $in['account_icon'] : 'Users/user.svg';
  foreach (['menu_arrow_icon', 'submenu_arrow_icon', 'back_arrow_icon', 'topbar_arrow_icon'] as $arrow_key) {
    $candidate = seeheader_scalar($in[$arrow_key] ?? '');
    $out[$arrow_key] = in_array($candidate, $valid_icon_paths, true) ? $candidate : seeheader_defaults()[$arrow_key];
  }
  $out['nav_items'] = [];
  $used_ids = [];
  if (!empty($in['nav_items']) && is_array($in['nav_items'])) foreach (array_slice($in['nav_items'], 0, 200) as $index => $row) {
    if (!is_array($row)) continue;
    $id = sanitize_key(seeheader_scalar($row['id'] ?? ''));
    $label = sanitize_text_field(seeheader_scalar($row['label'] ?? ''));
    $url = esc_url_raw(seeheader_scalar($row['url'] ?? ''));
    $icon = sanitize_text_field(seeheader_scalar($row['icon'] ?? ''));
    $parent = sanitize_key(seeheader_scalar($row['parent'] ?? ''));
    if (!$label) continue;
    if (!$id) $id = 'nav-' . sanitize_key($index);
    if (isset($used_ids[$id])) $id = 'nav-' . count($out['nav_items']) . '-' . wp_generate_password(6, false, false);
    $used_ids[$id] = true;
    if ($icon && !in_array($icon, $valid_icon_paths, true)) $icon = '';
    $badge_icon = sanitize_text_field(seeheader_scalar($row['badge_icon'] ?? ''));
    $out['nav_items'][] = ['id' => $id, 'label' => $label, 'url' => $url ?: '#', 'icon' => $icon, 'parent' => $parent === $id ? '' : $parent, 'description' => sanitize_text_field(seeheader_scalar($row['description'] ?? '')), 'badge' => sanitize_text_field(seeheader_scalar($row['badge'] ?? '')), 'badge_icon' => in_array($badge_icon, $valid_icon_paths, true) ? $badge_icon : '', 'badge_text_color' => sanitize_hex_color(seeheader_scalar($row['badge_text_color'] ?? '')) ?: '', 'badge_bg_color' => sanitize_hex_color(seeheader_scalar($row['badge_bg_color'] ?? '')) ?: '', 'image' => esc_url_raw(seeheader_scalar($row['image'] ?? '')), 'image_alt' => sanitize_text_field(seeheader_scalar($row['image_alt'] ?? '')), 'type' => ($row['type'] ?? 'link') === 'heading' ? 'heading' : 'link', 'column' => min(4, max(1, absint(seeheader_scalar($row['column'] ?? 1)))), 'mega' => empty($row['mega']) ? 0 : 1, 'new_tab' => empty($row['new_tab']) ? 0 : 1];
  }
  $parent_map = [];
  foreach ($out['nav_items'] as $item) $parent_map[$item['id']] = $item['parent'];
  foreach ($out['nav_items'] as &$item) {
    $seen = [$item['id']];
    $parent = $item['parent'];
    $depth = 0;
    while ($parent && isset($parent_map[$parent]) && $depth++ < count($parent_map) + 1) {
      if (in_array($parent, $seen, true)) {
        $item['parent'] = '';
        break;
      }
      $seen[] = $parent;
      $parent = $parent_map[$parent];
    }
    if ($parent && !isset($parent_map[$parent])) $item['parent'] = '';
  }
  unset($item);
  $out['menu_icons'] = [];
  if (!empty($in['menu_icons']) && is_array($in['menu_icons'])) foreach ($in['menu_icons'] as $item_id => $icon) {
    $item_id = absint($item_id);
    if ($item_id && in_array($icon, $valid_icon_paths, true)) $out['menu_icons'][$item_id] = $icon;
  }
  foreach (['accent', 'surface', 'text', 'border'] as $color) {
    $value = sanitize_hex_color(seeheader_scalar($in[$color] ?? ''));
    $out[$color] = $value ?: seeheader_defaults()[$color];
  }
  foreach (['menu_text', 'menu_hover', 'icon_color', 'icon_hover', 'button_bg', 'button_text', 'button_hover_bg', 'button_hover_text', 'button_border'] as $color) $out[$color] = sanitize_hex_color(seeheader_scalar($in[$color] ?? '')) ?: '';
  $out['button_style'] = in_array($in['button_style'] ?? '', ['solid', 'outline', 'ghost'], true) ? $in['button_style'] : 'solid';
  $out['button_padding'] = min(32, max(0, absint(seeheader_scalar($in['button_padding'] ?? 11))));
  $out['button_radius'] = min(48, max(0, absint(seeheader_scalar($in['button_radius'] ?? 10))));
  $out['palette_mode'] = ($in['palette_mode'] ?? 'theme') === 'custom' ? 'custom' : 'theme';
  $out['width'] = min(1920, max(760, absint(seeheader_scalar($in['width'] ?? 1240))));
  $out['logo_height'] = min(100, max(24, absint(seeheader_scalar($in['logo_height'] ?? 52))));
  foreach (['main_height' => [48, 140], 'search_width' => [120, 700], 'nav_gap' => [0, 60], 'control_radius' => [0, 40]] as $key => $range) $out[$key] = !isset($in[$key]) || $in[$key] === '' ? '' : min($range[1], max($range[0], absint(seeheader_scalar($in[$key]))));
  $nav_alignment = $in['nav_alignment'] ?? '';
  $out['nav_alignment'] = in_array($nav_alignment, ['', 'flex-start', 'center', 'flex-end'], true) ? $nav_alignment : '';
  foreach (['desktop', 'mobile', 'shadow'] as $flag) $out[$flag] = empty($in[$flag]) ? 0 : 1;
  $out['items'] = [];
  if (!empty($in['items']) && is_array($in['items'])) foreach (array_slice($in['items'], 0, 24) as $item) {
    if (!is_array($item)) continue;
    $label = sanitize_text_field(seeheader_scalar($item['label'] ?? ''));
    $url = esc_url_raw(seeheader_scalar($item['url'] ?? ''));
    $icon = sanitize_text_field(seeheader_scalar($item['icon'] ?? ''));
    if ($icon && !in_array($icon, $valid_icon_paths, true)) $icon = '';
    if ($label && $url) $out['items'][] = ['label' => $label, 'url' => $url, 'icon' => $icon, 'new_tab' => empty($item['new_tab']) ? 0 : 1];
  }
  return $out;
}
add_action('admin_menu', function () {
  $GLOBALS['seeheader_admin_hook'] = add_menu_page('سازنده هدر', 'هدر ساز', 'manage_options', 'seeheader', 'seeheader_settings_page', 'dashicons-layout', 58);
});
add_action('admin_init', function () {
  register_setting('seeheader_group', 'seeheader_options', ['sanitize_callback' => 'seeheader_sanitize']);
});
add_action('rest_api_init', function () {
  $permission = static function () { return current_user_can('manage_options'); };
  register_rest_route('seeheader/v1', '/config', [
    ['methods' => 'GET', 'permission_callback' => $permission, 'callback' => static function () { return rest_ensure_response(seeheader_options()); }],
    ['methods' => 'POST', 'permission_callback' => $permission, 'callback' => static function ($request) {
      $changes = $request->get_json_params();
      if (!is_array($changes)) return new WP_Error('seeheader_invalid_config', 'تنظیمات باید یک شیء JSON باشد.', ['status' => 400]);
      $allowed = array_keys(seeheader_defaults());
      $changes = array_intersect_key($changes, array_fill_keys($allowed, true));
      $next = seeheader_sanitize(array_replace(seeheader_options(), $changes));
      update_option('seeheader_options', $next);
      return rest_ensure_response($next);
    }],
  ]);
  register_rest_route('seeheader/v1', '/icons', ['methods' => 'GET', 'permission_callback' => $permission, 'callback' => static function () { return rest_ensure_response(seeheader_icon_catalog()); }]);
});
function seeheader_admin_arrow_setting($key, $label, $value)
{
  $icon_url = seeheader_icon_url($value);
  echo '<div class="sh-arrow-icon-field" data-sh-icon-control data-sh-arrow-control><span>' . esc_html($label) . '</span><input data-field="icon" type="hidden" name="seeheader_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"><button type="button" class="sh-icon-select" aria-label="' . esc_attr($label) . '"><img data-icon-preview src="' . esc_url($icon_url) . '" alt=""' . ($icon_url ? '' : ' hidden') . '><b>' . esc_html($value ? basename($value) : 'انتخاب از پک') . '</b></button></div>';
}
function seeheader_settings_page()
{
  if (!current_user_can('manage_options')) return;
  $o = seeheader_options();
  $menus = wp_get_nav_menus();
  if (is_wp_error($menus) || !is_array($menus)) $menus = [];
  $themes = ['classic' => 'ReadyStudio', 'centered' => 'لوگو در مرکز', 'split' => 'دو بخش نامتقارن', 'glass' => 'شیشه‌ای', 'dark' => 'تیره', 'minimal' => 'مینیمال', 'editorial' => 'مجله‌ای', 'commerce' => 'فروشگاهی', 'floating' => 'شناور', 'arvancloud' => 'آروان‌کلاد', 'digikala' => 'دیجی‌کالا'];
  $icon_catalog = seeheader_icon_catalog();
  $menu_items = $o['menu'] ? wp_get_nav_menu_items($o['menu']) : [];
  if (!is_array($menu_items)) $menu_items = [];
?> <div class="wrap seeheader-admin" dir="rtl">
    <?php settings_errors('seeheader_options'); ?>
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
              </div><span><?php echo count($themes); ?> پوسته</span>
            </div>
            <div class="sh-theme-grid"><?php foreach ($themes as $key => $title): ?><label class="sh-theme-card <?php echo $o['theme'] === $key ? 'is-selected' : ''; ?>"><input type="radio" name="seeheader_options[theme]" value="<?php echo esc_attr($key); ?>" <?php checked($o['theme'], $key); ?>><span class="sh-mini sh-mini-<?php echo esc_attr($key); ?>"><i></i><i></i><i></i></span><b><?php echo esc_html($title); ?></b></label><?php endforeach; ?></div>
          </section>
          <section class="sh-section" id="sh-layout">
            <div class="sh-section-title">
              <div><small>02 / ساختار</small>
                <h2>محتوای هدر</h2>
              </div><span class="sh-section-note">لوگو، منو و ابزارها</span>
            </div>
            <div class="sh-menu-source"><label>منبع منو<select id="sh-menu-source" name="seeheader_options[menu_source]">
                  <option value="builder" <?php selected($o['menu_source'], 'builder'); ?>>ساخت منو در افزونه</option>
                  <option value="wordpress" <?php selected($o['menu_source'], 'wordpress'); ?>>استفاده از منوی وردپرس</option>
                </select></label>
              <p>با منوساز افزونه، ساختار منو و مگامنو را همین‌جا بسازید.</p>
            </div>
            <div class="sh-field-grid"><label>لوگو <span class="sh-media-field"><input type="url" id="sh-logo" name="seeheader_options[logo]" value="<?php echo esc_attr($o['logo']); ?>" placeholder="لوگوی پوسته یا لوگوی پیش‌فرض"><button type="button" class="button" data-sh-media="sh-logo">انتخاب تصویر</button></span></label><label class="sh-wp-source-field">منوی وردپرس<select name="seeheader_options[menu]">
                  <option value="0">انتخاب منوی وردپرس</option><?php foreach ($menus as $m) printf('<option value="%d" %s>%s</option>', $m->term_id, selected($o['menu'], $m->term_id, false), esc_html($m->name)); ?>
                </select></label><label>پیوند حساب کاربری<input type="url" name="seeheader_options[account_url]" value="<?php echo esc_attr($o['account_url']); ?>" placeholder="خالی = ورود / پیشخوان"></label><label>متن دکمه حساب<input type="text" name="seeheader_options[account_label]" value="<?php echo esc_attr($o['account_label']); ?>" placeholder="ورود"></label><label>آیکن حساب کاربری<select name="seeheader_options[account_icon]"><?php foreach ($icon_catalog as $icon_option) printf('<option value="%s" %s>%s / %s</option>', esc_attr($icon_option['path']), selected($o['account_icon'], $icon_option['path'], false), esc_html($icon_option['category']), esc_html($icon_option['name'])); ?></select></label><label>عرض محتوای هدر <span class="sh-range"><input type="range" min="760" max="1920" step="20" name="seeheader_options[width]" value="<?php echo esc_attr($o['width']); ?>"><output><?php echo esc_html($o['width']); ?>px</output></span></label></div>
            <details class="sh-layout-advanced">
              <summary>تنظیمات دقیق چیدمان</summary>
              <div class="sh-field-grid">
                <label>ارتفاع ردیف اصلی<input type="number" min="48" max="140" name="seeheader_options[main_height]" value="<?php echo esc_attr($o['main_height']); ?>" placeholder="پیش‌فرض پوسته"></label>
                <label>عرض جستجو<input type="number" min="120" max="700" name="seeheader_options[search_width]" value="<?php echo esc_attr($o['search_width']); ?>" placeholder="پیش‌فرض پوسته"></label>
                <label>فاصله آیتم‌های منو<input type="number" min="0" max="60" name="seeheader_options[nav_gap]" value="<?php echo esc_attr($o['nav_gap']); ?>" placeholder="پیش‌فرض پوسته"></label>
                <label>گردی گوشه ابزارها<input type="number" min="0" max="40" name="seeheader_options[control_radius]" value="<?php echo esc_attr($o['control_radius']); ?>" placeholder="پیش‌فرض پوسته"></label>
                <label>چیدمان آیتم‌های منو<select name="seeheader_options[nav_alignment]">
                    <option value="" <?php selected($o['nav_alignment'], ''); ?>>پیش‌فرض پوسته</option>
                    <option value="flex-start" <?php selected($o['nav_alignment'], 'flex-start'); ?>>راست</option>
                    <option value="center" <?php selected($o['nav_alignment'], 'center'); ?>>وسط</option>
                    <option value="flex-end" <?php selected($o['nav_alignment'], 'flex-end'); ?>>چپ</option>
                  </select></label>
              </div>
            </details>
            <details class="sh-layout-advanced">
              <summary>آیکن‌های فلش و راهبری</summary>
              <p>فلش‌ها را از پک آیکن افزونه انتخاب کنید. آیکن‌های پوشه‌های Arrows و Arrows Action برای این بخش مناسب‌اند.</p>
              <div class="sh-field-grid">
                <?php seeheader_admin_arrow_setting('menu_arrow_icon', 'فلش بازشدن منو در دسکتاپ', $o['menu_arrow_icon']); ?>
                <?php seeheader_admin_arrow_setting('submenu_arrow_icon', 'فلش ورود به زیرمنو در موبایل', $o['submenu_arrow_icon']); ?>
                <?php seeheader_admin_arrow_setting('back_arrow_icon', 'فلش بازگشت در موبایل', $o['back_arrow_icon']); ?>
                <?php seeheader_admin_arrow_setting('topbar_arrow_icon', 'فلش نوار اعلان', $o['topbar_arrow_icon']); ?>
              </div>
            </details>
            <div class="sh-menu-icons sh-wp-source-field">
              <details>
                <summary>تنظیم آیکن برای آیتم‌های منو</summary><?php if ($menu_items): ?><div class="sh-menu-icon-grid"><?php foreach ($menu_items as $menu_item): $menu_icon = $o['menu_icons'][$menu_item->ID] ?? ''; ?><div class="sh-menu-icon-field"><span><?php echo esc_html($menu_item->title); ?></span><span data-sh-icon-control><input data-field="icon" type="hidden" name="seeheader_options[menu_icons][<?php echo absint($menu_item->ID); ?>]" value="<?php echo esc_attr($menu_icon); ?>"><button type="button" class="sh-icon-select" aria-label="انتخاب آیکن برای <?php echo esc_attr($menu_item->title); ?>"><img data-icon-preview src="<?php echo esc_url(seeheader_icon_url($menu_icon)); ?>" alt="" <?php echo $menu_icon ? '' : 'hidden'; ?>><b><?php echo $menu_icon ? esc_html(basename($menu_icon)) : 'انتخاب از پک'; ?></b></button></span></div><?php endforeach; ?></div><?php else: ?><p>ابتدا یک منوی اصلی انتخاب و تنظیمات را ذخیره کنید تا آیتم‌های منو برای انتخاب آیکن نمایش داده شوند.</p><?php endif; ?>
              </details>
            </div>
            <section class="sh-built-menu sh-builder-source" id="sh-built-menu">
              <div class="sh-built-menu-head">
                <div><b>منوی اصلی هدر</b><small>آیتم‌ها را بکشید تا مرتب شوند. برای مگامنو، آیتم‌های زیرمجموعه را به والد وصل کنید.</small></div><button type="button" class="button button-primary sh-add-nav-item">+ افزودن آیتم</button>
              </div>
              <div class="sh-nav-items"><?php foreach ($o['nav_items'] as $i => $nav_item) seeheader_admin_nav_row($i, $nav_item, $o['nav_items'], $icon_catalog); ?></div>
              <template id="sh-nav-item-template"><?php seeheader_admin_nav_row('__INDEX__', [], $o['nav_items'], $icon_catalog); ?></template>
            </section>
            <div class="sh-icon-picker" hidden>
              <div class="sh-icon-picker-box" role="dialog" aria-modal="true" aria-labelledby="sh-icon-picker-title">
                <header>
                  <div><small>SEEHEADER ICON PACK</small>
                    <h3 id="sh-icon-picker-title">انتخاب آیکن</h3>
                  </div><button type="button" class="sh-icon-close" aria-label="بستن">×</button>
                </header><input type="search" class="sh-icon-search" placeholder="جست‌وجو در آیکن‌ها">
                <div class="sh-icon-categories"></div>
                <div class="sh-icon-grid"></div>
              </div>
            </div>
            <div class="sh-switches"><label><input type="checkbox" name="seeheader_options[search]" value="1" <?php checked($o['search']); ?>><span><b>جست‌وجوی سایت</b><small>فرم جست‌وجوی اصلی وردپرس</small></span></label><label><input type="checkbox" name="seeheader_options[account]" value="1" <?php checked($o['account']); ?>><span><b>حساب کاربری</b><small>ورود و پیوند حساب کاربر</small></span></label><label><input type="checkbox" name="seeheader_options[sticky]" value="1" <?php checked($o['sticky']); ?>><span><b>هدر چسبان</b><small>هنگام پیمایش بالای صفحه بماند</small></span></label><label><input type="checkbox" name="seeheader_options[shadow]" value="1" <?php checked($o['shadow']); ?>><span><b>سایه‌ی ظریف</b><small>جداسازی هدر از محتوای صفحه</small></span></label></div>
          </section>
          <section class="sh-section" id="sh-actions">
            <div class="sh-section-title">
              <div><small>03 / اکشن‌ها</small>
                <h2>پیوندها و دکمه‌ها</h2>
                <p>برای تماس، عضویت، فروشگاه یا هر مقصد دلخواه.</p>
              </div><button type="button" class="button sh-add-action">+ افزودن پیوند</button>
            </div>
            <div class="sh-action-list"><?php foreach ($o['items'] as $i => $item) seeheader_admin_action_row($i, $item); ?></div><template id="sh-action-template"><?php seeheader_admin_action_row('__INDEX__', []); ?></template>
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
              <div class="sh-live-head"><span data-sh-preview-logo><?php echo $o['logo'] ? '<img src="' . esc_url($o['logo']) . '" alt="">' : 'لوگوی سایت'; ?></span><i data-sh-preview-account></i><i data-sh-preview-search></i></div>
              <div class="sh-live-nav" data-sh-preview-menu></div>
            </div>
            <p class="sh-preview-caption">تغییرات ظاهری را پیش از ذخیره ببینید.</p>
          </section>
          <section class="sh-side-card"><small>05 / هویت بصری</small>
            <h2>رنگ‌ها</h2>
            <label>حالت رنگ<select name="seeheader_options[palette_mode]">
                <option value="theme" <?php selected($o['palette_mode'], 'theme'); ?>>رنگ آمادهٔ پوسته</option>
                <option value="custom" <?php selected($o['palette_mode'], 'custom'); ?>>رنگ دلخواه</option>
              </select></label>
            <div class="sh-colors"><?php foreach (['accent' => 'رنگ تأکیدی', 'surface' => 'پس‌زمینه', 'text' => 'رنگ متن', 'border' => 'خط جداکننده'] as $key => $label): ?><label><?php echo esc_html($label); ?><input type="color" name="seeheader_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($o[$key]); ?>"></label><?php endforeach; ?></div>
            <details class="sh-style-details" open><summary>رنگ منو و آیکن‌ها</summary><div class="sh-colors"><?php foreach (['menu_text' => 'متن منو', 'menu_hover' => 'هاور متن', 'icon_color' => 'آیکن‌ها', 'icon_hover' => 'هاور آیکن‌ها'] as $key => $label): ?><label><?php echo esc_html($label); ?><input type="color" name="seeheader_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($o[$key] ?: '#000000'); ?>" data-color-optional <?php echo !$o[$key] ? 'disabled' : ''; ?>><input type="checkbox" name="seeheader_color_auto[<?php echo esc_attr($key); ?>]" value="1" <?php checked($o[$key], ''); ?>> خودکار</label><?php endforeach; ?></div></details>
            <details class="sh-style-details"><summary>دکمه حساب کاربری</summary><div class="sh-colors"><?php foreach (['button_bg' => 'پس‌زمینه', 'button_text' => 'متن', 'button_hover_bg' => 'پس‌زمینه هاور', 'button_hover_text' => 'متن هاور', 'button_border' => 'حاشیه'] as $key => $label): ?><label><?php echo esc_html($label); ?><input type="color" name="seeheader_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($o[$key] ?: '#000000'); ?>" data-color-optional <?php echo !$o[$key] ? 'disabled' : ''; ?>><input type="checkbox" name="seeheader_color_auto[<?php echo esc_attr($key); ?>]" value="1" <?php checked($o[$key], ''); ?>> خودکار</label><?php endforeach; ?></div><label>نوع دکمه<select name="seeheader_options[button_style]"><option value="solid" <?php selected($o['button_style'], 'solid'); ?>>توپر</option><option value="outline" <?php selected($o['button_style'], 'outline'); ?>>دورخط</option><option value="ghost" <?php selected($o['button_style'], 'ghost'); ?>>شفاف</option></select></label><label>گردی گوشه <input type="number" min="0" max="48" name="seeheader_options[button_radius]" value="<?php echo esc_attr($o['button_radius']); ?>"></label><label>فاصله افقی <input type="number" min="0" max="32" name="seeheader_options[button_padding]" value="<?php echo esc_attr($o['button_padding']); ?>"></label></details>
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
      function seeheader_admin_action_row($index, $item)
      {
        $icon = $item['icon'] ?? ''; ?><div class="sh-action-row"><label>عنوان<input type="text" name="seeheader_options[items][<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="مثلاً تماس با ما"></label><label>پیوند<input type="url" name="seeheader_options[items][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($item['url'] ?? ''); ?>" placeholder="https://"></label>
    <div data-sh-icon-control><input data-field="icon" type="hidden" name="seeheader_options[items][<?php echo esc_attr($index); ?>][icon]" value="<?php echo esc_attr($icon); ?>"><span class="sh-icon-label">آیکن</span><button type="button" class="sh-icon-select" aria-label="انتخاب آیکن پیوند"><img data-icon-preview src="<?php echo esc_url(seeheader_icon_url($icon)); ?>" alt="" <?php echo $icon ? '' : 'hidden'; ?>><b><?php echo $icon ? esc_html(basename($icon)) : 'انتخاب از پک'; ?></b></button></div><label class="sh-action-new-tab"><input type="checkbox" name="seeheader_options[items][<?php echo esc_attr($index); ?>][new_tab]" value="1" <?php checked($item['new_tab'] ?? 0); ?>> زبانهٔ جدید</label><button type="button" class="sh-remove" aria-label="حذف پیوند">×</button>
  </div><?php }

      function seeheader_admin_nav_row($index, $item, $items, $catalog)
      {
        $id = $item['id'] ?? '__ITEMID__';
        $icon = $item['icon'] ?? '';
        $icon_url = seeheader_icon_url($icon);
        $image_id = 'sh-mega-image-' . $index;
        ?>
  <article class="sh-nav-row" data-nav-row>
    <header><button type="button" class="sh-drag" aria-label="جابجایی آیتم">⠿</button><strong data-row-title><?php echo esc_html($item['label'] ?? 'آیتم منو'); ?></strong><button type="button" class="sh-add-child" title="افزودن زیرآیتم">+ زیرآیتم</button><button type="button" class="sh-nav-remove" aria-label="حذف آیتم">حذف</button></header>
    <input type="hidden" data-field="id" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($id); ?>">
    <div class="sh-nav-main-fields">
      <label>عنوان<input data-nav-label type="text" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="مثلاً محصولات"></label>
      <label>نشانی پیوند<input type="url" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr(($item['url'] ?? '') === '#' ? '' : ($item['url'] ?? '')); ?>" placeholder="https:// یا خالی برای والد"></label>
      <div class="sh-icon-select-wrap" data-sh-icon-control><span>آیکن</span><input data-field="icon" type="hidden" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][icon]" value="<?php echo esc_attr($icon); ?>"><button type="button" class="sh-icon-select" aria-label="انتخاب آیکن"><img data-icon-preview src="<?php echo esc_url($icon_url); ?>" alt="" <?php echo $icon ? '' : 'hidden'; ?>><b><?php echo $icon ? esc_html(basename($icon)) : 'انتخاب از پک'; ?></b></button></div>
    </div>
    <details class="sh-nav-more">
      <summary>زیرمنو و تنظیمات مگامنو</summary>
      <div class="sh-nav-more-grid">
        <label>آیتم والد<select data-field="parent" data-nav-parent name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][parent]">
            <option value="">آیتم سطح اول</option><?php foreach ($items as $parent_index => $parent_item) {
                                                    if ((string)$parent_index === (string)$index || empty($parent_item['label'])) continue;
                                                    printf('<option value="%s" %s>%s</option>', esc_attr($parent_item['id'] ?? ''), selected($item['parent'] ?? '', $parent_item['id'] ?? '', false), esc_html($parent_item['label']));
                                                  } ?>
          </select></label>
        <label>ستون در مگامنو<select name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][column]"><?php for ($c = 1; $c <= 4; $c++) printf('<option value="%d" %s>ستون %d</option>', $c, selected($item['column'] ?? 1, $c, false), $c); ?></select></label>
        <label>متن بج<input type="text" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][badge]" value="<?php echo esc_attr($item['badge'] ?? ''); ?>" placeholder="جدید / به‌زودی"></label>
        <label class="sh-badge-color">رنگ متن بج<input type="color" data-color-optional name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][badge_text_color]" value="<?php echo esc_attr(($item['badge_text_color'] ?? '') ?: '#ffffff'); ?>" <?php echo empty($item['badge_text_color']) ? 'disabled' : ''; ?>><span><input type="checkbox" <?php checked(empty($item['badge_text_color'])); ?>> خودکار</span></label>
        <label class="sh-badge-color">رنگ پس‌زمینه بج<input type="color" data-color-optional name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][badge_bg_color]" value="<?php echo esc_attr(($item['badge_bg_color'] ?? '') ?: '#e5442d'); ?>" <?php echo empty($item['badge_bg_color']) ? 'disabled' : ''; ?>><span><input type="checkbox" <?php checked(empty($item['badge_bg_color'])); ?>> خودکار</span></label>
        <div class="sh-icon-select-wrap" data-sh-icon-control><span>آیکن بج</span><input data-field="icon" type="hidden" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][badge_icon]" value="<?php echo esc_attr($item['badge_icon'] ?? ''); ?>"><button type="button" class="sh-icon-select" aria-label="انتخاب آیکن بج"><img data-icon-preview src="<?php echo esc_url(seeheader_icon_url($item['badge_icon'] ?? '')); ?>" alt="" <?php echo empty($item['badge_icon']) ? 'hidden' : ''; ?>><b><?php echo !empty($item['badge_icon']) ? esc_html(basename($item['badge_icon'])) : 'انتخاب آیکن'; ?></b></button></div>
        <label>توضیح<input type="text" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][description]" value="<?php echo esc_attr($item['description'] ?? ''); ?>" placeholder="توضیح کوتاه"></label>
        <label class="sh-menu-image-field">تصویر آیتم یا کارت مگامنو<span class="sh-media-field"><input id="<?php echo esc_attr($image_id); ?>" type="url" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][image]" value="<?php echo esc_attr($item['image'] ?? ''); ?>" placeholder="انتخاب تصویر"><button type="button" class="button" data-sh-media="<?php echo esc_attr($image_id); ?>">انتخاب</button></span><img class="sh-image-preview" src="<?php echo esc_url($item['image'] ?? ''); ?>" alt="" <?php echo empty($item['image']) ? 'hidden' : ''; ?>></label>
        <label>متن جایگزین تصویر<input type="text" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][image_alt]" value="<?php echo esc_attr($item['image_alt'] ?? ''); ?>" placeholder="توصیف کوتاه تصویر"></label>
        <label>نوع آیتم<select name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][type]">
            <option value="link" <?php selected($item['type'] ?? 'link', 'link'); ?>>پیوند</option>
            <option value="heading" <?php selected($item['type'] ?? '', 'heading'); ?>>عنوان گروه</option>
          </select></label>
        <label class="sh-mini-check"><input type="checkbox" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][mega]" value="1" <?php checked($item['mega'] ?? 0); ?>> مگامنو برای این آیتم</label>
        <label class="sh-mini-check"><input type="checkbox" name="seeheader_options[nav_items][<?php echo esc_attr($index); ?>][new_tab]" value="1" <?php checked($item['new_tab'] ?? 0); ?>> بازشدن در زبانه جدید</label>
      </div>
    </details>
  </article><?php
          }
          function seeheader_children($items, $parent_id)
          {
            return array_values(array_filter($items, static function ($item) use ($parent_id) {
              return is_array($item) && (string)($item['parent'] ?? '') === (string)$parent_id;
            }));
          }
          function seeheader_render_nav_link($item, $has_children = false)
          {
            $is_heading = ($item['type'] ?? '') === 'heading';
            $url = $item['url'] ?? '#';
            $target = !empty($item['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $icon = !empty($item['icon']) ? '<span class="sh-menu-icon">' . seeheader_icon($item['icon']) . '</span>' : '';
            $description = !empty($item['description']) ? '<small>' . esc_html($item['description']) . '</small>' : '';
            $badge = '';
            if (!empty($item['badge'])) {
              $badge_vars = '';
              foreach (['badge_text_color' => '--sh-badge-text', 'badge_bg_color' => '--sh-badge-bg'] as $key => $variable) if (!empty($item[$key]) && sanitize_hex_color($item[$key])) $badge_vars .= $variable . ':' . $item[$key] . ';';
              $badge_icon = !empty($item['badge_icon']) ? seeheader_icon($item['badge_icon']) : '';
              $badge = '<em class="sh-menu-badge"' . ($badge_vars ? ' style="' . esc_attr($badge_vars) . '"' : '') . '>' . $badge_icon . '<span>' . esc_html($item['badge']) . '</span></em>';
            }
            $thumbnail = !empty($item['image']) ? '<span class="sh-menu-thumbnail"><img src="' . esc_url($item['image']) . '" alt="" width="34" height="34" loading="lazy" decoding="async"></span>' : '';
            if ($is_heading && $has_children) return '<button type="button" class="sh-mega-heading sh-menu-heading-toggle" aria-haspopup="true" aria-expanded="false">' . $thumbnail . $icon . '<span>' . esc_html($item['label']) . '</span>' . $badge . '<i class="sh-menu-chevron" aria-hidden="true"></i></button>' . $description;
            if ($is_heading) return '<span class="sh-mega-heading">' . $thumbnail . $icon . '<span>' . esc_html($item['label']) . '</span>' . $badge . '</span>' . $description;
            if (!$url || $url === '#') {
              $tag = $has_children ? 'button' : 'span';
              return '<' . $tag . ($tag === 'button' ? ' type="button" aria-expanded="false"' : '') . ' class="sh-menu-label' . ($has_children ? ' sh-menu-heading-toggle' : '') . '">' . $thumbnail . $icon . '<span>' . esc_html($item['label']) . '</span>' . $badge . ($has_children ? '<i class="sh-menu-chevron" aria-hidden="true"></i>' : '') . '</' . $tag . '>' . $description;
            }
            $aria = $has_children ? ' aria-haspopup="true" aria-expanded="false"' : '';
            return '<a href="' . esc_url($url) . '"' . $target . $aria . '>' . $thumbnail . $icon . '<span>' . esc_html($item['label']) . '</span>' . $badge . ($has_children ? '<i class="sh-menu-chevron" aria-hidden="true"></i>' : '') . '</a>' . $description;
          }
          function seeheader_render_built_items($items, $parent_id = '', $depth = 0, $theme = 'classic', $seen = [])
          {
            if ($depth > 4) return '';
            $children = seeheader_children($items, $parent_id);
            if (!$children) return '';
            $html = '';
            foreach ($children as $item) {
              $id = $item['id'] ?? '';
              if (!$id || in_array($id, $seen, true)) continue;
              $next_seen = array_merge($seen, [$id]);
              $sub = seeheader_children($items, $id);
              $mega = !empty($item['mega']) && $sub;
              $classes = 'sh-built-item' . ($sub ? ' sh-has-submenu' : '') . ($mega ? ' sh-has-mega' : '');
              $html .= '<li class="' . esc_attr($classes) . '">' . seeheader_render_nav_link($item, (bool)$sub);
              if ($mega) {
                if ($theme === 'digikala') $html .= seeheader_render_digikala_panel($items, $item, $sub, $theme, $next_seen);
                else $html .= seeheader_render_columns_panel($items, $item, $sub, $theme, $next_seen);
              } elseif ($sub) $html .= '<ul class="sub-menu">' . seeheader_render_built_items($items, $id, $depth + 1, $theme, $next_seen) . '</ul>';
              $html .= '</li>';
            }
            return $html;
          }
          function seeheader_render_columns_panel($items, $root, $groups, $theme, $seen)
          {
            $columns = [];
            foreach ($groups as $group) {
              $column = min(4, max(1, absint($group['column'] ?? 1)));
              $columns[$column][] = $group;
            }
            ksort($columns);
            $html = '<div class="sh-mega-panel sh-mega-columns" data-layout="columns"><div class="sh-mega-grid" style="--sh-mega-cols:4">';
            for ($column = 1; $column <= 4; $column++) {
              if (empty($columns[$column])) {
                $html .= '<div class="sh-mega-spacer" aria-hidden="true"></div>';
                continue;
              }
              foreach ($columns[$column] as $group) {
                $id = $group['id'];
                $kids = seeheader_children($items, $id);
                $html .= '<section class="sh-mega-column">' . seeheader_render_nav_link($group, (bool)$kids);
                if ($kids) $html .= '<ul>' . seeheader_render_built_items($items, $id, 2, $theme, array_merge($seen, [$id])) . '</ul>';
                $html .= '</section>';
              }
            }
            return $html . '</div>' . seeheader_panel_promo($items, $root['id']) . '</div>';
          }
          function seeheader_render_digikala_panel($items, $root, $groups, $theme, $seen)
          {
            $html = '<div class="sh-mega-panel sh-mega-catalog" data-layout="catalog"><div class="sh-mega-categories" role="tablist" aria-label="دسته‌بندی‌ها">';
            foreach ($groups as $i => $group) $html .= '<button type="button" role="tab" aria-selected="' . ($i === 0 ? 'true' : 'false') . '" class="sh-mega-category' . ($i === 0 ? ' is-active' : '') . '" data-mega-tab="' . esc_attr($group['id']) . '">' . (!empty($group['icon']) ? '<span class="sh-menu-icon">' . seeheader_icon($group['icon']) . '</span>' : '') . '<span>' . esc_html($group['label']) . '</span></button>';
            $html .= '</div><div class="sh-mega-content">';
            foreach ($groups as $i => $group) {
              $kids = seeheader_children($items, $group['id']);
              $html .= '<section class="sh-mega-category-panel' . ($i === 0 ? ' is-active' : '') . '" role="tabpanel" data-mega-panel="' . esc_attr($group['id']) . '">' . seeheader_render_nav_link($group, (bool)$kids) . '<div class="sh-mega-links"><ul>' . ($kids ? seeheader_render_built_items($items, $group['id'], 2, $theme, array_merge($seen, [$group['id']])) : '') . '</ul></div></section>';
            }
            return $html . '</div>' . seeheader_panel_promo($items, $root['id']) . '</div>';
          }
          function seeheader_panel_promo($items, $root_id)
          {
            $parents = [];
            foreach ($items as $item) if (!empty($item['id'])) $parents[$item['id']] = $item['parent'] ?? '';
            $cards = '';
            foreach ($items as $item) {
              if (empty($item['image']) || empty($item['id'])) continue;
              $cursor = $item['id'];
              $visited = [];
              while ($cursor && !isset($visited[$cursor])) {
                if ($cursor === $root_id) break;
                $visited[$cursor] = true;
                $cursor = $parents[$cursor] ?? '';
              }
              if ($cursor !== $root_id) continue;
              $url = $item['url'] ?? '#';
              $tag = $url && $url !== '#' ? 'a' : 'div';
              $target = $tag === 'a' && !empty($item['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
              $cards .= '<' . $tag . ' class="sh-feature-card"' . ($tag === 'a' ? ' href="' . esc_url($url) . '"' . $target : '') . '><img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['image_alt'] ?? '') . '" width="320" height="160" loading="lazy" decoding="async"><span class="sh-feature-copy"><strong>' . esc_html($item['label']) . '</strong>' . (!empty($item['description']) ? '<small>' . esc_html($item['description']) . '</small>' : '') . '</span></' . $tag . '>';
            }
            return $cards ? '<aside class="sh-mega-featured" aria-label="مطالب تصویری">' . $cards . '</aside>' : '';
          }

          /** Render an icon from the bundled SVG icon pack. */
          function seeheader_valid_icon_path($path)
          {
            static $checked = [];
            if (!is_string($path) || $path === '' || strpos($path, "\0") !== false || strpos($path, '\\') !== false || $path[0] === '/' || preg_match('~(^|/)\.{1,2}(/|$)~', $path)) return false;
            if (isset($checked[$path])) return $checked[$path];
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') return $checked[$path] = false;
            $base = realpath(SEEHEADER_DIR . 'assets/icons');
            $file = $base ? realpath($base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path)) : false;
            if (!$base || !$file || !is_file($file)) return $checked[$path] = false;
            $prefix = $base . DIRECTORY_SEPARATOR;
            $inside = DIRECTORY_SEPARATOR === '\\' ? strncasecmp($file, $prefix, strlen($prefix)) === 0 : strncmp($file, $prefix, strlen($prefix)) === 0;
            return $checked[$path] = $inside;
          }

          function seeheader_icon_url($path)
          {
            if (!seeheader_valid_icon_path($path)) return '';
            return SEEHEADER_URL . 'assets/icons/' . implode('/', array_map('rawurlencode', explode('/', $path)));
          }

          function seeheader_icon($path)
          {
            $path = ltrim(str_replace('\\', '/', (string) $path), '/');
            if (!seeheader_valid_icon_path($path)) return '';
            return '<span class="sh-action-icon" style="--sh-icon-src:url(&quot;' . esc_url(SEEHEADER_URL . 'assets/icons/' . implode('/', array_map('rawurlencode', explode('/', $path)))) . '&quot;)" aria-hidden="true"></span>';
          }

          /** Use the configured logo, then the active theme logo, then the site name. */
          function seeheader_logo($options)
          {
            $url = !empty($options['logo']) ? $options['logo'] : get_theme_mod('custom_logo');
            if (!$options['logo'] && $url) {
              $image = wp_get_attachment_image_src($url, 'full');
              $url = $image ? $image[0] : '';
            }
            $home = esc_url(home_url('/'));
            $label = esc_attr(get_bloginfo('name'));
            $content = $url
              ? '<img src="' . esc_url($url) . '" alt="' . $label . '" loading="eager">'
              : '<span class="sh-site-name">' . esc_html(get_bloginfo('name')) . '</span>';
            return '<a class="sh-logo" href="' . $home . '" rel="home" aria-label="' . $label . '">' . $content . '</a>';
          }

          /** Return the selected WordPress menu using the plugin's front-end classes. */
          function seeheader_menu_markup($menu_id)
          {
            $options = seeheader_options();
            $markup = wp_nav_menu([
              'menu' => absint($menu_id),
              'container' => false,
              'menu_class' => 'sh-menu',
              'echo' => false,
              'fallback_cb' => false,
              'depth' => 4,
              'seeheader_menu' => true,
              'seeheader_icon_map' => $options['menu_icons'],
            ]);
            return is_string($markup) ? $markup : '';
          }
          add_filter('nav_menu_item_title', function ($title, $menu_item, $args) {
            if (empty($args->seeheader_icon_map) || empty($menu_item->ID)) return $title;
            $path = $args->seeheader_icon_map[$menu_item->ID] ?? '';
            $icon = seeheader_icon($path);
            return $icon ? '<span class="sh-menu-icon">' . $icon . '</span>' . $title : $title;
          }, 10, 3);
          add_filter('nav_menu_link_attributes', function ($atts, $menu_item, $args) {
            if (empty($args->seeheader_menu) || empty($menu_item->classes) || !in_array('menu-item-has-children', $menu_item->classes, true)) return $atts;
            $atts['aria-haspopup'] = 'true';
            $atts['aria-expanded'] = 'false';
            return $atts;
          }, 10, 3);

          function seeheader_render($args = [])
          {
            $o = wp_parse_args($args, seeheader_options());
            $theme = in_array($o['theme'], ['classic', 'centered', 'split', 'glass', 'dark', 'minimal', 'editorial', 'commerce', 'floating', 'arvancloud', 'digikala'], true) ? $o['theme'] : 'classic';
            if (($o['palette_mode'] ?? 'theme') !== 'custom') $o = array_merge($o, seeheader_theme_palette($theme));
            $cls = 'sh-header sh-theme-' . $theme . (($o['palette_mode'] ?? 'theme') === 'custom' ? ' sh-custom-palette' : '') . (!empty($o['sticky']) ? ' sh-sticky' : '') . (!empty($o['shadow']) ? ' sh-shadow' : '') . (empty($o['desktop']) ? ' sh-hide-desktop' : '') . (empty($o['mobile']) ? ' sh-hide-mobile' : '');
            $id = 'sh-nav-' . wp_unique_id();
            $search_id = 'sh-search-' . wp_unique_id();
            $defaults = seeheader_defaults();
            foreach (['accent', 'surface', 'text', 'border'] as $color_key) $o[$color_key] = sanitize_hex_color($o[$color_key] ?? '') ?: $defaults[$color_key];
            $vars = sprintf('--sh-accent:%s;--sh-surface:%s;--sh-text:%s;--sh-border:%s;--sh-width:%dpx;--sh-logo-height:%dpx', $o['accent'], $o['surface'], $o['text'], $o['border'], min(1920, max(760, absint($o['width']))), min(100, max(24, absint($o['logo_height']))));
            foreach (['main_height' => ['--sh-main-height', 48, 140], 'search_width' => ['--sh-search-width', 120, 700], 'nav_gap' => ['--sh-nav-gap', 0, 60], 'control_radius' => ['--sh-control-radius', 0, 40]] as $key => $rule) if (isset($o[$key]) && $o[$key] !== '') $vars .= ';' . $rule[0] . ':' . min($rule[2], max($rule[1], absint($o[$key]))) . 'px';
            foreach (['menu_text' => '--sh-menu-text', 'menu_hover' => '--sh-menu-hover', 'icon_color' => '--sh-icon-color', 'icon_hover' => '--sh-icon-hover', 'button_bg' => '--sh-button-bg', 'button_text' => '--sh-button-text', 'button_hover_bg' => '--sh-button-hover-bg', 'button_hover_text' => '--sh-button-hover-text', 'button_border' => '--sh-button-border'] as $key => $variable) if (!empty($o[$key]) && sanitize_hex_color($o[$key])) $vars .= ';' . $variable . ':' . $o[$key];
            $vars .= ';--sh-button-padding:' . min(32, max(0, absint($o['button_padding'] ?? 11))) . 'px;--sh-button-radius:' . min(48, max(0, absint($o['button_radius'] ?? 10))) . 'px';
            foreach (['down' => 'menu_arrow_icon', 'left' => 'submenu_arrow_icon', 'right' => 'back_arrow_icon', 'topbar' => 'topbar_arrow_icon'] as $direction => $key) {
              $arrow = seeheader_icon_url($o[$key] ?? $defaults[$key]);
              if ($arrow) $vars .= ';--sh-arrow-' . $direction . ':url("' . $arrow . '")';
            }
            if (in_array($o['nav_alignment'] ?? '', ['flex-start', 'center', 'flex-end'], true)) $vars .= ';--sh-nav-align:' . $o['nav_alignment'];
            $menu_markup = '';
            if (($o['menu_source'] ?? 'builder') === 'builder' && !empty($o['nav_items'])) $menu_markup = '<ul class="sh-menu sh-built-menu-list">' . seeheader_render_built_items($o['nav_items'], '', 0, $theme) . '</ul>';
            elseif (($o['menu_source'] ?? 'builder') === 'wordpress' && !empty($o['menu'])) $menu_markup = seeheader_menu_markup($o['menu']);
            if (!$menu_markup || strpos($menu_markup, '<li') === false) $menu_markup = '<ul class="sh-menu"><li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('صفحه اصلی', 'seeheader') . '</a></li></ul>';
            ob_start(); ?>
  <header class="<?php echo esc_attr($cls . ' sh-button-' . (in_array($o['button_style'] ?? '', ['solid', 'outline', 'ghost'], true) ? $o['button_style'] : 'solid')); ?>" style="<?php echo esc_attr($vars); ?>" dir="rtl">
    <?php if (!empty($o['topbar']) && !empty($o['topbar_text'])): ?><div class="sh-topbar"><?php if (!empty($o['topbar_url'])): ?><a href="<?php echo esc_url($o['topbar_url']); ?>"><?php echo esc_html($o['topbar_text']); ?><span class="sh-topbar-arrow" aria-hidden="true"></span></a><?php else: ?><span><?php echo esc_html($o['topbar_text']); ?></span><?php endif; ?></div><?php endif; ?>
    <div class="sh-main"><button class="sh-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($id); ?>" aria-label="باز کردن منو"><span></span><span></span></button>
      <?php echo seeheader_logo($o); ?><div class="sh-tools"><?php if (!empty($o['account'])): $account = $o['account_url'] ?: (is_user_logged_in() ? admin_url('profile.php') : wp_login_url()); ?><a class="sh-account" href="<?php echo esc_url($account); ?>" aria-label="<?php echo esc_attr(is_user_logged_in() ? 'حساب کاربری' : ($o['account_label'] ?: 'ورود')); ?>"><?php echo seeheader_icon($o['account_icon'] ?? 'Users/user.svg'); ?><span class="sh-tool-label"><?php echo is_user_logged_in() ? 'حساب کاربری' : esc_html($o['account_label'] ?: 'ورود'); ?></span></a><?php endif; ?>
        <?php if (!empty($o['search'])): ?><form class="sh-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>"><label class="screen-reader-text" for="<?php echo esc_attr($search_id); ?>">جستجو</label><input id="<?php echo esc_attr($search_id); ?>" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="جستجو..."><button type="submit" aria-label="جستجو"><span class="sh-search-icon"></span></button></form><?php endif; ?></div>
    </div>
    <nav class="sh-nav" id="<?php echo esc_attr($id); ?>" aria-label="فهرست اصلی">
      <div class="sh-nav-inner"><?php echo $menu_markup; ?>
        <?php foreach ((array)$o['items'] as $item): ?><a class="sh-custom-link" href="<?php echo esc_url($item['url']); ?>" <?php echo !empty($item['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo seeheader_icon($item['icon']); ?><span><?php echo esc_html($item['label']); ?></span></a><?php endforeach; ?></div>
    </nav>
  </header><?php return ob_get_clean();
          }
          add_shortcode('seeheader', 'seeheader_render');
          add_action('wp_body_open', function () {
            $o = seeheader_options();
            if ($o['enabled']) echo seeheader_render();
          }, 5);
          add_action('elementor/widgets/register', function ($manager) {
                                                                                                      if (!class_exists('Elementor\\Widget_Base') || !class_exists('Elementor\\Controls_Manager') || !is_object($manager) || !method_exists($manager, 'register')) return;
                                                                                                      if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.20.0', '<')) return;
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
                $this->add_control('theme', ['label' => 'پوسته', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => ['settings' => 'استفاده از تنظیمات افزونه', 'classic' => 'ReadyStudio', 'centered' => 'لوگوی وسط', 'split' => 'دو بخشی', 'glass' => 'شیشه‌ای', 'dark' => 'تیره', 'minimal' => 'مینیمال', 'editorial' => 'مجله‌ای', 'commerce' => 'فروشگاهی', 'floating' => 'شناور', 'arvancloud' => 'آروان‌کلاد', 'digikala' => 'دیجی‌کالا']]);
                $this->add_control('logo', ['label' => 'تصویر لوگو', 'type' => \Elementor\Controls_Manager::MEDIA]);
                $inherit = ['settings' => 'تنظیمات افزونه', 'yes' => 'نمایش', 'no' => 'عدم نمایش'];
                $this->add_control('search', ['label' => 'جستجو', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('account', ['label' => 'حساب کاربری', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('sticky', ['label' => 'هدر چسبان', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('shadow', ['label' => 'سایهٔ هدر', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('topbar', ['label' => 'نوار اعلان', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('desktop', ['label' => 'نمایش در دسکتاپ', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('mobile', ['label' => 'نمایش در موبایل', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $inherit]);
                $this->add_control('menu_source', ['label' => 'منبع منو', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => ['settings' => 'تنظیمات افزونه', 'builder' => 'منوی ساخته‌شده در افزونه', 'wordpress' => 'منوی وردپرس']]);
                $menu_options = [0 => 'منوی پیش‌فرض افزونه'];
                                                                                                          $menus = wp_get_nav_menus();
                                                                                                          if (is_array($menus)) foreach ($menus as $menu) $menu_options[$menu->term_id] = $menu->name;
                $this->add_control('menu', ['label' => 'منوی وردپرس', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 0, 'options' => $menu_options]);
                $this->add_control('account_label', ['label' => 'متن حساب کاربری', 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => 'تنظیمات افزونه']);
                $this->add_control('account_url', ['label' => 'پیوند حساب کاربری', 'type' => \Elementor\Controls_Manager::URL]);
                $icon_options = ['settings' => 'تنظیمات افزونه'];
                foreach (seeheader_icon_catalog() as $icon) $icon_options[$icon['path']] = $icon['category'] . ' / ' . $icon['name'];
                $this->add_control('account_icon', ['label' => 'آیکن حساب کاربری', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'settings', 'options' => $icon_options]);
                $this->add_control('topbar_text', ['label' => 'متن نوار اعلان', 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => 'تنظیمات افزونه']);
                $this->add_control('topbar_url', ['label' => 'پیوند نوار اعلان', 'type' => \Elementor\Controls_Manager::URL]);
                $this->end_controls_section();
                $this->start_controls_section('style', ['label' => 'رنگ و اندازه', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
                foreach (['accent' => 'رنگ تأکیدی', 'surface' => 'پس‌زمینه', 'text' => 'رنگ متن', 'border' => 'رنگ خط'] as $key => $label) $this->add_control($key, ['label' => $label, 'type' => \Elementor\Controls_Manager::COLOR]);
                foreach (['menu_text' => 'رنگ متن منو', 'menu_hover' => 'هاور متن منو', 'icon_color' => 'رنگ آیکن‌ها', 'icon_hover' => 'هاور آیکن‌ها', 'button_bg' => 'پس‌زمینه دکمه', 'button_text' => 'متن دکمه', 'button_hover_bg' => 'پس‌زمینه هاور دکمه', 'button_hover_text' => 'متن هاور دکمه', 'button_border' => 'حاشیه دکمه'] as $key => $label) $this->add_control($key, ['label' => $label, 'type' => \Elementor\Controls_Manager::COLOR]);
                $this->add_control('button_style', ['label' => 'نوع دکمه', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => ['' => 'تنظیمات افزونه', 'solid' => 'توپر', 'outline' => 'دورخط', 'ghost' => 'شفاف']]);
                $this->add_control('button_radius', ['label' => 'گردی دکمه', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 48]);
                $this->add_control('button_padding', ['label' => 'فاصله افقی دکمه', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 32]);
                $this->add_control('width', ['label' => 'حداکثر عرض هدر', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 760, 'max' => 1920]);
                $this->add_control('logo_height', ['label' => 'ارتفاع لوگو', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 24, 'max' => 100]);
                $this->add_control('main_height', ['label' => 'ارتفاع ردیف اصلی', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 48, 'max' => 140]);
                $this->add_control('search_width', ['label' => 'عرض جستجو', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 120, 'max' => 700]);
                $this->add_control('nav_gap', ['label' => 'فاصله آیتم‌های منو', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 60]);
                $this->add_control('control_radius', ['label' => 'گردی گوشه ابزارها', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 40]);
                $this->add_control('nav_alignment', ['label' => 'چیدمان منو', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => ['' => 'تنظیمات افزونه', 'flex-start' => 'راست', 'center' => 'وسط', 'flex-end' => 'چپ']]);
                $this->end_controls_section();
              }
              protected function render()
              {
                $s = $this->get_settings_for_display();
                $o = seeheader_options();
                if (!empty($s['theme']) && $s['theme'] !== 'settings') $o['theme'] = sanitize_key($s['theme']);
                if (!empty($s['logo']['url'])) $o['logo'] = $s['logo']['url'];
                foreach (['search', 'account', 'sticky', 'shadow', 'topbar', 'desktop', 'mobile'] as $key) if (isset($s[$key]) && $s[$key] !== 'settings') $o[$key] = $s[$key] === 'yes' ? 1 : 0;
                if (!empty($s['menu_source']) && $s['menu_source'] !== 'settings') $o['menu_source'] = in_array($s['menu_source'], ['builder', 'wordpress'], true) ? $s['menu_source'] : $o['menu_source'];
                if (!empty($s['menu'])) $o['menu'] = absint($s['menu']);
                if (!empty($s['account_label'])) $o['account_label'] = sanitize_text_field($s['account_label']);
                if (!empty($s['account_url']['url'])) $o['account_url'] = esc_url_raw($s['account_url']['url']);
                if (!empty($s['account_icon']) && $s['account_icon'] !== 'settings' && in_array($s['account_icon'], array_column(seeheader_icon_catalog(), 'path'), true)) $o['account_icon'] = $s['account_icon'];
                if (!empty($s['topbar_text'])) $o['topbar_text'] = sanitize_text_field($s['topbar_text']);
                if (!empty($s['topbar_url']['url'])) $o['topbar_url'] = esc_url_raw($s['topbar_url']['url']);
                foreach (['accent', 'surface', 'text', 'border'] as $key) if (!empty($s[$key]) && sanitize_hex_color($s[$key])) {
                  $o[$key] = sanitize_hex_color($s[$key]);
                  $o['palette_mode'] = 'custom';
                }
                foreach (['menu_text', 'menu_hover', 'icon_color', 'icon_hover', 'button_bg', 'button_text', 'button_hover_bg', 'button_hover_text', 'button_border'] as $key) if (!empty($s[$key]) && sanitize_hex_color($s[$key])) $o[$key] = sanitize_hex_color($s[$key]);
                if (!empty($s['button_style']) && in_array($s['button_style'], ['solid', 'outline', 'ghost'], true)) $o['button_style'] = $s['button_style'];
                foreach (['button_radius' => 48, 'button_padding' => 32] as $key => $maximum) if (isset($s[$key]) && $s[$key] !== '') $o[$key] = min($maximum, max(0, absint($s[$key])));
                if (!empty($s['width'])) $o['width'] = min(1920, max(760, absint($s['width'])));
                if (!empty($s['logo_height'])) $o['logo_height'] = min(100, max(24, absint($s['logo_height'])));
                foreach (['main_height' => [48, 140], 'search_width' => [120, 700], 'nav_gap' => [0, 60], 'control_radius' => [0, 40]] as $key => $range) if (isset($s[$key]) && $s[$key] !== '') $o[$key] = min($range[1], max($range[0], absint($s[$key])));
                if (!empty($s['nav_alignment']) && in_array($s['nav_alignment'], ['flex-start', 'center', 'flex-end'], true)) $o['nav_alignment'] = $s['nav_alignment'];
                echo seeheader_render($o);
              }
            });
          });
