<?php
namespace journal;

class VatLib {

	/**
	 * Méthodologie :
	 * Centralisation des mouvements de TVA à la clôture, via un compte de liaison 44500000, qu'on pourrait appeler "TVA à régulariser".
	 * C’est utilisé pour transférer les soldes des comptes de TVA collectée (4457*) et déductible (4456*) vers un compte pivot en vue :
	 * - soit de les compenser entre eux
	 * - soit de préparer l’écriture de déclaration / paiement
	 * - soit de présenter un solde unique en bilan (44551 ou 44567)
	 *
	 * @param FinancialYear $eFinancialYear
	 */
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

		// On récupère le tiers "TVA" ou on le crée.
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

				} else { // cas bizarre qui ne devrait pas se poser ?

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

				} else { // cas bizarre qui ne devrait pas se poser ?

					$eOperationReverse['type'] = OperationElement::CREDIT;
					$eOperationVat['type'] = OperationElement::DEBIT;

				}

				$eOperationReverse['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatSellClassPrefix'));
				$eOperationVat['description'] = new \account\VatUi()->getVatLabel(\Setting::get('account\vatSellClassPrefix'));
			}

			$cOperation = new \Collection([$eOperationReverse, $eOperationVat]);

			Operation::model()->insert($cOperation);

		}

		// Constatation d'une dette ou d'un crédit de TVA :
		// TVA collectée – TVA déductible = TVA nette à payer ou à récupérer
		// 1/ Calcul du solde de TVA sur 445 puis si > 0 : dette de TVA dans le passif

		$search = new \Search([
			'financialYear' => $eFinancialYear,
			'accountLabel' => \account\ClassLib::pad(\Setting::get('account\vatClass')),
		]);

		$eOperationVatTotal = OperationLib::applySearch($search)
      ->select([
        'total' => new \Sql('SUM(IF(type = "debit", -1 * amount, amount))', 'float')
      ])
      ->having('total != 0.0')
      ->group(['account'])
      ->get();

		if($eOperationVatTotal['total'] > 0) {

			// On débite 445 pour l'équilibrer
			$eOperationVatDebit = new Operation([
				'amount' => $eOperationVatTotal['total'],
				'account' => $eAccountVat,
				'accountLabel' => \account\ClassLib::pad($eAccountVat['class']),
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
				'type' => Operation::DEBIT,
			]);

			Operation::model()->insert($eOperationVatDebit);

			$eAccount = \account\AccountLib::getByClass(\Setting::get('account\payableVatClass'));

			// On crédite 44551 pour la TVA à décaisser
			$eOperationPayableVat = new Operation([
				'amount' => $eOperationVatTotal['total'],
				'account' => $eAccount,
				'accountLabel' => \account\ClassLib::pad($eAccount['class']),
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
				'type' => Operation::CREDIT,
			]);

			Operation::model()->insert($eOperationPayableVat);

		} else if($eOperationVatTotal['total'] < 0) {

			// On crédite 445 pour l'équilibrer
			$eOperationVatDebit = new Operation([
				'amount' => $eOperationVatTotal['total'],
				'account' => $eAccountVat,
				'accountLabel' => \account\ClassLib::pad($eAccountVat['class']),
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
				'type' => Operation::CREDIT,
			]);

			Operation::model()->insert($eOperationVatDebit);

			$eAccount = \account\AccountLib::getByClass(\Setting::get('account\v'));

			// On débite 44567 pour la TVA à reporter
			$eOperationCarriedVat = new Operation([
				'amount' => $eOperationVatTotal['total'],
				'account' => $eAccount,
				'accountLabel' => \account\ClassLib::pad($eAccount['class']),
				'document' => new \account\VatUi()->getVatTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'thirdParty' => $eThirdParty,
				'date' => $eFinancialYear['endDate'],
				'type' => Operation::DEBIT,
			]);

			Operation::model()->insert($eOperationCarriedVat);

		}

		Operation::model()->commit();

	}

}
?>
