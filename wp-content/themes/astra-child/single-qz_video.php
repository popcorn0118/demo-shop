<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'template_redirect', function () {

    if ( ! is_singular( 'qz_video' ) || is_admin() ) return;

    $video_page = function_exists('get_field') ? ( get_field('video_page', 'option') ?: [] ) : [];
    $enable_single_page = ! empty( $video_page['qz_video_enable_single_page'] );

    if ( $enable_single_page ) return;

    $post_id = get_queried_object_id();
    $youtube_url = function_exists('get_field') ? (string) get_field( 'youtube_url', $post_id ) : '';
    $youtube_url = trim( $youtube_url );

    $redirect_to = $youtube_url ?: get_post_type_archive_link( 'qz_video' );

    if ( $redirect_to && ! headers_sent() ) {
        wp_safe_redirect( $redirect_to, 302 );
        exit;
    }

}, 1 );

add_action( 'astra_entry_content_before', function () {

    if ( ! is_singular( 'qz_video' ) ) return;

    $video_page = function_exists('get_field') ? ( get_field('video_page', 'option') ?: [] ) : [];
    $enable_single_page = ! empty( $video_page['qz_video_enable_single_page'] );
    if ( ! $enable_single_page ) return;

    $post_id = get_the_ID();

    $youtube_url = function_exists('get_field') ? (string) get_field( 'youtube_url', $post_id ) : '';
    $youtube_url = trim( $youtube_url );
    if ( ! $youtube_url ) return;

    // 跟 Elementor 一樣：直接貼 URL，交給 WP oEmbed
    $embed_html = wp_oembed_get( $youtube_url );

    if ( ! $embed_html ) return;

    echo '<div class="qz-video-embed-wrap">';
    echo '<div class="qz-video-embed">';
    echo $embed_html;
    echo '</div>';
    echo '</div>';

}, 5 );

require get_template_directory() . '/single.php';
