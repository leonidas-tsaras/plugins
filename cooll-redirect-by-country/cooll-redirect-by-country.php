<?php
/**
 * Plugin Name: Cooll Redirect By Country
 * Description: Redirects if the visitor IP belongs to Country based on provided CIDR files.
 * Version: 1.0
 * Author: Leonidas Tsaras
 */

add_action( 'template_redirect', 'redirect_to');

function redirect_to() {
	//if is_admin() return;
    if(!is_ip_from_selected_country()) {
    	$url = get_option('url_to_redirect_to', "https://google.com");
		if(wp_redirect($url)) exit;
    }
}

function is_ip_from_selected_country() {
    $ipv4File = plugin_dir_path(__FILE__) . '/data/ipv4.php';
    $ipv6File = plugin_dir_path(__FILE__) . 'data/ipv6.php';
    $checker = new CountryIPChecker($ipv4File, $ipv6File);
    return $checker->is_country_ip();
}

class CountryIPChecker {
    private $ipv4;
    private $ipv6;

    public function __construct($ipv4File, $ipv6File) {
        if (file_exists($ipv4File)) include($ipv4File);
        if (file_exists($ipv6File)) include($ipv6File);
        $this->ipv4 = isset($ipv4) ? $ipv4 : [];
        $this->ipv6 = isset($ipv6) ? $ipv6 : [];
    }

    public function getClientIP() {
        return "104.152.48.255"; //Bulgaria

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getIPVersion($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return 'IPv4';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return 'IPv6';
        return null;
    }

    public function is_country_ip() {
        $ip = $this->getClientIP();
        $version = $this->getIPVersion($ip);
        if ($version === 'IPv4') return $this->is_IPv4($ip);
        if ($version === 'IPv6') return $this->is_IPv6($ip);
        return false;
    }

    private function is_IPv4($ip) {
        foreach ($this->ipv4 as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    private function is_IPv6($ip) {
        foreach ($this->ipv6 as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            $range .= strpos($ip, ':') !== false ? '/128' : '/32';
        }

        list($rangeIP, $netmask) = explode('/', $range, 2);
        $ipBin = inet_pton($ip);
        $rangeBin = inet_pton($rangeIP);
        if ($ipBin === false || $rangeBin === false) return false;

        $maskBytes = floor($netmask / 8);
        $maskBits = $netmask % 8;

        for ($i = 0; $i < $maskBytes; $i++) {
            if ($ipBin[$i] !== $rangeBin[$i]) return false;
        }

        if ($maskBits > 0) {
            $mask = ~(255 >> $maskBits) & 255;
            if ((ord($ipBin[$maskBytes]) & $mask) !== (ord($rangeBin[$maskBytes]) & $mask)) return false;
        }

        return true;
    }
}


/// admin panel
// Register settings
function cooll_register_admin_settings() {
    register_setting('cooll_settings_group', 'url_to_redirect_to');
}
add_action('admin_init', 'cooll_register_admin_settings');

// Add menu item
function cooll_add_admin_menu() {
    add_options_page(
        'Country IP Redirect Settings',
        'Country IP Redirect',
        'manage_options',
        'country_ip_redirect',
        'cooll_admin_settings_page'
    );
}
add_action('admin_menu', 'cooll_add_admin_menu');

// Settings page content
function cooll_admin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Country IP Redirect Settings</h1>
        <p>This plugin checks visitor's ip and redirects if not in the ip range list.</p>


        <form method="post" action="options.php">
            <?php settings_fields('cooll_settings_group'); ?>
            <?php do_settings_sections('cooll_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">url to redirect to:</th>
                    <td><input type="url" name="url_to_redirect_to" value="<?php echo esc_attr(get_option('url_to_redirect_to')); ?>" size="70"/></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

    </div>
    <?php
}
