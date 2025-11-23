<?php
namespace game;

class Player extends PlayerElement {

	private static ?bool $premium = NULL;

	public function getBoards(): int {
		return 1 + (int)(currentDate() >= '2025-12-01') + (int)(currentDate() >= '2025-12-10');
	}

	public function getRole(): string {
		return ($this['user']['role']['fqn'] === 'customer') ? 'customer' : 'farmer';
	}

	public function isPremium(): bool {

		if(self::$premium === NULL) {

			self::$premium = (
				\farm\FarmLib::getByUser($this['user'])->contains(fn($eFarm) => $eFarm['membership']) or
				\association\HistoryLib::hasDonate($this['user'], date('Y'))
			);

		}

		return self::$premium;

	}

	public function getDailyTime(): int {
		return $this->isPremium() ? GameSetting::TIME_DAY_PREMIUM : GameSetting::TIME_DAY;
	}

	public function getRemainingTime(): float {
		return $this->getDailyTime() - $this['time'];
	}

	public function getHarvestTime(\Collection $cTile): float {

		$bonus = $cTile->find(fn($eTile) => $eTile['growing']->notEmpty() and $eTile['growing']['fqn'] === 'pivoine')->count() * GameSetting::BONUS_PIVOINE / 60;

		return GameSetting::TIME_HARVESTING - $bonus;

	}

	public function canTime(int $additional): bool {

		return $this->getRemainingTime() >= $additional;

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