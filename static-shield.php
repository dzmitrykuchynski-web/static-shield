<?php

/**
 * The Static Shield plugin bootstrap file
 *
 *
 * @since             1.0.0
 * @package           Static_Shield
 *
 * @wordpress-plugin
 * Plugin Name:       Static Shield
 * Description:       Automatically generates a static version of your WordPress site, archives it, and uploads it to Cloudflare R2 for delivery via Cloudflare Workers. Includes automatic updates after new or edited posts, manual export controls, and an admin panel for API key management.
 * Version:           1.0.0
 * Author:            Static Shield
 * Author URI:        https://www.example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       static_shield
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'STATIC_SHIELD_VERSION', '1.0.0' );
define( 'STATIC_SHIELD_PLUGIN_NAME', 'static-shield' );

define( 'STATIC_SHIELD_PATH', plugin_dir_path( __FILE__ ) );
define( 'STATIC_SHIELD_URL', plugin_dir_url( __FILE__ ) );
define( 'STATIC_SHIELD_BASENAME', plugin_basename( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

use StaticShield\StaticShieldActivator;
use StaticShield\StaticShieldDeactivator;

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
use StaticShield\StaticShield;
use StaticShield\StaticShieldWorkerHandler;

/**
 * The code that runs during plugin activation.
 */
function activateStaticShield() {
    StaticShieldActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivateStaticShield() {
    StaticShieldDeactivator::deactivate();
}

register_activation_hook( __FILE__, 'activateStaticShield' );
register_deactivation_hook( __FILE__, 'deactivateStaticShield' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function runStaticShield() {

	$plugin = new StaticShield();
	$plugin->run();

}
runStaticShield();
