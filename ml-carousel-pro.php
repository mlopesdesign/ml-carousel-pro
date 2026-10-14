<?php
/**
 * Plugin Name:       ML Banner Pro
 * Plugin URI:        https://mlopesdesign.com.br
 * Description:       Carrossel profissional com grupos, múltiplos shortcodes, ordenação administrativa, autoplay, analytics e integração segura com templates como Nicepage.
 * Version:           1.12.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * Author:            Marcio Lopes
 * Author URI:        https://mlopesdesign.com.br
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ml-carousel-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('mlcp_consolidate_active_installation')) {
    function mlcp_consolidate_active_installation() {
        $current = plugin_basename(__FILE__);
        $active  = (array) get_option('active_plugins', array());

        foreach ($active as $plugin) {
            if ($plugin === $current) {
                continue;
            }
            if (basename($plugin) === 'ml-carousel-pro.php') {
                deactivate_plugins($plugin, true);
            }
        }
    }
}

register_activation_hook(__FILE__, 'mlcp_consolidate_active_installation');

// Guard against redeclaration conflicts with other ML plugins
if ( defined( 'MLCP_VERSION' ) ) {
    return; // Another ML plugin with same prefix is already loaded — abort silently
}

define('MLCP_VERSION',       '1.12.5');
define('MLCP_BASENAME',      plugin_basename( __FILE__ ) );
define('MLCP_PLUGIN_DIR',    plugin_dir_path(__FILE__));
define('MLCP_PLUGIN_URL',    plugin_dir_url(__FILE__));
define('MLCP_TEXT_DOMAIN',   'ml-carousel-pro');
define('MLCP_POST_TYPE',     'mlcp_item');
define('MLCP_GROUP_TAX',     'mlcp_group');
define('MLCP_MENU_SLUG',     'mlcp-dashboard');
define('MLCP_EXPIRATION_HOOK', 'mlcp_expire_item');

require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-analytics-service.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-helpers.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-install.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-post-types.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-taxonomies.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-assets.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-admin.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-meta-boxes.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-shortcodes.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-license-manager.php';
require_once MLCP_PLUGIN_DIR . 'includes/class-mlcp-github-updater.php';

register_activation_hook(__FILE__, array('MLCP_Install', 'activate'));

final class ML_Carousel_Pro {
    private $settings;

    public function __construct() {
        $this->settings = MLCP_Helpers::get_settings();

        add_action('init', array('MLCP_Post_Types', 'register'));
        add_action('init', array('MLCP_Taxonomies', 'register'));
        add_action('admin_menu', array('MLCP_Admin', 'register_menu'));
        add_action('admin_enqueue_scripts', array('MLCP_Assets', 'enqueue_admin'));
        add_action('wp_enqueue_scripts', array('MLCP_Assets', 'enqueue_front'));
        add_action('add_meta_boxes', array('MLCP_Meta_Boxes', 'register'));
        add_action('save_post_' . MLCP_POST_TYPE, array('MLCP_Meta_Boxes', 'save'), 10, 2);
        add_action(MLCP_EXPIRATION_HOOK, array('MLCP_Meta_Boxes', 'expire_item'));
        add_action('before_delete_post', array('MLCP_Meta_Boxes', 'clear_expiration_schedule'));
        add_action('trashed_post', array('MLCP_Meta_Boxes', 'clear_expiration_schedule'));
        add_action('admin_init', array('MLCP_Meta_Boxes', 'migrate_expired_drafts_to_trash'));
        add_filter('manage_edit-' . MLCP_POST_TYPE . '_columns', array('MLCP_Admin', 'item_columns'));
        add_action('manage_' . MLCP_POST_TYPE . '_posts_custom_column', array('MLCP_Admin', 'render_item_columns'), 10, 2);
        add_filter('views_edit-' . MLCP_POST_TYPE, array('MLCP_Admin', 'always_show_trash_view'));
        add_filter('post_row_actions', array('MLCP_Admin', 'duplicate_row_action'), 10, 2);
        add_action('pre_get_posts', array('MLCP_Admin', 'handle_admin_query'));
        add_filter('manage_edit-' . MLCP_GROUP_TAX . '_columns', array('MLCP_Admin', 'group_columns'));
        add_filter('manage_' . MLCP_GROUP_TAX . '_custom_column', array('MLCP_Admin', 'render_group_columns'), 10, 3);

        add_action(MLCP_GROUP_TAX . '_add_form_fields', array('MLCP_Taxonomies', 'add_group_fields'));
        add_action(MLCP_GROUP_TAX . '_edit_form_fields', array('MLCP_Taxonomies', 'edit_group_fields'));
        add_action('created_' . MLCP_GROUP_TAX, array('MLCP_Taxonomies', 'save_group_fields'));
        add_action('edited_' . MLCP_GROUP_TAX, array('MLCP_Taxonomies', 'save_group_fields'));

        add_action('admin_post_mlcp_save_settings', array('MLCP_Admin', 'save_settings'));
        add_action('admin_post_mlcp_duplicate_item', array('MLCP_Admin', 'handle_duplicate_item'));
        add_action('admin_post_mlcp_move_item', array('MLCP_Admin', 'handle_move_item'));
        add_action('wp_ajax_mlcp_load_sort_group', array('MLCP_Admin', 'ajax_load_sort_group'));
        add_action('wp_ajax_mlcp_save_sort_group', array('MLCP_Admin', 'ajax_save_sort_group'));
        add_action('wp_ajax_mlcp_save_items_order', array('MLCP_Admin', 'ajax_save_items_order'));
        add_action('all_admin_notices', array('MLCP_Admin', 'render_shared_admin_header'));
        add_action('admin_post_mlcp_activate_license', array('MLCP_Admin', 'handle_activate_license'));
        add_action('admin_post_mlcp_sync_license', array('MLCP_Admin', 'handle_sync_license'));
        add_action('admin_post_mlcp_remove_license', array('MLCP_Admin', 'handle_remove_license'));
        add_action('admin_post_mlcp_reset_analytics', array('MLCP_Admin', 'handle_reset_analytics'));
        add_action('wp_ajax_mlcp_track_event', array('MLCP_Admin', 'ajax_track_event'));
        add_action('wp_ajax_nopriv_mlcp_track_event', array('MLCP_Admin', 'ajax_track_event'));
        add_action('init', array('MLCP_Analytics_Service', 'maybe_migrate_legacy'));

        add_shortcode('ml_carousel', array('MLCP_Shortcodes', 'render'));

        // GitHub Updater — single update engine only.
        // Register immediately during plugin bootstrap so WordPress update checks
        // on Plugins/Updates screens do not run before our filters exist.
        if ( is_admin() && class_exists( 'MLCP_GitHub_Updater' ) ) {
            MLCP_GitHub_Updater::init();
        }
    }
}

new ML_Carousel_Pro();
