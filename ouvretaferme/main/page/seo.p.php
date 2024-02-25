<?php
(new Page())
	->get('/robots.txt', function($data) {

		$data = 'User-agent: *'."\n";
		$data .= 'Disallow: '.Setting::get('main\robotsDisallow').''."\n";

		throw new DataAction($data, 'text/txt');

	});
?>
