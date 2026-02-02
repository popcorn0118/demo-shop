<?php
/**
 * Custom: Woo 產品分類側欄（含子分類 / 數量 / 全部 / current）
 * Shortcode: [qz_product_cats]
 *
 * 參數（可選）：
 * - hide_empty: '0' | '1'  是否隱藏數量為 0 的分類（預設 0 = 不隱藏）
 * - orderby:    'name'|'menu_order'|'id'...  依 get_terms 規則
 * - order:      'ASC'|'DESC'
 * - title:      手機下拉按鈕文字（預設：產品分類）
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
    add_shortcode('qz_product_cats', 'qz_product_cats_cb');
});

/**
 * 短代碼 callback
 */
function qz_product_cats_cb($atts = []) {
    if (!class_exists('WooCommerce')) return '';

    $a = shortcode_atts([
      'hide_empty' => '0',
      'orderby'    => 'menu_order',  // 跟後台拖拉順序一致
      'order'      => 'ASC',
      'title'      => '產品分類',
    ], $atts, 'qz_product_cats');

    $hide_empty = ($a['hide_empty'] === '1');

    // 目前頁面狀態
    $is_shop = function_exists('is_shop') && is_shop();
    $current_term_id = (is_tax('product_cat')) ? get_queried_object_id() : 0;

    // 商店頁（/product/）
    $shop_url = function_exists('wc_get_page_permalink')
        ? wc_get_page_permalink('shop')
        : home_url('/product/');

    // 全部商品數
    $total_products = (int) ( wp_count_posts('product')->publish ?? 0 );

    // 頂層分類
    $parents = get_terms([
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => $hide_empty,
        'orderby'    => $a['orderby'],
        'order'      => $a['order'],
    ]);

    ob_start(); ?>
    <nav class="qz-prodcat" data-component="prodcat">
      <div class="qz-prodcat-header">
        <button class="qz-prodcat-toggle" type="button"
                aria-expanded="false" aria-controls="qz-prodcat-list">
          <?php echo esc_html($a['title']); ?>
        </button>
        <h2 class="qz-prodcat-title">產品分類</h2>
      </div>
      
      <ul id="qz-prodcat-list" class="qz-prodcat-list">
        <?php
        // 全部
        $all_class = $is_shop ? 'current' : '';
        ?>
        <li class="cat-item cat-item-all <?php echo esc_attr($all_class); ?>">
          <a href="<?php echo esc_url($shop_url); ?>">
            全部
            <!-- <span class="count"><?php echo (int) $total_products; ?></span> -->
          </a>
        </li>

        <?php foreach ($parents as $p) :
            $p_link = get_term_link($p);
            if (is_wp_error($p_link)) continue;

            // 第二層
            $children = get_terms([
                'taxonomy'   => 'product_cat',
                'parent'     => $p->term_id,
                'hide_empty' => $hide_empty,
                'orderby'    => $a['orderby'],
                'order'      => $a['order'],
            ]);

            // current 樣式：自己是 current，或其子是 current
            $is_current_parent = ($current_term_id && $current_term_id == $p->term_id);
            $contains_current  = false;
            foreach ($children as $c) {
                if ($current_term_id == $c->term_id) { $contains_current = true; break; }
            }

            $p_classes = [];
            if ($is_current_parent) $p_classes[] = 'current';
            if ($contains_current)  $p_classes[] = 'open'; // 如不想預設展開，可拿掉這行
            if (!empty($children))  $p_classes[] = 'has-children';
            ?>
            <li class="cat-item cat-item-parent <?php echo esc_attr(implode(' ', $p_classes)); ?>"
                data-term-id="<?php echo (int) $p->term_id; ?>">
              
              <a href="<?php echo esc_url($p_link); ?>">
                <?php echo esc_html($p->name); ?>
                <!-- <span class="count"><?php echo (int) $p->count; ?></span> -->
              </a>

              <?php if (!empty($children)) : ?>
                <!-- 展開/收合按鈕，取代原本 count 的位置 -->
                <button class="toggle" type="button" aria-label="展開子分類" aria-expanded="false">
                  <span class="toggle-sign"></span>
                </button>

                <ul class="children">
                  <?php foreach ($children as $c) :
                      $c_link = get_term_link($c);
                      if (is_wp_error($c_link)) continue;
                      $c_class = ($current_term_id == $c->term_id) ? 'current' : '';
                  ?>
                    <li class="cat-item cat-item-child <?php echo esc_attr($c_class); ?>"
                        data-term-id="<?php echo (int) $c->term_id; ?>">
                      <a href="<?php echo esc_url($c_link); ?>">
                        <?php echo esc_html($c->name); ?>
                        <!-- <span class="count"><?php echo (int) $c->count; ?></span> -->
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php
    return ob_get_clean();
}
