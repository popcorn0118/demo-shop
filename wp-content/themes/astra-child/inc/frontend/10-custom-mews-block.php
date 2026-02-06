<?php
/**
 * Custom: 首頁最新消息區塊
 * Shortcode: [qz_latest_news]
 *
 * 參數（可選）：
 * - posts: 顯示幾篇（預設 3）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    add_shortcode( 'qz_latest_news', 'qz_latest_news_cb' );
});

/**
 * Shortcode callback
 */
function qz_latest_news_cb( $atts = [] ) {

    $a = shortcode_atts([
        'posts' => 3,
    ], $atts, 'qz_latest_news' );

    $posts = max( 1, (int) $a['posts'] );

    $q = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);

    if ( ! $q->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start(); ?>
    <section class="home-news" aria-label="最新消息">
        <div class="home-news__container">

            <div class="home-news__grid">
                <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                    <article class="home-news__item">
                        <div class="home-news__warp" >

                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="home-news__thumb">
                                    <?php the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
                                </div>
                            <?php endif; ?>

                            <div class="home-news__item-info">

                                <div class="home-news__meta">
                                    <time class="home-news__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                        <?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?>
                                    </time>

                                    <span class="home-news__sep">/</span>

                                    <span class="home-news__cat">
                                        <?php
                                        $cats = get_the_category();
                                        echo ! empty( $cats ) ? esc_html( $cats[0]->name ) : '';
                                        ?>
                                    </span>
                                </div>

                                <h3 class="home-news__item-title"><?php the_title(); ?></h3>

                                <p class="home-news__excerpt">
                                    <?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?>
                                </p>

                                <a href="<?php the_permalink(); ?>" class="home-news__more">了解更多</a>

                            </div>

                        </div>
                        
                    </article>
                <?php endwhile; ?>
            </div>

        </div>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
