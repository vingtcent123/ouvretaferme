<?php
namespace association;

class AssociationUi {

	public function getProductDonationName(): string {

		return s("Don à l'association Ouvretaferme");

	}
	public function getMembershipProductName(?int $year): string {

		return s("Adhésion {year} à l'association Ouvretaferme", ['year' => $year]);

	}

	public static function confirmationUrl(History $eHistory, string $type, string $fromPage): string {

		if($eHistory['farm']->empty()) {

			return $fromPage.'?success=association:Membership::'.$type.'.created&email='.urlencode($eHistory['customer']['invoiceEmail']).'&customer='.$eHistory['customer']['id'];

		}

		return self::url($eHistory['farm']).'?'.$type;
	}

	public static function url(\farm\Farm $eFarm): string {

		if($eFarm->empty()) {
			return \Lime::getUrl().'/donner';
		}

		return \Lime::getUrl().'/ferme/'.$eFarm['id'].'/adherer';
	}

	public function donationThankYou(History $eHistory): string {

		if($eHistory->empty()) {

			return '<div class="util-block">'.s("Toute l'équipe de Ouvretaferme vous remercie pour votre générosité. Vous allez recevoir dans quelques minutes votre reçu par e-mail à l'adresse indiquée lorsque vous avez rempli votre don.").'</div>';

		}

		\Asset::js('association', 'association.js');

		$h = '<div class="util-block" onrender="Association.cleanArgs();">';

			$h .= '<p>'.s("Nous avons bien reçu votre don de {amount}.", ['amount' => '<b>'.\util\TextUi::money($eHistory['amount'], precision: 0).'</b>']).'</p>';
			$h .= '<p>'.s("Vous allez recevoir dans quelques minutes votre attestation de paiement par e-mail à l'adresse {email}.", ['email' => '<b>'.$eHistory['customer']['invoiceEmail'].'</b>']).'</p>';
			$h .= '<p class="mt-2 mb-1">'.s("Toute l'équipe de Ouvretaferme vous remercie pour votre générosité.").'</p>';

			$h .= '<a class="btn btn-outline-primary" href="'.\Setting::get('association\url').'">'.s("Consulter le site de l'association").'</a>';

		$h .= '</div>';



		return $h;

	}

	public function donationForm(\user\User $eUser = new \user\User(), \website\Website $eWebsite = new \website\Website()): string {

		\Asset::css('association', 'association.css');
		\Asset::js('association', 'association.js');

		$h = '<div id="association-donate-form-container">';

				$form = new \util\FormUi();

				if($eWebsite->empty()) {
					$h .= $form->openAjax('/association/donation:doCreatePayment', ['id' => 'association-donate']);
				} else {
					$h .= $form->openAjax(\website\WebsiteUi::url($eWebsite, '/:doDonate'), ['id' => 'website-donate', 'onrender' => 'Association.cleanArgs();']);
				}

					$h .= $form->hidden('from', LIME_URL);
					$h .= $form->dynamicGroups($eUser, ['email', 'firstName', 'lastName', 'phone']);
					$h .= $form->addressGroup(s("Adresse"), NULL, $eUser);

					$h .= new MembershipUi()->getAmountBlocks($form, [10, 20, 30]);

					$h .= $form->inputGroup($form->submit(s("Je donne")), ['class' => 'mt-1']);

				$h .= $form->close();

		$h .= '</div>';

		return $h;
	}

	public function getDocumentFilename(History $eHistory): string {
		return s("ouvretaferme-attestation-paiement-{id}", ['id' => $eHistory['id']]);
	}

