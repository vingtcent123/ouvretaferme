<?php
namespace journal;

/**
 * Traitements relatifs à la Balance Comptable
 */
Class TrialBalanceLib {

	public static function extractByAccounts(\Search $search, \account\FinancialYear $eFinancialYear): array {

		$startDate = $eFinancialYear['startDate'];
		if($search->get('startDate') !== '' and $search->get('startDate') > $startDate) {
			$startDate = $search->get('startDate');
		}
		$endDate = $eFinancialYear['endDate'];
		if($search->get('endDate') !== '' and $search->get('endDate') < $endDate) {
			$endDate = $search->get('endDate');
		}

		if($search->get('precision') === '') {
			$precision = 3;
		} else if($search->get('precision') > 5) {
			$precision = 5;
		} else if($search->get('precision') < 1) {
			$precision = 1;
		} else {
			$precision = $search->get('precision');
		}

		$cOperation = \journal\Operation::model()
			->select([
				'label' => new \Sql('SUBSTRING(accountLabel, 1, '.$precision.')'),
				'debit' => new \Sql('SUM(IF(type = "'.\journal\Operation::DEBIT.'", amount, 0))'),
				'credit' => new \Sql('SUM(IF(type = "'.\journal\Operation::CREDIT.'", amount, 0))'),
			])
			->whereDate('BETWEEN', new \Sql(Operation::model()->format($startDate).' AND '.Operation::model()->format($endDate)))
			->having('debit != 0 OR credit != 0')
			->group('label')
			->sort(['label' => SORT_ASC])
			->getCollection();

		$cAccountPrecision = \account\Account::model()
			->select(\account\Account::getSelection())
			->whereClass('IN', $cOperation->getColumn('label'))
			->getCollection(NULL, NULL, 'class');

		$cAccountAll = \account\Account::model()
			->select(\account\Account::getSelection())
			->getCollection(NULL, NULL, 'class');

		$data = [];
		foreach($cOperation as $eOperation) {
			if($cAccountPrecision->offsetExists($eOperation['label'])) {
				$account = $eOperation['label'];
				$label = $cAccountPrecision[$eOperation['label']]['description'];
			} else {
				$account = NULL;
				$labelTmp = $eOperation['label'];
				while($account === NULL) {
					$labelTmp = mb_substr($labelTmp, 0, mb_strlen($labelTmp) - 1);
					if($cAccountAll->offsetExists($labelTmp)) {
						$account = $labelTmp;
						$label = $cAccountAll[$account]['description'];
					}
				}
			}
			$data[] = [
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
