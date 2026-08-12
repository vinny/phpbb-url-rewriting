<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\migrations;

class v100 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['vinny_url_rewrite_enable']);
	}

	public static function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				FORUMS_TABLE => array(
					'vinny_url_forum_slug' => array('VCHAR:255', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				FORUMS_TABLE => array('vinny_url_forum_slug'),
			),
		);
	}

	public function update_data()
	{
		return array(
			// General Settings
			array('config.add', array('vinny_url_rewrite_enable', 0)),
			array('config.add', array('vinny_url_rewrite_mode', 1)),
			array('config.add', array('vinny_url_opengraph_enable', 1)),
			array('config.add', array('vinny_url_translit_enable', 1)),
			array('config.add', array('vinny_url_redirect_enable', 1)),
			array('config.add', array('vinny_url_min_word_length', 0)),
			array('config.add', array('vinny_url_members_enable', 0)),

			// Add ACP Module Category
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_URLREWRITING_TITLE'
			)),

			// Add ACP Module Modes
			array('module.add', array(
				'acp',
				'ACP_URLREWRITING_TITLE',
				array(
					'module_basename'   => '\vinny\urlrewriting\acp\main_module',
					'modes'             => array('settings', 'server', 'faq'),
				),
			)),
		);
	}
}
