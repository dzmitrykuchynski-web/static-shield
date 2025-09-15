<?php
namespace StaticShield;

class StaticShieldExporter {
    private $buildDir;
    private $zipPath;
    private $log = [];
    private $startTime;

    public function __construct() {
        $this->buildDir = WP_CONTENT_DIR . '/static-shield-builds';
        $this->zipPath  = WP_CONTENT_DIR . '/uploads/static-shield-builds/static-shield-builds.zip';
        $this->startTime = microtime(true);
    }

    public function runExport() {
        $this->log[] = $this->logEntry("Starting export...");

        $this->prepareBuildDir();

        $this->exportUrl( home_url(), 'index.html' );
        $this->exportAllPages();
        $this->copyUploads();
        $this->copyWpIncludesAssets();
        $this->createZip();

        $duration = round(microtime(true) - $this->startTime, 2);
        $this->log[] = $this->logEntry("Export finished in {$duration} seconds");

        // Save the log in option for display in the admin panel
        update_option('static_shield_last_log', $this->log);

        return $this->zipPath;
    }

    public function getLog() {
        return get_option('static_shield_last_log', []);
    }

    private function prepareBuildDir() {
        if ( ! file_exists( $this->buildDir ) ) {
            wp_mkdir_p( $this->buildDir );
        }

        $this->rrmdir( $this->buildDir );
        wp_mkdir_p( $this->buildDir );

        if ( file_exists( $this->zipPath ) ) {
            unlink( $this->zipPath );
        }

        $this->log[] = $this->logEntry("Build directory prepared: {$this->buildDir}");
    }

    private function exportUrl( $url, $filename ) {
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            $this->log[] = $this->logEntry("Failed: {$url} (" . $response->get_error_message() . ")", 'error');
            return;
        }

        $html = wp_remote_retrieve_body( $response );
        $path = $this->buildDir . '/' . $filename;

        file_put_contents( $path, $html );
        $this->log[] = $this->logEntry("Exported: {$url} → {$filename}");
    }

    private function exportAllPages() {
        $pages = get_pages();
        foreach ( $pages as $page ) {
            $this->exportUrl( get_permalink($page->ID), ($page->post_name ?: $page->ID) . '.html' );
        }

        $posts = get_posts([ 'numberposts' => -1 ]);
        foreach ( $posts as $post ) {
            $this->exportUrl( get_permalink($post->ID), ($post->post_name ?: $post->ID) . '.html' );
        }
    }

    private function copyUploads() {
        $uploads = wp_get_upload_dir();
        $this->copyDir( $uploads['basedir'], $this->buildDir . '/uploads' );
        $this->log[] = $this->logEntry("Copied uploads directory");
    }

    private function copyWpIncludesAssets() {
        $this->copyDir( ABSPATH . 'wp-includes/js', $this->buildDir . '/wp-includes/js' );
        $this->copyDir( ABSPATH . 'wp-includes/css', $this->buildDir . '/wp-includes/css' );
        $this->log[] = $this->logEntry("Copied wp-includes assets");
    }

    private function createZip() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->log[] = $this->logEntry("ZipArchive not available", 'error');
            return;
        }

        $zip = new \ZipArchive();
        if ( $zip->open( $this->zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
            $this->log[] = $this->logEntry("Failed to create ZIP archive", 'error');
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $this->buildDir, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $files as $file ) {
            $filePath = $file->getRealPath();
            $relativePath = substr( $filePath, strlen( $this->buildDir ) + 1 );

            if ( $file->isDir() ) {
                $zip->addEmptyDir( $relativePath );
            } else {
                $zip->addFile( $filePath, $relativePath );
            }
        }

        $zip->close();

        $size = size_format( filesize($this->zipPath) );
        $this->log[] = $this->logEntry("ZIP archive created ({$size})");
    }

    private function rrmdir( $dir ) {
        if ( ! is_dir( $dir ) ) return;
        foreach ( scandir($dir) as $object ) {
            if ( $object === "." || $object === ".." ) continue;
            $path = $dir . "/" . $object;
            if ( is_dir( $path ) ) {
                $this->rrmdir( $path );
            } else {
                unlink( $path );
            }
        }
        rmdir( $dir );
    }

    private function copyDir( $src, $dst ) {
        if ( ! is_dir( $src ) ) return;
        wp_mkdir_p( $dst );
        $dir = opendir( $src );

        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( $file === '.' || $file === '..' ) continue;

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if ( is_dir( $srcPath ) ) {
                $this->copyDir( $srcPath, $dstPath );
            } else {
                copy( $srcPath, $dstPath );
            }
        }
        closedir( $dir );
    }

    private function logEntry( $message, $type = 'info' ) {
        $time = current_time('mysql');
        return "[{$time}] [{$type}] {$message}";
    }
}
