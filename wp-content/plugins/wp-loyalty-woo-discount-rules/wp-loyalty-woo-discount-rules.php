<?php
/**
 * Plugin Name: WPLoyalty - Discount Rule Integration
 * Plugin URI: https://www.wployalty.net
 * Description: Adds Woo Discount Rule conditional support for WPLoyalty
 * Version: 1.0.2
 * Author: WPLoyalty
 * Slug: wp-loyalty-woo-discount-rule
 * Text Domain: wp-loyalty-woo-discount-rule
 * Requires Plugins: woocommerce, wp-loyalty-rules, woo-discount-rules,
 * Domain Path: /i18n/languages/
 * Requires at least: 4.9.0
 * WC requires at least: 6.5
 * WC tested up to: 9.7
 * Contributors: Alagesan
 * Author URI: https://wployalty.net/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WPLoyalty: 1.2.0
 * WPLoyalty Page Link: wp-loyalty-woo-discount-rule
 */
defined('ABSPATH') or die;
if (!function_exists('isWoocommerceActive')) {
    function isWoocommerceActive()
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }
}
if (!function_exists('isWployaltyActiveOrNot')) {
    function isWployaltyActiveOrNot()
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
        }
        return in_array('wp-loyalty-rules/wp-loyalty-rules.php', $active_plugins, false);
    }
}
if (!isWployaltyActiveOrNot() || !isWoocommerceActive()) {
    return;
}

defined('WLWD_PLUGIN_NAME') or define('WLWD_PLUGIN_NAME', 'WPLoyalty - Discount Rule Integration');
defined('WLWD_PLUGIN_VERSION') or define('WLWD_PLUGIN_VERSION', '1.0.2');
defined('WLWD_PLUGIN_SLUG') or define('WLWD_PLUGIN_SLUG', 'wp-loyalty-woo-discount-rule');
defined('WLWD_PLUGIN_PATH') or define('WLWD_PLUGIN_PATH', __DIR__ . '/');
defined('WLWD_PLUGIN_URL') or define('WLWD_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!class_exists('\Wlwd\App\Router')) {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        return;
    }
    require __DIR__ . '/vendor/autoload.php';
}
if (class_exists('\Wlwd\App\Router')) {
    $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/wployalty/wployalty_woo_discount_rules',
        __FILE__,
        'wp-loyalty-woo-discount-rules'
    );
    $myUpdateChecker->getVcsApi()->enableReleaseAssets();
    $router = new \Wlwd\App\Router();
    $router->init();
}