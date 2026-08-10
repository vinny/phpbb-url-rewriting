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

class main_info
{
	public function module()
	{
		return array(
			'filename'  => '\vinny\urlrewriting\acp\main_module',
			'title'     => 'ACP_URLREWRITING_TITLE',
			'modes'     => array(
				'settings'  => array('title' => 'ACP_URLREWRITING_SETTINGS', 'auth' => 'ext_vinny/urlrewriting && acl_a_board', 'cat' => array('ACP_URLREWRITING')),
				'server'    => array('title' => 'ACP_URLREWRITING_SERVER', 'auth' => 'ext_vinny/urlrewriting && acl_a_server', 'cat' => array('ACP_URLREWRITING')),
				'faq'       => array('title' => 'ACP_URLREWRITING_FAQ', 'auth' => 'ext_vinny/urlrewriting && acl_a_board', 'cat' => array('ACP_URLREWRITING')),
			),
		);
	}
}
