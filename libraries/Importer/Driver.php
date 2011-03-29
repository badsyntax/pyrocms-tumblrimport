<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

abstract class Importer_Driver
{
	protected $ci;
	protected $config;

	abstract public function import_posts();
	abstract public function save_posts($posts);
	abstract public function save_post($data);
	abstract public function save_post_tags($tags);
	
	public function __construct($config = NULL)
	{
		$this->ci = &get_instance(); 
		$this->config = $config;
	}
}
