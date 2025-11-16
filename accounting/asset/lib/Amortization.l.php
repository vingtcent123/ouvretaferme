<?php
namespace asset;

class AmortizationLib extends \asset\AmortizationCrud {

	const DAYS_IN_MONTH = 30;
	const DAYS_IN_YEAR = 360;

	public static function isAmortizable(Asset $eAsset): bool {

		$nonAmortizableAssetsClasses = [
			\account\AccountSetting::NON_AMORTIZABLE_IMPROVEMENTS_CLASS,
			\account\AccountSetting::ASSET_LEASEHOLD_RIGHTS_CLASS,
			\account\AccountSetting::ASSET_GOODWILL_CLASS,
			\account\AccountSetting::ASSET_LANDS_CLASS,
		];

		// Pas une classe d'immo => non
		if(substr($eAsset['accountLabel'], 0, mb_strlen((string)\account\AccountSetting::ASSET_GENERAL_CLASS)) !== (string)\account\AccountSetting::ASSET_GENERAL_CLASS) {
			return FALSE;
		}

		foreach($nonAmortizableAssetsClasses as $class) {

			$stringClass = (string)$class;
			if(substr($eAsset['accountLabel'], 0, mb_strlen($stringClass)) === $stringClass) {
				return FALSE;
			}

		}
		return TRUE;

	}

	/**
	 * Calcul du ratio de prorata
	 * - si linéaire => date de mise en service
	 * - si dégressif => 1er jour du mois de la date d'acquisition
	 *
	 * @param string $firstDateOfFinancialYear
	 * @param Asset $eAsset
	 * @return void
	 */
	public static function computeProrataTemporis(\account\FinancialYear $eFinancialYear, Asset $eAsset, string $type): float {

		if($eAsset[$type.'Mode'] === Asset::LINEAR) {

			$startDate = $eAsset['startDate'];
			$daysFirstMonth = self::DAYS_IN_MONTH - (int)mb_substr($startDate, -2);

		} else {

			$startDate = mb_substr($eAsset['acquisitionDate'], 0, 8).'01';
			$daysFirstMonth = 0;

		}

		$months = self::getMonthsBetweenTwoDates($startDate, $eFinancialYear['endDate']);
		$days = $daysFirstMonth + $months * self::DAYS_IN_MONTH; // 1er mois + mois complets suivants

		// Nombre de mois dans cet exercice comptable (gère le cas où l'exercice comptable dure + que 1 an)
		$monthsInFinancialYear = self::getMonthsBetweenTwoDates($eFinancialYear['startDate'], $eFinancialYear['endDate']);

		return round(min(1, $days / (self::DAYS_IN_MONTH * $monthsInFinancialYear)), 2);

	}

	private static function computeLinearAmortizationUntil(Asset $eAsset, string $endDate): float {

		if($eAsset['economicMode'] === Asset::WITHOUT) {
			return 0;
		}

		$table = self::computeLinearTable($eAsset, 'economic');

		$found = FALSE;
		foreach($table as $amortizationYearData) {

			if($endDate >= $amortizationYearData['financialYear']['startDate'] and $endDate <= $amortizationYearData['financialYear']['endDate']) {
				$found = TRUE;
				break;
			}

		}

		if($found) {

			$startDate = max($amortizationYearData['financialYear']['startDate'], $eAsset['startDate']);
			$endDate = min($amortizationYearData['financialYear']['endDate'], $endDate);

			$months = self::getMonthsBetweenTwoDates($startDate, $endDate);

			$isFirstDayOfStartMonth = ((int)mb_substr($startDate, 8, 2) === 1);
			$daysFirstMonth = $isFirstDayOfStartMonth ? 0 : self::DAYS_IN_MONTH - (int)mb_substr($startDate, 8, 2);

			$lastDayOfEndDate = date('d', mktime(0, 0, 0, (int)mb_substr($endDate, 5, 2) + 1, 0, mb_substr($endDate, 0, 4)));
			$isLastDayOfEndMonth = ($lastDayOfEndDate >= (int)mb_substr($endDate, 8, 2));
			$daysLastMonth = ($isLastDayOfEndMonth ? 0 : (int)mb_substr($endDate, 8, 2));

			return round(
				$amortizationYearData['amortizationValue']
					* ($daysFirstMonth + $months * self::DAYS_IN_MONTH + $daysLastMonth)
					/ self::DAYS_IN_YEAR
				, 2);

		} else {

			return 0;

		}

	}

