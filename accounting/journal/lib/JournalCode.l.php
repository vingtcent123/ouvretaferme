<?php
namespace journal;

Class JournalCodeLib extends JournalCodeCrud {

	use \ModuleDeferred;

	public static function getPropertiesCreate(): array {
		return ['name', 'code', 'color', 'isReversable', 'isDisplayed'];
	}
	public static function getPropertiesUpdate(): array {
		return ['name', 'color', 'isReversable', 'isDisplayed'];
	}

	public static function deferred(): \Collection {

		$callback = fn() => JournalCode::model()
			->select(JournalCode::getSelection())
			->sort(['name' => SORT_ASC])
			->getCollection(index: 'id');

		$eFarm = \farm\Farm::getConnected();
		
		return self::getCache($eFarm['id'], $callback);

	}
	
	public static function askByCode(string $code): JournalCode {

		$cJournalCode = self::deferred();
		
		$cJournalCodeFiltered = $cJournalCode->find(fn($e) => $e['code'] === $code);
		if($cJournalCodeFiltered->notEmpty()) {
			return $cJournalCodeFiltered->first();
		}
		
		return new JournalCode();

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
