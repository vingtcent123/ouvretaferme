<?php
namespace asset;

class AmortizationLib extends \asset\AmortizationCrud {

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

	/**
	 * Calcule l'amortissement de l'immobilisation entre 2 dates
	 *
	 * @param string $startDate
	 * @param string $endDate
	 * @param Asset $eAsset
	 * @return float
	 */
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
				$currentAmortization = self::calculateAmortization($eFinancialYear['startDate'], $eFinancialYear['endDate'], $eAsset);
				$currentExcessAmortization = self::computeCurrentFinancialYearExcessAmortization($eAsset, $alreadyExcessAmortized, $eFinancialYear);

			}

			$financialYearDiminution = 0; // TODO

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
