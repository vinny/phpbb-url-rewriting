# Advanced URL Rewriting for phpBB
This extension transforms your standard phpBB URLs into human-readable, and cleanly structured links. Alongside its core URL rewriting capabilities, it comes packed with a built-in XML Sitemap Generator, Open Graph Tags for rich social media sharing, and automatic 301 redirects to ensure your existing links never break.

## Features
- **Human-Friendly URLs:** Replaces complex, parameter-heavy phpBB links with clean, descriptive URLs (e.g., `your-topic-title-t10`).
- **Smart Transliteration:** Automatically removes accents and special characters, creating clean ASCII slugs for maximum global compatibility.
- **Auto 301 Redirects:** Seamlessly forwards old standard URLs to the new friendly format, preserving existing links.
- **Integrated XML Sitemap:** Automatically generates a comprehensive sitemap of forums and topics, configurable and cached for optimal performance.
- **Open Graph Support:** Enhances social media sharing by extracting images and text from posts to create rich preview cards. 

## FAQ

**Q: Is this an SEO extension?**

A: No. This extension does not focus on SEO (Search Engine Optimization). Its sole purpose is to rewrite standard phpBB URLs into a cleaner, human-friendly format to improve the aesthetics and readability of your links.

**Q: Will my old links break after installing this?**

A: No! The extension includes an automatic 301 redirect feature. If someone visits an old standard URL (like `viewtopic.php?p=123`), they will be automatically redirected to the new rewritten URL without any issues.

**Q: I enabled URL rewriting, but now all my forum links return a 404 (Not Found) error. What happened?**

A: This means your server is not yet configured to handle the rewritten URLs. Use the extension's Server Configuration tab in the ACP to copy the required rules, then add them manually to your Apache `.htaccess` file or your NGINX server block.

**Q: Does this extension work on NGINX servers?**

A: Yes. The ACP module shows the required NGINX rewrite rules, but you must add them manually to your server block.

**Q: What happens to topic or forum titles that have accents or non-Latin characters?**

A: The extension has a built-in "Smart Transliteration" feature. It automatically removes accents and converts special characters into standard ASCII (e.g., converting "ação" to "acao") to ensure your URLs remain clean and functional.

**Q: What is the difference between Simple and Advanced modes?**

A: Simple mode uses minimal text (e.g., `forum-f123`), keeping URLs as short as possible. Advanced mode includes the forum or topic titles within the URL (e.g., `my-topic-title-t123`), which provides more context and readability.

**Q: Will this conflict with other SEO extensions?**

A: It is highly recommended to disable any other URL rewriting extensions before using this one to prevent conflicts in your server rules or URL generation.

## Requirements
- phpBB >= 3.3.0
- PHP >= 7.2.0 (PHP 8.x compatible)
- Apache (with mod_rewrite) or NGINX web server

## Uninstallation & Fallback ⚠️
If you decide to uninstall this extension, it is **crucial** to implement fallback redirection rules to prevent 404 errors. The URLs previously indexed by search engines (e.g., `topic-title-t10`) need to smoothly redirect back to the standard phpBB format (`viewtopic.php?t=10`) using 301 Permanent Redirects.

### Fallback Rules for Apache (.htaccess)
If you are using Apache, add the following code to the `.htaccess` file in your forum's root directory, immediately after `RewriteEngine On`:

```apache
# ----------------------------------------------------------------------
# FALLBACK: REDIRECT FRIENDLY URLS BACK TO STANDARD PHPBB
# Use only if the "Advanced URL Rewriting" extension is uninstalled
# ----------------------------------------------------------------------

# 1. Redirect post links (e.g., slug-t12-p34 or post-p34)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ %1/viewtopic.php?t=$1&p=$2 [QSA,R=301,L]

RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-p([0-9]+)$
RewriteRule ^.*-p([0-9]+)$ %1/viewtopic.php?p=$1 [QSA,R=301,L]

# 2. Redirect topic links (e.g., slug-t123 or topic-t123)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)$
RewriteRule ^.*-t([0-9]+)$ %1/viewtopic.php?t=$1 [QSA,R=301,L]

# 3. Redirect forum links (e.g., slug-f45 or forum-f45)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-f([0-9]+)$
RewriteRule ^.*-f([0-9]+)$ %1/viewforum.php?f=$1 [QSA,R=301,L]
```

### Fallback Rules for NGINX
If you are using NGINX, add the following code to your server block configuration (usually inside the `server {}` block):

```nginx
# ----------------------------------------------------------------------
# FALLBACK: REDIRECT FRIENDLY URLS BACK TO STANDARD PHPBB
# Use only if the "Advanced URL Rewriting" extension is uninstalled
# ----------------------------------------------------------------------

# 1. Redirect post links (e.g., slug-t12-p34 or post-p34)
rewrite ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$ $1/viewtopic.php?t=$2&p=$3 permanent;
rewrite ^(.*)/[^/]+-p([0-9]+)$ $1/viewtopic.php?p=$2 permanent;

# 2. Redirect topic links (e.g., slug-t123 or topic-t123)
rewrite ^(.*)/[^/]+-t([0-9]+)$ $1/viewtopic.php?t=$2 permanent;

# 3. Redirect forum links (e.g., slug-f45 or forum-f45)
rewrite ^(.*)/[^/]+-f([0-9]+)$ $1/viewforum.php?f=$2 permanent;
```

These rules tell search engines that the pages have permanently moved back to their original locations, preserving your site's link equity.

## Support this extension
Buy me a coffee and support this extension: https://ko-fi.com/vinny1

## License
GPL-2.0-only
