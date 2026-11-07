<?php
if (!defined('ABSPATH')) {
    exit;
}

if ( class_exists( 'MLCP_Shortcodes' ) ) { return; }

class MLCP_Shortcodes {
    public static function render($atts = array()) {
        $atts = shortcode_atts(array(
            'group' => '',
            'width' => '',
            'height' => '',
            'width_px' => '',
            'height_px' => '',
            'lock_proportion' => '',
            'desktop' => '',
            'tablet' => '',
            'mobile' => '',
            'gap' => '',
            'autoplay' => '',
            'autoplay_speed' => '',
            'arrows' => '',
            'rounded_corners' => '',
            'show_title' => '',
            'show_subtitle' => '',
            'class' => '',
            'overlay_enabled' => '',
            'overlay_opacity' => '',
            'template' => '',
            'visible' => '',
            'interval' => '',
            'accent' => '',
        ), $atts, 'ml_carousel');

        $settings = MLCP_Helpers::get_settings();
        $group = null;
        $group_settings = MLCP_Helpers::get_group_defaults();

        if (!empty($atts['group'])) {
            $group = get_term_by('slug', sanitize_title($atts['group']), MLCP_GROUP_TAX);
            if (!$group || is_wp_error($group)) {
                return '';
            }
            $group_settings = MLCP_Helpers::get_group_settings($group->term_id);
        }

        $query_args = array(
            'post_type' => MLCP_POST_TYPE,
            'posts_per_page' => -1,
            'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_mlcp_active',
                    'value' => '1',
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_mlcp_expire_at',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_mlcp_expire_at',
                        'value' => current_time('timestamp', true),
                        'compare' => '>',
                        'type' => 'NUMERIC',
                    ),
                ),
            ),
        );

        if ($group) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => MLCP_GROUP_TAX,
                    'field' => 'term_id',
                    'terms' => $group->term_id,
                ),
            );
        }

        $items = get_posts($query_args);
        if (!$items) {
            return '';
        }

        if (sanitize_key((string) $atts['template']) === 'agenda') {
            return self::render_agenda($items, $atts);
        }

        $default_block_width = max(1, (int) ($settings['default_width_value'] ?? 1140)) . MLCP_Helpers::normalize_size_unit($settings['default_width_unit'] ?? 'px');
        $block_width = $atts['width'] !== '' ? MLCP_Helpers::clean_css_size($atts['width'], $default_block_width) : $default_block_width;

        $card_width_px = MLCP_Helpers::clean_px($atts['width_px'] !== '' ? $atts['width_px'] : $group_settings['width_px'], $group_settings['width_px']);
        $height_px = MLCP_Helpers::clean_px($atts['height_px'] !== '' ? $atts['height_px'] : $group_settings['height_px'], $group_settings['height_px']);

        if ($atts['height'] !== '') {
            $legacy_height = MLCP_Helpers::clean_css_size($atts['height'], $height_px . 'px');
        } else {
            $legacy_height = $height_px . 'px';
        }

        $lock_proportion = MLCP_Helpers::normalize_bool($atts['lock_proportion'], $group_settings['lock_proportion']);
        $desktop = max(1, (int) ($atts['desktop'] !== '' ? $atts['desktop'] : $group_settings['desktop']));
        $tablet = max(1, (int) ($atts['tablet'] !== '' ? $atts['tablet'] : $group_settings['tablet']));
        $mobile = max(1, (int) ($atts['mobile'] !== '' ? $atts['mobile'] : $group_settings['mobile']));
        $gap = max(0, (int) ($atts['gap'] !== '' ? $atts['gap'] : $group_settings['gap']));
        $autoplay = MLCP_Helpers::normalize_bool($atts['autoplay'], $group_settings['autoplay']);
        $arrows = MLCP_Helpers::normalize_bool($atts['arrows'], $group_settings['arrows']);
        $rounded_corners = MLCP_Helpers::normalize_bool($atts['rounded_corners'], $group_settings['rounded_corners']);
        $autoplay_speed = max(1000, (int) ($atts['autoplay_speed'] !== '' ? $atts['autoplay_speed'] : $group_settings['autoplay_speed']));
        $overlay_enabled = MLCP_Helpers::normalize_bool($atts['overlay_enabled'], $group_settings['overlay_enabled']);
        $overlay_opacity = max(0, min(100, (int) ($atts['overlay_opacity'] !== '' ? $atts['overlay_opacity'] : $group_settings['overlay_opacity'])));
        $overlay_strength = $overlay_enabled ? ($overlay_opacity / 100) : 0;
        $show_title = MLCP_Helpers::normalize_bool($atts['show_title'], $group_settings['show_title']);
        $show_subtitle = MLCP_Helpers::normalize_bool($atts['show_subtitle'], $group_settings['show_subtitle']);
        $image_fit = in_array($group_settings['image_fit'] ?? 'cover', array('cover', 'contain'), true) ? $group_settings['image_fit'] : 'cover';
        // card_bg_color: group-level default (overridden per item below)
        $raw_bg_group  = $group_settings['card_bg_color'] ?? '';
        $card_margin_group = max(0, min(200, (int) ($group_settings['card_margin'] ?? 0)));
        if ($raw_bg_group !== '') {
            $card_bg_default = sanitize_hex_color($raw_bg_group) ?? 'transparent';
        } else {
            $card_bg_default = $image_fit === 'contain' ? 'transparent' : '#111';
        }
        $card_margin_default = $card_margin_group;
        $uid = 'mlcp-' . wp_rand(1000, 99999);

        wp_enqueue_style('mlcp-front');
        wp_enqueue_script('mlcp-front');

        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>"
             class="mlcp-carousel <?php echo esc_attr(trim((string) $atts['class'])); ?>"
             style="<?php echo esc_attr('--mlcp-width:' . $block_width . ';--mlcp-height:' . $legacy_height . ';--mlcp-card-width:' . $card_width_px . 'px;--mlcp-height-px:' . $height_px . 'px;--mlcp-ratio:' . $card_width_px . ' / ' . $height_px . ';--mlcp-desktop:' . $desktop . ';--mlcp-tablet:' . $tablet . ';--mlcp-mobile:' . $mobile . ';--mlcp-gap:' . $gap . 'px;--mlcp-overlay:' . $overlay_strength . ';--mlcp-image-fit:' . $image_fit . ';--mlcp-card-bg:' . $card_bg_default . ';--mlcp-card-margin:' . $card_margin_default . 'px;'); ?>"
             data-desktop="<?php echo esc_attr($desktop); ?>"
             data-tablet="<?php echo esc_attr($tablet); ?>"
             data-mobile="<?php echo esc_attr($mobile); ?>"
             data-autoplay="<?php echo esc_attr($autoplay); ?>"
             data-autoplay-speed="<?php echo esc_attr($autoplay_speed); ?>"
             data-arrows="<?php echo esc_attr($arrows); ?>"
             data-rounded-corners="<?php echo esc_attr($rounded_corners); ?>"
             data-show-title="<?php echo esc_attr($show_title); ?>"
             data-show-subtitle="<?php echo esc_attr($show_subtitle); ?>"
             data-lock-proportion="<?php echo esc_attr($lock_proportion); ?>">
            <?php if ($arrows) : ?>
                <button class="mlcp-nav mlcp-prev" type="button" aria-label="Anterior">&#10094;</button>
            <?php endif; ?>
            <div class="mlcp-viewport">
                <div class="mlcp-track">
                    <?php foreach ($items as $item) :
                        $meta     = MLCP_Helpers::get_item_meta($item->ID);
                        $img      = MLCP_Helpers::get_image_url($item->ID, 'large');
                        if (!$img) {
                            continue;
                        }
                        // Full-size for lightbox; fallback to large if full not available
                        $img_full = MLCP_Helpers::get_image_url($item->ID, 'full');
                        if (!$img_full) {
                            $img_full = $img;
                        }
                        $has_link = !empty($meta['link']) && trim($meta['link']) !== '' && trim($meta['link']) !== '#';
                        $target   = !empty($meta['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
                        $title    = esc_attr(get_the_title($item->ID));
                    ?>
                        <article class="mlcp-card" data-mlcp-item-id="<?php echo esc_attr($item->ID); ?>">
                            <?php if ($has_link) : ?>
                                <a class="mlcp-link mlcp-link--href"
                                   data-mlcp-track-click="1"
                                   data-mlcp-item-id="<?php echo esc_attr($item->ID); ?>"
                                   href="<?php echo esc_url($meta['link']); ?>"
                                   <?php echo $target; ?>
                                   aria-label="<?php echo esc_attr($title); ?>">
                            <?php else : ?>
                                <span class="mlcp-link mlcp-link--lightbox"
                                      data-mlcp-track-click="1"
                                      data-mlcp-item-id="<?php echo esc_attr($item->ID); ?>"
                                      role="button"
                                      tabindex="0"
                                      aria-label="<?php echo esc_attr(sprintf('Ver imagem: %s', get_the_title($item->ID))); ?>"
                                      data-mlcp-lightbox="<?php echo esc_url($img_full); ?>"
                                      data-mlcp-lightbox-alt="<?php echo esc_attr($title); ?>">
                            <?php endif; ?>
                                <div class="mlcp-image-wrap">
                                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo $title; ?>" />
                                    <?php if ($overlay_enabled && $overlay_opacity > 0) : ?><div class="mlcp-overlay"></div><?php endif; ?>
                                    <?php if ($show_title || ($show_subtitle && !empty($meta['subtitle_display']))) : ?>
                                    <div class="mlcp-caption">
                                        <?php if ($show_title) : ?>
                                            <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
                                        <?php endif; ?>
                                        <?php if ($show_subtitle && !empty($meta['subtitle_display'])) : ?>
                                            <p><?php echo esc_html($meta['subtitle']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php if ($has_link) : ?>
                                </a>
                            <?php else : ?>
                                </span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($arrows) : ?>
                <button class="mlcp-nav mlcp-next" type="button" aria-label="Pr&#243;ximo">&#10095;</button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Data do item (Y-m-d) a partir do campo Data (AAAA-MM-DD, DD-MM-AAAA ou DD/MM/AAAA) ou da expiração.
     */
    private static function item_date(array $meta): string {
        $raw = trim((string) $meta['date']);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        if (!empty($meta['expire_at'])) {
            return wp_date('Y-m-d', (int) $meta['expire_at']);
        }
        return '';
    }

    /**
     * Template "agenda": lista de eventos (data grande, título, subtítulo, cartaz).
     * Mostra "visible" linhas; se houver mais, rola sozinho para cima (pausa no mouse/foco,
     * respeita "reduzir movimento"). Cartaz abre no lightbox do plugin; com link, o título leva ao link.
     * Uso: [ml_carousel group="eventos" template="agenda" visible="3" interval="4000" accent="#A80F17"]
     * accent = cor da data (opcional; o tema/builder também pode trocar pela variável --mlcp-ag-accent).
     */
    private static function render_agenda(array $items, array $atts): string {
        $visible  = max(1, min(12, (int) ($atts['visible'] !== '' ? $atts['visible'] : 3)));
        $interval = max(2000, min(20000, (int) ($atts['interval'] !== '' ? $atts['interval'] : 4000)));
        $today    = wp_date('Y-m-d');
        $rows     = array();
        foreach ($items as $item) {
            $meta = MLCP_Helpers::get_item_meta($item->ID);
            $date = self::item_date($meta);
            if ($date !== '' && $date < $today) {
                continue;
            }
            $rows[] = array('item' => $item, 'meta' => $meta, 'date' => $date);
        }
        if (!$rows) {
            return '';
        }
        usort($rows, static function ($a, $b) {
            $da = $a['date'] !== '' ? $a['date'] : '9999-12-31';
            $db = $b['date'] !== '' ? $b['date'] : '9999-12-31';
            return strcmp($da, $db);
        });

        wp_enqueue_style('mlcp-front');
        wp_enqueue_script('mlcp-front');

        $out = '<div class="mlcp-agenda ' . esc_attr(trim((string) $atts['class'])) . '" data-mlcp-agenda data-visible="' . esc_attr((string) $visible) . '" data-interval="' . esc_attr((string) $interval) . '" style="--mlcp-ag-visible:' . (int) $visible . ';' . (sanitize_hex_color((string) $atts['accent']) ? '--mlcp-ag-accent:' . sanitize_hex_color((string) $atts['accent']) . ';' : '') . '">';
        $out .= '<div class="mlcp-ag-viewport"><ul class="mlcp-ag-list" role="list">';
        foreach ($rows as $r) {
            $item  = $r['item'];
            $meta  = $r['meta'];
            $title = get_the_title($item->ID);
            $ts    = $r['date'] !== '' ? (int) strtotime($r['date'] . ' 12:00:00') : 0;
            $img   = MLCP_Helpers::get_image_url($item->ID, 'medium');
            $full  = MLCP_Helpers::get_image_url($item->ID, 'full');
            $full  = $full ? $full : $img;
            $link  = trim((string) $meta['link']);
            $has_link = $link !== '' && $link !== '#';
            $target = !empty($meta['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $out .= '<li class="mlcp-ag-row" data-mlcp-item-id="' . esc_attr((string) $item->ID) . '">';
            $out .= '<span class="mlcp-ag-day">' . ($ts ? '<b>' . esc_html(wp_date('d', $ts)) . '</b><span>' . esc_html(rtrim(wp_date('M', $ts), '.')) . '</span>' : '') . '</span>';
            $out .= '<span class="mlcp-ag-txt">';
            $out .= $has_link ? '<a class="mlcp-ag-title" href="' . esc_url($link) . '"' . $target . '>' . esc_html($title) . '</a>' : '<b class="mlcp-ag-title">' . esc_html($title) . '</b>';
            if (trim((string) $meta['subtitle']) !== '') {
                $out .= '<span class="mlcp-ag-sub">' . esc_html($meta['subtitle']) . '</span>';
            }
            $out .= '</span>';
            if ($img) {
                $alt = 'Cartaz: ' . $title . ($ts ? ', ' . wp_date('j \\d\\e F', $ts) : '');
                $out .= '<span class="mlcp-ag-poster mlcp-link--lightbox" role="button" tabindex="0" data-mlcp-track-click="1" data-mlcp-item-id="' . esc_attr((string) $item->ID) . '" data-mlcp-lightbox="' . esc_url($full) . '" data-mlcp-lightbox-alt="' . esc_attr($alt) . '" aria-label="' . esc_attr('Ver cartaz: ' . $title) . '"><img src="' . esc_url($img) . '" alt="" loading="lazy" decoding="async"></span>';
            } else {
                $out .= '<span class="mlcp-ag-poster is-empty"></span>';
            }
            $out .= '</li>';
        }
        $out .= '</ul></div></div>';
        return $out;
    }
}
