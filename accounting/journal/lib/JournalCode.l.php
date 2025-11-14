<?php
namespace journal;

Class JournalCodeLib extends JournalCodeCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'code', 'color', 'isExtournable'];
	}

	public static function getAll(): \Collection {

		return JournalCode::model()
			->select(JournalCode::getSelection())
			->getCollection();

	}
	public static function getByCode(string $code): JournalCode {

		return JournalCode::model()
			->select(JournalCode::getSelection())
			->whereCode()
			->get();

	}

}
