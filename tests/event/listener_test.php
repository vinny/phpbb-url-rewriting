<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace vinny\urlrewriting\tests\event;

use Symfony\Component\HttpKernel\KernelEvents;

class listener_test extends \phpbb_test_case
{
	/** @var \vinny\urlrewriting\event\listener */
	protected $listener;

	protected $auth;
	protected $config;
	protected $template;
	protected $user;
	protected $url_helper;
	protected $db;
	protected $request;

	public function setUp(): void
	{
		parent::setUp();

		$this->auth = $this->getMockBuilder('\phpbb\auth\auth')
			->disableOriginalConstructor()
			->getMock();

		$this->config = new \phpbb\config\config(array(
			'vinny_url_rewrite_enable' => 1,
			'force_server_vars'        => 1,
			'server_protocol'          => 'https://',
			'server_name'              => 'example.com',
			'server_port'              => 443,
			'script_path'              => '/phpBB',
			'cookie_secure'            => 1,
		));

		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder('\phpbb\user')
			->disableOriginalConstructor()
			->getMock();
		$this->user->host = 'example.com';

		global $config, $user;
		$config = $this->config;
		$user = $this->user;

		$this->url_helper = $this->getMockBuilder('\vinny\urlrewriting\helper\url_helper')
			->disableOriginalConstructor()
			->getMock();

		$this->db = $this->getMockBuilder('\phpbb\db\driver\driver_interface')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();

		$this->listener = new \vinny\urlrewriting\event\listener(
			$this->auth,
			$this->config,
			$this->template,
			$this->user,
			$this->url_helper,
			$this->db,
			$this->request,
			'php'
		);
	}

	public function test_get_subscribed_events()
	{
		$events = \vinny\urlrewriting\event\listener::getSubscribedEvents();

		$this->assertIsArray($events);
		$this->assertArrayHasKey('core.append_sid', $events);
		$this->assertArrayHasKey('core.page_header', $events);
		$this->assertArrayHasKey('core.page_header_after', $events);
		$this->assertArrayHasKey('core.twig_environment_render_template_after', $events);
		$this->assertArrayHasKey('core.viewforum_modify_topicrow', $events);
		$this->assertArrayHasKey('core.approve_posts_after', $events);
		$this->assertArrayHasKey('core.approve_topics_after', $events);
		$this->assertArrayHasKey('core.disapprove_posts_after', $events);
		$this->assertArrayHasKey('core.feed_modify_feed_row', $events);
	}

	public function test_process_html_output_disabled()
	{
		$this->config['vinny_url_rewrite_enable'] = 0;
		$html = '<a href="viewtopic.php?t=123">Link</a>';
		$this->assertSame($html, $this->listener->process_html_output($html));
	}

	public function test_rewrite_url_with_fragment_param()
	{
		$this->url_helper->expects($this->any())
			->method('generate_post_link')
			->willReturn('topic-slug-t1-p123#p123');

		$feed_event = new \phpbb\event\data(array(
			'row' => array(
				'post_id'     => 123,
				'topic_id'    => 1,
				'topic_title' => 'Test Topic',
			),
		));
		$this->listener->rewrite_feed_row($feed_event);

		$event = new \phpbb\event\data(array(
			'url' => 'viewtopic.php',
			'params' => 'p=123#p123',
			'is_amp' => true,
			'append_sid_overwrite' => false,
		));

		$this->listener->rewrite_url($event);
		$this->assertNotEmpty($event['append_sid_overwrite']);
		$this->assertStringContainsString('topic-slug-t1-p123#p123', $event['append_sid_overwrite']);
	}
}
