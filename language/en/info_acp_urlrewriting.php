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
	'ACP_URLREWRITING_SERVER'               => 'Server configuration',
	'ACP_URLREWRITING_FAQ'                  => 'FAQ and guides',

	'VINNY_URL_REWRITE_ENABLE'              => 'Enable URL rewriting',
	'VINNY_URL_OPENGRAPH_ENABLE'            => 'Enable Open Graph tags',
	'VINNY_URL_TRANSLIT_ENABLE'             => 'Enable transliteration',
	'VINNY_URL_REDIRECT_ENABLE'             => 'Enable 301 redirects',
	'VINNY_URL_MIN_WORD_LENGTH'             => 'Minimum word length for URLs',
	'VINNY_URL_MEMBERS_ENABLE'              => 'Enable member profile URLs',

	'VINNY_URL_FORUM_SLUG'                  => 'Custom forum URL slug',
	'VINNY_URL_FORUM_SLUG_EXPLAIN'          => 'Optional custom URL text for this forum. If left blank, the forum name will be used.',
	'VINNY_URL_FORUM_SLUG_PLACEHOLDER'      => 'forum-name',
	'VINNY_URL_FORUM_SLUG_TOO_LONG'         => 'The custom forum URL slug must not exceed 255 characters.',
	'VINNY_URL_FORUM_SLUG_WRONG_FORMAT'     => 'The custom forum URL slug contains invalid characters.',

	'ACP_URLREWRITING_SETTINGS_EXPLAIN'     => 'Configure general URL rewriting settings. Reading the FAQ is recommended to understand how this extension works.',
	'ACP_URLREWRITING_SERVER_EXPLAIN'       => 'Copy rewrite rules for your web server. This page does not modify server configuration files automatically.',
	'ACP_URLREWRITING_FAQ_EXPLAIN'          => 'Usage guides, configuration steps, feature behavior, and precautions before disabling the extension.',

	// Explanations for Settings
	'VINNY_URL_REWRITE_ENABLE_EXPLAIN'      => 'Enable rewriting phpBB URLs into a human-friendly format. <b>Note: Requires web server rewrite rules.</b> See the FAQ for details.',
	'VINNY_URL_OPENGRAPH_ENABLE_EXPLAIN'    => 'Add Open Graph meta tags for improved social media link previews.',
	'VINNY_URL_TRANSLIT_ENABLE_EXPLAIN'     => 'Remove accents and special characters from URLs (e.g., “ação” becomes “acao”).',
	'VINNY_URL_REDIRECT_ENABLE_EXPLAIN'     => 'Redirect standard phpBB URLs to friendly URLs using 301 redirects.',
	'VINNY_URL_MIN_WORD_LENGTH_EXPLAIN'     => 'Words shorter than this number of characters will be omitted from generated URLs (0 to disable).',
	'VINNY_URL_MEMBERS_ENABLE_EXPLAIN'      => 'Rewrite member profile links to a clean format (e.g. member/Username).',

	'VINNY_URL_REWRITE_MODE'                => 'URL rewriting mode',
	'VINNY_URL_REWRITE_MODE_EXPLAIN'        => 'Select the format of friendly URLs:<br><b>Simple:</b> forum-f123, topic-t456<br><b>Advanced:</b> forum-name-f123, topic-title-t456',
	'VINNY_URL_MODE_SIMPLE'                 => 'Simple',
	'VINNY_URL_MODE_ADVANCED'               => 'Advanced',

	'ACP_URLREWRITING_HTACCESS_IMPORTANT'   => 'Apache .htaccess configuration',
	'ACP_URLREWRITING_HTACCESS_RULE_EXPLAIN'=> 'Copy and paste the following code into `.htaccess`, immediately after `RewriteEngine On`:',
	'ACP_URLREWRITING_NGINX_IMPORTANT'      => 'NGINX configuration',
	'ACP_URLREWRITING_NGINX_RULE_EXPLAIN'   => 'Add the following rules to your NGINX server block configuration:',
	'ACP_URLREWRITING_SELECT_SERVER'        => 'Select web server',
	'ACP_URLREWRITING_SERVER_APACHE'        => 'Apache (.htaccess)',
	'ACP_URLREWRITING_SERVER_NGINX'         => 'NGINX',
	'COPY_CODE'                             => 'Copy code',

	'ACP_URLREWRITING_APACHE_RULES_ADVANCED'   => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANT: Place these rules before the default phpBB app.php rewrites.
