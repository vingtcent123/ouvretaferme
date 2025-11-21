<?php
namespace asset;

class AmortizationLib extends \asset\AmortizationCrud {

	const DAYS_IN_MONTH = 30;
	const DAYS_IN_YEAR = 360;
	const MONTHS_IN_YEAR = 12;

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

		return round(min(1, $days / (self::DAYS_IN_MONTH * $monthsInFinancialYear)), 4);

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

			return round($amortizationYearData['amortizationValue'] * ($months / self::MONTHS_IN_YEAR), 2);

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

		$table = self::computeTheoricTable($eAsset);

		if($eAsset['endedDate'] === NULL) {
			return $table;
		}

		$effectiveTable = [];
		$amortizationCumulated = 0;
		$amortizationFiscalCumulated = 0;

		foreach($table as $year => $period) {

			if($eAsset['endedDate'] > $period['financialYear']['endDate']) {
				$effectiveTable[$year] = $period;
				$amortizationCumulated += $period['amortizationValue'];
				$amortizationFiscalCumulated += $period['fiscalAmortizationValue'];
				continue;
			}

			// Déjà fini !
			if($eAsset['endedDate'] < $period['financialYear']['startDate']) {
				continue;
			}

			// calcul du prorata
			$amortization = self::computeAmortizationUntil($eAsset, $eAsset['endedDate'], 'economic');
			$amortizationCumulated = round($amortizationCumulated + $amortization, 2);
			$amortizationFiscal = self::computeAmortizationUntil($eAsset, $eAsset['endedDate'], 'fiscal');
			$amortizationFiscalCumulated = round($amortizationFiscalCumulated + $amortizationFiscal, 2);

			$excessDotation = 0;
			$excessRecovery = 0;

			if($eAsset['isExcess']) {

				if($amortizationFiscal > $amortization) {

					$excessDotation = $amortizationFiscal - $amortization;
					$excessRecovery = 0;

				} else if($amortization > $amortizationFiscal) {

					$excessDotation = 0;
					$excessRecovery = $amortization - $amortizationFiscal;
				}

			}

			$period['amortizationValue'] = $amortization;
			$effectiveTable[$year] = [
				'year' => $year,
				'financialYear' => $period['financialYear'],
				'base' => $period['base'],
				'rate' => $period['rate'],
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => $amortizationCumulated,
				'endValue' => round($period['base'] - $amortizationCumulated, 2),
				'fiscalAmortizationValue' => $amortizationFiscal,
				'fiscalAmortizationValueCumulated' => $amortizationFiscalCumulated,
				'excessDotation' => $excessDotation,
				'excessRecovery' => $excessRecovery,
			];
		}

