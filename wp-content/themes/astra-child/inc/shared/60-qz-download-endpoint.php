<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 下載專區的下載次數(產生 endpoint 下載網址)
add_action('wp_ajax_qz_download_file', 'qz_download_file_endpoint');
add_action('wp_ajax_nopriv_qz_download_file', 'qz_download_file_endpoint');

function qz_download_file_endpoint() {

    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    $att_id  = isset($_GET['att_id'])  ? (int) $_GET['att_id']  : 0;

    if ( ! $post_id || ! $att_id ) {
        wp_die('Bad request', 400);
    }

    // 只允許下載 CPT
    if ( get_post_type($post_id) !== 'qz_download' ) {
        wp_die('Not allowed', 403);
    }

    // 需登入下載
    $require_login = false;
    if ( function_exists('get_field') ) {
        $require_login = (bool) get_field('download_require_login', $post_id);
    }
    if ( $require_login && ! is_user_logged_in() ) {
        wp_die('Login required', 403);
    }

    // 驗證：這個 att_id 必須存在於該篇文章的 upload_file repeater
    $valid = false;
    if ( function_exists('get_field') ) {
        $rows = get_field('upload_file', $post_id);
        if ( is_array($rows) ) {
            foreach ( $rows as $r ) {
                $f = $r['file'] ?? null;
                if ( is_array($f) && ! empty($f['ID']) && (int)$f['ID'] === $att_id ) { $valid = true; break; }
                if ( is_numeric($f) && (int)$f === $att_id ) { $valid = true; break; }
            }
        }
    }
    if ( ! $valid ) {
        wp_die('File not allowed', 403);
    }

    $file_path = get_attached_file($att_id);
    if ( ! $file_path || ! file_exists($file_path) ) {
        wp_die('File not found', 404);
    }

    // 下載次數（文章層級）
    $count = (int) get_post_meta($post_id, '_qz_download_count', true);
    update_post_meta($post_id, '_qz_download_count', $count + 1);

    // 強制下載（Firefox PDF 不會 inline）
    $filename = basename($file_path);
    $mime     = get_post_mime_type($att_id) ?: 'application/octet-stream';

    nocache_headers();
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file_path));

    while ( ob_get_level() ) { ob_end_clean(); }
    readfile($file_path);
    exit;
}
