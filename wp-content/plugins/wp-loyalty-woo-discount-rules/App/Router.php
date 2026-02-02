<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlwd\App;

defined('ABSPATH') or die;

class Router
{
    private static $base;
    function init()
    {
        self::$base = empty(self::$base) ? new \Wlwd\App\Controllers\Base() : self::$base;
        if (is_admin()) {
            add_action('admin_menu', array(self::$base, 'addMenu'));
            add_action('network_admin_menu', array(self::$base, 'addMenu'));
            /*add_action('admin_enqueue_scripts', array(self::$base, 'adminScripts'), 100);*/
            add_action('admin_footer', array(self::$base, 'menuHideProperties'));
        }
        add_action( 'advanced_woo_discount_rules_loaded', function() {
            add_filter('advanced_woo_discount_rules_conditions', array(self::$base, 'addConditions'));
        });
        add_filter('advanced_woo_discount_rules_conditions', array(self::$base, 'addConditions'));
    }
}