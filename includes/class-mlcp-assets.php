<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Assets {
    public static function enqueue_admin($hook) {
        $allowed = array(
            'toplevel_page_' . MLCP_MENU_SLUG,
            'ml-carousel-pro_page_mlcp-shortcodes',
            'ml-carousel-pro_page_mlcp-settings',
            'ml-carousel-pro_page_mlcp-license',
            'ml-carousel-pro_page_mlcp-analytics',
            'ml-banner-pro_page_mlcp-shortcodes',
            'ml-banner-pro_page_mlcp-settings',
            'ml-banner-pro_page_mlcp-license',
            'ml-banner-pro_page_mlcp-analytics',
            'ml-banner-pro_page_mlcp-sort',
            'mlcp-dashboard_page_mlcp-shortcodes',
            'mlcp-dashboard_page_mlcp-settings',
            'mlcp-dashboard_page_mlcp-license',
            'mlcp-dashboard_page_mlcp-analytics',
            'mlcp-dashboard_page_mlcp-sort',
            'ml-carousel-pro_page_mlcp-sort',
            'edit-tags.php',
            'post.php',
            'post-new.php',
            'edit.php',
        );
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) { return; }
        $is_plugin_screen = in_array($hook, $allowed, true) || $screen->post_type === MLCP_POST_TYPE || $screen->taxonomy === MLCP_GROUP_TAX;
        if (!$is_plugin_screen) { return; }
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('mlcp-admin', MLCP_PLUGIN_URL . 'assets/css/admin.css', array('wp-color-picker'), MLCP_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('mlcp-admin', MLCP_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable', 'wp-color-picker'), MLCP_VERSION, true);
        wp_localize_script('mlcp-admin', 'mlcpAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mlcp_admin_nonce'),
            'chooseImage' => __('Escolher imagem', MLCP_TEXT_DOMAIN),
            'useImage' => __('Usar imagem', MLCP_TEXT_DOMAIN),
            'selectGroupFirst' => __('Selecione um grupo primeiro.', MLCP_TEXT_DOMAIN),
            'saveSuccess' => __('Ordenação salva com sucesso.', MLCP_TEXT_DOMAIN),
            'saveError' => __('Não foi possível salvar a ordenação.', MLCP_TEXT_DOMAIN),
        ));
    }
    public static function enqueue_front() {
        wp_register_style('mlcp-front', MLCP_PLUGIN_URL . 'assets/css/front.css', array(), MLCP_VERSION);
        wp_register_script('mlcp-front', MLCP_PLUGIN_URL . 'assets/js/front.js', array(), MLCP_VERSION, true);
        wp_localize_script('mlcp-front', 'mlcpFront', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mlcp_front_nonce'),
        ));
    }
}
