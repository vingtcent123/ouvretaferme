<?php
// Set URLs
Lime::setUrls([
	'dev' => NULL,
	'prod' => NULL
]);

// Set apps
Lime::setApps(['lime']);

// Set asset version
if(LIME_ENV === 'prod') {
	\Asset::setVersion('1');
}

// Set current lang
// L::setLang();

// Do not save errors in the database
Feature::register('dev', [
	'errorDatabase' => FALSE
]);
?>
