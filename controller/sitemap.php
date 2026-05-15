<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\controller;

use Symfony\Component\HttpFoundation\Response;

if (!defined('IN_PHPBB'))
{
	exit;
}

class sitemap
{
	const CHANGEFREQ_VALUES = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');

	protected $config;
	protected $db;
	protected $user;
	protected $url_helper;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \vinny\urlrewriting\helper\url_helper $url_helper, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->url_helper = $url_helper;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function index()
	{
		if (empty($this->config['vinny_url_rewrite_enable']) || empty($this->config['vinny_url_sitemap_enable']))
		{
			$this->user->add_lang_ext('vinny/urlrewriting', 'info_acp_urlrewriting');

			return new Response($this->user->lang('VINNY_URL_SITEMAP_DISABLED'), 404);
		}

		$cache_time = $this->get_cache_time();
		$cache_file = $this->get_cache_file();

		if ($cache_time > 0)
		{
			$xml = $this->read_cache($cache_file, $cache_time);
			if ($xml !== '')
			{
				return $this->xml_response($xml);
			}
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		$xml .= $this->build_url_node(generate_board_url(), '', 'daily', '1.0');

		$limit = isset($this->config['vinny_url_sitemap_limit']) ? min(50000, max(1, (int) $this->config['vinny_url_sitemap_limit'])) : 50000;
		$priority = $this->normalise_priority(isset($this->config['vinny_url_sitemap_priority']) ? $this->config['vinny_url_sitemap_priority'] : '0.5');
		$changefreq = $this->normalise_changefreq(isset($this->config['vinny_url_sitemap_changefreq']) ? $this->config['vinny_url_sitemap_changefreq'] : 'daily');

		$excluded_forums = array();
		if (!empty($this->config['vinny_url_sitemap_excluded']))
		{
			$excluded_forums = array_map('intval', explode(',', $this->config['vinny_url_sitemap_excluded']));
		}

		$sitemap_auth = $this->get_anonymous_auth();
		if ($sitemap_auth === false)
		{
			$xml .= '</urlset>';

			return $this->xml_response($xml);
		}

		$sitemap_forum_ids = array();
		$current_count = 1;

		$sql = 'SELECT forum_id, forum_name, forum_type, forum_password
			FROM ' . FORUMS_TABLE . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($current_count >= $limit)
			{
				break;
			}

			if (in_array((int) $row['forum_id'], $excluded_forums, true))
			{
				continue;
			}

			if (!$sitemap_auth->acl_get('f_list', (int) $row['forum_id']) || !$sitemap_auth->acl_get('f_read', (int) $row['forum_id']) || (int) $row['forum_type'] === FORUM_LINK || !empty($row['forum_password']))
			{
				continue;
			}

			$sitemap_forum_ids[] = (int) $row['forum_id'];
			$url = $this->generate_forum_url((int) $row['forum_id'], (string) $row['forum_name']);

			$xml .= $this->build_url_node($url, '', $changefreq, $priority);
			$current_count++;
		}
		$this->db->sql_freeresult($result);

		if ($current_count < $limit && !empty($sitemap_forum_ids))
		{
			$remaining = $limit - $current_count;
			$sql = 'SELECT topic_id, forum_id, topic_title, topic_time
				FROM ' . TOPICS_TABLE . '
				WHERE topic_status <> ' . ITEM_MOVED . '
					AND topic_visibility = ' . ITEM_APPROVED . '
					AND ' . $this->db->sql_in_set('forum_id', $sitemap_forum_ids) . '
				ORDER BY topic_time DESC';
			$result = $this->db->sql_query_limit($sql, $remaining);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$url = $this->generate_topic_url((int) $row['topic_id'], (int) $row['forum_id'], (string) $row['topic_title']);
				$xml .= $this->build_url_node($url, date('Y-m-d', (int) $row['topic_time']), $changefreq, $priority);
			}
			$this->db->sql_freeresult($result);
		}

		$xml .= '</urlset>';

