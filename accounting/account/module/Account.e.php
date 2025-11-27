<?php
namespace account;

class Account extends AccountElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'vatAccount' => ['id', 'class', 'vatRate', 'description'],
			'journalCode' => \journal\JournalCode::getSelection(),
		];

	}

	public function canQuickUpdate(?string $property = NULL): bool {

		$this->expects(['custom', 'class']);

		if($this['visible'] === FALSE) {
			return FALSE;
		}

		if($property === NULL) {
			$property = POST('property');
		}

		if($property === 'description') {
			return $this['custom'] === TRUE;
		}

		if($property === 'journalCode') {
			return (mb_strlen($this['class']) > 2);
		}

		return FALSE;

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('class.size', function(string $class): bool {

				return mb_strlen($class) > 3 and mb_strlen($class) <= 8;

			})
			->setCallback('class.numeric', function(string $class): bool {

				return is_numeric($class) === TRUE;

			})
			->setCallback('class.duplicate', function(string $class): bool {

				return AccountLib::countByClass($class) === 0;


			})
			->setCallback('class.unknown', function(string $class): bool {

				return
					in_array((int)substr($class, 0, 1), [8, 9]) === FALSE
					and in_array((int)substr($class, 0, 2), [19, 39, 55, 56, 57]) === FALSE;

			})
			->setCallback('vatAccount.check', function(Account $eAccountVat): bool {

				if($eAccountVat->exists() === FALSE) {
					return TRUE;
				}

				$eAccountVatDb = AccountLib::getById($eAccountVat['id']);

				return str_starts_with($eAccountVatDb['class'], AccountSetting::VAT_CLASS);

			});

		parent::build($properties, $input, $p);

	}


}
?>
