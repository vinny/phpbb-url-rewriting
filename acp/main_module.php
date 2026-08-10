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
				$this->server_config($config, $template, $user);
			break;

			case 'faq':
				$this->faq($template, $user);
			break;
		}
	}

	protected function settings($config, $request, $template, $user)
	{
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('vinny_urlrewriting_settings'))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('vinny_url_rewrite_enable', $this->normalise_bool($request->variable('vinny_url_rewrite_enable', 0)));
			$config->set('vinny_url_rewrite_mode', $this->normalise_bool($request->variable('vinny_url_rewrite_mode', 1)));
			$config->set('vinny_url_translit_enable', $this->normalise_bool($request->variable('vinny_url_translit_enable', 0)));
			$config->set('vinny_url_opengraph_enable', $this->normalise_bool($request->variable('vinny_url_opengraph_enable', 0)));
			$config->set('vinny_url_redirect_enable', $this->normalise_bool($request->variable('vinny_url_redirect_enable', 0)));

			trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'U_ACTION'                      => $this->u_action,
			'VINNY_URL_REWRITE_ENABLE'      => $config['vinny_url_rewrite_enable'],
			'VINNY_URL_REWRITE_MODE'        => isset($config['vinny_url_rewrite_mode']) ? $config['vinny_url_rewrite_mode'] : 1,
			'VINNY_URL_TRANSLIT_ENABLE'     => $config['vinny_url_translit_enable'],
			'VINNY_URL_OPENGRAPH_ENABLE'    => $config['vinny_url_opengraph_enable'],
			'VINNY_URL_REDIRECT_ENABLE'     => $config['vinny_url_redirect_enable'],
		));
	}

	protected function server_config($config, $template, $user)
	{
		$mode = isset($config['vinny_url_rewrite_mode']) ? (int) $config['vinny_url_rewrite_mode'] : 1;
		$apache_rules_key = ($mode === 1) ? 'ACP_URLREWRITING_APACHE_RULES_ADVANCED' : 'ACP_URLREWRITING_APACHE_RULES_SIMPLE';
		$nginx_rules_key = ($mode === 1) ? 'ACP_URLREWRITING_NGINX_RULES_ADVANCED' : 'ACP_URLREWRITING_NGINX_RULES_SIMPLE';

		$template->assign_vars(array(
			'HTACCESS_RULES' => $user->lang($apache_rules_key),
			'NGINX_RULES'    => $user->lang($nginx_rules_key),
		));
	}

	protected function normalise_bool($value)
	{
		return $value ? 1 : 0;
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

		$this->add_faq_row($template, $user, 'ACP_URLREWRITING_FAQ_FALLBACK_APACHE', $this->fallback_apache_rules($user), true, 'apache');
		$this->add_faq_row($template, $user, 'ACP_URLREWRITING_FAQ_FALLBACK_NGINX', $this->fallback_nginx_rules($user), true, 'nginx');
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

	protected function fallback_apache_rules($user)
	{
		return $user->lang('ACP_URLREWRITING_APACHE_FALLBACK_RULES');
	}

	protected function fallback_nginx_rules($user)
	{
		return $user->lang('ACP_URLREWRITING_NGINX_FALLBACK_RULES');
	}
}
