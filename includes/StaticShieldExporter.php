<?php
namespace StaticShield;

/**
 * Class StaticShieldExporter
 *
 * Exports the WordPress site as static files and creates a ZIP archive.
 *
 * @link       https://www.alreadymedia.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 */
class StaticShieldExporter {
    /**
     * Path to the temporary build directory.
     *
     * @var string
     */
    private $buildDir;

    /**
     * Path to the final ZIP archive.
     *
     * @var string
     */
    private $zipPath;

    /**
     * Log of export actions.
     *
     * @var array
     */
    private $log = [];

    /**
     * Timestamp when export started.
     *
     * @var float
     */
    private $startTime;

    public function __construct() {
        $uploads = wp_get_upload_dir();

        $baseDir        = $uploads['basedir'] . '/static-shield-builds';
        $this->buildDir = $baseDir . '/temp';
        $this->zipPath  = $baseDir . '/static-shield-builds.zip';

        $this->startTime = microtime(true);
    }

    /**
     * Run the full export process.
     *
     * @return string Path to the created ZIP archive.
     */
    public function runExport() {
        update_option('static_shield_last_log', []);

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

    /**
     * Get the export log.
     *
     * @return array
     */
    public function getLog() {
        return get_option('static_shield_last_log', []);
    }

    /**
     * Prepare the build directory by cleaning old files.
     */
    private function prepareBuildDir() {
        if ( file_exists( $this->buildDir ) ) {
            $this->rrmdir( $this->buildDir );
        }
        wp_mkdir_p( $this->buildDir );

        if ( file_exists( $this->zipPath ) ) {
            unlink( $this->zipPath );
        }

        $this->log[] = $this->logEntry("Build directory prepared: {$this->buildDir}");
    }

    /**
     * Export a single URL to a static HTML file.
     *
     * @param string $url URL to export.
     * @param string $filename Filename to save the HTML as.
     */
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

    /**
     * Export all pages and posts to HTML files.
     */
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

    /**
     * Copy uploads folder to the build directory, excluding the build itself.
     */
    private function copyUploads() {
        $uploads = wp_get_upload_dir();

        $this->copyDir(
            $uploads['basedir'],
            $this->buildDir . '/uploads',
            [ $uploads['basedir'] . '/static-shield-builds' ]
        );

        $this->log[] = $this->logEntry("Copied uploads directory");
    }

    /**
     * Copy wp-includes JS and CSS assets to the build directory.
     */
    private function copyWpIncludesAssets() {
        $this->copyDir( ABSPATH . 'wp-includes/js', $this->buildDir . '/wp-includes/js' );
        $this->copyDir( ABSPATH . 'wp-includes/css', $this->buildDir . '/wp-includes/css' );
        $this->log[] = $this->logEntry("Copied wp-includes assets");
    }

    /**
     * Create a ZIP archive from the build directory.
     */
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

    /**
     * Recursively remove a directory and its contents.
     *
     * @param string $dir Directory path.
     */
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

    /**
     * Recursively copy a directory to another location, excluding some paths.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     * @param array  $exclude Paths to exclude.
     */
    private function copyDir( $src, $dst, $exclude = [] ) {
        if ( ! is_dir( $src ) ) return;
        wp_mkdir_p( $dst );
        $dir = opendir( $src );

        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( $file === '.' || $file === '..' ) continue;

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            foreach ( $exclude as $ex ) {
                if ( strpos( $srcPath, $ex ) === 0 ) {
                    continue 2;
                }
            }

            if ( is_dir( $srcPath ) ) {
                $this->copyDir( $srcPath, $dstPath, $exclude );
            } else {
                copy( $srcPath, $dstPath );
            }
        }
        closedir( $dir );
    }

    /**
     * Create a timestamped log entry.
     *
     * @param string $message Log message.
     * @param string $type Log type (info, error, etc.).
     *
     * @return string Formatted log entry.
     */
    private function logEntry( $message, $type = 'info' ) {
        $time = current_time('mysql');
        $entry = "[{$time}] [{$type}] {$message}";

        $currentLog = get_option('static_shield_last_log', []);
        $currentLog[] = $entry;
        update_option('static_shield_last_log', $currentLog);

        return $entry;
    }
}
