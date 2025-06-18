<?php
(new Page())
	->get('/sitemap.xml', function($data) {

		$host = SERVER('HTTP_HOST');
		$sitemap = \main\SitemapLib::getSitemap($host);

		throw new DataAction($sitemap, 'text/xml');

	});

?>
