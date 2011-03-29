<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * The Tumblr Import module will import posts and tags from a tumblr XML feed.
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
			'field' => 'categories',
			'label' => 'Import tags as categories',
			'rules' => 'trim|numeric|required'
		)
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
		$this->load->library('form_validation');
		$this->lang->load('blog/blog');
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
	 * Main request
	 * @access public
	 * @return void
	 */
	public function index()
	{
		$this->form_validation->set_rules($this->validation_rules);

		if ($this->form_validation->run())
		{
			require_once './addons/modules/tumblrimport/libraries/Importer.php';

			$config = $this->input->post();

			$result = Importer::factory('Tumblr', $config)
				->import_posts();

			if ($result === FALSE)
			{
				$this->session->set_flashdata('error', 'Error loading XML feed.');
				redirect('admin/tumblrimport');
			} 

			$flashmsg = sprintf('Saved %s of %s posts.', 
				$result['saved'],
				$result['total_posts']
			);

			$this->session->set_flashdata($result['saved'] ? 'success' : 'error', $flashmsg);
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
		$this->_default($data->categories, '1', FALSE);

		// Load the view
		$this->template
			->title($this->module_details['name'])
			->set('data', $data)
			->build('admin/index');
	}

	/**
	 * Set a default value
	 * @access private
	 * @return void
	 */
	private function _default(&$var, $val, $default)
	{
		if ($var === $default)
		{
			$var = $val;
		}
	}
	
}