# Topics
RewriteRule ^.*-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ viewtopic.php?t=$1&p=$2 [QSA,L]
# Forums
RewriteRule ^.*-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]
# Members
RewriteRule ^member/(.+)$ memberlist.php?mode=viewprofile&un=$1 [B,QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_APACHE_RULES_SIMPLE'     => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANT: Place these rules before the default phpBB app.php rewrites.
# Topics
RewriteRule ^topic-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
# Forums
RewriteRule ^forum-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]
# Members
RewriteRule ^member/(.+)$ memberlist.php?mode=viewprofile&un=$1 [B,QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_ADVANCED'    => '# Vinny URL Rewriting NGINX Rules - begin
# Topics
rewrite ^/(.*)-t([0-9]+)$ /viewtopic.php?t=$2 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
rewrite ^/(.*)-t([0-9]+)-p([0-9]+)$ /viewtopic.php?t=$2&p=$3 last;
# Forums
rewrite ^/(.*)-f([0-9]+)$ /viewforum.php?f=$2 last;
# Members
rewrite ^/member/(.+)$ /memberlist.php?mode=viewprofile&un=$1 last;
# Vinny URL Rewriting NGINX Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_SIMPLE'      => '# Vinny URL Rewriting NGINX Rules - begin
# Topics
rewrite ^/topic-t([0-9]+)$ /viewtopic.php?t=$1 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
# Forums
rewrite ^/forum-f([0-9]+)$ /viewforum.php?f=$1 last;
# Members
rewrite ^/member/(.+)$ /memberlist.php?mode=viewprofile&un=$1 last;
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

# 4. Redirect member profile links (e.g., member/username)
RewriteRule ^member/(.+)$ memberlist.php?mode=viewprofile&un=$1 [B,QSA,R=301,L]
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

# 4. Redirect member profile links (e.g., member/username)
rewrite ^/member/(.+)$ /memberlist.php?mode=viewprofile&un=$1 permanent;
# Vinny URL Rewriting NGINX Fallback Rules - end',

	'ACP_URLREWRITING_FAQ_OVERVIEW'         => 'Overview',
	'ACP_URLREWRITING_FAQ_OVERVIEW_TEXT'    => 'This extension transforms standard phpBB URLs into friendly links and adds helper features for Open Graph and 301 redirects.',
	'ACP_URLREWRITING_FAQ_FEATURES'         => 'Main features',
	'ACP_URLREWRITING_FAQ_FEATURES_TEXT'    => '<ul><li>Friendly URLs: replaces parameter-based links with clean, readable URLs.</li><li>Transliteration: removes accents and special characters to generate ASCII slugs.</li><li>301 redirects: forwards old URLs to matching friendly URLs.</li><li>Open Graph: adds metadata to improve link previews on social networks.</li></ul>',

	'ACP_URLREWRITING_FAQ_FUNCTIONS'        => 'What each option does',
	'ACP_URLREWRITING_FAQ_REWRITE_ENABLE'   => 'Enables replacement of standard phpBB links with friendly URLs. Web server rewrite rules are also required for these URLs to resolve.',
	'ACP_URLREWRITING_FAQ_REWRITE_MODE'     => 'Simple mode uses short URLs, such as forum-f123 and topic-t456. Advanced mode includes the forum or topic title, such as forum-name-f123 and topic-title-t456.',
	'ACP_URLREWRITING_FAQ_TRANSLIT'         => 'Converts accented characters to non-accented equivalents (e.g., “ação” becomes “acao”).',
	'ACP_URLREWRITING_FAQ_REDIRECTS'        => 'Redirects old standard URLs (e.g., viewtopic.php?t=123) to matching friendly URLs using 301 redirects.',
	'ACP_URLREWRITING_FAQ_OPENGRAPH'        => 'Adds Open Graph tags to topics so shared links display title, description, and preview image when available.',

	'ACP_URLREWRITING_FAQ_SERVER'           => 'How to configure the server',
	'ACP_URLREWRITING_FAQ_SERVER_TEXT'      => 'Rewrite rules displayed in the Server configuration module must be copied manually to your Apache or NGINX server configuration.',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS'     => 'Step-by-step setup',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS_TEXT'=> '<ol><li>Choose the URL mode in Settings.</li><li>Open Server configuration and select Apache or NGINX.</li><li>Copy the matching block and paste it into your web server configuration file.</li><li>Purge the phpBB cache and test forum, topic, and post links.</li></ol>',

	'ACP_URLREWRITING_FAQ_QUESTIONS'        => 'Frequently asked questions',
	'ACP_URLREWRITING_FAQ_Q_HUMAN_FRIENDLY' => 'What is the main goal of this extension?',
	'ACP_URLREWRITING_FAQ_A_HUMAN_FRIENDLY' => 'Its purpose is to rewrite standard phpBB URLs into a cleaner, human-friendly format.',
	'ACP_URLREWRITING_FAQ_Q_OLD_LINKS'      => 'Will old links break after installing this extension?',
	'ACP_URLREWRITING_FAQ_A_OLD_LINKS'      => 'No. Old links will redirect automatically provided 301 redirects are enabled and web server rules are set up correctly.',
	'ACP_URLREWRITING_FAQ_Q_404'            => 'Why do rewritten links return a 404 error after enabling the extension?',
	'ACP_URLREWRITING_FAQ_A_404'            => 'This indicates your web server has not been configured with the rewrite rules yet, or the rules were placed in the wrong location.',
	'ACP_URLREWRITING_FAQ_Q_NGINX'          => 'Does this extension work on NGINX servers?',
	'ACP_URLREWRITING_FAQ_A_NGINX'          => 'Yes. The Server configuration module provides NGINX rules, which must be added manually to your server block.',
	'ACP_URLREWRITING_FAQ_Q_CONFLICT'       => 'Can this extension conflict with other URL rewriting extensions?',
	'ACP_URLREWRITING_FAQ_A_CONFLICT'       => 'Yes. It is highly recommended to disable other URL rewriting extensions to prevent conflicts in server rules or link generation.',

	'ACP_URLREWRITING_FAQ_UNINSTALL'        => 'Disabling and fallback rules',
	'ACP_URLREWRITING_FAQ_UNINSTALL_WARNING'=> 'Before disabling or removing the extension, set up fallback rules to prevent 404 errors for friendly URLs that have already been indexed or shared.',
	'ACP_URLREWRITING_FAQ_UNINSTALL_TEXT'   => 'The rules below redirect friendly URLs back to standard phpBB URLs using permanent 301 redirects.',
	'ACP_URLREWRITING_FAQ_FALLBACK_APACHE'  => 'Fallback rules for Apache',
	'ACP_URLREWRITING_FAQ_FALLBACK_NGINX'   => 'Fallback rules for NGINX',

	// Support
	'VINNY_URLREWRITING_SUPPORT_STAR'       => 'If you like this extension, please give it a star on <a href="https://github.com/vinny/phpbb-url-rewriting" target="_blank" rel="noopener"><i class="icon fa fa-github fa-fw" aria-hidden="true"></i>GitHub</a>.',
	'VINNY_URLREWRITING_SUPPORT_DONATE'     => 'If you find it useful, you can also support its development with an optional <a href="https://ko-fi.com/vinny1" target="_blank" rel="noopener"><i class="icon fa fa-heart fa-fw" aria-hidden="true"></i>donation</a>.',
));
