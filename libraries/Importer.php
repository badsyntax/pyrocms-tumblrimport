<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once './addons/modules/tumblrimport/libraries/Importer/Driver.php';
require_once './addons/modules/tumblrimport/libraries/Importer/Tumblr.php';

class Importer
{
	public static function factory($driver=NULL, $config)
	{
		$importer_feeds = &get_instance()->config->item('tumblrimport_feeds');
		$config['feeds'] = array();

		foreach($importer_feeds[strtolower($driver)] as $type => $feed)
		{
			$config['feeds'][$type] = sprintf($feed, $config['blog_url']);
		}
 
		$importer = 'Importer_Driver_'.ucfirst($driver);
		return new $importer($config);
	}
}
