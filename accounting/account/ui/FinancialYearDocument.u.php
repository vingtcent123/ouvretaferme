<?php
namespace account;

Class FinancialYearDocumentUi {

	public function list(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): \Panel {

		if($eFinancialYear['nOperation'] === 0) {

			$h = '<div class="util-empty">';
				$h .= '<p>'.s("Dès que vous aurez créé vos premières écritures dans cet exercice comptable, vous pourrez éditer vos documents comme les bilans, le compte de résultat...").'</p>';
				$h .= '<p>'.s("Voici plusieurs manières d'ajouter des écritures à votre exercice comptable :").'</p>';
			$h .= '</div>';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal" class="util-button">';

					$h .= '<h4>'.s("Créer une écriture depuis le journal").'</h4>';
					$h .= \Asset::icon('journal-bookmark');

				$h .= '</a>';

				$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/operations" class="util-button">';

					$h .= '<h4>'.s("Créer une écriture depuis mes opérations bancaires").'</h4>';
					$h .= \Asset::icon('piggy-bank');

				$h .= '</a>';

				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/?id='.$eFinancialYear['id'].'" class="util-button">';

					$h .= '<h4>'.s("Importer un fichier FEC depuis les paramètres de l'exercice").'</h4>';
					$h .= \Asset::icon('gear');

				$h .= '</a>';

			$h .= '</div>';

			return new \Panel(
				id: 'panel-financial-year-document',
				title: s("Tous les documents de l'exercice {value}", $eFinancialYear->getLabel()),
				body: $h,
			);

		}
		$hasDocumentGenerating = FALSE;
		\Asset::js('account', 'financialYearDocument.js');

		$documents = [
			\account\FinancialYearDocumentLib::INCOME_STATEMENT => ['accept' => NULL, 'label' => s("Compte de résultat"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
			\account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => ['accept' => NULL, 'label' => s("Compte de résultat avec synthèse"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
			\account\FinancialYearDocumentLib::SIG => ['accept' => NULL, 'label' => s("Soldes intermédiaires de gestion"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
			\account\FinancialYearDocumentLib::ASSET_AMORTIZATION => ['accept' => NULL, 'label' => s("Immobilisations : amortissements"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
			\account\FinancialYearDocumentLib::ASSET_ACQUISITION => ['accept' => NULL, 'label' => s("Immobilisations : acquisitions"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
			\account\FinancialYearDocumentLib::BALANCE => ['accept' => NULL, 'label' => s("Balance"), 'temporary' => $eFinancialYear->isClosed() === FALSE],
		];

		if($eFinancialYear->isClosed()) {

			$documents[\account\FinancialYearDocumentLib::CLOSING] = ['accept' => 'acceptGenerateClose', 'label' => s("Bilan de clôture"), 'temporary' => FALSE];
			$documents[\account\FinancialYearDocumentLib::CLOSING_DETAILED] = ['accept' => 'acceptGenerateClose', 'label' => s("Bilan de clôture détaillé"), 'temporary' => FALSE];

		} else {

			$documents[\account\FinancialYearDocumentLib::BALANCE_SHEET] = ['accept' => NULL, 'label' => s("Bilan"), 'temporary' => TRUE];

		}

		if($eFinancialYear['previous']->notEmpty()) {
			$documents[\account\FinancialYearDocumentLib::OPENING_DETAILED] = ['accept' => 'acceptGenerateOpen', 'label' => s("Bilan d'ouverture détaillé"), 'temporary' => FALSE];
			$documents[\account\FinancialYearDocumentLib::OPENING] = ['accept' => 'acceptGenerateOpen', 'label' => s("Bilan d'ouverture"), 'temporary' => FALSE];
		}

		uasort($documents, function($document1, $document2) {
			return strcmp($document1['label'], $document2['label']);
		});

		$showGeneration = count($eFinancialYear['cDocument']->find(fn($e) => $e['generation'] === FinancialYearDocument::SUCCESS)->getColumn('generationAt')) > 0;
		$showGenerationAt = count($eFinancialYear['cDocument']->getColumn('generationAt')) > 0;

		$h = '<table class="tr-hover tr-even">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>';
						$h .= s("Document");
					$h .= '</th>';
					$h .= '<th></th>';
					if($showGeneration) {
						$h .= '<th>';
							$h .= s("Généré le");
						$h .= '</th>';
					}
					if($showGenerationAt) {
						$h .= '<th></th>';
					}
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($documents as $document => $documentData) {

					$label = $documentData['label'];
					$accept = $documentData['accept'];
					$temporary = $documentData['temporary'];

					$h .= '<tr>';
						$h .= '<td>';
							$h .= $label;
							if($temporary) {
								$h .= ' <span class="util-badge bg-muted">'.s("Provisoire").'</span>';
							}
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$attributes['class'] = 'btn btn-primary';
							if(
								(isset($eFinancialYear['cDocument'][$document]) and $eFinancialYear['cDocument'][$document]->isProcessing()) or
								($accept !== NULL and $eFinancialYear->{$accept}() === FALSE)
							) {
								$attributes['disabled'] = 'disabled';
								$attributes['class'] .= ' disabled';
							}
							$h .= '<a data-ajax-navigation="never" data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:generate?type='.$document.'" post-id='.$eFinancialYear['id'].'" '.attrs($attributes).'>'.\Asset::icon('arrow-clockwise').' ';
								if(FinancialYearDocumentLib::hasDocument($eFinancialYear, $document)) {
									$h .= s("Regénérer");
								} else {
									$h .= s("Générer");
								}
							$h .= '</a>';
						$h .= '</td>';

						if($showGeneration) {

							$h .= '<td>';
								if(FinancialYearDocumentLib::hasDocument($eFinancialYear, $document)) {
									$h .= \util\DateUi::numeric($eFinancialYear['cDocument'][$document]['generationAt']);
								}
							$h .= '</td>';

						}

						if($showGenerationAt) {

							$h .= '<td class="text-center">';
								if(isset($eFinancialYear['cDocument'][$document])) {
									if($eFinancialYear['cDocument'][$document]->isProcessing()) {
										$hasDocumentGenerating = TRUE;
										$h .= '<i>'.s("Génération en cours...").'</i>';
									} else if(FinancialYearDocumentLib::hasDocument($eFinancialYear, $document)) {
										$h .= '<a data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:download?type='.$document.'&id='.$eFinancialYear['id'].'" class="btn btn-primary">'.\Asset::icon('file-pdf').' '.s("PDF").'</a>';
									}
								}
							$h .= '</td>';

						}

						$h .= '<td>';
							$url = match($document) {
								FinancialYearDocumentLib::BALANCE_SHEET => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/bilan',
								FinancialYearDocumentLib::OPENING => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/bilan',
								FinancialYearDocumentLib::OPENING_DETAILED => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/bilan?type=detailed',
								FinancialYearDocumentLib::CLOSING => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/bilan',
								FinancialYearDocumentLib::CLOSING_DETAILED => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/bilan?type=detailed',
								FinancialYearDocumentLib::INCOME_STATEMENT => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/compte-de-resultat',
								FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/compte-de-resultat?type=detailed',
								FinancialYearDocumentLib::SIG => \company\CompanyUi::urlFarm($eFarm).'/etats-financiers/sig',
								FinancialYearDocumentLib::ASSET_AMORTIZATION => \company\CompanyUi::urlFarm($eFarm).'/immobilisations',
								FinancialYearDocumentLib::ASSET_ACQUISITION => \company\CompanyUi::urlFarm($eFarm).'/immobilisations/acquisitions',
								FinancialYearDocumentLib::BALANCE => \company\CompanyUi::urlJournal($eFarm).'/balance',
							};
							$h .= '<a href="'.$url.'">'.s("Voir les données").'</a>';
						$h .= '</td>';
					$h .= '</tr>';

				}

			$h .= '</tbody>';
		$h .= '</table>';

		if($hasDocumentGenerating === TRUE) {

			$attributes = [
				'onrender' => 'FinancialYearDocument.checkGeneration("'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:check")',
			];

		} else {
			$attributes = [];
		}

		return new \Panel(
			id: 'panel-financial-year-document',
			title: s("Tous les documents de l'exercice {value}", $eFinancialYear->getLabel()),
			body: '<div '.attrs($attributes).'>'.$h.'</div>',
			attributes: $attributes,
		);

	}

	public function getPdfLink(\farm\Farm $eFarm, FinancialYearDocument $eFinancialYearDocument, string $type): string {

		$h = '';
		if($eFinancialYearDocument->empty()) {

			$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:generate?type='.$type.'" post-id="'.$eFarm['eFinancialYear']['id'].'" data-ajax-navigation="never" class="btn btn-primary" data-waiter="'.s("Génération en cours...").'" title="'.s("Générer le PDF").'">'.\Asset::icon('file-pdf').'  '.s("PDF").'</a> ';

		} else if($eFinancialYearDocument['generation'] === \account\FinancialYearDocument::SUCCESS) {

			$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:download?type='.$type.'&id='.$eFarm['eFinancialYear']['id'].'" data-ajax-navigation="never" class="btn btn-primary" title="'.s("Télécharger le PDF").'">'.\Asset::icon('file-pdf').'  '.s("PDF").'</a> ';

		} else {

			$h .= '<a onrender="FinancialYearDocument.checkGeneration(\''.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:check\')" class="btn btn-primary disabled" title="'.s("Génération en cours").'">'.\Asset::icon('file-pdf').'  '.s("PDF en génération...").'</a> ';

		}

		return $h;
	}

}
