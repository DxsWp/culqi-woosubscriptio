<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://daxes.net/
 * @since             1.0.0
 * @package           Culqi_Woosubscriptio
 *
 * @wordpress-plugin
 * Plugin Name:       Culqi WC + Subscriptio
 * Plugin URI:        http://daxes.net/x/wp/plugins
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Laurence HR
 * Author URI:        http://daxes.net/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       culqi-woosubscriptio
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CULQI_WOOSUBSCRIPTIO_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-culqi-woosubscriptio-activator.php
 */
function activate_culqi_woosubscriptio() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-culqi-woosubscriptio-activator.php';
	Culqi_Woosubscriptio_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-culqi-woosubscriptio-deactivator.php
 */
function deactivate_culqi_woosubscriptio() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-culqi-woosubscriptio-deactivator.php';
	Culqi_Woosubscriptio_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_culqi_woosubscriptio' );
register_deactivation_hook( __FILE__, 'deactivate_culqi_woosubscriptio' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-culqi-woosubscriptio.php';

require plugin_dir_path( __FILE__ ) . 'WC_CulqiSubscriptio.class.php';

//DEFINE('PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)) . '/');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'init_wc_culqi_subscriptio_payment_gateway', 1);     
  
    function woocommerce_culqi_subscriptio_add_gateway($methods) {
      $methods[] = 'WC_CulqiSubscriptio';
      return $methods;
    }
  
    remove_filter('woocommerce_payment_gateways', 'woocommerce_culqi_add_gateway');
    add_filter('woocommerce_payment_gateways', 'woocommerce_culqi_subscriptio_add_gateway');
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_culqi_woosubscriptio() {

	$plugin = new Culqi_Woosubscriptio();
	$plugin->run();

}
run_culqi_woosubscriptio();
