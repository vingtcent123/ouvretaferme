<?php
namespace farm;

class FarmSetting extends \Settings {

	const SEASON_BEGIN = '01-01-';

	const INVITE_DELAY = 7;

	const CATEGORIES_LIMIT = 5;

	const NEW_SEASON = 10;

	const CALENDAR_LIMIT = 20;

	public static $mainActions;

	public static $featureTime;
	public static $featureStock;

}

FarmSetting::setPrivilege('admin', FALSE);
FarmSetting::setPrivilege('access', FALSE);

?>
