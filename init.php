<?php defined('SYSPATH') or die('No direct script access.');

require Kohana::find_file('vendor', 'http_build_url');

Route::set('fotolia_preview', 'fotolia/preview/<id>')
	->defaults(array(
		'controller' => 'fotolia',
		'action'     => 'preview',
	));