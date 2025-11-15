<?php
namespace journal;

Class JournalCodeLib extends JournalCodeCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'code', 'color', 'isExtournable'];
	}

	public static function getAll(): \Collection {

		return JournalCode::model()
			->select(JournalCode::getSelection())
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}
	public static function getByCode(string $code): JournalCode {

		return JournalCode::model()
			->select(JournalCode::getSelection())
			->whereCode($code)
			->get();

	}

}
