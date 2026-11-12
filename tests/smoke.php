<?php
/** Minimal WordPress stubs for checking the plugin without a database. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message) {
    throw new ErrorException($message, 0, $severity);
});

define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = [];
$GLOBALS['filters'] = [];
$GLOBALS['styles'] = [];
$GLOBALS['scripts'] = [];
$GLOBALS['localized'] = [];
$GLOBALS['inline_styles'] = [];
$GLOBALS['saved_options'] = [
    'enabled' => 1,
    'menu' => 4,
    'menu_source' => 'builder',
    'mobile_breakpoint' => 900,
    'nav_items' => [
        ['id' => 'products', 'label' => 'محصولات', 'url' => '#', 'parent' => '', 'mega' => 1, 'column' => 1],
        ['id' => 'group', 'label' => 'گروه', 'url' => '#', 'parent' => 'products', 'column' => 1],
        ['id' => 'leaf', 'label' => 'زیرگروه', 'url' => '/leaf', 'parent' => 'group', 'column' => 1],
        ['id' => 'empty', 'label' => 'گروه بدون فرزند', 'url' => '/empty', 'parent' => 'products', 'column' => 2],
    ],
    'items' => [['label' => 'تماس', 'url' => '/contact', 'icon' => 'Call/phone.svg']],
];

function plugin_dir_path($path) { return dirname($path) . '/'; }
function plugin_dir_url($path) { return 'https://example.test/wp-content/plugins/seeheader/'; }
function add_action($name, $callback, $priority = 10) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, $priority = 10, $accepted = 1) { $GLOBALS['filters'][$name][] = $callback; }
function add_shortcode($name, $callback) { return true; }
function get_option($name, $default = []) { return $GLOBALS['saved_options']; }
function update_option($name, $value) { $GLOBALS['saved_options'] = $value; return true; }
function register_rest_route($namespace, $route, $args) { $GLOBALS['rest_routes'][$namespace . $route] = $args; }
function rest_ensure_response($value) { return $value; }
class WP_Error { public function __construct($code, $message, $data = []) {} }
function wp_parse_args($args, $defaults) { return array_merge($defaults, $args); }
function absint($value) { return abs((int) $value); }
function wp_generate_password($length = 6) { return str_repeat('a', $length); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_hex_color($value) { return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : null; }
function esc_url_raw($value) { return filter_var((string) $value, FILTER_SANITIZE_URL); }
function esc_url($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html__($value, $domain) { return esc_html($value); }
function checked($value, $expected = 1, $echo = true) { $result = $value == $expected ? 'checked="checked"' : ''; if ($echo) echo $result; return $result; }
function selected($value, $expected, $echo = true) { $result = $value == $expected ? 'selected="selected"' : ''; if ($echo) echo $result; return $result; }
function current_user_can($capability) { return true; }
function is_wp_error($value) { return false; }
function wp_get_nav_menus() { return [(object) ['term_id' => 4, 'name' => 'منوی اصلی']]; }
function wp_get_nav_menu_items($id) { return [(object) ['ID' => 42, 'title' => 'صفحه اصلی']]; }
function settings_errors($setting) { return; }
function settings_fields($group) { echo '<input type="hidden" name="option_page" value="' . esc_attr($group) . '">'; }
function submit_button($text) { echo '<button type="submit">' . esc_html($text) . '</button>'; }
function add_menu_page() { return 'toplevel_page_seeheader'; }
function register_setting() { return true; }
function wp_enqueue_media() { return true; }
function wp_enqueue_style($handle) { $GLOBALS['styles'][] = $handle; }
function wp_enqueue_script($handle) { $GLOBALS['scripts'][] = $handle; }
function wp_localize_script($handle, $name, $data) { $GLOBALS['localized'][$name] = $data; }
function wp_add_inline_style($handle, $css) { $GLOBALS['inline_styles'][] = $css; }
function get_theme_mod($key) { return 0; }
function wp_get_attachment_image_src() { return false; }
function home_url($path = '/') { return 'https://example.test' . $path; }
function get_bloginfo($key) { return 'نمونه'; }
function is_user_logged_in() { return false; }
function admin_url($path) { return 'https://example.test/wp-admin/' . $path; }
function wp_login_url() { return 'https://example.test/wp-login.php'; }
function get_search_query() { return ''; }
function wp_unique_id() { static $id = 0; return ++$id; }
function wp_nav_menu($args) { return '<ul class="sh-menu"><li><a href="/">منوی وردپرس</a></li></ul>'; }

require dirname(__DIR__) . '/seeheader/seeheader.php';

function ensure($condition, $message) { if (!$condition) throw new RuntimeException($message); }

$GLOBALS['hooks']['admin_menu'][0]();
$GLOBALS['hooks']['admin_enqueue_scripts'][0]('toplevel_page_seeheader');
ensure(in_array('seeheader-admin', $GLOBALS['scripts'], true), 'Admin script was not enqueued');
ensure(in_array('seeheader-admin', $GLOBALS['styles'], true), 'Admin style was not enqueued');
ensure(count($GLOBALS['localized']['SeeHeaderIconPack']['palettes']) === 11, 'Theme palettes were not localized');

ob_start();
seeheader_settings_page();
$admin = ob_get_clean();
foreach (['sh-themes', 'sh-layout', 'sh-actions', 'sh-topbar', 'sh-responsive', 'sh-nav-item-template', 'sh-action-template'] as $id) {
    ensure(strpos($admin, 'id="' . $id . '"') !== false, 'Missing admin section ' . $id);
}
ensure(substr_count($admin, '<form') === substr_count($admin, '</form>'), 'Admin form is unbalanced');
ensure(strpos($admin, 'data-sh-icon-control') !== false, 'Icon picker fields are missing');

$GLOBALS['hooks']['wp_enqueue_scripts'][0]();
ensure(strpos(implode('', $GLOBALS['inline_styles']), 'max-width:900px') !== false, 'Mobile breakpoint was not applied');
ensure(strpos(implode('', $GLOBALS['inline_styles']), 'min-width:901px') !== false, 'Desktop breakpoint was not applied');

$front = seeheader_render();
ensure(substr_count($front, '<section') === substr_count($front, '</section>'), 'Mega menu sections are unbalanced');
ensure(strpos($front, 'گروه بدون فرزند') !== false, 'Mega menu group is missing');

$clean = seeheader_sanitize([
    'logo' => ['invalid'],
    'accent' => ['invalid'],
    'nav_items' => [['id' => 'same', 'label' => 'A'], ['id' => 'same', 'label' => 'B']],
    'control_radius' => '0',
    'nav_gap' => '0',
    'menu_hover' => '#123456',
    'button_style' => 'outline',
    'button_radius' => 18,
]);
ensure($clean['nav_items'][0]['id'] !== $clean['nav_items'][1]['id'], 'Duplicate IDs were saved');
ensure($clean['control_radius'] === 0 && $clean['nav_gap'] === 0, 'Zero layout values were not saved');
ensure($clean['logo'] === '', 'Malformed logo was accepted');
ensure($clean['menu_hover'] === '#123456' && $clean['button_style'] === 'outline' && $clean['button_radius'] === 18, 'Menu or button styling was not saved');
ensure(strpos(seeheader_render($clean), 'sh-button-outline') !== false, 'Button style was not rendered');

$rich = seeheader_sanitize(['nav_items' => [
    ['id' => 'resources', 'label' => 'Resources', 'url' => '/resources', 'mega' => 1],
    ['id' => 'guide', 'parent' => 'resources', 'label' => 'Guide', 'url' => '/guide', 'image' => 'https://example.test/guide.jpg', 'image_alt' => 'Guide artwork', 'badge' => 'New', 'badge_icon' => 'Essentional/crown-star.svg', 'badge_text_color' => '#006f73', 'badge_bg_color' => '#dff7f7'],
]]);
ensure(count($rich['nav_items']) === 2 && $rich['nav_items'][1]['parent'] === 'resources', 'Rich menu changed the item structure');
$rich_html = seeheader_render($rich);
ensure(strpos($rich_html, 'sh-feature-card') !== false && strpos($rich_html, 'Guide artwork') !== false, 'Image card was not rendered');
ensure(strpos($rich_html, '--sh-badge-text:#006f73') !== false && strpos($rich_html, '--sh-badge-bg:#dff7f7') !== false, 'Badge colors were not rendered');
ensure(strpos($rich_html, 'crown-star.svg') !== false, 'Badge icon was not rendered');

$title = $GLOBALS['filters']['nav_menu_item_title'][0]('منو', (object) ['ID' => 42], (object) ['seeheader_icon_map' => [42 => 'Users/user.svg']]);
ensure(strpos($title, 'sh-action-icon') !== false, 'WordPress menu icon was not rendered');

$GLOBALS['hooks']['rest_api_init'][0]();
ensure(isset($GLOBALS['rest_routes']['seeheader/v1/config']), 'MCP config REST route was not registered');
ensure(isset($GLOBALS['rest_routes']['seeheader/v1/icons']), 'MCP icons REST route was not registered');
$request = new class { public function get_json_params() { return ['button_style' => 'outline', 'button_radius' => 18, 'menu_hover' => '#123456']; } };
$updated = $GLOBALS['rest_routes']['seeheader/v1/config'][1]['callback']($request);
ensure($updated['button_style'] === 'outline' && $updated['button_radius'] === 18 && $updated['menu_hover'] === '#123456', 'MCP config update failed');
ensure(count($updated['nav_items']) === 4, 'MCP partial update lost the existing menu');

echo "Admin, sanitization, responsive CSS, mega menu and WordPress icons: OK\n";
