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

	public static function getVatDataDeclaration(\account\FinancialYear $eFinancialYear, \Search $search = new \Search()): array {

		$checkData = self::getForCheck($search);
		$sales = $checkData['sales'];
		$taxes = $checkData['taxes'];
		$deposits = $checkData['deposits'];

		// Ventes (comptes 70*)
dd($sales);

	}
}

?>
