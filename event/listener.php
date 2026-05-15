<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (!defined('IN_PHPBB'))
{
	exit;
}

class listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $template;
	protected $user;
	protected $url_helper;
	protected $db;
	protected $request;
	protected $php_ext;

	protected $forum_cache = null;

	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, \vinny\urlrewriting\helper\url_helper $url_helper, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->url_helper = $url_helper;
		$this->db = $db;
		$this->request = $request;
		$this->php_ext = $php_ext;
	}

	protected function get_forum_data($forum_id)
	{
		if ($this->forum_cache === null)
		{
			// Load all forum names (lightweight query)
			$sql = 'SELECT forum_id, forum_name FROM ' . FORUMS_TABLE;
			$result = $this->db->sql_query($sql, 7200); // Cache for 2 hours
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->forum_cache[$row['forum_id']] = $row['forum_name'];
			}
			$this->db->sql_freeresult($result);
		}

		return isset($this->forum_cache[$forum_id]) ? $this->forum_cache[$forum_id] : false;
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.append_sid'							=> 'rewrite_url',
			'core.page_header'							=> 'handle_page_header',
			'core.page_header_after'					=> 'redirect_url',
			'core.viewforum_modify_topicrow'			=> 'rewrite_viewforum_links',
			'core.display_forums_modify_template_vars'	=> 'rewrite_last_post_links',
			'core.viewtopic_modify_post_row'			=> 'rewrite_viewtopic_post_links',
			'core.ucp_pm_compose_template'				=> 'rewrite_pm_quoted_link',
			'core.make_jumpbox_modify_tpl_ary'			=> 'rewrite_jumpbox_links',
			'core.approve_posts_after'					=> 'fix_mcp_post_moderation_redirect',
			'core.approve_topics_after'					=> 'fix_mcp_post_moderation_redirect',
			'core.disapprove_posts_after'				=> 'fix_mcp_post_moderation_redirect',
			'core.modify_notification_message'			=> 'rewrite_notification_message_urls',
		);
	}

	public function rewrite_url($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$url = $event['url'];
		$params = $event['params'];
		$is_amp = $event['is_amp'];

		$original_url = $url;
		$script_name = $this->get_script_name_from_url($url);
		$parsed_params = $this->parse_url_params($url, $params);

		if ($script_name === 'viewtopic.' . $this->php_ext)
		{
			$url = $this->build_topic_url($parsed_params, $is_amp);
		}
		elseif ($script_name === 'viewforum.' . $this->php_ext)
		{
			$url = $this->build_forum_url($parsed_params, $is_amp);
		}

		if ($url && $url !== $original_url)
		{
			$event['append_sid_overwrite'] = $url;
		}
	}

	public function rewrite_viewforum_links($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$row = $event['row'];
		$topic_row = $event['topic_row'];

		$title = isset($row['topic_title']) ? $row['topic_title'] : (isset($row['TOPIC_TITLE']) ? $row['TOPIC_TITLE'] : '');
		$topic_id = isset($row['topic_id']) ? $row['topic_id'] : (isset($row['TOPIC_ID']) ? $row['TOPIC_ID'] : 0);

		if ($title && $topic_id)
		{
			$new_url = $this->url_helper->generate_topic_link($topic_id, $title);
			$topic_row['U_VIEW_TOPIC'] = $new_url;
			$event['topic_row'] = $topic_row;
		}

		if (isset($row['topic_last_post_id']) && $row['topic_last_post_id'])
		{
			$post_id = $row['topic_last_post_id'];
			$new_last_post_url = $this->url_helper->generate_post_link($post_id, $topic_id, $title);

			if ($new_last_post_url)
			{
				$topic_row['U_LAST_POST'] = $new_last_post_url;
				$topic_row['LAST_POST_LINK'] = $new_last_post_url; // Some styles use this
				$event['topic_row'] = $topic_row;
			}
		}
	}

	public function rewrite_last_post_links($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$forum_row = $event['forum_row'];
		$row = $event['row'];

		if (isset($row['forum_last_post_id']) && $row['forum_last_post_id'])
		{
			$post_id = $row['forum_last_post_id'];
			$new_url = $this->url_helper->generate_post_link($post_id);

			if ($new_url)
			{
				$forum_row['U_LAST_POST'] = $new_url;
				$event['forum_row'] = $forum_row;
			}
		}
	}

	public function rewrite_viewtopic_post_links($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$row = $event['row'];
		$post_row = $event['post_row'];
		$topic_data = $event['topic_data'];

		if (isset($row['post_id']))
		{
			$post_id = $row['post_id'];

			$topic_id = isset($topic_data['topic_id']) ? $topic_data['topic_id'] : 0;
			$topic_title = isset($topic_data['topic_title']) ? $topic_data['topic_title'] : '';

			$new_url = $this->url_helper->generate_post_link($post_id, $topic_id, $topic_title);

			if ($new_url)
			{
				$post_row['U_MINI_POST'] = $new_url;
				$post_row['U_MINI_POST_VIEW'] = $new_url;
				$event['post_row'] = $post_row;
			}
		}
	}

	public function rewrite_pm_quoted_link($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		if ($this->request->variable('action', '') !== 'quotepost')
		{
			return;
		}

		$post_id = $this->request->variable('p', 0);
		if (!$post_id)
		{
			return;
		}

		$template_ary = $event['template_ary'];

		if (!isset($template_ary['MESSAGE']))
		{
			return;
		}

		$message_text = $template_ary['MESSAGE'];

		if (empty($message_text))
		{
			return;
		}

		$full_friendly_url = $this->get_full_friendly_post_url($post_id);
		if (!$full_friendly_url)
		{
			return;
		}

		$new_message = $this->replace_viewtopic_post_urls($message_text, $post_id, $full_friendly_url);

		if ($new_message !== $message_text && $new_message !== null)
		{
			$template_ary['MESSAGE'] = $new_message;
			$event['template_ary'] = $template_ary;
		}
	}

	public function rewrite_jumpbox_links($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		// Do not rewrite links inside MCP
		if (isset($this->user->page['page_name']) && strpos($this->user->page['page_name'], 'mcp.' . $this->php_ext) !== false)
		{
			return;
		}

		$tpl_ary = $event['tpl_ary'];

		if (isset($tpl_ary['FORUM_ID']))
		{
			$forum_id = (int) $tpl_ary['FORUM_ID'];

			if ($forum_id > 0)
			{
				$forum_name = isset($tpl_ary['FORUM_NAME']) ? $tpl_ary['FORUM_NAME'] : $this->get_forum_data($forum_id);
				$friendly_path = $this->url_helper->generate_forum_link($forum_id, (string) $forum_name);

				if ($friendly_path)
				{
					$tpl_ary['LINK'] = generate_board_url() . '/' . $friendly_path;
				}
			}
		}

		$event['tpl_ary'] = $tpl_ary;
	}

	protected $topic_cache = array();
	protected $topic_forum_cache = array();
	protected $post_topic_cache = array();

	protected function get_topic_title($topic_id)
	{
		if (isset($this->topic_cache[$topic_id]))
		{
			return $this->topic_cache[$topic_id];
		}

		$sql = 'SELECT topic_title FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql, 600);
		$title = $this->db->sql_fetchfield('topic_title');
		$this->db->sql_freeresult($result);

		$this->topic_cache[$topic_id] = $title;
		return $title;
	}

	protected function get_forum_id_from_topic($topic_id)
	{
		if (isset($this->topic_forum_cache[$topic_id]))
		{
			return $this->topic_forum_cache[$topic_id];
		}

		$sql = 'SELECT forum_id FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql, 600);
		$forum_id = (int) $this->db->sql_fetchfield('forum_id');
		$this->db->sql_freeresult($result);

		$this->topic_forum_cache[$topic_id] = $forum_id;
		return $forum_id;
	}

	protected function get_topic_info_from_post($post_id)
	{
		if (isset($this->post_topic_cache[$post_id]))
		{
			return $this->post_topic_cache[$post_id];
		}

		$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title
				FROM ' . POSTS_TABLE . ' p
				JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
				WHERE p.post_id = ' . (int) $post_id;

		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->post_topic_cache[$post_id] = $row;
			$this->topic_forum_cache[$row['topic_id']] = (int) $row['forum_id'];
			return $row;
		}
		return false;
	}

	protected function get_full_friendly_post_url($post_id)
	{
		$topic_info = $this->get_topic_info_from_post($post_id);
		if (!$topic_info)
		{
			return '';
		}

		$friendly_path = $this->url_helper->generate_post_link($post_id, $topic_info['topic_id'], $topic_info['topic_title']);

		return $friendly_path ? generate_board_url() . '/' . $friendly_path : '';
	}

	protected function replace_viewtopic_post_urls($text, $post_id, $replacement_url)
	{
		$path = '(?:(?:https?:)?//[^\s\]\)"]+?/|(?:\./|\../|/)?(?:[^\s\]\)"]*/)?';
		$pattern = '~' . $path . 'viewtopic\.' . preg_quote($this->php_ext, '~') . '\?[^\s\]\)"]+~i';

		return preg_replace_callback($pattern, function ($matches) use ($post_id, $replacement_url)
		{
			$url = $matches[0];
			$parts = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));

			if (empty($parts['query']))
			{
				return $url;
			}

			parse_str(str_replace('&amp;', '&', $parts['query']), $params);

			return (isset($params['p']) && (int) $params['p'] === (int) $post_id) ? $replacement_url : $url;
		}, $text);
	}

	public function rewrite_notification_message_urls($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$message = $event['message'];
		$pattern = '~https?://[^\s<>"\]]+/(?:viewtopic|viewforum)\.' . preg_quote($this->php_ext, '~') . '\?[^\s<>"\]]+~i';

		$event['message'] = preg_replace_callback($pattern, function ($matches)
		{
			return $this->rewrite_absolute_board_url($matches[0]);
		}, $message);
	}

	protected function rewrite_absolute_board_url($url)
	{
		$decoded_url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
		$parts = parse_url($decoded_url);

		if (empty($parts['path']) || empty($parts['query']))
		{
			return $url;
		}

		$script_name = basename($parts['path']);
		parse_str(str_replace('&amp;', '&', $parts['query']), $params);

		if (!empty($parts['fragment']))
		{
			$params['#'] = $parts['fragment'];
		}

		if ($script_name === 'viewtopic.' . $this->php_ext)
		{
			$friendly_url = $this->build_topic_url($params, false);
		}
		elseif ($script_name === 'viewforum.' . $this->php_ext)
		{
			$friendly_url = $this->build_forum_url($params, false);
		}
		else
		{
			$friendly_url = '';
		}

		return $friendly_url ? generate_board_url() . '/' . $friendly_url : $url;
	}

	protected function get_script_name_from_url($url)
	{
		$path = parse_url($url, PHP_URL_PATH);

		if ($path === null || $path === false)
		{
			return '';
		}

		return basename($path);
	}

	protected function parse_url_params($url, $params)
	{
		$parsed_params = array();
		$query = parse_url($url, PHP_URL_QUERY);
		$fragment = parse_url($url, PHP_URL_FRAGMENT);

		if ($query)
		{
			parse_str(str_replace('&amp;', '&', $query), $parsed_params);
		}

		if (is_array($params))
		{
			$parsed_params = array_merge($parsed_params, $params);
		}
		elseif (is_string($params) && $params !== '')
		{
			$params = ltrim($params, '?&');
			parse_str(str_replace('&amp;', '&', $params), $string_params);
			$parsed_params = array_merge($parsed_params, $string_params);
		}

		if ($fragment)
		{
			$parsed_params['#'] = $fragment;
		}

		return $parsed_params;
	}

	protected function build_topic_url(array $params, $is_amp = true)
	{
		$topic_id = isset($params['t']) ? (int) $params['t'] : 0;
		$post_id = isset($params['p']) ? (int) $params['p'] : 0;
		$friendly_url = '';

		if ($post_id)
		{
			$title = '';
			if ($topic_id)
			{
				$title = $this->get_topic_title($topic_id);
			}

			if (!$title)
			{
				$info = $this->get_topic_info_from_post($post_id);
				if ($info)
				{
					$topic_id = $info['topic_id'];
					$title = $info['topic_title'];
				}
			}

			$friendly_url = $this->url_helper->generate_post_link($post_id, $topic_id, $title);

			if (empty($params['#']))
			{
				$friendly_url = $this->remove_url_anchor($friendly_url);
			}
		}
		elseif ($topic_id)
		{
			$title = $this->get_topic_title($topic_id);
			$friendly_url = $this->url_helper->generate_topic_link($topic_id, (string) $title);
		}

		if (!$friendly_url)
		{
			return '';
		}

		return $this->append_extra_params($friendly_url, $params, array('f', 't', 'p', 'sid'), $is_amp);
	}

	protected function remove_url_anchor($url)
	{
		$anchor_position = strpos($url, '#');

		return ($anchor_position === false) ? $url : substr($url, 0, $anchor_position);
	}

	protected function build_forum_url(array $params, $is_amp = true)
	{
		$forum_id = isset($params['f']) ? (int) $params['f'] : 0;

		if (!$forum_id)
		{
			return '';
		}

		$forum_name = $this->get_forum_data($forum_id);
		$friendly_url = $this->url_helper->generate_forum_link($forum_id, (string) $forum_name);

		return $this->append_extra_params($friendly_url, $params, array('f', 'sid'), $is_amp);
	}

	protected function append_extra_params($url, array $params, array $ignored_params, $is_amp = true)
	{
		$anchor = '';

		if (strpos($url, '#') !== false)
		{
			list($url, $anchor) = explode('#', $url, 2);
		}

		if (!empty($params['#']))
		{
			$anchor = ltrim((string) $params['#'], '#');
			unset($params['#']);
		}

		foreach ($ignored_params as $param)
		{
			unset($params[$param]);
		}

		$query_params = array();
		foreach ($params as $key => $value)
		{
			if ($value === '' || $value === null || is_array($value))
			{
				continue;
			}

			$query_params[$key] = $value;
		}

		if ($query_params)
		{
			$separator = $is_amp ? '&amp;' : '&';
			$url .= '?' . http_build_query($query_params, '', $separator);
		}

		if ($anchor !== '')
		{
			$url .= '#' . $anchor;
		}

		return $url;
	}

	public function handle_page_header($event)
	{
		$this->fix_viewtopic_action_urls();
		$this->add_seo_tags($event);
	}

	protected function fix_viewtopic_action_urls()
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$script_name = isset($this->user->page['page_name']) ? $this->user->page['page_name'] : '';
		$request_uri = $this->request->server('REQUEST_URI');
		$topic_id = $this->request->variable('t', 0);
		$post_id = $this->request->variable('p', 0);
		$forum_id = $this->request->variable('f', 0);

		if ($script_name !== 'viewtopic.' . $this->php_ext && !$topic_id && !$post_id && !preg_match('/(?:^|\/)[^\/?]+-t\d+(?:-p\d+)?(?:[?#]|$)/', $request_uri))
		{
			return;
		}

		if (!$topic_id && $post_id)
		{
			$topic_info = $this->get_topic_info_from_post($post_id);
			$topic_id = $topic_info ? (int) $topic_info['topic_id'] : 0;
			$forum_id = (!$forum_id && $topic_info) ? (int) $topic_info['forum_id'] : $forum_id;
		}

		if (!$topic_id)
		{
			return;
		}

		if (!$forum_id)
		{
			$forum_id = $this->get_forum_id_from_topic($topic_id);
		}

		$start = $this->request->variable('start', 0);
		$topic_url = $this->build_topic_url(array('t' => $topic_id), true);
		if ($topic_url)
		{
			$this->template->assign_vars(array(
				'U_TOPIC'		=> generate_board_url() . '/' . $topic_url,
				'U_CANONICAL'	=> generate_board_url() . '/' . $topic_url,
			));
		}

		if (!empty($this->config['allow_bookmarks']) && !empty($this->user->data['is_registered']))
		{
			$params = array(
				't'			=> $topic_id,
				'bookmark'	=> 1,
				'hash'		=> generate_link_hash('topic_' . $topic_id),
			);

			if ($start > 0)
			{
				$params['start'] = $start;
			}

			$bookmark_url = $this->build_topic_url($params, true);
			if ($bookmark_url)
			{
				$this->template->assign_var('U_BOOKMARK_TOPIC', $bookmark_url);
			}
		}

		if ($this->auth->acl_get('f_print', $forum_id))
		{
			$params = array(
				't'		=> $topic_id,
				'view'	=> 'print',
			);

			if ($start > 0)
			{
				$params['start'] = $start;
			}

			$print_url = $this->build_topic_url($params, true);
			if ($print_url)
			{
				$this->template->assign_var('U_PRINT_TOPIC', $print_url);
			}
		}
	}

	public function add_seo_tags($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		if (empty($this->config['vinny_url_opengraph_enable']))
		{
			return;
		}

		$script_name = $this->user->page['page_name'];

		if (!$this->is_open_graph_page($script_name))
		{
			return;
		}

		$page_data = $this->get_open_graph_page_data($script_name);
		$og_data = $this->get_open_graph_data($script_name, $page_data);

		$this->template->assign_vars(array(
			'S_OPENGRAPH'	=> true,
			'OG_TITLE'		=> utf8_htmlspecialchars(isset($event['page_title']) ? $event['page_title'] : $this->config['sitename']),
			'OG_URL'		=> utf8_htmlspecialchars($og_data['url']),
			'OG_DESC'		=> utf8_htmlspecialchars($og_data['description']),
			'OG_IMAGE'		=> utf8_htmlspecialchars($og_data['image']),
			'OG_TYPE'		=> ($page_data['mode'] === 'topic') ? 'article' : 'website',
		));
	}

	protected function is_open_graph_page($script_name)
	{
		return strpos($script_name, 'index.' . $this->php_ext) === 0 ||
			strpos($script_name, 'viewforum.' . $this->php_ext) === 0 ||
			strpos($script_name, 'viewtopic.' . $this->php_ext) === 0;
	}

	protected function get_open_graph_page_data($script_name)
	{
		$id = 0;
		$mode = '';
		$post_id = 0;

		if (strpos($script_name, 'viewtopic.' . $this->php_ext) === 0)
		{
			$id = $this->request->variable('t', 0);
			$post_id = $this->request->variable('p', 0);
			$mode = 'topic';

			if (!$id && $post_id)
			{
				$topic_info = $this->get_topic_info_from_post($post_id);
				if ($topic_info)
				{
					$id = (int) $topic_info['topic_id'];
				}
			}
		}
		elseif (strpos($script_name, 'viewforum.' . $this->php_ext) === 0)
		{
			$id = $this->request->variable('f', 0);
			$mode = 'forum';
		}
		elseif (strpos($script_name, 'index.' . $this->php_ext) === 0)
		{
			$mode = 'index';
		}

		return array(
			'id'	=> $id,
			'mode'	=> $mode,
		);
	}

	protected function get_open_graph_data($script_name, array $page_data)
	{
		$description = $this->config['site_desc'];
		$image = '';

		if ($page_data['mode'] === 'forum' && $page_data['id'])
		{
			$description = $this->get_forum_open_graph_description($page_data['id'], $description);
		}
		elseif ($page_data['mode'] === 'topic' && $page_data['id'])
		{
			$topic_data = $this->get_topic_open_graph_data($page_data['id']);
			$description = $topic_data['description'] ? $topic_data['description'] : $description;
			$image = $topic_data['image'];
		}

		return array(
			'url'			=> $this->get_open_graph_canonical_url($script_name, $page_data),
			'description'	=> $description,
			'image'			=> $image ? $image : $this->get_default_open_graph_image(),
		);
	}

	protected function get_open_graph_canonical_url($script_name, array $page_data)
	{
		if ($page_data['mode'] === 'topic' && $page_data['id'])
		{
			$title = $this->get_topic_title($page_data['id']);

			return generate_board_url() . '/' . $this->url_helper->generate_topic_link($page_data['id'], (string) $title);
		}

		if ($page_data['mode'] === 'forum' && $page_data['id'])
		{
			$forum_name = $this->get_forum_data($page_data['id']);

			return generate_board_url() . '/' . $this->url_helper->generate_forum_link($page_data['id'], (string) $forum_name);
		}

		$base_script_name = $script_name;
		if (($pos = strpos($base_script_name, '/')) !== false)
		{
			$base_script_name = substr($base_script_name, 0, $pos);
		}

		if ($base_script_name === 'index.' . $this->php_ext)
		{
			return generate_board_url() . '/';
		}

		return generate_board_url() . '/' . $base_script_name;
	}

	protected function get_forum_open_graph_description($forum_id, $default_description)
	{
		$sql = 'SELECT forum_desc
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql, 7200);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || empty($row['forum_desc']))
		{
			return $default_description;
		}

		$description = $this->clean_open_graph_text($row['forum_desc']);

		return $description ? $description : $default_description;
	}

	protected function get_topic_open_graph_data($topic_id)
	{
		$data = array(
			'description'	=> '',
			'image'			=> '',
		);

		$sql = 'SELECT p.post_text, p.post_id, p.bbcode_uid, p.bbcode_bitfield, p.enable_bbcode, p.enable_smilies, p.enable_magic_url
			FROM ' . TOPICS_TABLE . ' t
			JOIN ' . POSTS_TABLE . ' p ON t.topic_first_post_id = p.post_id
			WHERE t.topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return $data;
		}

		$bbcode_options = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
			(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
			(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		$html_content = generate_text_for_display($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

		$data['description'] = $this->clean_open_graph_text($html_content, 200);
		$data['image'] = $this->get_open_graph_image_from_html($html_content);

		if (!$data['image'])
		{
			$data['image'] = $this->get_open_graph_image_from_attachment($row['post_id']);
		}

		return $data;
	}

	protected function clean_open_graph_text($text, $limit = 0)
	{
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = trim(preg_replace('/\s+/', ' ', $text));

		if ($limit && mb_strlen($text) > $limit)
		{
			$text = mb_substr($text, 0, $limit - 3) . '...';
		}

		return $text;
	}

	protected function get_open_graph_image_from_html($html_content)
	{
		if (!preg_match('/<img[^>]+src="([^"]+)"/i', $html_content, $matches))
		{
			return '';
		}

		$img_url = $matches[1];
		if (strpos($img_url, './') === 0)
		{
			$img_url = generate_board_url() . '/' . substr($img_url, 2);
		}
		elseif (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0)
		{
			$img_url = generate_board_url() . '/' . ltrim($img_url, '/');
		}

		$smilies_path = generate_board_url() . '/' . $this->config['smilies_path'];

		return (strpos($img_url, $smilies_path) === false) ? $img_url : '';
	}

	protected function get_open_graph_image_from_attachment($post_id)
	{
		$sql = 'SELECT attach_id, extension, mimetype
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE post_msg_id = ' . (int) $post_id . '
				AND in_message = 0
				AND (mimetype ' . $this->db->sql_like_expression('image/' . $this->db->get_any_char()) . '
					OR ' . $this->db->sql_in_set('extension', array('jpg', 'jpeg', 'png', 'gif', 'webp')) . ')
			ORDER BY filetime ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$attachment = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $attachment ? generate_board_url() . '/download/file.' . $this->php_ext . '?id=' . $attachment['attach_id'] : '';
	}

	protected function get_default_open_graph_image()
	{
		$theme_path = (isset($this->user->theme['theme_path'])) ? $this->user->theme['theme_path'] : 'prosilver';

		return generate_board_url() . '/styles/' . $theme_path . '/theme/images/site_logo.svg';
	}

	public function redirect_url($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		if (empty($this->config['vinny_url_redirect_enable']))
		{
			return;
		}

		$script_name = $this->user->page['page_name'];
		if ($script_name !== 'viewtopic.' . $this->php_ext && $script_name !== 'viewforum.' . $this->php_ext)
		{
			return;
		}

		$request_uri = $this->request->server('REQUEST_URI');
		if (strpos($request_uri, '.php') === false)
		{
			return;
		}

		$query_params = $this->get_redirect_query_params(($script_name === 'viewtopic.' . $this->php_ext) ? array('f', 't', 'p', 'sid') : array('f', 'sid'));
		$friendly_params = $query_params;
		$friendly_params['#'] = '';

		if ($script_name === 'viewtopic.' . $this->php_ext)
		{
			$friendly_params['t'] = $this->request->variable('t', 0);
			$friendly_params['p'] = $this->request->variable('p', 0);
			$friendly_url = $this->build_topic_url($friendly_params, false);
		}
		else
		{
			$friendly_params['f'] = $this->request->variable('f', 0);
			$friendly_url = $this->build_forum_url($friendly_params, false);
		}

		if (!$friendly_url)
		{
			return;
		}

		$redirect_url = generate_board_url() . '/' . $friendly_url;
		$redirect_url = redirect($redirect_url, true);

		garbage_collection();
		header('Location: ' . $redirect_url, true, 301);
		exit_handler();
	}

	protected function get_redirect_query_params(array $ignored_params)
	{
		$query_params = array();
		$request_uri = $this->request->server('REQUEST_URI');
		$query = parse_url($request_uri, PHP_URL_QUERY);

		if (!$query)
		{
			return $query_params;
		}

		parse_str(str_replace('&amp;', '&', $query), $query_params);

		foreach ($ignored_params as $param)
		{
			unset($query_params[$param]);
		}

		foreach ($query_params as $key => $value)
		{
			if ($value === '' || $value === null || is_array($value))
			{
				unset($query_params[$key]);
			}
		}

		return $query_params;
	}

	public function fix_mcp_post_moderation_redirect($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$redirect = $event['redirect'];

		if (strpos($redirect, '.php') !== false)
		{
			return;
		}

		if (preg_match('/(?:&amp;|&)p=(\d+)#p\1$/', $redirect, $match))
		{
			$event['redirect'] = substr($redirect, 0, -strlen($match[0]));
		}
	}
}
