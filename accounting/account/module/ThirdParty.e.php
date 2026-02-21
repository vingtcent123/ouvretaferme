<?php
namespace account;

class ThirdParty extends ThirdPartyElement {

	public static function getSelection(): array {

		return parent::getSelection() + ['customer' => \selling\Customer::getSelection()];

	}

	public function acceptDelete(): bool {

		$this->expects(['operations', 'accounts']);
		return (
			$this->exists() === TRUE and
			$this['operations'] === 0 and
			$this['accounts'] === 0
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.duplicate', function(?string $name) use($p): bool {

				if($p->for === 'update') {
					return TRUE;
				}

				return (ThirdParty::model()->whereName($name)->count() === 0);

			});

		parent::build($properties, $input, $p);

	}

}
?>
