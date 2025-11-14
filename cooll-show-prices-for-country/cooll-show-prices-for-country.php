<?php
/**
 * Plugin Name: Cooll Show Prices For Country
 * Description: Checks if the visitor IP belongs to Country based on provided CIDR files and hides the product prices.
 * Version: 1.0
 * Author: Leonidas Tsaras
 */

include plugin_dir_path(__FILE__) . '/inc/CountryIPChecker.php';
//include "inc/CountryIPChecker.php";

add_action( 'plugins_loaded', 'cooll_hide_prices' );

function cooll_hide_prices() {
    if(!is_ip_from_selected_country()) {
        //hide prices
        cooll_hide_prices_for_selected_country();
    }
}

function cooll_hide_prices_for_selected_country() {
    //if (is_admin() || current_user_can('manage_woocommerce')) return;

    // Hide prices
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);

    // Hide Add to Cart
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

    // Optional: Replace price text
    add_filter('woocommerce_get_price_html', 'cooll_custom_hide_price_message');

}
function cooll_custom_hide_price_message() {
    $message = get_option('greek_ip_checker_message');
    return '<span class="woocommerce-price-unavailable">' . $message . '</span>';
}


// Example usage: customize these paths from plugin settings or theme
//$ipv4Path = get_option('greek_ip_checker_ipv4_file', plugin_dir_path(__FILE__) . 'data/ipv4.php');
//$ipv6Path = get_option('greek_ip_checker_ipv6_file', plugin_dir_path(__FILE__) . 'data/ipv6.php');

function is_ip_from_selected_country($ipv4File = null, $ipv6File = null) {
    //return (is_admin());
    $ipv4File = $ipv4File ?? plugin_dir_path(__FILE__) . 'data/ipv4.php';
    $ipv6File = $ipv6File ?? plugin_dir_path(__FILE__) . 'data/ipv6.php';
    $checker = new CountryIPChecker($ipv4File, $ipv6File);
    return $checker->is_country_ip();
}





/// admin panel
// Register settings
function cooll_ip_checker_register_settings() {
    //register_setting('cooll_ip_checker_settings_group', 'cooll_ip_checker_message_ip_checker_ipv4_file');
    //register_setting('cooll_ip_checker_settings_group', 'cooll_ip_checker_message_ip_checker_ipv6_file');
    register_setting('cooll_ip_checker_settings_group', 'cooll_ip_checker_message');
}
add_action('admin_init', 'cooll_ip_checker_register_settings');

// Add menu item
function greek_ip_checker_add_admin_menu() {
    add_options_page(
        'Country IP Checker Settings',
        'Country IP Checker',
        'manage_options',
        'country-ip-checker',
        'cooll_ip_checker_settings_page'
    );
}
add_action('admin_menu', 'greek_ip_checker_add_admin_menu');

// Settings page content
function cooll_ip_checker_settings_page() {
    ?>
    <div class="wrap">
        <h1>Greek IP Checker Settings</h1>
        <p>This plugin checks visitor's ip and if it's Not Greek hides prices.</p>
        <form method="post" action="options.php">
            <?php settings_fields('cooll_ip_checker_settings_group'); ?>
            <?php do_settings_sections('cooll_ip_checker_settings_group'); ?>
            <table class="form-table">
<!--                 <tr valign="top">
                    <th scope="row">IPv4 File Path</th>
                    <td><input type="text" name="greek_ip_checker_ipv4_file" value="<?php //echo esc_attr(get_option('greek_ip_checker_ipv4_file')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">IPv6 File Path</th>
                    <td><input type="text" name="greek_ip_checker_ipv6_file" value="<?php //echo esc_attr(get_option('greek_ip_checker_ipv6_file')); ?>" size="50"/></td>
                </tr> -->
                <tr valign="top">
                    <th scope="row">Message to replace prices</th>
                    <td><input type="text" name="cooll_ip_checker_message" value="<?php echo esc_attr(get_option('cooll_ip_checker_message')); ?>" size="50"/></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
