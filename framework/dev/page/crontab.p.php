<?php

/**
 * Generate a crontab file for the current package using Package::getList() dependencies
 */
(new Page())
	->cli('index', function($data) {

		$libCron = new \dev\CronLib();

		$packages = Package::getList();

		$results = $libCron->getClasses($packages);
		$lines = [];

		foreach($results as $package => $classes) {

			echo '- '.$package.' -'."\n";

			foreach($classes as $info) {

				echo $info['uri'].': ';

				$lines[] = $libCron->getLine($info['page'], $info['uri']);

				echo 'OK'."\n";

			}

		}

		// Save crontab
		$libCron->save($lines);

	});
?>
