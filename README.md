# Static Shield

**Tags:** static, cloudflare, r2, worker, dns, export  
**Requires at least:** 6.0  
**Tested up to:** 6.6  
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Static Shield converts your WordPress site into a static build, uploads it to **Cloudflare R2**, manages **DNS records**, and integrates with **Cloudflare Workers**.  
This helps improve performance and security by reducing PHP/MySQL load.

---

## ✨ Features

- 📦 Export WordPress into a static ZIP archive.
- ☁️ Upload builds to **Cloudflare R2**.
- 🔄 Automatic build regeneration after post/page updates.
- 🛡 Manage DNS records via Cloudflare Worker API.
- ⚙️ Intuitive admin dashboard in WordPress.
- 🔑 Store and manage Cloudflare API keys and Worker URLs.
- ⚡ AJAX endpoints for dynamic settings management.

---

## 📥 Installation

1. Download or clone the repository.
2. Place the `static-shield` folder into `wp-content/plugins/`.
3. Activate the plugin in **Dashboard → Plugins**.
4. Go to **Static Shield → Settings** and configure Cloudflare credentials and Worker URL.

---

## ⚙️ Usage

- **Manual Export**  
  Run a manual export from the settings page. A ZIP archive will be generated and made available for download.

- **Automatic Export**  
  Each time you update a post or page, Static Shield regenerates the static build and uploads it to Cloudflare (if enabled).

- **DNS Management**  
  From the **DNS** tab you can:
    - List all records.
    - Add new records (`A`, `CNAME`, `TXT`, etc.).
    - Delete records.

---

## 🔧 Hooks & AJAX Endpoints

### Actions
- `save_post` — triggers automatic export.
- `admin_post_static_shield_manual_export` — manual export handler.

### AJAX
- `wp_ajax_static_shield_get_logs` — fetch export logs.
- `wp_ajax_static_shield_save_domain_settings` — save domain settings.
- `wp_ajax_static_shield_save_worker_settings` — save Worker + R2 settings.
- `wp_ajax_static_shield_dns_list` — list DNS records.
- `wp_ajax_static_shield_dns_add` — add DNS record.
- `wp_ajax_static_shield_dns_delete` — delete DNS record.

---

## 🔐 Security

- All AJAX requests require `manage_options`.
- WordPress Nonce is used for CSRF protection.
- Cloudflare API keys are stored in `wp_options`, accessible only by administrators.

---

## 📜 Changelog

### 1.0.0
- Initial release.
- Static export to ZIP.
- Cloudflare R2 integration.
- DNS manager via Worker.
- Automatic export after post updates.

---

## 📄 License

This plugin is licensed under the **GPLv2 or later**.  
