<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 搜尋結果頁的標題翻譯處理
add_filter('astra_the_title', function($title, $id = 0) {

    if ( is_admin() ) {
        return $title;
    }
    // 只改搜尋頁「頁首標題」，不要改 loop 裡每篇文章標題
    if ( is_search() && ! in_the_loop() ) {

        $q = get_search_query(false);

        if ( $q ) {
            return sprintf(
                /* translators: %s: search query */
                __('Search results for: %s', 'astra'),
                '<span>' . esc_html($q) . '</span>'
            );
        }

        return __('Search results', 'astra');
    }

    return $title;

}, 20, 2);
