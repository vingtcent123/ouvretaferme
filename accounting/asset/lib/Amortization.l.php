<?php
namespace asset;

class AmortizationLib extends \asset\AmortizationCrud {

	const DAYS_IN_MONTH = 30;
	const DAYS_IN_YEAR = 360;


	public static function isAmortizable(Asset $eAsset): bool {

		return substr($eAsset['accountLabel'], 0, mb_strlen(\account\AccountSetting::NON_AMORTIZABLE_ASSET_CLASS)) !== \account\AccountSetting::NON_AMORTIZABLE_ASSET_CLASS;

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
	public static function computeProrataTemporis(\account\FinancialYear $eFinancialYear, Asset $eAsset): float {

		if($eAsset['economicMode'] === Asset::LINEAR) {

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

		$table = self::computeLinearTable($eAsset);

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

			$lastDayOfEndDate = date('d', mktime(0, 0, 0, mb_substr($endDate, 5, 2) + 1, 0, mb_substr($endDate, 0, 4)));
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
	 * @param Asset $eAsset
	 * @param string $endDate
	 * @return float
	 */
	private static function computeDegressiveAmortizationUntil(Asset $eAsset, string $endDate): float {

		// endDate doit être le dernier jour du mois précédent (= pas de prorata)
		$endDate = date('Y-m-d', mktime(0, 0, 0, mb_substr($endDate, 5, 2), 0, mb_substr($endDate, 0, 4)));

		$table = self::computeDegressiveTable($eAsset);

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

	public static function computeAmortizationUntil(Asset $eAsset, string $endDate): float {

		if($eAsset['economicMode'] === Asset::LINEAR) {

			return self::computeLinearAmortizationUntil($eAsset, $endDate);

		} else if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

			return self::computeDegressiveAmortizationUntil($eAsset, $endDate);

		}

	}

	public static function computeTable(Asset $eAsset): array {

		if($eAsset['economicMode'] === Asset::LINEAR) {

			return self::computeLinearTable($eAsset);

		} else if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

			return self::computeDegressiveTable($eAsset);

		}

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


	private static function computeLinearTable(Asset $eAsset): array {

		$durationInYears = floor($eAsset['economicDuration'] / 12);
		$rate = self::getLinearRate($durationInYears);

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$endedDate = $eAsset['endedDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

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
						$amortization = round($eAsset['amortizableBase'] * $rate * AmortizationLib::computeProrataTemporis($eFinancialYear, $eAsset) / 100, 2);
						break;
					case $durationInYears:
						$amortization = round($eAsset['amortizableBase'] - $amortizationCumulated, 2);
						break;
					default:
						$amortization = round($eAsset['amortizableBase'] * $rate / 100, 2);
				}

			} else {

				$amortization = $eAmortization['amount'];

			}

			$amortizationCumulated += $amortization;

			$table[] = [
				'year' => $i + 1,
				'financialYear' => $eFinancialYear,
				'base' => $eAsset['amortizableBase'],
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($eAsset['amortizableBase'] - $amortizationCumulated),
				'amortization' => $eAmortization,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

			// On n'amortit pas + que la valeur initiale
			if($amortizationCumulated >= $eAsset['amortizableBase']) {
				break;
			}

		}

		return $table;

	}

	private static function getLinearRate(int $duration): float {

		return round(1 / $duration * 100, 2);

	}

	private static function getDegressiveCoefficient(int $duration): float {

		$baseLinearRate = self::getLinearRate($duration);

		if($duration === 3 or $duration === 4) {

			$degressiveCoefficient = 1.25;

		} else if($duration === 5 or $duration === 6) {

			$degressiveCoefficient = 1.75;

		} else {

			$degressiveCoefficient = 2.25;

		}

		return $degressiveCoefficient;

	}

	private static function computeDegressiveTable(Asset $eAsset): array {

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$durationInYears = round($eAsset['economicDuration'] / 12);

		$baseLinearRate = self::getLinearRate($durationInYears);
		$degressiveCoefficient = self::getDegressiveCoefficient($durationInYears);

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

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

			$eDepreciation = $eAsset['cAmortization'][$i] ?? new Amortization();

			if($eDepreciation->empty()) {

				switch($i) {
					case 0:
						$amortization = round(($eAsset['amortizableBase']) * $rate * AmortizationLib::computeProrataTemporis($eFinancialYear, $eAsset) / 100, 2);
						break;
					case $durationInYears:
						$amortization = round(($eAsset['amortizableBase'] - $amortizationCumulated), 2);
						break;
					default:
						$amortization = round(($eAsset['amortizableBase'] - $amortizationCumulated) * $rate / 100, 2);
				}

			} else {

				$amortization = $eDepreciation['amount'];

			}

			$base = round($eAsset['amortizableBase'] - $amortizationCumulated, 2);
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
				'endValue' => round($eAsset['amortizableBase'] - $amortizationCumulated),
				'amortization' => $eDepreciation,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

		}

		return $table;

	}
/*
	public static function getDegressiveCoefficient(int $duration): int {

		if($duration <= 4) {
			return 1.25;
		}

		if($duration <= 6) {
			return 1.75;
		}

		return 2.25;

	}

	public static function computeAmortizationRate(Asset $eAsset) {

		if($eAsset['economicMode'] === Asset::LINEAR) {
			return 1 / ($eAsset['economicDuration'] / 12);
		}

		if($eAsset['type'] === Asset::DEGRESSIVE) {
			return AmortizationLib::getDegressiveCoefficient($eAsset['economicDuration'] / 12) / ($eAsset['economicDuration'] * 12);
		}

		throw new \NotExpectedAction('Unknown amortization type.');

	}

	private static function computeCurrentFinancialYearExcessAmortization(Asset $eAsset, float $alreadyAmortized, \account\FinancialYear $eFinancialYear): float {

		return 0.0;

	}

	private static function getDays(string $startDate, string $endDate, Asset $eAsset): int {

		// Calcul du nombre de mois complets
		$startDatetime = new \DateTime($startDate);
		$endDatetime = new \DateTime($endDate);
		$interval = $startDatetime->diff($endDatetime);
		$months = (int)$interval->format('%m');
		$days = $months * 30; // En comptabilité, un mois fait 30 jours.

		// Ajout du nombre de jours de prorata (début)
		if($eAsset['startDate'] >= $startDate) {

			$lastDayOfMonth = date("Y-m-d", mktime(0, 0, 0, (int)date('m', strtotime($eAsset['startDate'])) + 1, 0, date('Y', strtotime($startDate))));

			// Intervalle : on aurait du faire +1 mais ce n'est pas le calcul de ISTEA.
			$days += min(30, max((int)date('d', strtotime($lastDayOfMonth)) - (int)date('d', strtotime($eAsset['startDate'])), 1));
		}

		// Ajout du nombre de jours de prorata (fin)
		if($eAsset['endDate'] < $endDate) {
			$days += min(date('d', strtotime($eAsset['endDate'])), 30);
		}

		return $days;
	}

	public static function calculateGrantAmortization(string $startDate, string $endDate, Asset $eAsset): array {

		$value = $eAsset['value'];
		$rate = 1 / $eAsset['duration'];

		$days = self::getDays(max($startDate, $eAsset['startDate']), min($endDate, $eAsset['endDate']), $eAsset);

		$prorata = min(1, $days / 360);

		return ['prorataDays' => $prorata, 'value' => round($value * $rate * $prorata, 2)];
	}

	public static function calculateAmortization(string $startDate, string $endDate, Asset $eAsset): float {

		if(in_array($eAsset['economicMode'], [Asset::LINEAR, Asset::DEGRESSIVE]) === FALSE) {
			return 0.0;
		}

		$base = $eAsset['value'];
		$rate = AmortizationLib::computeAmortizationRate($eAsset);

		$days = self::getDays(max($startDate, $eAsset['startDate']), min($endDate, $eAsset['endDate']), $eAsset);

		$prorata = min(1, $days / 360);

		if ($eAsset['economicMode'] === AssetElement::LINEAR) {

			// Annuité = $base * $rate
			return round($base * $rate * $prorata, 2);

		}

		// Si l'amortissement est dégressif, il faut calculer le plus avantageux.
		$remainingValue = $eAsset['value'];
		$durationInYears = $eAsset['duration'] / 12;

		for ($currentYear = 1; $currentYear <= $durationInYears; $currentYear++) {
			$annuity = $remainingValue * $rate;

			// Calcul de l’amortissement linéaire résiduel
			$linearBase = $eAsset['value'] - (($currentYear - 1) * ($eAsset['value'] / $durationInYears));
			$linearAnnuity = $linearBase / ($durationInYears - ($currentYear - 1));

			if ($linearAnnuity > $annuity) {
				$annuity = $linearAnnuity;
			}

			if ($currentYear === $durationInYears) {
				return round($annuity * ($durationInYears === 1 ? $prorata : 1), 2);
			}

			$remainingValue -= $annuity;

		}

		throw new \NotExpectedAction('Unable to calculate amortization for asset '.$eAsset['id']);
	}*/

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

		$amortizationValue = self::computeAmortizationUntil($eAsset, $endDate);
		$hash = \journal\OperationLib::generateHash().'i';

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

		// Étape 2 : Amortissement, on crédite 28XXXXXX
		$values = self::getAmortizationOperationValues($eFinancialYear, $eAsset, $endDate, $amortizationValue);
		$values['hash'] = $hash;

		if($amortizationValue !== 0.0) {
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

		// Étape 4 : Si l'immobilisation a été entièrement amortie ou n'est plus valide
		$amortizedValue = Amortization::model()
	    ->whereAsset($eAsset)
	    ->getValue(new \Sql('SUM(amount)', 'float'));

		if($eAsset['endDate'] <= $endDate or $amortizedValue >= $eAsset['value']) {
			Asset::model()->update(
				$eAsset,
				['status' => Asset::ENDED, 'updatedAt' => new \Sql('NOW()')],
			);
		}

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
				'financialYearDiminution' => 0,
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
			'subvention' => AssetLib::getGrantsByFinancialYear($eFinancialYear),
		};

		$ccAmortization = Amortization::model()
			->select(['asset', 'financialYear', 'amount', 'type'])
			->whereAsset('IN', $cAsset)
			->whereDate('<=', $eFinancialYear['endDate'])
			->getCollection(NULL, NULL, ['asset', 'financialYear']);

		$amortizations = [];

		foreach($cAsset as $eAsset) {

			$accountLabel = $eAsset['accountLabel'];

			$cAmortization = $ccAmortization->offsetExists($eAsset['id']) ? $ccAmortization->offsetGet($eAsset['id']) : new \Collection();

			// sum what has already been amortized for this asset (during previous financial years)
			$alreadyAmortized = array_reduce(
				$cAmortization->getArrayCopy(),
				fn($res, $eAmortization) => $res + (($eAmortization['financialYear']['id'] !== $eFinancialYear['id'] and $eAmortization['type'] === Amortization::ECONOMIC) ? $eAmortization['amount'] : 0), 0,
			);
			$alreadyExcessAmortized = array_reduce(
				$cAmortization->getArrayCopy(),
				fn($res, $eAmortization) => $res + (($eAmortization['financialYear']['id'] !== $eFinancialYear['id'] and $eAmortization['type'] === Amortization::EXCESS) ? $eAmortization['amount'] : 0), 0,
			);

			// This financial year amortization
			if($eFinancialYear['status'] === \account\FinancialYearElement::CLOSE) {

				$currentAmortization = ($cAmortization->offsetExists($eFinancialYear['id']) and $cAmortization[$eFinancialYear['id']]['type'] === Amortization::ECONOMIC) ? $cAmortization[$eFinancialYear['id']]['amount'] : 0;
				$currentExcessAmortization = ($cAmortization->offsetExists($eFinancialYear['id']) and $cAmortization[$eFinancialYear['id']]['type'] === Amortization::EXCESS) ? $cAmortization[$eFinancialYear['id']]['amount'] : 0;

			} else {

				// Estimate
				$currentAmortization = self::computeAmortizationUntil($eAsset, $eFinancialYear['endDate']);
				// TODO dérogatoire
				$currentExcessAmortization = 0;

			}

			$financialYearDiminution = 0; // TODO saykoi ?

			$vnc = $eAsset['value'] - $alreadyAmortized - $currentAmortization;
			$vnf = $currentExcessAmortization > 0 ? $eAsset['value'] - $alreadyExcessAmortized - $currentExcessAmortization : $vnc;

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
					'startFinancialYearValue' => $eAsset['startDate'] >= $eFinancialYear['startDate'] ? NULL : $eAsset['value'] - $alreadyAmortized,
					'currentFinancialYearAmortization' => $currentAmortization,
					'currentFinancialYearDegressiveAmortization' => 0,
					'financialYearDiminution' => $financialYearDiminution,
					'endFinancialYearValue' => $currentAmortization - $financialYearDiminution,
				],

				// Diminution de valeur brut
				'grossValueDiminution' => 0, // TODO

				// VNC
				'netFinancialValue' => $vnc,

				// Excess amortization (amortissement dérogatoire)
				'excess' => [
					'startFinancialYearValue' => 0,
					'currentFinancialYearAmortization' => $currentExcessAmortization,
					'reversal' => 0,
					'endFinancialYearValue' => $currentExcessAmortization,
				],

				// VNF
				'fiscalNetValue' => $vnf,


			];

			$amortizations[] = $amortization;

		}

		return $amortizations;

	}

}
?>
