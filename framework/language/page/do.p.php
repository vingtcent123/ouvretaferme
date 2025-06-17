<?php
/**
 * Import messages
 * php lime.php -a [package] language/import package=main langs=ru_RU
 *
 * @return Action
 */
(new Page(function($data) {

	$app = GET('app', 'string', LIME_APP);
	$package = GET('package');

	$package = new ReflectionPackage($package, $app);

	if($package->exists() === FALSE) {
		throw new LineAction("Error: Package '".$package->getPackageName()."' does not exist.");
	}

	$data->package = $package;

	$langs = GET('langs');
	$langSource = GET('langSource', 'string', L::getDefaultLang());

	if($langs === '') {
		throw new LineAction("Error: Missing selected languages langs=?");
	} else if($langs === '*') {
		$langs = $data->package->getLangs();
	} else {

		$langs = explode(',', $langs);

		array_map(function($lang) {

			if(!preg_match('/^[a-z]{2}\\_[A-Z]{2}$/', $lang)) {
				throw new LineAction("Error: Language '".$lang."' is invalid.");
			}

		}, $langs);

	}

	if(!preg_match('/^[a-z]{2}\\_[A-Z]{2}$/', $langSource)) {
		throw new LineAction("Error: Source language '".$langSource."' is invalid.");
	}

	$data->langSource = $langSource;
	$data->langs = array_diff($langs, [$langSource]);

}))
	->cli('import', function($data) {

		// Create a new instance of the import and csv packages
		$libImport = new language\ImportLib($data->package);
		$libCsv = new language\CsvLib($data->package);

		$stats = [];
		$errors = [];
		$warnings = [];

		foreach($data->langs as $lang) {

			try {

				$messagesByPath = $libCsv->load($lang);

				$libImport->import($data->langSource, $lang, $messagesByPath);

			}
			catch(Exception $e) {

				throw new JsonAction([
					 'exception' => $e->getMessage(),
				]);

			}

			$stats[$lang] = $libImport->getStats();
			$errors[$lang] = $libImport->getErrors();
			$warnings[$lang] = $libImport->getWarnings();

		}

		throw new JsonAction([
			 'warnings' => $warnings,
			 'errors' => $errors,
			 'stats' => $stats
		]);

	})
	->cli('export', function($data) {

		// Create a new instance of the export and csv packages
		$libExport = new language\ExportLib($data->package);
		$libCsv = new language\CsvLib($data->package);

		$stats = [];

		foreach($data->langs as $lang) {

			try {

				$export = $libExport->export($data->langSource, $lang);

			}
			catch(Exception $e) {

				throw new JsonAction([
					'exception' => $e->getMessage(),
				]);

			}

			$csv = $libCsv->create($lang, $export);
			$libCsv->save($lang, $csv);

			$stats[$lang] = $libExport->getStats();

		}

		throw new JsonAction($stats);

	});
?>
