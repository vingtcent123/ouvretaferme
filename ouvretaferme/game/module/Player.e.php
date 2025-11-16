<?php
namespace game;

class Player extends PlayerElement {

	public static function isPremium(\user\User $eUser): bool {
		return \farm\FarmLib::getByUser($eUser)->contains(fn($eFarm) => $eFarm['membership']);
	}

	public static function getDailyTime(\user\User $eUser): int {

		return self::isPremium($eUser) ? GameSetting::TIME_DAY_PREMIUM : GameSetting::TIME_DAY;

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('farm.check', function(\farm\Farm $eFarm): bool {

				$this->expects(['cFarm']);

				return (
					$eFarm->empty() or
					$this['cFarm']->offsetExists($eFarm['id'])
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>