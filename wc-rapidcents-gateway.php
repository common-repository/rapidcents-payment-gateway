<?php
/**
 * The plugin index.
 *
 * @link              https://rapidcents.com
 * @since             1.0.0
 *
 * @package           Wc_Rapidcents_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Rapidcents Payment Gateway
 * Plugin URI:        https://rapidcents.com
 * Description:       Rapidcents Payment Gateway for WooCommerce
 * Version:           1.0.0
 * Author:            Rapidcents
 * Author URI:        https://rapidcents.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-rapidcents-gateway
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
define( 'WC_RAPIDCENTS_GATEWAY_VERSION', '1.0.0' );

define( 'WC_RAPIDCENTS_GATEWAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'WC_RAPIDCENTS_GATEWAY_LOGGING', false );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-rapidcents-gateway-activator.php
 */
function activate_wc_rapidcents_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-rapidcents-gateway-activator.php';
	Wc_Rapidcents_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-rapidcents-gateway-deactivator.php
 */
function deactivate_wc_rapidcents_gateway() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-rapidcents-gateway-deactivator.php';
	Wc_Rapidcents_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_rapidcents_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_wc_rapidcents_gateway' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-rapidcents-gateway.php';

/**
 * Retrieves an instance of the RapidCents_Helper class.
 *
 * This function creates and returns a new instance of the RapidCents_Helper class,
 * providing a convenient way to access helper functions related to RapidCents.
 *
 * @return RapidCents_Helper An instance of the RapidCents_Helper class.
 */
function rc_helper() {
	return new RapidCents_Helper();
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
function run_wc_rapidcents_gateway() {

	$plugin = new Wc_Rapidcents_Gateway();
	$plugin->run();
}
run_wc_rapidcents_gateway();
