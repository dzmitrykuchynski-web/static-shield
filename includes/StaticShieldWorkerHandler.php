<?php

namespace StaticShield;

/**
 * Handles integration with Cloudflare Worker for serving static site.
 *
 * Redirects WordPress requests to the configured Worker URL
 * if Cloudflare Worker usage is enabled in plugin settings.
 *
 * @link       https://www.example.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */
class StaticShieldWorkerHandler
{
    /**
     * Initialize hooks for serving static content.
     *
     * Registers template_redirect action to intercept frontend requests
     * and forward them to the Cloudflare Worker if enabled.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action('template_redirect', [self::class, 'serveStatic']);
    }

    /**
     * Serve static site via Cloudflare Worker.
     *
     * - If Cloudflare Worker usage is disabled (option `static_shield_use_cf` is 0),
     *   fallback to normal WordPress rendering.
     * - If enabled, fetch Worker URL from settings (`static_shield_cf_worker`)
     *   and redirect the request there, preserving the request URI.
     *
     * @since 1.0.0
     * @return void
     */
    public static function serveStatic() {
        if (!get_option('static_shield_use_cf')) {
            return;
        }

        $workerUrl = trim(get_option('static_shield_cf_worker', ''));
        if (empty($workerUrl)) {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $targetUrl  = rtrim($workerUrl, '/') . $requestUri;

        wp_redirect($targetUrl, 302);
        exit;
    }
}
