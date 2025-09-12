<?php
namespace StaticShield;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.alreadymedia.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */
class StaticShieldI18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function loadPluginTextdomain() {

		load_plugin_textdomain(
			'static-shield',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}
}
