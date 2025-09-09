<?php
// Script préparatoire pour sauvegarder les moyens de paiement des sales qui n'ont pas de payment
// mais qui ont un paymentMethod défini.
// à passer avant la MEP qui supprime le champ paymentMethod.
// Lancer le script php framework/lime.php -a ouvretaferme -e prod configure/payments
new Page()
	->cli('index', function($data) {

		$cSale = \selling\Sale::model()
			->select(['id', 'paymentMethod'])
			->join(\selling\Payment::model(), 'm1.id = m2.sale', 'LEFT')
			->where('paymentMethod IS NOT NULL')
			->where('m2.id IS NULL')
			->getCollection();

		d($cSale->count().' ventes sans paiement');
		foreach($cSale as $eSale) {
			\selling\PaymentLib::putBySale($eSale, $eSale['paymentMethod']);
		}
		d('done');

	});
