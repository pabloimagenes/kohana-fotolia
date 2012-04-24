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
	
	const THUMBNAIL_30 = 30;
	const THUMBNAIL_110 = 110;
	const THUMBNAIL_400 = 400;
	
	public static $instance = NULL;
	
	public static $method_no_cache = array('media/getMedia');
	public static $cache_lifetime = 86400;
	
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
				$this->session_token = $token->session_id;
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
		$cache_active = !in_array($method, Fotolia::$method_no_cache);
		$cache_id = print_r(func_get_args(), TRUE);
		
		if ($this->config['cache'] === FALSE || ($result = Cache::instance()->get($cache_id, NULL)) === NULL)
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
				$result = $response->body();
			}
			else
			{
				return FALSE;
			}
			
			if ($this->config['cache'])
				Cache::instance()->set($cache_id, $result, Fotolia::$cache_lifetime);
		}
		return json_decode($result);
	}
	
	public function loginUser()
	{
		return $this->make_call('user/loginUser', NULL, array('login' => $this->config['username'], 'pass' => $this->config['password']));
	}
	
	public function getMediaComp($id)
	{
		$response = $this->make_call('media/getMediaComp', array('id' => $id));
		if ($response !== FALSE)
		{
			$response = Request::factory(http_build_url($response->url, array('user' => $this->config['key'])))->execute();
			if ($response->status() == 200)
			{
				return $response->body();
			}
		}
		return FALSE;
	}
	
	public function getMediaData($id, $thumbnail_size, $language_id = NULL)
	{
		if ($language_id === NULL) $language_id = Kohana::$config->load('fotolia.language_id');
		
		$param = array(
			'id'             => $id,
			'thumbnail_size' => $thumbnail_size,
			'language_id'    => $language_id,
		);
		
		return $this->make_call('media/getMediaData', $param);
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

		$param = array();
		$param['search_parameters'] = array();
		$param['search_parameters']['cat' . $type . '_id'] = $category_id;
		$param['search_parameters']['language_id'] = $language_id;
		$param['search_parameters']['offset'] = ($page - 1) * $limit;
		$param['search_parameters']['limit'] = $limit;
		$param['search_parameters']['filters'] = array();
		$param['search_parameters']['filters']['content_type:photo'] = 1;
		$param['search_parameters']['filters']['content_type:illustration'] = 1;
		$param['search_parameters']['filters']['content_type:vector'] = 1;
		$param['search_parameters']['filters']['content_type:all'] = 0;
		
		if ($search !== NULL)
			$param['search_parameters']['words'] = $search;
		
		if (($result = $this->make_call('search/getSearchResults', $param)) !== FALSE)
			$result = Fotolia_Photo::factory($result, $language_id);
		
		return $result;
	}
	
	public function getMedia($media_id, $media_license, $save_file)
	{
		$param = array(
			'id' => $media_id,
			'license_name' => $media_license,
			'session_id' => $this->session_token(),
		);
		
		if (($result = $this->make_call('media/getMedia', $param)) !== FALSE)
		{
			$file = Request::factory($result->url)->execute();
			file_put_contents($save_file . '.' . $result->extension, $file->body());
		}
	}
	
}
