<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Header marquee － ACF 來源：qibuzhan-widgets
 * 跑馬燈
 * 
 */



function qz_show_header_marquee() {

    if ( is_admin() ) {
        return;
    }

    // WooCommerce：購物車 / 結帳 / 會員＆各種 endpoint 不顯示
    if ( function_exists( 'is_woocommerce' ) ) {
        if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() ) {
            return;
        }
    }
    
    // 沒 ACF 就跳過
    if ( ! function_exists( 'get_field' ) ) {
        return;
    }
    
    $post_id = 'option';

    // 總開關
    $show_bar = get_field( 'show', $post_id );
    if ( ! $show_bar ) {
        return;
    }

    // 浮動
    $float = get_field( 'float', $post_id );
    
    // 速度（毫秒），給 data-speed 給前端 JS 用
    $speed = (int) get_field( 'speed', $post_id );
    if ( $speed <= 0 ) {
        $speed = 3000;
    }

    // 跑馬燈內容 repeater
    $rows = get_field( 'marquee', $post_id );
    if ( empty( $rows ) || ! is_array( $rows ) ) {
        return;
    }

    // 跑馬燈樣式
    $style = get_field( 'marquee_style', $post_id );
    if ( empty( $rows ) || ! is_array( $rows ) ) {
        return;
    }

    $items = array();

    foreach ( $rows as $row ) {
        $text     = isset( $row['text'] ) ? trim( $row['text'] ) : '';
        $link     = isset( $row['link'] ) ? trim( $row['link'] ) : '';
        $new_page = ! empty( $row['new_page'] );
        $show     = isset( $row['show'] ) ? (bool) $row['show'] : true;

        if ( ! $show || $text === '' ) {
            continue;
        }

        $items[] = array(
            'text'     => $text,
            'link'     => $link,
            'new_page' => $new_page,
        );
    }

    if ( empty( $items ) ) {
        return;
    }
    ?>
    <div class="qz-header-marquee <?php echo !empty($float) ? 'float' : ''; ?>" data-speed="<?php echo esc_attr( $speed ); ?>" style="background-color: <?php echo $style['bg_color']; ?>">
        <div class="qz-header-marquee__inner js-qz-header-marquee">
            <?php foreach ( $items as $item ) : ?>
                <div class="qz-header-marquee__slide">
                    <?php if ( $item['link'] ) : ?>
                        <a class="qz-header-marquee__text"
                           href="<?php echo esc_url( $item['link'] ); ?>"
                           style="color: <?php echo $style['text_color']; ?>"
                           <?php if ( $item['new_page'] ) : ?>
                               target="_blank" rel="noopener"
                           <?php endif; ?>>
                            <?php echo esc_html( $item['text'] ); ?>
                        </a>
                    <?php else : ?>
                        <span class="qz-header-marquee__text" style="color: <?php echo $style['text_color']; ?>">
                            <?php echo esc_html( $item['text'] ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
}
add_action( 'wp_body_open', 'qz_show_header_marquee', 5 );