	/**
	 * En amortissement dégressif, Prorata de fin en cas de mise au rebut ou de vente
	 * /!\ Calcul sur les mois complets
	 *
	 */
	private static function computeDegressiveAmortizationUntil(Asset $eAsset, string $endDate, string $type): float {

		// endDate doit être le dernier jour du mois précédent (= pas de prorata)
		$endDate = date('Y-m-d', mktime(0, 0, 0, mb_substr($endDate, 5, 2), 0, mb_substr($endDate, 0, 4)));

		$table = self::computeDegressiveTable($eAsset, $type);

		// On récupère la valeur d'amortissement de l'année considérée
		$found = FALSE;
		foreach($table as $amortizationYearData) {

			if($endDate > $amortizationYearData['financialYear']['startDate'] and $endDate <= $amortizationYearData['financialYear']['endDate']) {
				$found = TRUE;
				break;
			}

		}

		if($found) {

			$startDate = max($amortizationYearData['financialYear']['startDate'], $eAsset['startDate']);
			$endDate = min($amortizationYearData['financialYear']['endDate'], $endDate);

			$months = self::getMonthsBetweenTwoDates($startDate, $endDate);

			return round($amortizationYearData['amortizationValue'] * ($months / self::DAYS_IN_YEAR), 2);

		} else {

			return 0;

		}

	}

	public static function computeAmortizationUntil(Asset $eAsset, string $endDate, string $type): float {

		if($eAsset[$type.'Mode'] === Asset::LINEAR) {

			return self::computeLinearAmortizationUntil($eAsset, $endDate);

		} else if($eAsset[$type.'Mode'] === Asset::DEGRESSIVE) {

			return self::computeDegressiveAmortizationUntil($eAsset, $endDate, $type);

		}

		return 0.0;

	}

	public static function computeTable(Asset $eAsset): array {

		if($eAsset['economicMode'] === Asset::LINEAR) {

			$table = self::computeLinearTable($eAsset, 'economic');

			if($eAsset['isExcess']) {
				if($eAsset['fiscalMode'] === Asset::LINEAR) {
					$tableFiscal = self::computeLinearTable($eAsset, 'fiscal');
				} else {
					$tableFiscal = self::computeLinearTable($eAsset, 'fiscal');
				}
			}

		} else if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

			$table = self::computeDegressiveTable($eAsset, 'economic');

			if($eAsset['isExcess']) {
				if($eAsset['fiscalMode'] === Asset::LINEAR) {
					$tableFiscal = self::computeLinearTable($eAsset, 'fiscal');
				} else {
					$tableFiscal = self::computeLinearTable($eAsset, 'fiscal');
				}
			}

		} else {

			return [];

		}

		if($eAsset['isExcess'] === FALSE) {
			return $table;
		}

		$tableFiscal = ($eAsset['fiscalMode'] === Asset::LINEAR) ? self::computeLinearTable($eAsset, 'fiscal') : self::computeDegressiveTable($eAsset, 'fiscal');

		$maxIndex = max(count($table), count($tableFiscal));

		for($year = 0; $year < $maxIndex; $year++) {

			if(isset($table[$year]) === 0) {

				$table[$year] = $tableFiscal[$year];
				$table[$year]['excessAmortizationValue'] = $tableFiscal[$year]['amortizationValue'];
				$table[$year]['excessAmortizationValueCumulated'] = $tableFiscal[$year]['amortizationValueCumulated'];
				$table[$year]['amortizationValue'] = 0;
				$table[$year]['base'] = $table[$year - 1]['base'];
				$table[$year]['endValue'] = 0;
				$table[$year]['amortizationValueCumulated'] = $table[$year - 1]['amortizationValueCumulated'];

			} else {

				$table[$year]['excessAmortizationValue'] = ($tableFiscal[$year]['amortizationValue'] ?? 0);
				$table[$year]['excessAmortizationValueCumulated'] = ($tableFiscal[$year]['amortizationValueCumulated'] ?? 0);

				// Dotation
				if($table[$year]['excessAmortizationValue'] > $table[$year]['amortizationValue']) { // Si AF > AC

					$table[$year]['dotation'] = $table[$year]['excessAmortizationValue'] - $table[$year]['amortizationValue'];
					$table[$year]['recovery'] = 0;

					// Reprise
				} else if($table[$year]['excessAmortizationValue'] < $table[$year]['amortizationValue']) { // Si AF < AC

					$table[$year]['recovery'] = $table[$year]['amortizationValue'] - $table[$year]['excessAmortizationValue'];
					$table[$year]['dotation'] = 0;

				}

			}
		}

