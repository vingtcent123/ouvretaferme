<?php
namespace account;

class ImportUi {

	public function __construct() {

		\Asset::css('account', 'fec.css');
		\Asset::js('account', 'import.js');

	}

	public function history(\Collection $cImport): string {

		$hasImportWaiting = ($cImport->find(fn($e) => (in_array($e['status'], [Import::CREATED, Import::WAITING, Import::FEEDBACK_TO_TREAT]) and $e['createdAt'] > date('Y-m-d H:i', strtotime('10 minutes ago'))))->count() > 0);

		if($hasImportWaiting === TRUE and OTF_DEMO === FALSE) {
			$attributes = [
				'onrender' => 'Import.check("'.\farm\FarmUi::urlFinancialYear().'/account/financialYear/fec:check")',
			];
		} else {
			$attributes = [];
		}
		$h = '<table class="tr-even tr-hover" '.attrs($attributes).'>';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Créé le").'</th>';
					$h .= '<th class="text-center">'.s("Mis à jour le").'</th>';
					$h .= '<th>'.s("Statut").'</th>';
					$h .= '<th>'.s("Exercice").'</th>';
					$h .= '<th class="text-center">'.s("Nombre d'écritures").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
				foreach($cImport as $eImport) {

					$nOperation = count(explode("\n", $eImport['content'])) - 1;

					$h .= '<tr>';
						$h .= '<td class="text-center">'.\util\DateUi::numeric($eImport['createdAt'], \util\DateUi::DATE).'</td>';
						$h .= '<td class="text-center">'.\util\DateUi::numeric($eImport['updatedAt'], \util\DateUi::DATE).'</td>';
						$h .= '<td>';

							if($eImport['errors'] !== NULL) {

								$h .= \Asset::icon('exclamation-triangle').' '.s("En erreur, traitement non réalisable.");

								if($eImport['errors']->get() & Import::DATES) {
									$h .= '<br /><small>'.\util\FormUi::info(s("Certaines écritures ne correspondent pas aux dates de l'exercice choisi.")).'</small>';
								}

							} else {

								$h .= self::p('status')->values[$eImport['status']];

							}
						$h .= '</td>';
						$h .= '<td>'.$eImport['financialYear']->getLabel().'</td>';
						$h .= '<td class="text-center">'.$nOperation.'</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';
		$h .= '</table>';

		return $h;

	}

	public function create(\farm\Farm $eFarm): string {

		$form = new \util\FormUi();
		$eImport = new Import(['financialYear' => $eFarm['eFinancialYear']]);

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm, $eFarm['eFinancialYear']).'/financialYear/fec:doCreate', ['id' => 'fec-import', 'binary']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroup($eImport, 'financialYear*', function($d) use ($eFarm) {
				$values = [];
				foreach($eFarm['cFinancialYear']->find(fn($e) => $e['nOperation'] === 0)->makeArray(fn($e) => ['id' => $e['id'], 'label' => s("Exercice {value}", FinancialYearUi::getYear($e))]) as $value) {
					$values[$value['id']] = $value['label'];
				}
				$d->values = $values;
				$d->default = $eFarm['eFinancialYear']['id'];
				$d->attributes['mandatory'] = TRUE;
			});
			$h .= $form->dynamicGroup($eImport, 'financialYearStatus*', function($d) {
				$d->default = NULL;
			});

			$h .= $form->group(s("Fichier FEC").\util\FormUi::asterisk(), $form->file('fec', ['accept' => '.txt']));


			$h .= $form->group(
				content: $form->submit(s("Lancer l'import"))
			);


		$h .= $form->close();

		return $h;

	}

	public function showImport(\farm\Farm $eFarm, Import $eImport, \Collection $cJournalCode, \Collection $cAccount, \Collection $cMethod): string {

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Date d'import").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eImport['createdAt']).'</dd>';

				$h .= '<dt>'.s("Fichier").'</dt>';
				$h .= '<dd>'.encode($eImport['filename']).'</dd>';

				$h .= '<dt>'.self::p('status')->label.'</dt>';
				$h .= '<dd>'.self::p('status')->values[$eImport['status']].'</dd>';

				$h .= '<dt>'.self::p('delimiter')->label.'</dt>';
				$h .= '<dd>'.encode($eImport['delimiter']).'</dd>';

				$h .= '<dt>'.s("Dernière mise à jour").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eImport['updatedAt']).'</dd>';

				$h .= '<dt>'.s("Nombre de lignes d'écritures").'</dt>';
				$nLines = count(explode("\n", $eImport['content'])) - 1;
				$h .= '<dd>'.$nLines.'</dd>';

				$h .= '<dt>'.s("Numéro de SIREN").'</dt>';
				$h .= '<dd>'.mb_substr($eImport['filename'], 0, 9).'</dd>';

				$h .= '<dt>'.s("Date d'export / de clôture").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric(mb_substr($eImport['filename'], 12, 4).'-'.mb_substr($eImport['filename'], 16, 2).'-'.mb_substr($eImport['filename'], 18, 2)).'</dd>';

