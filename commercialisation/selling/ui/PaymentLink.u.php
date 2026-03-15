<?php
namespace selling;

class PaymentLinkUi {

	public static function getList(Sale|Invoice $eElement): string {

		if($eElement['cPaymentLink']->empty()) {
			return '';
		}

		$h = '<h3>';
			$h .= s("Liens de paiement").'  <span class="util-badge bg-primary">'.$eElement['cPaymentLink']->count().'</span>';
		$h .= '</h3>';
		$h .= new PaymentLinkUi()->showExistingPaymentLinks($eElement['cPaymentLink']);

		return $h;

	}

	public static function getPaymentReceivedEmail(PaymentLink $ePaymentLink): array {

		if($ePaymentLink['source'] === PaymentLink::SALE) {

			$eElement = $ePaymentLink['sale'];
			$name = SaleUi::getName($eElement);
			$url = \Lime::getUrl().'/vente/'.$eElement['id'];

		} else if($ePaymentLink['source'] === PaymentLink::INVOICE) {

			$eElement = $ePaymentLink['invoice'];
			$name = InvoiceUi::getName($eElement);
			$url = \Lime::getUrl().'/ferme/'.$ePaymentLink['farm']['id'].'/factures/?name='.$eElement['number'];

		}

		$title = s("Paiement depuis un lien de paiement", $eElement['customer']->getName());

		$content = s("Bonjour,
		
Un lien de paiement a été payé : 
- Référence : <link>{name}</link>
- Client : {customer}
- Montant payé : {amount} 

À bientôt,
L'équipe {siteName}", [
			'customer' => $eElement['customer']->getName(),
			'amount' => \util\TextUi::money($ePaymentLink['amountIncludingVat']),
			'name' => $name,
			'link' => '<a href="'.$url.'">',
		]);


