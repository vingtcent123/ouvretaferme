<?php
namespace bank;

class ImportUi {

	public function __construct() {
	}

	public function getImportTitle(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Historique des imports de relevés bancaires");
			$h .= '</h1>';

			if(
				$eFinancialYear['status'] === \account\FinancialYearElement::OPEN
				and $eFinancialYear['endDate'] >= date('Y-m-d')
				and $eFarm->canManage() === TRUE
			) {

				$h .= '<div>';
					$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/import:import" class="btn btn-primary">'.\Asset::icon('file-earmark-plus').' '.s("Importer un relevé .ofx").'</a>';
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getPeriod(string $period, \account\FinancialYear $eFinancialYear): string {

		if($period > date('Y-m-d')) {
			$period = date('Y-m-d');
		}
		$year = \account\FinancialYearUi::getYear($eFinancialYear);

		if(mb_strlen($year) === 4) {
			return \util\DateUi::numeric($period,\util\DateUi::DAY_MONTH);
		}

		return \util\DateUi::numeric($period, \util\DateUi::DATE);
	}

	public function getImport(
		\farm\Farm $eFarm,
		\Collection $cImport,
		array $imports,
		\account\FinancialYear $eFinancialYearSelected,
	): string {
		\Asset::css('bank', 'import.css');

		if($cImport->empty() === TRUE) {
			return '<div class="util-info">'.
				s("Aucun import bancaire n'a été réalisé pour l'exercice {year}", ['year' => \account\FinancialYearUi::getYear($eFinancialYearSelected)]).
				'</div>';
		}

		$h = '<div class="import-timeline-wrapper stick-xs">';

			// Timeline header
			$h .= '<div class="import-timeline import-timeline-header">';

				$h .= '<div class="util-grid-header util-grid-icon text-center">';
					$h .= \Asset::icon('calendar-week');
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="import-timeline-body">';

			foreach(array_reverse($imports) as $import) {
				$eImport = $import['import'];
				$h .= '<div class="import-timeline import-timeline-only">';
					$h .= '<div class="import-timeline-item">';
						$h .= '<div class="import-timeline-circle">';
							if($import['endPeriod'] === date('Y-m-d')) {
								$h .= s("Aujourd'hui");
							} else {
								$h .= $this->getPeriod($import['endPeriod'], $eFinancialYearSelected);
							}
							$h .= '<br />';
							$h .= $this->getPeriod($import['startPeriod'], $eFinancialYearSelected);
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="import-timeline-action">';
						$h .= '<div class="import-timeline-action-title">';
							if($eImport->empty()) {
								$h .= '<div class="util-warning-outline mt-1 mb-1">';
									$h .= \Asset::icon('exclamation-circle', ['class' => 'mr-1']).'&nbsp;'.s("Aucun import n'a couvert cette période");
								$h .= '</div>';
							} else {
								$h .= $this->getImportDetails($eFarm, $eImport);
							}
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';
			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getImportDetails(\farm\Farm $eFarm, Import $eImport): string {

		$h = '';

		$args = [
			'date' => \util\DateUi::numeric($eImport['processedAt'], \util\DateUi::DATE),
			'id' => $eImport['id'],
		];

		$status = (match($eImport['status']) {
			ImportElement::NONE => ['class' => 'util-info mb-0', 'text' => s("Aucune donnée importée"), 'icon' => 'slash-circle'],
			ImportElement::PROCESSING => ['class' => 'util-info mb-0', 'text' => s("En cours d'import"), 'icon' => 'arrow-repeat'],
			ImportElement::FULL => ['class' => 'util-success mb-0', 'text' => s("Import total #{id} du {date}", $args), 'icon' => 'check2-all'],
			ImportElement::PARTIAL => ['class' => 'util-warning-outline', 'text' => s("Import partiel #{id} du {date}", $args), 'icon' => 'check2'],
			ImportElement::ERROR => ['class' => 'util-info', 'text' => s("Import #{id} en erreur", $args), 'icon' => 'exclamation-octogon'],
		});

		$h .= '<div class="'.$status['class'].'">';
			$h .= \Asset::icon($status['icon'], ['class' => 'mr-1']);
			$h .= encode($status['text']);

			$h .= '<div class="color-text">';
				$h .= \Asset::icon('chevron-right', ['class' => 'mr-1']);
				$h .= s("Fichier {name}", ['name' => '<i>'.encode($eImport['filename']).'</i>']);
			$h .= '</div>';

			$h .= '<div>';

				if(in_array($eImport['status'], [ImportElement::FULL, ImportElement::PARTIAL]) === TRUE) {

					$h.= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow?import='.$eImport['id'].'" class="color-text">';
						$h.= \Asset::icon('chevron-right', ['class' => 'mr-1']);
						$h.= p(
							"{number} mouvement enregistré",
							"{number} mouvements enregistrés",
							count($eImport['result']['imported']),
							['number' => count($eImport['result']['imported'])]
						);
					$h.= '</a>';
					if(empty($eImport['result']['alreadyImported']) === FALSE) {
						$h .= '<div>';
						$h.= \Asset::icon('slash-circle', ['class' => 'mr-1']);
						$h.= p(
							"{number} mouvement ignoré (déjà importé)",
							"{number} mouvements ignorés (déjà importés)",
							count($eImport['result']['alreadyImported']),
							['number' => count($eImport['result']['alreadyImported'])]
						);
						$h .= '</div>';
					}

				} else if($eImport['status'] === ImportElement::NONE) {

					$h.= \Asset::icon('chevron-right', ['class' => 'mr-1']);
					$h.= s("Aucun mouvement enregistré");

				} else if($eImport['status'] === ImportElement::ERROR) {

					$h.= \Asset::icon('chevron-right', ['class' => 'mr-1']);
					$h.= s("Cet import n'a pas pu être réalisé");

				}
			$h.= '</div>';

		$h .= '</div>';

		return $h;
	}

}
?>
