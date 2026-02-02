<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin assets
 */
add_action( 'admin_enqueue_scripts', function () {

    $ver = defined('CHILD_THEME_ASTRA_CHILD_VERSION') ? CHILD_THEME_ASTRA_CHILD_VERSION : '1.0.0';
    $uri = get_stylesheet_directory_uri();

    wp_enqueue_style( 'astra-child-admin-css', $uri . '/assets/css/admin.css', [], $ver );

}, 20 );