		return [
			$title,
			\mail\DesignUi::encapsulateText($eElement['farm'], $content),
			\mail\DesignUi::encapsulateHtml($eElement['farm'], nl2br($content))
		];

	}

	public static function getPaymentConfirmationEmail(PaymentLink $ePaymentLink): array {

		if($ePaymentLink['source'] === PaymentLink::SALE) {

			$eElement = $ePaymentLink['sale'];
			$reference = s("commande {value}", $eElement['document']);

		} else if($ePaymentLink['source'] === PaymentLink::INVOICE) {

			$eElement = $ePaymentLink['invoice'];
			$reference = s("facture {value}", $eElement['number']);

		}

		$title = s("Confirmation de paiement pour la {value}", $reference);

		$content = s("Bonjour,
		
Nous vous confirmons que le paiement d'un montant de {amount} depuis un lien Stripe a bien été enregistré pour la {reference}. 

Merci et à bientôt,
{farmName}", [
			'amount' => \util\TextUi::money($ePaymentLink['amountIncludingVat']),
			'reference' => $reference,
			'farmName' => $eElement['farm']['name'],
		]);


		return [
			$title,
			\mail\DesignUi::encapsulateText($eElement['farm'], $content),
			\mail\DesignUi::encapsulateHtml($eElement['farm'], nl2br($content))
		];

	}

	public function create(Invoice|Sale $eElement, \Collection $cPaymentLink): \Panel {

		$form = new \util\FormUi();

		if($eElement['priceIncludingVat'] !== NULL) {

			if($eElement instanceof Invoice) {
				$title = s("{name} de {amount}", ['amount' => \util\TextUi::money($eElement['priceIncludingVat']), 'name' => InvoiceUi::getName($eElement)]);
			} else if($eElement instanceof Sale) {
				$title = s("{name} de {amount}", ['amount' => \util\TextUi::money($eElement['priceIncludingVat']), 'name' => SaleUi::getName($eElement)]);
			}

		} else {

			if($eElement instanceof Invoice) {
				$title = encode(InvoiceUi::getName($eElement));
			} else if($eElement instanceof Sale) {
				$title = encode(SaleUi::getName($eElement));
			}

		}

		$source = $eElement instanceof Invoice ? PaymentLink::INVOICE : PaymentLink::SALE;

		$h = '<h4>'.$title.'</h4>';
		if($eElement['paymentAmount'] !== NULL and $eElement['priceIncludingVat'] - $eElement['paymentAmount'] > 0) {
			$h .= '<div class="util-info">';
				if($eElement instanceof Sale) {
					$h .= s("Il reste {value} à régler sur cette vente.", \util\TextUi::money($eElement['priceIncludingVat'] - $eElement['paymentAmount']));
				} else {
					$h .= s("Il reste {value} à régler sur cette facture.", \util\TextUi::money($eElement['priceIncludingVat'] - $eElement['paymentAmount']));
				}
				$h .= '</div>';
		}

		$h .= new PaymentLinkUi()->showExistingPaymentLinksBlock($cPaymentLink);

		$h .= $form->openAjax('/selling/paymentLink:doCreate');

			$h .= $form->hidden('farm', $eElement['farm']['id']);
			$h .= $form->hidden('customer', $eElement['customer']['id']);
			$h .= $form->hidden('source', $source);
			if($eElement instanceof Invoice) {
				$h .= $form->hidden('invoice', $eElement['id']);
			} else {
				$h .= $form->hidden('sale', $eElement['id']);
			}

			$h .= $form->dynamicGroups(new PaymentLink(), ['validUntil*', 'amountIncludingVat*'], ['validUntil*' => function($d) use($form, $eElement) {
				$d->default = date('Y-m-d', strtotime('now + 5 days'));
				$d->attributes = ['min' => date('Y-m-d', strtotime('now + 1 day'))];
			},'amountIncludingVat*' => function($d) use($form, $eElement) {
				$d->default = round($eElement['priceIncludingVat'] - $eElement['paymentAmount'] ?? 0, 2);
			}]);

			$h .= $form->submit(s("Créer le lien de paiement"));

		$h .= $form->close();

		return new \Panel(
			id: 'panel-'.$source.'-update',
			title: s("  Créer un lien de paiement par carte bancaire en ligne"),
			body: $h
		);

	}

	public function showExistingPaymentLinksBlock(\Collection $cPaymentLink): string {

		if($cPaymentLink->empty()) {
			return '';
		}

		$h = '<div class="util-block-optional">';
			$h .= '<p>'.p("Vous avez un lien de paiement actif : ", "Vous avez {value} liens de paiement actifs :", $cPaymentLink->count()).'</p>';
			$h .= new PaymentLinkUi()->showExistingPaymentLinks($cPaymentLink);
		$h .= '</div>';

		return $h;
	}

	public function showExistingPaymentLinks(\Collection $cPaymentLink): string {

		if($cPaymentLink->empty()) {
			return '';
		}

		$h = '<table class="tbody-even">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.self::p('url')->label.'</th>';
					$h .= '<th>'.self::p('status')->label.'</th>';
					$h .= '<th class="text-center">'.self::p('validUntil')->label.'</th>';
					$h .= '<th class="text-end t-highlight">'.self::p('amountIncludingVat')->label.'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';
			foreach($cPaymentLink as $ePaymentLink) {
				$h .= '<tr>';
					$h .= '<td>';
						$h .= '<a href="'.$ePaymentLink['url'].'" id="payment-url-'.$ePaymentLink['id'].'">'.$ePaymentLink['url'].'</a>';
					$h .= '</td>';
					$h .= '<td>';
						$h .= self::p('status')->values[$ePaymentLink['status']];
					$h .= '</td>';
					$h .= '<td class="text-center">';
						$h .= \util\DateUi::numeric($ePaymentLink['validUntil']);
					$h .= '</td>';
					$h .= '<td class="text-end t-highlight">';
						$h .= \util\TextUi::money($ePaymentLink['amountIncludingVat']);
					$h .= '</td>';
					$h .= '<td class="text-center">';
						$h .= '<a onclick="doCopy(this)" data-selector="#payment-url-'.$ePaymentLink['id'].'" data-message="'.s("Copié !").'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('clipboard').' '.s("Copier le lien").'</a>';
					$h .= '</td>';
				$h .= '</tr>';

			}
		$h .= '</table>';

		return$h;
	}

	public function getSuccessActions(PaymentLink $ePaymentLink): string {

		$h = '<div class="mt-1">';
			$h .= '<a href="'.$ePaymentLink['url'].'" id="payment-url">'.$ePaymentLink['url'].'</a>';
			$h .= '  <a onclick="doCopy(this)" data-selector="#payment-url" data-message="'.s("Copié !").'" class="btn btn-sm btn-transparent">'.\Asset::icon('clipboard').' '.s("Copier le lien").'</a>';
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = PaymentLink::model()->describer($property, [
			'validUntil' => s("Date limite de validité du lien"),
			'url' => s("Lien"),
			'amountIncludingVat' => s("Montant du paiement (TTC)"),
			'status' => s("Statut du lien"),
		]);

		switch($property) {

			case 'amountIncludingVat':
				$d->append = function(\util\FormUi $form) {
					return $form->addon(s("€"));
				};
				break;

			case 'status':
				$d->values = [
					PaymentLink::ACTIVE => s("En attente de paiement"),
					PaymentLink::PAID => s("Payé"),
					PaymentLink::EXPIRED => s("Expiré"),
					PaymentLink::INACTIVE => s("Désactivé"),
				];
				break;

		}

		return $d;

	}

}
