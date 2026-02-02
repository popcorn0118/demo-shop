<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin list columns: qz_download
 * - 分類（qz_download_cat）
 * - 附件數量（ACF repeater: upload_file）
 * - 短代碼（[qz_download id="123"]）
 */

// 1) 欄位
add_filter( 'manage_qz_download_posts_columns', function( $columns ) {

    // 保留 checkbox / 標題 / 日期，插入自訂欄位
    $new = [];

    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;

        if ( $key === 'title' ) {
            $new['qz_download_cat']   = '分類';
            $new['qz_download_files'] = '附件數量';
            $new['qz_download_sc']    = '短代碼';
        }
    }

    return $new;
}, 20 );

// 2) 欄位內容
add_action( 'manage_qz_download_posts_custom_column', function( $column, $post_id ) {

    if ( $column === 'qz_download_cat' ) {

        $tax = taxonomy_exists('qz_download_cat') ? 'qz_download_cat' : 'category';
        $terms = get_the_terms( $post_id, $tax );

        if ( is_wp_error($terms) || empty($terms) ) {
            echo '—';
            return;
        }

        $names = wp_list_pluck( $terms, 'name' );
        echo esc_html( implode('、', $names) );
        return;
    }

    if ( $column === 'qz_download_files' ) {

        // 沒裝 ACF 或沒設定 repeater
        if ( ! function_exists('get_field') ) {
            echo '—';
            return;
        }

        $rows = get_field( 'upload_file', $post_id );
        if ( ! is_array($rows) || empty($rows) ) {
            echo '0';
            return;
        }

        // 只算「真的有選檔案」的行
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


/**
 * Admin list columns: qz_download
 * 輸出短代碼的html
 */
add_action('init', function () {

    // 若 archive 檔內已經有同名 function，就不要重複宣告
    if ( ! function_exists('qz_download_build_dl_url') ) {
        function qz_download_build_dl_url( $post_id, $att_id ) {
            $post_id = (int) $post_id;
            $att_id  = (int) $att_id;
            if ( ! $post_id || ! $att_id ) return '';

            return add_query_arg(
                [
                    'action'  => 'qz_download_file',
                    'post_id' => $post_id,
                    'att_id'  => $att_id,
                ],
                admin_url( 'admin-ajax.php' )
            );
        }
    }

    // Bytes → KB/MB/GB（只在這段需要用到檔案大小）
    if ( ! function_exists('qz_download_human_filesize') ) {
        function qz_download_human_filesize( $bytes ) {
            $bytes = (float) $bytes;
            if ( $bytes <= 0 ) return '—';

            $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
            $i = 0;
            while ( $bytes >= 1024 && $i < count($units) - 1 ) {
                $bytes /= 1024;
                $i++;
            }
            $decimals = ( $i === 0 ) ? 0 : 2;
            return number_format( $bytes, $decimals ) . ' ' . $units[$i];
        }
    }

    if ( ! function_exists('qz_download_normalize_file') ) {
        function qz_download_normalize_file( $acf_file ) {
            $out = [
                'url'    => '',
                'name'   => '',
                'bytes'  => 0,
                'ext'    => '',
                'att_id' => 0,
            ];

            if ( empty( $acf_file ) ) return $out;

            if ( is_array( $acf_file ) ) {
                $out['url']    = isset($acf_file['url']) ? (string) $acf_file['url'] : '';
                $out['name']   = isset($acf_file['filename']) ? (string) $acf_file['filename'] : '';
                $out['bytes']  = isset($acf_file['filesize']) ? (int) $acf_file['filesize'] : 0;
                $out['att_id'] = isset($acf_file['ID']) ? (int) $acf_file['ID'] : 0;
            } elseif ( is_numeric( $acf_file ) ) {
                $att_id = (int) $acf_file;
                $out['att_id'] = $att_id;
                $out['url']    = wp_get_attachment_url( $att_id );
                $out['name']   = basename( (string) $out['url'] );
                $path = get_attached_file( $att_id );
                if ( $path && file_exists( $path ) ) $out['bytes'] = (int) filesize( $path );
            } else {
                $out['url']  = (string) $acf_file;
                $out['name'] = basename( (string) $acf_file );
            }

            $ext = '';
            if ( ! empty( $out['name'] ) ) {
                $ext = pathinfo( $out['name'], PATHINFO_EXTENSION );
            } elseif ( ! empty( $out['url'] ) ) {
                $ext = pathinfo( parse_url( $out['url'], PHP_URL_PATH ), PATHINFO_EXTENSION );
            }
            $out['ext'] = strtoupper( (string) $ext );

            return $out;
        }
    }

    // shortcode：已存在就不要重複註冊
    if ( shortcode_exists('qz_download') ) return;

    add_shortcode('qz_download', function ($atts) {

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'qz_download');

        $post_id = (int) $atts['id'];
        if ( ! $post_id ) return '';

        if ( get_post_type($post_id) !== 'qz_download' ) return '';

        if ( ! function_exists('get_field') ) return ''; // 你這頁依賴 ACF，沒 ACF 就不輸出

        $require_login = (bool) get_field('download_require_login', $post_id);
        if ( $require_login && ! is_user_logged_in() ) {
            // 你要固定 /my-account/ 也行，但這樣比較通用
            $login_url = wp_login_url( get_permalink() );
            return '<a class="qz-download-btn qz-download-btn-login" href="' . esc_url($login_url) . '">登入後下載</a>';
        }

        $rows = get_field('upload_file', $post_id);
        if ( ! is_array($rows) || empty($rows) ) return '';

        $files = [];
        foreach ( $rows as $r ) {
            $norm = qz_download_normalize_file( $r['file'] ?? null );
            $att_id = (int) ($norm['att_id'] ?? 0);
            if ( ! $att_id ) continue;

            $name = trim( (string) ($r['file_name'] ?? '') );
            if ( $name === '' ) $name = (string) ($norm['name'] ?? '');

            $dl = qz_download_build_dl_url( $post_id, $att_id );
            if ( ! $dl ) continue;

            $sort = trim( (string) ($r['file_sort'] ?? '') );
            $sort = is_numeric($sort) ? (int) $sort : 999999;

            // 日期：有填 override 就用；沒填就用「附件上傳日」
            $date = '';
            $dt_override = trim( (string) ($r['file_date_override'] ?? '') );
            if ( $dt_override ) {
                $ts = strtotime( $dt_override );
                if ( $ts ) $date = date_i18n( 'Y-m-d', $ts );
            }
            if ( ! $date ) {
                $date = get_the_date( 'Y-m-d', $att_id ); // attachment post_date（上傳日）
            }

            $bytes = (int) ($norm['bytes'] ?? 0);
            $size  = qz_download_human_filesize( $bytes );

            $files[] = [
                'dl'    => $dl,
                'name'  => $name ?: '檔案',
                'ext'   => (string) ($norm['ext'] ?? 'FILE'),
                'sort'  => $sort,
                'size'  => $size,
                'date'  => $date,
            ];
        }

        if ( empty($files) ) return '';

        usort($files, function($a, $b){
            if ( $a['sort'] === $b['sort'] ) return strnatcasecmp($a['name'], $b['name']);
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        // === 輸出：icon 標題（檔案大小） 日期（不使用 ul/li，避免 •）
        $html  = '<div class="qz-download-shortcode">';
        $html .= '<div class="qz-download-shortcode-list">';

        foreach ($files as $f) {
            $html .= '<div class="qz-download-shortcode-item">';
            $html .=   '<span class="qz-download-shortcode-icon">' . esc_html($f['ext']) . '</span>';
            $html .=   '<a class="qz-download-shortcode-title" href="' . esc_url($f['dl']) . '">'
                    .      esc_html($f['name'])
                    .      ' <span class="qz-download-shortcode-size">(' . esc_html($f['size']) . ')</span>'
                    .   '</a>';
            $html .=   '<span class="qz-download-shortcode-date">' . esc_html($f['date']) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    });

});

