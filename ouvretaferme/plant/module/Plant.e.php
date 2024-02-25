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
			\Privilege::can('plant\admin') and
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
			\Privilege::can('plant\admin') and
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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'family.check' => function(Family $eFamily): bool {

				return (
					$eFamily->empty() or
					Family::model()->exists($eFamily)
				);

			},

		]);

	}

}
?>