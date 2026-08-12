# Advanced URL Rewriting for phpBB [![Build Status](https://github.com/vinny/phpbb-url-rewriting/actions/workflows/tests.yml/badge.svg)](https://github.com/vinny/phpbb-url-rewriting/actions)

Advanced URL Rewriting converts standard phpBB URLs into clean, human-readable links. It includes Open Graph meta tags for social media sharing, universal HTML link rewriting across the board, and automatic 301 redirects to ensure existing links continue working.

## Features

- **Human-friendly URLs:** Replaces parameter-heavy phpBB links with clean URLs (`topic-title-t123`, `forum-name-f45`, `post-p678`).
- **Custom forum slugs:** Define custom URL slugs for individual forums directly in forum management.
- **Member profile URLs:** Option to rewrite user profile links into clean URLs (`member/Username`).
- **Word length filtering:** Ignore short stop words below a configured character length when generating slugs.
- **Universal link rewriting:** Automatically rewrites standard URLs in HTML content across core pages and third-party extensions.
- **Two rewriting modes:** Choose between Advanced mode (includes topic/forum titles) and Simple mode (minimal short URLs).
- **Smart transliteration:** Automatically removes accents and special characters, converting text to clean ASCII slugs (`ação` becomes `acao`).
- **Automatic 301 redirects:** Forwards old standard URLs (such as `viewtopic.php?t=123`) to matching friendly URLs.
- **Open Graph support:** Injects meta tags on topic pages to display title, description, and preview images when shared on social networks.

## Requirements

- phpBB 3.3.0 or higher
- PHP 7.2.0 or higher
- Apache (with `mod_rewrite`) or NGINX web server

## Installation

1. Download the extension files.
2. Upload the files to `ext/vinny/urlrewriting` in your phpBB root directory.
3. Go to the **Admin Control Panel (ACP) > Customise > Manage extensions**.
4. Locate **Advanced URL Rewriting** under Disabled Extensions and click **Enable**.
5. Copy the web server rewrite rules from the extension's ACP module to your server configuration.

## Configuration & Web Server Rules

After enabling the extension, you must add rewrite rules to your web server so rewritten URLs resolve correctly.

Rules for Apache (`.htaccess`) and NGINX are available directly in the ACP under **ACP > Extension > Advanced URL Rewriting > Server Configuration**.

## FAQ

**Q: What is the main goal of this extension?**  
A: Its purpose is to rewrite standard phpBB URLs into a cleaner, human-friendly format.

**Q: Will old links break after enabling this extension?**  
A: No. As long as 301 redirects are enabled and your web server rules are set up correctly, old links will automatically redirect to their friendly counterparts.

**Q: Why do rewritten links return a 404 error after enabling the extension?**  
A: This usually means your web server has not received the rewrite rules yet, or the rules were placed in the wrong position in your configuration file.

**Q: Does this extension work on NGINX?**  
A: Yes. The Server Configuration tab in the ACP provides the required NGINX rules, which must be added manually to your server block.

**Q: Can this extension conflict with other URL rewriting extensions?**  
A: Yes. It is recommended to disable other URL rewriting extensions before enabling this one to prevent conflicts in server rules or link generation.

## Uninstallation & Fallback Rules

> [!CAUTION]
> If you decide to uninstall or disable this extension, you **must** implement fallback redirection rules on your web server. Otherwise, URLs indexed by search engines or shared on external sites (such as `topic-title-t123`) will return 404 errors.

Add the following fallback rules to your server configuration to redirect friendly URLs back to standard phpBB links (`viewtopic.php?t=123`):

### Apache (.htaccess Fallback)

Place these rules in your `.htaccess` file immediately after `RewriteEngine On`:

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

### NGINX Fallback

Add these rules inside your NGINX `server {}` block:

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

## Support

If you find this extension helpful, consider supporting its development on [Ko-fi](https://ko-fi.com/vinny1).

## License

[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)](license.txt)
