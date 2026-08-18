<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\textformatter\s9e;

class quote_helper extends \phpbb\textformatter\s9e\quote_helper
{
	protected $db;
	protected $config;
	protected $url_helper;

	protected $cache = array();

	public function __construct(\phpbb\user $user, $root_path, $php_ext, \phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \vinny\urlrewriting\helper\url_helper $url_helper)
	{
		parent::__construct($user, $root_path, $php_ext);
		$this->db = $db;
		$this->config = $config;
		$this->url_helper = $url_helper;
	}

	public function inject_metadata($xml)
	{
		return \s9e\TextFormatter\Utils::replaceAttributes(
			$xml,
			'QUOTE',
			function ($attributes)
			{
				if (isset($attributes['post_id']))
				{
					$url = $this->get_friendly_post_url($attributes['post_id']);
					if ($url)
					{
						$attributes['post_url'] = $url;
					}
					else
					{
						$attributes['post_url'] = str_replace('{POST_ID}', $attributes['post_id'], $this->post_url);
					}
				}

				if (isset($attributes['msg_id']))
				{
					$attributes['msg_url'] = str_replace('{MSG_ID}', $attributes['msg_id'], $this->msg_url);
				}
				if (isset($attributes['time']))
				{
					$attributes['date'] = $this->user->format_date($attributes['time']);
				}
				if (isset($attributes['user_id']))
				{
					$attributes['profile_url'] = str_replace('{USER_ID}', $attributes['user_id'], $this->profile_url);
				}

				return $attributes;
			}
		);
	}

	protected function get_friendly_post_url($post_id)
	{
		$post_id = (int) $post_id;

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return null;
		}

		if (array_key_exists($post_id, $this->cache))
		{
			return $this->cache[$post_id];
		}

		$sql = 'SELECT t.topic_id, t.topic_title
			FROM ' . POSTS_TABLE . ' p
			JOIN ' . TOPICS_TABLE . ' t ON t.topic_id = p.topic_id
			WHERE p.post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$friendly_path = $this->url_helper->generate_post_link($post_id, $row['topic_id'], $row['topic_title']);
			$url = $this->get_board_url() . '/' . $friendly_path;
			$this->cache[$post_id] = $url;

			return $url;
		}

		$this->cache[$post_id] = null;

		return null;
	}

	protected function get_board_url()
	{
		global $user, $config;

		if (!isset($user) || $user === null)
		{
			$user = $this->user;
		}

		if (!isset($config) || $config === null)
		{
			$config = $this->config;
		}

		if (function_exists('generate_board_url'))
		{
			return generate_board_url();
		}

		return '';
	}
}
