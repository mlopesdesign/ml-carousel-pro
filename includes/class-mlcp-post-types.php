<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Post_Types {
    public static function register() {
        $labels = array(
            'name' => __('Itens do Carrossel', MLCP_TEXT_DOMAIN),
            'singular_name' => __('Item do Carrossel', MLCP_TEXT_DOMAIN),
            'add_new' => __('Adicionar novo', MLCP_TEXT_DOMAIN),
            'add_new_item' => __('Adicionar novo item', MLCP_TEXT_DOMAIN),
            'edit_item' => __('Editar item', MLCP_TEXT_DOMAIN),
            'new_item' => __('Novo item', MLCP_TEXT_DOMAIN),
            'view_item' => __('Ver item', MLCP_TEXT_DOMAIN),
            'search_items' => __('Buscar itens', MLCP_TEXT_DOMAIN),
            'not_found' => __('Nenhum item encontrado', MLCP_TEXT_DOMAIN),
            'menu_name' => __('Itens', MLCP_TEXT_DOMAIN),
        );

        register_post_type(MLCP_POST_TYPE, array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title'),
            'hierarchical' => false,
            'menu_position' => 58,
            'menu_icon' => 'dashicons-images-alt2',
            'rewrite' => false,
            'query_var' => false,
        ));
    }
}
