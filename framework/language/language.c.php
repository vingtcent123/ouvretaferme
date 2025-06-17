<?php
Setting::register('language', [
	
	 // Directory used to import translated messages
	'directoryImport' => 'lang/import/',
	 
	 // Directory used to export messages to translate
	'directoryExport' => 'lang/export/',

	 // HTML tags allowed in messages
	'allowedTags' => ['b', 'i', 'em', 's', 'u', 'small', 'big', 'strong', 'p', 'br', 'ul', 'li'],
	
	 // Registered custom methods
	'customMethods' => [
		'gender' => ['Male', 'Female']
	],
	 
]);
?>
