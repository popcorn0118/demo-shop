<?php
/**
 * astra-child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package astra-child
 * @since 1.0.0
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Child Theme bootstrap
 * - 分前台 / 後台載入
 *   inc/
 *     shared/   00-*.php + [num]-*.php  (前後台都需要的 function/class)
 *     frontend/ 00-*.php + [num]-*.php  (只前台)
 *     admin/    00-*.php + [num]-*.php  (只後台)
 */
add_action( 'after_setup_theme', function () {

    $base = trailingslashit( get_stylesheet_directory() ) . 'inc/';

    $load_group = function( $dir ) {

        if ( ! is_dir( $dir ) ) {
            return;
        }

        // 先載入 00- 系列
        foreach ( glob( $dir . '/00-*.php' ) as $file ) {
            require_once $file;
        }

        // 再載入其他有編號的檔案
        foreach ( glob( $dir . '/*.php' ) as $file ) {
            $basename = basename( $file );

            if ( strpos( $basename, '00-' ) === 0 ) {
                continue;
            }

            if ( ! preg_match( '/^[0-9]+-/', $basename ) ) {
                continue;
            }

            require_once $file;
        }
    };

    // shared：永遠載
    $load_group( rtrim( $base, '/' ) . '/shared' );

    // admin / frontend 分流
    if ( is_admin() ) {
        $load_group( rtrim( $base, '/' ) . '/admin' );
    } else {
        $load_group( rtrim( $base, '/' ) . '/frontend' );
    }

}, 20 );
