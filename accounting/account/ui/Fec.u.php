<?php
namespace account;

class FecUi {

	public function getAttestation(\farm\Farm $eFarm): string {

		$header = new \account\PdfUi()->getHeader($eFarm, s("Attestation conformité FEC"), $eFarm['eFinancialYear']);

		$h = '<table class="pdf-table-bordered">';

			$h .= '<thead>';
				$h .= $header;
			$h .= '</thead>';

			$h .= '<tbody>';
				$h .= '<tr>';
					$h .= '<td style="border: none; background-color: transparent;">';

						$h .= '<h3 class="mt-4">'.s("Texte de référence").'</h3>';
						$h .= '<p>'.s("Article A-47 A-1 du Livre des Procédures Fiscales, modifié par l'arrêté du 29 juillet 2013 sur le Contrôle Fiscal des Comptabilités Informatisées.").'</p>';

						$h .= '<p class="mt-3">'.s("Par la présente, l'association {siteName} atteste que la structure du Fichier des Ecritures Comptables (FEC) qui est géré par le site {siteName}.org est conforme aux spécifications techniques de l'Administration Fiscale en application du texte cité en référence.").'</p>';

						$h .= '<p class="mt-3">'.s("La présente attestation est à produire auprès de l'Administration fiscale sur sa demande pour justifier de la conformité des logiciels utilisés pour l'entité :").'</p>';

						$h .= '<ul style="list-style-type: none;">';
							$h .= '<li>'.s("Raison sociale : {value}", $eFarm['legalName'] ?? $eFarm['name']).'</li>';
							$h .= '<li>'.s("Adresse : {value}", $eFarm->getLegalAddress('html')).'</li>';
						$h .= '</ul>';

						$h .= '<p class="mt-3">'.s("Fait à Saint-Amant-Tallende, le {value}", \util\DateUi::numeric(date('Y-m-d'))).'</p>';
						$h .= '<p>'.s("Vincent Guth, président de l'association {siteName}").'</p>';
					$h .= '</td>';
				$h .= '</tr>';
			$h .= '</tbody>';

		$h .= '</table>';


		return $h;

	}

	public function getView(\farm\Farm $eFarm, array $data): \Panel {

		$h = '';

		$eFinancialYear = $eFarm['eFinancialYear'];

		if($eFinancialYear->isOpen()) {

			$h .= '<div class="util-info">';
				$h .= s("L'exercice {value} n'étant pas encore clôturé, le FEC généré sera <b>provisoire</b> et ne peut être transmis à l'administration fiscale tel quel.", $eFinancialYear->getLabel());
			$h .= '</div>';


		}

		$h .= $this->nonComplianceCheck($eFarm, $data);


		if($eFinancialYear->isOpen()) {

			$h .= '<a class="btn btn-primary" data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:download">';
				$h .= s("Télécharger un FEC provisoire de l'exercice {value}", $eFinancialYear->getLabel());
			$h .= '</a>';


		} else {

			$h .= '<div class="util-grid-icon mb-1">'.\Asset::icon('info-circle').' '.s("Pensez à joindre une notice explicative à votre FEC.").'</div>';

			$h .= '<a class="btn btn-primary" data-ajax-navigation="never" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:download">';
				$h .= s("Télécharger le FEC de l'exercice {value}", $eFinancialYear->getLabel());
			$h .= '</a>';

		}



		return new \Panel(
			id: 'panel-fec-view',
			title: s("Générer votre fichier des écritures comptables"),
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

	private function nonComplianceCheck(\farm\Farm $eFarm, array $data): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$nonCompliances = $this->hasNonCompliance($data);
		$h = '';

		if($nonCompliances === 0) {

			$h .= '<div class="util-block-success">'.s("Félicitations ! Aucune non-conformité à la génération d'un FEC n'a été détecté pour cet exercice").'</div>';

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
						$h .= '<div class="util-annotation">'.s("<link>Voir les écritures concernées</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode=-1">']).'</div>';
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
							$h .= '<div class="util-annotation">'.s("<link>Voir les écritures concernées</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?hasDocument=0">']).'</div>';
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

									$h .= '<div class="util-annotation">'.s("<link>Voir les écritures sans journal</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode=-1">']).'</div>';

								} else {

									$h .= '<div class="util-annotation">'.s("<link>Voir les écritures du journal</link>", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$eJournalCode['id'].'">']).'</div>';

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
