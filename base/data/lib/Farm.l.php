<?php
namespace data;

Class FarmLib extends FarmCrud {

	use \Notifiable;

	public static function create(Farm $e): void {

		Farm::model()->option('add-replace')->insert($e);

	}

	public static function calculate(): void {

		$isDaily = ((int)date('H')) === DataSetting::DAILY_HOUR;

		$cData = DataLib::deferred();

		foreach($cData as $eData) {

			if($isDaily === FALSE and $eData['frequency'] === Data::DAILY) {
				continue;
			}

			self::notify('calculateFarmData', $eData);

		}


	}
}
