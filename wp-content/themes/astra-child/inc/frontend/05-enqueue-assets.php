<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

    $ver = defined('CHILD_THEME_ASTRA_CHILD_VERSION') ? CHILD_THEME_ASTRA_CHILD_VERSION : '1.0.0';
    $uri = get_stylesheet_directory_uri();

    // CSS
    wp_enqueue_style( 'slick-css', $uri . '/assets/css/slick.css', [], $ver );
    wp_enqueue_style( 'astra-child-theme-css', $uri . '/style.css', ['astra-theme-css'], $ver, 'all' );

    // JS（slick 需要 jquery）
    wp_enqueue_script( 'slick-js', $uri . '/assets/js/slick.min.js', ['jquery'], $ver, true );
    wp_enqueue_script( 'main', $uri . '/assets/js/main.js', ['jquery'], $ver, true );
    wp_enqueue_script( 'custom-products-sidebar', $uri . '/assets/js/custom-products-sidebar.js', ['jquery'], $ver, true );

    // 文章單頁
    if ( is_single() ) {
        wp_enqueue_script( 'toc-script', $uri . '/assets/js/toc.js', ['jquery'], $ver, true );
    }

    // 常見問題
    if ( is_post_type_archive('qz_faq') || is_tax('qz_faq_cat') ) {
        wp_enqueue_script( 'faq-script', $uri . '/assets/js/faq.js', ['jquery'], $ver, true );
    }
    
    // 相簿專區
    if ( is_post_type_archive('qz_gallery') || is_tax('qz_gallery_cat') || is_singular( 'qz_gallery' ) ) {
        // qz_enqueue_swiper();
        qz_enqueue_fslightbox();
    }

    // 影片專區
    if ( is_post_type_archive('qz_video') || is_tax('qz_video_cat') ) {
        wp_enqueue_script( 'video-script', $uri . '/assets/js/video.js', ['jquery'], $ver, true );
    }

    // 下載專區
    if ( is_post_type_archive('qz_download') || is_tax('qz_download_cat') ) {
        wp_enqueue_style( 'dataTables-css', $uri . '/assets/css/dataTables.dataTables.css', [], $ver );
        wp_enqueue_script( 'dataTables-script', $uri . '/assets/js/dataTables.js', ['jquery'], $ver, true );
    }
}

/**
 * Swiper 共用載入器：沿用 Elementor 已註冊的 swiper（避免重複載入）
 */
function qz_enqueue_swiper() {

    $ver = defined('CHILD_THEME_ASTRA_CHILD_VERSION') ? CHILD_THEME_ASTRA_CHILD_VERSION : '1.0.0';
    $uri = get_stylesheet_directory_uri();

    if ( wp_style_is('swiper', 'registered') ) {
        wp_enqueue_style('swiper');
    }

    if ( wp_script_is('swiper', 'registered') ) {
        wp_enqueue_script('swiper');
    }

    wp_enqueue_script(
        'qz-swiper',
        $uri . '/assets/js/swiper.js',
        [ 'swiper' ],
        $ver,
        true
    );
}
/**
 * fslightbox 
 */
function qz_enqueue_fslightbox() {

    $ver = defined('CHILD_THEME_ASTRA_CHILD_VERSION') ? CHILD_THEME_ASTRA_CHILD_VERSION : '1.0.0';
    $uri = get_stylesheet_directory_uri();

    // 原廠：只載一份（建議 min）
    wp_enqueue_script(
        'fslightbox',
        $uri . '/assets/js/fslightbox.js',
        [],
        $ver,
        true
    );

    // 你自己寫的初始化（依賴原廠）
    wp_enqueue_script(
        'fslightbox-main',
        $uri . '/assets/js/fslightbox.main.js',
        [ 'fslightbox' ],
        $ver,
        true
    );
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
