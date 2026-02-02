<?php

namespace Wlwd\App\Conditions;
use Wdr\App\Conditions\Base;
use Wlwd\App\Helpers\Database;

defined('ABSPATH') or die;
class CustomerLevel extends Base
{
    function __construct()
    {
        parent::__construct();
        $this->name = 'customer_level';
        $this->label = __('Customer Level', 'wp-loyalty-woo-discount-rule');
        $this->group = __('Customer', 'wp-loyalty-woo-discount-rule');
        $this->template = WLWD_PLUGIN_PATH . 'App/Views/Admin/Conditions/Customer/level.php';
    }

    public function check($cart, $options)
    {
        if (isset($options->value) && isset($options->operator)) {
            $customer_level = 0;
            if (empty($customer_level) && get_current_user_id()) {
                $user_email = NULL;
                $user = (is_user_logged_in()) ? get_user_by('ID', get_current_user_id()) : NULL;
                if (!empty($user)) {
                    $user_email = isset($user->data->user_email) ? $user->data->user_email : NULL;
                }
                if(!empty($user_email)){
                    $wlr_user = Database::getPointUserByEmail($user_email);
                    $customer_level = isset($wlr_user->level_id) && !empty($wlr_user->level_id) ? $wlr_user->level_id : 0;
                    if ($customer_level <= 0) {
                        $points = (int)(isset($wlr_user->points) && $wlr_user->points > 0 ? $wlr_user->points : 0);
                        $customer_level = Database::getCurrentLevelId($points);
                    }
                }elseif (isset($options->value) && empty($user_email)) {
                    $customer_level =  Database::getCurrentLevelId(0);
                }
            }
            return $this->doCompareInListOperation($options->operator, $customer_level, $options->value);
        }
        return false;
    }
}