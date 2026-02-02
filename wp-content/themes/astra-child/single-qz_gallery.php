<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'astra_entry_content_before', function () {

    if ( ! is_singular( 'qz_gallery' ) ) return;

    $post_id = get_the_ID();

    // 盡量撈出你可能用的 ACF 圖庫欄位（圖庫必填）
    $images = [];
    if ( function_exists( 'get_field' ) ) {
        foreach ( [ 'photo_gallery', 'qz_gallery', 'gallery' ] as $field_key ) {
            $val = get_field( $field_key, $post_id );
            if ( is_array( $val ) && ! empty( $val ) ) {
                $images = $val;
                break;
            }
        }
    }

    if ( empty( $images ) ) return;

    $group = 'qz-gallery-' . $post_id;

    echo '<div class="qz-gallery-grid-wrap">';
    echo '<div class="qz-gallery-grid">';

    foreach ( $images as $img ) {

        // ACF gallery 可能回傳 array / id
        $img_id  = 0;
        $img_alt = '';

        if ( is_numeric( $img ) ) {
            $img_id = (int) $img;
        } elseif ( is_array( $img ) ) {
            if ( ! empty( $img['ID'] ) ) {
                $img_id  = (int) $img['ID'];
                $img_alt = isset($img['alt']) ? (string) $img['alt'] : '';
            } elseif ( ! empty( $img['id'] ) ) {
                $img_id  = (int) $img['id'];
                $img_alt = isset($img['alt']) ? (string) $img['alt'] : '';
            }
        }

        if ( ! $img_id ) continue;

        // 顯示用縮圖
        $thumb_html = wp_get_attachment_image( $img_id, 'medium', false, [
            'alt'      => $img_alt ? esc_attr($img_alt) : '',
            'loading'  => 'lazy',
            'decoding' => 'async',
        ] );

        // 燈箱用大圖
        $full = wp_get_attachment_image_url( $img_id, 'large' );
        if ( ! $full ) continue;

        // FsLightbox Pro：thumb + caption（照你列表的邏輯）
        $thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
        if ( ! $thumb_url ) {
            $thumb_url = wp_get_attachment_image_url( $img_id, 'medium' );
        }

        $att_title    = get_the_title( $img_id );
        $att_caption  = wp_get_attachment_caption( $img_id );
        $caption_html = '';

        if ( $att_title || $att_caption ) {
            if ( $att_title ) {
                $caption_html .= '<h2>' . esc_html( $att_title ) . '</h2>';
            }
            if ( $att_caption ) {
                $caption_html .= '<h3>' . esc_html( $att_caption ) . '</h3>';
            }
        }

        echo '<a class="qz-gallery-photo"'
            . ' href="' . esc_url( $full ) . '"'
            . ' data-fslightbox="' . esc_attr( $group ) . '"'
            . ' data-elementor-open-lightbox="no"'
            . ' data-title="' . esc_attr( $att_title ) . '"';

        if ( $thumb_url ) {
            echo ' data-thumb="' . esc_url( $thumb_url ) . '"';
        }
        if ( $caption_html ) {
            echo ' data-caption="' . esc_attr( $caption_html ) . '"';
        }

        echo '>';
        echo $thumb_html;
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';

}, 5 );

// 讓 qz_gallery 單頁沿用 Astra 預設 single.php（跟文章內頁同結構）
require get_template_directory() . '/single.php';
