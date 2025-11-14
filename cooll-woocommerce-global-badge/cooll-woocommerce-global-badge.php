<?php
/**
 * Plugin Name: Cooll WooCommerce Global Badge
 * Description: Add a global image badge to all products via Tools menu.
 * Version: 1.2.0
 * Author: leonidas.tsaras@gmail.com
 * Requires at least: 5.6
 * Tested up to: 6.7
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 * Text Domain: wc-global-badge
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cooll_WC_Global_Badge {

    private $version = '1.2.0';
    private $option_name = 'cooll_wc_global_badge_url';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_tools_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'display_badge' ], 9 );
    }

    // Add page under Tools
    public function add_tools_page() {
        add_submenu_page(
            'tools.php',
            __( 'WooCommerce Global Badge', 'wc-global-badge' ),
            __( 'Cooll WC Global Badge', 'wc-global-badge' ),
            'manage_woocommerce',
            'wc-global-badge',
            [ $this, 'render_settings_page' ]
        );
    }

    // Register setting
    public function register_settings() {
        register_setting( 'wc_global_badge_options', $this->option_name, [
            'sanitize_callback' => 'esc_url_raw'
        ] );
    }

    // Render settings page
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wc_global_badge_options' );
                do_settings_sections( 'wc_global_badge_options' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr( $this->option_name ); ?>">
                                <?php _e( 'Badge Image URL', 'wc-global-badge' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="url"
                                name="<?php echo esc_attr( $this->option_name ); ?>"
                                id="<?php echo esc_attr( $this->option_name ); ?>"
                                value="<?php echo esc_attr( get_option( $this->option_name ) ); ?>"
                                class="regular-text"
                                style="width:100%; max-width:800px;"
                                placeholder = <?php echo esc_attr(plugin_dir_url( __FILE__ ) . 'assets/images/badge.png'); ?>
                            />
                            <p class="description">
                                <?php _e( 'Enter full image URL. Badge appears on shop, category, and archive pages only.', 'wc-global-badge' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Badge URL', 'wc-global-badge' ) ); ?>
            </form>

            <?php if ( $url = get_option( $this->option_name ) ) : ?>
                <h3><?php _e( 'Preview', 'wc-global-badge' ); ?></h3>
                <p>
                    <img src="<?php echo esc_url( $url ); ?>" alt="Badge Preview" style="max-height:60px; border:1px solid #ccc;">
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    // Display badge on frontend
    public function display_badge() {
        if ( is_product() ) {
            return;
        }

        $url = get_option( $this->option_name );
        if ( ! $url ) {
            return;
        }

        printf(
            '<img src="%s" alt="Global Badge" class="wc-global-badge" loading="lazy">',
            esc_url( $url )
        );
    }

    // Enqueue CSS only on shop/archive
    public function enqueue_assets() {
        if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) ) {
            return;
        }

        wp_enqueue_style(
            'wc-global-badge',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            [],
            $this->version
        );
    }
}

new Cooll_WC_Global_Badge();