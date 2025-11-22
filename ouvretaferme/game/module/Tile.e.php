<?php
namespace game;

class Tile extends TileElement {

	public static function getSelection(): array {
		return parent::getSelection() + [
			'growing' => GrowingElement::getSelection()
		];
	}

	public function canHarvest(): bool {
		return ($this['harvestedAt'] <= currentDatetime());
	}

	public function canNotHarvest(): bool {
		return ($this->canHarvest() === FALSE);
	}

	public function getHarvest(\Collection $cTile): int {

		$this->expects([
			'growing' => ['harvest', 'bonusWatering']
		]);

		$bonus = $cTile->find(fn($eTile) => in_array($eTile['tile'], GameSetting::ADJACENT[$this['tile']]) and $eTile['growing']->notEmpty() and $eTile['growing']['fqn'] === 'luzerne')->count() * GameSetting::BONUS_LUZERNE;

		return $this['growing']['harvest'] + $this['watering'] * $this['growing']['bonusWatering'] + $bonus;

	}

	public function canRead(): bool {

		$ePlayer = PlayerLib::getOnline();

		return (
			$ePlayer->notEmpty() and
			$this['user']->is($ePlayer['user'])
		);

	}

}
?>