<?php
if (!defined('ABSPATH')) {
    exit;
}

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
}
