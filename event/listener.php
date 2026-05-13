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
	protected $config;
	protected $template;
	protected $user;
	protected $url_helper;
	protected $db;
    protected $request;
	protected $root_path;
	protected $php_ext;

    protected $forum_cache = null;

	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, \vinny\urlrewriting\helper\url_helper $url_helper, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->url_helper = $url_helper;
        $this->db = $db;
        $this->request = $request;
		$this->root_path = $root_path;
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
			'core.page_header'							=> 'add_seo_tags',
			'core.page_header_after'					=> 'redirect_url',
            'core.viewforum_modify_topicrow'			=> 'rewrite_viewforum_links',
            'core.display_forums_modify_template_vars'	=> 'rewrite_last_post_links',
            'core.viewtopic_modify_post_row'			=> 'rewrite_viewtopic_post_links',
            'core.ucp_pm_compose_template'				=> 'rewrite_pm_quoted_link',
            'core.submit_post_end'						=> 'fix_redirect_url',
            'core.make_jumpbox_modify_tpl_ary'			=> 'rewrite_jumpbox_links',
            'core.functions.redirect'					=> 'fix_core_redirect',
            'core.disapprove_posts_after'				=> 'fix_disapprove_topic_redirect',
		);
	}
    
    public function rewrite_url($event)
    {
        if (defined('IN_ADMIN') && IN_ADMIN) return;
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

        $url = $event['url'];
        $params = $event['params'];

        $original_url = $url;
        $original_anchor = '';

        // Fix redirect URLs that have hardcoded ampersands appended by phpBB core
        if (is_string($params) && strpos($params, 'redirect=') !== false) {
            if (preg_match('/(?:^|&amp;|&)redirect=([^&]+)/', $params, $match)) {
                $redirect_val = urldecode($match[1]);
                if (strpos($redirect_val, '?') === false && strpos($redirect_val, '&') !== false) {
                     $new_redirect_val = urlencode(preg_replace('/&/', '?', $redirect_val, 1));
                     $params = str_replace('redirect=' . $match[1], 'redirect=' . $new_redirect_val, $params);
                     $event['params'] = $params;
                }
            }
        } elseif (is_array($params) && isset($params['redirect'])) {
            $redirect_val = $params['redirect'];
            if (strpos($redirect_val, '?') === false && strpos($redirect_val, '&') !== false) {
                $params['redirect'] = preg_replace('/&/', '?', $redirect_val, 1);
                $event['params'] = $params;
            }
        }
        
        if (strpos($original_url, '#') !== false) {
            $parts = explode('#', $original_url, 2);
            $original_anchor = $parts[1];
        } elseif (is_string($params) && strpos($params, '#') !== false) {
            $parts = explode('#', $params, 2);
            $original_anchor = $parts[1];
        } elseif (is_array($params) && isset($params['#'])) {
            $original_anchor = $params['#'];
        }

        if (strpos($url, 'viewtopic.' . $this->php_ext) !== false)
        {
            $this->rewrite_topic_url($url, $params);
        }
        else if (strpos($url, 'viewforum.' . $this->php_ext) !== false)
        {
            $this->rewrite_forum_url($url, $params);
        }

        if ($url !== $original_url) {
            if ($original_anchor) {
                if (strpos($url, '#') !== false) {
                    $url = preg_replace('/#.*$/', '#' . $original_anchor, $url);
                } else {
                    $url .= '#' . $original_anchor;
                }
            } else {
                if (strpos($url, '#') !== false) {
                    $url = preg_replace('/#.*$/', '', $url);
                }
            }

            $event['append_sid_overwrite'] = $url;
        }
    }

    public function rewrite_viewforum_links($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

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
        if (empty($this->config['vinny_url_rewrite_enable'])) return;
        
        $forum_row = $event['forum_row'];
        $row = $event['row'];

        if (isset($row['forum_last_post_id']) && $row['forum_last_post_id'])
        {
             $post_id = $row['forum_last_post_id'];
             $mode = isset($this->config['vinny_url_rewrite_mode']) ? $this->config['vinny_url_rewrite_mode'] : 1;
             
             $topic_id = 0;
             $topic_title = '';

             if ($mode == 1)
             {
                 // Need to fetch topic info
                 $topic_info = $this->get_topic_info_from_post($post_id);
                 if ($topic_info)
                 {
                     $topic_id = $topic_info['topic_id'];
                     $topic_title = $topic_info['topic_title'];
                 }
             }

             $new_url = $this->url_helper->generate_post_link($post_id, $topic_id, $topic_title);

             if ($new_url)
             {
                 $forum_row['U_LAST_POST'] = $new_url;
                 $event['forum_row'] = $forum_row;
             }
        }
    }

    public function rewrite_viewtopic_post_links($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

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
                $post_row['U_mini_post'] = $new_url; // just in case
                $event['post_row'] = $post_row;
            }
        }
    }

    public function rewrite_pm_quoted_link($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

        if ($this->request->variable('action', '') !== 'quotepost') return;

        $post_id = $this->request->variable('p', 0);
        if (!$post_id) return;

        $template_ary = $event['template_ary'];

        if (!isset($template_ary['MESSAGE'])) {
            return;
        }

        $message_text = $template_ary['MESSAGE'];

        if (empty($message_text)) {
            return;
        }

        $topic_info = $this->get_topic_info_from_post($post_id);
        if (!$topic_info) return;

        $friendly_path = $this->url_helper->generate_post_link($post_id, $topic_info['topic_id'], $topic_info['topic_title']);
        $full_friendly_url = generate_board_url() . '/' . $friendly_path;

        $pattern = '~\[url=(https?://[^\]]+?/viewtopic\.' . preg_quote($this->php_ext, '~') . '\?(?:f=\d+&amp;)?(?:t=\d+&amp;)?p=' . (int)$post_id . '(?:#p' . (int)$post_id . ')?)\](.*?)\[/url\]~i';

        $replacement = '[url=' . $full_friendly_url . ']$2[/url]';

        $new_message = preg_replace($pattern, $replacement, $message_text);

        if ($new_message !== $message_text && $new_message !== null) {
            $template_ary['MESSAGE'] = $new_message;
            $event['template_ary'] = $template_ary;
        }
    }

    public function fix_redirect_url($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

        $url = $event['url'];

        if (preg_match('/(#p\d+)\1$/', $url)) {
            $url = preg_replace('/(#p\d+)\1$/', '$1', $url);
            $event['url'] = $url;
        }
        
        if (preg_match('/-p\d+\?p=\d+/', $url)) {
             $url = preg_replace('/(-p\d+)\?p=\d+(?:&amp;|&)/', '$1?', $url);
             $url = preg_replace('/(-p\d+)\?p=\d+/', '$1', $url);
             $event['url'] = $url;
        }
    }

    public function fix_core_redirect($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

        $url = $event['url'];

        if (strpos($url, '?') === false && preg_match('#(?:^|/)(?:[^/?&]+-t\d+(?:-p\d+)?|[^/?&]+-f\d+|post-p\d+)&\w+=#', $url))
        {
            $event['url'] = preg_replace('/&/', '?', $url, 1);
        }
    }

    public function rewrite_jumpbox_links($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;
        
        // Do not rewrite links inside MCP
        if (isset($this->user->page['page_name']) && strpos($this->user->page['page_name'], 'mcp.' . $this->php_ext) !== false)
        {
            return;
        }

        $tpl_ary = $event['tpl_ary'];

        foreach ($tpl_ary as &$item)
        {
            if (isset($item['FORUM_ID']))
            {
                $forum_id = (int) $item['FORUM_ID'];
                
                // If FORUM_ID > 0
                if ($forum_id > 0)
                {
                    $forum_name = isset($item['FORUM_NAME']) ? $item['FORUM_NAME'] : $this->get_forum_data($forum_id);
                    $friendly_path = $this->url_helper->generate_forum_link($forum_id, (string)$forum_name);
                    
                    if ($friendly_path) {
                        $item['LINK'] = generate_board_url() . '/' . $friendly_path;
                    }
                }
            }
        }
        
        $event['tpl_ary'] = $tpl_ary;
    }

    protected $topic_cache = array();
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

    protected function get_topic_info_from_post($post_id)
    {
        if (isset($this->post_topic_cache[$post_id]))
        {
            return $this->post_topic_cache[$post_id];
        }

        $sql = 'SELECT t.topic_id, t.topic_title 
                FROM ' . POSTS_TABLE . ' p
                JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
                WHERE p.post_id = ' . (int) $post_id;
        
        $result = $this->db->sql_query($sql, 600);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row)
        {
            $this->post_topic_cache[$post_id] = $row;
            return $row;
        }
        return false;
    }

	protected function rewrite_topic_url(&$url, $params)
	{
		$topic_id = 0;
        $post_id = 0;

        if (is_array($params))
        {
             if (isset($params['t'])) $topic_id = (int) $params['t'];
             if (isset($params['p'])) $post_id = (int) $params['p'];
        }
        else
        {
            parse_str($params, $parsed_params);
            if (isset($parsed_params['t'])) $topic_id = (int) $parsed_params['t'];
            if (isset($parsed_params['p'])) $post_id = (int) $parsed_params['p'];
        }

        if ($post_id)
        {
            // If we have topic_id, fetch title
            $title = '';
            if ($topic_id) {
                $title = $this->get_topic_title($topic_id);
            } 
            
            // If still no title (or no topic_id), try to fetch from post
            if (!$title) {
                $info = $this->get_topic_info_from_post($post_id);
                if ($info) {
                    $topic_id = $info['topic_id'];
                    $title = $info['topic_title'];
                }
            }

            $url = $this->url_helper->generate_post_link($post_id, $topic_id, $title);
            return;
        }

        if ($topic_id)
        {
             $title = $this->get_topic_title($topic_id);
             $url = $this->url_helper->generate_topic_link($topic_id, (string)$title);
        }
	}

	protected function rewrite_forum_url(&$url, $params)
	{
		$forum_id = 0;
        if (is_array($params))
        {
             if (isset($params['f'])) $forum_id = (int) $params['f'];
        }
        else
        {
            parse_str($params, $parsed_params);
            if (isset($parsed_params['f'])) $forum_id = (int) $parsed_params['f'];
        }

        if ($forum_id)
        {
             $forum_name = $this->get_forum_data($forum_id);
             $url = $this->url_helper->generate_forum_link($forum_id, (string)$forum_name);
        }
	}

	public function add_seo_tags($event)
	{
        if (defined('IN_ADMIN') && IN_ADMIN) return;
        if (empty($this->config['vinny_url_opengraph_enable'])) return;

        $script_name = $this->user->page['page_name'];

        // Only add Open Graph tags on index, viewforum, and viewtopic
        if (strpos($script_name, 'index.' . $this->php_ext) !== 0 && 
            strpos($script_name, 'viewforum.' . $this->php_ext) !== 0 && 
            strpos($script_name, 'viewtopic.' . $this->php_ext) !== 0)
        {
            return;
        }

        // Strip path info from script_name if necessary
        $base_script_name = $script_name;
        if (($pos = strpos($base_script_name, '/')) !== false)
        {
            $base_script_name = substr($base_script_name, 0, $pos);
        }

        if ($base_script_name === 'index.' . $this->php_ext)
        {
            $canonical_url = generate_board_url() . '/';
        }
        else
        {
            $canonical_url = generate_board_url() . '/' . $base_script_name;
        }
        
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
        
        if ($id)
        {
            if ($mode == 'topic') {
                $title = $this->get_topic_title($id);
                $canonical_url = !empty($this->config['vinny_url_rewrite_enable']) ? generate_board_url() . '/' . $this->url_helper->generate_topic_link($id, (string)$title) : generate_board_url() . '/viewtopic.' . $this->php_ext . '?t=' . $id;
            } elseif ($mode == 'forum') {
                $forum_name = $this->get_forum_data($id);
                $canonical_url = !empty($this->config['vinny_url_rewrite_enable']) ? generate_board_url() . '/' . $this->url_helper->generate_forum_link($id, (string)$forum_name) : generate_board_url() . '/viewforum.' . $this->php_ext . '?f=' . $id;
            }
        }

        // Open Graph
        if ($this->config['vinny_url_opengraph_enable'])
        {
            $og_title = isset($event['page_title']) ? $event['page_title'] : $this->config['sitename'];
            $og_url = $canonical_url;
            $og_desc = $this->config['site_desc']; 
            
            // Check for viewforum
            $script_name = $this->user->page['page_name'];
            if (strpos($script_name, 'viewforum') !== false)
            {
                $forum_id = $this->request->variable('f', 0);
                if ($forum_id)
                {
                    $sql = 'SELECT forum_desc, forum_desc_uid, forum_desc_bitfield, forum_desc_options FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $forum_id;
                    $result = $this->db->sql_query($sql, 7200);
                    $row = $this->db->sql_fetchrow($result);
                    $this->db->sql_freeresult($result);
                    
                    if ($row && !empty($row['forum_desc']))
                    {
                         $text = $row['forum_desc'];
                         $text = strip_tags($text);
                         $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                         $text = trim(preg_replace('/\s+/', ' ', $text));
                         if ($text) $og_desc = $text;
                    }
                }
            }
            // Check for viewtopic
            elseif (strpos($script_name, 'viewtopic') !== false)
            {
                $topic_id = $this->request->variable('t', 0);
                if (!$topic_id)
                {
                    $topic_id = $id;
                }
                
                if ($topic_id)
                {
                    $sql = 'SELECT p.post_text, p.post_id, p.bbcode_uid, p.bbcode_bitfield, p.enable_bbcode, p.enable_smilies, p.enable_magic_url
                        FROM ' . TOPICS_TABLE . ' t
                        JOIN ' . POSTS_TABLE . ' p ON t.topic_first_post_id = p.post_id
                        WHERE t.topic_id = ' . (int) $topic_id;
                    
                    $result = $this->db->sql_query($sql, 600);
                    $row = $this->db->sql_fetchrow($result);
                    $this->db->sql_freeresult($result);
                    
                    if ($row)
                    {
                        $post_text = $row['post_text'];
                        $post_id = $row['post_id'];
                        
                        $bbcode_options = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
                            (($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
                            (($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

                        $html_content = generate_text_for_display($post_text, $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

                        // Image extraction logic
                        if (preg_match('/<img[^>]+src="([^"]+)"/i', $html_content, $matches))
                        {
                            $img_url = $matches[1];
                            if (strpos($img_url, './') === 0) {
                                $img_url = generate_board_url() . '/' . substr($img_url, 2);
                            } 
                            elseif (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                                $img_url = generate_board_url() . '/' . ltrim($img_url, '/');
                            }
                            
                            $smilies_path = generate_board_url() . '/' . $this->config['smilies_path'];
                            if (strpos($img_url, $smilies_path) === false) {
                                $og_image = $img_url;
                            }
                        }
                        
                        // Fallback to attachments
                        if (!isset($og_image))
                        {
                            $sql_attach = 'SELECT attach_id, extension, mimetype 
                                FROM ' . ATTACHMENTS_TABLE . '
                                WHERE post_msg_id = ' . (int) $post_id . '
                                AND in_message = 0
                                AND (mimetype ' . $this->db->sql_like_expression('image/' . $this->db->get_any_char()) . '
                                    OR ' . $this->db->sql_in_set('extension', array('jpg', 'jpeg', 'png', 'gif', 'webp')) . ')
                                ORDER BY filetime ASC';
                            
                            $result_attach = $this->db->sql_query_limit($sql_attach, 1);
                            $attachment = $this->db->sql_fetchrow($result_attach);
                            $this->db->sql_freeresult($result_attach);
                            
                            if ($attachment)
                            {
                                $og_image = generate_board_url() . "/download/file." . $this->php_ext . "?id=" . $attachment['attach_id'];
                            }
                        }
                        
                        if ($html_content)
                        {
                            $text = strip_tags($html_content);
                            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                            $text = trim(preg_replace('/\s+/', ' ', $text));
                            if (mb_strlen($text) > 200) {
                                $text = mb_substr($text, 0, 197) . '...';
                            }
                            if ($text) $og_desc = $text;
                        }
                    }
                }
            }
            
            $theme_path = (isset($this->user->theme['theme_path'])) ? $this->user->theme['theme_path'] : 'prosilver';
            
            if (!isset($og_image)) {
                $og_image = generate_board_url() . '/styles/' . $theme_path . '/theme/images/site_logo.svg';
            }

            $this->template->assign_vars(array(
                'S_OPENGRAPH'   => true,
                'OG_TITLE'      => utf8_htmlspecialchars($og_title),
                'OG_URL'        => utf8_htmlspecialchars($og_url),
                'OG_DESC'       => utf8_htmlspecialchars($og_desc),
                'OG_IMAGE'      => utf8_htmlspecialchars($og_image),
                'OG_TYPE'       => (strpos($script_name, 'viewtopic') !== false) ? 'article' : 'website',
            ));
        }
	}

    public function redirect_url($event)
    {
        if (defined('IN_ADMIN') && IN_ADMIN) return;
        if (empty($this->config['vinny_url_rewrite_enable'])) return;
        if (empty($this->config['vinny_url_redirect_enable'])) return;

        $script_name = $this->user->page['page_name'];
        if ($script_name == 'viewtopic.' . $this->php_ext || $script_name == 'viewforum.' . $this->php_ext)
        {
            $request_uri = $this->request->server('REQUEST_URI');
            
            if (strpos($request_uri, '.php') !== false)
            {
                $id = 0;
                $mode = '';
                if ($script_name == 'viewtopic.' . $this->php_ext)
                {
                    $id = $this->request->variable('t', 0);
                    $post_id = $this->request->variable('p', 0);
                    $mode = ($post_id) ? 'post' : 'topic';
                }
                elseif ($script_name == 'viewforum.' . $this->php_ext)
                {
                    $id = $this->request->variable('f', 0);
                    $mode = 'forum';
                }

                $friendly_url = '';

                if ($mode == 'forum' && $id)
                {
                     $forum_name = $this->get_forum_data($id);
                     $friendly_url = $this->url_helper->generate_forum_link($id, (string)$forum_name);
                }
                elseif ($mode == 'topic' && $id)
                {
                     $title = $this->get_topic_title($id);
                     $friendly_url = $this->url_helper->generate_topic_link($id, (string)$title);
                }
                elseif ($mode == 'post' && $post_id)
                {
                     $topic_info = $this->get_topic_info_from_post($post_id);
                     if ($topic_info) {
                         $friendly_url = $this->url_helper->generate_post_link($post_id, $topic_info['topic_id'], $topic_info['topic_title']);
                     } else {
                         // Minimal fallback
                         $friendly_url = $this->url_helper->generate_post_link($post_id);
                     }
                }

                if ($friendly_url)
                {
                    $redirect_url = generate_board_url() . '/' . $friendly_url;
                    
                    http_response_code(301);
                    redirect($redirect_url);
                }
            }
        }
    }

    public function fix_disapprove_topic_redirect($event)
    {
        if (empty($this->config['vinny_url_rewrite_enable'])) return;

        $num_disapproved_topics = $event['num_disapproved_topics'];
        $redirect = $event['redirect'];
        $post_info = $event['post_info'];
        $post_id_list = $this->request->variable('post_id_list', array(0));
        
        if (strpos($redirect, 'mcp.' . $this->php_ext) === false && strpos($redirect, 'index.' . $this->php_ext) === false)
        {
            if ($num_disapproved_topics == 0)
            {
                if (isset($post_id_list[0]))
                {
                    $find1 = '?p=' . $post_id_list[0] . '#p' . $post_id_list[0];
                    $find2 = '&amp;p=' . $post_id_list[0] . '#p' . $post_id_list[0];
                    
                    if (strpos($redirect, $find1) !== false)
                    {
                        $event['redirect'] = str_replace($find1, '', $redirect);
                    }
                    elseif (strpos($redirect, $find2) !== false)
                    {
                        $event['redirect'] = str_replace($find2, '', $redirect);
                    }
                }
            }
            else
            {
                if (!empty($post_info))
                {
                    $first_post = reset($post_info);
                    if (isset($first_post['forum_id']))
                    {
                        $forum_id = $first_post['forum_id'];
                        $event['redirect'] = append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $forum_id);
                    }
                }
            }
        }
    }
}
