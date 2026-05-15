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

if (!defined('IN_PHPBB'))
{
	exit;
}

class url_helper
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \Transliterator|null */
	protected $transliterator;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config $config
	 */
	public function __construct(\phpbb\config\config $config)
	{
		$this->config = $config;
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
				return $text ?: 'n-a';
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

		return $text;
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

		return $text ?: 'n-a';
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
	 * @return string Relative URL
	 */
	public function generate_forum_link($forum_id, $forum_name)
	{
		$forum_id = (int) $forum_id;
		$mode = (int) ($this->config['vinny_url_rewrite_mode'] ?? 1);

		if ($mode === 1 && $forum_name)
		{
			$slug = $this->clean_url($forum_name);
			return $slug . "-f{$forum_id}";
		}

		return "forum-f{$forum_id}";
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

	protected function prepare_text($text)
	{
		$text = (string) $text;
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);

		return trim($text);
	}
}
