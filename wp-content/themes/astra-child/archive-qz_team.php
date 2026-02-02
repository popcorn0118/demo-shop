<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 相簿專區
 * team Archive：只客製「內容區塊」
 * - Sidebar / 外框 / 其他結構：沿用 Astra 的 archive.php
 * - 只替換 Astra 的 astra_content_loop 輸出
 */

if ( class_exists( 'Astra_Loop' ) && method_exists( 'Astra_Loop', 'get_instance' ) ) {

    // 拿掉 Astra 預設內容 loop
    remove_action( 'astra_content_loop', [ Astra_Loop::get_instance(), 'loop_markup' ], 10 );

    // 換成自己的內容 loop
    add_action( 'astra_content_loop', 'qz_team_archive_loop_markup', 10 );
}

function qz_team_archive_loop_markup() {

    $wpq        = $GLOBALS['wp_query'];
    $query_vars = is_object($wpq) ? (array) $wpq->query_vars : [];
    ?>

    <main id="main" class="site-main">
        <?php
        if ( have_posts() ) :

            do_action( 'astra_template_parts_content_top' );

            $team_page = get_field('team_page', 'option') ?: [];
            $enable_single_page = ! empty($team_page['qz_team_enable_single_page']); // True=啟用詳情頁

            echo '<div class="qz-archive qz-team-archive">';

            while ( have_posts() ) :
                the_post();

                $post_id  = get_the_ID();

                // ===== ACF：團隊欄位 =====
                $team_title  = (string) get_field( 'team_title',  $post_id ); // 頭銜
                $team_name   = (string) get_field( 'team_name',   $post_id ); // 英文名
                $team_slogan = (string) get_field( 'team_slogan', $post_id ); // 標語

                $term_names = [];
                $terms = get_the_terms( $post_id, 'qz_team_cat' );
                if ( empty($terms) || is_wp_error($terms) ) {
                    $terms = get_the_terms( $post_id, 'category' );
                }
                if ( ! empty($terms) && ! is_wp_error($terms) ) {
                    foreach ( $terms as $t ) {
                        $term_names[] = $t->name;
                    }
                }
                $cats_str = $term_names ? implode('、', $term_names) : '';

                $item_url = $enable_single_page ? get_permalink() : '';

                $team_images = get_field( 'photo_team', $post_id );
                if ( ! is_array( $team_images ) ) {
                    $team_images = [];
                }

                // === 精選圖（Featured Image） ===
                $thumb_id = get_post_thumbnail_id( $post_id );

                $classes = implode( ' ', get_post_class( 'qz-team-item', $post_id ) );
                $group   = 'qz-team-' . $post_id;
                ?>

                <?php if ( $enable_single_page ) : ?>
                    <a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $item_url ); ?>">
                <?php else : ?>
                    <div class="<?php echo esc_attr( $classes ); ?>">
                <?php endif; ?>

                    <?php
                    /**
                     * 顯示圖片優先順序：
                     * 1) 有 photo_team 圖庫 → 維持 grid 顯示
                     * 2) 沒有 photo_team → 顯示精選圖
                     */
                    ?>

                    <?php if ( ! empty( $team_images ) ) : ?>

                        <div class="qz-team-grid">

                            <?php if ( $enable_single_page ) : ?>

                                <?php foreach ( $team_images as $img ) :

                                    $img_id  = 0;
                                    $img_alt = '';

                                    if ( is_array( $img ) && ! empty( $img['ID'] ) ) {
                                        $img_id  = (int) $img['ID'];
                                        $img_alt = isset($img['alt']) ? (string) $img['alt'] : '';
                                    } elseif ( is_numeric( $img ) ) {
                                        $img_id = (int) $img;
                                    }

                                    if ( ! $img_id ) continue;
                                    ?>
                                    <figure class="qz-team-photo">
                                        <?php echo wp_get_attachment_image( $img_id, 'medium', false, [
                                            'alt' => $img_alt ? esc_attr($img_alt) : '',
                                        ] ); ?>
                                    </figure>
                                <?php endforeach; ?>

                            <?php else : ?>

                                <?php foreach ( $team_images as $img ) :

                                    $img_id  = 0;
                                    $img_alt = '';

                                    if ( is_array( $img ) && ! empty( $img['ID'] ) ) {
                                        $img_id  = (int) $img['ID'];
                                        $img_alt = isset($img['alt']) ? (string) $img['alt'] : '';
                                    } elseif ( is_numeric( $img ) ) {
                                        $img_id = (int) $img;
                                    }

                                    if ( ! $img_id ) continue;

                                    $thumb = wp_get_attachment_image( $img_id, 'medium', false, [
                                        'alt' => $img_alt ? esc_attr($img_alt) : '',
                                    ] );

                                    $full = wp_get_attachment_image_url( $img_id, 'large' );
                                    if ( ! $full ) continue;

                                    // === FS Lightbox Pro：thumb + caption ===
                                    $thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                                    if ( ! $thumb_url ) {
                                        $thumb_url = wp_get_attachment_image_url( $img_id, 'medium' );
                                    }

                                    $att_title   = get_the_title( $img_id );
                                    $att_caption = wp_get_attachment_caption( $img_id );
                                    $caption_html = '';

                                    if ( $att_title || $att_caption ) {
                                        if ( $att_title ) {
                                            $caption_html .= '<h2>' . esc_html( $att_title ) . '</h2>';
                                        }
                                        if ( $att_caption ) {
                                            $caption_html .= '<h3>' . esc_html( $att_caption ) . '</h3>';
                                        }
                                    }
                                    ?>
                                    <a class="qz-team-photo"
                                       href="<?php echo esc_url( $full ); ?>"
                                       data-fslightbox="<?php echo esc_attr( $group ); ?>"
                                       data-elementor-open-lightbox="no"
                                       <?php if ( $thumb_url ) : ?>
                                           data-thumb="<?php echo esc_url( $thumb_url ); ?>"
                                       <?php endif; ?>
                                       <?php if ( $caption_html ) : ?>
                                           data-caption="<?php echo esc_attr( $caption_html ); ?>"
                                       <?php endif; ?>>
                                        <?php echo $thumb; ?>
                                    </a>
                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    <?php elseif ( $thumb_id ) : ?>

                        <figure class="qz-team-thumb">
                            <?php
                            $thumb_alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
                            echo wp_get_attachment_image( $thumb_id, 'medium', false, [
                                'alt' => $thumb_alt ? esc_attr( $thumb_alt ) : '',
                            ] );
                            ?>
                        </figure>

                    <?php endif; ?>

                    <div class="qz-team-cont">
                        <?php if ( $cats_str ) : ?>
                            <div class="qz-team-meta">
                                <span class="qz-team-cats"><?php echo esc_html( $cats_str ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( $team_name ) : ?>
                            <div class="qz-team-name-en"><?php echo esc_html( $team_name ); ?></div>
                        <?php endif; ?>

                        <h3 class="qz-team-title"><?php the_title(); ?></h3>

                        <?php if ( $team_title ) : ?>
                            <div class="qz-team-role"><?php echo esc_html( $team_title ); ?></div>
                        <?php endif; ?>

                        <?php if ( !$enable_single_page && $team_slogan ) : ?>
                            <div class="qz-team-slogan"><?php echo esc_html( $team_slogan ); ?></div>
                        <?php endif; ?>

                        <?php if ( $enable_single_page ) : ?>
                            <?php
                            $excerpt = trim( get_post_field('post_excerpt', $post_id) );
                            if ( ! $excerpt ) {
                                $content = get_post_field('post_content', $post_id);
                                $content = strip_shortcodes($content);
                                $content = wp_strip_all_tags($content, true);
                                $excerpt = mb_substr( $content, 0, 500, 'UTF-8' );
                            }
                            ?>
                            <div class="qz-team-excerpt">
                                <?php echo esc_html( $excerpt ); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php if ( $enable_single_page ) : ?>
                    </a>
                <?php else : ?>
                    </div>
                <?php endif; ?>

                <?php
            endwhile;

            echo '</div>';

            do_action( 'astra_template_parts_content_bottom' );

        else :
            do_action( 'astra_template_parts_content_none' );
        endif;
        ?>
    </main>
    <?php
}

require get_template_directory() . '/archive.php';
