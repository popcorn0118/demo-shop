<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 團隊專區
 * team Single：只客製「內容區塊」
 * - Header / Footer / Sidebar / 外框：沿用 Astra 的 single.php
 * - 只替換 Astra 的 astra_content_loop 輸出
 */

if ( class_exists( 'Astra_Loop' ) && method_exists( 'Astra_Loop', 'get_instance' ) ) {

    // 拿掉 Astra 預設內容 loop
    remove_action( 'astra_content_loop', [ Astra_Loop::get_instance(), 'loop_markup' ], 10 );

    // 換成自己的內容 loop
    add_action( 'astra_content_loop', 'qz_team_single_loop_markup', 10 );
}

function qz_team_single_loop_markup() {

    if ( ! have_posts() ) {
        do_action( 'astra_template_parts_content_none' );
        return;
    }
    ?>

    <main id="main" class="site-main">
        <?php
        do_action( 'astra_template_parts_content_top' );

        while ( have_posts() ) :
            the_post();

            $post_id = get_the_ID();

            // ===== ACF：團隊欄位 =====
            $team_title  = (string) get_field( 'team_title',  $post_id ); // 頭銜
            $team_name   = (string) get_field( 'team_name',   $post_id ); // 英文名
            $team_slogan = (string) get_field( 'team_slogan', $post_id ); // 標語

            // === 精選圖（Featured Image） ===
            $thumb_id = get_post_thumbnail_id( $post_id );
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'qz-team-single' ); ?>>

                <div class="qz-team-two-col">

                    <!-- Left -->
                    <aside class="qz-team-side" aria-label="Team side content">

                        <?php if ( $thumb_id ) : ?>
                            <figure class="qz-team-thumb">
                                <?php
                                $thumb_alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
                                echo wp_get_attachment_image( $thumb_id, 'large', false, [
                                    'alt' => $thumb_alt ? esc_attr( $thumb_alt ) : '',
                                ] );
                                ?>
                            </figure>
                        <?php endif; ?>

                        <header class="qz-team-header">
                            <?php if ( $team_name ) : ?>
                                <div class="qz-team-name-en"><?php echo esc_html( $team_name ); ?></div>
                            <?php endif; ?>

                            <h1 class="qz-team-title"><?php the_title(); ?></h1>

                            <?php if ( $team_title ) : ?>
                                <div class="qz-team-role"><?php echo esc_html( $team_title ); ?></div>
                            <?php endif; ?>

                            <?php if ( $team_slogan ) : ?>
                                <div class="qz-team-slogan"><?php echo esc_html( $team_slogan ); ?></div>
                            <?php endif; ?>
                        </header>

                    </aside>

                    <!-- Right -->
                    <section class="qz-team-body" aria-label="Team content">
                        <div class="qz-team-content entry-content">
                            <?php the_content(); ?>
                        </div>
                    </section>

                </div>

            </article>

            <?php
        endwhile;

        do_action( 'astra_template_parts_content_bottom' );
        ?>
    </main>

    <?php
}

require get_template_directory() . '/single.php';
