<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file removes all plugin data such as options, scheduled events,
 * and temporary directories to ensure a clean uninstall.
 *
 * @link       https://www.alreadymedia.com/
 * @since      1.0.0
 * @package    Static_Shield
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options
delete_option( 'static_shield_version' );

// Remove plugin build directory
$uploadDir = wp_upload_dir();
$staticShieldDir = trailingslashit( $uploadDir['basedir'] ) . 'static-shield-builds';

if ( is_dir( $staticShieldDir ) ) {
    static_shield_remove_directory( $staticShieldDir );
}

/**
 * Secure recursive directory removal.
 *
 * @since    1.0.0
 * @param    string $dir Directory path.
 * @return   void
 */
function static_shield_remove_directory( $dir ) {
    $files = array_diff( scandir( $dir ), [ '.', '..' ] );
    foreach ( $files as $file ) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if ( is_dir( $filePath ) ) {
            static_shield_remove_directory( $filePath );
        } else {
            @unlink( $filePath );
        }
    }
    @rmdir( $dir );
}
