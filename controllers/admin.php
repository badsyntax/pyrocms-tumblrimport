<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * The Tumblr Import module will import posts, pages and tags 
 * from a tubmlr blog XML feed into PyroCMS.
 *
 * @author	Richard Willis
 * @package	PyroCMS
 * @subpackage	TumblrImport
 * @category	Modules
 * @license	Apache License v2.0
 */
class Admin extends Admin_Controller
{
	 /**
	 * Validation rules
	 *
	 * @var array
	 * @access private
	 */
	private $validation_rules = array(
		array(
			'field' => 'blog_url',
			'label' => 'Tumblr blog URL',
			'rules' => 'trim|max_length[255]|required|callback__check_url'
		),
		array(
			'field' => 'status',
			'label' => 'lang:blog_status_label',
			'rules' => 'trim|alpha|required'
		),
		array(
			'field' => 'redirects',
			'label' => 'Add redirects',
			'rules' => 'trim|numeric|required'
		),
		array(
			'field' => 'posts',
			'label' => 'Import posts',
			'rules' => 'trim|numeric|required'
		),
		array(
			'field' => 'categories',
			'label' => 'Import tags as categories',
			'rules' => 'trim|numeric|required'
		),
		array(
			'field' => 'pages',
			'label' => 'Import pages',
			'rules' => 'trim|numeric|required'
		),
	);
	
	/**
	 * Constructor method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('html');
		$this->load->model('blog/blog_categories_m');
		$this->load->model('blog/blog_m');
		$this->load->model('redirects/redirect_m');
		$this->load->model('pages/pages_m');
		$this->load->library('form_validation');
		$this->lang->load('blog/blog');
		$this->lang->load('redirects/redirects');
		$this->config->load('tumblrimport_config');
		$this->template->set_partial('shortcuts', 'admin/partials/shortcuts');
	}

	/**
	 * Validation callback method that checks the format of the feed URL
	 * @access public
	 * @param string url The URL to check
	 * @return bool
	 */
	public function _check_url(&$url)
	{
		if (!preg_match('/^https?:\/\/.+/', $url))
		{
			$this->form_validation->set_message('_check_url', 'Invalid blog URL.');

			return FALSE;
		}

		// Strip end slashes
		$url = preg_replace('/[\/]+$/', '', $url);

		return true;
	}

	/**
	 * @access public
	 * @return void
	 */
	public function index()
	{
		$this->form_validation->set_rules($this->validation_rules);

		if ($this->form_validation->run())
		{
			require_once './addons/modules/tumblrimport/libraries/Importer.php';
			require_once './addons/modules/tumblrimport/libraries/Importer/Tumblr.php';
		
			$driver = 'Tumblr';

			$blog_url = $this->input->post('blog_url');
			$importer_feeds = $this->config->item('tumblrimport_feeds');

			$driver_feeds = array();
			foreach($importer_feeds[strtolower($driver)] as $type => $feed)
			{
				$driver_feeds[$type] = sprintf($feed, $blog_url);
			}

			$importer = new $driver($blog_url, $driver_feeds);

			$result = $importer->import_posts();
			
			$msg = array(
				'saved' => sprintf('%s posts saved.', $result['saved']),
				'skipped' => $result['skipped'] > 0 
					? sprintf('%s skipped posts.', $result['skipped']) 
					: '',
				'duplicates' => $result['dupes'] > 0 
					? sprintf('%s duplicates not saved.', $result['dupes']) 
					: '',
				'redirects' => $result['redirects'] > 0 
					? sprintf('%s redirects saved.', $result['redirects']) 
					: ''
			);

			if (!!$this->input->post('pages') === TRUE)
			{
				$importer->import_pages();
			}

			$flashmsg = sprintf('%s %s %s %s', 
				$msg['saved'], 
				$msg['skipped'], 
				$msg['duplicates'], 
				$msg['redirects']
			);

			$this->session->set_flashdata($result['saved'] > 0 ? 'success' : 'error', $flashmsg);

			redirect('admin/tumblrimport');
		}

		// Required for validation
		foreach ($this->validation_rules as $rule)
		{
			$data->{$rule['field']} = $this->input->post($rule['field']);
		}

		// Default form values
		$this->_default($data->blog_url, 'http://', FALSE);
		$this->_default($data->status, 'draft', FALSE);
		$this->_default($data->redirects, '1', FALSE);
		$this->_default($data->posts, '1', FALSE);
		$this->_default($data->pages, '1', FALSE);
		$this->_default($data->categories, '1', FALSE);

		// Load the view
		$this->template
			->title($this->module_details['name'])
			->set('data', $data)
			->build('admin/index');
	}

	private function _default(&$var, $val, $default)
	{
		if ($val === $default)
		{
			$var = $val;
		}
	}
	
}
