<?php
namespace overview;

Class VatLib {


	public static function getForCheck(\Search $search = new \Search()): array {

		// Ligne A1 - Ventes (70* - 709 - 665)
		$cOperationVentes = \journal\OperationLib::applySearch($search)
			->select([
				'compte' => new \Sql('SUBSTRING(accountLabel, 1, 2)', 'int'),
				'vatRate',
				'amount' => new \Sql('ROUND(SUM(IF(type = "credit", amount, -1 * amount)))', 'int'),
			])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ESCOMPTES_ACCOUNT_CLASS.'%'),
			)
			->group(['compte', 'vatRate'])
			->getCollection();

		// Regroupement par taux de TVA
		$sales = [];
		foreach($cOperationVentes as &$eOperationVentes) {

			$key = (string)$eOperationVentes['vatRate'];
			if(isset($sales[$key]) === FALSE) {
				$sales[$key] = [
					'vatRate' => $eOperationVentes['vatRate'],
					'amount' => 0,
				];
			}
			$sales[$key]['amount'] += $eOperationVentes['amount'];
		}

		foreach($sales as $key => $sale) {
			$sales[$key]['tax'] = $sale['vatRate'] !== 0 ? round($sale['amount'] * $sale['vatRate'] / 100) : 0;
		}

		// Récupération des TVA enregistrées
		$cOperationTaxes = \journal\OperationLib::applySearch($search)
			->select([
				'compte' => new \Sql('TRIM(BOTH "0" FROM accountLabel)', 'int'),
				'amount', 'type',
				'operation' => ['vatRate'],
			])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_BUY_CLASS_PREFIX.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_SELL_CLASS_PREFIX.'%'),
			)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT.'%')
			->whereOperation('!=', NULL)
			->getCollection();

		$taxes = [];
		foreach($cOperationTaxes as $eOperation) {

			$key = (string)$eOperation['operation']['vatRate'];

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
			} else {
				if($eOperation['type'] === \journal\Operation::CREDIT) {
					$taxes[$eOperation['compte']][$key]['amount'] += $eOperation['amount'];
				} else {
					$taxes[$eOperation['compte']][$key]['amount'] -= $eOperation['amount'];
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
			  fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT.'%'),
			  fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX.'%'),
			)
			->group('compte')
			->getCollection();

		return [
			'sales' => $sales,
			'taxes' => $taxes,
			'deposits' => [
				\account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT => $cOperationTaxes[\account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT]['amount'] ?? 0,
				\account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX => $cOperationTaxes[\account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX]['amount'] ?? 0,
			]
		];

	}

	public static function getVatDeclarationParameters(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $period = 'current'): array {

		// $period peut être
		//  "current" (on se base sur la date courante),
		//  "last" (la dernière période avant la date courante)
		//  "next "la prochaine période après la date courante)

		if(in_array($period, ['current', 'last', 'next']) === FALSE) {
			throw new \NotExpectedAction('Unknown period for the VAT declaration');
		}

		if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {

			$periodFrom = $eFinancialYear['startDate'];
			$periodTo = $eFinancialYear['endDate'];

		} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::QUARTERLY) {

			$currentMonth = (int)date('m');

			if($currentMonth < 3) {
				$trimestre = 1;
			} else if($currentMonth < 6) {
				$trimestre = 2;
			} else if($currentMonth < 9) {
				$trimestre = 3;
			} else if($currentMonth < 12) {
				$trimestre = 4;
			}

			$periodFrom = date('Y-m-01', mktime(0, 0, 0, ($trimestre - 1) * 3 + 1, 1, date('Y')));
			$periodTo = date('Y-m-d', mktime(0, 0, 0, $trimestre * 3 + 1, 0, date('Y')));

		} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::MONTHLY) {

			$periodFrom = date('Y-m-01');
			$periodTo = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 0, date('Y')));

		}

		switch($period) {
			case 'current':
				break;

			case 'last':
				if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' - 1 year'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' - 1 year'));
				} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::QUARTERLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' - 3 month'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' - 3 month'));
				} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::MONTHLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' - 1 month'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' - 1 month'));
				}
				break;

			case 'next':
				if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' + 1 year'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' + 1 year'));
				} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::QUARTERLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' + 3 month'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' + 3 month'));
				} else if($eFinancialYear['vatFrequency'] === \account\FinancialYear::MONTHLY) {
					$periodFrom = date('Y-m-d', strtotime($periodFrom.' + 1 month'));
					$periodTo = date('Y-m-d', strtotime($periodTo.' + 1 month'));
				}
				break;
		}

		switch($eFinancialYear['vatFrequency']) {

			case \account\FinancialYear::ANNUALLY:

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

					$date = $firstDatetime->format('Y-m-d');

				} else {

					$limitDatetime = new \DateTime($periodTo);
					$limitDatetime->add(new \DateInterval('P3M'));

					$date = $limitDatetime->format('Y-m-d');

				}
				break;

			case \account\FinancialYear::QUARTERLY:
			case \account\FinancialYear::MONTHLY:
				$isRegionParisienne = in_array(mb_substr($eFarm['legalPostcode'], 0, 2), ['75', '92', '93', '94']);

				// TODO Émilie: changer pour récupérer cette donnée et la stocker dans les données de la ferme
				$curl = new \util\CurlLib();
				$options = [
					CURLOPT_HTTPHEADER => [
						'X-INSEE-Api-Key-Integration: '.\company\CompanySetting::$inseeApiKey,
					],
					CURLOPT_RETURNTRANSFER => TRUE,
				];
				$data = json_decode($curl->exec('https://api.insee.fr/api-sirene/3.11/siren/'.mb_substr(str_replace(' ', '', $eFarm['siret']), 0, 9), [], 'GET', $options));
				$firstLetter = mb_strtolower(mb_substr($eFarm['legalName'], 0, 1));
				$categorieJuridique = (int)$data->uniteLegale->periodesUniteLegale[0]->categorieJuridiqueUniteLegale ?? NULL;

				if($isRegionParisienne) {
					if($categorieJuridique === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) { // Entrepreneur individuel
						if($firstLetter <= 'h') {
							$day = 15;
						} else {
							$day = 17;
						}
					} else if($categorieJuridique >= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['from'] and $categorieJuridique <= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['to']) { // Société anonyme
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
					if($categorieJuridique === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) { // Entrepreneur individuel
						if($firstLetter <= 'h') {
							$day = 16;
						} else {
							$day = 19;
						}
					} else if($categorieJuridique >= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['from'] and $categorieJuridique <= \company\CompanySetting::CATEGORIE_JURIDIQUE_SOCIETE_ANONYME['to']) { // Société anonyme
						$day = 24;
					} else { // Autres sociétés
						$day = 21;
					} // Autres redevables : 24 (qui ?)

				}

				$limitDatetime = new \DateTime(mb_substr($periodTo, 0, 7).'-01');
				$limitDatetime->add(new \DateInterval('P1M'));

				$date = $limitDatetime->format('Y-m').'-'.$day;
		}

		return [
			// Date limite
			'limit' => $date,

			// Période déclarée
			'from' => $periodFrom,
			'to' => $periodTo,
		];

	}
	public static function getVatDataDeclaration(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Search $search = new \Search(), int $precision = 0): array {

		// On vérifie si on a une entrée en base de données
		$eVatDeclaration = VatDeclaration::model()
			->select(VatDeclaration::getSelection())
			->whereFrom($search->get('minDate'))
			->whereTo($search->get('maxDate'))
			->whereStatus('IN', [VatDeclaration::DRAFT, VatDeclaration::DECLARED])
			->get();

		if($eVatDeclaration->notEmpty()) {
			return $eVatDeclaration['data'] + ['eVatDeclaration' => $eVatDeclaration];
		}

		$checkData = self::getForCheck($search);
		$sales = $checkData['sales'];
		$taxes = $checkData['taxes'];
		$deposits = $checkData['deposits'];

		$vatData = [];

		// OPÉRATIONS NON TAXÉES

		// Autre opé non imposables 74 (sub), 76 (financiers), 771 (indemnités), 775 (cessions exonérées)
		$eOperationAutreOperationsNonImposables = \journal\OperationLib::applySearch($search)
			->select([
				'amount' => new \Sql('ROUND(SUM(IF(type = "credit", amount, -1 * amount)))', 'float'),
			])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SUBVENTION_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_FINANCIAL_ACCOUNT_CLASS.'%'),
			)
			->get();
		$vatData['0033'] = round($eOperationAutreOperationsNonImposables['amount'] ?? 0, $precision);

		// OPÉRATIONS TAXÉES

		// Ventes (comptes 70*) :
		// 0207 => Ventes à 20%
		$vatData['0207-base'] = round($sales[20]['amount'] ?? 0, $precision);
		$vatData['0207-tax'] = round($taxes[44571]['20']['amount'] ?? 0, $precision);
		// 0105 => Ventes à 5.5%
		$vatData['0105-base'] = round($sales['5.5']['amount'] ?? 0, $precision);
		$vatData['0105-tax'] = round($taxes[44571]['5.5']['amount'] ?? 0, $precision);
		// 0151 => Ventes à 10%
		$vatData['0151-base'] = round($sales['10']['amount'] ?? 0, $precision);
		$vatData['0151-tax'] = round($taxes[44571]['10']['amount'] ?? 0, $precision);

		// TVA s/ immos
		$vatData['0703'] = round(array_sum(array_column($taxes['44562'] ?? [], 'amount')), $precision);

		// TVA s/ ABS
		$vatData['0702'] = round(array_sum(array_column($taxes['44566'] ?? [], 'amount')), $precision);

		$vatData['16-number'] = round(array_sum(array_filter($vatData, fn($item, $index) => in_array($index, ['0207-tax', '0208-tax', '0105-tax', '0151-tax', '0201-tax', '0100-tax', '0950-tax', '0152-tax', '0900-tax', '0030-tax', '0040-tax', '0044-tax', '0970-tax', '0980-tax', '0981-tax']), ARRAY_FILTER_USE_BOTH)), $precision);
		$vatData['19-number'] = round(($vatData['16-number'] ?? 0) + ($vatData['0983'] ?? 0) + ($vatData['0600'] ?? 0) + ($vatData['0602'] ?? 0), $precision);
		$vatData['22-number'] = round(($vatData['0702'] ?? 0) + ($vatData['0704'] ?? 0), $precision);
		$vatData['26-number'] = round($vatData['22-number'] + ($vatData['0703'] ?? 0) + ($vatData['0058'] ?? 0) + ($vatData['0059'] ?? 0) + ($vatData['0603'] ?? 0), $precision);

		if($vatData['19-number'] > $vatData['26-number']) {
			$vatData['8900'] = round($vatData['19-number'] - $vatData['26-number'], $precision);
		} else {
			$vatData['0705'] = round($vatData['26-number'] - $vatData['19-number'], $precision);
		}

		// Acomptes  TODO : trouver un moyen de récupérer les acomptes déjà payés
		$vatData['deposit[0][paid]'] = 0;
		$vatData['deposit[0][not-paid]'] = 0;
		$vatData['deposit[1][paid]'] = 0;
		$vatData['deposit[1][not-paid]'] = 0;
		$vatData['deposit[total][paid]'] = round($vatData['deposit[0][paid]'] + $vatData['deposit[1][paid]'], $precision);
		$vatData['deposit[total][not-paid]'] = round($vatData['deposit[0][not-paid]'] + $vatData['deposit[1][not-paid]']);
		$vatData['0018'] = round(-1 * $deposits[\account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX]);

		if(($vatData['8900'] ?? 0) >= ($vatData['0705'] ?? 0 + $vatData['0018'] ?? 0)) {
			$vatData['33-number'] = round($vatData['8900'] - (($vatData['0705'] ?? 0) + ($vatData['0018'] ?? 0)), $precision);
		}
		if($vatData['0018'] ?? 0 >= $vatData['8900'] ?? 0) {
			$vatData['34-number'] = round(($vatData['0018'] ?? 0 - $vatData['8900'] ?? 0), $precision);
		}
		if(($vatData['0705'] ?? 0) > ($vatData['34-number'] ?? 0)) {
			$vatData['0020'] = round(($vatData['0705'] ?? 0 - $vatData['34-number'] ?? 0), $precision);
		}

		// Taxe ADAR
		// TODO Émilie : récupérer si cest un GAEC (catégorie juridique 6533) et le nombre d'associés
		$turnover = round(array_sum(array_filter($vatData, fn($index, $key) => in_array($key, ['0033', '0207-base', '0151-base', '0105-base', '0100-base', '0201-base', '0900-base', '0950-base', '0210-base', '0211-base', '0212-base', '0213-base', '0214-base', '0215-base', '0970-base', '0981-base']), ARRAY_FILTER_USE_BOTH)), $precision);
		$associates = 3;
		$fixedUnit = 90;
		$fixed = $fixedUnit * $associates;

		$adarRate = 0.19 / 100;
		$adarRate2 = 0.05 / 100;
		$adarTax = round($fixed + min(370000, $turnover) * $adarRate + max(0, $turnover - 370000) * $adarRate2, $precision);

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
}

?>
