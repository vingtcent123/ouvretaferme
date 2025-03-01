<?php
new Page()
	->get('/robots.txt', function($data) {

		$data = 'User-agent: *'."\n";

		if(OTF_DEMO) {
			$data .= 'Disallow: /'."\n";
		} else {
			$data .= 'Disallow: '."\n";
		}

		throw new DataAction($data, 'text/txt');

	});
?>
