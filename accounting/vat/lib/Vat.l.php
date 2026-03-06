<?php
namespace vat;

Class VatLib {

	public static function getPeriodForDates(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $from, string $to): ?array {

		$allPeriods = self::getAllPeriodForFinancialYear($eFarm, $eFinancialYear);
		if(isset($allPeriods[$from.'|'.$to])) {
			return $allPeriods[$from.'|'.$to];
		}

		return NULL;
	}

	public static function extractCurrentPeriod(array $allPeriods, string $from): array {

		$currentPeriod = [];
		$currentDate = date('Y-m-d');

		foreach($allPeriods as $period) {

			if($period['from'] === $from) {
				return $period;
			}

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
			->or(
				// Soit c'est de la TVA standard => on doit vérifier les numéros de compte
				fn() => $this
					->or(
						fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%'),
						fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ESCOMPTES_ACCOUNT_CLASS.'%'),
					)
					->whereVatRule(\journal\Operation::VAT_STD),
				// Soit c'est de la TVA "forcée" collectée => On prend sans vérifier
				fn() => $this->whereVatRule(\journal\Operation::VAT_STD_COLLECTED),
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
				'operation' => ['vatRate', 'accountLabel', 'asset'],
			])
			->whereVatRule('IN', [\journal\Operation::VAT_STD, \journal\Operation::VAT_STD_COLLECTED, \journal\Operation::VAT_STD_DEDUCTIBLE])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_BUY_CLASS_PREFIX.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_SELL_CLASS_PREFIX.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_ASSET_CLASS.'%'),
			)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::VAT_CREDIT_CLASS.'%')
			->whereOperation('!=', NULL)
			->getCollection();

		$taxes = [];
		foreach($cOperationTaxes as $eOperation) {

			$closestVatRate = self::getClosestVatRate($eFarm, $eOperation['operation']['vatRate']);
			$key = (string)$closestVatRate;

			$compte = $eOperation['compte'];

			if(isset($taxes[$compte]) === FALSE) {
				$taxes[$compte] = [];
			}

			if(isset($taxes[$compte][$key]) === FALSE) {

				$taxes[$compte][$key] = [
					'account' => $compte,
					'vatRate' => $eOperation['operation']['vatRate'],
					'amount' => 0,
 				];

			}

			// Achat = debit - credit
			// Vente = credit - debit
			if(
				\account\AccountLabelLib::isFromClass($compte, \account\AccountSetting::VAT_SELL_CLASS_PREFIX) or
					\account\AccountLabelLib::isFromClass($compte, \account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS)
			) {
				if($eOperation['type'] === \journal\Operation::CREDIT) {
					$taxes[$compte][$key]['amount'] += $eOperation['amount'];
				} else {
					$taxes[$compte][$key]['amount'] -= $eOperation['amount'];
				}
			} else {
				if($eOperation['type'] === \journal\Operation::DEBIT) {
					$taxes[$compte][$key]['amount'] += $eOperation['amount'];
				} else {
					$taxes[$compte][$key]['amount'] -= $eOperation['amount'];
				}
			}
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

		// On prend comme valeur de référence l'année courante
		if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {

			// Date limite de déclaration pour la période de référence : relative à l'exercice suivant
			$limitDate = date('Y-05-02', strtotime($eFinancialYear['startDate'].' + 1 year'));

			$referenceDate = $eFinancialYear['startDate'];

			// On en déduit la période de déclaration
			$periodFrom = date('Y-01-01', strtotime($referenceDate));
			$periodTo = date('Y-12-31', strtotime($referenceDate));

		} else if($eFarm->getConf('vatFrequency') === \farm\Configuration::QUARTERLY) {

			$currentMonth = $month;

			if($currentMonth <= 3) {
				$trimester = 1;
			} else if($currentMonth <= 6) {
				$trimester = 2;
			} else if($currentMonth <= 9) {
				$trimester = 3;
			} else if($currentMonth <= 12) {
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
	public static function getVatDataDeclaration(\farm\Farm $eFarm, \Search $search = new \Search(), int $precision = 0): array {

		$checkData = self::getForCheck($eFarm, $search);
		$sales = $checkData['sales'];
		$taxes = $checkData['taxes'];

		$cerfa = \vat\DeclarationLib::getCerfaFromFrequency($eFarm->getConf('vatFrequency'));

		$vatData = [];

		// OPÉRATIONS NON TAXÉES
		$vatData['0037'] = RawLib::dutyFreePurchase($search, $precision);
		$vatData['0032'] = RawLib::exportations($search, $precision);
		$vatData['0034'] = RawLib::intracom($search, $precision);
		$vatData['0033'] = RawLib::otherNonTaxable($search, $precision);

		// OPÉRATIONS TAXÉES

		// Ventes (comptes 70*) :
		if($cerfa === \vat\Declaration::CA3) {

			$vatData['0979'] = round($sales['20']['amount'] ?? 0 + $sales['5.5']['amount'] ?? 0 + $sales['10']['amount'] ?? 0, $precision);
			$vatData['0035'] = round(
				($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['20']['amount'] ?? 0)
				+ ($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['5.5']['amount'] ?? 0)
				+ ($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['10']['amount'] ?? 0),
				$precision
			);

		}

		// 0207 => Ventes à 20%
		$vatData['0207-base'] = round(($sales['20']['amount'] ?? 0), $precision);
		$vatData['0207'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['20']['amount'] ?? 0, $precision)
			+ round($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['20']['amount'] ?? 0, $precision);

			// 0105 => Ventes à 5.5%
		$vatData['0105-base'] = round(($sales['5.5']['amount'] ?? 0), $precision);
		$vatData['0105'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['5.5']['amount'] ?? 0, $precision)
			+ round($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['5.5']['amount'] ?? 0, $precision);

		// 0151 => Ventes à 10%
		$vatData['0151-base'] = round(($sales['10']['amount'] ?? 0), $precision);
		$vatData['0151'] = round($taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT]['10']['amount'] ?? 0, $precision)
			+ round($taxes[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS]['10']['amount'] ?? 0, $precision);

		// 0970 => Cessions d'immo
		$vatData['0970-base'] = RawLib::assetDisposal($search, $precision); // Base
		$vatData['0970'] = RawLib::assetDisposalTax($search, $precision); // Montant de TVA

		// TVA DEDUCTIBLE

		// TVA déductible s/ immos
		$vatData['0703'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_ASSET_CLASS] ?? [], 'amount')), $precision);

		// TVA s/ ABS
		$vatData['0702'] = round(array_sum(array_column($taxes[\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT] ?? [], 'amount')), $precision);

		$eDeclarationPrevious = DeclarationLib::getPrevious(new Declaration(['from' => $search->get('minDate')]));

		if($cerfa === \vat\Declaration::CA3) {

			// Report du dernier crédit de TVA
			if($eDeclarationPrevious->notEmpty() and $eDeclarationPrevious['status'] !== Declaration::DRAFT) {
				$vatData['8001'] = $eDeclarationPrevious['data']['8003'];
			}

			$vatData['16-number'] = round(array_sum(array_filter($vatData, fn($item, $index) => in_array($index, ['0207', '0105', '0151', '0201', '0100', '1120', '1110', '1090', '1081', '1050', '1040', '1010', '0990', '0900', '0208', '0152', '0210', '0211', '0212', '0213', '0214', '0215', '0600', '0602']), ARRAY_FILTER_USE_BOTH)), $precision);

			$vatData['23-number'] = round(($vatData['0703'] ?? 0) + ($vatData['0720'] ?? 0) + ($vatData['0059'] ?? 0) + ($vatData['8001'] ?? 0) + ($vatData['0603'] ?? 0), $precision);

			if($vatData['16-number'] > $vatData['23-number']) {
				$vatData['8900'] = round($vatData['16-number'] - $vatData['23-number'], $precision);
			} else {
				$vatData['0705'] = round($vatData['23-number'] - $vatData['16-number'], $precision);
			}

		} else {

			// Report du dernier crédit de TVA
			if($eDeclarationPrevious->notEmpty() and $eDeclarationPrevious['status'] !== Declaration::DRAFT) {
				$vatData['0058'] = $eDeclarationPrevious['data']['8003'];
			}

			$vatData['16-number'] = round(array_sum(array_filter($vatData, fn($item, $index) => in_array($index, ['0207', '0208', '0105', '0151', '0201', '0100', '0950', '0152', '0900', '0030', '0040', '0044', '0970', '0980', '0981']), ARRAY_FILTER_USE_BOTH)), $precision);
			$vatData['19-number'] = round(($vatData['16-number'] ?? 0) + ($vatData['0983'] ?? 0) + ($vatData['0600'] ?? 0) + ($vatData['0602'] ?? 0), $precision);

			$vatData['22-number'] = round(($vatData['0702'] ?? 0) + ($vatData['0704'] ?? 0), $precision);
			$vatData['26-number'] = round($vatData['22-number'] + ($vatData['0703'] ?? 0) + ($vatData['0058'] ?? 0) + ($vatData['0059'] ?? 0) + ($vatData['0603'] ?? 0), $precision);

			if($vatData['19-number'] > $vatData['26-number']) {
				$vatData['8900'] = round($vatData['19-number'] - $vatData['26-number'], $precision);
			} else {
				$vatData['0705'] = round($vatData['26-number'] - $vatData['19-number'], $precision);
			}

		}

		// Acomptes de TVA : on ne fait pas le calcul

		if(($vatData['8900'] ?? 0) >= (($vatData['0705'] ?? 0) + ($vatData['0018'] ?? 0))) {
			$vatData['33-number'] = round(($vatData['8900'] ?? 0) - (($vatData['0705'] ?? 0) + ($vatData['0018'] ?? 0)), $precision);
		}
		if($vatData['0018'] ?? 0 >= ($vatData['8900'] ?? 0)) {
			$vatData['34-number'] = round(($vatData['0018'] ?? 0) - ($vatData['8900'] ?? 0), $precision);
		}
		if(($vatData['0705'] ?? 0) > ($vatData['34-number'] ?? 0)) {
			$vatData['0020'] = round(($vatData['0705'] ?? 0 - $vatData['34-number'] ?? 0), $precision);
		}

		// Taxe ADAR : on ne fait pas le calcul
		$adarTax = 0;

		$vatData['4220'] = $adarTax;

		// Taxes assimilées
		$taxesAssimilees = round(array_sum(array_filter($vatData, fn($index, $key) => in_array($key, ['4215', '4220', '4331', '4229', '4228', '4298', '4299', '4206', '4315', '4314', '4324', '4325', '4217', '4213', '4238', '4236', '4239', '4326', '4334', '4253', '4254', '4247', '4248', '4249', '4250', '4273', '4274', '4321', '4268', '4270', '4269', '4271', '4303', '4323', '4313', '4335', '4256', '4259', '4255', '4336', '4266', '4267', '4309', '4310', '4311', '4306', '4307', '4308', '4258', '4258', '4261', '4337', '4291', '4294', '4296', '4295', '4293', '4301', '4322']), ARRAY_FILTER_USE_BOTH)), $precision);
		$vatData['55-number'] = $taxesAssimilees;

		$vatData['Y4'] = 0;

		// Récapitulation
		$vatData['8901'] = round(($vatData['33-number'] ?? 0) - ($vatData['8103'] ?? 0), $precision);
		$vatData['9992'] = round($vatData['8901'] + $vatData['55-number'] + ($vatData['8123'] ?? 0), $precision);

		if($cerfa === \vat\Declaration::CA12) {
			$vatData['57-number'] = max(0, round($vatData['16-number'] - (($vatData['0970'] ?? 0) + ($vatData['0980'] ?? 0) + $vatData['22-number']), $precision));
		}

		$vatData['reimburse-a'] = ($vatData['0705'] ?? 0);
		$vatData['reimburse-b'] = ($vatData['34-number'] ?? 0);
		$vatData['reimburse-c'] = $vatData['reimburse-a'] + $vatData['reimburse-b'];
		$vatData['reimburse-e'] = $vatData['reimburse-c'] + ($vatData['reimburse-d'] ?? 0);

		// Les reports
		$vatData['8002'] = ($vatData['reimburse-d'] ?? 0);
		$vatData['8003'] = ($vatData['0020'] ?? 0) - $vatData['8002']; // 0020 est reporté dans la ligne 49 qui doit être la base de ce calcul

		return $vatData;

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
	public static function generateOperationsFromDeclaration(\farm\Farm $eFarm, \vat\Declaration $eDeclaration): array {

		$cOperation = new \Collection();

		// On part des écritures
		$search = new \Search([
			'financialYear' => new \account\FinancialYear(),
			'minDate' => $eDeclaration['from'],
			'maxDate' => $eDeclaration['to'],
		]);

		$cerfaFromOperations = self::getVatDataDeclaration($eFarm, $search, precision: 2);

		// On récupère les données de la déclaration
		$cerfa = $eDeclaration['data'];

		$cAccount = \account\Account::model()
			->select(\account\Account::getSelection())
			->or(
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_BUY_CLASS_PREFIX.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_SELL_CLASS_PREFIX.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_DEBIT_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::CHARGES_OTHER_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::PRODUCT_OTHER_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::VAT_REIMBURSE_CLASS.'%'),
				fn() => $this->whereClass('LIKE', \account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS.'%'),
			)
			->getCollection(index: 'class');

		$eThirdParty = \account\ThirdPartyLib::getByName(VatUi::getTranslations('tresor-public'));
		if($eThirdParty->empty()) {
			$eThirdParty = new \account\ThirdParty(['name' => VatUi::getTranslations('tresor-public')]);
			\account\ThirdPartyLib::create($eThirdParty);
		}

		$document = VatUi::getTranslations('document', ['from' => \util\DateUi::numeric($eDeclaration['from']), 'to' => \util\DateUi::numeric($eDeclaration['to'])]);

		// Étape 1
		$vat4452Calculated = RawLib::dueTaxIntracom($search, 2);

		// 44571 - TVA collectée : on retire la TVA intracom qui sera soldée autrement
		if($eDeclaration['cerfa'] === \vat\Declaration::CA3) {

			$vat44571Declared = round($cerfa['16-number'] - $cerfa['0035'], 2);
			$vat44571Calculated = round($cerfaFromOperations['16-number'] - $cerfaFromOperations['0035'], 2);

		} else {

			$vat44571Declared = round($cerfa['19-number'] - $vat4452Calculated, 2);
			$vat44571Calculated = round($cerfaFromOperations['19-number'] - $vat4452Calculated, 2);

		}

		if($vat44571Calculated > 0) {
			$eOperation44571 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT),
				'amount' => $vat44571Calculated,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT, $eOperation44571);
		}

		// 4452 - TVA due intracom, juste à solder car déjà incluse dans
		$vat4452Declared = round($cerfa['0035'] ?? 0, 2);
		if($eDeclaration['cerfa'] === \vat\Declaration::CA3 and $vat4452Calculated > 0) {

			$eOperation44571 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS),
				'amount' => $vat4452Calculated,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS, $eOperation44571);

		}

		// Solde : crédit de TVA
		$amountVatCredit = $cerfa['8003'];
		if($amountVatCredit > 0) {
			$eOperation44567 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_CREDIT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_CREDIT_CLASS),
				'amount' => $amountVatCredit,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_CREDIT_CLASS),
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
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_ASSET_CLASS),
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
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT, $eOperation44566);
		}

		// TVA déductible intracom
		$vat445662Calculated = RawLib::deductibleTaxIntracom($search, 2);
		if($vat445662Calculated !== $vat4452Calculated) { // Les écritures ne sont pas équilibrées ! informer l'utilisateur

		}
		if($vat445662Calculated > 0) {
			$eOperation445662 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS),
				'amount' => $vat445662Calculated,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS, $eOperation445662);
		}

		// Acomptes de TVA
		if($eDeclaration['cerfa'] === \vat\Declaration::CA12) {
			$vat44581Declared = round($cerfa['0018'], 2);
			$vat44581Calculated = RawLib::deposit($search, 2);
			if($vat44581Calculated > 0) {
				$eOperation44581 = new \journal\Operation([
					'account' => $cAccount[\account\AccountSetting::VAT_DEPOSIT_CLASS],
					'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEPOSIT_CLASS),
					'amount' => $vat44581Calculated,
					'type' => \journal\Operation::CREDIT,
					'description' => VatUi::getTranslations(\account\AccountSetting::VAT_DEPOSIT_CLASS),
					'thirdParty' => $eThirdParty,
					'document' => $document,
				]);
				$cOperation->offsetSet(\account\AccountSetting::VAT_DEPOSIT_CLASS, $eOperation44581);
			}
		}

		// Solde : TVA à décaisser
		$amountVatToPay = $cerfa['8901'];
		if($amountVatToPay > 0) {
			$eOperation44551 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_DEBIT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEBIT_CLASS),
				'amount' => $amountVatToPay,
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_DEBIT_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_DEBIT_CLASS, $eOperation44551);
		}

		// Remboursement demandé
		$vat44583Declared = round($cerfa['8002'], 2);
		if($vat44583Declared > 0) {
			$eOperation44583 = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_REIMBURSE_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_REIMBURSE_CLASS),
				'amount' => $vat44583Declared,
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::VAT_REIMBURSE_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->offsetSet(\account\AccountSetting::VAT_REIMBURSE_CLASS, $eOperation44583);
		}

		// Équilibrages des arrondis
		$differenceDeclaredAndCalculated =
		  ($vat44571Declared - $vat44571Calculated)  // collectée
		  - ($vat44562Declared - $vat44562Calculated) // déductible immos
		  - ($vat44566Declared - $vat44566Calculated); // déductible autres

		// TVA due intracom à solder pour la CA3 (pour la CA12 on se base toujours sur la valeur en BD)
		if($eDeclaration['cerfa'] === \vat\Declaration::CA3) {
			$differenceDeclaredAndCalculated += ($vat4452Declared - $vat4452Calculated);
		} else { // acomptes (uniquement CA12)
			$differenceDeclaredAndCalculated -= ($vat44581Declared - $vat44581Calculated);
		}

		// Si on a déclaré + => Perte (658) sinon => Gain (758)
		if($differenceDeclaredAndCalculated > 0) {
			$class = \account\AccountSetting::CHARGES_OTHER_CLASS;
			$type = \journal\Operation::DEBIT;
		} else {
			$class = \account\AccountSetting::PRODUCT_OTHER_CLASS;
			$type = \journal\Operation::CREDIT;
		}
		$eOperationDifference = new \journal\Operation([
			'account' => $cAccount[$class],
			'accountLabel' => \account\AccountLabelLib::pad($class),
			'amount' => round(abs($differenceDeclaredAndCalculated), 2),
			'type' => $type,
			'description' => VatUi::getTranslations($class),
			'thirdParty' => $eThirdParty,
			'document' => $document,
		]);
		$cOperation->append($eOperationDifference);

		// Ajout de la taxe adar
		$adarTax = $cerfa['4220'];
		if($adarTax > 0) {

			$eOperationAdar = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS),
				'amount' => round($adarTax, 2),
				'type' => \journal\Operation::DEBIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->append($eOperationAdar);
			$eOperationVatAdar = new \journal\Operation([
				'account' => $cAccount[\account\AccountSetting::VAT_DEBIT_CLASS],
				'accountLabel' => \account\AccountLabelLib::pad(\account\AccountSetting::VAT_DEBIT_CLASS),
				'amount' => round($adarTax, 2),
				'type' => \journal\Operation::CREDIT,
				'description' => VatUi::getTranslations(\account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS),
				'thirdParty' => $eThirdParty,
				'document' => $document,
			]);
			$cOperation->append($eOperationVatAdar);

		}

		return [
			'cerfaCalculated' => $cerfaFromOperations,
			'cerfaDeclared' => $cerfa,
			'cOperation' => $cOperation,
		];

	}
	public static function createOperations(\farm\Farm $eFarm, \vat\Declaration $eDeclaration, \account\FinancialYear $eFinancialYear): void {

		$data = self::generateOperationsFromDeclaration($eFarm, $eDeclaration);
		$today = date('Y-m-d');

		$input = [
			'financialYear' => $eFinancialYear['id'],
			'account' => [],
			'accountLabel' => [],
			'description' => [],
			'amount' => [],
			'type' => [],
			'document' => [],
			'vatRate' => [],
			'vatValue' => [],
			'vatRule' => [],
			'comment' => [],
			'thirdParty' => [],
		];

		$index = 0;
		foreach($data['cOperation'] as $eOperation) {

			$input['account'][$index] = $eOperation['account']['id'];
			$input['thirdParty'][$index] = $eOperation['thirdParty']['id'];
			$input['date'][$index] = $today;
			$input['paymentDate'][$index] = $today;
			$input['vatRule'][$index] = \journal\Operation::VAT_HC;
			$input['vatValue'][$index] = NULL;

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

			$eDeclaration['status'] = Declaration::ACCOUNTED;
			$eDeclaration['accountedWithOperations'] = TRUE;
			DeclarationLib::update($eDeclaration, ['status', 'accountedWithOperations']);

		}

	}
}

?>
