<?php
namespace vat;

class DeclarationUi {

	public function __construct() {
	}

	public function getHistory(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cDeclaration, array $allPeriods): string {

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

						$eDeclaration = $cDeclaration->find(fn($e) => $e['from'] === $period['from'] and $e['to'] === $period['to'])->first() ?? new Declaration();

						$h .= '<tr class="';
							if($period['to'] > date('Y-m-d')) {
								$h .= 'vat-history-not-passed';
							}
						$h .= '">';
							$h .= '<td>';
								$text = s("{startDate} au {endDate}",
									['startDate' => \util\DateUi::numeric($period['from']), 'endDate' => \util\DateUi::numeric($period['to'])]
								);
								if($eDeclaration->empty()) {
									$h .= $text;
								} else {
									$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/declaration-de-tva?tab=cerfa&id='.$eDeclaration['id'].'">';
										$h .= $text;
									$h .= '</a>';
								}
							$h .= '</td>';
							$h .= '<td class="';
								if($period['limit'] < date('Y-m-d') and $eDeclaration->notEmpty() and $eDeclaration['status'] !== Declaration::DECLARED) {
									$h .= 'color-danger';
								} else if (($period['limit'] < date('Y-m-d', strtotime(\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days ago')) and ($eDeclaration->empty() or $eDeclaration['status'] !== Declaration::DECLARED))) {
									$h .= 'color-warning';
								}
								$h .= '">';
								$h .= \util\DateUi::numeric($period['limit']);
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eDeclaration->notEmpty()) {
									$h .= encode(self::p('status')->values[$eDeclaration['status']]);
								} else {
									if($period['from'] <= date('Y-m-d') and $period['to'] >= date('Y-m-d')) {
										$h .= s("En cours...");
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eDeclaration->empty()) {
									$h .= '-';
								} else {
									$h .= \util\DateUi::numeric($eDeclaration['createdAt']);
									$h .= '<div class="font-sm">('.s("par {value}", $eDeclaration['createdBy']->getName()).')</div>';
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eDeclaration->empty() or isset($eDeclaration['data']['0705']) === FALSE) {
									$h .= '-';
								} else {
									if((int)$eDeclaration['data']['0705'] > 0) {
										$h .= s("Crédit de TVA");
									} else {
										$h .= s("TVA à payer");
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eDeclaration->empty() or isset($eDeclaration['data']['0705']) === FALSE) {
									$h .= '-';
								} else {
									if((int)$eDeclaration['data']['0705'] > 0) {
										$h .= \util\TextUi::money($eDeclaration['data']['0705']);
									} else {
										$h .= \util\TextUi::money($eDeclaration['data']['8900']);
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eDeclaration->empty()) {
									$h .= '-';
								} else {
									if($eDeclaration['declaredAt'] !== NULL) {
										$h .= \util\DateUi::numeric($eDeclaration['declaredAt']);
										$h .= '<div class="font-sm">('.s("par {value}", $eDeclaration['declaredBy']->getName()).')</div>';
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								if($eDeclaration->empty()) {
									$h .= '-';
								} else if($eDeclaration['accountedAt'] === NULL) {
									$h .= s("Non");
									if($eDeclaration['declaredAt'] !== NULL) {
										$h .= '<br /><a class="font-sm" href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eDeclaration['id'].'">'.s("Voir les écritures proposées").'</a>';
									}
								} else {
									$h .= \util\DateUi::numeric($eDeclaration['accountedAt']);
									$h .= '<div class="font-sm">('.s("par {value}", $eDeclaration['accountedBy']->getName()).')</div>';
										$h .= '<a class="font-sm" href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eDeclaration['id'].'">'.s("Revoir les écritures proposées").'</a>';
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

		$d = Declaration::model()->describer($property, [
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
					Declaration::CA12 => s("CA12"),
					Declaration::CA3 => s("CA3"),
				];
				break;

			case 'status':
				$d->values = [
					Declaration::DECLARED => s("Déclarée"),
					Declaration::DRAFT => s("Créée"),
					Declaration::ACCOUNTED => s("Comptabilisée"),
				];
				break;


		}

		return $d;

	}

}

?>
