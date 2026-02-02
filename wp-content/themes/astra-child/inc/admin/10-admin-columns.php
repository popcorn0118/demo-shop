<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * 通用：所有自訂 CPT 後台列表顯示「分類」
 * - 優先使用結尾為 _cat 的 taxonomy（例如 qz_faq_cat / qz_download_cat）
 * - 若沒有 _cat，就不動（避免亂顯示）
 */
add_action( 'admin_init', function () {

    $pts = get_post_types( [ '_builtin' => false ], 'names' );
    if ( empty($pts) ) return;

    foreach ( $pts as $pt ) {

        $taxes = get_object_taxonomies( $pt, 'names' );
        if ( empty($taxes) ) continue;

        $tax = '';
        foreach ( $taxes as $t ) {
            if ( substr($t, -4) === '_cat' ) { $tax = $t; break; }
        }
        if ( ! $tax || ! taxonomy_exists($tax) ) continue;

        $col_key = 'qz_tax_' . $tax;

        // 欄位（兩種 hook 都掛，避免不同 WP / 外掛環境只吃其中一種）
        $add_columns = function( $columns ) use ( $col_key ) {
            $new = [];
            foreach ( $columns as $k => $label ) {
                $new[$k] = $label;
                if ( $k === 'title' ) $new[$col_key] = '分類';
            }
            return $new;
        };

        add_filter( "manage_{$pt}_posts_columns", $add_columns, 999 );
        add_filter( "manage_edit-{$pt}_columns",  $add_columns, 999 );

        // 內容
        add_action( "manage_{$pt}_posts_custom_column", function( $column, $post_id ) use ( $col_key, $tax ) {
            if ( $column !== $col_key ) return;

            $terms = get_the_terms( $post_id, $tax );
            if ( is_wp_error($terms) || empty($terms) ) { echo '—'; return; }

            $names = wp_list_pluck( $terms, 'name' );
            echo esc_html( implode('、', $names) );
        }, 10, 2 );
    }

});

/**
 * qz_download 專用：補「附件數量」「短代碼」
 * - 你的通用分類欄位已經會加 qz_tax_qz_download_cat
 * - 這裡用 priority 1000，確保在通用欄位之後插入，不互相覆蓋
 */

// 1) 欄位
add_filter( 'manage_qz_download_posts_columns', function( $columns ) {

    $tax_key = 'qz_tax_qz_download_cat'; // 通用邏輯產生的 key（qz_tax_{$tax}）

    $new = [];
    foreach ( $columns as $k => $label ) {
        $new[$k] = $label;

        // 盡量插在「分類」後面；如果沒有分類欄，就插在 title 後面
        if ( $k === $tax_key ) {
            $new['qz_download_files'] = '附件數量';
            $new['qz_download_sc']    = '短代碼';
        } elseif ( $k === 'title' && ! isset( $columns[ $tax_key ] ) ) {
            $new['qz_download_files'] = '附件數量';
            $new['qz_download_sc']    = '短代碼';
        }
    }

    return $new;
}, 1000 );

// 2) 欄位內容
add_action( 'manage_qz_download_posts_custom_column', function( $column, $post_id ) {

    if ( $column === 'qz_download_files' ) {

        if ( ! function_exists('get_field') ) {
            echo '—';
            return;
        }

        $rows = get_field( 'upload_file', $post_id );
        if ( ! is_array($rows) || empty($rows) ) {
            echo '0';
            return;
        }

        $count = 0;
        foreach ( $rows as $r ) {
            if ( ! empty( $r['file'] ) ) $count++;
        }

        echo (int) $count;
        return;
    }

    if ( $column === 'qz_download_sc' ) {
        $sc = sprintf( '[qz_download id="%d"]', (int) $post_id );
        echo '<code style="user-select:all;">' . esc_html($sc) . '</code>';
        return;
    }

}, 10, 2 );
