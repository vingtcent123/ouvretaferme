<?php
namespace selling;

class PaymentLinkUi {

	public static function getPaymentEmail(PaymentLink $ePaymentLink): array {

		if($ePaymentLink['source'] === PaymentLink::SALE) {

			$eElement = $ePaymentLink['sale'];
			$name = SaleUi::getName($eElement);

		} else if($ePaymentLink['source'] === PaymentLink::INVOICE) {

			$eElement = $ePaymentLink['invoice'];
			$name = InvoiceUi::getName($eElement);

		}

		$title = s("Paiement de {value} depuis un lien de paiement", $eElement['customer']->getName());

		$text = s("Bonjour,

{customer} vient de payer {amount} depuis le lien de paiement que vous lui avez transmis pour la référence : {name}.

À bientôt,
L'équipe {siteName}", ['customer' => $eElement['customer']->getName(), 'amount' => \util\TextUi::money($ePaymentLink['amountIncludingVat']), 'name' => $name]);


		return [
			$title,
			$text
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

		$h = '<h4>'.$title.'</h4>';

		$h .= '<div class="util-info">'.s("Pour faciliter le recouvrement de vos créances, vous pouvez proposer un lien de paiement direct à vos clients.").'</div>';
		$h .= $this->showExistingPaymentLinks($cPaymentLink, FALSE);

		$h .= $form->openAjax('/selling/paymentLink:doCreate');

			$h .= $form->hidden('farm', $eElement['farm']['id']);
			$h .= $form->hidden('customer', $eElement['customer']['id']);
			$h .= $form->hidden('source', $eElement instanceof Invoice ? PaymentLink::INVOICE : PaymentLink::SALE);
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
			id: 'panel-invoice-create-payment-link',
			title: s("Créer un lien de paiement par carte bancaire en ligne"),
			body: $h
		);

	}

	public function showExistingPaymentLinks(\Collection $cPaymentLink, bool $showCreateButton): string{

		if($cPaymentLink->empty()) {
			return '';
		}

		$h = '<div class="util-block-optional">';
			$h .= '<h4 class="color-warning">'.s("Attention !").'</h4>';
			$h .= '<p>'.p("Vous avez un lien de paiement actif : ", "Vous avez {value} liens de paiement actifs :", $cPaymentLink->count()).'</p>';
			$h .= '<table>';
				$h .= '<tr>';
					$h .= '<th>'.self::p('url')->label.'</th>';
					$h .= '<th>'.self::p('validUntil')->label.'</th>';
					$h .= '<th>'.self::p('status')->label.'</th>';
					$h .= '<th>'.self::p('amountIncludingVat')->label.'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
				foreach($cPaymentLink as $ePaymentLink) {
					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<a href="'.$ePaymentLink['url'].'" id="payment-url-'.$ePaymentLink['id'].'">'.$ePaymentLink['url'].'</a>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($ePaymentLink['validUntil']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('status')->values[$ePaymentLink['status']];
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($ePaymentLink['amountIncludingVat']);
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<a onclick="doCopy(this)" data-selector="#payment-url-'.$ePaymentLink['id'].'" data-message="'.s("Copié !").'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('clipboard').' '.s("Copier le lien").'</a>';
						$h .= '</td>';
					$h .= '</tr>';

				}
			$h .= '</table>';

			$eElement = $ePaymentLink->getElement();
			$h .= '<div class="text-end">';
				$h .= '<a href="/selling/paymentLink:create?'.($eElement instanceof Sale ? 'sale' : 'invoice').'='.$eElement['id'].'" class="btn btn-xs btn-outline-primary">';
					$h .= s("Créer un autre lien de paiement");
				$h .= '</a>';

			$h .= '</div>';
		$h .= '</div>';

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
