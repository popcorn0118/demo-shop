<?php
/* =================================
 *  單頁 - 上/下篇 + 相關文章
 *  Hook：astra_footer_before
 * ================================== */

add_action( 'astra_footer_before', 'qz_single_post_extras_before_footer' );
function qz_single_post_extras_before_footer() {

    if ( ! is_singular() ) return;

    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );

    // ===== 上/下頁：所有註冊內容都要顯示（含 post + registry CPT）=====
    $nav_cfg = qz_get_single_nav_cfg( $post_type );

    if ( $nav_cfg ) {

        ob_start();
        ?>
        <section class="ph-post-extras ph-bleed" aria-label="Post extras">
          <div class="ph-extras-inner">

            <nav class="ph-post-nav" aria-label="文章導覽">
              <div class="ast-container">
                <div class="ph-post-nav-grid">
                  <?php if ( get_previous_post() ) : ?>
                    <div class="nav-prev">
                      <?php previous_post_link(
                          '%link',
                          '<span class="text"><i></i>' . esc_html( $nav_cfg['prev_label'] ) . '</span><span class="title">%title</span>'
                      ); ?>
                    </div>
                  <?php endif; ?>

                  <div class="nav-center">
                    <a class="nav-back"
                       href="<?php echo esc_url( $nav_cfg['list_url'] ); ?>"
                       rel="up"
                       aria-label="<?php echo esc_attr( $nav_cfg['back_aria'] ); ?>">
                      <span class="icon" aria-hidden="true">
                        <svg clip-rule="evenodd" fill-rule="evenodd" height="512" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 32 32" width="512" xmlns="http://www.w3.org/2000/svg"><g id="Icon"><path d="m15 18c0-.552-.448-1-1-1h-7c-.552 0-1 .448-1 1v7c0 .552.448 1 1 1h7c.552 0 1-.448 1-1zm11 0c0-.552-.448-1-1-1h-7c-.552 0-1 .448-1 1v7c0 .552.448 1 1 1h7c.552 0 1-.448 1-1zm0-11c0-.552-.448-1-1-1h-7c-.552 0-1 .448-1 1v7c0 .552.448 1 1 1h7c.552 0 1-.448 1-1zm-11 0c0-.552-.448-1-1-1h-7c-.552 0-1 .448-1 1v7c0 .552.448 1 1 1h7c.552 0 1-.448 1-1z"/></g></svg>
                      </span>
                      <span class="label">返回列表</span>
                    </a>
                  </div>

                  <div class="nav-next">
                    <?php if ( get_next_post() ) : ?>
                      <?php next_post_link(
                          '%link',
                          '<span class="text">' . esc_html( $nav_cfg['next_label'] ) . '<i></i></span><span class="title">%title</span>'
                      ); ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </nav>

            <?php
            // ===== 相關文章：只在 post 顯示 =====
            if ( is_singular( 'post' ) ) :

                $tax     = 'category';
                $terms   = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );

                $args = array(
                    'post_type'              => 'post',
                    'post__not_in'           => array( $post_id ),
                    'posts_per_page'         => 3,
                    'ignore_sticky_posts'    => true,
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                );

                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy'         => $tax,
                            'field'            => 'term_id',
                            'terms'            => $terms,
                            'include_children' => false,
                            'operator'         => 'IN',
                        ),
                    );
                }

                $q = new WP_Query( $args );

                if ( $q->have_posts() ) : ?>
                  <div class="ph-related-wrap">
                    <div class="ast-container">
                      <h2 class="ph-related-title">相關文章</h2>
                      <ul class="ph-related-list">
                        <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                          <li class="ph-related-item">
                            <a class="ph-related-link" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                              <span class="thumb">
                                <?php
                                if ( has_post_thumbnail() ) {
                                    the_post_thumbnail( 'full' );
                                } else {
                                    echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/post-default.jpg' ) . '" alt="">';
                                }
                                ?>
                              </span>
                              <div class="info">
                                <time class="date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                  <?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?>
                                </time>
                                <div class="title"><?php the_title(); ?></div>
                                <div class="desc">
                                  <?php
                                  $raw = has_excerpt()
                                      ? get_the_excerpt()
                                      : wp_strip_all_tags( strip_shortcodes( get_the_content( null, false ) ), true );

                                  $excerpt = wp_trim_words( $raw, 500 );
                                  echo esc_html( $excerpt );
                                  ?>
                                </div>
                              </div>
                            </a>
                          </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                      </ul>
                    </div>
                  </div>
                <?php endif; ?>
            <?php endif; ?>

          </div>
        </section>
        <?php
        echo ob_get_clean();
    }
}

/**
 * 從 qz_content_registry() 自動組單頁導覽設定
 * - post 固定 /article
 * - registry CPT 用 archive_slug
 */
function qz_get_single_nav_cfg( $post_type ) {

    if ( $post_type === 'post' ) {
        return array(
            'list_url'   => home_url( 'article' ),
            'back_aria'  => '回到〈網站新知〉文章列表',
            'prev_label' => '上一篇文章',
            'next_label' => '下一篇文章',
        );
    }

    if ( ! function_exists( 'qz_content_registry' ) ) return null;

    $items = qz_content_registry();
    if ( ! is_array( $items ) ) return null;

    $hit = null;
    foreach ( $items as $it ) {
        if ( ! is_array( $it ) ) continue;
        if ( empty( $it['pt'] ) ) continue;
        if ( $it['pt'] === $post_type ) {
            $hit = $it;
            break;
        }
    }

    if ( ! $hit ) return null;

    $slug  = isset( $hit['archive_slug'] ) ? (string) $hit['archive_slug'] : '';
    $label = isset( $hit['pt_label'] ) ? (string) $hit['pt_label'] : '';

    $noun = $label ? preg_replace( '/(專區|介紹)$/u', '', $label ) : $post_type;

    $list_url = $slug ? home_url( $slug ) : ( get_post_type_archive_link( $post_type ) ?: home_url( '/' ) );

    return array(
        'list_url'   => $list_url,
        'back_aria'  => '回到〈' . $noun . '〉列表',
        'prev_label' => '上一篇' . $noun,
        'next_label' => '下一篇' . $noun,
    );
}
