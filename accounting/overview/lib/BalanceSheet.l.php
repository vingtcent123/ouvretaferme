<?php
namespace overview;

/**
 * Traitements relatifs au Bilan Comptable
 */
Class BalanceSheetLib {

	const VIEW_BASIC = 'basic';
	const VIEW_DETAILED = 'detailed';

	public static function getData(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison, bool $isDetailed): array {

		$cOperation = self::applyFinancialYearsCondition($eFinancialYear, $eFinancialYearComparison)
			->select([
				'financialYear',
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)'),
				// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float')
			])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->group(['class', 'financialYear'])
			->having('amount != 0.0')
			->sort(['class' => SORT_ASC])
			->getCollection();

		if($isDetailed) {
			$cOperationDetail = \overview\BalanceSheetLib::getDetailData(eFinancialYear: $eFinancialYear, eFinancialYearComparison: $eFinancialYearComparison);
		} else {
			$cOperationDetail = new \Collection();
		}

		$cBankAccount = \bank\BankAccountLib::getAll('label');

		$balanceSheetData = [
			'fixedAssets' => [], // actif immobilisé : 2*
			'currentAssets' => [], // circulant : 3, 4 (si débiteur), 5 (si débiteur)
			'equity' => [], // capitaux propres : 10*, 12*
			'debts' => [], // dettes : 4 (si créditeur), 5 (si créditeur)
		];

		$totals = [
			'fixedAssets' => ['current' => 0, 'comparison' => 0],
			'currentAssets' => ['current' => 0, 'comparison' => 0],
			'equity' => ['current' => 0, 'comparison' => 0],
			'debts' => ['current' => 0, 'comparison' => 0],
		];

		foreach($cOperation as $eOperation) {

			if($eOperation['amount'] === 0.0) {
				continue;
			}

			// Recherche des détails d'opération (même exercice comptable + classe de compte)
			$operationsSubClasses = $cOperationDetail->find(fn($e) => (mb_substr($e['class'], 0, 3) === $eOperation['class'] and $eOperation['financialYear']->is($e['financialYear'])))->getArrayCopy();

			$generalClass = (int)substr($eOperation['class'], 0, 1);

			switch($generalClass) {

				case \account\AccountSetting::ASSET_GENERAL_CLASS:
					self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['fixedAssets'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['fixedAssets']);
					break;

				case \account\AccountSetting::STOCK_GENERAL_CLASS:
					self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['currentAssets'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['currentAssets']);
					break;

				case \account\AccountSetting::FINANCIAL_GENERAL_CLASS:
					// On récupère la description du compte bancaire pour le retrouver facilement.
					if(mb_substr($eOperation['class'], 0, 3) === \account\AccountSetting::BANK_ACCOUNT_CLASS) {
						foreach($operationsSubClasses as &$operationSubClass) {
							if($cBankAccount->offsetExists($operationSubClass['class'])) {
								if($cBankAccount[$operationSubClass['class']]['description']) {
									$operationSubClass['description'] = $cBankAccount[$operationSubClass['class']]['description'];
								} else {
									$operationSubClass['description'] = new \bank\BankAccountUi()->getDefaultName($cBankAccount[$operationSubClass['class']]);
								}
							} else {
								$operationSubClass['description'] = new \bank\BankAccountUi()->getUnknownName();
							}
						}
					}
					// Pas de break car on utilise aussi ce qui est fait en classe 4

				case \account\AccountSetting::THIRD_PARTY_GENERAL_CLASS:
					if($eOperation['amount'] > 0) { // compte débiteur
						self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['currentAssets'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['currentAssets']);
					} else {
						$eOperation['amount'] *= -1; // compte créditeur
						foreach($operationsSubClasses as &$operationSubClass) {
							$operationSubClass['amount'] *= -1;
						}
						self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['debts'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['debts']);
					}
					break;

				case \account\AccountSetting::CAPITAL_GENERAL_CLASS:
					if((int)substr($eOperation['class'], 0, 2) < \account\AccountSetting::LOANS_CLASS) {
						self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['equity'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['equity']);
					} else { // Les emprunts + dettes + comptes de liaison partent en dettes
						foreach($operationsSubClasses as &$operationSubClass) {
							$operationSubClass['amount'] *= -1;
						}
						self::affectOperation(operationsSubClasses: $operationsSubClasses, balanceSheetDataCategory:  $balanceSheetData['debts'], eFinancialYear: $eFinancialYear, eOperation: $eOperation, totals: $totals['debts']);
					}
			}
		}

		$noResult = (isset($balanceSheetData['equity'][\account\AccountSetting::PROFIT_CLASS]) === FALSE and isset($balanceSheetData['equity'][\account\AccountSetting::LOSS_CLASS]) === FALSE);

		// On rajoute le résultat si l'exercice est encore ouvert
		if($noResult or $eFinancialYear->canUpdate()) {
			$result = \overview\IncomeStatementLib::computeResult($eFinancialYear);
			$class = ($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS);
			if(isset($balanceSheetData['equity'][$class]) === FALSE) {
				$balanceSheetData['equity'][$class] = [
					'class' => (string)$class,
					'current' => 0,
					'comparison' => 0,
				];
			}
			$balanceSheetData['equity'][$class]['current'] = $result;
			$totals['equity']['current'] = $result;
		}
		if($eFinancialYearComparison->notEmpty() and $eFinancialYearComparison->canUpdate()) {
			$result = \overview\IncomeStatementLib::computeResult($eFinancialYearComparison);
			$class = ($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS);
			if(isset($balanceSheetData['equity'][$class]) === FALSE) {
				$balanceSheetData['equity'][$class] = [
					'class' => (string)$class,
					'current' => 0,
					'comparison' => 0,
				];
			}
			$balanceSheetData['equity'][$class]['comparison'] = $result;
			$totals['equity']['comparison'] = $result;
		}

		return [$balanceSheetData, $totals];
	}

	private static function affectOperation(array $operationsSubClasses, array &$balanceSheetDataCategory, \account\FinancialYear $eFinancialYear, \journal\Operation $eOperation, array &$totals): void {

		foreach($operationsSubClasses as $eOperationSub) {
			if(isset($balanceSheetDataCategory[$eOperationSub['class']]) === FALSE) {
				$balanceSheetDataCategory[$eOperationSub['class']] = [
					'comparison' => 0,
					'current' => 0,
					'class' => $eOperationSub['class'],
					'description' => $eOperationSub['description'],
				];
			}
			if($eOperationSub['financialYear']->is($eFinancialYear)) {
				$balanceSheetDataCategory[$eOperationSub['class']]['current'] = $eOperationSub['amount'];
			} else {
				$balanceSheetDataCategory[$eOperationSub['class']]['comparison'] = $eOperationSub['amount'];
			}
		}
		if(isset($balanceSheetDataCategory[$eOperation['class']]) === FALSE) {
			$balanceSheetDataCategory[$eOperation['class']] = [
				'comparison' => 0,
				'current' => 0,
				'class' => $eOperation['class'],
			];
		}
		if($eOperation['financialYear']->is($eFinancialYear)) {
			$balanceSheetDataCategory[$eOperation['class']]['current'] = $eOperation['amount'];
			$totals['current'] += $eOperation['amount'];
		} else {
			$balanceSheetDataCategory[$eOperation['class']]['comparison'] = $eOperation['amount'];
			$totals['comparison'] += $eOperation['amount'];
		}
	}

	public static function getDetailData(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison): \Collection {

		return self::applyFinancialYearsCondition($eFinancialYear, $eFinancialYearComparison)
			->select([
				'class' => new \Sql('accountLabel'),
				// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float'),
				'description' => new \Sql('MIN(description)'),
				'financialYear',
			])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->group(['class', 'financialYear'])
			->having('amount != 0.0')
			->sort(['class' => SORT_ASC])
			->getCollection();

	}

	private static function applyFinancialYearsCondition(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison): \journal\OperationModel {

		$dateWhere = 'date BETWEEN '.\journal\Operation::model()->format($eFinancialYear['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYear['endDate']);

		if($eFinancialYearComparison->empty()) {

			return \journal\Operation::model()->where($dateWhere);

		} else {

			return \journal\Operation::model()
        ->or(
          fn() => $this->where('date BETWEEN '.\journal\Operation::model()->format($eFinancialYear['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYear['endDate'])),
          fn() => $this->where('date BETWEEN '.\journal\Operation::model()->format($eFinancialYearComparison['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYearComparison['endDate']), if: $eFinancialYearComparison->notEmpty()),
        );
		}
	}

}
