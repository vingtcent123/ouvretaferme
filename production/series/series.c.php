<?php
namespace series;

class SeriesSetting extends \Settings {

	const MISSING_WEEKS = 8;
	const DUPLICATE_LIMIT = 10;
	const DUPLICATE_INTERVAL = ['min' => -52, 'max' => 52];

}

SeriesSetting::setPrivilege('admin', FALSE);

?>
