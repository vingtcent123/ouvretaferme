<?php
namespace map;

class SeasonLib {

	public static function getOnline($season): int {

		$season = (int)$season;

		if($season < (int)date('Y') - 100 or $season > (int)date('Y') + 100) {
			throw new \NotExpectedAction('Invalid season');
		}

		self::setOnline($season);

		return $season;

	}

	public static function setOnline($season): void {

		\main\MainSetting::$onlineSeason = $season;

	}

	public static function whereSeason(\ModuleModel $m, ?int $season): \ModuleModel {

		if($season !== NULL) {
			$m
				->where('seasonFirst IS NULL OR seasonFirst <= '.$season)
				->where('seasonLast IS NULL OR seasonLast >= '.$season);
		}

		return $m;

	}

}
?>
