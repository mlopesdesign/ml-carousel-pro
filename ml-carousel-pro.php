<?php
/**
 * Plugin Name:       ML Banner Pro
 * Plugin URI:        https://mlopesdesign.com.br
 * Description:       Carrossel profissional com grupos, múltiplos shortcodes, ordenação administrativa, autoplay, analytics e integração segura com templates como Nicepage.
 * Version:           1.10.19
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

define('MLCP_VERSION', '1.10.19');
define('MLCP_PLUGIN_FILE', __FILE__);
define('MLCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MLCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MLCP_TEXT_DOMAIN', 'ml-carousel-pro');
define('MLCP_POST_TYPE', 'mlcp_item');
define('MLCP_GROUP_TAX', 'mlcp_group');
define('MLCP_MENU_SLUG', 'mlcp-dashboard');
define('MLCP_EXPIRATION_HOOK', 'mlcp_expire_item');

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
        add_filter('manage_edit-' . MLCP_POST_TYPE . '_columns', array('MLCP_Admin', 'item_columns'));
        add_action('manage_' . MLCP_POST_TYPE . '_posts_custom_column', array('MLCP_Admin', 'render_item_columns'), 10, 2);
        add_filter('manage_edit-' . MLCP_POST_TYPE . '_sortable_columns', array('MLCP_Admin', 'sortable_item_columns'));
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
        add_action('wp_ajax_mlcp_load_sort_group', array('MLCP_Admin', 'ajax_load_sort_group'));
        add_action('wp_ajax_mlcp_save_sort_group', array('MLCP_Admin', 'ajax_save_sort_group'));
        add_action('all_admin_notices', array('MLCP_Admin', 'render_shared_admin_header'));
        add_action('admin_post_mlcp_activate_license', array('MLCP_Admin', 'handle_activate_license'));
        add_action('admin_post_mlcp_sync_license', array('MLCP_Admin', 'handle_sync_license'));
        add_action('admin_post_mlcp_remove_license', array('MLCP_Admin', 'handle_remove_license'));
        add_action('admin_post_mlcp_reset_analytics', array('MLCP_Admin', 'handle_reset_analytics'));
        add_action('wp_ajax_mlcp_track_event', array('MLCP_Admin', 'ajax_track_event'));
        add_action('wp_ajax_nopriv_mlcp_track_event', array('MLCP_Admin', 'ajax_track_event'));
        add_action('init', array('MLCP_Helpers', 'migrate_legacy_analytics'));

        add_shortcode('ml_carousel', array('MLCP_Shortcodes', 'render'));
        add_action('admin_init', array('MLCP_GitHub_Updater', 'init'));
    }
}

new ML_Carousel_Pro();
