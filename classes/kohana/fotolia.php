<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Fotolia {
	
	const LANG_FRENCH = 1;
	const LANG_ENGLISH_US = 2;
	const LANG_ENGLISH_UK = 3;
	const LANG_GERMAN = 4;
	const LANG_SPANISH = 5;
	const LANG_ITALIAN = 6;
	const LANG_PORTUGUESE_PT = 7;
	const LANG_PORTUGUESE_BR = 8;
	const LANG_JAPANESE = 9;
	const LANG_POLISH = 11;
	
	const TYPE_REPRESENTATIVE = 1;
	const TYPE_CONCEPTUAL = 2;
	
	public static $instance = NULL;
	
	/**
	 * @return Fotolia
	 */
	public static function instance()
	{
		if (Fotolia::$instance === NULL)
		{
			Fotolia::$instance = new Fotolia();
		}
		return Fotolia::$instance;
	}
	
	protected $base = 'http://:key@api.fotolia.com/Rest/1/:method:query';
	protected $config;
	protected $session_token = NULL;
	
	public function session_token()
	{
		if ($this->session_token === NULL)
		{
			if (($token = $this->loginUser()) !== FALSE)
			{
				$this->session_token = $token->session_token;
			}
		}
		return $this->session_token;
	}
	
	public function __construct()
	{
		$this->config = Kohana::$config->load('fotolia');
	}
	
	protected function make_call($method, array $params = NULL, array $post = NULL)
	{		
		$url = __(
			$this->base,
			array(
				':key' => $this->config['key'],
				':method' => $method,
				':query' => URL::query($params)
			)
		);
		
		if ($post !== NULL)
		{
			$rq = Request::factory($url)->method(Request::POST)->post($post);
		}
		else
		{
			$rq = Request::factory($url)->method(Request::GET);
		}
		$response = $rq->execute();
		
		if ($response->status() == 200)
		{
			return json_decode($response->body());
		}
		else
		{
			return FALSE;
		}
	}
	
	public function loginUser()
	{
		return $this->make_call('user/loginUser', NULL, array('login' => $this->config['username'], 'pass' => $this->config['password']));
	}
	
	public function getMediaComp($id)
	{
		$cache_key = __CLASS__ . __METHOD__ . $id;
		if ($this->config['cache'] === FALSE || ($result = Kohana::cache($cache_key)) === NULL) {
			$response = $this->make_call('media/getMediaComp', array('id' => $id));
			if ($response !== FALSE)
			{
				$response = Request::factory(http_build_url($response->url, array('user' => $this->config['key'])))->execute();
				if ($response->status() == 200)
				{
					if ($this->config['cache'] === TRUE)
						Kohana::cache($cache_key, $response->body());
					return $response->body();
				}
			}
			return FALSE;
		}
		return $result;
	}
	
	public function getCategories($type, $language_id = NULL, $parent_id = NULL)
	{
		if ($language_id === NULL) $language_id = Kohana::$config->load('fotolia.language_id');
		
		$param = array('language_id' => $language_id);
		if ($parent_id !== NULL)
			$param['id'] = $parent_id;
		
		if (($result = $this->make_call('search/getCategories' . $type, $param)) !== FALSE)
			$result = Fotolia_Category::factory($result, $type, $language_id);

		return $result;
	}
	
	public function getPhotosByCategory($type, $category_id, $page = NULL, $limit = NULL, $language_id = NULL, $search = NULL)
	{	
		if ($language_id === NULL) $language_id = Kohana::$config->load('fotolia.language_id');
		if ($page === NULL) $page = 1;
		if ($limit === NULL) $limit = $this->config['per_page'];
		
		$cache_key = __CLASS__ . __METHOD__ . print_r(array($type, $category_id, $page, $limit, $language_id, $search), TRUE);
		
		if ($this->config['cache'] === FALSE || ($result = Kohana::cache($cache_key)) === NULL) {
			$param = array();
			$param['search_parameters'] = array();
			$param['search_parameters']['cat' . $type . '_id'] = $category_id;
			$param['search_parameters']['language_id'] = $language_id;
			$param['search_parameters']['offset'] = ($page - 1) * $limit;
			$param['search_parameters']['limit'] = $limit;
			if ($search !== NULL)
				$param['search_parameters']['words'] = $search;
			
			if (($result = $this->make_call('search/getSearchResults', $param)) !== FALSE)
				$result = Fotolia_Photo::factory($result, $language_id);
			
			if ($this->config['cache'] === TRUE)
				Kohana::cache($cache_key, $result);
		}
		
		return $result;
	}
	
}
