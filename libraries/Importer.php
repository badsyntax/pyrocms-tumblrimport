<?php

abstract class Importer
{
	protected $feeds;

	protected $blog_url;

	protected $ci;

	public function __construct($blog_url = NULL, $feeds = NULL)
	{
		$this->ci = &get_instance(); 
		$this->blog_url = $blog_url;
		$this->feeds = $feeds;
	}

	abstract public function import_posts();
	abstract public function import_pages();
	abstract public function load_feed($feed_url);
	abstract public function save_pages($pages, $status, $blog_url);
	abstract public function save_page($input);
	abstract public function save_posts($posts, $status, $blog_url);
	abstract public function save_post($data);
	abstract public function save_post_tags($tags);
	abstract public function save_redirects($id, $data, $blog_url);
}
