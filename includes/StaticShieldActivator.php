<?php
namespace StaticShield;

/**
 * Fired during plugin activation
 *
 * @link       https://www.alreadymedia.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

class StaticShieldActivator {
	public static function activate() {
        add_option( 'static_shield_version', STATIC_SHIELD_VERSION );
	}

}
