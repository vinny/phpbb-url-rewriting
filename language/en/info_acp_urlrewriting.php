<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_URLREWRITING_TITLE'                => 'Advanced URL Rewriting',
	'ACP_URLREWRITING'                      => 'Advanced URL Rewriting',
	'ACP_URLREWRITING_SETTINGS'             => 'Settings',
	'ACP_URLREWRITING_SERVER'               => 'Server Configuration',
	'ACP_URLREWRITING_FAQ'                  => 'FAQ and guides',

	'VINNY_URL_REWRITE_ENABLE'              => 'Enable URL Rewriting',
	'VINNY_URL_SITEMAP_ENABLE'              => 'Enable Sitemap',
	'VINNY_URL_OPENGRAPH_ENABLE'            => 'Enable Open Graph Tags',
	'VINNY_URL_TRANSLIT_ENABLE'             => 'Enable Transliteration',
	'VINNY_URL_REDIRECT_ENABLE'             => 'Enable 301 Redirects',

	'ACP_URLREWRITING_SITEMAP'              => 'Sitemap Settings',
	'ACP_URLREWRITING_SITEMAP_EXPLAIN'      => 'Configure the XML Sitemap generation settings.',

	'VINNY_URL_SITEMAP_CACHE_TIME'          => 'Cache Time (Hours)',
	'VINNY_URL_SITEMAP_CACHE_TIME_EXPLAIN'  => 'Time in hours to cache the sitemap. Set to 0 to disable caching.',
	'VINNY_URL_SITEMAP_LIMIT'               => 'URL Limit',
	'VINNY_URL_SITEMAP_LIMIT_EXPLAIN'       => 'Maximum number of URLs in the sitemap (Protocol limit is 50000).',
	'VINNY_URL_SITEMAP_EXCLUDED'            => 'Excluded Forums',
	'VINNY_URL_SITEMAP_EXCLUDED_EXPLAIN'    => 'Select forums to exclude from the sitemap. Use CTRL+Click to select multiple.',
	'VINNY_URL_SITEMAP_PRIORITY'            => 'Priority',
	'VINNY_URL_SITEMAP_PRIORITY_EXPLAIN'    => 'The priority of this URL relative to other URLs on your site.',
	'VINNY_URL_SITEMAP_CHANGEFREQ'          => 'Change Frequency',
	'VINNY_URL_SITEMAP_CHANGEFREQ_EXPLAIN'  => 'How frequently the page is likely to change.',
	'VINNY_URL_CHANGEFREQ_ALWAYS'           => 'Always',
	'VINNY_URL_CHANGEFREQ_HOURLY'           => 'Hourly',
	'VINNY_URL_CHANGEFREQ_DAILY'            => 'Daily',
	'VINNY_URL_CHANGEFREQ_WEEKLY'           => 'Weekly',
	'VINNY_URL_CHANGEFREQ_MONTHLY'          => 'Monthly',
	'VINNY_URL_CHANGEFREQ_YEARLY'           => 'Yearly',
	'VINNY_URL_CHANGEFREQ_NEVER'            => 'Never',

	'ACP_URLREWRITING_SETTINGS_EXPLAIN'     => 'Configure the general settings for URL Rewriting. Before changing any option below, reading the FAQ is essential to understand how this extension works. Read it carefully first.',
	'ACP_URLREWRITING_SERVER_EXPLAIN'       => 'Copy the rewrite rules for your web server. This page does not read or write server configuration files.',
	'ACP_URLREWRITING_FAQ_EXPLAIN'          => 'Usage guides, configuration steps, feature behavior, and precautions before disabling the extension.',

	// Explanations for Settings
	'VINNY_URL_REWRITE_ENABLE_EXPLAIN'      => 'Enable or disable the rewriting of URLs to Human-Friendly format. <b>IMPORTANT: This requires web server rewrite rules.</b> Read the FAQ for more information.',
	'VINNY_URL_SITEMAP_ENABLE_EXPLAIN'      => 'Enable or disable the XML Sitemap generation.',
	'VINNY_URL_OPENGRAPH_ENABLE_EXPLAIN'    => 'Add Open Graph meta tags for better social media sharing.',
	'VINNY_URL_TRANSLIT_ENABLE_EXPLAIN'     => 'Remove accents and special characters from URLs (e.g., "ação" becomes "acao").',
	'VINNY_URL_REDIRECT_ENABLE_EXPLAIN'     => 'Redirect old standard URLs to the new friendly URLs via 301 Redirect.',

	'VINNY_URL_REWRITE_MODE'                => 'URL Rewriting Mode',
	'VINNY_URL_REWRITE_MODE_EXPLAIN'        => 'Select the format of the friendly URLs:<br><b>Simple:</b> e.g., forum-f123, topic-t456<br><b>Advanced:</b> e.g., forum-name-f123, topic-title-t456',
	'VINNY_URL_MODE_SIMPLE'                 => 'Simple',
	'VINNY_URL_MODE_ADVANCED'               => 'Advanced',

	'ACP_URLREWRITING_HTACCESS_IMPORTANT'   => 'Apache .htaccess configuration',
	'ACP_URLREWRITING_HTACCESS_RULE_EXPLAIN'=> 'Copy and paste the following code into your .htaccess, after the line <code>RewriteEngine On</code>:',
	'ACP_URLREWRITING_NGINX_IMPORTANT'      => 'NGINX configuration',
	'ACP_URLREWRITING_NGINX_RULE_EXPLAIN'   => 'Add the following rules to your NGINX server block configuration:',
	'ACP_URLREWRITING_SELECT_SERVER'        => 'Select Web Server',
	'ACP_URLREWRITING_SERVER_APACHE'        => 'Apache (.htaccess)',
	'ACP_URLREWRITING_SERVER_NGINX'         => 'NGINX',
	'COPY_CODE'                             => 'Copy Code',

	'ACP_URLREWRITING_APACHE_RULES_ADVANCED'   => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANT: Place these rules before the default phpBB app.php rewrites.
