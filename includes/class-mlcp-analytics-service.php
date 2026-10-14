<?php
/**
 * MLCP Analytics Service
 *
 * Camada única de leitura/escrita de analytics.
 * Storage primário: get_option('mlcp_analytics')
 * Fallback de leitura: get_option('ml_banner_analytics')
 *
 * @package ML_Carousel_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'MLCP_Analytics_Service' ) ) {
    return;
}

class MLCP_Analytics_Service {

    const OPTION_PRIMARY  = 'mlcp_analytics';
    const OPTION_LEGACY   = 'ml_banner_analytics';
    const OPTION_MIGRATED = 'mlcp_analytics_migrated_v2';

    /* ------------------------------------------------------------------ */
    /* READ                                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Retorna o array completo de analytics.
     * Estrutura: [ post_id => [ 'views' => int, 'clicks' => int, 'last' => int ] ]
     */
    public static function get_data(): array {
        $data = get_option( self::OPTION_PRIMARY, null );

        if ( is_array( $data ) && ! empty( $data ) ) {
            return self::normalize_data( $data );
        }

        // Fallback: lê legacy apenas se primário vazio/inválido
        $legacy = get_option( self::OPTION_LEGACY, array() );
        if ( is_array( $legacy ) && ! empty( $legacy ) ) {
            return self::normalize_data( $legacy );
        }

        return array();
    }

    /**
     * Retorna analytics de um item específico.
     */
    public static function get_item( int $item_id ): array {
        $data = self::get_data();
        $row  = $data[ $item_id ] ?? array();
        return array(
            'views'  => max( 0, (int) ( $row['views']  ?? 0 ) ),
            'clicks' => max( 0, (int) ( $row['clicks'] ?? 0 ) ),
            'last'   => max( 0, (int) ( $row['last']   ?? 0 ) ),
        );
    }

    /* ------------------------------------------------------------------ */
    /* WRITE                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Salva o array completo — SEMPRE em mlcp_analytics, nunca em ml_banner_analytics.
     */
    public static function save_data( array $data ): bool {
        return (bool) update_option( self::OPTION_PRIMARY, self::normalize_data( $data ), false );
    }

    /**
     * Incrementa view para um item. Retorna false se item_id inválido.
     */
    public static function increment_view( int $item_id ): bool {
        if ( $item_id <= 0 ) {
            return false;
        }
        $data = self::get_data();
        $row  = $data[ $item_id ] ?? array( 'views' => 0, 'clicks' => 0, 'last' => 0 );

        $row['views'] = max( 0, (int) ( $row['views'] ?? 0 ) ) + 1;
        $row['last']  = time();

        $data[ $item_id ] = $row;
        return self::save_data( $data );
    }

    /**
     * Incrementa click para um item.
     */
    public static function increment_click( int $item_id ): bool {
        if ( $item_id <= 0 ) {
            return false;
        }
        $data = self::get_data();
        $row  = $data[ $item_id ] ?? array( 'views' => 0, 'clicks' => 0, 'last' => 0 );

        $row['clicks'] = max( 0, (int) ( $row['clicks'] ?? 0 ) ) + 1;
        $row['last']   = time();

        $data[ $item_id ] = $row;
        return self::save_data( $data );
    }

    /**
     * Reset: sem argumento = tudo; com post_id = só aquele item.
     * Nunca apaga imagens, grupos, settings ou configurações.
     */
    public static function reset_all( int $item_id = 0 ): void {
        $data = self::get_data();

        if ( $item_id > 0 ) {
            unset( $data[ $item_id ] );
        } else {
            $data = array();
        }

        self::save_data( $data );
    }

    /* ------------------------------------------------------------------ */
    /* MIGRATION                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Migração única: copia ml_banner_analytics + post_meta legados → mlcp_analytics.
     * Nunca apaga a fonte legacy. Nunca sobrescreve dados existentes válidos.
     * Guarded by option mlcp_analytics_migrated_v2.
     */
    public static function maybe_migrate_legacy(): void {
        if ( get_option( self::OPTION_MIGRATED ) ) {
            return;
        }

        $primary = get_option( self::OPTION_PRIMARY, null );
        $primary = is_array( $primary ) ? $primary : array();

        $legacy  = get_option( self::OPTION_LEGACY, array() );
        $legacy  = is_array( $legacy ) ? $legacy : array();

        // Merge legacy → primary (primary wins on conflict)
        $merged = array_merge( $legacy, $primary );

        // Also absorb post_meta data from old v1.10.x format
        $posts = get_posts( array(
            'post_type'      => defined( 'MLCP_POST_TYPE' ) ? MLCP_POST_TYPE : 'mlcp_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );

        foreach ( $posts as $pid ) {
            $pid = (int) $pid;
            if ( $pid <= 0 ) {
                continue;
            }
            $pm_views  = max( 0, (int) get_post_meta( $pid, '_mlcp_views',         true ) );
            $pm_clicks = max( 0, (int) get_post_meta( $pid, '_mlcp_clicks',        true ) );
            $pm_last   = max( 0, (int) get_post_meta( $pid, '_mlcp_last_activity', true ) );

            if ( $pm_views <= 0 && $pm_clicks <= 0 ) {
                continue;
            }

            $existing = $merged[ $pid ] ?? array( 'views' => 0, 'clicks' => 0, 'last' => 0 );
            $merged[ $pid ] = array(
                'views'  => max( (int) ( $existing['views']  ?? 0 ), $pm_views  ),
                'clicks' => max( (int) ( $existing['clicks'] ?? 0 ), $pm_clicks ),
                'last'   => max( (int) ( $existing['last']   ?? 0 ), $pm_last   ),
            );
        }

        if ( ! empty( $merged ) ) {
            update_option( self::OPTION_PRIMARY, self::normalize_data( $merged ), false );
        }

        update_option( self::OPTION_MIGRATED, 1, false );
    }

    /* ------------------------------------------------------------------ */
    /* HELPERS                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Normaliza o array: garante que cada entrada tem views/clicks/last como int >= 0.
     */
    public static function normalize_data( array $data ): array {
        $clean = array();
        foreach ( $data as $pid => $row ) {
            $pid = (int) $pid;
            if ( $pid <= 0 ) {
                continue;
            }
            $row = is_array( $row ) ? $row : array();
            $clean[ $pid ] = array(
                'views'  => max( 0, (int) ( $row['views']  ?? 0 ) ),
                'clicks' => max( 0, (int) ( $row['clicks'] ?? 0 ) ),
                'last'   => max( 0, (int) ( $row['last']   ?? 0 ) ),
            );
        }
        return $clean;
    }

    /**
     * Resumo agregado para a tela de analytics.
     */
    public static function get_summary(): array {
        $data   = self::get_data();
        $views  = 0;
        $clicks = 0;
        foreach ( $data as $row ) {
            $views  += max( 0, (int) ( $row['views']  ?? 0 ) );
            $clicks += max( 0, (int) ( $row['clicks'] ?? 0 ) );
        }
        $ctr = $views > 0 ? round( $clicks / $views * 100, 1 ) . '%' : '0%';
        return array(
            'views'  => $views,
            'clicks' => $clicks,
            'ctr'    => $ctr,
            'items'  => count( $data ),
        );
    }
}
