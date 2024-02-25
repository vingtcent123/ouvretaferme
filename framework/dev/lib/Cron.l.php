<?php
namespace dev;

/**
 * Cron library
 */
class CronLib {

	/**
	 * Returns classes of cron pages for a list of packages
	 */
	public static function getClasses(array $packages): array {

		$results = [];

		foreach($packages as $package => $app) {

			$files = [];
			$directory = \Package::getPath($package).'/page/';

			if(is_dir($directory)) {
				exec('find '.$directory.' -name "*.p.php"', $files);
			} else {
				$files = [];
			}

			foreach($files as $file) {

				try {
					\Page::includeFile($file);
				} catch(\DisabledPage) {
					continue;
				}

			}

		}

		foreach(\Page::all() as $path => $pages) {

			foreach($pages as $page) {

				if($page['type'] !== 'cron') {
					continue;
				}

				$package = $page['package'];

				$uri = substr($path, strlen(\Package::getPath($package)) + 6, -6);

				if($package !== 'main') {
					$uri = $package.'/'.$uri;
				}

				if($page['name'] !== 'index') {
					$uri .= ':'.$page['name'];
				}

				$results[$package][] = [
					'uri' => $uri,
					'package' => $package,
					'page' => $page,
				];

			}

		}

		return $results;

	}

	/**
	 * Build crontab line from a page
	 */
	public static function getLine(array $page, string $uri): string {

		$interval = $page['interval'];

		$command = \Setting::get('php').' '.LIME_DIRECTORY.'/framework/lime.php -a '.LIME_APP;

		if(strpos($interval, 'permanent@') === 0) {

			$lifetime = \Setting::get('cronPermanentLifetime');

			if($lifetime < 3600) {
				$interval = '*/'.(int)($lifetime / 60).' * * * *';
			} else if($lifetime === 3600) {
				$interval = '0 * * * *';
			} else if($lifetime < 86400) {
				$interval = '0 */'.(int)($lifetime / 3600).' * * *';
			} else {
				$interval = '0 0 * * *';
			}

			return $interval.' '.$command.' '.$uri;

		} else {
			return $interval.' '.$command.' '.$uri;
		}


	}

	/**
	 * Save cron lines in the a crontab file
	 */
	public static function save(array $lines) {

		if(empty($lines)) {
			return;
		}

		$directory = \Package::getPath().\Setting::get('cronSaveDirectory');

		if(is_dir($directory) === FALSE) {
			mkdir($directory);
		}

		sort($lines);

		foreach(['dev', 'preprod', 'prod'] as $mode) {
			self::saveMode($lines, $directory, $mode);
		}

	}

	protected static function saveMode(array $lines, string $directory, string $mode) {

		$lines = array_map(function($line) use($mode) {
			return str_replace('lime.php', 'lime.php -e '.$mode, $line);
		}, $lines);

		$host = \Lime::getHost();
		$sharp = str_repeat('#', strlen($host) + 4);

		$content = $sharp."\n";
		$content .= '# '.$host.' #'."\n";
		$content .= $sharp."\n";
		$content .= "\n";
		$content .= implode("\n", $lines);

		file_put_contents($directory.'/'.$mode, $content);

	}

}
?>