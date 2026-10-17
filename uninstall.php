<?php
/**
 * Uninstall — ML Banner Pro.
 *
 * Remove apenas caches e opcoes internas do atualizador. Banners, grupos, configuracoes,
 * licenca e estatisticas sao PRESERVADOS (reinstalar o plugin recupera tudo).
 *
 * @package ML_Carousel_Pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_transient( 'mlcp_github_release_cache' );
delete_transient( 'ml_updater_' . md5( 'ml-carousel-pro' ) );
delete_option( 'mlcp_updater_cache_version' );


// 1.13.0: licença pelo site tools — a licença local fica (reinstalar recupera); só a
// renovação diária é desagendada.
wp_clear_scheduled_hook( 'mllc_ml_carousel_pro_refresh' );
