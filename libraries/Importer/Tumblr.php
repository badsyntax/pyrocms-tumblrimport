<?php

class Tumblr extends Importer
{
	public function import_posts()
	{
		// Try load the remote post XML feed
		$feed = $this->load_feed($this->feeds['posts']);

		$posts = (array) $feed->posts;
		$posts = $posts['post'];

		if (!$posts)
		{	
			$this->ci->session->set_flashdata('error', 'No posts were found!');
			redirect('admin/tumblrimport');
		}
		
		// Try save the posts
		return $this->save_posts($posts, $this->ci->input->post('status'));
	}

	public function import_pages()
	{
		// Try load the remote pages XML feed
		$feed = (array) $this->load_feed($this->feeds['pages']);
		$pages = $feed['pages']['page'];

		if (!$pages)
		{
			return;
		}

		// Try save the pages
		$result = $this->save_pages($pages, $this->ci->input->post('status'));
	}

	public function load_feed($feed_url = NULL)
	{
		if (!$xml = @file_get_contents($feed_url))
		{
			$this->ci->session->set_flashdata('error', sprintf('Error loading XML feed at location: %s. Have you entered the URL correctly?', $feed_url));
			redirect('admin/tumblrimport');
		}

		if (!$data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA) )
		{
			$this->ci->session->set_flashdata('error', 'Error parsing the XML feed.');
			redirect('admin/tumblrimport');
		}

		return $data;
	}

	public function save_pages($pages = array(), $statues = 'draft')
	{
		$saved = $duplicates = $redirects = 0;

		foreach($pages as $page)
		{
			die(print_r($page));
			$title = $page['@attributes']['title'];
	
			die($title);
			$this->ci->pages_m->create(array(
				'parent_id' => 0,
				'title' => '',
				'slug' => '',
				'status' => $status,
				'navigation_group_id' => 0,
				'meta_title' => '',
				'meta_keywords' => '',
				'meta_description' => '',
				'layout_id' => 1,
				'css' => '',
				'js' => '',
				'use_revision_id' => '',
				'compare_revision_1' => '',
				'compare_revision_2' => '',
			));
		}
	}

	public function save_page($input) { }

	public function save_posts($posts = array(), $status = 'draft')
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
			
			$post = $this->ci->db->get_where('blog', array('title' => $title))->result_array();
			if (count($post))
			{
				$duplicates++;

				continue;
			}

			$tags = (array) @$data['tag'];

			$category_id = $tags ? $this->save_post_tags($tags) : 0;
		
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
				if ($this->ci->input->post('redirects'))
				{
					$redirects += $this->save_redirects($result, $data);
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

	public function save_post($data = array())
	{
		// Try inset a new blog post
		$id = $this->ci->blog_m->insert($data);

		if ($id)
		{
			$this->ci->cache->delete_all('ci->blog_m');
			return $id;
		}
		return false;
	}

	public function save_post_tags($tags = array())
	{
		// Use the first tag for the category.
		// (PyroCMS does not support multiple categories.)
		$category = $tags[0];

		// Save the category		
		if (!$this->ci->blog_categories_m->check_title($category))
		{	
			$this->ci->blog_categories_m->insert(array('title' => $category));
		}

		// Get the category ID from the db
		$category_db = current($this->ci->db->get_where('blog_categories', array('slug' => url_title($category)))->result_array());

		return $category_db['id'];
	}

	public function save_redirects($id = 0, $data = array())
	{
		$db_post = $this->ci->blog_m->get($id);

		// URL of the blog post: FIXME for pages
		$redirect_to = 'blog/'.date('Y/m', $db_post->created_on).'/'.$db_post->slug;

		$total = 0;

		if (isset($data['@attributes']['url']))
		{
			$redirect = array(
				'from' => str_replace($this->blog_url.'/', '', $data['@attributes']['url']),
				'to' => $redirect_to
			);
			if  (!count($this->ci->db->where(array('from' => $redirect['from']))->get('redirects')->row()))
			{
				$this->ci->redirect_m->insert($redirect);
				$total++;
			}
		}
		if (isset($data['@attributes']['url-with-slug']))
		{
			$redirect = array(
				'from' => str_replace($this->blog_url.'/', '', $data['@attributes']['url-with-slug']),
				'to' => $redirect_to
			);
			if  (!count($this->ci->db->where(array('from' => $redirect['from']))->get('redirects')->row()))
			{
				$this->ci->redirect_m->insert($redirect);
				$total++;
			}
		}

		return $total;
	}
}
