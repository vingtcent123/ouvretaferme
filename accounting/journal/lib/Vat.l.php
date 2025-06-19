<?php
namespace journal;

class VatLib {

	public static function balance(\account\FinancialYear $eFinancialYear): void {

		Operation::model()->beginTransaction();

		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \Setting::get('account\vatClass'),
		]);

		$cOperationYear = OperationLib::applySearch($search)
			->select([
				'account' => ['id', 'class', 'description'],
				'total' => new \Sql('SUM(IF(type = "debit", -1 * amount, amount))', 'float')
			])
			->having('total != 0.0')
			->group(['account'])
			->getCollection();

		$eAccountVat = \account\AccountLib::getByClass(\Setting::get('account\vatClass'));

		$eThirdParty = \account\ThirdPartyLib::getByName(new \account\VatUi()->getVatTranslation());
		if($eThirdParty->exists() === FALSE) {

			$eThirdParty = new \account\ThirdParty(['name' => new \account\VatUi()->getVatTranslation()]);
			\account\ThirdPartyLib::create($eThirdParty);

		}

		foreach($cOperationYear as $eOperation) {

			$eOperationReverse = new Operation([
				'accountLabel' => \account\ClassLib::pad($eOperation['account']['class']),
				'amount' => abs($eOperation['total']),
				'account' => $eOperation['account'],
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
			]);
			$eOperationVat = new Operation([
				'amount' => $eOperationReverse['amount'],
				'account' => $eAccountVat,
				'accountLabel' => \account\ClassLib::pad($eAccountVat['class']),
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
			]);

			// Si c'est 4456* (Taxes sur le CA déductibles) => le créditer et le débiter sur le compte 44500000
			if(\account\ClassLib::isFromClass($eOperation['account']['class'], \Setting::get('account\vatBuyClassPrefix'))) {

				// Devrait être négatif, sinon, il faut tout inverser
				if($eOperation['total'] < 0) {

					$eOperationReverse['type'] = OperationElement::CREDIT;
					$eOperationVat['type'] = OperationElement::DEBIT;

				} else {

					$eOperationReverse['type'] = OperationElement::DEBIT;
					$eOperationVat['type'] = OperationElement::CREDIT;

				}

				$eOperationReverse['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatBuyClassPrefix'));
				$eOperationVat['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatBuyClassPrefix'));

			} else {
			// Si c'est 4457* (Taxes sur le CA collectées) => le débiter et le créditer sur le compte 44500000

				// Devrait être positif, sinon, il faut tout inverser
				if($eOperation['total'] > 0) {

					$eOperationReverse['type'] = OperationElement::DEBIT;
					$eOperationVat['type'] = OperationElement::CREDIT;

				} else {

					$eOperationReverse['type'] = OperationElement::CREDIT;
					$eOperationVat['type'] = OperationElement::DEBIT;

				}

				$eOperationReverse['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatSellClassPrefix'));
				$eOperationVat['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatSellClassPrefix'));
			}

			$cOperation = new \Collection([$eOperationReverse, $eOperationVat]);
			Operation::model()->insert($cOperation);

		}

		Operation::model()->commit();

	}

}
?>
