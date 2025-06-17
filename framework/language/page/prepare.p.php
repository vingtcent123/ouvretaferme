<?php
/**
 * Run prepare
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

}))
	->cli('index', function($data) {

		// Create a new instance of the prepare package
		$libPrepare = new language\PrepareLib($data->package);

		// First actions (integrity, moves...)
		$check = $libPrepare->prePrepare();

		if($check === FALSE) {
			throw new JsonAction(['errors' => $libPrepare->getErrors()]);
		}

		$libPrepare->prepare();

		throw new JsonAction([
			 'errors' => $libPrepare->getErrors(),
			 'stats' => $libPrepare->getStats()]
		);

	});
?>
