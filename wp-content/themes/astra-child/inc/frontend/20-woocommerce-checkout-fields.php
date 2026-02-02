<?php
/**
 * ==========================
 * WooCommerce 結帳欄位客製
 * - 台灣地址欄位排序＆樣式
 * - 姓名欄位左右對調
 * ==========================
 */

/**
 * 地址欄位：
 *  - 第二列：縣市(1)、鄉鎮市(2)、郵遞區號(3) 並排
 *  - 第三列：詳細地址 100% 寬，移除 address_2
 */
add_filter( 'woocommerce_default_address_fields', 'qz_checkout_address_tw' );
function qz_checkout_address_tw( $fields ) {

    // 共用：三欄欄位用的 class
    $col_class = array( 'form-row', 'qz-tw-col-1-3' );

    // 1. 縣 / 市
    if ( isset( $fields['state'] ) ) {
        $fields['state']['priority'] = 60;
        $fields['state']['placeholder'] = '縣／市';
        $fields['state']['class']       = $col_class;
    }

    // 2. 鄉鎮市
    if ( isset( $fields['city'] ) ) {
        $fields['city']['priority'] = 61;
        $fields['city']['placeholder'] = '鄉鎮區';
        $fields['city']['class']       = $col_class;
    }

    // 3. 郵遞區號
    if ( isset( $fields['postcode'] ) ) {
        $fields['postcode']['priority'] = 62;
        $fields['postcode']['placeholder'] = '郵遞區號';
        $fields['postcode']['class']       = $col_class;
        $fields['postcode']['clear']       = false; // 不另起一行
    }

    // 4. 詳細地址（原 address_1）
    if ( isset( $fields['address_1'] ) ) {
        $fields['address_1']['priority']    = 70;
        $fields['address_1']['class']       = array( 'form-row-wide' ); // 100%
        $fields['address_1']['clear']       = true;                     // 另起一行
        $fields['address_1']['placeholder'] = '詳細地址';
    }

    // 移除「公寓、套房、單元等（選填）」欄位
    if ( isset( $fields['address_2'] ) ) {
        unset( $fields['address_2'] );
    }

    return $fields;
}

/**
 * 姓名欄位：
 *  - 左：姓氏
 *  - 右：名字
 */
add_filter( 'woocommerce_checkout_fields', 'qz_checkout_swap_name_fields' );
function qz_checkout_swap_name_fields( $fields ) {

    /* === 隱藏國家欄位（Billing / Shipping） === */
    if ( isset( $fields['billing']['billing_country'] ) ) {
        $fields['billing']['billing_country']['type']  = 'hidden'; // 變成 hidden input
        $fields['billing']['billing_country']['label'] = false;    // 不顯示標籤
    }

    if ( isset( $fields['shipping']['shipping_country'] ) ) {
        $fields['shipping']['shipping_country']['type']  = 'hidden';
        $fields['shipping']['shipping_country']['label'] = false;
    }

    /* === 姓名欄位左右對調 === */
    // ====== Billing ======
    if ( isset( $fields['billing']['billing_last_name'] ) ) {
        $fields['billing']['billing_last_name']['priority'] = 10;
        $fields['billing']['billing_last_name']['class']    = array( 'form-row-first' );
    }

    if ( isset( $fields['billing']['billing_first_name'] ) ) {
        $fields['billing']['billing_first_name']['priority'] = 20;
        $fields['billing']['billing_first_name']['class']    = array( 'form-row-last' );
    }

    // ====== Shipping（如果有啟用配送地址）======
    if ( isset( $fields['shipping']['shipping_last_name'] ) ) {
        $fields['shipping']['shipping_last_name']['priority'] = 10;
        $fields['shipping']['shipping_last_name']['class']    = array( 'form-row-first' );
    }

    if ( isset( $fields['shipping']['shipping_first_name'] ) ) {
        $fields['shipping']['shipping_first_name']['priority'] = 20;
        $fields['shipping']['shipping_first_name']['class']    = array( 'form-row-last' );
    }

    return $fields;
}


/**
 * 會員專區：帳單地址欄位
 * 只調整姓名左右，完全不碰地址欄位（避免影響 RY City Select）
 */
add_filter( 'woocommerce_billing_fields', 'qz_myaccount_billing_fields_swap_name', 20 );
function qz_myaccount_billing_fields_swap_name( $fields ) {

    if ( isset( $fields['billing_last_name'] ) ) {
        // 左：姓氏
        $fields['billing_last_name']['priority'] = 10;
        $fields['billing_last_name']['class']    = array( 'form-row-first' );
    }

    if ( isset( $fields['billing_first_name'] ) ) {
        // 右：名字
        $fields['billing_first_name']['priority'] = 20;
        $fields['billing_first_name']['class']    = array( 'form-row-last' );
    }

    return $fields;
}

/**
 * 會員專區：運送地址欄位
 * 同樣只調整姓名
 */
add_filter( 'woocommerce_shipping_fields', 'qz_myaccount_shipping_fields_swap_name', 20 );
function qz_myaccount_shipping_fields_swap_name( $fields ) {

    if ( isset( $fields['shipping_last_name'] ) ) {
        $fields['shipping_last_name']['priority'] = 10;
        $fields['shipping_last_name']['class']    = array( 'form-row-first' );
    }

    if ( isset( $fields['shipping_first_name'] ) ) {
        $fields['shipping_first_name']['priority'] = 20;
        $fields['shipping_first_name']['class']    = array( 'form-row-last' );
    }

    return $fields;
}