		if ($cache_time > 0)
		{
			$this->write_cache($cache_file, $xml);
		}

		return $this->xml_response($xml);
	}

	protected function get_cache_time()
	{
		return isset($this->config['vinny_url_sitemap_cache_time']) ? max(0, (int) $this->config['vinny_url_sitemap_cache_time']) : 24;
	}

	protected function get_cache_file()
	{
		$cache_key = md5(implode('|', array(
			generate_board_url(),
			isset($this->config['vinny_url_rewrite_mode']) ? $this->config['vinny_url_rewrite_mode'] : '',
			isset($this->config['vinny_url_sitemap_limit']) ? $this->config['vinny_url_sitemap_limit'] : '',
			isset($this->config['vinny_url_sitemap_priority']) ? $this->config['vinny_url_sitemap_priority'] : '',
			isset($this->config['vinny_url_sitemap_changefreq']) ? $this->config['vinny_url_sitemap_changefreq'] : '',
			isset($this->config['vinny_url_sitemap_excluded']) ? $this->config['vinny_url_sitemap_excluded'] : '',
		)));

		return $this->root_path . 'store/sitemap_cache_' . $cache_key . '.xml';
	}

	protected function read_cache($cache_file, $cache_time)
	{
		if (!is_readable($cache_file))
		{
			return '';
		}

		$cache_mtime = @filemtime($cache_file);
		if ($cache_mtime === false || time() - $cache_mtime >= $cache_time * 3600)
		{
			return '';
		}

		$xml = @file_get_contents($cache_file);

		return ($xml !== false) ? $xml : '';
	}

	protected function write_cache($cache_file, $xml)
	{
		$cache_dir = dirname($cache_file);

		if (!is_dir($cache_dir) || !is_writable($cache_dir))
		{
			return;
		}

		@file_put_contents($cache_file, $xml, LOCK_EX);
	}

	protected function xml_response($xml)
	{
		$response = new Response($xml);
		$response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

		return $response;
	}

	protected function get_anonymous_auth()
	{
		$auth = new \phpbb\auth\auth();
		$user_data = $auth->obtain_user_data(ANONYMOUS);

		if (!is_array($user_data) || empty($user_data['user_id']))
		{
			return false;
		}

		$auth->acl($user_data);

		return $auth;
	}

	protected function build_url_node($url, $lastmod, $changefreq, $priority)
	{
		$xml = '<url>';
		$xml .= '<loc>' . utf8_htmlspecialchars($url) . '</loc>';

		if ($lastmod !== '')
		{
			$xml .= '<lastmod>' . utf8_htmlspecialchars($lastmod) . '</lastmod>';
		}

		$xml .= '<changefreq>' . utf8_htmlspecialchars($changefreq) . '</changefreq>';
		$xml .= '<priority>' . utf8_htmlspecialchars($priority) . '</priority>';
		$xml .= '</url>';

		return $xml;
	}

	protected function generate_forum_url($forum_id, $forum_name)
	{
		if (!empty($this->config['vinny_url_rewrite_enable']))
		{
			return generate_board_url() . '/' . $this->url_helper->generate_forum_link($forum_id, $forum_name);
		}

		return generate_board_url() . '/viewforum.' . $this->php_ext . '?f=' . $forum_id;
	}

	protected function generate_topic_url($topic_id, $forum_id, $topic_title)
	{
		if (!empty($this->config['vinny_url_rewrite_enable']))
		{
			return generate_board_url() . '/' . $this->url_helper->generate_topic_link($topic_id, $topic_title);
		}

		return generate_board_url() . '/viewtopic.' . $this->php_ext . '?f=' . $forum_id . '&t=' . $topic_id;
	}

	protected function normalise_changefreq($changefreq)
	{
		return in_array($changefreq, self::CHANGEFREQ_VALUES, true) ? $changefreq : 'daily';
	}

	protected function normalise_priority($priority)
	{
		$priority = (float) $priority;
		$priority = min(1, max(0, $priority));

		return number_format($priority, 1, '.', '');
	}
}
