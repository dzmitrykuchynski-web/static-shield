<?php
namespace StaticShield;

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.example.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */
class StaticShieldDeactivator {
    /**
     * Run tasks on plugin deactivation.
     *
     * @since    1.0.0
     * @return   void
     */
    public static function deactivate() {
        $uploads   = wp_get_upload_dir();
        $buildRoot = $uploads['basedir'] . '/static-shield-builds';

        if ( is_dir( $buildRoot ) ) {
            self::rrmdir( $buildRoot );
        }
    }

    /**
     * Recursively remove directory
     *
     * @param string $dir
     * @return void
     */
    private static function rrmdir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        foreach ( scandir( $dir ) as $object ) {
            if ( $object === '.' || $object === '..' ) {
                continue;
            }
            $path = $dir . '/' . $object;
            if ( is_dir( $path ) ) {
                self::rrmdir( $path );
            } else {
                unlink( $path );
            }
        }
        rmdir( $dir );
    }

}
