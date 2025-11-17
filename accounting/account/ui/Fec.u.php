<?php
namespace account;

class FecUi {

	public function getView(\farm\Farm $eFarm, FinancialYear $eFinancialYear, array $data): \Panel {

		$h = '';

		if($eFinancialYear->isOpen()) {

			$h .= '<div class="util-info">';
				$h .= s("L'exercice {value} n'étant pas encore clôturé, le FEC généré sera <b>provisoire</b> et ne peut être transmis à l'administration fiscale tel quel.", FinancialYearUi::getYear($eFinancialYear));
			$h .= '</div>';


		}

		$h .= $this->nonComplianceCheck($eFarm, $eFinancialYear, $data);


		if($eFinancialYear->isOpen()) {

			$h .= '<a class="btn btn-primary" data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:download?financialYear='.$eFinancialYear['id'].'">';
				$h .= s("Télécharger un FEC provisoire de l'exercice {value}", FinancialYearUi::getYear($eFinancialYear));
			$h .= '</a>';


		} else {

			$h .= '<div class="util-grid-icon mb-1">'.\Asset::icon('info-circle').' '.s("Pensez à joindre une notice explicative à votre FEC.").'</div>';

			$h .= '<a class="btn btn-primary" data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:download?financialYear='.$eFinancialYear['id'].'">';
				$h .= s("Télécharger le FEC de l'exercice {value}", FinancialYearUi::getYear($eFinancialYear));
			$h .= '</a>';

		}



		return new \Panel(
			id: 'panel-fec-view',
			title: s("Générer un Fichier des Écritures Comptables"),
			body: $h,
			close: 'passthrough',
			url: \company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:view',
		);
	}

	private function hasNonCompliance(array $data): int {

		$nonCompliance = 0;

		if($data['hasSiren'] === FALSE) {
			$nonCompliance ++;
		}

		if($data['noJournal'] > 0) {
			$nonCompliance ++;
		}

		if($data['noDocument'] > 0) {
			$nonCompliance ++;
		}

		foreach($data['journalBalance'] as $balance) {
			if($balance[\journal\Operation::DEBIT] !== $balance[\journal\Operation::CREDIT]) {
				$nonCompliance ++;
			}
		}

		return $nonCompliance;

	}

