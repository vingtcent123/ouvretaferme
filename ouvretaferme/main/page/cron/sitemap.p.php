<?php
/**
 * Sitemap generation
 *
 */
(new Page())
	->cron('index', function($data) {

		\main\SitemapLib::buildSitemap();

	}, interval: '0 6 * * *');
?>
