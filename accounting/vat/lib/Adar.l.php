<?php
namespace vat;

// Taxe ADAR (calcul pour valeur annuelle), calculée sur le dernier exercice clos
// Calcul :
// - partie forfaitaire = 90€ par exploitant $UNIT_BY_ASSOCIATE_ADAR_TAX
// - partie variable : 0,19% ($RATE_1_ADAR) du CA jusqu'à 370k ($THRESHOLD_ADAR_TAX), 0,05% ($RATE_2_ADAR) au delà
// si déclaration annuelle => tout le temps
// si déclaration trimestrielle => 1er trimestre de l'exercice ou mois de mars
// si déclaration mensuelle => 3è mois de l'exercice ou mois de mars
// si exercice incomplet : calculer un prorata tempris de la partie forfaitaire et du seuil de 370k selon le nombre de jours
class AdarLib {

	public static function getTaxableBase(\account\FinancialYear $eFinancialYear, array $vatParameters): float {

		 return \journal\Operation::model()
			->select([
				'amount' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float'),
			])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::INCOME_GRANT_CLASS.'%'),
			)
			->whereVatRule('IN', [
				\journal\Operation::VAT_STD, \journal\Operation::VAT_STD_COLLECTED,
				\journal\Operation::VAT_0_EXPORT, \journal\Operation::VAT_0_INTRACOM,
				\journal\Operation::VAT_HC,
			])
			->wherePaymentDate('>=', $eFinancialYear['startDate'])
			->wherePaymentDate('<=', $eFinancialYear['endDate'])
			->get()['amount'] ?? 0.0;
	}

}
