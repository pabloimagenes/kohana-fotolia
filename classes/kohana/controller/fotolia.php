<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Controller_Fotolia extends Controller {
	
	public function action_preview()
	{
		$fotolia = Fotolia::instance();
		$preview = $fotolia->getMediaComp($this->request->param('id'));
		$this->response->headers('Content-Type', 'image/jpeg');
		$this->response->body($preview);
	}
	
}
