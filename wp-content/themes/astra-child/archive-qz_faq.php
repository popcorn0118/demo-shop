<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 常見問題
 * FAQ Archive：只客製「內容區塊」
 * - Sidebar / 外框 / 其他結構：沿用 Astra 的 archive.php
 * - 只替換 Astra 的 astra_content_loop 輸出
 */

if ( class_exists( 'Astra_Loop' ) && method_exists( 'Astra_Loop', 'get_instance' ) ) {

    // 拿掉 Astra 預設內容 loop
    remove_action( 'astra_content_loop', [ Astra_Loop::get_instance(), 'loop_markup' ], 10 );

    // 換成自己的內容 loop
    add_action( 'astra_content_loop', 'qz_faq_archive_loop_markup', 10 );
}

/**
 * 只改內容區塊：要什麼版型就改這裡
 */
function qz_faq_archive_loop_markup() {

    // 先記住主查詢（後面 Schema 要用，同頁分頁/分類篩選都跟著走）
    $wpq        = $GLOBALS['wp_query'];
    $query_vars = is_object($wpq) ? (array) $wpq->query_vars : [];
    ?>
    <main id="main" class="site-main">
        <?php
        if ( have_posts() ) :

            do_action( 'astra_template_parts_content_top' );

            // === 「內容區塊」開始 ===

            $faq_page = get_field('faq_page', 'option') ?: [];

            $accordion_mode     = $faq_page['qz_faq_accordion_mode'] ?? 'single_first';
            $enable_single_page = ! empty($faq_page['qz_faq_enable_single_page']); // True=啟用詳情頁

            echo '<div class="qz-archive qz-faq-archive" data-accordion-mode="' . esc_attr($accordion_mode) . '">';

            while ( have_posts() ) :
                the_post();
                ?>
                <div <?php post_class( 'qz-faq-item' ); ?>>
                    <h3 class="qz-faq-title">
                        <a href="#">
                            <span class="qz-toggle-txt"><?php the_title(); ?></span>
                            <span class="qz-toggle-btn"></span>
                        </a>
                    </h3>

                    <div class="qz-faq-panel">
                        <?php if ( $enable_single_page ) : ?>

                            <?php
                            // 前台顯示：摘要優先，沒有摘要就取前 500 字（不要 […]）
                            $excerpt = trim( get_post_field('post_excerpt', get_the_ID()) );

                            if ( ! $excerpt ) {
                                $content = get_post_field('post_content', get_the_ID());
                                $content = strip_shortcodes($content);
                                $content = wp_strip_all_tags($content, true);
                                $excerpt = mb_substr( $content, 0, 500, 'UTF-8' );
                            }
                            ?>
                            <div class="qz-faq-excerpt">
                                <?php echo esc_html( $excerpt ); ?>
                            </div>

                            <a class="qz-faq-more" href="<?php the_permalink(); ?>">了解更多</a>

                        <?php else : ?>

                            <div class="qz-faq-content">
                                <?php echo apply_filters( 'the_content', get_the_content() ); ?>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
                <?php
            endwhile;

            echo '</div>';
            // === 「內容區塊」結束 ===

            do_action( 'astra_template_parts_content_bottom' );

        else :
            do_action( 'astra_template_parts_content_none' );
        endif;
        ?>
    </main>

    <?php
    // ===== FAQPage Schema（獨立收集，不受 ACF 開關影響）=====
    // 用主查詢 query_vars 再跑一個「只取 IDs」的查詢，確保同頁分頁/分類/搜尋條件一致
    if ( ! empty($query_vars) ) {

        $schema_args = $query_vars;
        $schema_args['fields']                 = 'ids';
        $schema_args['no_found_rows']          = true;
        $schema_args['update_post_meta_cache'] = false;
        $schema_args['update_post_term_cache'] = false;
        $schema_args['ignore_sticky_posts']    = true;

        $ids = get_posts( $schema_args );

        $faq_entities = [];

        if ( ! empty($ids) ) {
            foreach ( $ids as $post_id ) {

                $question = wp_strip_all_tags( get_the_title($post_id), true );
                if ( ! $question ) continue;

                // Schema 一律用「完整內容純文字」（不受 ACF 影響）
                $content = get_post_field('post_content', $post_id);
                $content = strip_shortcodes($content);
                $answer  = trim( wp_strip_all_tags($content, true) );

                if ( ! $answer ) continue;

                $faq_entities[] = [
                    '@type' => 'Question',
                    'name'  => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $answer,
                    ],
                ];
            }
        }

        if ( ! empty($faq_entities) ) {
            $schema = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $faq_entities,
            ];

            echo '<script type="application/ld+json">'
                . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . '</script>';
        }
    }
}

// 其他結構（sidebar / container / header/footer）全部沿用 Astra
require get_template_directory() . '/archive.php';
