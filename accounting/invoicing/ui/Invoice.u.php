<?php
namespace invoicing;

Class InvoiceUi {

	public function __construct() {
		\Asset::css('invoicing', 'invoicing.css');
	}

	public function summary(Invoice $eInvoice): string {

		$h = '<div class="sale-presentation util-block stick-xs">';

			$h .= '<dl class="util-presentation util-presentation-2">';
				if($eInvoice['direction'] === Invoice::OUT) {
					$h .= '<dt>'.s("Client").'</dt>';
					$h .= '<dd>'.encode($eInvoice['buyer']['name'] ?? '').'</dd>';
				} else {
					$h .= '<dt>'.s("Vendeur").'</dt>';
					$h .= '<dd>'.encode($eInvoice['seller']['name'] ?? '').'</dd>';
				}
				$h .= '<dt>'.s("Date de vente").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eInvoice['issuedAt']).'</dd>';
				$h .= '<dt>'.s("Statut").'</dt>';
				$h .= '<dd>';
					$h .= InvoiceUi::p('status')->values[$eInvoice['status']];
					if($eInvoice['synchronizedAt'] !== NULL) {
						$h .= ' <div class="color-muted font-sm" style="font-weight: normal; line-height: 1.2">('.\util\DateUi::numeric($eInvoice['synchronizedAt'], \util\DateUi::DATE_HOUR_MINUTE).')</div>';
					}
				$h .= '</dd>';
			$h .= '</dl>';

		$h .= '</div>';

		$h .= '<table class="table-transparent tr-bordered invoice-summary">';
			$h .= '<tbody>';
				$h .= '<tr>';
					$h .= '<td>'.s("Montant total TTC").'</td>';
					$h .= '<td class="invoice-summary-value invoice-summary-value-highlight">'.\util\TextUi::money($eInvoice['amountIncludingVat']).'</td>';
				$h .= '</tr>';
				$h .= '<tr class="color-muted">';
					$h .= '<td style="padding-left: 2rem">'.s("Dont TVA").'</td>';
					$h .= '<td class="invoice-summary-value">'.\util\TextUi::money($eInvoice['vat']).'</td>';
				$h .= '</tr>';
				$h .= '<tr class="color-muted">';
					$h .= '<td style="padding-left: 2rem">'.s("Dont montant HT").'</td>';
					$h .= '<td class="invoice-summary-value">'.\util\TextUi::money($eInvoice['amountExcludingVat']).'</td>';
				$h .= '</tr>';
			$h .= '</tbody>';
		$h .= '</table>';

		return $h;
	}

	public function list(\farm\Farm $eFarm, \Collection $cInvoice, string $direction): string {
		
		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Numéro").'</th>';
						$h .= '<th>';
							if($direction === Invoice::IN) {
								$h .= s("Vendeur");
							} else {
								$h .= s("Client");
							}
						$h .= '</th>';
						$h .= '<th>'.s("État").'</th>';
						$h .= '<th>'.s("Date de facture").'</th>';
						$h .= '<th>'.s("Date de paiement").'</th>';
						$h .= '<th class="t-highlight text-center">'.s("Montant").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cInvoice as $eInvoice) {
						$h .= '<tr>';
							$h .= '<td><a class="btn btn-sm btn-outline-primary" href="'.\farm\FarmUi::urlConnected($eFarm).'/facturation-electronique/facture/'.$eInvoice['id'].'">'.encode($eInvoice['number']).'</a></td>';
							$h .= '<td>';
								if($eInvoice['direction'] === Invoice::OUT) {
									$h .= encode($eInvoice['buyer']['name'] ?? '');
								} else {
									$h .= encode($eInvoice['seller']['name'] ?? '');
								}
							$h .= '</td>';
							$h .= '<td></td>';
							$h .= '<td>'.\util\DateUi::numeric($eInvoice['issuedAt']).'</td>';
							$h .= '<td>'.($eInvoice['paymentDueAt'] ? \util\DateUi::numeric($eInvoice['paymentDueAt']) : '').'</td>';
							$h .= '<td class="t-highlight text-center">';
								if($eInvoice['amountIncludingVat'] !== NULL) {
									$h .= \util\TextUi::money($eInvoice['amountIncludingVat']).' <small class="color-muted">'.s("TTC").'</small>';
								} else if($eInvoice['amountExcludingVat'] !== NULL) {
									$h .= \util\TextUi::money($eInvoice['amountExcludingVat']).' <small class="color-muted">'.s("HT").'</small>';
								}
							$h .= '</td>';
						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Invoice::model()->describer($property, [
			'status' => s("Statut"),
		]);

		switch($property) {

			case 'status' :
				$d->values = [
					Invoice::SYNCHRONIZED => s("Synchronisée"),
					Invoice::ERROR => s("En erreur"),
				];
				break;

		}

		return $d;

	}

}
