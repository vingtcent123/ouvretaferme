<?php

(new Page(function($data) {

	$app = GET('app', 'string', LIME_APP);
	$package = GET('package');

	$package = new ReflectionPackage($package, $app);

	if($package->exists() === FALSE) {
		throw new LineAction("Error: Package '".$package->getPackageName()."' does not exist.");
	}

	$data->package = $package;

}))
	->cli('index', function($data) {

		$stats = [];

		foreach($data->package->getLangs() as $lang) {

			list($nFiles, $nMessages, $nWords) = language\StatsLib::count($data->package, $lang);

			$stats[$lang] = [
				'files' => $nFiles,
				'messages' => $nMessages,
				'words' => $nWords
			];

		}

		throw new JsonAction($stats);

	});
?>