				$h .= '<dt>'.s("État de l'exercice comptable<br />à la fin de l'import").'</dt>';
				$h .= '<dd>';
					$h .= $eImport['financialYearStatus'] === Import::OPEN ? s("Ouvert") : s("Clôturé");
				$h .= '</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if($eImport['status'] === Import::FEEDBACK_REQUESTED) {

			$nJournaux = count(array_find($eImport['rules']['journaux'], fn($journal) => count($journal['journalCode']) === 0) ?? []);
			$nComptes = count(array_find($eImport['rules']['comptes'], fn($compte) => count($compte['account']) === 0) ?? []);
			$nComptesAux = count(array_find($eImport['rules']['comptesAux'], fn($compte) => count($compte['account']) === 0) ?? []);
			$nPayments = count(array_find($eImport['rules']['paiements'], fn($paiement) => count($paiement['payment']) === 0) ?? []);

			$form = new \util\FormUi();
			$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm, $eFarm['eFinancialYear']).'/financialYear/fec:doValidateRules', [
				'id' => 'import-update-rules',
				'url-update' => \company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:doUpdateRuleValue'
			]);

			$h .= $form->hidden('id', $eImport['id']);


			$h .= '<h4 class="mt-1">'.s("Correspondance de journaux").'</h4>';

			if($nJournaux > 0) {

				$h .= '<div class="util-info">'.s("Certains journaux du FEC n'ont pas été trouvés, veuillez indiquer ici à quel journal sur {siteName} les rattacher. Vous pouvez également les <link>créer dans les paramètres de la comptabilité</link>.", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/journalCode">']).'</div>';

			} else {

				$h .= '<div class="util-info">'.s("Vous pouvez vérifier les journaux qui seront rattachés à vos écritures.").'</div>';

			}

			$h .= '<table class="tr-even">';
				$h .= '<tr>';
					$h .= '<th>'.s("Code dans le fichier FEC").'</th>';
					$h .= '<th>'.s("Journal dans le fichier FEC").'</th>';
					$h .= '<th>'.s("Journal dans {siteName}").'</th>';
				$h .= '</tr>';
				$journaux = [];
				foreach($cJournalCode as $eJournalCode) {
					$journaux[$eJournalCode['id']] = $eJournalCode['name'].' ('.$eJournalCode['code'].')';
				}
				foreach($eImport['rules']['journaux'] as $journal => $dataJournal) {
					$class = 'class="'.(($dataJournal['journalCode']['id'] ?? NULL) === NULL ? 'color-danger' : '').'"';
					$h .= '<tr>';
						$h .= '<td '.$class.'>'.encode($journal).'</td>';
						$h .= '<td '.$class.'>'.encode($dataJournal['label']).'</td>';
						$h .= '<td>'.$form->select('journalCode['.$journal.']', $journaux, $dataJournal['journalCode']['id'] ?? NULL, [
							'data-label' => $journal,
							'onchange' => 'Import.updateJournal(this)',
						]).'</td>';
					$h .= '</tr>';
				}
			$h .= '</table>';


			if($nComptes > 0 or $nComptesAux) {

				$h .= '<h4 class="mt-1">'.s("Correspondance de comptes").'</h4>';

				$missingAccounts = [];
				foreach($eImport['rules']['comptes'] + $eImport['rules']['comptesAux'] as $compte => $dataCompte) {
					$eAccount = isset($dataCompte['account']['id']) ? $cAccount->find(fn($e) => $e['id'] === $dataCompte['account']['id'])->first() : NULL;
					if($eAccount === NULL) {
						$missingAccounts[] = $compte;
					}
				}
				$missingAccounts = array_unique($missingAccounts);

				$h .= '<div class="util-info">'.s("Certains numéro de comptes n'ont pas été trouvés, veuillez indiquer ici à quel numéro de compte sur {siteName} les rattacher. Vous pouvez également les <link>créer dans les paramètres de la comptabilité</link>. <br />Les comptes manquants sont : {accounts}. <br /> {icon} Attention, s'il s'agit d'anciens comptes liés à un Plan Comptable qui n'est plus en vigueur, assurez-vous de ne pas vous en resservir ultérieurement en les désactivant après l'import.", ['link' => '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/account">', 'accounts' => join(', ', $missingAccounts), 'icon' => \Asset::icon('exclamation-triangle')]).'</div>';


				$h .= '<table class="tr-even">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th colspan="2" class="text-center">'.s("Fichier FEC").'</th>';
							$h .= '<th rowspan="2">'.s("Compte dans {siteName}").'</th>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<th>'.s("Numéro").'</th>';
							$h .= '<th>'.s("Libellé de compte").'</th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';
						$d = new \PropertyDescriber('account');
						new \account\AccountUi()->query($d, $eFarm);
						foreach($eImport['rules']['comptes'] as $compte => $dataCompte) {
							$eAccount = isset($dataCompte['account']['id']) ? $cAccount->find(fn($e) => $e['id'] === $dataCompte['account']['id'])->first() : new Account();
							$class = 'class="'.(($eAccount and $eAccount->empty()) ? 'color-danger' : '').'"';
							$h .= '<tr>';
								$h .= '<td '.$class.'>'.encode($compte).'</td>';
								$h .= '<td '.$class.'>'.encode($dataCompte['label']).'</td>';
								$h .= '<td>';
									$e = new \journal\Operation(['account' => $eAccount]);
									$h .= $form->dynamicField($e, 'account', function($d) use($compte, $form) {
										$d->attributes['data-account-label'] = $compte;
										$d->attributes['data-type'] = 'comptes';
										$d->attributes['name'] = 'comptes['.$compte.']';
										$d->name = 'comptes['.$compte.']';
									},);
								$h .= '</td>';
							$h .= '</tr>';
						}
						foreach($eImport['rules']['comptesAux'] as $compte => $dataCompte) {
							$eAccount = isset($dataCompte['account']['id']) ? $cAccount->find(fn($e) => $e['id'] === $dataCompte['account']['id'])->first() : new Account();
							$class = 'class="'.($eAccount->empty() ? 'color-danger' : '').'"';
							$h .= '<tr>';
								$h .= '<td '.$class.'>'.encode($compte).'</td>';
								$h .= '<td '.$class.'>'.encode($dataCompte['label']).'</td>';
								$h .= '<td>';
									$e = new \journal\Operation(['account' => $eAccount]);
									$h .= $form->dynamicField($e, 'account', function($d) use($compte, $form) {
										$d->attributes['data-account-label'] = $compte;
										$d->attributes['data-type'] = 'comptesAux';
										$d->attributes['name'] = 'comptesAux['.$compte.']';
										$d->name = 'comptes['.$compte.']';
									},);
								$h .= '</td>';
							$h .= '</tr>';
						}
					$h .= '</tbody>';
				$h .= '</table>';

			}

			if(count($eImport['rules']['paiements']) > 0) {

				$h .= '<h4>'.s("Correspondance de paiements").'</h4>';

				if($nPayments > 0) {

					$h .= '<div class="util-info">'.s("Certains moyens de paiement du FEC n'ont pas été trouvés, veuillez indiquer ici à quel moyen de paiement sur {siteName} les rattacher.").'</div>';

				} else {

					$h .= '<div class="util-info">'.s("Vous pouvez vérifier les moyens de paiement rattachés aux écritures du FEC.").'</div>';
				}

				$h .= '<table class="tr-even">';
					$h .= '<tr>';
						$h .= '<th>'.s("Moyen de paiement dans le fichier FEC").'</th>';
						$h .= '<th>'.s("Moyen de paiement dans {siteName}").'</th>';
					$h .= '</tr>';
					$payments = [];
					foreach($cMethod as $eMethod) {
						$payments[$eMethod['id']] = $eMethod['name'];
					}
					foreach($eImport['rules']['paiements'] as $paiement => $dataPaiement) {
						$class = 'class="'.(($dataPaiement['payment']['id'] ?? NULL) === NULL ? 'color-danger' : '').'"';
						$h .= '<tr>';
							$h .= '<td '.$class.'>'.encode($paiement).'</td>';
							$h .= '<td>'.$form->select('paiements['.$paiement.']', $payments, $dataPaiement['payment']['id'] ?? NULL, [
								'data-label' => $paiement,
								'onchange' => 'Import.updatePayment(this)',
							]).'</td>';
						$h .= '</tr>';
					}
				$h .= '</table>';

			}

			$h .= $form->submit(s("Relancer l'import"), ['class' => 'mb-2 btn btn-primary']);
		}

		return $h;
	}
	public static function p(string $property): \PropertyDescriber {

		$d = Import::model()->describer($property, [
			'delimiter' => s("Caractère de délimitation"),
			'status' => s("État"),
			'financialYear' => s("Exercice comptable"),
			'financialYearStatus' => s("Après l'import..."),
			'filename' => s("Nom du fichier"),
		]);

		switch($property) {

			case 'status':
				$d->values = [
					Import::DONE => s("Terminé"),
					Import::CANCELLED => s("Annulé"),
					Import::CREATED => s("En attente de traitement"),
					Import::IN_PROGRESS => s("En cours"),
					Import::FEEDBACK_TO_TREAT => s("En attente de retraitement"),
					Import::FEEDBACK_REQUESTED => s("Action à réaliser"),
					Import::WAITING => s("En attente de traitement"),
				];
				break;

			case 'financialYear':
				$d->after = \util\FormUi::info(s("Seuls les exercices sans aucune écriture comptable sont disponibles."));
				break;

			case 'financialYearStatus':
				$open = '<h4>'.s("... l'exercice comptable sera ouvert").'</h4>';
				$open .= '<p>'.\Asset::icon('arrow-right').' <i><small>'.s("Les écritures comptables d'inventaire <b>d'ouverture</b> sont dans le FEC").'</small></i></p>';

				$close = '<h4>'.s("... l'exercice comptable sera clôturé").'</h4>';
				$close .= '<p>'.\Asset::icon('arrow-right').' <i><small>'.s("Les écritures comptables d'inventaire <b>d'ouverture ET de clôture</b> sont dans le FEC").'</small></i></p>';

				$d->values = [
					Import::OPEN => $open,
					Import::CLOSED => $close,
				];
				break;


		}
		return $d;

	}
}
?>
<?php
