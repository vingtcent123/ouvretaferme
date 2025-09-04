<?php
namespace language;

class LanguageSetting extends \Settings {

	// Directory used to import translated messages
	const DIRECTORY_IMPORT = '/lang/import/';

	// Directory used to export messages to translate
	const DIRECTORY_EXPORT = 'lang/export/';

	// HTML tags allowed in messages
	const ALLOWED_TAGS = ['b', 'i', 'em', 's', 'u', 'small', 'big', 'strong', 'p', 'br', 'ul', 'li'];

	// Registered custom methods
	const CUSTOM_METHODS = [
		'gender' => ['Male', 'Female']
	];

}

?>
