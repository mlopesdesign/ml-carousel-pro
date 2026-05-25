<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Taxonomies {
    public static function register() {
        register_taxonomy(MLCP_GROUP_TAX, array(MLCP_POST_TYPE), array(
            'labels' => array(
                'name' => __('Grupos', MLCP_TEXT_DOMAIN),
                'singular_name' => __('Grupo', MLCP_TEXT_DOMAIN),
                'search_items' => __('Buscar grupos', MLCP_TEXT_DOMAIN),
                'all_items' => __('Todos os grupos', MLCP_TEXT_DOMAIN),
                'edit_item' => __('Editar grupo', MLCP_TEXT_DOMAIN),
                'update_item' => __('Atualizar grupo', MLCP_TEXT_DOMAIN),
                'add_new_item' => __('Adicionar novo grupo', MLCP_TEXT_DOMAIN),
                'new_item_name' => __('Nome do novo grupo', MLCP_TEXT_DOMAIN),
                'menu_name' => __('Grupos', MLCP_TEXT_DOMAIN),
            ),
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => false,
            'show_in_menu' => false,
            'show_in_rest' => false,
            'hierarchical' => true,
            'rewrite' => false,
            'meta_box_cb' => false,
        ));
    }

    public static function add_group_fields() {
        $settings = MLCP_Helpers::get_group_defaults();
        include MLCP_PLUGIN_DIR . 'admin/views/group-fields-add.php';
    }

    public static function edit_group_fields($term) {
        $settings = MLCP_Helpers::get_group_settings($term->term_id);
        include MLCP_PLUGIN_DIR . 'admin/views/group-fields-edit.php';
    }

    public static function save_group_fields($term_id) {
        if (!current_user_can('manage_categories')) {
            return;
        }

        $fields = array('width_px', 'height_px', 'lock_proportion', 'rounded_corners', 'desktop', 'tablet', 'mobile', 'gap', 'autoplay', 'autoplay_speed', 'arrows', 'overlay_enabled', 'overlay_opacity', 'show_title', 'show_subtitle', 'image_fit', 'card_bg_color', 'card_margin');
        foreach ($fields as $field) {
            $value = isset($_POST['mlcp_' . $field]) ? wp_unslash($_POST['mlcp_' . $field]) : '';
            switch ($field) {
                case 'width_px':
                case 'height_px':
                case 'desktop':
                case 'tablet':
                case 'mobile':
                case 'gap':
                case 'autoplay_speed':
                    $value = max(0, (int) $value);
                    break;
                case 'lock_proportion':
                case 'rounded_corners':
                case 'autoplay':
                case 'arrows':
                case 'show_title':
                case 'show_subtitle':
                case 'overlay_enabled':
                    $value = !empty($value) ? 1 : 0;
                    break;
                case 'overlay_opacity':
                    $value = max(0, min(100, (int) $value));
                    break;
                case 'image_fit':
                    $value = in_array($value, array('cover', 'contain'), true) ? $value : 'cover';
                    break;
                case 'card_bg_color':
                    $value = sanitize_hex_color($value) ?? '';
                    break;
                case 'card_margin':
                    $value = max(0, min(200, (int) $value));
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            update_term_meta($term_id, 'mlcp_' . $field, $value);
        }
    }
}
