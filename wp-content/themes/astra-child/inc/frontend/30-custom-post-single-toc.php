<?php
/* =================================
 *  文章單頁側欄(目錄) 短代碼
 * [article_toc]
 * ================================== */

function qz_article_toc_shortcode( $atts ) {

    // 只有「文章(post)單頁」才載入
    if ( is_admin() || ! is_singular( 'post' ) ) {
        return '';
    }

    // 沒開側邊欄就不輸出（Astra）
    if ( function_exists( 'astra_page_layout' ) ) {
        $layout = astra_page_layout();
        if ( is_string( $layout ) && strpos( $layout, 'no-sidebar' ) !== false ) {
            return '';
        }
    }

    // 沒裝 ez-toc 或短代碼不存在就不輸出
    if ( ! shortcode_exists( 'ez-toc' ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="ph-toc ph-toc-desk">
      <div class="ph-toc-desk-warp">
        <?php echo do_shortcode( '[ez-toc]' ); ?>
      </div>
    </div>

    <div class="ph-toc ph-toc-mobile">
      <button id="ph-toc-fab" aria-controls="ph-toc-modal" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
          <path d="M6 6H4v2h2V6zm14 0H8v2h12V6zM4 11h2v2H4v-2zm16 0H8v2h12v-2zM4 16h2v2H4v-2zm16 0H8v2h12v-2z" fill="currentColor"></path>
        </svg>
      </button>
      <div id="ph-toc-overlay"></div>
      <div id="ph-toc-modal" role="dialog" aria-modal="true">
        <div class="ph-toc-head">
          <div class="left">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
              <path d="M6 6H4v2h2V6zm14 0H8v2h12V6zM4 11h2v2H4v-2zm16 0H8v2h12v-2zM4 16h2v2H4v-2zm16 0H8v2h12v-2z" fill="currentColor"></path>
            </svg>
            <strong>文章目錄</strong>
          </div>
          <button class="ph-toc-close" aria-label="關閉">×</button>
        </div>
        <div class="ph-toc-body">
          <?php echo do_shortcode( '[ez-toc header_label="文章目錄" toggle_view="no"]' ); ?>
        </div>
      </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode( 'article_toc', 'qz_article_toc_shortcode' );
