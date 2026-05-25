<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Meta_Boxes {
    public static function register() {
        add_meta_box('mlcp_item_details', __('Detalhes do item', MLCP_TEXT_DOMAIN), array(__CLASS__, 'render'), MLCP_POST_TYPE, 'normal', 'high');
        add_meta_box('mlcp_item_status', __('Estado e validade', MLCP_TEXT_DOMAIN), array(__CLASS__, 'render_status'), MLCP_POST_TYPE, 'side', 'default');
        add_meta_box('mlcp_item_groups', __('Grupo(s)', MLCP_TEXT_DOMAIN), array(__CLASS__, 'render_groups'), MLCP_POST_TYPE, 'side', 'default');
    }

    public static function render($post) {
        $meta = MLCP_Helpers::get_item_meta($post->ID);
        wp_nonce_field('mlcp_save_item', 'mlcp_nonce');
        include MLCP_PLUGIN_DIR . 'admin/views/meta-box-item.php';
    }

    public static function render_groups($post) {
        $terms = get_terms(array('taxonomy' => MLCP_GROUP_TAX, 'hide_empty' => false));
        $current = wp_get_post_terms($post->ID, MLCP_GROUP_TAX, array('fields' => 'ids'));
        echo '<div class="mlcp-groups-box">';
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                echo '<label class="mlcp-check-row"><input type="checkbox" name="tax_input[' . esc_attr(MLCP_GROUP_TAX) . '][]" value="' . esc_attr($term->term_id) . '" ' . checked(in_array($term->term_id, $current, true), true, false) . ' /> ' . esc_html($term->name) . '</label>';
            }
        } else {
            echo '<p>Crie um grupo primeiro.</p>';
        }
        echo '</div>';
    }

    public static function render_status($post) {
        $meta = MLCP_Helpers::get_item_meta($post->ID);
        echo '<div class="mlcp-field mlcp-field-side">';
        echo '<label for="mlcp_expire_at">Expiracao do item</label>';
        echo '<input type="datetime-local" name="mlcp_expire_at" id="mlcp_expire_at" value="' . esc_attr(MLCP_Helpers::format_local_datetime($meta['expire_at'])) . '" />';
        echo '<p class="description">Quando chegar esse dia e hora, o item vai para rascunho no horario do site.</p>';
        echo '</div>';

        echo '<div class="mlcp-field mlcp-field-side">';
        echo '<label class="mlcp-check-row"><input type="checkbox" name="mlcp_new_tab" value="1" ' . checked($meta['new_tab'], 1, false) . ' /> Abrir em nova aba</label>';
        echo '<label class="mlcp-check-row"><input type="checkbox" name="mlcp_active" value="1" ' . checked($meta['active'], 1, false) . ' /> Item ativo</label>';
        echo '</div>';
    }

    public static function save($post_id, $post) {
        if (!isset($_POST['mlcp_nonce']) || !wp_verify_nonce($_POST['mlcp_nonce'], 'mlcp_save_item')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            '_mlcp_image_id' => isset($_POST['mlcp_image_id']) ? (int) $_POST['mlcp_image_id'] : 0,
            '_mlcp_image_url' => isset($_POST['mlcp_image_url']) ? esc_url_raw(wp_unslash($_POST['mlcp_image_url'])) : '',
            '_mlcp_date' => isset($_POST['mlcp_date']) ? sanitize_text_field(wp_unslash($_POST['mlcp_date'])) : '',
            '_mlcp_subtitle' => isset($_POST['mlcp_subtitle']) ? sanitize_text_field(wp_unslash($_POST['mlcp_subtitle'])) : '',
            '_mlcp_link'          => isset($_POST['mlcp_link']) ? esc_url_raw(wp_unslash($_POST['mlcp_link'])) : '',
            '_mlcp_item_bg_color' => isset($_POST['mlcp_item_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['mlcp_item_bg_color'])) ?? '' : '',
            '_mlcp_item_margin'   => isset($_POST['mlcp_item_margin']) ? max(0, min(200, (int) $_POST['mlcp_item_margin'])) : 0,
            '_mlcp_new_tab' => !empty($_POST['mlcp_new_tab']) ? 1 : 0,
            '_mlcp_active' => !empty($_POST['mlcp_active']) ? 1 : 0,
        );

        $expire_input = isset($_POST['mlcp_expire_at']) ? sanitize_text_field(wp_unslash($_POST['mlcp_expire_at'])) : '';
        $expire_at = MLCP_Helpers::parse_local_datetime($expire_input);
        $fields['_mlcp_expire_at'] = $expire_at;

        foreach ($fields as $key => $value) {
            if ($key === '_mlcp_expire_at' && $value <= 0) {
                delete_post_meta($post_id, $key);
                continue;
            }

            update_post_meta($post_id, $key, $value);
        }

        self::sync_expiration($post_id, $expire_at);

        $menu_order = isset($_POST['menu_order']) ? (int) $_POST['menu_order'] : 0;
        remove_action('save_post_' . MLCP_POST_TYPE, array(__CLASS__, 'save'), 10);
        wp_update_post(array('ID' => $post_id, 'menu_order' => $menu_order));
        add_action('save_post_' . MLCP_POST_TYPE, array(__CLASS__, 'save'), 10, 2);
    }

    public static function sync_expiration($post_id, $expire_at = 0) {
        self::clear_expiration_schedule($post_id);

        $expire_at = (int) $expire_at;
        if ($expire_at <= 0) {
            return;
        }

        $now = current_time('timestamp', true);
        if ($expire_at <= $now) {
            self::expire_item($post_id);
            return;
        }

        wp_schedule_single_event($expire_at, MLCP_EXPIRATION_HOOK, array($post_id));
    }

    public static function clear_expiration_schedule($post_id) {
        wp_clear_scheduled_hook(MLCP_EXPIRATION_HOOK, array((int) $post_id));
    }

    public static function expire_item($post_id) {
        $post_id = (int) $post_id;
        if (!$post_id || get_post_type($post_id) !== MLCP_POST_TYPE) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_status === 'draft') {
            return;
        }

        remove_action('save_post_' . MLCP_POST_TYPE, array(__CLASS__, 'save'), 10);
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft',
        ));
        add_action('save_post_' . MLCP_POST_TYPE, array(__CLASS__, 'save'), 10, 2);
    }
}
