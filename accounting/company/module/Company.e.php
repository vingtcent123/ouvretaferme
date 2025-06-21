<?php
namespace company;

class Company extends CompanyElement {

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	// Peut gérer l'entreprise
	public function canCreate(): bool {

		return TRUE;

	}

	public function canRemote(): bool {
		return GET('key') === \Setting::get('main\remoteKey') || LIME_ENV === 'dev';
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.empty', function(?string $name): bool {

				return $name !== NULL and mb_strlen($name) > 0;

			});
		parent::build($properties, $input, $p);

	}

}
?>