		return $effectiveTable;
	}

	public static function computeTheoricTable(Asset $eAsset): array {

		$economicTable = match($eAsset['economicMode']) {
			Asset::LINEAR => self::computeLinearTable($eAsset, 'economic'),
			Asset::DEGRESSIVE => self::computeDegressiveTable($eAsset, 'economic'),
			default => [],
		};
		$fiscalTable = match($eAsset['fiscalMode']) {
			Asset::LINEAR => self::computeLinearTable($eAsset, 'fiscal'),
			Asset::DEGRESSIVE => self::computeDegressiveTable($eAsset, 'fiscal'),
			default => [],
		};

		if($eAsset['isExcess'] === FALSE) {
			return match($eAsset['economicMode']) {
				Asset::LINEAR => $economicTable,
				Asset::DEGRESSIVE => $fiscalTable,
				default => [],
			};
		}

		$maxIndex = max(count($economicTable), count($fiscalTable));

		$table = $economicTable;

		for($year = 0; $year < $maxIndex; $year++) {

			if(isset($table[$year]) === FALSE) {

				$table[$year] = $fiscalTable[$year];
				$table[$year]['fiscalAmortizationValue'] = $fiscalTable[$year]['amortizationValue'];
				$table[$year]['fiscalAmortizationValueCumulated'] = $fiscalTable[$year]['amortizationValueCumulated'];
				$table[$year]['amortizationValue'] = 0;
				$table[$year]['base'] = $table[$year - 1]['base'];
				$table[$year]['endValue'] = 0;
				$table[$year]['amortizationValueCumulated'] = $table[$year - 1]['amortizationValueCumulated'];

			} else {

				$table[$year]['fiscalAmortizationValue'] = ($fiscalTable[$year]['amortizationValue'] ?? 0);
				$table[$year]['fiscalAmortizationValueCumulated'] = ($fiscalTable[$year]['amortizationValueCumulated'] ?? 0);

				// Dotation
				if($table[$year]['fiscalAmortizationValue'] > $table[$year]['amortizationValue']) { // Si AF > AC

					$table[$year]['excessDotation'] = $table[$year]['fiscalAmortizationValue'] - ($table[$year]['amortizationValue'] ?? 0);
					$table[$year]['excessRecovery'] = 0;

					// Reprise
				} else if($table[$year]['fiscalAmortizationValue'] < $table[$year]['amortizationValue']) { // Si AF < AC

					$table[$year]['excessDotation'] = 0;
					$table[$year]['excessRecovery'] = $table[$year]['amortizationValue'] - $table[$year]['fiscalAmortizationValue'];

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
		return floor((int)$interval->format('%d') / self::DAYS_IN_MONTH) + (int)$interval->format('%m') + 12 * (int)$interval->format('%y');

	}

	private static function findFirstFinancialYearForAsset(Asset $eAsset, \Collection $cFinancialYear): \account\FinancialYear {

		$startDate = $eAsset['startDate'];

		$found = FALSE;
		foreach($cFinancialYear as $eFinancialYear) {
			if($eFinancialYear['startDate'] <= $startDate and $eFinancialYear['endDate'] >= $startDate) {
				$found = TRUE;
				break;
			}
		}

		$financialYearStartDate = $eFinancialYear['startDate'];
		$financialYearEndDate = $eFinancialYear['endDate'];
		while($found === FALSE) {

			$financialYearStartDate = date('Y-m-d', strtotime($financialYearStartDate. ' - 1 YEAR'));
			$financialYearEndDate = date('Y-m-d', strtotime($financialYearEndDate. ' - 1 YEAR'));

			if($financialYearStartDate <= $startDate and $financialYearEndDate >= $startDate) {
				$found = TRUE;
			}
		}

		return new \account\FinancialYear(['startDate' => $financialYearStartDate, 'endDate' => $financialYearEndDate]);
	}


	private static function computeLinearTable(Asset $eAsset, string $type): array {

		$durationInYears = floor($eAsset[$type.'Duration'] / 12);
		$rate = self::getLinearRate($durationInYears);

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$table = [];
		$amortizationCumulated = 0;

		$eFinancialYear = self::findFirstFinancialYearForAsset($eAsset, $cFinancialYearAll);

		$currentDate = $eAsset['startDate'];

		$amortizableBase = AssetLib::getAmortizableBase($eAsset, $type);

		for($i = 0; $i <= $durationInYears; $i++) {

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

			$amortizationCumulated += $amortization;

			$table[] = [
				'year' => $i,
				'financialYear' => $eFinancialYear,
				'base' => $amortizableBase,
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($amortizableBase - $amortizationCumulated),
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

			// On n'amortit pas + que la valeur initiale
			if($amortizationCumulated >= $amortizableBase) {
				break;
			}

			// On incrémente l'exercice
			$eFinancialYear = new \account\FinancialYear([
				'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
				'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
			]);

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

		$eFinancialYear = self::findFirstFinancialYearForAsset($eAsset, $cFinancialYearAll);

		$amortizableBase = AssetLib::getAmortizableBase($eAsset, $type);

		for($i = 0; $i < $durationInYears; $i++) {

			if($i !== 0) {

				$eFinancialYearCurrent = $cFinancialYearAll->find(fn($e) => $e['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

				if($eFinancialYearCurrent === NULL) { // On simule les années suivantes
					$eFinancialYear = new \account\FinancialYear([
						'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
						'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
					]);
				} else {
					$eFinancialYear = $eFinancialYearCurrent;
				}

			}
			$linearRate = round(1 / ($durationInYears - $i) * 100, 2);
			$degressiveRate = round($baseLinearRate * $degressiveCoefficient, 2);

			$rate = round(max($linearRate, $degressiveRate), 2);

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

			$base = round($amortizableBase - $amortizationCumulated, 2);
			$amortizationCumulated = round($amortizationCumulated + $amortization, 2);

			$table[] = [
				'year' => $i + 1,
				'financialYear' => $eFinancialYear,
				'base' => $base,
				'linearRate' => $linearRate,
				'degressiveRate' => $degressiveRate,
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($amortizableBase - $amortizationCumulated, 2),
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

	public static function simulate(\account\FinancialYear $eFinancialYear, \Collection $cAsset): void {

		foreach($cAsset as &$eAsset) {
			$eAsset['operations'] = self::amortize($eFinancialYear, $eAsset, NULL, TRUE);
		}

	}

	/**
	 * Amortit l'immobilisation sur l'exercice comptable dépendant de sa date d'acquisition / date de fin d'amortissement
	 * Crée une entrée "Dotation aux amortissements" (classe 6) au débit et une entrée "Amortissement" (classe 2) au crédit
	 *
	 */
	public static function amortize(\account\FinancialYear $eFinancialYear, Asset $eAsset, ?string $endDate, bool $simulate = FALSE): \Collection {

		$cOperation = new \Collection();

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

		if($simulate) {
			$cOperation->append(new \journal\Operation($values));
		} else {
			\journal\OperationLib::createFromValues($values);
		}

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
			if($simulate) {
				$cOperation->append(new \journal\Operation($values));
			} else {
				\journal\OperationLib::createFromValues($values);
			}

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
			if($simulate) {
				$cOperation->append(new \journal\Operation($values));
			} else {
				\journal\OperationLib::createFromValues($values);
			}

		}

		// Étape 2 : Amortissement, on crédite 28XXXXXX
		$values = self::getAmortizationOperationValues($eFinancialYear, $eAsset, $endDate, $amortizationValue);
		$values['hash'] = $hash;

		if($amortizationValue !== 0.0) {
			if($simulate) {
				$cOperation->append(new \journal\Operation($values));
			} else {
				\journal\OperationLib::createFromValues($values);
			}
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
			if($simulate) {
				$cOperation->append(new \journal\Operation($values));
			} else {
				\journal\OperationLib::createFromValues($values);
			}


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
			if($simulate) {
				$cOperation->append(new \journal\Operation($values));
			} else {
				\journal\OperationLib::createFromValues($values);
			}

		}

		if($simulate) {
			return $cOperation;
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

		if(($eAccountExcessAmortization ?? 0) > 0) {

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
				'status' => new \Sql('IF(economicAmortization >= value OR endDate <= "'.$endDate.'", "'.Asset::ENDED.'", status)'),
				'updatedAt' => new \Sql('NOW()'),
			]
		);

		return $cOperation;

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

		$amortizations = [];

		foreach($cAsset as $eAsset) {

			$table = self::computeTable($eAsset);
			$accountLabel = $eAsset['accountLabel'];

			$alreadyAmortized = 0;
			$alreadyExcessAmortized = 0;
			$alreadyExcessRecovered = 0;

			$currentAmortization = 0;
			$currentExcessAmortization = 0;
			$currentExcessRecovery = 0;

			foreach($table as $period) {

				if($period['financialYear']['startDate'] > $eFinancialYear['startDate']) {
					continue;
				}

				if($period['financialYear']['startDate'] === $eFinancialYear['startDate']) {

					$currentAmortization = $period['amortizationValue'];
					$currentExcessAmortization = ($period['excessDotation'] ?? 0);
					$currentExcessRecovery = ($period['excessRecovery'] ?? 0);

				} else {

					$alreadyAmortized += ($period['amortizationValue'] ?? 0);

					$alreadyExcessAmortized += ($period['excessDotation'] ?? 0);
					$alreadyExcessAmortized -= ($period['excessRecovery'] ?? 0);

				}

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
					'startFinancialYearValue' => $alreadyAmortized,
					'currentFinancialYearAmortization' => $currentAmortization, // Dotation pour l'exercice
					'currentFinancialYearDegressiveAmortization' => $currentExcessAmortization,
					'endFinancialYearValue' => $alreadyAmortized + $currentAmortization, // Cumul de toutes les dotations
				],

				// Diminution de valeur brut
				'grossValueDiminution' => 0, // TODO

				// VNC
				'netFinancialValue' => $vnc,

				// Excess amortization (amortissement dérogatoire)
				'excess' => [
					'startFinancialYearValue' => $alreadyExcessAmortized,
					'currentFinancialYearAmortization' => $currentExcessAmortization,
					'currentFinancialYearRecovery' => $currentExcessRecovery,
					'endFinancialYearValue' => $alreadyExcessAmortized + $currentExcessAmortization - $currentExcessRecovery,
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
	 *
	 * débite 145
	 * crédite 787
	 */
	public static function recoverExcess(\account\FinancialYear $eFinancialYear, Asset $eAsset, string $endDate): void {

		if($eAsset['isExcess'] === FALSE) {
			return;
		}

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
				'excessRecovery' => new \Sql('excessRecovery + '.$amount),
				'updatedAt' => new \Sql('NOW()'),
			]);
	}

	public static function resume(Asset $eAsset): void {

		// Calcul des amortissements déjà réalisés s'il y a une reprise
		if($eAsset['resumeDate'] !== NULL) {

			$table = AmortizationLib::computeTable($eAsset);

			$economicAmortization = 0;
			$excessAmortization = 0;
			$excessRecovery = 0;

			foreach($table as $period) {

				if($period['financialYear']['endDate'] < $eAsset['resumeDate']) {

					$economicAmortization += $period['amortizationValue'];

					if($eAsset['isExcess']) {
						$excessAmortization += $period['excessDotation'];
						$excessRecovery += $period['excessRecovery'];
					}

				}

			}

			$eAsset['economicAmortization'] = $economicAmortization;

			$eAmortization = new Amortization([
				'asset' => $eAsset,
				'amount' => $economicAmortization,
				'type' => Amortization::ECONOMIC,
				'date' => date('Y-m-d', strtotime($eAsset['resumeDate'].' - 1 DAY')),
				'financialYear' => NULL,
			]);

			Amortization::model()->insert($eAmortization);

			if($eAsset['isExcess']) {
				$eAsset['excessAmortization'] = $excessAmortization;
				$eAsset['excessRecovery'] = $excessRecovery;

				$eAmortization = new Amortization([
					'asset' => $eAsset,
					'amount' => $excessAmortization,
					'type' => Amortization::EXCESS,
					'date' => date('Y-m-d', strtotime($eAsset['resumeDate'].' - 1 DAY')),
					'financialYear' => NULL,
				]);

				Amortization::model()->insert($eAmortization);

			}

			Asset::model()
		     ->update($eAsset, [
			     'economicAmortization' => $economicAmortization,
			     'excessAmortization' => $excessAmortization,
			     'excessRecovery' => $excessRecovery,
		     ]);
		}
	}

}
?>