		return $table;

	}

	/**
	 * Retourne le nombre de mois complets entre 2 dates
	 *
	 * @param string $date1
	 * @param string $date2
	 * @return int
	 */
	public static function getMonthsBetweenTwoDates(string $date1, string $date2): int {

		if($date1 < $date2) {
			$datetime1 = new \DateTime($date1);
			$datetime2 = new \DateTime($date2);
		} else {
			$datetime1 = new \DateTime($date2);
			$datetime2 = new \DateTime($date1);
		}
		$interval = $datetime1->diff($datetime2);

		// Gère le cas où l'exercice comptable dure + que 1 an
		return (int)$interval->format('%m') + 1 + 12 * (int)$interval->format('%y');

	}


	private static function computeLinearTable(Asset $eAsset, string $type): array {

		$durationInYears = floor($eAsset[$type.'Duration'] / 12);
		$rate = self::getLinearRate($durationInYears);

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$endedDate = $eAsset['endedDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

		$amortizableBase = AssetLib::getAmortizableBase($eAsset, $type);

		for($i = 0; $i <= $durationInYears; $i++) {

			$eFinancialYearCurrent = $cFinancialYearAll->find(fn($e) => $e['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

			if($eFinancialYearCurrent === NULL) {
				$eFinancialYear = new \account\FinancialYear([
					'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
					'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
				]);
			} else {
				$eFinancialYear = $eFinancialYearCurrent;
			}

			if($endedDate !== NULL and $eFinancialYear['startDate'] > $endedDate) {
				continue;
			}

			$eAmortization = $eAsset['cAmortization'][$i] ?? new Amortization();

			if($eAmortization->empty()) {

				switch($i) {
					case 0:
						$amortization = round($amortizableBase * $rate * AmortizationLib::computeProrataTemporis($eFinancialYear, $eAsset, $type) / 100, 2);
						break;
					case $durationInYears:
						$amortization = round($amortizableBase - $amortizationCumulated, 2);
						break;
					default:
						$amortization = round($amortizableBase * $rate / 100, 2);
				}

			} else {

				$amortization = $eAmortization['amount'];

			}

			$amortizationCumulated += $amortization;

			$table[] = [
				'year' => $i + 1,
				'financialYear' => $eFinancialYear,
				'base' => $amortizableBase,
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($amortizableBase - $amortizationCumulated),
				'amortization' => $eAmortization,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

			// On n'amortit pas + que la valeur initiale
			if($amortizationCumulated >= $amortizableBase) {
				break;
			}

		}

		return $table;

	}

	private static function getLinearRate(int $duration): float {

		return round(1 / $duration * 100, 2);

	}

	private static function getDegressiveCoefficient(int $duration): float {

		if($duration === 3 or $duration === 4) {

			$degressiveCoefficient = 1.25;

		} else if($duration === 5 or $duration === 6) {

			$degressiveCoefficient = 1.75;

		} else {

			$degressiveCoefficient = 2.25;

		}

		return $degressiveCoefficient;

	}

	private static function computeDegressiveTable(Asset $eAsset, string $type): array {

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$durationInYears = round($eAsset[$type.'Duration'] / 12);

		$baseLinearRate = self::getLinearRate($durationInYears);
		$degressiveCoefficient = self::getDegressiveCoefficient($durationInYears);

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

		$amortizableBase = AssetLib::getAmortizableBase($eAsset, $type);

		for($i = 0; $i <= $durationInYears / 12; $i++) {

			$eFinancialYearCurrent = $cFinancialYearAll->find(fn($e) => $e['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();
			$linearRate = round(1 / ($durationInYears + 1 - $i) * 100, 2);
			$degressiveRate = round($baseLinearRate * $degressiveCoefficient, 2);

			$rate = round(max($linearRate, $degressiveRate), 2);

			if($eFinancialYearCurrent === NULL) { // On simule les années suivantes
				$eFinancialYear = new \account\FinancialYear([
					'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
					'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
				]);
			} else {
				$eFinancialYear = $eFinancialYearCurrent;
			}

			$eAmortization = $eAsset['cAmortization'][$i] ?? new Amortization();

			if($eAmortization->empty()) {

				switch($i) {
					case 0:
						$amortization = round(($amortizableBase) * $rate * AmortizationLib::computeProrataTemporis($eFinancialYear, $eAsset, $type) / 100, 2);
						break;
					case $durationInYears:
						$amortization = round(($amortizableBase - $amortizationCumulated), 2);
						break;
					default:
						$amortization = round(($amortizableBase - $amortizationCumulated) * $rate / 100, 2);
				}

			} else {

				$amortization = $eAmortization['amount'];

			}

			$base = round($amortizableBase - $amortizationCumulated, 2);
			$amortizationCumulated += $amortization;

			$table[] = [
				'year' => $i + 1,
				'financialYear' => $eFinancialYear,
				'base' => $base,
				'linearRate' => $linearRate,
				'degressiveRate' => $degressiveRate,
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($amortizableBase - $amortizationCumulated),
				'amortization' => $eAmortization,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

		}
		return $table;

	}

	public static function amortizeGrant(\account\FinancialYear $eFinancialYear, Asset $eAsset): void {

		$amortizationValue = self::computeAmortizationUntil($eAsset, $eFinancialYear['endDate'], 'economic');
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_ASSETS;

		$grantDebitClass = \account\AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS;
		$amortizationChargeClass = \account\AccountSetting::INVESTMENT_GRANT_TO_RESULT_CLASS;

		$cAccount = \account\AccountLib::getByClasses([$eAsset['account']['class'], $grantDebitClass, $amortizationChargeClass], index: 'class');

		// Étape 1 : On débite 139
		$eAccountGrantDebit = $cAccount[$grantDebitClass]['id'];
		$values = [
			'account' => $eAccountGrantDebit['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountGrantDebit['class']),
			'date' => $eFinancialYear['endDate'],
			'paymentDate' => $eFinancialYear['endDate'],
			'description' => $eAccountGrantDebit['description'].' - '.$eAsset['description'],
			'amount' => $amortizationValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'hash' => $hash,
			'journalCode' => $eAccountGrantDebit['journalCode'],
		];
		\journal\OperationLib::createFromValues($values);

		// Étape 2 : on crédite 777
		$eAccountGrantAmortization = $cAccount[$amortizationChargeClass]['id'];
		$values = [
			'account' => $eAccountGrantAmortization['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountGrantAmortization['class']),
			'date' => $eFinancialYear['endDate'],
			'paymentDate' => $eFinancialYear['endDate'],
			'description' => $eAccountGrantAmortization['description'].' - '.$eAsset['description'],
			'amount' => $amortizationValue,
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'hash' => $hash,
			'journalCode' => $eAccountGrantAmortization['journalCode'],
		];
		\journal\OperationLib::createFromValues($values);

		// Étape 3 : on inscrit cet amortissement dans la base de données de la subvention
		$eAmortization = new Amortization([
			'asset' => $eAsset,
			'amount' => $amortizationValue,
			'type' => Amortization::ECONOMIC,
			'date' => $eFinancialYear['endDate'],
			'financialYear' => $eFinancialYear,
		]);
		Amortization::model()->insert($eAmortization);

		// Étape 4 : Si la sub est entièrement amortie, il faut débiter le compte d'origine (131 ou 138) et créditer 139 du montant total

		$amortizedTotalValue = Amortization::model()
	    ->whereAsset($eAsset)
	    ->getValue(new \Sql('SUM(amount)', 'float'));

		if($eAsset['endDate'] <= $eFinancialYear['endDate'] or $amortizedTotalValue >= $eAsset['value']) {
			Asset::model()->update(
				$eAsset,
				['status' => Asset::ENDED, 'updatedAt' => new \Sql('NOW()')],
			);
		}


	}
	/**
	 * Amortit l'immobilisation sur l'exercice comptable dépendant de sa date d'acquisition / date de fin d'amortissement
	 * Crée une entrée "Dotation aux amortissements" (classe 6) au débit et une entrée "Amortissement" (classe 2) au crédit
	 *
	 */
	public static function amortize(\account\FinancialYear $eFinancialYear, Asset $eAsset, ?string $endDate): void {

		// Cas où on sort l'immo manuellement (cassé, mise au rebus etc.)
		if($endDate === NULL) {
			$endDate = $eFinancialYear['endDate'];
		}

		$amortizationEconomicValue = self::computeAmortizationUntil($eAsset, $endDate, 'economic');
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_ASSETS;

		$amortizationValue = $amortizationEconomicValue;
		$amortizationExcessValue = 0;
		$amortizationExcessRecoverValue = 0;

		if($eAsset['isExcess']) {

			$amortizationExcessClass = \account\AccountSetting::EXCESS_AMORTIZATION_CLASS;
			$eAccountExcessAmortization = \account\AccountLib::getByClass($amortizationExcessClass);

			$amortizationFiscalValue = self::computeAmortizationUntil($eAsset, $endDate, 'fiscal');

			if($amortizationFiscalValue > $amortizationEconomicValue) { // Dotation
				$amortizationExcessValue = $amortizationFiscalValue - $amortizationEconomicValue;
			} else if($amortizationEconomicValue > $amortizationFiscalValue) { // Reprise
				$amortizationExcessRecoverValue = $amortizationEconomicValue - $amortizationFiscalValue;
			}
		}

		// Étape 1 : Dotation aux amortissements, on débite 6811XXXX
		if($eAsset->isIntangible()) {
			$amortizationChargeClass = \account\AccountSetting::INTANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS;
		} else {
			$amortizationChargeClass = \account\AccountSetting::TANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS;
		}

		$eAccountAmortizationCharge = \account\AccountLib::getByClass($amortizationChargeClass);
		$accountLabel = \account\ClassLib::pad(\account\AccountSetting::ASSETS_AMORTIZATION_CHARGE_CLASS.mb_substr($eAsset['accountLabel'], 0, 3));
		$description = new AssetUi()->getTranslation($amortizationChargeClass).' '.$eAsset['description'];
		$values = [
			'account' => $eAccountAmortizationCharge['id'],
			'accountLabel' => $accountLabel,
			'date' => $endDate,
			'paymentDate' => $endDate,
			'description' => $description,
			'amount' => $amortizationValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'hash' => $hash,
			'journalCode' => $eAccountAmortizationCharge['journalCode'],
		];
		\journal\OperationLib::createFromValues($values);

		// Étape 1b : Amortissement dérogatoire, on débite 687 : dotation aux amortissements
		if($amortizationExcessValue > 0) {

			$amortizationExcessChargeClass = \account\AccountSetting::ASSETS_AMORTIZATION__EXCEPTIONAL_CHARGE_CLASS;
			$eAccountExcessAmortizationCharge = \account\AccountLib::getByClass($amortizationExcessChargeClass);
			$description = new AssetUi()->getTranslation($amortizationExcessChargeClass).' '.$eAsset['description'];
			$values = [
				'account' => $eAccountExcessAmortizationCharge['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountExcessAmortizationCharge['class']),
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => $description,
				'amount' => $amortizationExcessValue,
				'type' => \journal\OperationElement::DEBIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAccountAmortizationCharge['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

			// Étape 1c : Reprise d'amortissement dérogatoire, on débite 145
		} else if($amortizationExcessRecoverValue > 0) {

			$description = new AssetUi()->getTranslation(\account\AccountSetting::EXCESS_AMORTIZATION_CLASS).' '.$eAsset['description'];
			$values = [
				'account' => $eAccountExcessAmortization['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountExcessAmortization['class']),
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => $description,
				'amount' => $amortizationExcessRecoverValue,
				'type' => \journal\OperationElement::DEBIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAccountExcessAmortization['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

		}

		// Étape 2 : Amortissement, on crédite 28XXXXXX
		$values = self::getAmortizationOperationValues($eFinancialYear, $eAsset, $endDate, $amortizationValue);
		$values['hash'] = $hash;

		if($amortizationValue !== 0.0) {
			\journal\OperationLib::createFromValues($values);
		}

		// Étape 2b : Amortissement dérogatoire, on crédite 145
		if($amortizationExcessValue > 0) {

			$eAccountExcessAmortization = \account\AccountLib::getByClass($amortizationExcessClass);
			$description = new AssetUi()->getTranslation($amortizationExcessClass).' '.$eAsset['description'];
			$values = [
				'account' => $eAccountExcessAmortization['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountExcessAmortization['class']),
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => $description,
				'amount' => $amortizationExcessValue,
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAccountAmortizationCharge['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);


		// Étape 2c : Reprise d'amortissement dérogatoire, on crédite 787
		} else if($amortizationExcessRecoverValue > 0) {

			$eAccountExcessRecoveryAmortization = \account\AccountLib::getByClass(\account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION);
			$description = new AssetUi()->getTranslation(\account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION).' '.$eAsset['description'];
			$values = [
				'account' => $eAccountExcessRecoveryAmortization['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountExcessRecoveryAmortization['class']),
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => $description,
				'amount' => $amortizationExcessRecoverValue,
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAccountExcessRecoveryAmortization['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

		}

		// Étape 3 : Entrée dans la table Amortization
		$eAmortization = new Amortization([
			'asset' => $eAsset,
			'amount' => $amortizationValue,
			'type' => Amortization::ECONOMIC,
			'date' => $endDate,
			'financialYear' => $eFinancialYear,
		]);

		Amortization::model()->insert($eAmortization);

		if($eAccountExcessAmortization > 0) {

			$eAmortization = new Amortization([
				'asset' => $eAsset,
				'amount' => $eAccountExcessAmortization,
				'type' => Amortization::EXCESS,
				'date' => $endDate,
				'financialYear' => $eFinancialYear,
			]);

			Amortization::model()->insert($eAmortization);

		}

		// Étape 4 : Mise à jour de l'immobilisation
		Asset::model()->update(
			$eAsset,
			[
				'economicAmortization' => new \Sql('economicAmortization + '.$amortizationValue),
				'excessAmortization' => new \Sql('excessAmortization + '.$amortizationExcessValue),
				'excessRecovery' => new \Sql('excessRecovery + '.$amortizationExcessRecoverValue),
				'status' => new \Sql('IF(economicAmortization >= value OR endDate <= "'.$endDate.'", "'.Asset::ENDED.'" : status)'),
				'updatedAt' => new \Sql('NOW()'),
			]
		);

	}

	/**
	 * Renvoie les valeurs d'une opération d'amortissement pour l'immobilisation et le montant donnés
	 * Opération en 28xxx
	 *
	 * @return array
	 */
	public static function getAmortizationOperationValues(\account\FinancialYear $eFinancialYear, Asset $eAsset, string $operationDate, float $amount): array {

		// Trouve le compte le plus proche
		$cAccountAmortization = \account\AccountLib::getAll(new \Search(['classPrefix' => \account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS]));
		$assetAmortizationClass = rtrim(\account\ClassLib::getAmortizationClassFromClass($eAsset['accountLabel']), '0');
		$eAccountAmortizationFound = new \account\Account();
		for($i = 0; $i < mb_strlen($assetAmortizationClass); $i++) {
			$accountLabel = mb_substr($assetAmortizationClass, 0, mb_strlen($assetAmortizationClass) - $i);
			foreach($cAccountAmortization as $offset => $eAccountAmortization) {
				if($eAccountAmortization['class'] !== $accountLabel) {
					$cAccountAmortization->offsetUnset($offset);
				} else {
					$eAccountAmortizationFound = $eAccountAmortization;
					break 2;
				}
			}
		}

		// Pas trouvé ? Le compte général à 3 chiffres
		if($eAccountAmortizationFound->empty()) {
			$eAccountAmortizationFound = $eAsset->isTangible()
				? \account\AccountLib::getByClass(\account\AccountSetting::ASSET_AMORTIZATION_TANGIBLE_CLASS)
				: \account\AccountLib::getByClass(\account\AccountSetting::ASSET_AMORTIZATION_INTANGIBLE_CLASS);
		}

		$amortizationAccountLabel = \account\ClassLib::pad(rtrim(\account\ClassLib::getAmortizationClassFromClass($eAsset['accountLabel']), '0'));

		$description = new AssetUi()->getTranslation('amortization').' '.$eAsset['description'];

		return [
			'account' => $eAccountAmortizationFound['id'],
			'accountLabel' => \account\ClassLib::pad($amortizationAccountLabel),
			'date' => $operationDate,
			'paymentDate' => $operationDate,
			'description' => $description,
			'amount' => $amount,
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'journalCode' => $eAccountAmortizationFound['journalCode'],
		];

	}

	public static function getSummary(\account\FinancialYear $eFinancialYear): array {

		$amortizations = self::getByFinancialYear($eFinancialYear, 'asset');

		if(empty($amortizations)) {
			return [];
		}

		$emptyLine = [
			'account' => '',
			'accountLabel' => '',
			'acquisitionValue' => 0,
			'economic' => [
				'startFinancialYearValue' => 0,
				'currentFinancialYearAmortization' => 0,
				'currentFinancialYearDegressiveAmortization' => 0,
				'endFinancialYearValue' => 0,
			],
			'grossValueDiminution' => 0,
			'netFinancialValue' => 0,
			'excess' => [
				'startFinancialYearValue' => 0,
				'currentFinancialYearAmortization' => 0,
				'reversal' => 0,
				'endFinancialYearValue' => 0,
			],
			'fiscalNetValue' => 0,
		];

		$lines = [];
		$total = $emptyLine;
		$generalTotal = $emptyLine;

		$currentAccountLabel = NULL;

		foreach($amortizations as $amortization) {

			if($currentAccountLabel !== NULL and $amortization['accountLabel'] !== $currentAccountLabel) {

				AmortizationUi::addTotalLine($generalTotal, $total);
				$lines[] = $total;
				$total = $emptyLine;

			}

			$total['accountLabel'] = $amortization['accountLabel'];
			$total['description'] = $amortization['accountDescription'];
			$currentAccountLabel = $amortization['accountLabel'];
			AmortizationUi::addTotalLine($total, $amortization);

		}

		AmortizationUi::addTotalLine($generalTotal, $total);

		$lines[] = $total;
		$lines[] = $generalTotal;

		return $lines;

	}

	public static function getByFinancialYear(\account\FinancialYear $eFinancialYear, string $type): array {

		$cAsset = match($type) {
			'asset' => AssetLib::getAssetsByFinancialYear($eFinancialYear),
			'grant' => AssetLib::getGrantsByFinancialYear($eFinancialYear),
		};

		$ccAmortization = Amortization::model()
			->select(['asset', 'financialYear', 'amount', 'type',])
			->whereAsset('IN', $cAsset)
			->whereDate('<=', $eFinancialYear['endDate'])
			->getCollection(NULL, NULL, ['asset', 'financialYear']);

		$amortizations = [];

		foreach($cAsset as $eAsset) {

			$accountLabel = $eAsset['accountLabel'];

			$cAmortization = $ccAmortization->offsetExists($eAsset['id']) ? $ccAmortization->offsetGet($eAsset['id']) : new \Collection();

			// sum what has already been amortized for this asset (during previous financial years)
			$alreadyAmortized = $eAsset['economicAmortization'];
			$alreadyExcessAmortized = $eAsset['excessAmortization'];

			// This financial year amortization
			if($eFinancialYear['status'] === \account\FinancialYearElement::CLOSE) {

				if(($cAmortization->offsetExists($eFinancialYear['id']) and $cAmortization[$eFinancialYear['id']]['type'] === Amortization::ECONOMIC)) {

					$currentAmortization = $cAmortization[$eFinancialYear['id']]['amount'];
					$alreadyAmortized -= $currentAmortization;

				} else {
					$currentAmortization = 0;
				}

				if($cAmortization->offsetExists($eFinancialYear['id']) and $cAmortization[$eFinancialYear['id']]['type'] === Amortization::EXCESS) {

					$currentExcessAmortization =  $cAmortization[$eFinancialYear['id']]['amount'];
					$alreadyExcessAmortized -= $currentExcessAmortization;

				} else {
					$currentExcessAmortization = 0;
				}


			} else {

				// Estimate
				$currentAmortization = self::computeAmortizationUntil($eAsset, $eFinancialYear['endDate'], 'economic');
				$currentExcessAmortization = 0;

			}

			$vnc = AssetLib::getAmortizableBase($eAsset, 'economic') - $alreadyAmortized - $currentAmortization;
			$vnf = AssetLib::getAmortizableBase($eAsset, 'economic') - $alreadyAmortized - $alreadyExcessAmortized - $currentExcessAmortization - $currentAmortization;

			$amortization = [
				'id' => $eAsset['id'],
				'accountLabel' => $accountLabel,
				'accountDescription' => $eAsset['account']['description'],
				'description' => $eAsset['description'],

				'status' => $eAsset['status'],
				'economicMode' => $eAsset['economicMode'],
				'fiscalMode' => $eAsset['fiscalMode'],

				'acquisitionDate' => $eAsset['acquisitionDate'],
				'startDate' => $eAsset['startDate'],
				'endDate' => $eAsset['endDate'],

				'duration' => $eAsset['economicDuration'],

				'acquisitionValue' => $eAsset['value'],

				// Economic amortization
				'economic' => [
					// Début exercice : NULL si acquis durant l'exercice comptable
					'startFinancialYearValue' => $eAsset['startDate'] >= $eFinancialYear['startDate'] ? NULL : $alreadyAmortized,
					'currentFinancialYearAmortization' => $currentAmortization, // Dotation pour l'exercice (proratisé)
					'currentFinancialYearDegressiveAmortization' => $currentExcessAmortization,
					'endFinancialYearValue' => $alreadyAmortized + $currentAmortization, // Cumul de toutes les dotations
				],

				// Diminution de valeur brut
				'grossValueDiminution' => 0, // TODO

				// VNC
				'netFinancialValue' => $vnc,

				// Excess amortization (amortissement dérogatoire)
				'excess' => [
					'startFinancialYearValue' => 0,
					'currentFinancialYearAmortization' => $currentExcessAmortization,
					'endFinancialYearValue' => $alreadyExcessAmortized + $currentExcessAmortization,
				],

				// VNF
				'fiscalNetValue' => $vnf,


			];

			$amortizations[] = $amortization;

		}

		return $amortizations;

	}

	/**
	 * Reprise de toutes les dotations dérogatoires
	 * débite 145
	 * crédite 787
	 */
	public static function recoverExcess(\account\FinancialYear $eFinancialYear, Asset $eAsset, string $endDate): void {

		if($eAsset['excessRecovery'] >= $eAsset['excessAmortization']) {
			return;
		}

		$amount = $eAsset['excessAmortization'] - $eAsset['excessRecovery'];
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_ASSETS;

		// Débite 145
		$eAccountExcessAmortization = \account\AccountLib::getByClass(\account\AccountSetting::EXCESS_AMORTIZATION_CLASS);
		$description = new AssetUi()->getTranslation(\account\AccountSetting::EXCESS_AMORTIZATION_CLASS).' '.$eAsset['description'];
		$values = [
			'account' => $amount['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountExcessAmortization['class']),
			'date' => $endDate,
			'paymentDate' => $endDate,
			'description' => $description,
			'amount' => $amount,
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'hash' => $hash,
			'journalCode' => $eAccountExcessAmortization['journalCode'],
		];
		\journal\OperationLib::createFromValues($values);

		// Crédite 787
		$eAccountExcessRecoveryAmortization = \account\AccountLib::getByClass(\account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION);
		$description = new AssetUi()->getTranslation(\account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION).' '.$eAsset['description'];
		$values = [
			'account' => $eAccountExcessRecoveryAmortization['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountExcessRecoveryAmortization['class']),
			'date' => $endDate,
			'paymentDate' => $endDate,
			'description' => $description,
			'amount' => $amount,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
			'hash' => $hash,
			'journalCode' => $eAccountExcessRecoveryAmortization['journalCode'],
		];
		\journal\OperationLib::createFromValues($values);

		// Met à jour l'asset
		Asset::model()
			->update($eAsset, [
				'status' => Asset::ENDED,
				'excessRecovery' => new \Sql('excessRecovery + '.$amount),
				'updatedAt' => new \Sql('NOW()'),
			]);
	}

}
?>