# Topics
RewriteRule ^.*-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ viewtopic.php?t=$1&p=$2 [QSA,L]
# Forums
RewriteRule ^.*-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]

# Sitemap
RewriteRule ^sitemap\.xml$ app.php/sitemap.xml [QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_APACHE_RULES_SIMPLE'     => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANT: Place these rules before the default phpBB app.php rewrites.
# Topics
RewriteRule ^topic-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
# Forums
RewriteRule ^forum-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]

# Sitemap
RewriteRule ^sitemap\.xml$ app.php/sitemap.xml [QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_ADVANCED'    => '# Vinny URL Rewriting NGINX Rules - begin
# Topics
rewrite ^/(.*)-t([0-9]+)$ /viewtopic.php?t=$2 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
rewrite ^/(.*)-t([0-9]+)-p([0-9]+)$ /viewtopic.php?t=$2&p=$3 last;
# Forums
rewrite ^/(.*)-f([0-9]+)$ /viewforum.php?f=$2 last;

# Sitemap
rewrite ^/sitemap\.xml$ /app.php/sitemap.xml last;
# Vinny URL Rewriting NGINX Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_SIMPLE'      => '# Vinny URL Rewriting NGINX Rules - begin
# Topics
rewrite ^/topic-t([0-9]+)$ /viewtopic.php?t=$1 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
# Forums
rewrite ^/forum-f([0-9]+)$ /viewforum.php?f=$1 last;

# Sitemap
rewrite ^/sitemap\.xml$ /app.php/sitemap.xml last;
# Vinny URL Rewriting NGINX Rules - end',
	'ACP_URLREWRITING_APACHE_FALLBACK_RULES'   => '# Vinny URL Rewriting Apache Fallback Rules - begin
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
# Vinny URL Rewriting Apache Fallback Rules - end',
	'ACP_URLREWRITING_NGINX_FALLBACK_RULES'    => '# Vinny URL Rewriting NGINX Fallback Rules - begin
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
# Vinny URL Rewriting NGINX Fallback Rules - end',

	'ACP_URLREWRITING_FAQ_OVERVIEW'         => 'Overview',
	'ACP_URLREWRITING_FAQ_OVERVIEW_TEXT'    => 'This extension transforms standard phpBB URLs into friendly links and adds helper features for XML sitemaps, Open Graph, and 301 redirects.',
	'ACP_URLREWRITING_FAQ_FEATURES'         => 'Main features',
	'ACP_URLREWRITING_FAQ_FEATURES_TEXT'    => '<ul><li>Friendly URLs: replaces parameter-based links with clean, readable URLs.</li><li>Transliteration: removes accents and special characters to generate ASCII slugs.</li><li>301 redirects: forwards old URLs to the new friendly URLs.</li><li>XML sitemap: generates a list of public forums and topics for search engines.</li><li>Open Graph: adds metadata to improve link previews on social networks.</li></ul>',

	'ACP_URLREWRITING_FAQ_FUNCTIONS'        => 'What each option does',
	'ACP_URLREWRITING_FAQ_REWRITE_ENABLE'   => 'Enables replacement of standard phpBB links with friendly URLs. The web server rewrite rules are also required for those URLs to resolve.',
	'ACP_URLREWRITING_FAQ_REWRITE_MODE'     => 'Simple mode uses short URLs, such as forum-f123 and topic-t456. Advanced mode includes the forum or topic title, such as forum-name-f123 and topic-title-t456.',
	'ACP_URLREWRITING_FAQ_TRANSLIT'         => 'When enabled, accented characters are converted to non-accented equivalents, for example ação to acao.',
	'ACP_URLREWRITING_FAQ_REDIRECTS'        => 'When enabled, old standard URLs such as viewtopic.php?t=123 redirect to the matching friendly URL.',
	'ACP_URLREWRITING_FAQ_OPENGRAPH'        => 'Adds Open Graph tags on topics so shared links can show title, description, and an image when available.',

	'ACP_URLREWRITING_FAQ_SITEMAP'          => 'How the sitemap works',
	'ACP_URLREWRITING_FAQ_SITEMAP_TEXT'     => 'The sitemap is generated as XML and includes the board index, public forums, and visible topics. Password-protected forums, link forums, and excluded forums should not be published.',
	'ACP_URLREWRITING_FAQ_SITEMAP_CACHE'    => 'Defines how many hours the generated XML remains cached. Use 0 only when you need to disable caching.',
	'ACP_URLREWRITING_FAQ_SITEMAP_LIMIT'    => 'Controls the maximum number of URLs in the XML. The sitemap protocol limit is 50000 URLs per file.',
	'ACP_URLREWRITING_FAQ_SITEMAP_EXCLUDED' => 'Lets you remove specific forums from the sitemap, even when they are public.',

	'ACP_URLREWRITING_FAQ_SERVER'           => 'How to configure the server',
	'ACP_URLREWRITING_FAQ_SERVER_TEXT'      => 'The rules displayed in the Server Configuration module must be copied manually to Apache or NGINX.',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS'     => 'Step by step',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS_TEXT'=> '<ol><li>Choose the URL mode in Settings.</li><li>Open the Server Configuration module and select Apache or NGINX.</li><li>Copy the matching block and paste it into your web server configuration file.</li><li>Purge the phpBB cache and test forum, topic, post, and sitemap.xml links.</li></ol>',

	'ACP_URLREWRITING_FAQ_QUESTIONS'        => 'Frequently asked questions',
	'ACP_URLREWRITING_FAQ_Q_SEO'            => 'Is this an SEO extension?',
	'ACP_URLREWRITING_FAQ_A_SEO'            => 'No. Its purpose is to rewrite standard phpBB URLs into a cleaner and more readable format.',
	'ACP_URLREWRITING_FAQ_Q_OLD_LINKS'      => 'Will old links break after installing it?',
	'ACP_URLREWRITING_FAQ_A_OLD_LINKS'      => 'No, as long as 301 redirects are enabled and the server rules are configured correctly.',
	'ACP_URLREWRITING_FAQ_Q_404'            => 'I enabled the extension and links return 404. What happened?',
	'ACP_URLREWRITING_FAQ_A_404'            => 'This usually means the web server has not received the rewrite rules yet, or the rules were placed in the wrong position.',
	'ACP_URLREWRITING_FAQ_Q_NGINX'          => 'Does it work with NGINX?',
	'ACP_URLREWRITING_FAQ_A_NGINX'          => 'Yes. The Server Configuration module shows the NGINX rules, but they must be added manually to the server block.',
	'ACP_URLREWRITING_FAQ_Q_CONFLICT'       => 'Can it conflict with other SEO or URL rewriting extensions?',
	'ACP_URLREWRITING_FAQ_A_CONFLICT'       => 'Yes. It is recommended to disable other URL rewriting extensions to avoid conflicts in server rules and link generation.',

	'ACP_URLREWRITING_FAQ_UNINSTALL'        => 'Disabling and fallback rules',
	'ACP_URLREWRITING_FAQ_UNINSTALL_WARNING'=> 'Before disabling or removing the extension, configure fallback rules to avoid 404 errors for friendly URLs that were already indexed or shared.',
	'ACP_URLREWRITING_FAQ_UNINSTALL_TEXT'   => 'The rules below redirect friendly URLs back to standard phpBB URLs using permanent 301 redirects.',
	'ACP_URLREWRITING_FAQ_FALLBACK_APACHE'  => 'Fallback rules for Apache',
	'ACP_URLREWRITING_FAQ_FALLBACK_NGINX'   => 'Fallback rules for NGINX',

	'VIEW_SITEMAP'                          => 'View Sitemap',
	'VINNY_URL_SITEMAP_DISABLED'            => 'The sitemap is unavailable because the URL Rewriting extension or the sitemap feature is disabled.',
));
