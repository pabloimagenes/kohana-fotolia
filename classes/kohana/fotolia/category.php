<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Fotolia_Category {
	
	public static function factory($rows, $type, $language_id)
	{
		$result = array();
		foreach ($rows as $row) {
			$result[] = new Fotolia_Category($row, $type, $language_id);
		}
		return $result;
	}
	
	public $id;
	public $name;
	public $subcategories_count;
	public $language_id;
	public $type;
	
	public function __construct($row, $type, $language_id)
	{
		$this->id = $row->id;
		$this->name = $row->name;
		$this->subcategories_count = $row->nb_sub_categories;
		$this->language_id = $language_id;
		$this->type = $type;
	}
	
	public function get_subcategories()
	{
		$result = array();
		if ($this->subcategories_count > 0)
		{
			$fotolia = Fotolia::instance();
			$result = $fotolia->getCategories($this->type, $this->language_id, $this->id);
		}
		return $result;
	}
	
	public function get_photos_list($page = NULL, $per_page = NULL)
	{
		$fotolia = Fotolia::instance();
		return $fotolia->getPhotosByCategory($this->type, $this->id, $page, $per_page, $this->language_id);
	}
	
}