	public function getDocumentMail(\farm\Farm $eFarm, History $eHistory): array {

		$template = match($eHistory['type']) {
			History::MEMBERSHIP => s("Bonjour,

Merci pour votre soutien !

Vous trouverez en pièce jointe votre attestation de paiement pour votre adhésion d'un montant de @amount.

Cordialement,
@farm"),
			History::DONATION => s("Bonjour,

Merci pour votre générosité !

Vous trouverez en pièce jointe votre attestation de paiement pour votre don d'un montant de @amount.

Cordialement,
@farm")
};
		$variables = ['farm' => encode($eFarm['name']), 'amount' => \util\TextUi::money($eHistory['amount'], precision: 0)];

		$title = match($eHistory['type']) {
			History::MEMBERSHIP => s("Reçu de votre adhésion {year} à l'association Ouvretaferme", ['year' => encode($eHistory['membership'])]),
			History::DONATION => s("Reçu de votre don à l'association Ouvretaferme"),
		};

		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getPdfTitle(): string {

		return s("Ouvretaferme - Reçu");

	}

	public function getPdfDocument(History $eHistory, \farm\Farm $eFarmOtf): string {

		\Asset::css('association', 'pdf.css');

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$h .= $this->getDocumentTop($eHistory, $eFarmOtf);

			$h .= '<div class="pdf-document-body">';

				$h .= '<table class="pdf-document-items ">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<td class="pdf-document-item-header">'.s("Désignation").'</td>';
							$h .= '<td class="pdf-document-item-header pdf-document-number">'.s("Quantité").'</td>';
							$h .= '<td class="pdf-document-item-header pdf-document-unit-price">'.s("Prix unitaire").'</td>';
							$h .= '<td class="pdf-document-item-header pdf-document-price">'.s("Montant").'</td>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';
						$h .= '<tr class="pdf-document-item pdf-document-item-last">';
							$h .= '<td class="pdf-document-product">';
								$h .= match($eHistory['type']) {
									History::MEMBERSHIP => s("Adhésion {year} à l'association Ouvretaferme", ['year' => $eHistory['membership']]),
									History::DONATION => s("Don"),
								};
							$h .= '</td>';
							$h .= '<td class="pdf-document-number">1</td>';
							$h .= '<td class="pdf-document-unit-price">'.\util\TextUi::money($eHistory['amount']).'</td>';
							$h .= '<td class="pdf-document-price">'.\util\TextUi::money($eHistory['amount']).'</td>';
						$h .= '</tr>';
						$h .= '<tr class="pdf-document-item-total">';
						$h .= '<td colspan="4">';
							$h .= '<div class="pdf-document-total">';
								$h .= '<div class="pdf-document-total-label">Total</div>';
								$h .= '<div class="pdf-document-total-value">'.\util\TextUi::money($eHistory['amount']).'</div>';
							$h .= '</div>';
						$h .= '</td>';

					$h .= '</tr>';
				$h .= '</tbody>';

			$h .= '</table>';

			$h .= '<p>';
				$h .= s("Paiement réalisé le {date}", ['date' => \util\DateUi::numeric($eHistory['paidAt'])]);
			$h .= '</p>';
			$h .= '<p>';
				$h .= s("Moyen de paiement : {paymentMethod}", ['paymentMethod' => encode($eHistory['sale']['paymentMethod']['name'])]);
			$h .= '</p>';

			$h .= '<div class="pdf-document-custom-bottom">';
				if($eHistory['type'] === History::DONATION) {

					$h .= s("L'association <b>Ouvretaferme</b> vous remercie pour votre générosité !");

				} else {

					if($eHistory['amount'] > \Setting::get('association\membershipFee')) {

						$h .= s("L'association <b>Ouvretaferme</b> vous remercie pour votre engagement et votre soutien !");

					} else {

						$h .= s("L'association <b>Ouvretaferme</b> vous remercie pour votre engagement !");

					}
				}
			$h .= '</div>';
		$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	protected function getDocumentTop(History $eHistory, \farm\Farm $eFarm): string {

		$eCustomer = $eHistory['customer'];
		$logo = new \media\FarmLogoUi()->getUrlByElement($eFarm, 'm');

		$h = '<div class="pdf-document-header">';

			$h .= '<div class="pdf-document-vendor '.($logo !== NULL ? 'pdf-document-vendor-with-logo' : '').'">';
				if($logo !== NULL) {
					$h .= '<div class="pdf-document-vendor-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-document-vendor-name">';
					$h .= encode($eFarm['legalName']).'<br/>';
				$h .= '</div>';
			$h .= '<div class="pdf-document-vendor-address">';
				$h .= $eFarm->getLegalAddress('html');
			$h .= '</div>';

			if($eFarm['siret']) {

				$h .= '<div class="pdf-document-vendor-registration">';
					$h .= s("SIRET <u>{value}</u>", encode($eFarm['siret']));
				$h .= '</div>';

			}

		$h .= '</div>';

		$h .= '<div class="pdf-document-top">';

			$h .= '<div class="pdf-document-structure">';

				$h .= '<h2 class="pdf-document-title">'.s("Attestation de paiement").'</h2>';

				$h .= '<div class="pdf-document-details">';

						$h .= '<div class="pdf-document-detail-label">'.s("Reçu n°").'</div>';
						$h .= '<div><b>'.$eHistory['id'].'</b></div>';

					$h .= '<div class="pdf-document-detail-label">'.s("Date d'émission").'</div>';
					$h .= '<div>'.\util\DateUi::numeric(date('Y-m-d'), \util\DateUi::DATE).'</div>';

				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="pdf-document-customer">';

				$h .= '<div class="pdf-document-customer-name">';
					if($eCustomer['type'] === \selling\Customer::PRO) {
						$h .= encode($eCustomer->getLegalName()).'<br/>';
					} else {
						$h .= encode($eCustomer['firstName']).' '.encode($eCustomer['lastName']);
					}
				$h .= '</div>';

				if($eCustomer->hasInvoiceAddress()) {
					$h .= '<div class="pdf-document-customer-address">';
						$h .= $eCustomer->getInvoiceAddress('html');
					$h .= '</div>';
				}

				$email = $eCustomer['email'] ?? $eCustomer['user']['email'] ?? NULL;

				if($email) {
					$h .= '<div class="pdf-document-customer-email">';
						$h .= encode($email);
					$h .= '</div>';
				}

				if($eCustomer['siret'] !== NULL or $eCustomer['invoiceVat'] !== NULL) {
					$h .= '<br/>';
				}

				if($eCustomer['siret'] !== NULL) {
					$h .= '<div class="pdf-document-customer-registration">';
						$h .= s("SIRET <u>{value}</u>", encode($eCustomer['siret']));
					$h .= '</div>';
				}
				if($eCustomer['invoiceVat'] !== NULL) {
					$h .= '<div class="pdf-document-customer-registration">';
						$h .= s("TVA intracommunautaire <u>{value}</u>", encode($eCustomer['invoiceVat']));
					$h .= '</div>';
				}

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}
}
?>
