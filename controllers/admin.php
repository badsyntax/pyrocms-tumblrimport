<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * The Tumblr Import module will import posts, pages and tags 
 * from a tubmlr blog XML feed into PyroCMS.
 *
 * @author	Richard Willis
 * @package	PyroCMS
 * @subpackage	Gallery Module
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
			'rules' => 'trim|max_length[255]|required||callback__check_url'
		),
		array(
			'field' => 'status',
			'label' => 'lang:blog_status_label',
			'rules' => 'trim|alpha'
		),
		array(
			'field' => 'redirects',
			'label' => 'Add redirects',
			'rules' => 'trim|numeric'
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
		$this->load->library('form_validation');
		$this->lang->load('blog/blog');
                $this->lang->load('redirects/redirects');
		$this->template->set_partial('shortcuts', 'admin/partials/shortcuts');
	}

	/**
	 * Validation callback method that checks the format of the feed URL
	 * @access public
	 * @param string url The URL to check
	 * @return bool
	 */
	public function _check_url($url = '')
	{
		if (!preg_match('/^https?:\/\/.+/', $url))
		{
			$this->form_validation->set_message('_check_url', 'Invalid blog URL');
			return FALSE;
		}

		return TRUE;
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
			// Strip end slashes
			$blog_url = preg_replace('/[\/]+$/', '', $this->input->post('blog_url'));

			// Try load the remote XML feed into memory
			$posts = $this->load_posts(sprintf('%s/api/read?num=50', $blog_url));

			// Try save the posts
			$result = $this->save_posts($posts, $this->input->post('status'), $blog_url);

			// Skipped posts
			$skipped = $result['skipped'] > 0 ? sprintf('%s skipped posts.', $result['skipped']) : '';
			// Skipped duplicate posts
			$duplicates = $result['dupes'] > 0 ? sprintf('%s duplicates not saved.', $result['dupes']) : '';
			// Redrects
			$redirects = $result['redirects'] > 0 ? sprintf('%s redirects saved.', $result['redirects']) : '';
			// Combine stats
			$flashmsg = sprintf('%s posts saved. %s %s %s', $result['saved'], $skipped, $duplicates, $redirects);

			$this->session->set_flashdata($result['saved'] > 0 ? 'success' : 'error', $flashmsg);
			redirect('admin/tumblrimport');
		}

		// Required for validation
		foreach ($this->validation_rules as $rule)
		{
			$data->{$rule['field']} = $this->input->post($rule['field']);
		}

		// Default values
		if (!$data->blog_url)
		{
			$data->blog_url = 'http://';
		}
		if (!$data->status)
		{
			$data->status = 'draft';
		}
		if (!$data->redirects)
		{
			$data->redirects = '1';
		}

		// Load the view
		$this->template
			->title($this->module_details['name'])
			->set('data', $data)
			->build('admin/index');
	}

	private function load_posts($feed_url = NULL)
	{
		if (!$xml = @file_get_contents($feed_url))
		{
			$this->session->set_flashdata('error', sprintf('Error loading XML feed at location: %s. Have you entered the URL correctly?', $feed_url));
			redirect('admin/tumblrimport');
		}

		if (!$xml_array = (array) @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA) )
		{
			$this->session->set_flashdata('error', 'Error parsing the XML feed.');
			redirect('admin/tumblrimport');
		}

		$posts = (array) $xml_array['posts'];
			
		if (!$posts)
		{
			$this->session->set_flashdata('error', 'No posts were found!');
			redirect('admin/tumblrimport');
		}

		return $posts['post'];
	}

	private function save_posts($posts = array(), $status = 'draft', $blog_url = '')
	{
		$saved = $duplicates = $skipped = $redirects = 0;

		foreach($posts as $data)
		{
			$data = (array) $data;

			$title = NULL;

			// Quote post
			if (isset($data['quote-text']))
			{
				$title = substr($data['quote-text'], 0, 128);
				$intro = $body = $data['quote-text'].' - '.$data['quote-source'];
			}
			// Text quote
			else if (isset($data['regular-title']))
			{
				$title = $data['regular-title'];
				$intro = $body = $data['regular-body'];
			}
			// Link post
			else if (isset($data['link-text']))
			{
				$title = $data['link-text'];
				$intro = $body = anchor($data['link-url'], $data['link-url']).' '.@$data['link-description'];
			}
			// Chat post
			else if (isset($data['conversation-title']))
			{
				$title = $data['conversation-title'];
				$intro = $body = $data['conversation-text'];
			}
			// TODO: Photo post
			else if (isset($data['photo-url']))
			{
			}

			if ($title === NULL)
			{
				$skipped++;

				continue;
			}
			
			$post = $this->db->get_where('blog', array('title' => $title))->result_array();
			if (count($post))
			{
				$duplicates++;

				continue;
			}

			$tags = (array) @$data['tag'];

			$category_id = $tags ? $this->save_tags($tags) : 0;
		
			$result = $this->save_post(array(
				'title' => $title,
				'slug' => $data['@attributes']['slug'],
				'category_id' => $category_id,
				'intro' => $intro,
				'body' => $body,
				'status' => $status,
				'created_on' => $data['@attributes']['unix-timestamp']
			));

			if ($result)
			{
				if ($this->input->post('redirects'))
				{
					$redirects += $this->save_redirects($result, $data, $blog_url);
				}
				$saved += (int) !!$result;
			}
		}

		return array(
			'saved' => $saved,
			'dupes' => $duplicates,
			'skipped' => $skipped,
			'redirects' => $redirects
		);
	}

	private function save_tags($tags = array())
	{
		// Use the first tag for the category.
		// (PyroCMS does not support multiple categories.)
		$category = $tags[0];

		// Save the category		
		if (!$this->blog_categories_m->check_title($category))
		{	
			$this->blog_categories_m->insert(array('title' => $category));
		}

		// Get the category ID from the db
		$category_db = current($this->db->get_where('blog_categories', array('slug' => url_title($category)))->result_array());

		return $category_db['id'];
	}

	private function save_redirects($id = 0, $data = array(), $blog_url = '')
	{
		$db_post = $this->blog_m->get($id);

		$redirect_to = 'blog/'.date('Y/m', $db_post->created_on).'/'.$db_post->slug;

		$total = 0;

		if (isset($data['@attributes']['url']))
		{
			$redirect = array(
				'from' => str_replace($blog_url.'/', '', $data['@attributes']['url']),
				'to' => $redirect_to
			);
			if  (!count($this->db->where(array('from' => $redirect['from']))->get('redirects')->row()))
			{
				$this->redirect_m->insert($redirect);
				$total++;
			}
		}
		if (isset($data['@attributes']['url-with-slug']))
		{
			$redirect = array(
				'from' => str_replace($blog_url.'/', '', $data['@attributes']['url-with-slug']),
				'to' => $redirect_to
			);
			if  (!count($this->db->where(array('from' => $redirect['from']))->get('redirects')->row()))
			{
				$this->redirect_m->insert($redirect);
				$total++;
			}
		}

		return $total;
	}

	private function save_post($data=array())
	{
		// Try inset a new blog post
		$id = $this->blog_m->insert($data);

		if ($id)
		{
			$this->cache->delete_all('blog_m');
			return $id;
		}
		return false;
	}
}
