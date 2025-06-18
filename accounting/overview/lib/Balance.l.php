<?php
namespace overview;

/**
 * Dans le bilan, il y a uniquement les comptes 1, 2, 3, 4, 5.
 */
Class BalanceLib {

	public static function getAccountLabelsWithDepreciation(array $accountLabels): array {

		$accountLabelsWithDepreciation = [];
		foreach($accountLabels as $accountLabel) {
			$accountLabelsWithDepreciation[] = $accountLabel;
			$accountLabelsWithDepreciation[] = (int)\asset\AssetLib::depreciationClassByAssetClass($accountLabel);
		}

		return $accountLabelsWithDepreciation;

	}

	protected static function formatSummarizedBalanceData(array $balance, array $categories): array {

		$totalValue = 0;
		$totalAmort = 0;
		$totalNet = 0;

		$formattedData = [];

		$allLabels = new BalanceUi()->extractLabelsFromCategories($categories);

		foreach($balance as $balanceLine) {

			if(in_array((int)$balanceLine['accountPrefix'], $allLabels) === FALSE) {
				continue;
			}

			if(\asset\AssetLib::isDepreciationClass($balanceLine['accountPrefix']) === TRUE) {
				$totalAmort += $balanceLine['amount'];
			} else {
				$totalValue += $balanceLine['amount'];
			}

		}

		$totalNet += $totalValue - $totalAmort;

		foreach($categories as $subCategories) {
			$name = $subCategories['name'];
			$categories = $subCategories['categories'];

			$totalCategoryValue = 0;
			$totalCategoryAmort = 0;
			$totalCategoryNet = 0;

			foreach($categories as $categoryDetails) {

				$categoryName = $categoryDetails['name'];
				$accounts = $categoryDetails['accounts'];

				$totalSubCategoryValue = 0;
				$totalSubCategoryAmort = 0;
				$totalSubCategoryNet = 0;
				foreach($accounts as $account) {

					$value = $balance[$account]['amount'] ?? 0;
					$accountAmort = mb_substr($account, 0, 1).'8'.mb_substr($account, 1);
					$valueAmort = $balance[$accountAmort]['amount'] ?? 0;
					$net = $value + $valueAmort;

					$totalSubCategoryValue += $value;
					$totalSubCategoryAmort += $valueAmort;
					$totalSubCategoryNet += $net;

					$formattedData[] = [
						'type' => 'line',
						'label' => \accounting\AccountUi::getLabelByAccount($account),
						'value' => $value,
						'valueAmort' => $valueAmort,
						'net' => $net,
						'total' => NULL,
					];

				}

				$formattedData[] = [
					'type' => 'subcategory',
					'label' => $categoryName,
					'value' => $totalSubCategoryValue,
					'valueAmort' => $totalSubCategoryAmort,
					'net' => $totalSubCategoryNet,
					'total' => $totalNet,
				];

				$totalCategoryValue += $totalSubCategoryValue;
				$totalCategoryAmort += $totalSubCategoryAmort;
				$totalCategoryNet += $totalSubCategoryNet;
			}

			$formattedData[] = [
				'type' => 'category',
				'label' => $name,
				'value' => $totalCategoryValue,
				'valueAmort' => $totalCategoryAmort,
				'net' => $totalCategoryNet,
				'total' => $totalNet,
			];

		}

		$formattedData[] = [
			'type' => 'total',
			'label' => '',
			'value' => $totalValue,
			'valueAmort' => $totalAmort,
			'net' => $totalNet,
			'total' => $totalNet,
		];

		return $formattedData;

	}

	public static function getOpeningBalance(\accounting\FinancialYear $eFinancialYear): array {

		$eFinancialYearPrevious = \accounting\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);

		if($eFinancialYearPrevious->exists() === FALSE) {
			return [];
		}

		return self::getSummarizedBalance($eFinancialYearPrevious);
	}

	public static function getSummarizedBalance(\accounting\FinancialYear $eFinancialYear): array {

		$balanceAssetCategories = \Setting::get('accounting\balanceAssetCategories');
		$balanceLiabilityCategories = \Setting::get('accounting\balanceLiabilityCategories');

		$accountLabels = new BalanceUi()->extractLabelsFromCategories($balanceAssetCategories + $balanceLiabilityCategories);
		$accountLabelsWithDepreciation = self::getAccountLabelsWithDepreciation($accountLabels);

		[$resultTable, ] = \journal\AnalyzeLib::getResult($eFinancialYear);

		if(count($resultTable) === 0) {
			return [];
		}

		$result = array_sum(array_column($resultTable, 'credit')) - array_sum(array_column($resultTable, 'debit'));

		$where = implode('%" OR accountLabel LIKE "', $accountLabelsWithDepreciation);
		$case = '';
		foreach($accountLabelsWithDepreciation as $accountLabel) {
			$case .= ' WHEN accountLabel LIKE "'.$accountLabel.'%" THEN '.$accountLabel;
			$case .= ' WHEN accountLabel LIKE "'.$accountLabel.'%" THEN '.$accountLabel;
		}

		$cOperation = \journal\Operation::model()
			->select([
				'accountPrefix' => new \Sql('CASE '.$case.' END'),
				'amount' => new \Sql('ABS(SUM(if(type = "debit" OR accountLabel LIKE "139%", -1 * amount, amount)))', 'float')
			])
			->where('accountLabel LIKE "'.$where.'"')
			->where('date BETWEEN "'.$eFinancialYear['startDate'].'" AND "'.$eFinancialYear['endDate'].'"')
			->group('accountPrefix')
			->getCollection(NULL, NULL, 'accountPrefix');

		// Résultat en compte 120 si > 0, en compte 129 si < 0
		if($result > 0) {
			$cOperation->offsetSet(120, new \journal\Operation(['accountPrefix' => '120', 'amount' => $result]));
		} else {
			$cOperation->offsetSet(129, new \journal\Operation(['accountPrefix' => '129', 'amount' => $result]));
		}

		// We set all the amortissements to negative values.
		foreach($cOperation as &$eOperation) {
			$secondCar = mb_substr($eOperation['accountPrefix'], 1, 1);
			if($secondCar === '8' or str_starts_with($eOperation['accountPrefix'], '139')) {
				$eOperation['amount'] *= -1;
			}
		}

		$balanceData = $cOperation->getArrayCopy();

		return [
			'asset' => self::formatSummarizedBalanceData($balanceData, $balanceAssetCategories),
			'liability' => self::formatSummarizedBalanceData($balanceData, $balanceLiabilityCategories),
		];
	}

	protected static function formatDetailedBalanceData(\Collection $balanceData, \Collection $cOperationLabel, array $categories): array {

		// Compute totals
		$allLabels = new BalanceUi()->extractLabelsFromCategories($categories);

		$totalValue = 0;
		$totalAmort = 0;

		foreach($balanceData as $balanceLine) {
			foreach($allLabels as $label) {
				if(
					\asset\AssetLib::isDepreciationClass($balanceLine['accountLabel']) === TRUE
					and str_starts_with(substr($balanceLine['accountLabel'], 0, 1).substr($balanceLine['accountLabel'], 2), $label) === TRUE
				) {
					$totalAmort += $balanceLine['amount'];
					break;
				} elseif(str_starts_with($balanceLine['accountLabel'], $label) === TRUE) {
					$totalValue += $balanceLine['amount'];
					break;
				}
			}
		}

		$totalNet = $totalValue + $totalAmort;

		$formattedData = [];

		foreach($categories as $subCategories) {
			$name = $subCategories['name'];
			$categories = $subCategories['categories'];

			$totalCategoryValue = 0;
			$totalCategoryAmort = 0;
			$totalCategoryNet = 0;

			foreach($categories as $categoryDetails) {

				$categoryName = $categoryDetails['name'];
				$accounts = $categoryDetails['accounts'];

				$totalSubCategoryValue = 0;
				$totalSubCategoryAmort = 0;
				$totalSubCategoryNet = 0;

				foreach($accounts as $account) {

					$balanceLines = array_filter($balanceData->getArrayCopy(), fn($line) => str_starts_with($line['accountLabel'], (string)$account));
					$amortAccount = (int)(mb_substr($account, 0, 1).'8'.mb_substr($account, 1));
					$balanceAmortLines = array_filter($balanceData->getArrayCopy(), fn($line) => str_starts_with($line['accountLabel'], (string)$amortAccount));

					if(empty($balanceLines) === TRUE) {
						continue;
					}

					$value = 0;
					$amort = 0;
					$net = 0;

					foreach($balanceLines as $balanceLine) {

						$label = $cOperationLabel[$balanceLine['accountLabel']]['account']['description'] ?? \accounting\AccountUi::getLabelByAccount($account);

						$value += $balanceLine['amount'];
						$formattedData[] = [
							'type' => 'line',
							'accountClass' => $balanceLine['accountLabel'],
							'label' => $label,
							'value' => $balanceLine['amount'],
							'valueAmort' => 0,
							'net' => $balanceLine['amount'],
							'total' => NULL,
						];
					}
					foreach($balanceAmortLines as $balanceAmortLine) {
						$amort += $balanceAmortLine['amount'];
						$formattedData[] = [
							'type' => 'line',
							'accountClass' => $balanceAmortLine['accountLabel'],
							'label' => \accounting\AccountUi::getLabelByAccount($account),
							'value' => 0,
							'valueAmort' => $balanceAmortLine['amount'],
							'net' => $balanceAmortLine['amount'],
							'total' => NULL,
						];
					}

					$net = $value + $amort;

					if(count($balanceLines) + count($balanceAmortLines) !== 1) {

						$formattedData[] = [
							'type' => 'subcategory',
							'label' => \accounting\AccountUi::getLabelByAccount($account),
							'value' => $value,
							'valueAmort' => $amort,
							'net' => $net,
							'total' => NULL,
						];

					}

					$totalSubCategoryValue += $value;
					$totalSubCategoryAmort += $amort;
					$totalSubCategoryNet += $net;
				}

				$formattedData[] = [
					'type' => 'subcategory',
					'label' => $categoryName,
					'value' => $totalSubCategoryValue,
					'valueAmort' => $totalSubCategoryAmort,
					'net' => $totalSubCategoryNet,
					'total' => $totalNet,
				];

				$totalCategoryValue += $totalSubCategoryValue;
				$totalCategoryAmort += $totalSubCategoryAmort;
				$totalCategoryNet += $totalSubCategoryNet;
			}

			$formattedData[] = [
				'type' => 'category',
				'label' => $name,
				'value' => $totalCategoryValue,
				'valueAmort' => $totalCategoryAmort,
				'net' => $totalCategoryNet,
				'total' => $totalNet,
			];
		}

		$formattedData[] = [
			'type' => 'total',
			'label' => '',
			'value' => $totalValue,
			'valueAmort' => $totalAmort,
			'net' => $totalNet,
			'total' => $totalNet,
		];

		return $formattedData;
	}

	public static function getDetailedBalance(\accounting\FinancialYear $eFinancialYear): array {

		$balanceAssetCategories = \Setting::get('accounting\balanceAssetCategories');
		$balanceLiabilityCategories = \Setting::get('accounting\balanceLiabilityCategories');

		$accountLabels = new BalanceUi()->extractLabelsFromCategories($balanceAssetCategories + $balanceLiabilityCategories);
		$accountLabelsWithDepreciation = self::getAccountLabelsWithDepreciation($accountLabels);

		[$resultTable, ] = \journal\AnalyzeLib::getResult($eFinancialYear);
		if(empty($resultTable)) {
			return [];
		}

		$result = array_sum(array_column($resultTable, 'credit')) - array_sum(array_column($resultTable, 'debit'));

		$where = implode('%" OR accountLabel LIKE "', $accountLabelsWithDepreciation);

		$cOperation = \journal\Operation::model()
			->select([
				'accountLabel',
				'accountPrefix' => new \Sql('TRIM(BOTH "0" FROM accountLabel)'),
				'amount' => new \Sql('ABS(SUM(if(type = "debit" OR accountLabel LIKE "139%", -1 * amount, amount)))', 'float')
			])
			->where('accountLabel LIKE "'.$where.'"')
			->where('date BETWEEN "'.$eFinancialYear['startDate'].'" AND "'.$eFinancialYear['endDate'].'"')
			->having('FLOOR(amount) > 0.0')
			->group(['accountPrefix', 'accountLabel'])
			->getCollection();

		// Résultat en compte 120 si > 0, en compte 129 si < 0
		if($result > 0) {
			$cOperation->append(new \journal\Operation(['accountPrefix' => '120', 'accountLabel' => '12000000', 'amount' => $result]));
		} else {
			$cOperation->append(new \journal\Operation(['accountPrefix' => '129', 'accountLabel' => '12900000', 'amount' => $result]));
		}

		// We set all the amortissements to negative values.
		foreach($cOperation as $accountLabel => &$eOperation) {
			$secondCar = mb_substr($eOperation['accountPrefix'], 1, 1);
			if($secondCar === '8' or str_starts_with($eOperation['accountPrefix'], '139')) {
				$eOperation['amount'] *= -1;
			}
		}

		// Retrieve all the labels
		$cOperationLabels = \journal\Operation::model()
			->select([
				'accountLabel',
				'account' => ['id', 'class', 'description']
			])
			->where('accountLabel LIKE "'.$where.'"')
			->where('date BETWEEN "'.$eFinancialYear['startDate'].'" AND "'.$eFinancialYear['endDate'].'"')
			->getCollection(NULL, NULL, 'accountLabel');

		return [
			'asset' => self::formatDetailedBalanceData($cOperation, $cOperationLabels, $balanceAssetCategories),
			'liability' => self::formatDetailedBalanceData($cOperation, $cOperationLabels, $balanceLiabilityCategories),
		];
	}

}

?>
