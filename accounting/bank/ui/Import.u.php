<?php
namespace bank;

class ImportUi {

	public function __construct() {
	}

	public function getImportTitle(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Historique des imports de relevés bancaires");
			$h .= '</h1>';

			if(
				$eFinancialYear['status'] === \accounting\FinancialYearElement::OPEN
				and $eFinancialYear['endDate'] >= date('Y-m-d')
				and $eCompany->canWrite() === TRUE
			) {

				$h .= '<div>';
					$h .= '<a href="'.\company\CompanyUi::urlBank($eCompany).'/import:import" class="btn btn-primary">'.\Asset::icon('file-earmark-plus').' '.s("Importer un relevé .ofx").'</a>';
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getPeriod(string $period, \accounting\FinancialYear $eFinancialYear): string {

		if($period > date('Y-m-d')) {
			$period = date('Y-m-d');
		}
		$year = \accounting\FinancialYearUi::getYear($eFinancialYear);

		if(mb_strlen($year) === 4) {
			return \util\DateUi::numeric($period,\util\DateUi::DAY_MONTH);
		}

		return \util\DateUi::numeric($period, \util\DateUi::DATE);
	}

	public function getImport(
		\company\Company $eCompany,
		\Collection $cImport,
		array $imports,
		\accounting\FinancialYear $eFinancialYearSelected,
	): string {
		\Asset::css('bank', 'flow.css');

		if($cImport->empty() === TRUE) {
			return '<div class="util-info">'.
				s("Aucun import bancaire n'a été réalisé pour l'exercice {year}", ['year' => \accounting\FinancialYearUi::getYear($eFinancialYearSelected)]).
				'</div>';
		}

		$h = '<div class="flow-timeline-wrapper stick-xs">';

			// Timeline header
			$h .= '<div class="flow-timeline flow-timeline-header">';

				$h .= '<div class="util-grid-header util-grid-icon text-center">';
					$h .= \Asset::icon('calendar-week');
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="flow-timeline-body">';

			foreach(array_reverse($imports) as $import) {
				$eImport = $import['import'];
				$h .= '<div class="flow-timeline flow-timeline-only">';
					$h .= '<div class="flow-timeline-item">';
						$h .= '<div class="flow-timeline-circle">';
							if($import['endPeriod'] === date('Y-m-d')) {
								$h .= s("Aujourd'hui");
							} else {
								$h .= $this->getPeriod($import['endPeriod'], $eFinancialYearSelected);
							}
							$h .= '<br />';
							$h .= $this->getPeriod($import['startPeriod'], $eFinancialYearSelected);
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="flow-timeline-action">';
						$h .= '<div class="flow-timeline-action-title">';
							if($eImport->exists() === FALSE) {
								$h .= '<div class="util-warning mt-1 mb-1">';
									$h .= \Asset::icon('exclamation-circle').'&nbsp;'.s("Aucun import n'a couvert cette période");
								$h .= '</div>';
							} else {
								$h .= $this->getImportDetails($eCompany, $eImport);
							}
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';
			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getImportDetails(\company\Company $eCompany, Import $eImport): string {

		$h = '';

		$status = (match($eImport['status']) {
			ImportElement::NONE => ['class' => 'util-info mb-0', 'title' => s("Aucune donnée importée"), 'icon' => 'slash-circle'],
			ImportElement::PROCESSING => ['class' => 'util-info mb-0', 'title' => s("En cours d'import"), 'icon' => 'arrow-repeat'],
			ImportElement::FULL => ['class' => 'util-success mb-0', 'title' => s("Totalement importé"), 'icon' => 'check2-all'],
			ImportElement::PARTIAL => ['class' => 'util-warning', 'title' => s("Partiellement importé"), 'icon' => 'check2'],
			ImportElement::ERROR => ['class' => 'util-info', 'title' => s("En erreur"), 'icon' => 'exclamation-octogon'],
		});

		$h .= '<div class="'.$status['class'].'" title="'.$status['title'].'">';
			$h .= \Asset::icon($status['icon']);
			$h .= '&nbsp;';
			$h .= s("Import du {date}", ['date' => \util\DateUi::numeric($eImport['processedAt'], \util\DateUi::DATE)]);

			$h .= '<div class="color-text">';
				$h .= \Asset::icon('chevron-right');
				$h .= s("Fichier {name}", ['name' => '<i>'.encode($eImport['filename']).'</i>']);
			$h .= '</div>';

			$h .= '<div>';

				if(in_array($eImport['status'], [ImportElement::FULL, ImportElement::PARTIAL]) === TRUE) {

					$h.= '<a href="'.\company\CompanyUi::urlBank($eCompany).'/cashflow?import='.$eImport['id'].'" class="color-text">';
						$h.= \Asset::icon('chevron-right');
						$h.= p(
							"{number} mouvement enregistré",
							"{number} mouvements enregistrés",
							count($eImport['result']['imported']),
							['number' => count($eImport['result']['imported'])]
						);
					$h.= '</a>';
					if(empty($eImport['result']['alreadyImported']) === FALSE) {
						$h .= '<div>';
						$h.= \Asset::icon('slash-circle');
						$h.= p(
							"{number} mouvement ignoré (déjà importé)",
							"{number} mouvements ignorés (déjà importés)",
							count($eImport['result']['alreadyImported']),
							['number' => count($eImport['result']['alreadyImported'])]
						);
						$h .= '</div>';
					}

				} else if($eImport['status'] === ImportElement::NONE) {

					$h.= \Asset::icon('chevron-right');
					$h.= s("Aucun mouvement enregistré");

				} else if($eImport['status'] === ImportElement::ERROR) {

					$h.= \Asset::icon('chevron-right');
					$h.= s("Cet import n'a pas pu être réalisé");

				}
			$h.= '</div>';

		$h .= '</div>';

		return $h;
	}

}
?>
