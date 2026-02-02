<?php

defined('ABSPATH') or die;

echo ($render_saved_condition == true) ? '' : '<div class="customer_level">';
$operator = isset($options->operator) ? $options->operator : 'in_list';
$values = isset($options->value) ? $options->value : false;

$available_levels = \Wlwd\App\Helpers\Database::getAvailableLevels();

?>
<div class="wdr_shipping_city_group wdr-condition-type-options">
    <div class="wdr-level-method wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('In List', 'wp-loyalty-woo-discount-rule'); ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('Not In List', 'wp-loyalty-woo-discount-rule'); ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Customer level should be', 'wp-loyalty-woo-discount-rule'); ?></span>
    </div>

    <div class="customer-level-value wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="<?php echo ($render_saved_condition == true) ? 'edit-all-loaded-values' : '' ?>"
                data-list="customer_level_list"
                data-field="autoloaded"
                data-placeholder="<?php esc_attr_e('Search customer level', 'wp-loyalty-woo-discount-rule');?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]">
            <?php
            foreach ($available_levels as $level) {
                if(!empty($level->id) && is_array($values) && in_array($level->id, $values)){
               ?>
                    <option value="<?php echo esc_attr($level->id); ?>" selected><?php echo esc_html($level->name); ?></option>
               <?php } else {?>
                <option value="<?php echo esc_attr($level->id); ?>" ><?php echo esc_html($level->name); ?></option>
            <?php } }?>

        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select customer level', 'wp-loyalty-woo-discount-rule'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>
