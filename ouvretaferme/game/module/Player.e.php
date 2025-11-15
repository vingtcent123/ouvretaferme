<?php
namespace game;

class Player extends PlayerElement {

	public function getStartTime(): int {

		$this->expects(['user']);

		return \farm\FarmLib::getByUser($this['user'])->contains(fn($eFarm) => $eFarm['membership']) ? GameSetting::TIME_DAY_PREMIUM : GameSetting::TIME_DAY;

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