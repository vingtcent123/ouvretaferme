<?php
namespace main;

/**
 * Sitemap handling
 */
class SitemapLib {

	public static function buildSitemap() {

		$sitemapArray = [];

		// On rajoute le sitemap
		$sitemapArray[] = [
			'loc' => \Lime::getUrl().'/sitemap',
			'lastmod' => NULL,
			'changefreq' => 'daily',
			'priority' => '0.9'
		];

		$sitemapArray[] = [
			'loc' => \Lime::getUrl().'/presentation/producteur',
			'lastmod' => NULL,
			'changefreq' => 'daily',
			'priority' => '1'
		];

		$sitemap = [
			'sitemap' => self::generateSitemap($sitemapArray),
			'lastUpdate' => currentDatetime(),
		];

		\Cache::db()->set('sitemap', $sitemap); // no timeout

	}

	public static function generateSitemap(array $sitemapArray): string {

		$sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>';
		$sitemapXml .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

		foreach($sitemapArray as $sitemapItem) {

			$sitemapXml .= '
	<url>
		<loc>'.$sitemapItem['loc'].'</loc>';
			if(isset($sitemapArray['lastmod'])) {
				$sitemapXml .= '
		<lastmod>'.$sitemapItem['lastmod'].'</lastmod>';
			}
			$sitemapXml .= '
		<changefreq>'.$sitemapItem['changefreq'].'</changefreq>
		<priority>'.$sitemapItem['priority'].'</priority>
	</url>';
		}
		$sitemapXml .= '</urlset>';

		return $sitemapXml;

	}

	public static function getSitemap(string $host): string {

		if(\Cache::db()->exists('sitemap')) {
			return \Cache::db()->get('sitemap')['sitemap'];
		} else {
			throw new \Exception('Sitemap.xml not created yet');
		}

	}

}
?>
