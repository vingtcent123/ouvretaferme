<?php
namespace overview;

class VatDeclarationUi {

	public function __construct() {
	}

	public function getHistory(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cVatDeclaration): string {

		if($cVatDeclaration->empty()) {
			return '<div class="util-info">'.s("Il n'y a pas d'historique de déclaration de TVA pour cet exercice comptable.").'</div>';
		}


		$h = '<table class="tr-even tr-hover">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.s("Période déclarée").'</th>';
					$h .= '<th>'.s("Date limite").'</th>';
					$h .= '<th>'.s("Statut").'</th>';
					$h .= '<th>'.s("Création").'</th>';
					$h .= '<th>'.s("Crédit ou TVA à payer").'</th>';
					$h .= '<th>'.s("Résultat net").'</th>';
					$h .= '<th>'.s("Déclaration").'</th>';
					$h .= '<th>'.s("Comptabilisée").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cVatDeclaration as $eVatDeclaration) {

					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<a href="'.\company\CompanyUi::urlSummary($eFarm).'/vat:?tab=cerfa&id='.$eVatDeclaration['id'].'">';
								$h .= s("{startDate} au {endDate}",
										['startDate' => \util\DateUi::numeric($eVatDeclaration['from']), 'endDate' => \util\DateUi::numeric($eVatDeclaration['to'])]
									);
							$h .= '</a>';
						$h .= '</td>';
						$h .= '<td class="';
							if($eVatDeclaration['limit'] < date('Y-m-d') and $eVatDeclaration['status'] !== VatDeclaration::DECLARED) {
								$h .= 'color-danger';
							} else if (($eVatDeclaration['limit'] < date('Y-m-d', strtotime(VatDeclarationLib::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days ago')) and $eVatDeclaration['status'] !== VatDeclaration::DECLARED)) {
								$h .= 'color-warning';
							}
							$h .= '">';
							$h .= \util\DateUi::numeric($eVatDeclaration['limit']);
						$h .= '</td>';
						$h .= '<td>'.self::p('status')->values[$eVatDeclaration['status']].'</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eVatDeclaration['createdAt']);
							$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['createdBy']->getName()).')</div>';
						$h .= '</td>';
						$h .= '<td>';
							if((int)$eVatDeclaration['data']['49-number'] > 0) {
								$h .= s("Crédit de TVA");
							} else {
								$h .= s("TVA à payer");
							}
						$h .= '</td>';
						$h .= '<td>';
							if((int)$eVatDeclaration['data']['49-number'] > 0) {
								$h .= \util\TextUi::money($eVatDeclaration['data']['49-number']);
							} else {
								$h .= \util\TextUi::money($eVatDeclaration['data']['9992']);
							}
						$h .= '</td>';
						$h .= '<td>';
							if($eVatDeclaration['declaredAt'] !== NULL) {
								$h .= \util\DateUi::numeric($eVatDeclaration['declaredAt']);
								$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['declaredBy']->getName()).')</div>';
							}
						$h .= '</td>';
						$h .= '<td>';
							if($eVatDeclaration['accountedAt'] === NULL) {
								$h .= s("Non");
								if($eVatDeclaration['declaredAt'] !== NULL) {
									$h .= '<br /><a class="font-sm" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat:operations?id='.$eVatDeclaration['id'].'">'.s("Voir les écritures proposées").'</a>';
								}
							} else {
								$h .= \util\DateUi::numeric($eVatDeclaration['accountedAt']);
								$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['accountedBy']->getName()).')</div>';
							}
						$h .= '</td>';
					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';


		return $h;
	}

	public static function p(string $property): \PropertyDescriber {

		$d = VatDeclaration::model()->describer($property, [
			'status' => s("Status"),
			'cerfa' => s("Cerfa"),
			'createdAt' => s("Créée le"),
			'createdBy' => s("Créée par"),
			'updatedAt' => s("Modifiée le"),
			'declaredAt' => s("Marquée \"déclarée\" le"),
			'declaredBy' => s("Marquée \"déclarée\" par"),
		]);

		switch($property) {

			case 'cerfa' :
				$d->values = [
					VatDeclaration::CA12 => s("CA12"),
					VatDeclaration::CA3 => s("CA3"),
				];
				break;

			case 'status':
				$d->values = [
					VatDeclaration::DECLARED => s("Déclarée"),
					VatDeclaration::DRAFT => s("Créée"),
				];
				break;


		}

		return $d;

	}

}

?>
