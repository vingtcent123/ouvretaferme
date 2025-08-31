<?php
namespace association;

class AssociationUi {

	public function getProductDonationName(): string {

		return s("Don à l'association Ouvretaferme (merci !)");

	}
	public function getMembershipProductName(): string {

		$year = date('Y');

		return s("Adhésion {year} à l'association Ouvretaferme", ['year' => $year]);

	}

	public static function confirmationUrl(\farm\Farm $eFarm, string $type): string {
		return self::url($eFarm).'?success=association:Membership::'.$type.'.created';
	}

	public static function url(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/ferme/'.$eFarm['id'].'/adherer';
	}

	public function getDocumentFilename(History $eHistory): string {
		return s("ouvretaferme-attestation-paiement-{date}-{id}", ['date' => substr($eHistory['paidAt'], 0, 10), 'id' => $eHistory['id']]);
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

		$eCustomer = $eHistory['sale']['customer'];
		$logo = new \media\FarmLogoUi()->getUrlByElement($eFarm, 'm');

		$h = '<div class="pdf-document-header">';

			$h .= '<div class="pdf-document-vendor '.($logo !== NULL ? 'pdf-document-vendor-with-logo' : '').'">';
				if($logo !== NULL) {
					$h .= '<div class="pdf-document-vendor-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-document-vendor-name">';
					$h .= encode($eFarm['legalName'] ?? '').'<br/>';
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
					$h .= encode($eCustomer->getLegalName()).'<br/>';
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
