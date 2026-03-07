<?php
namespace selling;

class ArchiveUi {

	public function getCsvPaymentHeader(): array {

		return [
			[
				'*** '.s("PAIEMENTS").' ***'
			],
			[
				s("Référence"),
				s("Moyen de paiement"),
				s("Montant payé"),
				s("Date de paiement"),
			]
		];

	}

	public function getCsvItemHeader(): array {

		return [
			[
				'*** '.s("ARTICLES").' ***'
			],
			[
				s("Référence"),
				s("Libellé"),
				s("Quantité"),
				s("Prix unitaire TTC"),
				s("Montant TTC"),
				s("Montant HT"),
				s("Taux de TVA"),
			]
		];

	}

	public function getCsvTransactionHeader(): array {

		return [
			[
				'*** '.s("TRANSACTIONS").' ***'
			],
			[
				s("Référence"),
				s("Facture"),
				s("Date"),
				s("Montant TTC"),
				s("Montant HT"),
				s("Remise commerciale en %"),
				s("État de la transaction"),
				s("État du paiement"),
			]
		];

	}

	public static function getReference(Payment $ePayment): ?string {

		if($ePayment['sale']->notEmpty()) {
			return self::getSaleReference($ePayment['sale']);
		}

		if($ePayment['invoice']->notEmpty()) {
			return self::getInvoiceReference($ePayment['invoice']);
		}

	}

	public static function getSaleReference(Sale $eSale): ?string {
		return s("Vente n°{value}", $eSale['document']);
	}

	public static function getInvoiceReference(Invoice $eInvoice): ?string {
		return s("Facture n°{value}", $eInvoice['number']);
	}

	public function getList(\Collection $cArchive): string {

		$h = '<div class="util-block-info">';
		$h .= \Asset::icon('info', ['class' => 'util-block-icon']);
		$h .= '<p>'.s("Les archives de vos données de vente contiennent l'intégralité de vos ventes destinées aux clients particuliers pour lesquelles un paiement a été enregistré ou qui ont marquées comme livrées. Elles peuvent être communiquées à l'administration fiscale en cas de contrôle ou être utilisées pour vos analyses personnelles.").'</p>';
		$h .= '</div>';

		if($cArchive->empty()) {
			$h .= '<div class="util-empty">'.s("Vous n'avez pas encore généré d'archive de vos ventes.").'</div>';
			return $h;
		}

		$h .= '<div class="util-overflow-md">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-min-content text-center">'.s("Archive").'</th>';
						$h .= '<th>'.s("Début").'</th>';
						$h .= '<th>'.s("Fin").'</th>';
						$h .= '<th>'.s("SHA256").'</th>';
						$h .= '<th>'.s("Généré").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cArchive as $eArchive) {

					$h .= '<tr>';
						$h .= '<td class="text-center">';
							$h .= '<div class="btn btn-outline-primary btn-readonly btn-xs">'.$eArchive['id'].'</div>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['from']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['to']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<span class="color-muted font-sm">'.$eArchive['sha256'].'</span>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['createdAt'], \util\DateUi::DATE_HOUR_MINUTE);
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a href="/selling/archives:get?id='.$eArchive['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger").'</a>';
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(\selling\Archive $eArchive): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/archives:doCreate', ['class' => 'archive-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eArchive['farm']);
			$h .= $form->dynamicGroups($eArchive, ['from*', 'to*']);

			$h .= $form->group(
				content: $form->submit(
					s("Créer l'archive"),
					['data-waiter' => s("Création en cours")]
				)
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-archive-create',
			title: s("Créer une archive des ventes"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \selling\Archive::model()->describer($property, [
			'from' => s("Début des données à archiver"),
			'to' => s("Fin des données à archiver"),
		]);

		return $d;

	}

}
?>
