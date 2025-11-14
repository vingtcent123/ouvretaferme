<?php
namespace journal;

class JournalCode extends JournalCodeElement {

	public function canUpdate(?string $property = NULL): bool {

		$property = POST('property');

		return $this['isCustom'] === TRUE or in_array($property, ['color']);

	}

	public function canDelete(): bool {

		return $this['isCustom'] === TRUE and Operation::model()->whereJournalCode($this)->count() === 0;

	}

	public function canQuickUpdate(?string $property = NULL): bool {

		if($property === NULL) {
			$property = POST('property');
		}

		return $this['isCustom'] === TRUE or in_array($property, ['name', 'color']);

	}
}
?>
