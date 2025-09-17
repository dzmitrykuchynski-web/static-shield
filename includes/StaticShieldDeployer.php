<?php

namespace StaticShield;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

/**
 * Handles deployment of static builds to Cloudflare R2 storage.
 *
 * Provides methods to upload ZIP archives containing static site builds
 * into the configured R2 bucket using AWS SDK for PHP.
 *
 * @link       https://www.example.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */
class StaticShieldDeployer {
    /**
     * Cloudflare R2 access key ID.
     *
     * @since 1.0.0
     * @var string|null
     */
    private $accessKeyId;

    /**
     * Cloudflare R2 access key secret.
     *
     * @since 1.0.0
     * @var string|null
     */
    private $accessKeySecret;

    /**
     * Cloudflare account ID.
     *
     * @since 1.0.0
     * @var string|null
     */
    private $accountId;

    /**
     * Cloudflare R2 bucket name.
     *
     * @since 1.0.0
     * @var string|null
     */
    private $bucket;

    /**
     * Constructor.
     *
     * Initializes credentials and bucket configuration
     * from WordPress options.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->accessKeyId     = get_option('static_shield_cf_access_key_id');
        $this->accessKeySecret = get_option('static_shield_cf_secret_access_key');
        $this->accountId       = get_option('static_shield_cf_account_id');
        $this->bucket          = get_option('static_shield_cf_bucket');
    }

    /**
     * Upload a ZIP archive to the configured R2 bucket.
     *
     * @since 1.0.0
     *
     * @param string $zipPath Absolute path to the ZIP file to upload.
     * @return bool True if upload succeeded, false otherwise.
     */
    public function uploadToR2($zipPath) {
        if (!file_exists($zipPath)) {
            $this->log("[error] File not found: $zipPath");
            return false;
        }

        $objectKey = '';
        if ($zipPath !== null) {
            $objectKey = basename($zipPath);
        }

        try {
            $credentials = new Credentials($this->accessKeyId, $this->accessKeySecret);

            $s3_client = new S3Client([
                'region'      => 'auto',
                'endpoint'    => "https://{$this->accountId}.r2.cloudflarestorage.com",
                'version'     => 'latest',
                'credentials' => $credentials,
            ]);

            $result = $s3_client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $objectKey,
                'SourceFile'  => $zipPath,
                'ContentType' => 'application/zip',
            ]);

            $this->log("[info] Uploaded to R2 successfully. ETag: " . $result['ETag']);
            return true;

        } catch (AwsException $e) {
            $this->log("[error] Upload failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Write a log entry into WordPress options.
     *
     * Appends a timestamped log message to `static_shield_last_log`.
     *
     * @since 1.0.0
     *
     * @param string $message Log message content.
     * @param string $type    Message type (info|error). Default: 'info'.
     * @return void
     */
    private function log($message, $type = 'info') {
        $time = current_time('mysql');
        $entry = "[{$time}] [{$type}] {$message}";
        $log = get_option('static_shield_last_log', []);
        $log[] = $entry;
        update_option('static_shield_last_log', $log);
    }
}
