<?php
namespace account;

Class FinancialYearDocumentUi {

	public function getAllDocuments(FinancialYear $eFinancialYear): array {

		return [
			\account\FinancialYearDocumentLib::INCOME_STATEMENT => ['accept' => NULL, 'label' => s("Compte de résultat")],
			\account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => ['accept' => NULL, 'label' => s("Compte de résultat avec synthèse")],
			\account\FinancialYearDocumentLib::SIG => ['accept' => NULL, 'label' => s("Soldes intermédiaires de gestion")],
			\account\FinancialYearDocumentLib::ASSET_AMORTIZATION => ['accept' => NULL, 'label' => s("Amortissements")],
			\account\FinancialYearDocumentLib::ASSET_ACQUISITION => ['accept' => NULL, 'label' => s("Acquisitions")],
			\account\FinancialYearDocumentLib::BALANCE => ['accept' => NULL, 'label' => s("Balance")],
			\account\FinancialYearDocumentLib::BALANCE_DETAILED => ['accept' => NULL, 'label' => s("Balance détaillée")],
			\account\FinancialYearDocumentLib::CLOSING => ['accept' => 'acceptGenerateClose', 'label' => s("Bilan de clôture")],
			\account\FinancialYearDocumentLib::CLOSING_DETAILED => ['accept' => 'acceptGenerateClose', 'label' => s("Bilan de clôture détaillé")],
			\account\FinancialYearDocumentLib::BALANCE_SHEET => ['accept' => NULL, 'label' => s("Bilan"), 'temporary' => TRUE],
			\account\FinancialYearDocumentLib::OPENING_DETAILED => ['accept' => 'acceptGenerateOpen', 'label' => s("Bilan d'ouverture détaillé")],
			\account\FinancialYearDocumentLib::OPENING => ['accept' => 'acceptGenerateOpen', 'label' => s("Bilan d'ouverture")],
		];

	}

	public function list(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		\Asset::js('account', 'financialYearDocument.js');

		$hasDocumentGenerating = ($eFinancialYear['cDocument']->notEmpty() and $eFinancialYear['cDocument']->find(fn($e) => $e->isProcessing())->notEmpty());

		$bilanDocuments = [];
		if($eFinancialYear->isClosed() === FALSE) {
			$bilanDocuments[] = \account\FinancialYearDocumentLib::BALANCE_SHEET;
		}
		if($eFinancialYear['previous']->notEmpty() and $eFinancialYear->isOpen()) {
			$bilanDocuments[] = \account\FinancialYearDocumentLib::OPENING;
			$bilanDocuments[] = \account\FinancialYearDocumentLib::OPENING_DETAILED;
		}

		if($eFinancialYear->isClosed()) {
			$bilanDocuments[] = \account\FinancialYearDocumentLib::CLOSING;
			$bilanDocuments[] = \account\FinancialYearDocumentLib::CLOSING_DETAILED;
		}

		$h = '';

		$h .= '<div class="financial-year-documents">';

			$h .= '<h3>';
				$h .= s("Bilans");
				if($eFinancialYear->isClosed() === FALSE) {
					$h .= ' <span class="util-badge bg-primary">'.s("Provisoire").'</span>';
				}
			$h .= '</h3>';
			$h .= '<div class="util-buttons">';
				foreach($bilanDocuments as $document) {
					$h .= $this->getDocumentCell($eFarm, $eFinancialYear, $document);
				}
			$h .= '</div>';

			$h .= '<h3>';
				$h .= s("Comptes de résultat");
				if($eFinancialYear->isClosed() === FALSE) {
					$h .= ' <span class="util-badge bg-primary">'.s("Provisoire").'</span>';
				}
			$h .= '</h3>';
			$h .= '<div class="util-buttons">';
				foreach([\account\FinancialYearDocumentLib::INCOME_STATEMENT, \account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED, \account\FinancialYearDocumentLib::SIG] as $document) {
					$h .= $this->getDocumentCell($eFarm, $eFinancialYear, $document);
				}
			$h .= '</div>';

			$h .= '<h3>';
				$h .= s("Balances");
				if($eFinancialYear->isClosed() === FALSE) {
					$h .= ' <span class="util-badge bg-primary">'.s("Provisoire").'</span>';
				}
			$h .= '</h3>';
			$h .= '<div class="util-buttons">';
				foreach([\account\FinancialYearDocumentLib::BALANCE, \account\FinancialYearDocumentLib::BALANCE_DETAILED] as $document) {
					$h .= $this->getDocumentCell($eFarm, $eFinancialYear, $document);
				}
			$h .= '</div>';


			$h .= '<h3>';
				$h .= s("Immobilisations");
				if($eFinancialYear->isClosed() === FALSE) {
					$h .= ' <span class="util-badge bg-primary">'.s("Provisoire").'</span>';
				}
			$h .= '</h3>';
			$h .= '<div class="util-buttons">';
				foreach([\account\FinancialYearDocumentLib::ASSET_AMORTIZATION, \account\FinancialYearDocumentLib::ASSET_ACQUISITION] as $document) {
					$h .= $this->getDocumentCell($eFarm, $eFinancialYear, $document);
				}
			$h .= '</div>';

			if($eFinancialYear['nOperation'] > 0) {

				$h .= '<h3>'.s("Fichiers FEC").'</h3>';

				$h .= '<div>';

					$h .= '<a data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:download" class="btn btn-primary">';
						$h .= \Asset::icon('filetype-txt');
						$h .= ' '.s("Format TXT");
					$h .= '</a>';
					$h .= ' ';
					$h .= '<a data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:downloadCsv" class="btn btn-primary">';
						$h .= \Asset::icon('filetype-csv');
						$h .= ' '.s("Format CSV");
					$h .= '</a>';

				$h .= '</div>';

			}
		$h .= '</div>';

		if($hasDocumentGenerating === TRUE) {

			$attributes = [
				'onrender' => 'FinancialYearDocument.checkGeneration("'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:check")',
			];

		} else {
			$attributes = [];
		}

		return '<div '.attrs($attributes).'>'.$h.'</div>';

	}

	public function getDocumentCell(\farm\Farm $eFarm, FinancialYear $eFinancialYear, string $document): string {

		$allDocuments = $this->getAllDocuments($eFinancialYear);
		$label = $allDocuments[$document]['label'];

		$h = '<div class="util-block mb-0">';
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
				FinancialYearDocumentLib::BALANCE_DETAILED => \company\CompanyUi::urlJournal($eFarm).'/balance?precision=8',
			};
			$h .= '<h4>';
				$h .= '<a href="'.$url.'">'.$label.'</a>';
			$h .= '</h4>';

			$h .= '<div>';

				if(isset($eFinancialYear['cDocument'][$document]) and $eFinancialYear['cDocument'][$document]->isProcessing()) {
					$h .= '<span class="financial-year-document-generating">'.s("Génération en cours...").'</span>';
				} else {
					$attributes = [
						'data-ajax-navigation' => 'never',
						'href' => \company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:download?type='.$document.'&id='.$eFinancialYear['id'],
						'class' => 'btn btn-outline-primary mr-1',
					];
					if($eFinancialYear->isClosed() === FALSE) {
						$attributes['data-waiter'] = s("Génération en cours...");
					}
					$h .= '<a '.attrs($attributes).'>'.\Asset::icon('file-pdf').' '.s("PDF").'</a>';
				}

			$h .= '</div>';

		$h .= '</div>';


		return $h;
	}

	public function getPdfLink(\farm\Farm $eFarm, FinancialYearDocument $eFinancialYearDocument, string $type): string {

		return '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/pdf:download?type='.$type.'&id='.$eFarm['eFinancialYear']['id'].'" data-ajax-navigation="never" class="btn btn-primary" data-waiter="'.s("Génération en cours...").'" title="'.s("Générer le PDF").'">'.\Asset::icon('file-pdf').'  '.s("PDF").'</a> ';

	}

}
