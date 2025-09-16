<?php

namespace StaticShield;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class StaticShieldDeployer {
    private $accessKeyId;
    private $accessKeySecret;
    private $accountId;
    private $bucket;

    public function __construct() {
        $this->accessKeyId     = get_option('static_shield_cf_access_key_id');
        $this->accessKeySecret = get_option('static_shield_cf_secret_access_key');
        $this->accountId       = get_option('static_shield_cf_account_id');
        $this->bucket          = get_option('static_shield_cf_bucket');
    }

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

    private function log($message, $type = 'info') {
        $time = current_time('mysql');
        $entry = "[{$time}] [{$type}] {$message}";
        $log = get_option('static_shield_last_log', []);
        $log[] = $entry;
        update_option('static_shield_last_log', $log);
    }
}
