<?php
namespace journal;

Class JournalCodeLib extends JournalCodeCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'code', 'color', 'isReversable', 'isDisplayed'];
	}
	public static function getPropertiesUpdate(): array {
		return ['name', 'color', 'isReversable', 'isDisplayed'];
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

	public static function countAccountsByJournalCode(\Collection $cJournalCode): void {

		$cAccount = \account\Account::model()
			->select(['journalCode', 'number' => new \Sql('COUNT(*)', 'int')])
			->where('journalCode IS NOT NULL')
			->group('journalCode')
			->getCollection(NULL, NULL, 'journalCode');

		foreach($cJournalCode as &$eJournalCode) {
			$eJournalCode['accounts'] = $cAccount[$eJournalCode['id']]['number'] ?? 0;
		}

	}

	public static function updateAccountsForJournalCode(JournalCode $eJournalCode, array $accounts): void {

		\account\Account::model()
			->whereId('IN', $accounts)
			->update(['journalCode' => $eJournalCode]);

	}

	public static function delete(JournalCode $e): void {

		JournalCode::model()->beginTransaction();

		parent::delete($e);

		\account\Account::model()
			->whereJournalCode($e)
			->update(['journalCode' => NULL]);

		JournalCode::model()->commit();

	}

}
