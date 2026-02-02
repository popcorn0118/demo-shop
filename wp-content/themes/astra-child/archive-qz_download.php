<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Archive: qz_download
 * - 需要 ACF repeater: upload_file
 *   - file (File)
 *   - file_name (Text)
 *   - file_note (Textarea)
 *   - file_sort (Text/Number)
 *   - file_date_override (DateTime picker)
 * - 文章層級: download_require_login (True/False)
 */


/**
 * Bytes → KB/MB/GB
 * ※ 避免跟 functions.php 重複宣告造成 fatal
 */
if ( ! function_exists( 'qz_download_human_filesize' ) ) {
    function qz_download_human_filesize( $bytes ) {
        $bytes = (float) $bytes;
        if ( $bytes <= 0 ) return '—';

        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
        $i = 0;
        while ( $bytes >= 1024 && $i < count($units) - 1 ) {
            $bytes /= 1024;
            $i++;
        }
        // KB 以上顯示 2 位小數
        $decimals = ( $i === 0 ) ? 0 : 2;
        return number_format( $bytes, $decimals ) . ' ' . $units[$i];
    }
}

/**
 * 取「顯示用日期」：優先 max_ts（附件/override 算出來的最新日期），否則回 post_date（文章發布日）
 * ※ 避免跟其他地方重複宣告造成 fatal
 */
if ( ! function_exists( 'qz_download_display_date' ) ) {
    function qz_download_display_date( $post_id, $max_ts = 0 ) {
        $max_ts = (int) $max_ts;
        if ( $max_ts > 0 ) return date_i18n( 'Y-m-d', $max_ts );

        $ts = get_post_time( 'U', false, $post_id ); // post_date（發布日）
        return $ts ? date_i18n( 'Y-m-d', $ts ) : '—';
    }
}

get_header();

// ===== taxonomy (分類) =====
$tax = 'qz_download_cat';
if ( ! taxonomy_exists( $tax ) ) {
    // fallback
    $tax = 'category';
}

$terms = get_terms([
    'taxonomy'   => $tax,
    'hide_empty' => false,
]);

// ===== query =====
$paged = max( 1, (int) get_query_var( 'paged' ) );

$q = new WP_Query([
    'post_type'      => 'qz_download',
    'post_status'    => 'publish',
    'posts_per_page' => -1, // DataTables 前端分頁
    'orderby'        => 'modified',
    'order'          => 'DESC',
]);

