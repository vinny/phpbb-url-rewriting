<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\helper;

class url_helper
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface|null */
	protected $db;

	/** @var array|null */
	protected $forum_slugs;

	/** @var \Transliterator|null */
	protected $transliterator;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config $config
	 * @param \phpbb\db\driver\driver_interface|null $db
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db = null)
	{
		$this->config = $config;
		$this->db = $db;
	}

	/**
	 * Transliterate a string to ASCII (remove accents, etc.)
	 *
	 * @param string $text
	 * @return string
	 */
	public function transliterate($text)
	{
		$text = $this->prepare_text($text);

		if (empty($text))
		{
			return 'n-a';
		}

		if (class_exists('Transliterator'))
		{
			if ($this->transliterator === null)
			{
				$this->transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
			}

			if ($this->transliterator)
			{
				$transliterated = $this->transliterator->transliterate($text);

				if ($transliterated === false)
				{
					$transliterated = '';
				}

				$text = $transliterated;
				$text = preg_replace('/[^a-z0-9]+/', '-', $text);
				$text = trim($text, '-');
				return $this->filter_short_words($text ?: 'n-a');
			}
		}

		// Fallback: simple replacement map for common chars
		$start = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
		$end   = 'aaaaaceeeeiiiinooooouuuuyyaaaaaceeeeiiiinooooouuuuy';

		$text = strtr($text, array_combine(preg_split('//u', $start, -1, PREG_SPLIT_NO_EMPTY), preg_split('//u', $end, -1, PREG_SPLIT_NO_EMPTY)));

		// Lowercase
		$text = strtolower($text);

		// Remove unwanted characters (keep letters, numbers, dashes)
		$text = preg_replace('/[^a-z0-9]+/', '-', $text);

		// Trim dashes
		$text = trim($text, '-');

		if (empty($text))
		{
			return 'n-a';
		}

		return $this->filter_short_words($text);
	}

	/**
	 * Clean a URL segment
	 *
	 * @param string $text
	 * @return string
	 */
	public function clean_url($text)
	{
		if (!empty($this->config['vinny_url_translit_enable']))
		{
			return $this->transliterate($text);
		}

		$text = $this->prepare_text($text);

		if ($text === '')
		{
			return 'n-a';
		}

		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9]+/', '-', $text);
		$text = trim($text, '-');

		return $this->filter_short_words($text ?: 'n-a');
	}

	/**
	 * Generate friendly topic URL
	 *
	 * @param int $topic_id
	 * @param string $topic_title
	 * @return string Relative URL
	 */
	public function generate_topic_link($topic_id, $topic_title)
	{
		$topic_id = (int) $topic_id;
		$mode = (int) ($this->config['vinny_url_rewrite_mode'] ?? 1);

		if ($mode === 1 && $topic_title)
		{
			$slug = $this->clean_url($topic_title);
			return $slug . "-t{$topic_id}";
		}

		return "topic-t{$topic_id}";
	}

	/**
	 * Generate friendly forum URL
	 *
	 * @param int $forum_id
	 * @param string $forum_name
	 * @param string $custom_slug
	 * @return string Relative URL
	 */
	public function generate_forum_link($forum_id, $forum_name, $custom_slug = '')
	{
		$forum_id = (int) $forum_id;
		$mode = (int) ($this->config['vinny_url_rewrite_mode'] ?? 1);

		if ($mode === 1)
		{
			if ($custom_slug === '' && $forum_id > 0 && $this->db !== null)
			{
				$custom_slug = $this->get_forum_custom_slug($forum_id);
			}

			if ($custom_slug !== '')
			{
				$slug = $this->clean_url($custom_slug);
				return $slug . "-f{$forum_id}";
			}

			if ($forum_name)
			{
				$slug = $this->clean_url($forum_name);
				return $slug . "-f{$forum_id}";
			}
		}

		return "forum-f{$forum_id}";
	}

	/**
	 * Get custom forum slug from database if configured
	 *
	 * @param int $forum_id
	 * @return string
	 */
	public function get_forum_custom_slug($forum_id)
	{
		$forum_id = (int) $forum_id;

		if ($this->forum_slugs === null && $this->db !== null)
		{
			$this->forum_slugs = array();
			$sql = 'SELECT forum_id, vinny_url_forum_slug FROM ' . FORUMS_TABLE . " WHERE vinny_url_forum_slug <> ''";
			$result = $this->db->sql_query($sql, 600);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->forum_slugs[(int) $row['forum_id']] = (string) $row['vinny_url_forum_slug'];
			}
			$this->db->sql_freeresult($result);
		}

		return isset($this->forum_slugs[$forum_id]) ? $this->forum_slugs[$forum_id] : '';
	}

	/**
	 * Generate friendly post URL
	 *
	 * @param int $post_id
	 * @param int $topic_id
	 * @param string $topic_title
	 * @return string Relative URL with hash
	 */
	public function generate_post_link($post_id, $topic_id = 0, $topic_title = '')
	{
		$post_id = (int) $post_id;
		$topic_id = (int) $topic_id;
		$mode = (int) ($this->config['vinny_url_rewrite_mode'] ?? 1);

		if ($mode === 1 && $topic_id && $topic_title)
		{
			$slug = $this->clean_url($topic_title);
			return $slug . "-t{$topic_id}-p{$post_id}#p{$post_id}";
		}

		return "post-p{$post_id}#p{$post_id}";
	}

	/**
	 * Generate friendly member profile URL
	 *
	 * @param string $username
	 * @return string Relative URL
	 */
	public function generate_member_link($username)
	{
		return 'member/' . rawurlencode($username);
	}

	public function topic_path($topic_id, $topic_title = '')
	{
		return $this->generate_topic_link($topic_id, $topic_title);
	}

	public function forum_path($forum_id, $forum_name = '', $custom_slug = '')
	{
		return $this->generate_forum_link($forum_id, $forum_name, $custom_slug);
	}

	public function post_path($post_id, $topic_id = 0, $topic_title = '')
	{
		return $this->generate_post_link($post_id, $topic_id, $topic_title);
	}

	/**
	 * Check if URL rewriting is enabled
	 *
	 * @return bool
	 */
	public function is_enabled()
	{
		return !empty($this->config['vinny_url_rewrite_enable']);
	}

	protected function prepare_text($text)
	{
		$text = (string) $text;
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);

		return trim($text);
	}

	protected function filter_short_words($text)
	{
		$min_len = (int) ($this->config['vinny_url_min_word_length'] ?? 0);
		if ($min_len <= 1)
		{
			return $text;
		}

		$words = explode('-', $text);
		$filtered = array();

		foreach ($words as $word)
		{
			if (mb_strlen($word) >= $min_len)
			{
				$filtered[] = $word;
			}
		}

		return !empty($filtered) ? implode('-', $filtered) : $text;
	}
}
