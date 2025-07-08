<?php
namespace journal;

class VatLib {

	public static function balance(\account\FinancialYear $eFinancialYear): void {

		Operation::model()->beginTransaction();

		// On récupère le tiers "TVA" ou on le crée.
		$eThirdPartyVat = \account\ThirdPartyLib::getByName(new \account\VatUi()->getVatTranslation());
		if($eThirdPartyVat->exists() === FALSE) {

			$eThirdPartyVat = new \account\ThirdParty(['name' => new \account\VatUi()->getVatTranslation()]);
			\account\ThirdPartyLib::create($eThirdPartyVat);

		}

		// Calcul de la TVA déductible (vatBuyClassPrefix / 4456)
		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \Setting::get('account\vatBuyClassPrefix'),
		]);

		$eOperation = OperationLib::applySearch($search)
			->select([
				'total' => new \Sql('SUM(IF(type = "debit", amount, -1 * amount))', 'float')
			])
			->having('total != 0.0')
			->get();
		$deductibleVatAmount = $eOperation['total'];

		$eAccount = \account\AccountLib::getByClass(\Setting::get('account\vatBuyClassPrefix'));

		// Opération d'équilibrage
		$eOperationReverse = new Operation([
			'accountLabel' => \account\ClassLib::pad($eAccount['class']),
			'amount' => abs($deductibleVatAmount),
			'account' => $eAccount,
			'document' => new \account\VatUi()->getVatTranslation(),
			'documentDate' => new \Sql('NOW()'),
			'thirdParty' => $eThirdPartyVat,
			'date' => $eFinancialYear['endDate'],
			'description' => new \account\VatUi()->getVatLabel(\Setting::get('account\vatBuyClassPrefix')),
			'type' => $deductibleVatAmount >= 0 ? OperationElement::DEBIT : OperationElement::CREDIT,
			'financialYear' => $eFinancialYear,
		]);

		Operation::model()->insert($eOperationReverse);

		// Calcul de la TVA collectée (vatSellClassPrefix / 4457)
		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \Setting::get('account\vatSellClassPrefix'),
		]);

		$eOperation = OperationLib::applySearch($search)
			->select([
				'total' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float')
			])
			->having('total != 0.0')
			->get();
		$collectedVatAmount = $eOperation['total'];

		$eAccount = \account\AccountLib::getByClass(\Setting::get('account\collectedVatClass'));

		$eOperationReverse = new Operation([
			'accountLabel' => \account\ClassLib::pad($eAccount['class']),
			'amount' => abs($collectedVatAmount),
			'account' => $eAccount,
			'document' => new \account\VatUi()->getVatTranslation(),
			'documentDate' => new \Sql('NOW()'),
			'thirdParty' => $eThirdPartyVat,
			'date' => $eFinancialYear['endDate'],
			'description' => new \account\VatUi()->getVatLabel(\Setting::get('account\collectedVatClass')),
			'type' => $deductibleVatAmount >= 0 ? OperationElement::CREDIT : OperationElement::DEBIT,
			'financialYear' => $eFinancialYear,
		]);

		Operation::model()->insert($eOperationReverse);

		$balance = abs($collectedVatAmount - $deductibleVatAmount);

		// Constatation du solde net dans le bilan sur 44551 / payableVatClass si la TVA est à décaisser, sur 44567 / carriedVatClass pour un crédit de TVA
		$class = ($collectedVatAmount > $deductibleVatAmount ? \Setting::get('account\payableVatClass') : \Setting::get('account\carriedVatClass'));
		$type = ($collectedVatAmount > $deductibleVatAmount ? OperationElement::CREDIT : OperationElement::DEBIT);
		$eAccount = \account\AccountLib::getByClass($class);

		$eOperationVat = new Operation([
			'amount' => $balance,
			'account' => $eAccount,
			'accountLabel' => \account\ClassLib::pad($eAccount['class']),
			'document' => new \account\VatUi()->getVatTranslation(),
			'documentDate' => new \Sql('NOW()'),
			'thirdParty' => $eThirdPartyVat,
			'date' => $eFinancialYear['endDate'],
			'financialYear' => $eFinancialYear,
			'description' => new \account\VatUi()->getVatBalanceTranslation($eAccount['class'], $eFinancialYear),
			'type' => $type,
		]);

		Operation::model()->insert($eOperationVat);

		Operation::model()->commit();

	}

}
?>
