<?php
namespace overview;

Class VatLib {

	/**
	 * Récupère la période par défaut de la déclaration de TVA
	 * (celle qui est actuellement modifiable ou la dernière déclarée)
	 * Cette période doit être calculée par rapport à la date actuelle et à la fréquence de TVA.
	 * - Si annuelle => 01/01/N-1 au 31/12/N-1
	 * - Si mensuelle => 01/M-1/N au 30|31/M-1/N
	 * - Si trimestrielle => dernier trimestre complet (trimestre = 01 à 03, 04 à 06, 07 à 09, 10 à 12)
	 *
	 * @param \account\FinancialYear $eFinancialYear
	 * @return array
	 */
	public static function getDefaultPeriod(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): array {

		$allPeriods = self::getAllPeriodForFinancialYear($eFarm, $eFinancialYear);

		if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {
			return first($allPeriods);
		}

		$last = NULL;
		foreach($allPeriods as $period) {
			$eVatDeclaration = new VatDeclaration($period);
			if($eVatDeclaration->canUpdate()) {
				return $period;
			}
			if($period['from'] < date('Y-m-d')) {
				$last = $period;
			}
		}

		// On n'a pas trouvé de période modifiable => On prend la dernière de l'exercice
		if($last !== NULL) {
			return last($period);
		}

		// On n'a pas trouvé de période déjà passée => On prend la première de l'exercice
		return first($period);

	}

	public static function extractCurrentPeriod(array $allPeriods): array {

		$currentPeriod = [];
		$currentDate = date('Y-m-d');

		foreach($allPeriods as $period) {
			if($period['to'] < $currentDate) {
				if(empty($currentPeriod) or $currentPeriod['to'] < $period['to']) {
					$currentPeriod = $period;
				}
			}
		}

		if(empty($currentPeriod)) {
			return first($allPeriods);
		}
		return $currentPeriod;

	}

	public static function getAllPeriodForFinancialYear(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): array {

		if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {
			$period = self::getVatDeclarationParameters($eFarm, $eFinancialYear, $eFinancialYear['startDate']);
			return [$period['from'].'|'.$period['to'] => $period];
		}

		if($eFarm->getConf('vatFrequency') === \farm\Configuration::QUARTERLY) {
			$monthsPerPeriod = 3;
			$totalPeriods = 4;
		} else {
			$monthsPerPeriod = 1;
			$totalPeriods = 12;
		}

		$periods = [];
		$referenceDate = $eFinancialYear['startDate'];
		for($i = 0; $i < $totalPeriods; $i++) {
			$date = mb_substr($referenceDate, 0, 5) // YEAR
				.mb_str_pad(((int)mb_substr($referenceDate, 5, 2)), 2, '0', STR_PAD_LEFT) // MONTH
 				.mb_substr($referenceDate, -3) // DAY
			;
			$date = date('Y-m-d', strtotime($date.' +'.($i * $monthsPerPeriod).' month'));
			$period = self::getVatDeclarationParameters($eFarm, $eFinancialYear, $date);
			$periods[$period['from'].'|'.$period['to']] = $period;
		}

		return $periods;

	}

	public static function getClosestVatRate(\farm\Farm $eFarm, float $vatRate) {

		$vatRates = \selling\SellingSetting::getVatRates($eFarm);

		if(in_array($vatRate, $vatRates)) {
			return $vatRate;
		}

		$closestVatRate = NULL;
		$difference = NULL;

		foreach($vatRates as $legalVatRate) {

			if($closestVatRate === NULL) {

				$closestVatRate = $legalVatRate;
				$difference = abs($vatRate - $legalVatRate);

			}

			if($difference > abs($vatRate - $legalVatRate)) {

				$closestVatRate = $legalVatRate;
				$difference = abs($vatRate - $legalVatRate);

			}

		}

		return $closestVatRate;

	}

	public static function getTurnoverOperations(\Search $search = new \Search()): \Collection {

		return \journal\OperationLib::applySearch($search)
			->select([
				'compte' => new \Sql('SUBSTRING(accountLabel, 1, 2)', 'int'),
				'vatRate',
				'amount' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float'),
			])
			->whereVatRule(\journal\Operation::VAT_STD)
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ESCOMPTES_ACCOUNT_CLASS.'%'),
			)
			->group(['compte', 'vatRate'])
			->getCollection();

	}

	public static function getForCheck(\farm\Farm $eFarm, \Search $search = new \Search()): array {

		// Ligne A1 - Ventes (70* - 709 - 665) : VAT_STD
		$cOperationVentes = self::getTurnoverOperations($search);

		// Regroupement par taux de TVA
		$sales = [];
		foreach($cOperationVentes as &$eOperationVentes) {

			$closestVatRate = self::getClosestVatRate($eFarm, $eOperationVentes['vatRate']);
			$key = (string)$closestVatRate;
			if(isset($sales[$key]) === FALSE) {
				$sales[$key] = [
					'vatRate' => $eOperationVentes['vatRate'],
					'amount' => 0,
					'tax' => 0,
				];
			}
			$sales[$key]['amount'] += $eOperationVentes['amount'];
			$sales[$key]['tax'] += round($eOperationVentes['amount'] * $closestVatRate / 100, 2);
		}

		// Récupération des TVA enregistrées
		$cOperationTaxes = \journal\OperationLib::applySearch($search)
			->select([
				'compte' => new \Sql('TRIM(BOTH "0" FROM accountLabel)', 'int'),
				'amount', 'type',
				'operation' => ['vatRate'],
			])
			->whereVatRule(\journal\Operation::VAT_STD)
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_BUY_CLASS_PREFIX.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_SELL_CLASS_PREFIX.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS.'%'),
			)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::VAT_CREDIT_CLASS.'%')
			->whereOperation('!=', NULL)
			->getCollection();

		$taxes = [];
		foreach($cOperationTaxes as $eOperation) {

			$closestVatRate = self::getClosestVatRate($eFarm, $eOperation['operation']['vatRate']);
			$key = (string)$closestVatRate;

			if(isset($taxes[$eOperation['compte']]) === FALSE) {
				$taxes[$eOperation['compte']] = [];
			}
			if(isset($taxes[$eOperation['compte']][$key]) === FALSE) {
				$taxes[$eOperation['compte']][$key] = [
					'account' => $eOperation['compte'],
					'vatRate' => $eOperation['operation']['vatRate'],
					'amount' => 0,
 				];

			}

			if(mb_strpos($eOperation['compte'], (string)\account\AccountSetting::VAT_BUY_CLASS_PREFIX) !== FALSE) {
				if($eOperation['type'] === \journal\Operation::DEBIT) {
					$taxes[$eOperation['compte']][$key]['amount'] += $eOperation['amount'];
				} else {
					$taxes[$eOperation['compte']][$key]['amount'] -= $eOperation['amount'];
				}
			} else if(mb_strpos($eOperation['compte'], (string)\account\AccountSetting::VAT_SELL_CLASS_PREFIX) !== FALSE) {
				if($eOperation['type'] === \journal\Operation::CREDIT) {
					$taxes[$eOperation['compte']][$key]['amount'] += $eOperation['amount'];
				} else {
					$taxes[$eOperation['compte']][$key]['amount'] -= $eOperation['amount'];
				}
			} // On ne fait rien de la TVA intracom (autoliquidée)

		}

		// Récupération du crédit de TVA et des acomptes déjà versés
		$cOperationTaxes = \journal\OperationLib::applySearch($search)
			->select([
			  'compte' => new \Sql('SUBSTRING(accountLabel, 1, 5)', 'int'),
			  'amount' => new \Sql('SUM(IF(type="'.\journal\Operation::DEBIT.'", amount, -1 * amount))', 'float'),
			])
			->or(
			  fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_CREDIT_CLASS.'%'),
			  fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_DEPOSIT_CLASS.'%'),
			)
			->group('compte')
			->getCollection();

		return [
			'sales' => $sales,
			'taxes' => $taxes,
			'deposits' => [
				\account\AccountSetting::VAT_CREDIT_CLASS => $cOperationTaxes[\account\AccountSetting::VAT_CREDIT_CLASS]['amount'] ?? 0,
				\account\AccountSetting::VAT_DEPOSIT_CLASS => $cOperationTaxes[\account\AccountSetting::VAT_DEPOSIT_CLASS]['amount'] ?? 0,
			]
		];

	}

	/**
	 * Les dates ne dépendent pas de l'exercice comptable mais de l'année civile.
	 */
	public static function getVatDeclarationParameters(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $referenceDate): array {

		$year = (int)mb_substr($referenceDate, 0, 4);
		$month = (int)mb_substr($referenceDate, 5, 2);

		// On prend comme valeur de référence l'année précédente
		if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {

			// Date limite de déclaration pour la période de référence : relative à l'exercice courant
			$limitDate = date('Y-05-02', strtotime($eFinancialYear));

			// Dates de calcul de l'assiette d'imposition : relates à l'exercice précédent
			$eFinancialYearLast = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);

			if($eFinancialYearLast->empty()) {
				$referenceDate = date('Y-m-d', strtotime($eFinancialYear['startDate'].' - 1 YEAR'));
			} else {
				$referenceDate = $eFinancialYearLast['startDate'];
			}

			// On en déduit la période de déclaration
			$periodFrom = date('Y-01-01', strtotime($referenceDate));
			$periodTo = date('Y-12-31', strtotime($referenceDate));

		} else if($eFinancialYear['vatFrequency'] === \farm\Configuration::QUARTERLY) {

			$currentMonth = $month;

			if($currentMonth < 3) {
				$trimester = 1;
			} else if($currentMonth < 6) {
				$trimester = 2;
			} else if($currentMonth < 9) {
				$trimester = 3;
			} else if($currentMonth < 12) {
				$trimester = 4;
			}

			$periodFrom = date('Y-m-01', mktime(0, 0, 0, ($trimester - 1) * 3 + 1, 1, $year));
			$periodTo = date('Y-m-d', mktime(0, 0, 0, $trimester * 3 + 1, 0, $year));

		} else if($eFarm->getConf('vatFrequency') === \farm\Configuration::MONTHLY) {

			$periodFrom = mb_substr($referenceDate, 0, 8).'01';;
			$periodTo = date('Y-m-d', mktime(0, 0, 0, $month + 1, 0, $year));

		}

		switch($eFarm->getConf('vatFrequency')) {

			// Règle échéance annuelle : https://www.impots.gouv.fr/professionnel/questions/je-suis-soumis-au-regime-simplifie-dimposition-la-tva-quelle-echeance-dois
			case \farm\Configuration::ANNUALLY:

				if(mb_substr($eFinancialYear['endDate'], -5) === '12-31') {
					$nextYear = (int)mb_substr($periodTo, 0, 4) + 1;
					$firstDatetime = new \DateTime($nextYear.'-05-02');
					$isWorkingDay = FALSE;
					$foundOneWorkingDay = FALSE;

					while($isWorkingDay === FALSE or $foundOneWorkingDay === FALSE) {
						$day = (int)$firstDatetime->format('w');
						if($day < 6 and $day > 0 and in_array($firstDatetime->format('m-d'), ['05-01', '05-08']) === FALSE) {
							if($foundOneWorkingDay === FALSE) {
								$foundOneWorkingDay = TRUE;
								$firstDatetime->add(new \DateInterval('P1D'));
							} else {
								$isWorkingDay = TRUE;
							}
						} else {
							$firstDatetime->add(new \DateInterval('P1D'));
						}
					}

					$limitDate = $firstDatetime->format('Y-m-d');

				} else {

					$limitDatetime = new \DateTime($periodTo);
					$limitDatetime->add(new \DateInterval('P3M'));

					$limitDate = $limitDatetime->format('Y-m-d');

				}
				break;

			case \farm\Configuration::QUARTERLY:
			case \farm\Configuration::MONTHLY:
				$isRegionParisienne = in_array(mb_substr($eFarm['legalPostcode'], 0, 2), ['75', '92', '93', '94']);

				$firstLetter = mb_strtolower(mb_substr($eFarm['legalName'], 0, 1));

				if($isRegionParisienne) {
					if($eFinancialYear['legalCategory'] === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) { // Entrepreneur individuel
						if($firstLetter <= 'h') {
							$day = 15;
						} else {
							$day = 17;
						}
					} else if($eFinancialYear['legalCategory'] >= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['from'] and $eFinancialYear['legalCategory'] <= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['to']) { // Société anonyme
						if((int)mb_substr($eFarm['siret'], 0, 2) <= 74) {
							$day = 23;
						} else {
							$day = 24;
						}
					} else { // Autres sociétés
						if((int)mb_substr($eFarm['siret'], 0, 2) <= 68) {
							$day = 19;
						} else if((int)mb_substr($eFarm['siret'], 0, 2) <= 78) {
							$day = 20;
						} else {
							$day = 21;
						}
					} // Autres redevables : 24 (qui ?)
				} else { // Autres départements
					if($eFinancialYear['legalCategory'] === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) { // Entrepreneur individuel
						if($firstLetter <= 'h') {
							$day = 16;
						} else {
							$day = 19;
						}
					} else if($eFinancialYear['legalCategory'] >= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['from'] and $eFinancialYear['legalCategory'] <= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['to']) { // Société anonyme
						$day = 24;
					} else { // Autres sociétés
						$day = 21;
					} // Autres redevables : 24 (qui ?)

				}

				$limitDatetime = new \DateTime(mb_substr($periodTo, 0, 7).'-01');
				$limitDatetime->add(new \DateInterval('P1M'));

				$limitDate = $limitDatetime->format('Y-m').'-'.$day;
		}

		return [
			// Date limite
			'limit' => $limitDate,

			// Période déclarée
			'from' => $periodFrom,
			'to' => $periodTo,
		];

	}
	public static function getVatDataDeclaration(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Search $search = new \Search(), int $precision = 0): array {

		$checkData = self::getForCheck($eFarm, $search);
		$sales = $checkData['sales'];
		$taxes = $checkData['taxes'];
		$deposits = $checkData['deposits'];

		$vatData = [];

		// OPÉRATIONS NON TAXÉES

		// Autre opé non imposables VAT_0 (exonéré) / Filtré sur classe 7
		$eOperationAutreOperationsNonImposables = \journal\OperationLib::applySearch($search)
			->select([
				'amount' => new \Sql('ROUND(SUM(IF(type = "credit", amount, -1 * amount)))', 'float'),
			])
			->whereVatRule(\journal\Operation::VAT_0)
			->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%')
			->get();
		$vatData['0033'] = round($eOperationAutreOperationsNonImposables['amount'] ?? 0, $precision);

		// OPÉRATIONS TAXÉES

		// Ventes (comptes 70*) :
		// 0207 => Ventes à 20%
		$vatData['0207-base'] = round($sales[20]['amount'] ?? 0, $precision);
		$vatData['0207-tax'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['20']['amount'] ?? 0, $precision);
		// 0105 => Ventes à 5.5%
		$vatData['0105-base'] = round($sales['5.5']['amount'] ?? 0, $precision);
		$vatData['0105-tax'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['5.5']['amount'] ?? 0, $precision);
		// 0151 => Ventes à 10%
		$vatData['0151-base'] = round($sales['10']['amount'] ?? 0, $precision);
		$vatData['0151-tax'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['10']['amount'] ?? 0, $precision);

		// TVA s/ immos
		$vatData['0703'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_ASSET_CLASS] ?? [], 'amount')), $precision);

		// TVA due intracom (achat autoliquidé)
		$vatData['0044'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS] ?? [], 'amount')), $precision);
		$vatData['0044-tax'] = round($vatData['0044'] * 0.2, $precision);

		// TVA déductible intracom (achat auto liquidé)
		$vatData['0034'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS] ?? [], 'amount')), $precision);
		$vatData['0034-tax'] = round($vatData['0034'] * 0.2, $precision);

		// TVA s/ ABS
		$vatData['0702'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT] ?? [], 'amount')), $precision);

		$vatData['16-number'] = round(array_sum(array_filter($vatData, fn($item, $index) => in_array($index, ['0207-tax', '0208-tax', '0105-tax', '0151-tax', '0201-tax', '0100-tax', '0950-tax', '0152-tax', '0900-tax', '0030-tax', '0040-tax', '0044-tax', '0970-tax', '0980-tax', '0981-tax']), ARRAY_FILTER_USE_BOTH)), $precision);
		$vatData['19-number'] = round(($vatData['16-number'] ?? 0) + ($vatData['0983'] ?? 0) + ($vatData['0600'] ?? 0) + ($vatData['0602'] ?? 0), $precision);
		$vatData['22-number'] = round(($vatData['0702'] ?? 0) + ($vatData['0704'] ?? 0), $precision);
		$vatData['26-number'] = round($vatData['22-number'] + ($vatData['0703'] ?? 0) + ($vatData['0058'] ?? 0) + ($vatData['0059'] ?? 0) + ($vatData['0603'] ?? 0), $precision);

		if($vatData['19-number'] > $vatData['26-number']) {
			$vatData['8900'] = round($vatData['19-number'] - $vatData['26-number'], $precision);
		} else {
			$vatData['0705'] = round($vatData['26-number'] - $vatData['19-number'], $precision);
		}

		$cOperationDeposit = \journal\OperationLib::applySearch($search)
	     ->select([
				 'period' => new \Sql('SUBSTRING(date, 1, 7)'),
	       'amount' => new \Sql('ROUND(SUM(IF(type = "debit", amount, -1 * amount)))', 'float'),
	     ])
			->whereAccountLabel('LIKE', \account\AccountSetting::VAT_DEPOSIT_CLASS.'%')
			->group(['period'])
			->getCollection();
		$firstDeposit = 0;
		$lastDeposit = 0;
		foreach($cOperationDeposit as $eOperationDeposit) {
			if((int)mb_substr($eOperationDeposit['period'], -2) === 12) { // Acompte de décembre
				$lastDeposit += $eOperationDeposit['amount'];
			} else {
				$firstDeposit += $eOperationDeposit['amount'];
			}
		}

		$vatData['deposit[0][paid]'] = $firstDeposit;
		$vatData['deposit[0][not-paid]'] = 0;
		$vatData['deposit[1][paid]'] = $lastDeposit;
		$vatData['deposit[1][not-paid]'] = 0;
		$vatData['deposit[total][paid]'] = round($vatData['deposit[0][paid]'] + $vatData['deposit[1][paid]'], $precision);
		$vatData['deposit[total][not-paid]'] = round($vatData['deposit[0][not-paid]'] + $vatData['deposit[1][not-paid]']);
		$vatData['0018'] = round(-1 * $deposits[\account\AccountSetting::VAT_DEPOSIT_CLASS]);

		if(($vatData['8900'] ?? 0) >= ($vatData['0705'] ?? 0 + $vatData['0018'] ?? 0)) {
			$vatData['33-number'] = round(($vatData['8900'] ?? 0) - (($vatData['0705'] ?? 0) + ($vatData['0018'] ?? 0)), $precision);
		}
		if($vatData['0018'] ?? 0 >= $vatData['8900'] ?? 0) {
			$vatData['34-number'] = round(($vatData['0018'] ?? 0 - $vatData['8900'] ?? 0), $precision);
		}
		if(($vatData['0705'] ?? 0) > ($vatData['34-number'] ?? 0)) {
			$vatData['0020'] = round(($vatData['0705'] ?? 0 - $vatData['34-number'] ?? 0), $precision);
		}

		// Taxe ADAR (calcul pour valeur annuelle), calculée sur le dernier exercice clos
		// Calcul :
		// - partie forfaitaire = 90€ par exploitant $UNIT_BY_ASSOCIATE_ADAR_TAX
		// - partie variable : 0,19% ($RATE_1_ADAR) du CA jusqu'à 370k ($THRESHOLD_ADAR_TAX), 0,05% ($RATE_2_ADAR) au delà
		// si déclaration annuelle => tout le temps
		// si déclaration trimestrielle => 1er trimestre de l'exercice ou mois de mars
		// si déclaration mensuelle => 3è mois de l'exercice ou mois de mars
		// si exercice incomplet : calculer un prorata tempris de la partie forfaitaire et du seuil de 370k selon le nombre de jours
		$eFinancialYearLast = \account\FinancialYearLib::getPreviousFinancialYear($eFarm['eFinancialYear']);
		$adarTax = 0;

		if($eFinancialYearLast->notEmpty()) {

			$searchAdar = new \Search(['minDate' => $eFinancialYearLast['startDate'], 'maxDate' => $eFinancialYearLast['endDate'], 'financialYear' => new \account\FinancialYear()]);
			$turnover = self::getTurnoverOperations($searchAdar)->sum('amount');

			// Calcul du prorata
			$daysYear = (strtotime($eFinancialYearLast['endDate']) - strtotime($eFinancialYearLast['endDate'].' - 1 YEAR')) / 86400;
			$daysFinancialYear = ((strtotime($eFinancialYearLast['endDate']) - strtotime($eFinancialYearLast['startDate'])) / 86400 + 1);
			$prorata = $daysFinancialYear / $daysYear;

			// Pas de taxe ADAR l'année de création de l'exploitation
			$isNotCreationYear = ($eFarm['startedAt'] === NULL or $eFarm['startedAt'].'-12-31' < $eFinancialYearLast['startDate']);

			if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {

				$isInPeriod = TRUE;

			} else if($eFarm->getConf('vatFrequency') === \farm\Configuration::QUARTERLY) {

				$firstMonth = (int)mb_substr($search->get('minDate'), 6, 2);
				$hasMarchInTrimester = in_array($firstMonth, [1, 2, 3]);
				$isFirstTrimester = (int)mb_substr($eFinancialYearLast['startDate'], 6, 2);

				if(
					$eFinancialYearLast['startDate'] >= date('Y-04-01', strtotime($eFinancialYearLast['startDate']))
				) {
					$isInPeriod = $isFirstTrimester;
				} else {
					$isInPeriod = $hasMarchInTrimester;
				}

			} else { // Monthly

				// 3è mois de l'exercice :
				$thirdMonth = (int)mb_substr($eFinancialYear['startDate'], 6, 2) + 2;
				// Mois en cours de déclaration
				$currentMonth = (int)mb_substr($search['minDate'], 6, 2);

				// On prend le premier encore le 3è mois et le mois de mars
				$isInPeriod = ($currentMonth === min($thirdMonth, 3));

			}

			if($isNotCreationYear and $isInPeriod) {

	      $associates = min(1, $eFinancialYearLast['associates']); // Le nombre d'associés du dernier exercice, au moins 1
				$UNIT_BY_ASSOCIATE_ADAR_TAX = 90;
				$fixed = $UNIT_BY_ASSOCIATE_ADAR_TAX * $associates * $prorata;

				$RATE_1_ADAR = 0.19 / 100;
				$THRESHOLD_ADAR_TAX = 370000 * $prorata;
				$RATE_2_ADAR = 0.05 / 100;

				$adarTax = round($fixed + min($THRESHOLD_ADAR_TAX, $turnover) * $RATE_1_ADAR + max(0, $turnover - $THRESHOLD_ADAR_TAX) * $RATE_2_ADAR, $precision);

			}

		}

		$vatData['4220'] = $adarTax;

		// Taxes assimilées
		$taxesAssimilees = round(array_sum(array_filter($vatData, fn($index, $key) => in_array($key, ['4215', '4220', '4331', '4229', '4228', '4298', '4299', '4206', '4315', '4314', '4324', '4325', '4217', '4213', '4238', '4236', '4239', '4326', '4334', '4253', '4254', '4247', '4248', '4249', '4250', '4273', '4274', '4321', '4268', '4270', '4269', '4271', '4303', '4323', '4313', '4335', '4256', '4259', '4255', '4336', '4266', '4267', '4309', '4310', '4311', '4306', '4307', '4308', '4258', '4258', '4261', '4337', '4291', '4294', '4296', '4295', '4293', '4301', '4322']), ARRAY_FILTER_USE_BOTH)), $precision);
		$vatData['55-number'] = $taxesAssimilees;

		$vatData['Y4'] = 0;

		// Récapitulation
		$vatData['8901'] = round(($vatData['33-number'] ?? 0) - ($vatData['8103'] ?? 0), $precision);
		$vatData['9992'] = round($vatData['8901'] + $vatData['55-number'] + ($vatData['8123'] ?? 0), $precision);

		$vatData['57-number'] = max(0, round($vatData['16-number'] - (($vatData['0970'] ?? 0) + ($vatData['0980'] ?? 0) + $vatData['22-number']), $precision));

		// Achats intracom (4452 puis remonter sur l'opération initiale)


		return $vatData;

	}
	public static function getVatHistory(\account\FinancialYear $eFinancialYear): \Collection {

		return VatDeclaration::model()
			->select(VatDeclaration::getSelection())
			->whereFinancialYear($eFinancialYear)
			->sort(['from' => SORT_ASC, 'createdAt' => SORT_ASC])
			->getCollection();

	}

	/**
	 * Étape 1 - Débiter :
	 *    - 44571 (TVA collectée),
	 *    - 4452 (TVA due intracom autoliquidée),
	 *    - 44567 SI crédit de TVA
	 * puis ajuster 44571 / 4452 par 658 ou 758
	 *
	 * Étape 2 - Créditer :
	 *    - 44562 (TVA sur immos),
	 *    - 44566 (TVA déductible),
	 *    - 445662 (TVA déductible intracom),
	 *    - 44551 SI TVA à décaisser
	 * puis ajuster 44562 / 44566 / 445662 par 658 ou 758
	 *
	 */
	public static function generateOperationsFromDeclaration(\farm\Farm $eFarm, VatDeclaration $eVatDeclaration, \account\FinancialYear $eFinancialYear): array {

		$cOperation = new \Collection();

		// On part des écritures
		$search = new \Search([
			'financialYear' => new \account\FinancialYear(),
			'minDate' => $eVatDeclaration['from'],
			'maxDate' => $eVatDeclaration['to'],
		]);

		$cerfaFromOperations = self::getVatDataDeclaration($eFarm, $eFinancialYear, $search, precision: 2);

		// On récupère les données de la déclaration
		$cerfa = $eVatDeclaration['data'];

		$cAccount = \account\Account::model()
			->select(\account\Account::getSelection())
			->or(
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_BUY_CLASS_PREFIX.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_SELL_CLASS_PREFIX.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_DEBIT_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::CHARGES_OTHER_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::PRODUCT_OTHER_CLASS.'%'),
			)
			->getCollection(NULL, NULL, 'class');

		$eThirdParty = \account\ThirdPartyLib::getByName(VatUi::getTranslations('tresor-public'));
		$document = VatUi::getTranslations('document', ['from' => \util\DateUi::numeric($eVatDeclaration['from']), 'to' => \util\DateUi::numeric($eVatDeclaration['to'])]);

		// Étape 1
		// 44571 - TVA collectée
		$vat44571Declared = round($cerfa['19-number'], 2);
		$vat44571Calculated = round($cerfaFromOperations['19-number'], 2);
		if($vat44571Calculated > 0) {
			$eOperation44571 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT),
				'amount' => $vat44571Calculated,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations('tva-sur-ventes'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT, $eOperation44571);
		}

		// 4452 - TVA due intracom
		$vat4452Declared = round($cerfa['0044'], 2);
		$vat4452Calculated = round($cerfaFromOperations['0044-tax'], 2);
		if($vat4452Calculated > 0) {
			$eOperation44571 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS),
				'amount' => $vat4452Calculated,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations('tva-sur-ventes'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS, $eOperation44571);
		}

		// Solde : crédit de TVA
		$amountVatCredit = $cerfa['0020'];
		if($amountVatCredit > 0) {
			$eOperation44567 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_CREDIT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_CREDIT_CLASS),
				'amount' => $amountVatCredit,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations('tva-credit'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_CREDIT_CLASS, $eOperation44567);
		}

		// Étape 2 | Créditer : 44562 (TVA sur immos), 44566 (TVA déductible), 445662 (TVA déductible intracom), 44551 SI TVA à décaisser + ajuster 44562 / 44566 / 445662 par 658 ou 758

		// TVA sur immos
		$vat44562Declared = round($cerfa['0703'], 2);
		$vat44562Calculated = round($cerfaFromOperations['0703'], 2);
		if($vat44562Calculated > 0) {
			$eOperation44562 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_ASSET_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_ASSET_CLASS),
				'amount' => $vat44562Calculated,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations('tva-versee'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_ASSET_CLASS, $eOperation44562);
		}

		// TVA sur ABS
		$vat44566Declared = round($cerfa['0702'], 2);
		$vat44566Calculated = round($cerfaFromOperations['0702'], 2);
		if($vat44566Calculated > 0) {
			$eOperation44566 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT),
				'amount' => $vat44566Calculated,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations('tva-versee'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT, $eOperation44566);
		}

		// TVA déductible intracom (on ne déclare que le montant total sur le CERFA, pas la taxe due)
		$vat445662Declared = round($cerfa['0034'], 2) * 0.3;
		$vat445662Calculated = round($cerfaFromOperations['0034'], 2) * 0.2;
		if($vat445662Calculated > 0) {
			$eOperation445662 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS),
				'amount' => $vat445662Calculated,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations('tva-versee'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS, $eOperation445662);
		}

		// Solde : TVA à décaisser
		$amountVatToPay = $cerfaFromOperations['8901'];
		if($amountVatToPay > 0) {
			$eOperation44551 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_DEBIT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEBIT_CLASS),
				'amount' => $amountVatToPay,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations('tva-debit'),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_DEBIT_CLASS, $eOperation44551);
		}

		$differenceDeclaredAndCalculated = round(
			$vat44562Declared + $vat44566Declared + $vat445662Declared + $vat44571Calculated + $vat4452Calculated
			- $vat44562Calculated - $vat44566Calculated - $vat445662Calculated - $vat44571Declared - $vat4452Declared,
			2);

		// Si on a déclaré + => Gain (658) sinon => Perte (758)
		if($differenceDeclaredAndCalculated > 0) {
			$class = \account\AccountSetting::PRODUCT_OTHER_CLASS;
			$type = \journal\Operation::CREDIT;
		} else {
			$class = \account\AccountSetting::CHARGES_OTHER_CLASS;
			$type = \journal\Operation::DEBIT;
		}
		$eOperationDifference = new \journal\Operation([
			'account' => $cAccount[$class],
			'accountLabel' => \account\AccountLabelLib::pad($class),
			'amount' => abs($differenceDeclaredAndCalculated),
			'type' => $type,
			'description' => VatUi::getTranslations('tva-versee'),
			'thirdParty' => $eThirdParty,
			'document' => $document,
		]);
		$cOperation->append($eOperationDifference);

		return [
			'cerfaCalculated' => $cerfaFromOperations,
			'cerfaDeclared' => $cerfa,
			'cOperation' => $cOperation,
		];

	}
	public static function createOperations(\farm\Farm $eFarm, VatDeclaration $eVatDeclaration, \account\FinancialYear $eFinancialYear): void {

		$data = self::generateOperationsFromDeclaration($eFarm, $eVatDeclaration, $eFinancialYear);

		$eFinancialYear['status'] = \account\FinancialYear::OPEN; // pour pouvoir gérer les écritures
		$input = [
			'financialYear' => $eFinancialYear['id'],
			'account' => [],
			'accountLabel' => [],
			'description' => [],
			'amount' => [],
			'type' => [],
			'document' => [],
			'vatRate' => [],
			'comment' => [],
			'thirdParty' => [],
		];

		$index = 0;
		foreach($data['cOperation'] as $eOperation) {

			$input['account'][$index] = $eOperation['account']['id'];
			$input['thirdParty'][$index] = $eOperation['thirdParty']['id'];
			$input['date'][$index] = $eFinancialYear['endDate'];
			$input['paymentDate'][$index] = $eFinancialYear['endDate'];

			foreach(['accountLabel', 'description', 'amount', 'type', 'document'] as $field) {
				$input[$field][$index] = $eOperation[$field];
			}

			$index++;
		}

		$fw = new \FailWatch();

		\journal\Operation::model()->beginTransaction();

		\journal\OperationLib::prepareOperations($eFarm, $input);

		if($fw->ko()) {
			\journal\Operation::model()->rollBack();
		} else {
			\journal\Operation::model()->commit();
		}

		$fw->validate();

		if($fw->ok()) {

			VatDeclaration::model()
				->update($eVatDeclaration, [
					'accountedAt' => new \Sql('NOW()'),
					'accountedBy' => \user\ConnectionLib::getOnline(),
					'updatedAt' => new \Sql('NOW()'),
					'updatedBy' => \user\ConnectionLib::getOnline()
				]);

		}

	}
}

?>