	private function nonComplianceCheck(\farm\Farm $eFarm, FinancialYear $eFinancialYear, array $data): string {

		$nonCompliances = $this->hasNonCompliance($data);
		$h = '';

		if($nonCompliances === 0) {

			$h .= '<div class="util-success">'.s("Félicitations ! Aucune non-conformité à la génération d'un FEC n'a été détecté pour cet exercice").'</div>';

		} else {

			$h .= '<div class="util-warning-outline">'.p("Attention, {value} non-conformité a été détectée. Corrigez-la dès que possible.", "Attention, {value} non-conformités ont été détectées. Corrigez-les dès que possible !", $nonCompliances).'</div>';

		}

		$h .= '<h3>'.("Points de vérification").'</h3>';

		$h .= '<table class="tr-hover tr-even">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.s("Critère").'</th>';
					$h .= '<th class="text-center td-min-content">'.s("Statut").'</th>';
					$h .= '<th>'.s("Détails").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';
				$h .= '<tr>';

					$h .= '<td>'.s("Présence du numéro SIREN").'</td>';
					$h .= '<td class="text-center td-min-content">';
					if($data['hasSiren']) {
						$h .= $this->getYes();
					} else {
						$h .= $this->getNo();
					}
					$h .= '</td>';
					$h .= '<td>';
						if($data['hasSiren'] === FALSE) {
							$h .= s("Non trouvé");
							$h .= '<br />';
							$h .= '<div class="util-annotation">'.s("Vous pouvez le configurer <link>sur cette page</link>", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']).'</div>';
						} else {
							$h .= s("Siren : {value}", encode(mb_substr($eFarm['siret'], 0, 9)));
						}
					$h .= '</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<td>'.s("Journaux").'</td>';
					$h .= '<td class="text-center td-min-content">';
					if($data['noJournal'] === 0) {
						$h .= $this->getYes();
					} else {
						$h .= $this->getNo();
					}
					$h .= '</td>';
					$h .= '<td>';
					 $h .= p("{value} écriture n'a pas de journal", "{value} écritures n'ont pas de journal", $data['noJournal']);
						$h .= '<div class="util-annotation">'.s("<link>Voir les écritures concernées</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?financialYear='.$eFinancialYear['id'].'&journalCode=-1">']).'</div>';
					$h .= '</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<td>'.s("Documents comptables").'</td>';
					$h .= '<td class="text-center td-min-content">';
					if($data['noDocument'] === 0) {
						$h .= $this->getYes();
					} else {
						$h .= $this->getNo();
					}
					$h .= '</td>';
					$h .= '<td>';
						if($data['noDocument'] > 0) {
							$h .= p("{value} écriture n'a pas de document comptable", "{value} écritures n'ont pas de document comptable", $data['noDocument']);
							$h .= '<div class="util-annotation">'.s("<link>Voir les écritures concernées</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?financialYear='.$eFinancialYear['id'].'&hasDocument=1">']).'</div>';
						}
					$h .= '</td>';
				$h .= '</tr>';

				foreach($data['journalBalance'] as $journal) {

					if($journal['journalCode']->notEmpty()) {
						$eJournalCode = $data['cJournalCode'][$journal['journalCode']['id']];
					} else {
						$eJournalCode = new \journal\JournalCode();
					}

					$h .= '<tr>';
						$h .= '<td>';
							if($eJournalCode->empty()) {
								$h .= s("Équilibre des écritures sans journal");
							} else {
								$h .= s("Équilibre du journal \"{value}\"", encode($eJournalCode['name']));
							}
						$h .= '</td>';
						$h .= '<td class="text-center td-min-content">';
							if($journal[\journal\Operation::CREDIT] === $journal[\journal\Operation::DEBIT]) {
								$h .= $this->getYes();
							} else {
								$h .= $this->getNo();
							}
						$h .= '</td>';
						$h .= '<td>';
							if($journal[\journal\Operation::CREDIT] !== $journal[\journal\Operation::DEBIT]) {

								$amount = abs($journal[\journal\Operation::CREDIT] - $journal[\journal\Operation::DEBIT]);
								if($journal[\journal\Operation::DEBIT] > $journal[\journal\Operation::CREDIT]) {
									$direction = s("Solde débiteur");
								} else {
									$direction = s("Solde créditeur");
								}

								$h .= s("Déséquilibre de {amount} ({balance})", [
									'amount' => \util\TextUi::money($amount),
									'balance' => $direction,
								]);

								if($eJournalCode->empty()) {

									$h .= '<div class="util-annotation">'.s("<link>Voir les écritures sans journal</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?financialYear='.$eFinancialYear['id'].'&journalCode=-1">']).'</div>';

								} else {

									$h .= '<div class="util-annotation">'.s("<link>Voir les écritures du journal</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?financialYear='.$eFinancialYear['id'].'&journalCode='.$eJournalCode['id'].'">']).'</div>';

								}
							} else {

								if($eJournalCode->notEmpty()) {

									$h .= s("Ce journal est équilibré.");

 								} else {

									$h .= s("Les écritures sont équilibrées mais devraient se trouver dans des journaux.");

								}

							}
						$h .= '</td>';
					$h .= '</tr>';

				}
			$h .= '</thead>';
		$h .= '</table>';

		return $h;

	}

	private function getYes(): string {
		return '<span class="color-success">'.\Asset::icon('check-lg').'</span>';
	}
	private function getNo(): string {
		return '<span class="color-danger">'.\Asset::icon('x-lg').'</span>';
	}

}
?>
