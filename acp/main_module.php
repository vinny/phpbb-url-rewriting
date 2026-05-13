<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\acp;

if (!defined('IN_PHPBB'))
{
	exit;
}

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $config, $request, $template, $user;

        $user->add_lang_ext('vinny/urlrewriting', 'info_acp_urlrewriting');

        $this->tpl_name = 'acp_url_rewriting_' . $mode;
        $this->page_title = $user->lang('ACP_URLREWRITING_' . strtoupper($mode));

        // Generate a valid form token
        add_form_key('vinny_urlrewriting_' . $mode);

        switch ($mode)
        {
            case 'settings':
                $this->settings($config, $request, $template, $user);
            break;

            case 'server':
                $this->server_config($config, $template);
            break;

            case 'faq':
                $this->faq($template, $user);
            break;

            case 'sitemap':
                $this->sitemap($config, $request, $template, $user);
            break;
        }
    }

    protected function sitemap($config, $request, $template, $user)
    {
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('vinny_urlrewriting_sitemap'))
            {
                trigger_error('FORM_INVALID');
            }

            $config->set('vinny_url_sitemap_cache_time', $request->variable('vinny_url_sitemap_cache_time', 24));
            $config->set('vinny_url_sitemap_limit', $request->variable('vinny_url_sitemap_limit', 50000));
            $config->set('vinny_url_sitemap_priority', (string) $request->variable('vinny_url_sitemap_priority', 0.5, true));
            $config->set('vinny_url_sitemap_changefreq', $request->variable('vinny_url_sitemap_changefreq', 'daily'));
            
            // Excluded forums array to csv
            $excluded_forums = $request->variable('vinny_url_sitemap_excluded', array(0));
            $config->set('vinny_url_sitemap_excluded', implode(',', $excluded_forums));

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        // Use make_forum_select logic
        $forum_list = make_forum_select(false, false, true, false, false, false, true);
        
        $excluded_forums = explode(',', $config['vinny_url_sitemap_excluded']);

        foreach ($forum_list as $forum_id => $forum_data)
        {
            if ($forum_data['forum_type'] == FORUM_LINK)
            {
                continue;
            }

            $template->assign_block_vars('forums', array(
                'FORUM_ID'      => $forum_id,
                'FORUM_NAME'    => (isset($forum_data['padding']) ? $forum_data['padding'] : '') . $forum_data['forum_name'],
                'SELECTED'      => in_array($forum_id, $excluded_forums),
                'DISABLED'      => $forum_data['disabled']
            ));
        }

        $template->assign_vars(array(
            'U_ACTION'                      => $this->u_action,
            'VINNY_URL_SITEMAP_CACHE_TIME'  => $config['vinny_url_sitemap_cache_time'],
            'VINNY_URL_SITEMAP_LIMIT'       => $config['vinny_url_sitemap_limit'],
            'VINNY_URL_SITEMAP_PRIORITY'    => $config['vinny_url_sitemap_priority'],
            'VINNY_URL_SITEMAP_CHANGEFREQ'  => $config['vinny_url_sitemap_changefreq'],
            'U_SITEMAP_URL'                 => generate_board_url() . '/sitemap.xml',
        ));
    }

    protected function settings($config, $request, $template, $user)
    {
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('vinny_urlrewriting_settings'))
            {
                trigger_error('FORM_INVALID');
            }

            $config->set('vinny_url_rewrite_enable', $request->variable('vinny_url_rewrite_enable', 0));
            $config->set('vinny_url_rewrite_mode', $request->variable('vinny_url_rewrite_mode', 1));
            $config->set('vinny_url_translit_enable', $request->variable('vinny_url_translit_enable', 0));
            $config->set('vinny_url_sitemap_enable', $request->variable('vinny_url_sitemap_enable', 0));
            $config->set('vinny_url_opengraph_enable', $request->variable('vinny_url_opengraph_enable', 0));
            $config->set('vinny_url_redirect_enable', $request->variable('vinny_url_redirect_enable', 0));

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        $template->assign_vars(array(
            'U_ACTION'                      => $this->u_action,
            'VINNY_URL_REWRITE_ENABLE'      => $config['vinny_url_rewrite_enable'],
            'VINNY_URL_REWRITE_MODE'        => isset($config['vinny_url_rewrite_mode']) ? $config['vinny_url_rewrite_mode'] : 1, // Default Advanced
            'VINNY_URL_TRANSLIT_ENABLE'     => $config['vinny_url_translit_enable'],
            'VINNY_URL_SITEMAP_ENABLE'      => $config['vinny_url_sitemap_enable'],
            'VINNY_URL_OPENGRAPH_ENABLE'    => $config['vinny_url_opengraph_enable'],
            'VINNY_URL_REDIRECT_ENABLE'     => $config['vinny_url_redirect_enable'],
        ));
    }

    protected function server_config($config, $template)
    {
        $mode = isset($config['vinny_url_rewrite_mode']) ? (int) $config['vinny_url_rewrite_mode'] : 1;

        $apache_rules = "# Vinny URL Rewriting Apache Rules - begin\n";
        $apache_rules .= "# IMPORTANT: Place these rules before the default phpBB app.php rewrites.\n";
        $nginx_rules = "# Vinny URL Rewriting NGINX Rules - begin\n";

        if ($mode === 1)
        {
            $apache_rules .= "# Topics\n";
            $apache_rules .= "RewriteRule ^.*-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]\n";
            $apache_rules .= "# Posts\n";
            $apache_rules .= "RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]\n";
            $apache_rules .= "RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ viewtopic.php?t=$1&p=$2 [QSA,L]\n";
            $apache_rules .= "# Forums\n";
            $apache_rules .= "RewriteRule ^.*-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]\n";

            $nginx_rules .= "# Topics\n";
            $nginx_rules .= "rewrite ^/(.*)-t([0-9]+)$ /viewtopic.php?t=$2 last;\n";
            $nginx_rules .= "# Posts\n";
            $nginx_rules .= "rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;\n";
            $nginx_rules .= "rewrite ^/(.*)-t([0-9]+)-p([0-9]+)$ /viewtopic.php?t=$2&p=$3 last;\n";
            $nginx_rules .= "# Forums\n";
            $nginx_rules .= "rewrite ^/(.*)-f([0-9]+)$ /viewforum.php?f=$2 last;\n";
        }
        else
        {
            $apache_rules .= "# Topics\n";
            $apache_rules .= "RewriteRule ^topic-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]\n";
            $apache_rules .= "# Posts\n";
            $apache_rules .= "RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]\n";
            $apache_rules .= "# Forums\n";
            $apache_rules .= "RewriteRule ^forum-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]\n";

            $nginx_rules .= "# Topics\n";
            $nginx_rules .= "rewrite ^/topic-t([0-9]+)$ /viewtopic.php?t=$1 last;\n";
            $nginx_rules .= "# Posts\n";
            $nginx_rules .= "rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;\n";
            $nginx_rules .= "# Forums\n";
            $nginx_rules .= "rewrite ^/forum-f([0-9]+)$ /viewforum.php?f=$1 last;\n";
        }

        $apache_rules .= "\n# Sitemap\nRewriteRule ^sitemap\\.xml$ app.php/sitemap.xml [QSA,L]\n";
        $apache_rules .= "# Vinny URL Rewriting Apache Rules - end";
        $nginx_rules .= "\n# Sitemap\nrewrite ^/sitemap\\.xml$ /app.php/sitemap.xml last;\n";
        $nginx_rules .= "# Vinny URL Rewriting NGINX Rules - end";

        $template->assign_vars(array(
            'HTACCESS_RULES' => $apache_rules,
            'NGINX_RULES'    => $nginx_rules,
        ));
    }

    protected function faq($template, $user)
    {
        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_OVERVIEW', array(
            'ACP_URLREWRITING_FAQ_OVERVIEW' => 'ACP_URLREWRITING_FAQ_OVERVIEW_TEXT',
            'ACP_URLREWRITING_FAQ_FEATURES' => 'ACP_URLREWRITING_FAQ_FEATURES_TEXT',
        ));

        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_FUNCTIONS', array(
            'VINNY_URL_REWRITE_ENABLE'      => 'ACP_URLREWRITING_FAQ_REWRITE_ENABLE',
            'VINNY_URL_REWRITE_MODE'        => 'ACP_URLREWRITING_FAQ_REWRITE_MODE',
            'VINNY_URL_TRANSLIT_ENABLE'     => 'ACP_URLREWRITING_FAQ_TRANSLIT',
            'VINNY_URL_REDIRECT_ENABLE'     => 'ACP_URLREWRITING_FAQ_REDIRECTS',
            'VINNY_URL_OPENGRAPH_ENABLE'    => 'ACP_URLREWRITING_FAQ_OPENGRAPH',
        ));

        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_SITEMAP', array(
            'ACP_URLREWRITING_FAQ_SITEMAP'  => 'ACP_URLREWRITING_FAQ_SITEMAP_TEXT',
            'VINNY_URL_SITEMAP_CACHE_TIME'  => 'ACP_URLREWRITING_FAQ_SITEMAP_CACHE',
            'VINNY_URL_SITEMAP_LIMIT'       => 'ACP_URLREWRITING_FAQ_SITEMAP_LIMIT',
            'VINNY_URL_SITEMAP_EXCLUDED'    => 'ACP_URLREWRITING_FAQ_SITEMAP_EXCLUDED',
        ));

        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_SERVER', array(
            'ACP_URLREWRITING_FAQ_SERVER'       => 'ACP_URLREWRITING_FAQ_SERVER_TEXT',
            'ACP_URLREWRITING_FAQ_SERVER_STEPS' => 'ACP_URLREWRITING_FAQ_SERVER_STEPS_TEXT',
        ));

        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_QUESTIONS', array(
            'ACP_URLREWRITING_FAQ_Q_SEO'        => 'ACP_URLREWRITING_FAQ_A_SEO',
            'ACP_URLREWRITING_FAQ_Q_OLD_LINKS'  => 'ACP_URLREWRITING_FAQ_A_OLD_LINKS',
            'ACP_URLREWRITING_FAQ_Q_404'        => 'ACP_URLREWRITING_FAQ_A_404',
            'ACP_URLREWRITING_FAQ_Q_NGINX'      => 'ACP_URLREWRITING_FAQ_A_NGINX',
            'ACP_URLREWRITING_FAQ_Q_CONFLICT'   => 'ACP_URLREWRITING_FAQ_A_CONFLICT',
        ));

        $this->add_faq_block($template, $user, 'ACP_URLREWRITING_FAQ_UNINSTALL', array(
            'ACP_URLREWRITING_FAQ_UNINSTALL_WARNING' => 'ACP_URLREWRITING_FAQ_UNINSTALL_TEXT',
        ), true);

        $this->add_faq_row($template, $user, 'ACP_URLREWRITING_FAQ_FALLBACK_APACHE', $this->fallback_apache_rules(), true, 'apache');
        $this->add_faq_row($template, $user, 'ACP_URLREWRITING_FAQ_FALLBACK_NGINX', $this->fallback_nginx_rules(), true, 'nginx');
    }

    protected function add_faq_block($template, $user, $title_key, array $rows, $server_switch = false)
    {
        $template->assign_block_vars('faq_block', array(
            'BLOCK_TITLE'     => $user->lang($title_key),
            'S_SERVER_SWITCH' => $server_switch,
        ));

        foreach ($rows as $question_key => $answer_key)
        {
            $this->add_faq_row($template, $user, $question_key, $user->lang($answer_key));
        }
    }

    protected function add_faq_row($template, $user, $question_key, $answer, $code = false, $code_type = '')
    {
        $template->assign_block_vars('faq_block.faq_row', array(
            'FAQ_QUESTION'  => $user->lang($question_key),
            'FAQ_ANSWER'    => $answer,
            'S_CODE_BLOCK'  => $code,
            'S_NGINX_CODE'  => $code_type === 'nginx',
            'CODE_TYPE'     => $code_type,
        ));
    }

    protected function fallback_apache_rules()
    {
        return '# Vinny URL Rewriting Apache Fallback Rules - begin
# ----------------------------------------------------------------------
# FALLBACK: REDIRECT FRIENDLY URLS BACK TO STANDARD PHPBB
# Use only if the "Advanced URL Rewriting" extension is uninstalled
# ----------------------------------------------------------------------

# 1. Redirect post links (e.g., slug-t12-p34 or post-p34)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ %1/viewtopic.php?t=$1&amp;p=$2 [QSA,R=301,L]

RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-p([0-9]+)$
RewriteRule ^.*-p([0-9]+)$ %1/viewtopic.php?p=$1 [QSA,R=301,L]

# 2. Redirect topic links (e.g., slug-t123 or topic-t123)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)$
RewriteRule ^.*-t([0-9]+)$ %1/viewtopic.php?t=$1 [QSA,R=301,L]

# 3. Redirect forum links (e.g., slug-f45 or forum-f45)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-f([0-9]+)$
RewriteRule ^.*-f([0-9]+)$ %1/viewforum.php?f=$1 [QSA,R=301,L]
# Vinny URL Rewriting Apache Fallback Rules - end';
    }

    protected function fallback_nginx_rules()
    {
        return '# Vinny URL Rewriting NGINX Fallback Rules - begin
# ----------------------------------------------------------------------
# FALLBACK: REDIRECT FRIENDLY URLS BACK TO STANDARD PHPBB
# Use only if the "Advanced URL Rewriting" extension is uninstalled
# ----------------------------------------------------------------------

# 1. Redirect post links (e.g., slug-t12-p34 or post-p34)
rewrite ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$ $1/viewtopic.php?t=$2&amp;p=$3 permanent;
rewrite ^(.*)/[^/]+-p([0-9]+)$ $1/viewtopic.php?p=$2 permanent;

# 2. Redirect topic links (e.g., slug-t123 or topic-t123)
rewrite ^(.*)/[^/]+-t([0-9]+)$ $1/viewtopic.php?t=$2 permanent;

# 3. Redirect forum links (e.g., slug-f45 or forum-f45)
rewrite ^(.*)/[^/]+-f([0-9]+)$ $1/viewforum.php?f=$2 permanent;
# Vinny URL Rewriting NGINX Fallback Rules - end';
    }
}
