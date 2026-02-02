<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * My Account 新增「追蹤清單」分頁
 */
/* 1. 註冊 endpoint：/my-account/wishlist/ */
add_action( 'init', function () {
    add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
} );

/* 2. 在側邊選單中加入一個項目 */
add_filter( 'woocommerce_account_menu_items', function( $items ) {

    $new = array();

    // 從 YITH 設定抓「預設願望清單名稱」
    $wishlist_label = get_option( 'yith_wcwl_wishlist_title' );
    if ( ! $wishlist_label ) {
        $wishlist_label = '追蹤清單'; // 後備文字，避免沒設定時空白
    }

    foreach ( $items as $endpoint => $label ) {

        $new[ $endpoint ] = $label;

        // 想放在哪裡就插在哪個 key 之後，這裡示範放在「訂單」後面
        if ( 'orders' === $endpoint ) {
            // 'wishlist' 這個 key 要跟 endpoint 的 slug 一樣
            $new['wishlist'] = esc_html( $wishlist_label );  // 左側 tab 顯示文字
        }
    }

    return $new;
} );

/* 3. 追蹤清單分頁的實際內容 */
add_action( 'woocommerce_account_wishlist_endpoint', function () {
    // echo '<h2 class="woocommerce-MyAccount-title">追蹤清單</h2>';
    echo '<div id="wishlist-container">';
    echo do_shortcode( '[yith_wcwl_wishlist]' );
    echo '</div>';
} );


/**
 * 關閉 YITH Wishlist 的響應式（不要套用 .mobile 版面）
 */
add_filter( 'yith_wcwl_is_wishlist_responsive', '__return_false' );
