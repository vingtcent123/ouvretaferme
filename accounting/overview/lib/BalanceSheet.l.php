<?php
namespace overview;

/**
 * Traitements relatifs au Bilan Comptable
 */
Class BalanceSheetLib {

	const VIEW_BASIC = 'basic';
	const VIEW_DETAILED = 'detailed';

	public static function getData(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison, bool $isDetailed): array {

		// On récupère toutes les entrées sur 3 chiffres
		$cOperation = self::applyFinancialYearsCondition($eFinancialYear, $eFinancialYearComparison)
			->select([
				'financialYear',
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)'),
				// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float')
			])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::THIRD_PARTY_DEPRECIATION_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::FINANCIAL_DEPRECIATION_CLASS.'%"'))
			->group(['class', 'financialYear'])
			->having('amount != 0.0')
			->sort(['class' => SORT_ASC])
			->getCollection()
			// On récupère également toutes les dépréciations, amortissements et provisions
			->mergeCollection(self::applyFinancialYearsCondition($eFinancialYear, $eFinancialYearComparison)
				->select([
					'financialYear',
					'class' => new \Sql('SUBSTRING(accountLabel, 1, 4)'),
					// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
					'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float')
				])
				->or(
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS.'%"')),
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS.'%"')),
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::THIRD_PARTY_DEPRECIATION_CLASS.'%"')),
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::FINANCIAL_DEPRECIATION_CLASS.'%"')),
				)
				->group(['class', 'financialYear'])
				->having('amount != 0.0')
				->sort(['class' => SORT_ASC])
				->getCollection()
			);

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
			'fixedAssets' => [
				'currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0,
				'comparisonBrut' => 0, 'comparisonDepreciation' => 0, 'comparisonNet' => 0,
			],
			'currentAssets' => [
				'currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0,
				'comparisonBrut' => 0, 'comparisonDepreciation' => 0, 'comparisonNet' => 0,
			],
			'equity' => [
				'currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0,
				'comparisonBrut' => 0, 'comparisonDepreciation' => 0, 'comparisonNet' => 0,
			],
			'debts' => [
				'currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0,
				'comparisonBrut' => 0, 'comparisonDepreciation' => 0, 'comparisonNet' => 0,
			],
		];

		foreach($cOperation as $eOperation) {

			if($eOperation['amount'] === 0.0) {
				continue;
			}

			// Recherche des détails d'opération (même exercice comptable + classe de compte ou amortissement/dépréciation de cette classe)
			$operationsSubClasses = $cOperationDetail->find(function($e) use($eOperation): bool {

				$isSameClass = (mb_substr($e['class'], 0, 3) === $eOperation['class']);

				$classFromAmortizationOrDepreciation = rtrim(\account\AccountLabelLib::getClassFromAmortizationOrDepreciationClass($e['class']), '0');
				// On prend le plus petit nombre de chiffres significatifs
				$length = min(mb_strlen($classFromAmortizationOrDepreciation), mb_strlen(rtrim($eOperation['class'], '0')));

				$isAmortizationOrDepreciationClass = (
					$classFromAmortizationOrDepreciation !== "" and
					mb_substr($classFromAmortizationOrDepreciation, 0, $length) === mb_substr($eOperation['class'], 0, $length)
				);

				$isFinancialYear = $eOperation['financialYear']->is($e['financialYear']);

				return (($isSameClass or $isAmortizationOrDepreciationClass) and $isFinancialYear);
			})->getArrayCopy();

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
		if($noResult or $eFinancialYear->acceptUpdate()) {

			$result = \overview\IncomeStatementLib::computeResult($eFinancialYear);
			$class = ($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS);

			if(isset($balanceSheetData['equity'][$class]) === FALSE) {
				$balanceSheetData['equity'][$class] = [
					'class' => (string)$class,
					'currentBrut' => 0,
					'currentDepreciation' => 0,
					'currentNet' => 0,
					'comparisonBrut' => 0,
					'comparisonDepreciation' => 0,
					'comparisonNet' => 0,
				];
			}

			$balanceSheetData['equity'][$class]['currentBrut'] = $result;
			$balanceSheetData['equity'][$class]['currentNet'] = $balanceSheetData['equity'][$class]['currentBrut'] - $balanceSheetData['equity'][$class]['currentDepreciation'];
			$totals['equity']['currentBrut'] += $result;
			$totals['equity']['currentNet'] = $totals['equity']['currentBrut'] - $totals['equity']['currentDepreciation'];

		}

		if($eFinancialYearComparison->notEmpty() and $eFinancialYearComparison->canUpdate()) {
			$result = \overview\IncomeStatementLib::computeResult($eFinancialYearComparison);
			$class = ($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS);
			if(isset($balanceSheetData['equity'][$class]) === FALSE) {
				$balanceSheetData['equity'][$class] = [
					'class' => (string)$class,
					'currentBrut' => 0,
					'currentDepreciation' => 0,
					'currentNet' => 0,
					'comparisonBrut' => 0,
					'comparisonDepreciation' => 0,
					'comparisonNet' => 0,
				];
			}
			$balanceSheetData['equity'][$class]['comparisonBrut'] = $result;
			$balanceSheetData['equity'][$class]['comparisonNet'] = $balanceSheetData['equity'][$class]['comparisonBrut'] - $balanceSheetData['equity'][$class]['comparisonDepreciation'];
			$totals['equity']['comparisonBrut'] += $result;
			$totals['equity']['comparisonNet'] = $totals['equity']['comparisonBrut'] - $totals['equity']['comparisonDepreciation'];
		}

		// Recalcule les totaux
		foreach($totals as $category => $values) {

			$categoryBalance = $balanceSheetData[$category];

			foreach(['currentBrut', 'currentDepreciation', 'currentNet', 'comparisonBrut', 'comparaisonDepreciation', 'comparaisonNet'] as $key) {

				$sum = 0;
				foreach($categoryBalance as $class => $balance) {
					// Cas où c'est du détail + cas où on ne veut pas de comparaison
					if(mb_strlen((string)$class) > 3 or isset($balance[$key]) === FALSE) {
						continue;
					}
					$sum += $balance[$key];
				}
				$totals[$category][$key] = round($sum, 2);

			}
		}
		return [$balanceSheetData, $totals];
	}

	private static function affectOperation(array $operationsSubClasses, array &$balanceSheetDataCategory, \account\FinancialYear $eFinancialYear, \journal\Operation $eOperation, array &$totals): void {

		foreach($operationsSubClasses as $eOperationSub) {

			if(\account\AccountLabelLib::isAmortizationOrDepreciationClass($eOperationSub['class'])) { // On ne le visualise pas
				continue;
			}

			if(isset($balanceSheetDataCategory[$eOperationSub['class']]) === FALSE) {
				$balanceSheetDataCategory[$eOperationSub['class']] = [
					'comparisonBrut' => 0,
					'comparisonDepreciation' => 0,
					'comparisonNet' => 0,
					'currentBrut' => 0,
					'currentDepreciation' => 0,
					'currentNet' => 0,
					'class' => $eOperationSub['class'],
					'description' => $eOperationSub['description'],
				];
			}

			if($eOperationSub['financialYear']->is($eFinancialYear)) {
				$balanceSheetDataCategory[$eOperationSub['class']]['currentBrut'] = $eOperationSub['amount'];
				$balanceSheetDataCategory[$eOperationSub['class']]['currentNet'] = ($balanceSheetDataCategory[$eOperationSub['class']]['currentBrut'] - $balanceSheetDataCategory[$eOperationSub['class']]['currentDepreciation']);
			} else {
				$balanceSheetDataCategory[$eOperationSub['class']]['comparisonBrut'] = $eOperationSub['amount'];
				$balanceSheetDataCategory[$eOperationSub['class']]['comparisonNet'] = ($balanceSheetDataCategory[$eOperationSub['class']]['comparisonBrut'] - $balanceSheetDataCategory[$eOperationSub['class']]['comparisonDepreciation']);
			}

		}

		if(\account\AccountLabelLib::isAmortizationOrDepreciationClass($eOperation['class']) === FALSE and isset($balanceSheetDataCategory[$eOperation['class']]) === FALSE) {
			$balanceSheetDataCategory[$eOperation['class']] = [
				'comparisonBrut' => 0,
				'comparisonDepreciation' => 0,
				'comparisonNet' => 0,
				'currentBrut' => 0,
				'currentDepreciation' => 0,
				'currentNet' => 0,
				'current' => 0,
				'class' => $eOperation['class'],
			];
		}

		if(\account\AccountLabelLib::isAmortizationOrDepreciationClass($eOperation['class'])) {

			// Classe à 3 chiffres
			$originClass = \account\AccountLabelLib::getClassFromAmortizationOrDepreciationClass($eOperation['class']);
			$balanceSheetDataCategory[$originClass]['currentDepreciation'] += abs($eOperation['amount']);
			$balanceSheetDataCategory[$originClass]['currentNet'] = round($balanceSheetDataCategory[$originClass]['currentBrut'] - $balanceSheetDataCategory[$originClass]['currentDepreciation'], 2);

			// Classe complète (si isDetailed)
			$originClass = \account\AccountLabelLib::pad($originClass);

			// Cas où ça n'est pas créé : si les amortissements ont été regroupés et qu'on a perdu les sous-comptes
			if(isset($balanceSheetDataCategory[$originClass])) {

				$balanceSheetDataCategory[$originClass]['currentDepreciation'] += abs($eOperation['amount']);
				$balanceSheetDataCategory[$originClass]['currentNet'] = round($balanceSheetDataCategory[$originClass]['currentBrut'] - $balanceSheetDataCategory[$originClass]['currentDepreciation'], 2);
			}

		} else {

			if($eOperation['financialYear']->is($eFinancialYear)) {

				$balanceSheetDataCategory[$eOperation['class']]['currentBrut'] = $eOperation['amount'];
				$totals['currentBrut'] += $eOperation['amount'];
				$balanceSheetDataCategory[$eOperation['class']]['currentNet'] = round($balanceSheetDataCategory[$eOperation['class']]['currentBrut'] - $balanceSheetDataCategory[$eOperation['class']]['currentDepreciation'], 2);

			} else {

				$balanceSheetDataCategory[$eOperation['class']]['comparisonBrut'] = $eOperation['amount'];
				$totals['comparisonBrut'] += $eOperation['amount'];

				$balanceSheetDataCategory[$eOperation['class']]['comparisonNet'] = round($balanceSheetDataCategory[$eOperation['class']]['comparisonBrut'] - $balanceSheetDataCategory[$eOperation['class']]['comparisonDepreciation'], 2);
			}

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
