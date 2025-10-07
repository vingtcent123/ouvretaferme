<?php
namespace journal;

/**
 * Traitements relatifs à la Balance Comptable
 */
Class TrialBalanceLib {

	public static function extractByAccounts(\Search $search, \account\FinancialYear $eFinancialYear): array {

		// Filtres
		$startDate = $eFinancialYear['startDate'];
		if($search->get('startDate') !== '' and $search->get('startDate') >= $startDate) {
			$startDate = $search->get('startDate');
		}
		$endDate = $eFinancialYear['endDate'];
		if($search->get('endDate') !== '' and $search->get('endDate') <= $endDate) {
			$endDate = $search->get('endDate');
		}

		if($search->get('precision') === '') {
			$precision = 3;
		} else if($search->get('precision') > 8) {
			$precision = 8;
		} else if($search->get('precision') < 2) {
			$precision = 2;
		} else {
			$precision = $search->get('precision');
		}

		// Récupération des opérations
		$cOperation = \journal\Operation::model()
			->select([
				'label' => new \Sql('SUBSTRING(accountLabel, 1, '.$precision.')'),
				'debit' => new \Sql('SUM(IF(type = "'.\journal\Operation::DEBIT.'", amount, 0))', 'float'),
				'credit' => new \Sql('SUM(IF(type = "'.\journal\Operation::CREDIT.'", amount, 0))', 'float'),
			])
			->whereDate('BETWEEN', new \Sql(Operation::model()->format($startDate).' AND '.Operation::model()->format($endDate)))
			->having('debit != 0 OR credit != 0')
			->group('label')
			->sort(['label' => SORT_ASC])
			->getCollection();

		// Récupération des comptes
		$cAccountAll = \account\Account::model()
			->select(\account\Account::getSelection())
			->getCollection(NULL, NULL, 'class');

		$data = [];

		// Formattage en récupérant le compte qui correspond au mieux
		foreach($cOperation as $eOperation) {

			$account = NULL;
			$labelTmp = $eOperation['label'];

			for($i = 0; $i < mb_strlen($eOperation['label']) - 1; $i++) {

				if($cAccountAll->offsetExists($labelTmp)) {
					$account = $labelTmp;
					$label = $cAccountAll[$account]['description'];
					break;
				}

				$labelTmp = mb_substr($labelTmp, 0, mb_strlen($labelTmp) - 1);

			}
			$data[$account] = [
				'account' => $account,
				'accountDetail' => mb_strlen($account) < $precision ? ' ('.$eOperation['label'].')' : '',
				'debit' => $eOperation['debit'],
				'credit' => $eOperation['credit'],
				'label' => $label,
			];

		}

		return $data;
	}

}
