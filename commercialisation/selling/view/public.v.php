<?php
new AdaptativeView('/paiement', function($data, MainTemplate $t) {

	if($data->e['source'] === \selling\PaymentLink::INVOICE) {

		$eElement = $data->e['invoice'];
		$name = \selling\InvoiceUi::getName($eElement);
		$title = s("Récapitulatif de votre facture");

	} else {

		$eElement = $data->e['sale'];
		$name = \selling\SaleUi::getName($eElement);
		$title = s("Récapitulatif de votre achat");

	}

	$h = '<div class="text-center">';
		$h .= '<br/><br/>';
		$h .= '<h2 class="color-success">'.Asset::icon('check-lg').' '.s("Votre paiement a bien été enregistré !").'</h2>';
		$h .= '<h4>'.s("{value} a bien été informé de votre paiement.", '<b>'.encode($eElement['farm']['name']).'</b>').'</h4>';
		$h .= '<br/><br/>';

		$h .= '<h4>';
			$h .= $title;
		$h .= '</h4>';
		$h .= '<div class="util-block stick-xs bg-background-light" style="max-width: 600px; margin: auto;">';

		$h .= '<dl class="util-presentation util-presentation-1" style="grid-template-columns: auto auto">';

			$h .= '<dt class="text-end">'.s("Référence").'</dt>';
			$h .= '<dd class="text-start">'.$name.'</dd>';

			$h .= '<dt class="text-end">'.s("Montant total").'</dt>';
			$h .= '<dd class="text-start">'.\util\TextUi::money($eElement['priceIncludingVat']).'</dd>';

			$h .= '<dt class="text-end">'.s("Montant réglé ce jour").'</dt>';
			$h .= '<dd class="text-start">'.\util\TextUi::money($data->e['amountIncludingVat']).'</dd>';

			$h .= '<dt class="text-end">'.s("Moyen de paiement").'</dt>';
			$h .= '<dd class="text-start">'.encode($data->eMethod['name']).'</dd>';

			$h .= '<dt class="text-end">'.s("État du paiement").'</dt>';
			$h .= '<dd class="text-start">'.\selling\InvoiceUi::p('paymentStatus')->values[$eElement['paymentStatus']].'</dd>';

		$h .= '</dl>';


		$h .= '</div>';
	$h .= '</div>';

	$t->title = s("Votre paiement sur {siteName}");
	$t->header = $h;

});
