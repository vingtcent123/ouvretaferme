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
					$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/imports:import" class="btn btn-primary">'.\Asset::icon('file-earmark-plus').' '.s("Importer un relevé OFX").'</a>';
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getPeriod(string $period, \account\FinancialYear $eFinancialYear): string {

		if($period > date('Y-m-d')) {
			$period = date('Y-m-d');
		}
		$year = $eFinancialYear->getLabel();

		if(mb_strlen($year) === 4) {
			return \util\DateUi::numeric($period,\util\DateUi::DAY_MONTH);
		}

		return \util\DateUi::numeric($period, \util\DateUi::DATE);
	}

	public function getImport(
		\farm\Farm $eFarm,
		\Collection $cImport,
		array $imports
	): string {

		$eFarm->expects(['eFinancialYear']);

		\Asset::css('bank', 'import.css');

		$eFinancialYearSelected = $eFarm['eFinancialYear'];

		if($cImport->empty() === TRUE) {

			$h = '<div class="util-block-help">'.("Importez ici vos opérations bancaires. Cela vous permettra de les relier à des écritures comptables et à suivre votre trésorerie.").'</div>';

			$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/imports:import" class="btn btn-primary">'.\Asset::icon('file-earmark-plus').' '.s("Importer un relevé OFX").'</a>';

			return $h;
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
				$endPeriod = $this->getPeriod($import['endPeriod'], $eFinancialYearSelected);
				$startPeriod = $this->getPeriod($import['startPeriod'], $eFinancialYearSelected);
				if($endPeriod === $startPeriod and $eImport->empty()) {
					continue;
				}

				$h .= '<div class="import-timeline import-timeline-only">';
					$h .= '<div class="import-timeline-item">';
						$h .= '<div class="import-timeline-circle">';
							if(substr($endPeriod, 0, 5) === date('d/m')) {
								$h .= s("Aujourd'hui");
							} else {
								$h .= $endPeriod;
							}
							$h .= '<br />';
							$h .= $startPeriod;
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="import-timeline-action">';
						$h .= '<div class="import-timeline-action-title">';
							if($eImport->empty()) {
								$h .= '<div class="util-block-optional mt-1 mb-1">';
									$h .= \Asset::icon('exclamation-triangle', ['class' => 'mr-1']).'&nbsp;'.s("Aucun import n'a couvert cette période");
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
			ImportElement::NONE => ['class' => 'util-block-optional mb-0', 'text' => s("Aucune donnée importée")],
			ImportElement::PROCESSING => ['class' => 'util-info mb-0', 'text' => s("En cours d'import")],
			ImportElement::FULL => ['class' => 'util-block-success mb-0', 'text' => s("Import total #{id} du {date}", $args)],
			ImportElement::PARTIAL => ['class' => 'util-block-warning', 'text' => s("Import partiel #{id} du {date}", $args)],
			ImportElement::ERROR => ['class' => 'util-block-danger', 'text' => s("Import #{id} en erreur", $args)],
		});

		$h .= '<div class="'.$status['class'].'">';
			$h .= '<h4>'.encode($status['text']).'</h4>';

			$h .= '<div>';
				$h .= \Asset::icon('chevron-right', ['class' => 'mr-1']);
				$h .= s("Fichier {name}", ['name' => '<i>'.encode($eImport['filename']).'</i>']);
			$h .= '</div>';

			$h .= '<div>';

				if(in_array($eImport['status'], [ImportElement::FULL, ImportElement::PARTIAL]) === TRUE) {

					$h.= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/operations?import='.$eImport['id'].'" class="color-white">';
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

	public function update(\farm\Farm $eFarm, Import $eImport, \Collection $cBankAccount): \Panel {

		$h = '<div class="util-info">';
			$h .= s("Le compte bancaire de votre import n°{value} n'a pas pu être détecté. Souhaitez-vous en choisir un ou créer un nouveau compte bancaire ?", $eImport['id']);
		$h .= '</div>';

		$form = new \util\FormUi();
		$h .= $form->openAjax(\company\CompanyUi::urlBank($eFarm).'/import:doUpdateAccount');

			$h .= $form->hidden('id', $eImport['id']);

			$values = [];
			foreach($cBankAccount as $eBankAccount) {
				$label = $eBankAccount['accountId'].' - '.$eBankAccount['label'];
				if($eBankAccount['description']) {
					$label .= ' - '.$eBankAccount['description'];
				}
				$values[$eBankAccount['id']] = encode($label);
			}
			$values[0] = s("Créer un nouveau bancaire automatiquement");

			$h .= $form->group(s("Compte bancaire"), $form->select('account', $values, attributes: ['mandatory' => TRUE]));

			$h .= $form->group('', $form->submit(s("Enregistrer")));

		$h .= $form->close();

		return new \Panel(
			id: 'panel-import-account',
			title: s("Configurer le compte bancaire"),
			body: $h
		);
	}
}
?>