?>
<div class="qz-download-archive" aria-label="下載專區">
    <div class="ast-container">
        <div class="qz-download-controls" aria-label="下載專區篩選">
            <div class="qz-download-controls-left">
                <div class="qz-download-length-warp">
                    <label for="qz-download-length" class="qz-download-label">顯示</label>
                    <select id="qz-download-length" class="qz-download-select">
                        <option value="1">1</option>
                        <option value="3">3</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                    </select>
                    <span class="qz-download-label">筆結果</span>
                </div>
            </div>

            <div class="qz-download-controls-right">
                <div class="qz-download-cat-warp">
                    <!-- <label class="qz-download-label" for="qz-download-cat">分類：</label> -->
                    <select id="qz-download-cat" class="qz-download-select">
                        <option value="">全部分類</option>
                        <?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
                            <?php foreach ( $terms as $t ) : ?>
                                <option value="<?php echo esc_attr( $t->name ); ?>"><?php echo esc_html( $t->name ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="qz-download-search-warp">
                    <!-- <label class="qz-download-label" for="qz-download-search">搜尋：</label> -->
                    <input id="qz-download-search" class="qz-download-input" type="search" placeholder="搜尋關鍵字" />
                </div>
            </div>

        </div>

        <table id="qz-download-table" class="qz-download-table display" aria-label="下載列表">
            <thead>
                <tr>
                    <th>標題</th>
                    <th>類別</th>
                    <th>更新日期</th>
                    <th>文件大小</th>
                    <th>下載</th>
                </tr>
            </thead>

            <tbody>
            <?php if ( $q->have_posts() ) : ?>
                <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                    <?php
                    $post_id = get_the_ID();

                    $require_login = false;
                    if ( function_exists('get_field') ) {
                        $require_login = (bool) get_field( 'download_require_login', $post_id );
                    }

                    $rows = [];
                    if ( function_exists('get_field') ) {
                        $rows = get_field( 'upload_file', $post_id );
                    }
                    if ( ! is_array( $rows ) ) $rows = [];

                    $files = [];
                    $total_bytes = 0;

                    // ✅ 用「附件上傳日/override」去算整列最新日期
                    $max_ts = 0;

                    foreach ( $rows as $r ) {
                        // 這兩個 function 你已放在 functions.php（init），這裡直接用
                        if ( ! function_exists('qz_download_normalize_file') ) continue;
                        if ( ! function_exists('qz_download_build_dl_url') ) continue;

                        $norm = qz_download_normalize_file( $r['file'] ?? null );

                        $display_name = trim( (string) ( $r['file_name'] ?? '' ) );
                        if ( $display_name === '' ) $display_name = $norm['name'];

                        $note = trim( (string) ( $r['file_note'] ?? '' ) );
                        $sort = trim( (string) ( $r['file_sort'] ?? '' ) );
                        $sort = is_numeric($sort) ? (int) $sort : 999999;

                        $att_id = (int) $norm['att_id'];

                        // ✅ 日期：優先 override；沒填就用 attachment 上傳日（post_date）
                        $dt_override = trim( (string) ( $r['file_date_override'] ?? '' ) );
                        $file_ts = 0;

                        if ( $dt_override ) {
                            $ts = strtotime( $dt_override );
                            if ( $ts ) $file_ts = (int) $ts;
                        }

                        if ( ! $file_ts && $att_id ) {
                            $ts = get_post_time( 'U', false, $att_id ); // attachment post_date（上傳日）
                            if ( $ts ) $file_ts = (int) $ts;
                        }

                        if ( $file_ts > $max_ts ) $max_ts = $file_ts;

                        $total_bytes += (int) $norm['bytes'];

                        $dl_url = $att_id ? qz_download_build_dl_url( $post_id, $att_id ) : '';

                        $files[] = [
                            'att_id' => $att_id,
                            'dl'    => (string) $dl_url,      // ✅ endpoint
                            'url'   => (string) $norm['url'], // 備援
                            'name'  => (string) $display_name,
                            'note'  => (string) $note,
                            'bytes' => (int) $norm['bytes'],
                            'ext'   => (string) $norm['ext'],
                            'sort'  => (int) $sort,
                            'date'  => $file_ts ? date_i18n( 'Y-m-d', $file_ts ) : '',
                        ];
                    }

                    // 附件排序：file_sort ASC → name ASC
                    usort( $files, function( $a, $b ){
                        if ( $a['sort'] === $b['sort'] ) {
                            return strnatcasecmp( $a['name'], $b['name'] );
                        }
                        return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
                    });

                    $file_count = count( $files );

                    // 類別文字（允許多分類）
                    $cat_names = [];
                    $post_terms = get_the_terms( $post_id, $tax );
                    if ( ! is_wp_error($post_terms) && ! empty($post_terms) ) {
                        foreach ( $post_terms as $pt ) $cat_names[] = $pt->name;
                    }
                    $cat_text = $cat_names ? implode( '、', $cat_names ) : '—';

                    // ✅ 日期：用 max_ts（附件/override 算出來的最新日期）；都無才 fallback 文章發布日
                    $date_text = qz_download_display_date( $post_id, $max_ts );

                    // 大小：單檔→該檔 bytes；多檔→總和
                    $size_text = '—';
                    if ( $file_count === 1 ) {
                        $size_text = qz_download_human_filesize( $files[0]['bytes'] );
                    } elseif ( $file_count > 1 ) {
                        $size_text = qz_download_human_filesize( $total_bytes );
                    }

                    // ✅ 左欄小資訊：文章層級下載次數（endpoint 會累加）
                    $download_count = (int) get_post_meta( $post_id, '_qz_download_count', true );

                    // 右欄按鈕
                    $can_download = ( ! $require_login ) || is_user_logged_in();

                    $row_payload = [
                        'postId'       => $post_id,
                        'requireLogin' => $require_login ? 1 : 0,
                        'fileCount'    => $file_count,
                        'files'        => $files,
                    ];
                    $row_json = wp_json_encode( $row_payload, JSON_UNESCAPED_UNICODE );

                    ?>
                    <tr
                        class="qz-download-row"
                        data-files='<?php echo esc_attr( $row_json ); ?>'
                    >
                        <td class="qz-download-col-title">
                            <!-- <a class="qz-download-post-title" href="<?php //the_permalink(); ?>">
                                <?php //the_title(); ?>
                            </a> -->

                            <div class="qz-download-post-title">
                                <?php if ( (int) $file_count === 1 && ! empty( $files[0]['ext'] ) ) : ?>
                                    <span class="qz-download-filetype" id="qz-<?php echo esc_attr( $files[0]['ext'] ); ?>">
                                        <?php echo esc_html( $files[0]['ext'] ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php the_title(); ?>
                            </div>

                            <div class="qz-download-note">
                                <?php echo $note; ?>
                            </div>

                            <div class="qz-download-meta">
                                <span class="qz-download-meta-item"><?php echo (int) $download_count; ?>次下載</span>
                                <span class="qz-download-meta-item"><?php echo (int) $file_count; ?>支檔案</span>
                            </div>
                        </td>

                        <td class="qz-download-col-cat"><?php echo esc_html( $cat_text ); ?></td>
                        <td class="qz-download-col-date"><?php echo esc_html( $date_text ); ?></td>
                        <td class="qz-download-col-size"><?php echo esc_html( $size_text ); ?></td>

                        <td class="qz-download-col-action">
                            <?php if ( ! $file_count ) : ?>
                                <span class="qz-download-empty">—</span>

                            <?php elseif ( ! $can_download ) : ?>
                                <a class="qz-download-btn qz-download-btn-login"
                                   href="<?php echo esc_url( '/my-account/' ); ?>">
                                    登入後下載
                                </a>

                            <?php elseif ( $file_count === 1 ) : ?>
                                <a class="qz-download-btn qz-download-btn-direct"
                                    href="<?php echo esc_url( $files[0]['dl'] ); ?>">
                                    下載
                                </a>

                            <?php else : ?>
                                <button type="button" class="qz-download-btn qz-download-btn-toggle"
                                        aria-expanded="false">
                                    <!-- <span class="qz-download-caret">▼</span> -->
                                    <span class="qz-download-caret"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="qz-download-footer" aria-label="下載專區分頁">
            <div class="qz-download-footer-left">

                <div id="qz-download-info" class="qz-download-info" aria-live="polite"></div>
            </div>

            <div class="qz-download-footer-right">
                <div id="qz-download-pagination" class="qz-download-pagination"></div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tableEl = document.getElementById('qz-download-table');
    if (!tableEl || typeof window.DataTable === 'undefined') return;

    // DataTables 初始化（不使用內建 length/filter UI，改用我們自己的）
    var dt = new DataTable('#qz-download-table', {
        paging: true,
        pageLength: 10,
        lengthChange: false,
        searching: true,
        ordering: true,
        info: true,
        // 只渲染 table，本頁自建 footer
        dom: 't',

        language: {
            info: '顯示 _START_ 至 _END_ 筆結果，共 _TOTAL_ 筆',
            infoEmpty: '顯示 0 至 0 筆結果，共 0 筆',
            zeroRecords: '沒有符合的結果',
            paginate: {
                previous: '上一頁',
                next: '下一頁'
            }
        },

        // 日期、大小欄位排序容易被字串干擾，更準的話下一步再加 data-order
        columnDefs: [
            { orderable: false, targets: [4] }
        ]
    });

    // ===== 自建 info / pagination 容器 =====
    function renderInfo() {
        var info = dt.page.info();
        var text = '';
        if (info.recordsTotal === 0) {
            text = '顯示 0 至 0 筆結果，共 0 筆';
        } else {
            text = '顯示 ' + (info.start + 1) + ' 至 ' + info.end + ' 筆結果，共 ' + info.recordsTotal + ' 筆';
        }
        document.getElementById('qz-download-info').textContent = text;
    }

    function renderPagination() {
        var info = dt.page.info();
        var container = document.getElementById('qz-download-pagination');
        container.innerHTML = '';

        var totalPages = info.pages || 0;
        var current = info.page || 0; // 0-based

        if (totalPages <= 1) return;

        // 上一頁：有才顯示
        if (current > 0) {
            var prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'qz-download-page-btn';
            prev.textContent = '←';
            prev.addEventListener('click', function(){ dt.page('previous').draw('page'); });
            container.appendChild(prev);
        }

        // 計算 3 顆頁碼範圍（1-based 顯示）
        var start = current - 1;
        var end = current + 1;

        if (start < 0) { start = 0; end = Math.min(2, totalPages - 1); }
        if (end > totalPages - 1) { end = totalPages - 1; start = Math.max(0, end - 2); }

        for (var p = start; p <= end; p++) {
            if (p === current) {
                var cur = document.createElement('span');
                cur.className = 'qz-download-page-current';
                cur.textContent = String(p + 1);
                container.appendChild(cur);
            } else {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'qz-download-page-num';
                btn.textContent = String(p + 1);
                (function(pageIndex){
                    btn.addEventListener('click', function(){
                        dt.page(pageIndex).draw('page');
                    });
                })(p);
                container.appendChild(btn);
            }
        }

        // 下一頁：有才顯示
        if (current < totalPages - 1) {
            var next = document.createElement('button');
            next.type = 'button';
            next.className = 'qz-download-page-btn';
            next.textContent = '→';
            next.addEventListener('click', function(){ dt.page('next').draw('page'); });
            container.appendChild(next);
        }
    }


    dt.on('draw', function () {
        renderInfo();
        renderPagination();
    });

    renderInfo();
    renderPagination();

    // ===== 自建：搜尋 =====
    var searchEl = document.getElementById('qz-download-search');
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            dt.search(this.value || '').draw();
        });
    }

    // ===== 自建：分類（搜尋第 2 欄：類別）=====
    var catEl = document.getElementById('qz-download-cat');
    if (catEl) {
        catEl.addEventListener('change', function () {
            var v = this.value || '';
            // 類別欄位 index = 1
            dt.column(1).search(v, false, true).draw();
        });
    }

    // ===== 自建：筆數 =====
    var lenEl = document.getElementById('qz-download-length');
    if (lenEl) {
        lenEl.addEventListener('change', function () {
            var v = parseInt(this.value, 10);
            if (!v) return;
            dt.page.len(v).draw();
        });
    }

    // ===== 多附件：child row 展開（往下長 tr）=====
    function escapeHtml(str) {
        return String(str || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatChildRow(payload) {
        var files = payload.files || [];

        // 取主表頭欄寬（px）→ child table colgroup 用同寬，才會對齊
        var ths = tableEl.querySelectorAll('thead th');
        var widths = Array.from(ths).map(function(th){
            return Math.round(th.getBoundingClientRect().width);
        });

        var html = '';
        html += '<div class="qz-download-child">';
        html += '  <table class="qz-download-child-table" aria-label="檔案列表">';
        html += '    <colgroup>';
        for (var i = 0; i < widths.length; i++) {
            html += '      <col style="width:' + widths[i] + 'px">';
        }
        html += '    </colgroup>';
        html += '    <tbody>';

        files.forEach(function (f) {
            var name = escapeHtml(f.name || '檔案');
            var ext  = escapeHtml((f.ext || 'FILE').toUpperCase());
            var date = escapeHtml(f.date || '');
            var dl   = escapeHtml(f.dl || '');
            var note   = escapeHtml(f.note || '');
            var size = escapeHtml(f.bytes ? (Math.round((f.bytes/1024)*100)/100) + ' KB' : '—');

            // 子項目不一定有分類，先留空（要帶父分類也行再跟我說）
            var cat  = '';

            html += '      <tr class="qz-download-child-tr">';
            html += '        <td class="qz-download-col-title" colspan="2">';
            html += '          <span id="qz-' + ext + '" class="qz-download-child-ext">' + ext + '</span>';
            html += '          <div class="qz-download-child-warp">';
            html += '          <span class="qz-download-child-name">' + name + '</span>';
            html += '          <span class="qz-download-child-note">' + note + '</span>';
            html += '          </div>';
            html += '        </td>';
            html += '        <td class="qz-download-col-cat">' + cat + '</td>';
            html += '        <td class="qz-download-col-date">' + date + '</td>';
            html += '        <td class="qz-download-col-size">' + size + '</td>';
            html += '        <td class="qz-download-col-action"><a class="qz-download-child-link" href="' + dl + '">下載</a></td>';
            html += '      </tr>';
        });

        html += '    </tbody>';
        html += '  </table>';
        html += '</div>';

        return html;
    }

    tableEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.qz-download-btn-toggle');
        if (!btn) return;

        var tr = e.target.closest('tr');
        if (!tr) return;

        var row = dt.row(tr);
        var isShown = row.child.isShown();

        // 收合
        if (isShown) {
            row.child.hide();
            tr.classList.remove('is-open');
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            return;
        }

        // 展開
        var payloadRaw = tr.getAttribute('data-files') || '{}';
        var payload = {};
        try { payload = JSON.parse(payloadRaw); } catch (err) {}

        row.child(formatChildRow(payload), 'qz-download-child-row').show();
        tr.classList.add('is-open');
        btn.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
    });

});
</script>

<?php
get_footer();
