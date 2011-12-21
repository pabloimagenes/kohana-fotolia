<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Fotolia_Photo {
	
	public static function factory($rows, $language_id)
	{
		$result = array('count' => 0, 'photos' => array());
		foreach ($rows as $key => $row) {
			if ($key == "nb_results")
				$result['count'] = $row;
			else
				$result['photos'][] = new Fotolia_Photo($row, $language_id);
		}
		return $result;
	}
	
	public $id;
	public $title;
	public $media_type;
	public $thumbnails = array();
	public $licenses = array();
	public $html_img;
	
	protected function licenses($item, $key) {
		$this->licenses[$item->name] = $item->price;
	}
	
	public function __construct($data, $language_id)
	{
		$this->html_img = $data->thumbnail_html_tag;
		$this->id = $data->id;
		$this->title = $data->title;
		$this->media_type = $data->media_type_id;
		$this->language_id = $language_id;
		array_walk($data->licenses, array($this, "licenses"));
		$this->thumbnails = array(
			"30" => $data->thumbnail_30_url,
			"110" => $data->thumbnail_110_url,
			"400" => $data->thumbnail_400_url,
		);
	}
	
	public function __toString()
	{
		return $this->html_img;
	}
	
}
