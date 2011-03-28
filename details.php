<?php defined('BASEPATH') or exit('No direct script access allowed');

class Module_TumblrImport extends Module {

	public $version = '1.0';

	public function info()
	{
		return array(
			'name' => array(
				'en' => 'Tumblr Import'
			),
			'description' => array(
				'en' => 'Import a tumblr blog into your PyroCMS blog.'
			),
			'frontend' => FALSE,
			'backend' => TRUE,
			'menu' => 'content'
		);
	}

	public function install()
	{
		return TRUE;
	}

	public function uninstall()
	{
		return TRUE;
	}

	public function upgrade($old_version)
	{
		return TRUE;
	}

	public function help()
	{
		// Return a string containing help info
		// You could include a file and return it here.
		return "<h4>Overview</h4>";
	}
}
/* End of file details.php */
