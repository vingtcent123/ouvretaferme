<?php
namespace plant;

class Plant extends PlantElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'family' => ['name', 'fqn'],
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);

		if(
			\user\ConnectionLib::getOnline()->isAdmin() and
			$this['farm']->empty()
		) {
			return TRUE;
		}

		return (
			$this['farm']->empty() === FALSE and
			$this['farm']->canWrite()
		);

	}

	public function canUpdate(): bool {

		return (
			$this->isOwner() or
			$this['farm']->canManage()
		);

	}

	public function canDelete(): bool {

		return $this->isOwner();

	}

	public function isOwner(): bool {

		$this->expects(['farm', 'fqn']);

		if(
			\user\ConnectionLib::getOnline()->isAdmin() and
			$this['farm']->empty() and
			$this['fqn'] !== NULL
		) {
			return TRUE;
		}

		return (
			$this['farm']->empty() === FALSE and
			$this['farm']->canManage() and
			$this['fqn'] === NULL
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('family.check', function(Family $eFamily): bool {

				return (
					$eFamily->empty() or
					Family::model()->exists($eFamily)
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>
