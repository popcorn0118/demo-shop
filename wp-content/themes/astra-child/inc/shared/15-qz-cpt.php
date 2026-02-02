<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * 共用：內容中心註冊表（CPT + Taxonomy）
 * 之後新增/調整只動這裡
 * 
 */
function qz_content_registry() {
    return apply_filters( 'qz_content_registry', [
        [
            'pt'           => 'qz_faq',
            'pt_label'     => '常見問題',
            'icon'         => 'dashicons-editor-help',
            'archive_slug' => 'faq',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_faq_cat',
                'tax_label'    => '常見問題分類',
                'tax_slug'     => 'faq-category',
                'hierarchical' => true,
            ],
        ],
        [
            'pt'           => 'qz_gallery',
            'pt_label'     => '相簿專區',
            'icon'         => 'dashicons-format-gallery',
            'archive_slug' => 'gallery',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_gallery_cat',
                'tax_label'    => '相簿分類',
                'tax_slug'     => 'gallery-category',
                'hierarchical' => true,
            ],
        ],
        [
            'pt'           => 'qz_video',
            'pt_label'     => '影音專區',
            'icon'         => 'dashicons-video-alt3',
            'archive_slug' => 'video',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_video_cat',
                'tax_label'    => '影音分類',
                'tax_slug'     => 'video-category',
                'hierarchical' => true,
            ],
        ],
        [
            'pt'           => 'qz_download',
            'pt_label'     => '下載專區',
            'icon'         => 'dashicons-download',
            'archive_slug' => 'download',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_download_cat',
                'tax_label'    => '下載分類',
                'tax_slug'     => 'download-category',
                'hierarchical' => true,
            ],
        ],
        [
            'pt'           => 'qz_team',
            'pt_label'     => '團隊介紹',
            'icon'         => 'dashicons-groups',
            'archive_slug' => 'team',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_team_cat',
                'tax_label'    => '團隊分類',
                'tax_slug'     => 'team-category',
                'hierarchical' => true,
            ],
        ],
        [
            'pt'           => 'qz_case',
            'pt_label'     => '案例介紹',
            'icon'         => 'dashicons-portfolio',
            'archive_slug' => 'case',
            'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
            'tax'          => [
                'tax'          => 'qz_case_cat',
                'tax_label'    => '案例分類',
                'tax_slug'     => 'case-category',
                'hierarchical' => true,
            ],
        ],
    ] );
}

/**
 * 共用：CPT => taxonomy 對照（給其他檔案用）
 */
function qz_content_pt_tax_map() {
    $map = [];
    foreach ( qz_content_registry() as $cfg ) {
        if ( ! empty( $cfg['pt'] ) && ! empty( $cfg['tax']['tax'] ) ) {
            $map[ $cfg['pt'] ] = $cfg['tax']['tax'];
        }
    }
    return $map;
}

/**
 * 註冊 CPT / Taxonomy
 */
add_action( 'init', function () {

    foreach ( qz_content_registry() as $cfg ) {

        $pt           = $cfg['pt'] ?? '';
        $pt_label     = $cfg['pt_label'] ?? '';
        $icon         = $cfg['icon'] ?? 'dashicons-admin-post';
        $archive_slug = $cfg['archive_slug'] ?? $pt;
        $supports     = $cfg['supports'] ?? [ 'title', 'editor' ];

        if ( ! $pt || ! $pt_label ) {
            continue;
        }

        // CPT
        if ( ! post_type_exists( $pt ) ) {

            register_post_type( $pt, [
                'labels' => [
                    'name'               => $pt_label,
                    'singular_name'      => $pt_label,
                    'menu_name'          => $pt_label,
                    'add_new'            => '新增',
                    'add_new_item'       => '新增' . $pt_label,
                    'edit_item'          => '編輯' . $pt_label,
                    'new_item'           => '新增' . $pt_label,
                    'view_item'          => '查看' . $pt_label,
                    'search_items'       => '搜尋' . $pt_label,
                    'not_found'          => '找不到' . $pt_label,
                    'not_found_in_trash' => '回收桶內找不到' . $pt_label,
                    'all_items'          => '所有' . $pt_label,
                ],
                'public'       => true,
                'show_in_rest' => true,
                'menu_icon'    => $icon,
                'supports'     => $supports,
                'has_archive'  => true,
                'rewrite'      => [ 'slug' => $archive_slug, 'with_front' => false ],
            ] );
        }

        // Taxonomy
        $tax_cfg = $cfg['tax'] ?? [];
        $tax     = $tax_cfg['tax'] ?? '';

        if ( $tax && ! taxonomy_exists( $tax ) ) {

            $tax_label    = $tax_cfg['tax_label'] ?? ( $pt_label . '分類' );
            $tax_slug     = $tax_cfg['tax_slug'] ?? $tax;
            $hierarchical = isset( $tax_cfg['hierarchical'] ) ? (bool) $tax_cfg['hierarchical'] : true;

            register_taxonomy( $tax, [ $pt ], [
                'labels' => [
                    'name'          => $tax_label,
                    'singular_name' => $tax_label,
                    'menu_name'     => $tax_label,
                    'all_items'     => '所有分類',
                    'edit_item'     => '編輯分類',
                    'view_item'     => '查看分類',
                    'update_item'   => '更新分類',
                    'add_new_item'  => '新增分類',
                    'new_item_name' => '新分類名稱',
                    'search_items'  => '搜尋分類',
                ],
                'public'       => true,
                'show_in_rest' => true,
                'hierarchical' => $hierarchical,
                'rewrite'      => [ 'slug' => $tax_slug, 'with_front' => false ],
            ] );
        }
    }

}, 10 );

/**
 * Rank Math breadcrumb：在 CPT 單頁/分類頁插入「CPT 列表」層級
 */
add_filter( 'rank_math/frontend/breadcrumb/items', function( $crumbs, $class ) {

    foreach ( qz_content_registry() as $cfg ) {

        $pt  = $cfg['pt'] ?? '';
        $tax = $cfg['tax']['tax'] ?? '';

        if ( ! $pt ) continue;

        // 只處理：CPT 單頁 + CPT taxonomy 頁
        if ( ! is_singular( $pt ) && ( ! $tax || ! is_tax( $tax ) ) ) {
            continue;
        }

        $obj = get_post_type_object( $pt );
        if ( ! $obj ) return $crumbs;

        $archive_link = get_post_type_archive_link( $pt );
        if ( ! $archive_link ) return $crumbs;

        // 避免重複插入
        foreach ( $crumbs as $c ) {
            if ( isset( $c[1] ) && $c[1] === $archive_link ) {
                return $crumbs;
            }
        }

        // 插在「首頁」後面
        array_splice( $crumbs, 1, 0, [ [ $obj->labels->name, $archive_link ] ] );

        return $crumbs;
    }

    return $crumbs;

}, 10, 2 );
