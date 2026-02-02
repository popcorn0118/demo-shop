<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlwd\App\Controllers;


defined('ABSPATH') or die;

class Base
{
    public static function hasAdminPrivilege() {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return true;
        } else {
            return false;
        }
    }
    function addMenu()
    {
        if (self::hasAdminPrivilege()) {
            add_menu_page(__('WPLoyalty - Woo Discount Rule Compatibility', 'wp-loyalty-woo-discount-rule'), __('WPLoyalty - Woo Discount Rule Compatibility', 'wp-loyalty-woo-discount-rule'), 'manage_woocommerce', WLWD_PLUGIN_SLUG, array($this, 'manageLoyaltyPages'), 'dashicons-megaphone', 57);
        }
    }

    function manageLoyaltyPages(){
        $path = WLWD_PLUGIN_PATH . 'App/Views/Admin/main.php';
        self::renderTemplate($path);
    }


    public static function renderTemplate($file, $data = [], $display = true)
    {
        if (!is_string($file) || !is_array($data) || !is_bool($display)) return false;

        $content = '';
        if (file_exists($file)) {
            ob_start();
            extract($data);
            include $file;
            $content = ob_get_clean();
        }
        if ($display) {
            echo $content;
        } else {
            return $content;
        }
    }


    function menuHideProperties()
    {
        ?>
        <style>
            #toplevel_page_wp-loyalty-woo-discount-rule{
                display: none !important;
            }
        </style>
        <?php
    }

    function addConditions($available_conditions){
        if (file_exists(WLWD_PLUGIN_PATH . 'App/Conditions/')) {
            $conditions_list = array_slice(scandir(WLWD_PLUGIN_PATH . 'App/Conditions/'), 2);
            if (!empty($conditions_list)) {
                foreach ($conditions_list as $condition) {
                    $class_name = basename($condition, '.php');
                    if (!in_array($class_name, array('Base'))) {
                        $condition_class_name = 'Wlwd\App\Conditions\\' . $class_name;
                        if (class_exists($condition_class_name)) {
                            $condition_object = new $condition_class_name();
                            if ($condition_object instanceof \Wdr\App\Conditions\Base) {
                                $rule_name = $condition_object->name();
                                if (!empty($rule_name)) {
                                    $available_conditions[$rule_name] = array(
                                        'object' => $condition_object,
                                        'label' => $condition_object->label,
                                        'group' => $condition_object->group,
                                        'template' => $condition_object->template,
                                        'extra_params' => $condition_object->extra_params,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        return $available_conditions;
    }
}