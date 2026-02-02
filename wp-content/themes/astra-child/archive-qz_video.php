<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 影音專區
 * video Archive / video taxonomy：只客製「內容區塊」
 * - Sidebar / 外框 / 其他結構：沿用 Astra 的 archive.php
 * - 只替換 Astra 的 astra_content_loop 輸出
 */

if ( class_exists( 'Astra_Loop' ) && method_exists( 'Astra_Loop', 'get_instance' ) ) {

    // 拿掉 Astra 預設內容 loop
    remove_action( 'astra_content_loop', [ Astra_Loop::get_instance(), 'loop_markup' ], 10 );

    // 換成自己的內容 loop
    add_action( 'astra_content_loop', 'qz_video_archive_loop_markup', 10 );
}

if ( ! function_exists( 'qz_parse_youtube_id' ) ) {
    function qz_parse_youtube_id( $url ) {
        $url = trim( (string) $url );
        if ( ! $url ) return '';

        // youtu.be/<id>
        if ( preg_match( '~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m ) ) {
            return $m[1];
        }

        // youtube.com/watch?v=<id>
        $parts = wp_parse_url( $url );
        if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $q );
            if ( ! empty( $q['v'] ) ) {
                return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $q['v'] );
            }
        }

        // youtube.com/embed/<id>
        if ( preg_match( '~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m ) ) {
            return $m[1];
        }

        return '';
    }
}

