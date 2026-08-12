<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\tests\helper;

class url_helper_test extends \phpbb_test_case
{
	/** @var \vinny\urlrewriting\helper\url_helper */
	protected $url_helper;

	/** @var \phpbb\config\config */
	protected $config;

	public function setUp(): void
	{
		parent::setUp();

		$this->config = new \phpbb\config\config(array(
			'vinny_url_rewrite_mode'   => 1,
			'vinny_url_translit_enable' => 1,
			'vinny_url_min_word_length' => 0,
		));

		$this->url_helper = new \vinny\urlrewriting\helper\url_helper($this->config);
	}

	public function test_transliterate()
	{
		$result = $this->url_helper->transliterate('Ação e Reação');
		$this->assertSame('acao-e-reacao', $result);

		$result = $this->url_helper->transliterate('Fórum de Dúvidas');
		$this->assertSame('forum-de-duvidas', $result);

		$result = $this->url_helper->transliterate('');
		$this->assertSame('n-a', $result);
	}

	public function test_clean_url_with_transliteration()
	{
		$this->config['vinny_url_translit_enable'] = 1;

		$result = $this->url_helper->clean_url('Tópico de Teste!');
		$this->assertSame('topico-de-teste', $result);
	}

	public function test_clean_url_without_transliteration()
	{
		$this->config['vinny_url_translit_enable'] = 0;

		$result = $this->url_helper->clean_url('Simple Title 123');
		$this->assertSame('simple-title-123', $result);
	}

	public function test_min_word_length()
	{
		$this->config['vinny_url_min_word_length'] = 3;

		$result = $this->url_helper->clean_url('A quick brown fox');
		$this->assertSame('quick-brown-fox', $result);
	}

	public function test_generate_topic_link_advanced_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 1;

		$link = $this->url_helper->generate_topic_link(123, 'My Topic Title');
		$this->assertSame('my-topic-title-t123', $link);
	}

	public function test_generate_topic_link_simple_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 0;

		$link = $this->url_helper->generate_topic_link(123, 'My Topic Title');
		$this->assertSame('topic-t123', $link);
	}

	public function test_generate_forum_link_advanced_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 1;

		$link = $this->url_helper->generate_forum_link(45, 'General Discussion');
		$this->assertSame('general-discussion-f45', $link);
	}

	public function test_generate_forum_link_custom_slug()
	{
		$this->config['vinny_url_rewrite_mode'] = 1;

		$link = $this->url_helper->generate_forum_link(45, 'General Discussion', 'custom-slug');
		$this->assertSame('custom-slug-f45', $link);
	}

	public function test_generate_forum_link_simple_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 0;

		$link = $this->url_helper->generate_forum_link(45, 'General Discussion');
		$this->assertSame('forum-f45', $link);
	}

	public function test_generate_post_link_advanced_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 1;

		$link = $this->url_helper->generate_post_link(678, 123, 'My Topic Title');
		$this->assertSame('my-topic-title-t123-p678#p678', $link);
	}

	public function test_generate_post_link_simple_mode()
	{
		$this->config['vinny_url_rewrite_mode'] = 0;

		$link = $this->url_helper->generate_post_link(678, 123, 'My Topic Title');
		$this->assertSame('post-p678#p678', $link);
	}

	public function test_generate_member_link()
	{
		$link = $this->url_helper->generate_member_link('John Doe');
		$this->assertSame('member/John%20Doe', $link);
	}

	public function test_helper_alias_methods()
	{
		$this->config['vinny_url_rewrite_mode'] = 1;

		$this->assertSame('my-topic-title-t123', $this->url_helper->topic_path(123, 'My Topic Title'));
		$this->assertSame('general-discussion-f45', $this->url_helper->forum_path(45, 'General Discussion'));
		$this->assertSame('my-topic-title-t123-p678#p678', $this->url_helper->post_path(678, 123, 'My Topic Title'));
	}
}
