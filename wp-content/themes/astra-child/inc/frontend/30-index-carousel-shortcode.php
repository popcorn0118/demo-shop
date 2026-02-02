<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 首頁banner輪播
 * Shortcode: [qz_index_carousel]
 */

add_shortcode( 'qz_index_carousel', 'qz_index_carousel_shortcode' );

function qz_index_carousel_shortcode( $atts ) {

    if ( ! function_exists('have_rows') ) return '';

    $atts = shortcode_atts([
        'wrap_class' => 'qz-index-carousel',
    ], $atts, 'qz_index_carousel' );

    $repeater_name = 'index_carousel';

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        $post_id = (int) get_option('page_on_front');
    }
    if ( ! $post_id ) return '';
    if ( ! have_rows( $repeater_name, $post_id ) ) return '';

    // mode 在 repeater 外面：影響整塊輪播
    $mode = trim( (string) get_field( 'mode', $post_id ) );
    if ( ! in_array( $mode, [ 'image_link', 'banner' ], true ) ) $mode = 'banner';

    // 頁面主標 H1（視覺隱藏，SEO/語意保留）
    $main_h1 = trim( (string) get_field( 'main_title_h1', $post_id ) );

    // helper：ACF image（array/id/url）→ [url, alt]
    $get_img = function( $img ) {
        $url = '';
        $alt = '';
        if ( is_array($img) ) {
            $url = ! empty($img['url']) ? $img['url'] : '';
            $alt = ! empty($img['alt']) ? $img['alt'] : '';
        } elseif ( is_numeric($img) ) {
            $url = wp_get_attachment_image_url( (int) $img, 'full' );
            $alt = get_post_meta( (int) $img, '_wp_attachment_image_alt', true );
        } elseif ( is_string($img) ) {
            $url = $img;
        }
        return [ $url, $alt ];
    };

    // helper：ACF image（array/id/url）→ attachment ID（沒有就回 0）
    $get_img_id = function( $img ) {
        if ( is_array($img) && ! empty($img['ID']) ) return (int) $img['ID'];
        if ( is_numeric($img) ) return (int) $img;
        return 0;
    };

    // helper：attachment id → src/srcset（sizes 固定用 100vw）
    $get_wp_src = function( $attach_id ) {
        $attach_id = (int) $attach_id;
        if ( ! $attach_id ) return [
            'src'    => '',
            'srcset' => '',
            'sizes'  => '100vw',
        ];

        $src    = wp_get_attachment_image_url( $attach_id, 'full' );
        $srcset = wp_get_attachment_image_srcset( $attach_id, 'full' );

        return [
            'src'    => $src ? $src : '',
            'srcset' => $srcset ? $srcset : '',
            'sizes'  => '100vw',
        ];
    };

    ob_start();
    ?>

    <?php if ( $main_h1 ) : ?>
        <h1 class="visually-hidden qz-index-carousel__h1"><?php echo esc_html( $main_h1 ); ?></h1>
    <?php endif; ?>

    <div class="qz-index-carousel <?php echo esc_attr( sanitize_html_class( $mode ) ); ?>">
        <div class="qz-index-carousel__track">
            <?php while ( have_rows( $repeater_name, $post_id ) ) : the_row();

                $show = get_sub_field('show');
                if ( empty($show) ) continue;

                // groups
                $img_warp        = get_sub_field('img_warp');
                $color_warp      = get_sub_field('color_warp');
                $text_warp       = get_sub_field('text_warp');
                $text_align_warp = get_sub_field('text_align_warp');

                // images (group)
                $image_link = '';
                if ( is_array($img_warp) && ! empty($img_warp['image_link']) ) {
                    $image_link = trim( (string) $img_warp['image_link'] );
                }
                $img_d = is_array($img_warp) && !empty($img_warp['image'])        ? $img_warp['image']        : null;
                $img_t = is_array($img_warp) && !empty($img_warp['image_tablet']) ? $img_warp['image_tablet'] : null;
                $img_m = is_array($img_warp) && !empty($img_warp['image_mobile']) ? $img_warp['image_mobile'] : null;

                // 向下相容：如果你舊欄位還有 image，就拿來墊底
                $img_fallback = get_sub_field('image');

                // images -> url/alt（fallback 邏輯：m > t > d > image）
                list($d_url, $d_alt) = $get_img( $img_d ?: $img_fallback );
                list($t_url, $t_alt) = $get_img( $img_t ?: ($img_d ?: $img_fallback) );
                list($m_url, $m_alt) = $get_img( $img_m ?: ($img_t ?: ($img_d ?: $img_fallback)) );
                $img_alt = $d_alt ?: ($t_alt ?: $m_alt);

                // WP srcset：三斷點各自走各自的 attachment（沒有就往上 fallback）
                $base_img = $img_d ?: $img_fallback;
                $base_id  = $get_img_id( $base_img );

                $d_img = $img_d ?: $img_fallback;
                $t_img = $img_t ?: $d_img;
                $m_img = $img_m ?: $t_img;

                $d_id  = $get_img_id( $d_img ) ?: $base_id;
                $t_id  = $get_img_id( $t_img ) ?: $d_id;
                $m_id  = $get_img_id( $m_img ) ?: $t_id;

                $d_wp = $get_wp_src( $d_id );
                $t_wp = $get_wp_src( $t_id );
                $m_wp = $get_wp_src( $m_id );

                $fallback_src = $m_url ?: ( $t_url ?: $d_url );
                if ( ! $fallback_src && ! $m_wp['src'] && ! $t_wp['src'] && ! $d_wp['src'] ) continue;

                // mode=image_link：整個輪播只出圖（其餘欄位完全不碰）
                if ( $mode === 'image_link' ) :
                    $aria = $img_alt ? $img_alt : 'banner';
            ?>
                    <div class="qz-index-carousel__slide">
                        <?php if ( $image_link ) : ?>
                            <a class="qz-index-carousel__link"
                               href="<?php echo esc_url( $image_link ); ?>"
                               aria-label="<?php echo esc_attr( $aria ); ?>">
                        <?php endif; ?>

                        <picture class="qz-index-carousel__pic">
                            <?php if ( ! empty($d_wp['srcset']) ) : ?>
                                <source media="(min-width: 1024px)" srcset="<?php echo esc_attr( $d_wp['srcset'] ); ?>" sizes="<?php echo esc_attr( $d_wp['sizes'] ); ?>">
                            <?php elseif ( $d_url ) : ?>
                                <source media="(min-width: 1024px)" srcset="<?php echo esc_url( $d_url ); ?>" sizes="100vw">
                            <?php endif; ?>

                            <?php if ( ! empty($t_wp['srcset']) ) : ?>
                                <source media="(min-width: 768px)" srcset="<?php echo esc_attr( $t_wp['srcset'] ); ?>" sizes="<?php echo esc_attr( $t_wp['sizes'] ); ?>">
                            <?php elseif ( $t_url ) : ?>
                                <source media="(min-width: 768px)" srcset="<?php echo esc_url( $t_url ); ?>" sizes="100vw">
                            <?php endif; ?>

                            <img class="qz-index-carousel__img"
                                 src="<?php echo esc_url( $m_wp['src'] ?: $fallback_src ); ?>"
                                 <?php if ( ! empty($m_wp['srcset']) ) : ?>
                                    srcset="<?php echo esc_attr( $m_wp['srcset'] ); ?>"
                                    sizes="<?php echo esc_attr( $m_wp['sizes'] ); ?>"
                                 <?php endif; ?>
                                 alt="<?php echo esc_attr( $img_alt ); ?>"
                                 loading="lazy"
                                 decoding="async" />
                        </picture>

                        <?php if ( $image_link ) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
            <?php
                    continue;
                endif;

                // ===== banner 模式（全欄位）=====

                // align/custom css (group)
                $align = '';
                $custom_css = '';
                if ( is_array($text_align_warp) ) {
                    $align      = ! empty($text_align_warp['text_align']) ? (string) $text_align_warp['text_align'] : '';
                    $custom_css = ! empty($text_align_warp['custom_css']) ? (string) $text_align_warp['custom_css'] : '';
                }
                $align = trim($align);
                if ( ! in_array( $align, ['left','center','right','custom'], true ) ) $align = 'center';

                // ===== colors (group) NEW: color_warp -> desk_style / mobile_style =====
                $desk_style   = ( is_array($color_warp) && ! empty($color_warp['desk_style']) && is_array($color_warp['desk_style']) )
                    ? $color_warp['desk_style'] : [];

                $mobile_style = ( is_array($color_warp) && ! empty($color_warp['mobile_style']) && is_array($color_warp['mobile_style']) )
                    ? $color_warp['mobile_style'] : [];

                // 向下相容：舊結構（color_warp 直接放 color_*）
                $is_legacy = is_array($color_warp) && empty($desk_style) && (
                    ! empty($color_warp['color_coverage']) ||
                    ! empty($color_warp['color_text']) ||
                    ! empty($color_warp['color_text_sub']) ||
                    ! empty($color_warp['color_text_desc'])
                );

                $desk_overlay         = $is_legacy ? (string) ($color_warp['color_coverage'] ?? '')  : (string) ($desk_style['color_coverage'] ?? '');
                $desk_color_text      = $is_legacy ? (string) ($color_warp['color_text'] ?? '')      : (string) ($desk_style['color_text'] ?? '');
                $desk_color_text_sub  = $is_legacy ? (string) ($color_warp['color_text_sub'] ?? '')  : (string) ($desk_style['color_text_sub'] ?? '');
                $desk_color_text_desc = $is_legacy ? (string) ($color_warp['color_text_desc'] ?? '') : (string) ($desk_style['color_text_desc'] ?? '');

                $mob_overlay         = (string) ($mobile_style['color_coverage'] ?? '');
                $mob_color_text      = (string) ($mobile_style['color_text'] ?? '');
                $mob_color_text_sub  = (string) ($mobile_style['color_text_sub'] ?? '');
                $mob_color_text_desc = (string) ($mobile_style['color_text_desc'] ?? '');
                $mob_content_bg      = (string) ($mobile_style['content_bg'] ?? '');
                $mob_content_bg_blur    = (string) ($mobile_style['content_bg_blur'] ?? '');

                // text (group)
                $title     = is_array($text_warp) && !empty($text_warp['title'])     ? (string) $text_warp['title']     : '';
                $sub_title = is_array($text_warp) && !empty($text_warp['sub_title']) ? (string) $text_warp['sub_title'] : '';
                $desc      = is_array($text_warp) && !empty($text_warp['desc'])      ? (string) $text_warp['desc']      : '';

                // btn
                $btn        = get_sub_field('btn');
                $btn_url    = '';
                $btn_title  = '';
                $btn_target = '';
                if ( is_array($btn) ) {
                    $btn_url    = ! empty($btn['url']) ? $btn['url'] : '';
                    $btn_title  = ! empty($btn['title']) ? $btn['title'] : '';
                    $btn_target = ! empty($btn['target']) ? $btn['target'] : '';
                }

                // content class/style：custom 時不加 class，改用 inline style
                $content_class = '';
                $content_style = '';
                if ( $align === 'custom' ) {
                    $custom_css = trim($custom_css);
                    if ( $custom_css !== '' ) {
                        $content_style = 'style="' . esc_attr( $custom_css ) . '"';
                    }
                } else {
                    $content_class = $align;
                }

                // 背景圖（你目前的 JS 會負責切換斷點）
                $bg_desktop = $d_url ?: ($m_wp['src'] ?: $fallback_src);
                $bg_tablet  = $t_url ?: ($m_wp['src'] ?: $fallback_src);
                $bg_mobile  = ($m_wp['src'] ?: $fallback_src);

                // ===== 變數輸出：行內只放 CSS variables，不直接塞 color/background-color =====
                $vars = '';
                if ( $desk_overlay )         $vars .= '--qz-overlay:' . esc_attr($desk_overlay) . ';';
                if ( $desk_color_text )      $vars .= '--qz-text:' . esc_attr($desk_color_text) . ';';
                if ( $desk_color_text_sub )  $vars .= '--qz-text-sub:' . esc_attr($desk_color_text_sub) . ';';
                if ( $desk_color_text_desc ) $vars .= '--qz-text-desc:' . esc_attr($desk_color_text_desc) . ';';

                if ( $mob_overlay )          $vars .= '--qz-overlay-m:' . esc_attr($mob_overlay) . ';';
                if ( $mob_color_text )       $vars .= '--qz-text-m:' . esc_attr($mob_color_text) . ';';
                if ( $mob_color_text_sub )   $vars .= '--qz-text-sub-m:' . esc_attr($mob_color_text_sub) . ';';
                if ( $mob_color_text_desc )  $vars .= '--qz-text-desc-m:' . esc_attr($mob_color_text_desc) . ';';

                if ( $mob_content_bg )       $vars .= '--qz-content-bg-m:' . esc_attr($mob_content_bg) . ';';
                if ( $mob_content_bg_blur !== '' ) $vars .= '--qz-content-blur-m:' . esc_attr($mob_content_bg_blur) . ';';
            ?>
                <div class="qz-index-carousel__slide"
                    data-bg-desktop="<?php echo esc_url( $bg_desktop ); ?>"
                    data-bg-tablet="<?php echo esc_url( $bg_tablet ); ?>"
                    data-bg-mobile="<?php echo esc_url( $bg_mobile ); ?>"
                    style="background-image: url('<?php echo esc_url( $bg_desktop ); ?>');<?php echo $vars ? ' ' . $vars : ''; ?>">

                    <div class="qz-index-carousel__overlay"></div>

                    <?php if ( $sub_title || $title || $desc || ( $btn_url && $btn_title )) : ?>

                        <div class="qz-index-carousel__content<?php echo $content_class ? ' ' . esc_attr($content_class) : ''; ?>" <?php echo $content_style; ?>>

                            <?php if ( $sub_title ) : ?>
                                <h6 class="qz-index-carousel__sub-title">
                                    <?php echo esc_html($sub_title); ?>
                                </h6>
                            <?php endif; ?>

                            <?php if ( $title ) : ?>
                                <h2 class="qz-index-carousel__title">
                                    <?php echo esc_html($title); ?>
                                </h2>
                            <?php endif; ?>

                            <?php if ( $desc ) : ?>
                                <div class="qz-index-carousel__desc">
                                    <?php echo nl2br( esc_html($desc) ); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $btn_url && $btn_title ) : ?>
                                <div class="qz-index-carousel__btn">
                                    <a class="qz-index-carousel__btn-link"
                                    href="<?php echo esc_url($btn_url); ?>"
                                    <?php echo $btn_target ? 'target="'.esc_attr($btn_target).'" rel="noopener"' : ''; ?>>
                                        <?php echo esc_html($btn_title); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endwhile; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