function qz_video_archive_loop_markup() {

    $wpq        = $GLOBALS['wp_query'];
    $query_vars = is_object($wpq) ? (array) $wpq->query_vars : [];
    ?>

    <main id="main" class="site-main">
        <?php
        if ( have_posts() ) :

            do_action( 'astra_template_parts_content_top' );

            $video_page = function_exists('get_field') ? ( get_field('video_page', 'option') ?: [] ) : [];
            $enable_single_page = ! empty( $video_page['qz_video_enable_single_page'] ); // True=啟用詳情頁

            echo '<div class="qz-archive qz-video-archive">';

            while ( have_posts() ) :
                the_post();

                $post_id  = get_the_ID();
                $date_str = get_the_date('Y.m.d');

                $term_names = [];
                $terms = get_the_terms( $post_id, 'qz_video_cat' );
                if ( empty($terms) || is_wp_error($terms) ) {
                    $terms = get_the_terms( $post_id, 'category' );
                }
                if ( ! empty($terms) && ! is_wp_error($terms) ) {
                    foreach ( $terms as $t ) {
                        $term_names[] = $t->name;
                    }
                }
                $cats_str = $term_names ? implode('、', $term_names) : '';

                $item_url = $enable_single_page ? get_permalink() : '';

                $youtube_url = function_exists('get_field') ? (string) get_field( 'youtube_url', $post_id ) : '';
                $youtube_url = trim( $youtube_url );
                $video_id    = qz_parse_youtube_id( $youtube_url );

                $classes = implode( ' ', get_post_class( 'qz-video-item', $post_id ) );
                ?>

                <?php if ( $enable_single_page ) : ?>
                    <a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $item_url ); ?>">
                <?php else : ?>
                    <div class="<?php echo esc_attr( $classes ); ?>">
                <?php endif; ?>

                    <?php if ( $video_id ) : ?>

                        <?php
                        $yt_thumb = 'https://i.ytimg.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg';
                        $title    = get_the_title( $post_id );
                        $embed    = 'https://www.youtube.com/embed/' . rawurlencode($video_id);

                        $iframe_html =
                            '<iframe'
                            . ' src="' . esc_url( $embed ) . '"'
                            . ' title="YouTube video player"'
                            . ' frameborder="0"'
                            . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
                            . ' allowfullscreen'
                            . ' loading="lazy"'
                            . ' referrerpolicy="strict-origin-when-cross-origin"'
                            . '></iframe>';
                        ?>

                        <div class="qz-video-grid">

                            <?php if ( $enable_single_page ) : ?>

                                <?php
                                // 啟用詳情頁：列表永遠只顯示封面（不可播放）
                                if ( has_post_thumbnail( $post_id ) ) {
                                    echo '<figure class="qz-video-thumb">';
                                    echo get_the_post_thumbnail( $post_id, 'medium', [
                                        'loading'  => 'lazy',
                                        'decoding' => 'async',
                                    ] );
                                    echo '</figure>';
                                } else {
                                    echo '<figure class="qz-video-thumb">';
                                    echo '<img src="' . esc_url( $yt_thumb ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async">';
                                    echo '</figure>';
                                }
                                ?>

                            <?php else : ?>

                                <?php
                                    // 關閉詳情頁：列表要能直接播放
                                    $embed_base = 'https://www.youtube.com/embed/' . rawurlencode($video_id);
                                    $origin = home_url();
                                    $origin = preg_replace('~/$~', '', $origin);
                                    $embed_play = add_query_arg([
                                    'autoplay' => 1,
                                    'mute' => 0,
                                    'playsinline' => 1,
                                    'rel' => 0,
                                    'modestbranding' => 1,
                                    'origin' => $origin,
                                    ], $embed_base);

                                    $iframe_html_play =
                                    '<iframe'
                                    . ' src="' . esc_url( $embed_play ) . '"'
                                    . ' title="YouTube video player"'
                                    . ' frameborder="0"'
                                    . ' allow="accelerometer; autoplay; clipboard-read; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
                                    . ' allowfullscreen'
                                    . ' loading="lazy"'
                                    . ' referrerpolicy="strict-origin-when-cross-origin"'
                                    . '></iframe>';

                                    $cover_html = has_post_thumbnail($post_id)
                                    ? get_the_post_thumbnail($post_id, 'medium', ['loading'=>'lazy','decoding'=>'async'])
                                    : '<img src="' . esc_url($yt_thumb) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async">';

                                    ?>
                                    <div class="qz-video-embed qz-video-player" data-embed="<?php echo esc_attr($iframe_html_play); ?>">
                                        <div class="qz-video-cover" role="button" tabindex="0" aria-label="Play video">
                                            <?php echo $cover_html; ?>
                                            <span class="qz-video-play" aria-hidden="true"></span>
                                        </div>
                                    </div>


                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                    <div class="qz-video-meta">
                        <?php if ( $date_str ) : ?>
                            <time class="qz-video-date" datetime="<?php echo esc_attr( get_the_date('c') ); ?>">
                                <?php echo esc_html( $date_str ); ?>
                            </time>
                        <?php endif; ?>
                        <span class="line">/</span>
                        <?php if ( $cats_str ) : ?>
                            <span class="qz-video-cats"><?php echo esc_html( $cats_str ); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="qz-video-title"><?php the_title(); ?></h3>

                    <?php if ( $enable_single_page ) : ?>
                        <?php
                        $excerpt = trim( get_post_field('post_excerpt', $post_id) );
                        if ( ! $excerpt ) {
                            $content = get_post_field('post_content', $post_id);
                            $content = strip_shortcodes($content);
                            $content = wp_strip_all_tags($content, true);
                            $excerpt = mb_substr( $content, 0, 500, 'UTF-8' );
                        }
                        ?>
                        <div class="qz-video-excerpt">
                            <?php echo esc_html( $excerpt ); ?>
                        </div>
                    <?php endif; ?>

                <?php if ( $enable_single_page ) : ?>
                    </a>
                <?php else : ?>
                    </div>
                <?php endif; ?>

                <?php
            endwhile;

            echo '</div>';

            do_action( 'astra_template_parts_content_bottom' );

        else :
            do_action( 'astra_template_parts_content_none' );
        endif;
        ?>
    </main>
    <?php
}

require get_template_directory() . '/archive.php';
