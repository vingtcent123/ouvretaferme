<?php
namespace journal;

class JournalCode extends JournalCodeElement {

	public function isCustom(): bool {

		return $this['isCustom'];

	}
	public function canUpdate(): bool {

		return TRUE;

	}

	public function canDelete(): bool {

		return $this['isCustom'] === TRUE and Operation::model()->whereJournalCode($this)->count() === 0;

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('code.check', function(string $code): bool {

				return $this['isCustom'];

			});

		parent::build($properties, $input, $p);

	}

}
?>
