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

class listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $template;
	protected $user;
	protected $url_helper;
	protected $db;
	protected $request;
	protected $content_visibility;
	protected $cache;
	protected $php_ext;

	protected $forum_cache = null;
	protected $topic_cache = array();
	protected $topic_forum_cache = array();
	protected $post_topic_cache = array();
	protected $unlocked_forums = null;

	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, \vinny\urlrewriting\helper\url_helper $url_helper, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\content_visibility $content_visibility, \phpbb\cache\driver\driver_interface $cache, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->url_helper = $url_helper;
		$this->db = $db;
		$this->request = $request;
		$this->content_visibility = $content_visibility;
		$this->cache = $cache;
		$this->php_ext = $php_ext;
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

	protected function get_forum_data($forum_id)
	{
		if ($this->forum_cache === null)
		{
			// Load all forum names, custom slugs, and password flags (cached for 10 mins)
			$sql = 'SELECT forum_id, forum_name, forum_password, vinny_url_forum_slug FROM ' . FORUMS_TABLE;
			$result = $this->db->sql_query($sql, 600);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->forum_cache[(int) $row['forum_id']] = array(
					'forum_name'     => $row['forum_name'],
					'forum_url'      => isset($row['vinny_url_forum_slug']) ? $row['vinny_url_forum_slug'] : '',
					'forum_password' => !empty($row['forum_password']),
				);
			}
			$this->db->sql_freeresult($result);
		}

		return isset($this->forum_cache[$forum_id]) ? $this->forum_cache[$forum_id] : false;
	}

	protected function is_forum_accessible($forum_id)
	{
		$forum_id = (int) $forum_id;
		if (!$forum_id)
		{
			return false;
		}

		if (!$this->auth->acl_get('f_read', $forum_id))
		{
			return false;
		}

		$forum_data = $this->get_forum_data($forum_id);
		if ($forum_data && $forum_data['forum_password'])
		{
			if ($this->unlocked_forums === null)
			{
				$this->load_unlocked_forums();
			}

			if (!isset($this->unlocked_forums[$forum_id]))
			{
				return false;
			}
		}

		return true;
	}

	protected function load_unlocked_forums()
	{
		$this->unlocked_forums = array();

		if (empty($this->user->session_id))
		{
			return;
		}

		$sql = 'SELECT forum_id
			FROM ' . FORUMS_ACCESS_TABLE . "
			WHERE session_id = '" . $this->db->sql_escape($this->user->session_id) . "'
				AND user_id = " . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->unlocked_forums[(int) $row['forum_id']] = true;
		}
		$this->db->sql_freeresult($result);
	}

	protected function get_forum_name_and_slug($forum_id)
	{
		$forum_data = $this->get_forum_data($forum_id);
		if (is_array($forum_data))
		{
			return array($forum_data['forum_name'], $forum_data['forum_url']);
		}
		return array($forum_data ?: '', '');
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.modify_username_string'             => 'rewrite_member_url',
			'core.common'                             => array(
				array('resolve_forum_url', 0),
				array('resolve_member_url', 1),
			),
			'core.memberlist_view_profile'            => 'redirect_member_url',
			'core.acp_manage_forums_request_data'      => 'acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data'   => 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'      => 'acp_manage_forums_display_form',
			'core.acp_manage_forums_validate_data'     => 'acp_manage_forums_validate_data',
			'core.acp_manage_forums_update_data_after' => 'acp_manage_forums_update_data_after',
			'core.acp_manage_forums_delete_forum_after'=> 'acp_manage_forums_update_data_after',
			'core.acp_manage_forums_move_forums_after' => 'acp_manage_forums_update_data_after',
			'core.append_sid'							=> 'rewrite_url',
			'core.page_header'							=> 'handle_page_header',
			'core.page_header_after'					=> 'redirect_url',
			'core.twig_environment_render_template_after' => 'on_template_render_after',
			'core.viewforum_modify_topicrow'			=> 'rewrite_viewforum_links',
			'core.display_forums_modify_template_vars'	=> 'rewrite_last_post_links',
			'core.viewtopic_modify_post_row'			=> 'rewrite_viewtopic_post_links',
			'core.ucp_pm_compose_template'				=> 'rewrite_pm_quoted_link',
			'core.make_jumpbox_modify_tpl_ary'			=> 'rewrite_jumpbox_links',
			'core.approve_posts_after'					=> 'fix_mcp_post_moderation_redirect',
			'core.approve_topics_after'					=> 'fix_mcp_post_moderation_redirect',
			'core.disapprove_posts_after'				=> 'fix_mcp_post_moderation_redirect',
			'core.feed_modify_feed_row'					=> 'rewrite_feed_row',
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
		else if ($script_name === 'viewforum.' . $this->php_ext)
		{
			$url = $this->build_forum_url($parsed_params, $is_amp);
		}
		else if ($script_name === 'memberlist.' . $this->php_ext)
		{
			$url = $this->build_member_url($parsed_params);
		}

		if ($url && $url !== $original_url)
		{
			$event['append_sid_overwrite'] = $this->get_board_url() . '/' . ltrim($url, '/');
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
			$topic_row['U_VIEW_TOPIC'] = $this->get_board_url() . '/' . $new_url;
			$event['topic_row'] = $topic_row;
		}

		if (isset($row['topic_last_post_id']) && $row['topic_last_post_id'])
		{
			$post_id = $row['topic_last_post_id'];
			$new_last_post_url = $this->url_helper->generate_post_link($post_id, $topic_id, $title);

			if ($new_last_post_url)
			{
				$full_last_post_url = $this->get_board_url() . '/' . $new_last_post_url;
				$topic_row['U_LAST_POST'] = $full_last_post_url;
				$topic_row['LAST_POST_LINK'] = $full_last_post_url; // Some styles use this
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
				$forum_row['U_LAST_POST'] = $this->get_board_url() . '/' . $new_url;
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
				$full_post_url = $this->get_board_url() . '/' . $new_url;
				$post_row['U_MINI_POST'] = $full_post_url;
				$post_row['U_MINI_POST_VIEW'] = $full_post_url;
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
				list($forum_name, $forum_slug) = $this->get_forum_name_and_slug($forum_id);
				if (!$forum_name && isset($tpl_ary['FORUM_NAME']))
				{
					$forum_name = $tpl_ary['FORUM_NAME'];
				}
				$friendly_path = $this->url_helper->generate_forum_link($forum_id, (string) $forum_name, (string) $forum_slug);

				if ($friendly_path)
				{
					$tpl_ary['LINK'] = $this->get_board_url() . '/' . $friendly_path;
				}
			}
		}

		$event['tpl_ary'] = $tpl_ary;
	}

	protected function get_topic_data($topic_id)
	{
		$topic_id = (int) $topic_id;
		if (!$topic_id)
		{
			return false;
		}

		if (array_key_exists($topic_id, $this->topic_cache))
		{
			return $this->topic_cache[$topic_id];
		}

		$sql = 'SELECT topic_id, forum_id, topic_title, topic_poster, topic_visibility
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->topic_forum_cache[$topic_id] = (int) $row['forum_id'];

			// Ensure both forum is accessible (permissions/password) and content is visible (approved/not soft-deleted)
			if ($this->is_forum_accessible((int) $row['forum_id']) && $this->content_visibility->is_visible('topic', (int) $row['forum_id'], $row))
			{
				$this->topic_cache[$topic_id] = $row;
				return $row;
			}
		}

		$this->topic_cache[$topic_id] = false;
		return false;
	}

	protected function is_topic_accessible($topic_id)
	{
		return $this->get_topic_data($topic_id) !== false;
	}

	protected function get_topic_title($topic_id)
	{
		$topic_data = $this->get_topic_data($topic_id);
		return $topic_data ? $topic_data['topic_title'] : '';
	}

	protected function get_forum_id_from_topic($topic_id)
	{
		if (isset($this->topic_forum_cache[$topic_id]))
		{
			return $this->topic_forum_cache[$topic_id];
		}

		$topic_data = $this->get_topic_data($topic_id);
		if ($topic_data)
		{
			return (int) $topic_data['forum_id'];
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

		$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.topic_visibility, p.poster_id, p.post_visibility
				FROM ' . POSTS_TABLE . ' p
				JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
				WHERE p.post_id = ' . (int) $post_id;

		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row && $this->is_forum_accessible((int) $row['forum_id']) && $this->content_visibility->is_visible('topic', (int) $row['forum_id'], $row) && $this->content_visibility->is_visible('post', (int) $row['forum_id'], $row))
		{
			$this->post_topic_cache[$post_id] = $row;
			$this->topic_forum_cache[$row['topic_id']] = (int) $row['forum_id'];
			return $row;
		}

		$this->post_topic_cache[$post_id] = false;
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

		return $friendly_path ? $this->get_board_url() . '/' . $friendly_path : '';
	}

	protected function replace_viewtopic_post_urls($text, $post_id, $replacement_url)
	{
		$pattern = '~(?:(?:https?:)?//[^\s\]\)"]+/|\.\./|\./|/)?viewtopic\.' . preg_quote($this->php_ext, '~') . '\?[^\s\]\)"]+~i';

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
		else if ($script_name === 'viewforum.' . $this->php_ext)
		{
			$friendly_url = $this->build_forum_url($params, false);
		}
		else
		{
			$friendly_url = '';
		}

		return $friendly_url ? $this->get_board_url() . '/' . $friendly_url : $url;
	}

	protected function get_current_script_name()
	{
		$page_name = isset($this->user->page['page_name']) ? (string) $this->user->page['page_name'] : '';
		if (($pos = strpos($page_name, '/')) !== false)
		{
			$page_name = substr($page_name, 0, $pos);
		}
		return $page_name;
	}

	protected function get_script_name_from_url($url)
	{
		$path = parse_url($url, PHP_URL_PATH);

		if ($path === null || $path === false)
		{
			return '';
		}

		if (preg_match('/(?:^|\/)(viewtopic|viewforum|memberlist)\.' . preg_quote($this->php_ext, '/') . '(?:\/|$)/i', $path, $matches))
		{
			return $matches[1] . '.' . $this->php_ext;
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
		else if (is_string($params) && $params !== '')
		{
			$param_fragment = '';
			if (strpos($params, '#') !== false)
			{
				list($params, $param_fragment) = explode('#', $params, 2);
			}

			$params = ltrim($params, '?&');
			parse_str(str_replace('&amp;', '&', $params), $string_params);
			$parsed_params = array_merge($parsed_params, $string_params);

			if ($param_fragment !== '')
			{
				$parsed_params['#'] = $param_fragment;
			}
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
		else if ($topic_id)
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

		list($forum_name, $forum_slug) = $this->get_forum_name_and_slug($forum_id);
		$friendly_url = $this->url_helper->generate_forum_link($forum_id, (string) $forum_name, (string) $forum_slug);

		return $this->append_extra_params($friendly_url, $params, array('f', 'sid'), $is_amp);
	}

	protected $user_id_cache = array();

	protected function get_username_by_id($user_id)
	{
		$user_id = (int) $user_id;
		if (!$user_id)
		{
			return '';
		}

		if (isset($this->user_id_cache[$user_id]))
		{
			return $this->user_id_cache[$user_id];
		}

		$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql, 3600);
		$username = (string) $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		$this->user_id_cache[$user_id] = $username;

		return $username;
	}

	protected function build_member_url(array $params)
	{
		if (empty($this->config['vinny_url_members_enable']))
		{
			return '';
		}

		$mode = isset($params['mode']) ? $params['mode'] : '';
		if ($mode !== 'viewprofile')
		{
			return '';
		}

		$username = '';
		if (!empty($params['un']))
		{
			$username = rawurldecode($params['un']);
		}
		else if (!empty($params['u']))
		{
			$user_id = (int) $params['u'];
			$username = $this->get_username_by_id($user_id);
		}

		if (!$username)
		{
			return '';
		}

		$friendly_url = $this->url_helper->generate_member_link($username);

		return $this->append_extra_params($friendly_url, $params, array('mode', 'u', 'un', 'sid'));
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
		$this->add_open_graph_tags($event);
	}

	public function on_template_render_after($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$event['output'] = $this->process_html_output($event['output']);
	}

	public function rewrite_feed_row($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$row = $event['row'];

		if (!empty($row['post_id']) && !empty($row['topic_id']))
		{
			$this->post_topic_cache[(int) $row['post_id']] = array(
				'topic_id'    => (int) $row['topic_id'],
				'forum_id'    => isset($row['forum_id']) ? (int) $row['forum_id'] : 0,
				'topic_title' => isset($row['topic_title']) ? (string) $row['topic_title'] : '',
			);
		}

		if (!empty($row['topic_id']) && !empty($row['topic_title']))
		{
			$this->topic_cache[(int) $row['topic_id']] = (string) $row['topic_title'];
		}

		foreach ($row as $key => $value)
		{
			if (is_string($value) && (strpos($value, 'viewtopic.' . $this->php_ext) !== false || strpos($value, 'viewforum.' . $this->php_ext) !== false || strpos($value, 'memberlist.' . $this->php_ext) !== false))
			{
				$row[$key] = $this->process_html_output($value);
			}
		}

		$event['row'] = $row;
	}

	public function process_html_output($html)
	{
		if (empty($html) || empty($this->config['vinny_url_rewrite_enable']))
		{
			return $html;
		}

		// Rewrite viewtopic.php links (including viewtopic.php/slug)
		if (strpos($html, 'viewtopic.' . $this->php_ext) !== false)
		{
			$topic_pattern = '~(?:(?:https?:)?//[^\s\]\)"<>\']*?/|\.\./|\./|/)?viewtopic\.' . preg_quote($this->php_ext, '~') . '(?:/[^\s\]\)"<>\?]*?)?\?[^\s\]\)"<>\']+~i';
			$html = preg_replace_callback($topic_pattern, array($this, 'rewrite_html_topic_link'), $html);
		}

		// Rewrite viewforum.php links (including viewforum.php/slug)
		if (strpos($html, 'viewforum.' . $this->php_ext) !== false)
		{
			$forum_pattern = '~(?:(?:https?:)?//[^\s\]\)"<>\']*?/|\.\./|\./|/)?viewforum\.' . preg_quote($this->php_ext, '~') . '(?:/[^\s\]\)"<>\?]*?)?\?[^\s\]\)"<>\']+~i';
			$html = preg_replace_callback($forum_pattern, array($this, 'rewrite_html_forum_link'), $html);
		}

		// Rewrite member profile links (including memberlist.php/slug)
		if (!empty($this->config['vinny_url_members_enable']) && strpos($html, 'memberlist.' . $this->php_ext) !== false)
		{
			$member_pattern = '~(?:(?:https?:)?//[^\s\]\)"<>\']*?/|\.\./|\./|/)?memberlist\.' . preg_quote($this->php_ext, '~') . '(?:/[^\s\]\)"<>\?]*?)?\?[^\s\]\)"<>\']+~i';
			$html = preg_replace_callback($member_pattern, array($this, 'rewrite_html_member_link'), $html);
		}

		// Fix any topic URL with appended p=ID parameter (e.g. reader-response-t1439&p=1401050 or reader-response-t1439?p=1401050)
		if (strpos($html, '-t') !== false && strpos($html, 'p=') !== false)
		{
			$broken_post_pattern = '~(?:(?:https?:)?//[^\s\]\)"<>\']*?/|\.\./|\./|/)?([^\s\]\)"<>\']+?-t(\d+))(?:&amp;|&|\?)p=(\d+)(#p\3)?~i';
			$html = preg_replace_callback($broken_post_pattern, array($this, 'fix_malformed_post_link'), $html);
		}

		// Strip any leftover viewtopic.php/ or viewforum.php/ preceding friendly slugs
		if (strpos($html, 'viewtopic.' . $this->php_ext . '/') !== false || strpos($html, 'viewforum.' . $this->php_ext . '/') !== false)
		{
			$prefix_pattern = '~(?:(?:https?:)?//[^\s\]\)"<>\']*?/|\.\./|\./|/)?(?:viewtopic|viewforum)\.' . preg_quote($this->php_ext, '~') . '/([^\s\]\)"<>\?]+)(\?[^\s\]\)"<>]*)?~i';
			$html = preg_replace_callback($prefix_pattern, function ($matches)
			{
				$slug = $matches[1];
				$query = isset($matches[2]) ? $matches[2] : '';
				return $this->get_board_url() . '/' . ltrim($slug, '/') . $query;
			}, $html);
		}

		return $html;
	}

	public function rewrite_html_topic_link($matches)
	{
		$url = $matches[0];
		$parts = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));

		if (empty($parts['query']))
		{
			return $url;
		}

		parse_str(str_replace('&amp;', '&', $parts['query']), $params);

		$topic_id = isset($params['t']) ? (int) $params['t'] : 0;
		$post_id = isset($params['p']) ? (int) $params['p'] : 0;

		if ($post_id > 0)
		{
			$topic_title = '';
			if ($topic_id > 0)
			{
				$topic_title = $this->get_topic_title($topic_id);
			}

			if (!$topic_title)
			{
				$topic_info = $this->get_topic_info_from_post($post_id);
				if ($topic_info)
				{
					$topic_id = (int) $topic_info['topic_id'];
					$topic_title = (string) $topic_info['topic_title'];
				}
			}

			$friendly = $this->url_helper->generate_post_link($post_id, $topic_id, $topic_title);
			if ($friendly)
			{
				return $this->rebuild_url_with_friendly_path($url, $friendly, $params, array('t', 'p', 'f'));
			}
		}

		if ($topic_id > 0)
		{
			$title = $this->get_topic_title($topic_id);
			if ($title)
			{
				$friendly = $this->url_helper->generate_topic_link($topic_id, (string) $title);
				return $friendly ? $this->rebuild_url_with_friendly_path($url, $friendly, $params, array('t', 'f', 'p')) : $url;
			}
		}

		return $url;
	}

	public function fix_malformed_post_link($matches)
	{
		$base_url = $matches[1];
		$topic_id = (int) $matches[2];
		$post_id = (int) $matches[3];

		$topic_title = $this->get_topic_title($topic_id);
		$friendly = $this->url_helper->generate_post_link($post_id, $topic_id, (string) $topic_title);

		if (!$friendly)
		{
			return $matches[0];
		}

		return preg_replace('/-t' . $topic_id . '$/i', "-t{$topic_id}-p{$post_id}#p{$post_id}", $base_url);
	}

	public function rewrite_html_forum_link($matches)
	{
		$url = $matches[0];
		$parts = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));

		if (empty($parts['query']))
		{
			return $url;
		}

		parse_str(str_replace('&amp;', '&', $parts['query']), $params);

		$forum_id = isset($params['f']) ? (int) $params['f'] : 0;

		if ($forum_id > 0)
		{
			list($forum_name, $forum_slug) = $this->get_forum_name_and_slug($forum_id);
			if ($forum_name || $forum_slug)
			{
				$friendly = $this->url_helper->generate_forum_link($forum_id, (string) $forum_name, (string) $forum_slug);
				return $friendly ? $this->rebuild_url_with_friendly_path($url, $friendly, $params, array('f')) : $url;
			}
		}

		return $url;
	}

	public function rewrite_html_member_link($matches)
	{
		$url = $matches[0];
		$parts = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));

		if (empty($parts['query']))
		{
			return $url;
		}

		parse_str(str_replace('&amp;', '&', $parts['query']), $params);

		$mode = isset($params['mode']) ? $params['mode'] : '';
		if ($mode !== 'viewprofile')
		{
			return $url;
		}

		$username = '';
		if (!empty($params['un']))
		{
			$username = rawurldecode($params['un']);
		}
		else if (!empty($params['u']))
		{
			$user_id = (int) $params['u'];
			$username = $this->get_username_by_id($user_id);
		}

		if ($username)
		{
			$friendly = $this->url_helper->generate_member_link($username);
			return $friendly ? $this->rebuild_url_with_friendly_path($url, $friendly, $params, array('mode', 'u', 'un')) : $url;
		}

		return $url;
	}

	protected function rebuild_url_with_friendly_path($original_url, $friendly_path, array $params, array $consumed_keys)
	{
		foreach ($consumed_keys as $key)
		{
			unset($params[$key]);
		}

		$fragment = '';
		$hash_pos = strpos($original_url, '#');
		if ($hash_pos !== false)
		{
			$fragment = substr($original_url, $hash_pos);
			$original_url = substr($original_url, 0, $hash_pos);
		}

		$base_prefix = $this->get_board_url() . '/';
		$query_string = !empty($params) ? '?' . http_build_query($params, '', '&amp;') : '';

		return $base_prefix . ltrim($friendly_path, '/') . $query_string . $fragment;
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

		$script_name = $this->get_current_script_name();
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

		if (!$this->is_forum_accessible($forum_id))
		{
			return;
		}

		$start = $this->request->variable('start', 0);
		$topic_url = $this->build_topic_url(array('t' => $topic_id), true);
		if ($topic_url)
		{
			$this->template->assign_vars(array(
				'U_TOPIC'		=> $this->get_board_url() . '/' . $topic_url,
				'U_CANONICAL'	=> $this->get_board_url() . '/' . $topic_url,
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
				$this->template->assign_var('U_BOOKMARK_TOPIC', $this->get_board_url() . '/' . $bookmark_url);
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
				$this->template->assign_var('U_PRINT_TOPIC', $this->get_board_url() . '/' . $print_url);
			}
		}
	}

	public function add_open_graph_tags($event)
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

		$script_name = $this->get_current_script_name();

		if (!$this->is_open_graph_page($script_name))
		{
			return;
		}

		$page_data = $this->get_open_graph_page_data($script_name);

		if ($page_data['mode'] === 'topic' && $page_data['id'])
		{
			$forum_id = $this->get_forum_id_from_topic($page_data['id']);
			if (!$this->is_forum_accessible($forum_id))
			{
				return;
			}
		}
		else if ($page_data['mode'] === 'forum' && $page_data['id'])
		{
			if (!$this->is_forum_accessible($page_data['id']))
			{
				return;
			}
		}

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
		$script_name = $this->get_current_script_name();
		return $script_name === 'index.' . $this->php_ext ||
			$script_name === 'viewforum.' . $this->php_ext ||
			$script_name === 'viewtopic.' . $this->php_ext;
	}

	protected function get_open_graph_page_data($script_name)
	{
		$script_name = $this->get_current_script_name();
		$id = 0;
		$mode = '';
		$post_id = 0;

		if ($script_name === 'viewtopic.' . $this->php_ext)
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
		else if ($script_name === 'viewforum.' . $this->php_ext)
		{
			$id = $this->request->variable('f', 0);
			$mode = 'forum';
		}
		else if ($script_name === 'index.' . $this->php_ext)
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
		else if ($page_data['mode'] === 'topic' && $page_data['id'])
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
			return $this->get_board_url() . '/' . $this->url_helper->generate_topic_link($page_data['id'], (string) $title);
		}
		else if ($page_data['mode'] === 'forum' && $page_data['id'])
		{
			list($forum_name, $forum_slug) = $this->get_forum_name_and_slug($page_data['id']);
			return $this->get_board_url() . '/' . $this->url_helper->generate_forum_link($page_data['id'], (string) $forum_name, (string) $forum_slug);
		}
		else if ($page_data['mode'] === 'index')
		{
			return $this->get_board_url() . '/';
		}

		$base_script_name = $script_name;
		if (($pos = strpos($base_script_name, '/')) !== false)
		{
			$base_script_name = substr($base_script_name, 0, $pos);
		}

		return $this->get_board_url() . '/' . $base_script_name;
	}

	protected function get_forum_open_graph_description($forum_id, $default_description)
	{
		$sql = 'SELECT forum_desc
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql, 600);
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

		$sql = 'SELECT p.post_text, p.post_id, p.bbcode_uid, p.bbcode_bitfield, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.poster_id, p.post_visibility, t.forum_id
			FROM ' . TOPICS_TABLE . ' t
			JOIN ' . POSTS_TABLE . ' p ON t.topic_first_post_id = p.post_id
			WHERE t.topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql, 600);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || !$this->content_visibility->is_visible('post', (int) $row['forum_id'], $row))
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
			$ellipsis = $this->user->lang('ELLIPSIS');
			$ellipsis_len = mb_strlen($ellipsis);
			$text = mb_substr($text, 0, max(0, $limit - $ellipsis_len)) . $ellipsis;
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
			$img_url = $this->get_board_url() . '/' . substr($img_url, 2);
		}
		else if (strpos($img_url, '/') === 0)
		{
			$img_url = $this->get_board_url() . '/' . ltrim($img_url, '/');
		}

		$smilies_path = $this->get_board_url() . '/' . $this->config['smilies_path'];

		return (strpos($img_url, $smilies_path) === false) ? $img_url : '';
	}

	protected function get_open_graph_image_from_attachment($post_id)
	{
		$like_image = $this->db->sql_like_expression('image/' . $this->db->get_any_char());

		$sql_array = array(
			'SELECT'   => 'attach_id, extension, mimetype',
			'FROM'     => array(
				ATTACHMENTS_TABLE => 'a',
			),
			'WHERE'    => 'post_msg_id = ' . (int) $post_id . '
				AND in_message = 0
				AND (mimetype ' . $like_image . ' OR ' . $this->db->sql_in_set('extension', array('jpg', 'jpeg', 'png', 'gif', 'webp')) . ')',
			'ORDER_BY' => 'filetime ASC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, 1);
		$attachment = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $attachment ? $this->get_board_url() . '/download/file.' . $this->php_ext . '?id=' . $attachment['attach_id'] : '';
	}

	protected function get_default_open_graph_image()
	{
		$theme_path = isset($this->user->theme['theme_path']) ? $this->user->theme['theme_path'] : 'prosilver';
		return $this->get_board_url() . '/styles/' . $theme_path . '/theme/images/site_logo.svg';
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

		if ($this->request->is_set_post('login') || $this->request->server('REQUEST_METHOD') === 'POST')
		{
			return;
		}

		$script_name = $this->get_current_script_name();
		if ($script_name !== 'viewtopic.' . $this->php_ext && $script_name !== 'viewforum.' . $this->php_ext)
		{
			return;
		}

		$request_uri = $this->request->server('REQUEST_URI');
		if (strpos($request_uri, '.php') === false)
		{
			return;
		}

		if ($script_name === 'viewtopic.' . $this->php_ext)
		{
			$topic_id = $this->request->variable('t', 0);
			$post_id = $this->request->variable('p', 0);
			$forum_id = $this->request->variable('f', 0);

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

			if (!$this->is_forum_accessible($forum_id))
			{
				return;
			}

			$query_params = $this->get_redirect_query_params(array('f', 't', 'p', 'sid'));
			$friendly_params = $query_params;
			$friendly_params['#'] = '';
			$friendly_params['t'] = $topic_id;
			$friendly_params['p'] = $post_id;
			$friendly_url = $this->build_topic_url($friendly_params, false);
		}
		else
		{
			$forum_id = $this->request->variable('f', 0);
			if (!$forum_id || !$this->is_forum_accessible($forum_id))
			{
				return;
			}

			$query_params = $this->get_redirect_query_params(array('f', 'sid'));
			$friendly_params = $query_params;
			$friendly_params['#'] = '';
			$friendly_params['f'] = $forum_id;
			$friendly_url = $this->build_forum_url($friendly_params, false);
		}

		if ($friendly_url)
		{
			$redirect_url = $this->get_board_url() . '/' . $friendly_url;
			$redirect_url = redirect($redirect_url, true);

			garbage_collection();
			header('Location: ' . $redirect_url, true, 301);
			exit_handler();
		}
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

	public function acp_manage_forums_request_data($event)
	{
		$forum_url = $this->request->variable('vinny_url_forum_slug', '', true);
		$forum_url = $this->url_helper->clean_url($forum_url);
		$event->update_subarray('forum_data', 'vinny_url_forum_slug', $forum_url);
	}

	public function acp_manage_forums_initialise_data($event)
	{
		if ($event['action'] !== 'edit' && !$event['update'])
		{
			$event->update_subarray('forum_data', 'vinny_url_forum_slug', '');
		}
	}

	public function acp_manage_forums_display_form($event)
	{
		$forum_data = $event['forum_data'];
		$event->update_subarray('template_data', 'VINNY_URL_FORUM_SLUG', isset($forum_data['vinny_url_forum_slug']) ? $forum_data['vinny_url_forum_slug'] : '');
	}

	public function acp_manage_forums_validate_data($event)
	{
		$forum_data = $event['forum_data'];
		$forum_url = isset($forum_data['vinny_url_forum_slug']) ? (string) $forum_data['vinny_url_forum_slug'] : '';

		if (utf8_strlen($forum_url) > 255)
		{
			$errors = $event['errors'];
			$errors[] = $this->user->lang('VINNY_URL_FORUM_SLUG_TOO_LONG');
			$event['errors'] = $errors;
			return;
		}

		if ($forum_url !== '' && $this->url_helper->clean_url($forum_url) !== $forum_url)
		{
			$errors = $event['errors'];
			$errors[] = $this->user->lang('VINNY_URL_FORUM_SLUG_WRONG_FORMAT');
			$event['errors'] = $errors;
		}
	}

	public function acp_manage_forums_update_data_after($event)
	{
		$this->forum_cache = null;
		$this->cache->destroy('sql', FORUMS_TABLE);
	}

	public function resolve_forum_url($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']))
		{
			return;
		}

		$forum_uri = $this->request->variable('forum_url', '', true);
		if (!$forum_uri)
		{
			return;
		}

		$forum_uri = $this->url_helper->clean_url($forum_uri);
		$forum_id = 0;

		$sql = 'SELECT forum_id, forum_name, vinny_url_forum_slug FROM ' . FORUMS_TABLE;
		$result = $this->db->sql_query($sql, 600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$custom_url = !empty($row['vinny_url_forum_slug']) ? $this->url_helper->clean_url($row['vinny_url_forum_slug']) : '';
			$fallback_url = $this->url_helper->clean_url($row['forum_name']);

			if (($custom_url && $custom_url === $forum_uri) || (!$custom_url && $fallback_url === $forum_uri))
			{
				$forum_id = (int) $row['forum_id'];
				break;
			}
		}
		$this->db->sql_freeresult($result);

		if ($forum_id)
		{
			$this->request->overwrite('f', $forum_id, \phpbb\request\request_interface::GET);
			$this->request->overwrite('f', $forum_id, \phpbb\request\request_interface::REQUEST);
		}
	}

	public function rewrite_member_url($event)
	{
		if (empty($this->config['vinny_url_rewrite_enable']) || empty($this->config['vinny_url_members_enable']))
		{
			return;
		}

		if (empty($event['user_id']) || empty($event['username']))
		{
			return;
		}

		$new_url = $this->get_board_url() . '/' . $this->url_helper->generate_member_link($event['username']);

		if ($event['mode'] === 'profile')
		{
			$event['username_string'] = $new_url;
			return;
		}

		if (preg_match('/href="[^"]+"/', $event['username_string']))
		{
			$event['username_string'] = preg_replace('/href="[^"]+"/', 'href="' . $new_url . '"', $event['username_string']);
		}
	}

	public function resolve_member_url($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']) || empty($this->config['vinny_url_members_enable']))
		{
			return;
		}

		$username_clean = $this->request->variable('un', '', true);

		if ($username_clean === '')
		{
			return;
		}

		$username_clean = rawurldecode($username_clean);

		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . "
			WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($username_clean)) . "'";

		$result = $this->db->sql_query_limit($sql, 1);
		$user_id = (int) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		if ($user_id)
		{
			$this->request->overwrite('mode', 'viewprofile', \phpbb\request\request_interface::GET);
			$this->request->overwrite('u', $user_id, \phpbb\request\request_interface::GET);
			$this->request->overwrite('mode', 'viewprofile', \phpbb\request\request_interface::REQUEST);
			$this->request->overwrite('u', $user_id, \phpbb\request\request_interface::REQUEST);
		}
	}

	public function redirect_member_url($event)
	{
		if (defined('IN_ADMIN') && IN_ADMIN)
		{
			return;
		}

		if (empty($this->config['vinny_url_rewrite_enable']) || empty($this->config['vinny_url_redirect_enable']) || empty($this->config['vinny_url_members_enable']))
		{
			return;
		}

		$request_uri = $this->request->server('REQUEST_URI');

		if (strpos($request_uri, 'memberlist.' . $this->php_ext) === false)
		{
			return;
		}

		if (empty($event['member']['username']))
		{
			return;
		}

		$redirect_url = $this->get_board_url() . '/' . $this->url_helper->generate_member_link($event['member']['username']);
		$redirect_url = redirect($redirect_url, true);

		garbage_collection();
		header('Location: ' . $redirect_url, true, 301);
		exit_handler();
	}
}
