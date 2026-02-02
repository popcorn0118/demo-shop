<?php
/* =================================
  文章列表側欄(分類) 短代碼
  [article_category_dropdown]
 * ================================== */

function qz_article_category_dropdown_shortcode( $atts ) {

    // 沒開側邊欄就不輸出（Astra）
    if ( function_exists( 'astra_page_layout' ) ) {
        $layout = astra_page_layout();
        if ( is_string( $layout ) && strpos( $layout, 'no-sidebar' ) !== false ) {
            return '';
        }
    }

    // CPT -> taxonomy 對照：從 15-qz-cpt.php 共用 registry 取
    $map = function_exists( 'qz_content_pt_tax_map' ) ? qz_content_pt_tax_map() : array();

    $pt  = '';
    $tax = '';

    foreach ( $map as $post_type => $taxonomy ) {
        if (
            is_post_type_archive( $post_type ) ||
            is_singular( $post_type ) ||
            is_tax( $taxonomy )
        ) {
            $pt  = $post_type;
            $tax = $taxonomy;
            break;
        }
    }

    $is_cpt = ( $pt && $tax );

    // 文章分類：排除 announcements
    $exclude_id = 0;
    if ( ! $is_cpt ) {
        $exclude_term = get_term_by( 'slug', 'announcements', 'category' );
        $exclude_id   = ( $exclude_term && ! is_wp_error( $exclude_term ) ) ? $exclude_term->term_id : 0;
    }

    $taxonomy = $is_cpt ? $tax : 'category';

    $terms_args = array(
        'taxonomy'   => $taxonomy,
        'parent'     => 0,
        'hide_empty' => true,
    );

    if ( $exclude_id ) {
        $terms_args['exclude'] = array( $exclude_id );
    }

    $items = get_terms( $terms_args );
    if ( is_wp_error( $items ) ) {
        return '';
    }

    // 標題 & 全部分類連結
    if ( $is_cpt ) {
        $obj          = get_post_type_object( $pt );
        $title        = $obj ? ( $obj->labels->name . '分類' ) : '分類';
        $all_url      = get_post_type_archive_link( $pt );
        $is_all_page  = is_post_type_archive( $pt ) || is_singular( $pt );
        $current_name = ( is_tax( $tax ) && ( $t = get_queried_object() ) && ! empty( $t->name ) ) ? $t->name : '全部分類';
    } else {
        $title   = '文章分類';
        $all_url = home_url( 'article' );

        if ( is_category() ) {
            $t            = get_queried_object();
            $current_name = $t->name;
            $is_all_page  = false;
        } else {
            $slug         = get_post_field( 'post_name', get_queried_object_id() ); // 'article'
            $current_name = ( $slug === 'article' ) ? '全部分類' : '選擇分類';
            $is_all_page  = ( $slug === 'article' );
        }
    }

    $total_count = array_sum( wp_list_pluck( $items, 'count' ) );

    ob_start();
    ?>
    <div class="warp dropdown-warp category">
      <h2 class="title"><?php echo esc_html( $title ); ?></h2>

      <div class="dropdown-title">
        <?php echo esc_html( $current_name ); ?>
        <div class="dropdown-btn">
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 300 300" style="enable-background:new 0 0 300 300;" xml:space="preserve">
            <g class="line-1"><path d="M278.6,171.4H21.4C8.6,171.4,0,162.9,0,150s8.6-21.4,21.4-21.4h257.1c12.9,0,21.4,8.6,21.4,21.4S291.4,171.4,278.6,171.4z"/></g>
            <g class="line-2"><path d="M150,300c-12.9,0-21.4-8.6-21.4-8.6V21.4C128.6,8.6,137.1,0,150,0s21.4,8.6,21.4,21.4v257.1
                C171.4,291.4,162.9,300,150,300z"/></g>
            </svg>
        </div>
      </div>

      <div class="dropdown-list">
        <ul>
          <li class="item<?php echo ( $is_all_page ? ' active' : '' ); ?>">
            <a href="<?php echo esc_url( $all_url ); ?>">
              <span class="name">全部分類</span>
              <span class="count"><?php echo (int) $total_count; ?></span>
            </a>
          </li>

          <?php foreach ( $items as $item ) : ?>
            <?php
              $active = $is_cpt ? is_tax( $tax, $item->term_id ) : is_category( $item->term_id );
            ?>
            <li class="item<?php echo ( $active ? ' active' : '' ); ?>">
              <a href="<?php echo esc_url( get_term_link( $item ) ); ?>">
                <span class="name"><?php echo esc_html( $item->name ); ?></span>
                <span class="count"><?php echo (int) $item->count; ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode( 'article_category_dropdown', 'qz_article_category_dropdown_shortcode' );
