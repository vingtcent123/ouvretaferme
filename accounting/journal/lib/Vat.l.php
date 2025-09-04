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

		// Calcul de la TVA déductible (VAT_BUY_CLASS_PREFIX / 4456)
		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \account\AccountSetting::VAT_BUY_CLASS_PREFIX,
		]);

		$deductibleVatAmount = OperationLib::applySearch($search)
      ->select([
        'total' => new \Sql('SUM(IF(type = "debit", amount, -1 * amount))', 'float')
      ])
      ->get()['total'];

		if($deductibleVatAmount !== 0.0) {

			$eAccount = \account\AccountLib::getByClass(\account\AccountSetting::VAT_BUY_CLASS_PREFIX);

			// Opération d'équilibrage
			$eOperationReverse = new Operation([
				'accountLabel' => \account\ClassLib::pad($eAccount['class']),
				'amount' => abs($deductibleVatAmount),
				'account' => $eAccount,
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdPartyVat,
				'date' => $eFinancialYear['endDate'],
				'description' => new \account\VatUi()->getVatLabel(\account\AccountSetting::VAT_BUY_CLASS_PREFIX),
				'type' => $deductibleVatAmount >= 0 ? OperationElement::DEBIT : OperationElement::CREDIT,
				'financialYear' => $eFinancialYear,
			]);

			Operation::model()->insert($eOperationReverse);

		}

		// Calcul de la TVA collectée (VAT_SELL_CLASS_PREFIX / 4457)
		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \account\AccountSetting::VAT_SELL_CLASS_PREFIX,
		]);

		$collectedVatAmount = OperationLib::applySearch($search)
			->select([
				'total' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float')
			])
			->get()['total'];

		if($collectedVatAmount !== 0.0) {

			$eAccount = \account\AccountLib::getByClass(\account\AccountSetting::COLLECTED_VAT_CLASS);

			$eOperationReverse = new Operation([
				'accountLabel' => \account\ClassLib::pad($eAccount['class']),
				'amount' => abs($collectedVatAmount),
				'account' => $eAccount,
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdPartyVat,
				'date' => $eFinancialYear['endDate'],
				'description' => new \account\VatUi()->getVatLabel(\account\AccountSetting::COLLECTED_VAT_CLASS),
				'type' => $deductibleVatAmount >= 0 ? OperationElement::CREDIT : OperationElement::DEBIT,
				'financialYear' => $eFinancialYear,
				'journalCode' => Operation::OD,
			]);

			Operation::model()->insert($eOperationReverse);

		}

		$balance = abs($collectedVatAmount - $deductibleVatAmount);

		if($balance !== 0.0) {

			// Constatation du solde net dans le bilan sur 44551 / PAYABLE_VAT_CLASS si la TVA est à décaisser, sur 44567 / CARRIED_VAT_CLASS pour un crédit de TVA
			$class = ($collectedVatAmount > $deductibleVatAmount ? \account\AccountSetting::PAYABLE_VAT_CLASS : \account\AccountSetting::CARRIED_VAT_CLASS);
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
				'journalCode' => Operation::OD,
			]);

			Operation::model()->insert($eOperationVat);

		}

		Operation::model()->commit();

	}

}
?>
