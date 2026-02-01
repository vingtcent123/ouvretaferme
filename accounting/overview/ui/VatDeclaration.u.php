<?php
namespace overview;

class VatDeclarationUi {

	public function __construct() {
	}

	public function getHistory(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cVatDeclaration, array $allPeriods): string {

		$h = '<div class="stick-sm util-overflow-sm">';
			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Période déclarée").'</th>';
						$h .= '<th>'.s("Date limite").'</th>';
						$h .= '<th class="text-center">'.s("Statut").'</th>';
						$h .= '<th class="text-center">'.s("Création").'</th>';
						$h .= '<th class="text-center">'.s("Crédit ou TVA à payer").'</th>';
						$h .= '<th class="text-end">'.s("Résultat net").'</th>';
						$h .= '<th class="text-center">'.s("Déclaration").'</th>';
						$h .= '<th class="text-center">'.s("Comptabilisée").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($allPeriods as $period) {

						$eVatDeclaration = $cVatDeclaration->find(fn($e) => $e['from'] === $period['from'] and $e['to'] === $period['to'])->first() ?? new VatDeclaration();

						$h .= '<tr class="';
							if($period['to'] > date('Y-m-d')) {
								$h .= 'vat-history-not-passed';
							}
						$h .= '">';
							$h .= '<td>';
								$text = s("{startDate} au {endDate}",
									['startDate' => \util\DateUi::numeric($period['from']), 'endDate' => \util\DateUi::numeric($period['to'])]
								);
								if($eVatDeclaration->empty()) {
									$h .= $text;
								} else {
									$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva?tab=cerfa&id='.$eVatDeclaration['id'].'">';
										$h .= $text;
									$h .= '</a>';
								}
							$h .= '</td>';
							$h .= '<td class="';
								if($period['limit'] < date('Y-m-d') and $eVatDeclaration->notEmpty() and $eVatDeclaration['status'] !== VatDeclaration::DECLARED) {
									$h .= 'color-danger';
								} else if (($period['limit'] < date('Y-m-d', strtotime(VatDeclarationLib::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days ago')) and ($eVatDeclaration->empty() or $eVatDeclaration['status'] !== VatDeclaration::DECLARED))) {
									$h .= 'color-warning';
								}
								$h .= '">';
								$h .= \util\DateUi::numeric($period['limit']);
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eVatDeclaration->notEmpty()) {
									$h .= encode(self::p('status')->values[$eVatDeclaration['status']]);
								} else {
									if($period['from'] <= date('Y-m-d') and $period['to'] >= date('Y-m-d')) {
										$h .= s("En cours...");
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eVatDeclaration->empty()) {
									$h .= '-';
								} else {
									$h .= \util\DateUi::numeric($eVatDeclaration['createdAt']);
									$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['createdBy']->getName()).')</div>';
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eVatDeclaration->empty() or isset($eVatDeclaration['data']['0705']) === FALSE) {
									$h .= '-';
								} else {
									if((int)$eVatDeclaration['data']['0705'] > 0) {
										$h .= s("Crédit de TVA");
									} else {
										$h .= s("TVA à payer");
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eVatDeclaration->empty() or isset($eVatDeclaration['data']['0705']) === FALSE) {
									$h .= '-';
								} else {
									if((int)$eVatDeclaration['data']['0705'] > 0) {
										$h .= \util\TextUi::money($eVatDeclaration['data']['0705']);
									} else {
										$h .= \util\TextUi::money($eVatDeclaration['data']['8900']);
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eVatDeclaration->empty()) {
									$h .= '-';
								} else {
									if($eVatDeclaration['declaredAt'] !== NULL) {
										$h .= \util\DateUi::numeric($eVatDeclaration['declaredAt']);
										$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['declaredBy']->getName()).')</div>';
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eVatDeclaration->empty()) {
									$h .= '-';
								} else {
									if($eVatDeclaration['accountedAt'] === NULL) {
										$h .= s("Non");
										if($eVatDeclaration['declaredAt'] !== NULL) {
											$h .= '<br /><a class="font-sm" href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eVatDeclaration['id'].'">'.s("Voir les écritures proposées").'</a>';
										}
									} else {
										$h .= \util\DateUi::numeric($eVatDeclaration['accountedAt']);
										$h .= '<div class="font-sm">('.s("par {value}", $eVatDeclaration['accountedBy']->getName()).')</div>';
									}
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
