<?php
namespace vat;

class VatUi {

	public function __construct() {
		\Asset::css('vat', 'vat.css');
		\Asset::js('vat', 'vat.js');
	}

	public function list(\farm\Farm $eFarm, array $allPeriods, \Collection $cDeclaration): string {

		$h = '';

		if(in_array($eFarm->getConf('vatFrequency'), [\farm\Configuration::MONTHLY, \farm\Configuration::QUARTERLY])) {

			$h .= '<h2 class="mt-2">'.s("Les déclarations de TVA").'</h2>';

		} else {

			$h .= '<h2 class="mt-2">'.s("La déclaration de TVA").'</h2>';

		}

		$h .= '<div class="vat-period-container mb-2">';

			foreach($allPeriods as $period) {

				if($cDeclaration->offsetExists($period['from'])) {
					$eDeclaration = $cDeclaration->offsetGet($period['from']);
				} else {
					$eDeclaration = new Declaration();
				}

				if($period['from'] > date('Y-m-d')) {
					$isInFuture = TRUE;
				} else {
					$isInFuture = FALSE;
				}

				$h .= '<div class="vat-period '.($isInFuture ? 'vat-period-future' : '').'">';

					$h .= '<div class="vat-period-title">';
						$h .= '<h3>'.$this->getPeriod($eFarm->getConf('vatFrequency'), $period).'</h3>';
						if($isInFuture === FALSE) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/declaration-de-tva?from='.$period['from'].'" class="btn btn-outline-primary">'.s("Consulter").'</a>';
						}
					$h .= '</div>';
					$h .= '<div class="vat-period-details">';
						$h .= s("Statut");
						if($isInFuture) {
							$h .= '<span>'.\Asset::icon('dash').'</span>';
						} else if($eDeclaration->empty()) {
							$h .= '<span class="util-empty">'.s("Non renseignée").'</span>';
						} else if($eDeclaration['accountedAt'] !== NULL) {
							$h .= '<span class="vat-period-value color-success">'.\Asset::icon('journal-check').' '.s("Comptabilisée").'</span>';
						} else if($eDeclaration['declaredAt'] !== NULL) {
							$h .= '<span class="vat-period-value" title="'.s("Enregistrée déclarée le {value}", \util\DateUi::numeric($eDeclaration['declaredAt'], \util\DateUi::DATE)).'">'.\Asset::icon('building-check').' '.s("Déclarée").'</span>';
						} else if($eDeclaration['updatedAt'] !== NULL) {
							$h .= '<span class="vat-period-value">'.\Asset::icon('floppy-fill').' '.s("Enregistrée").'</span>';
						}
					$h .= '</div>';
					if($eDeclaration->exists() and $eDeclaration['declaredAt'] === NULL) {
						$h .= '<div class="vat-period-details">';
							$h .= s("Date limite de déclaration");
							$h .= '<span '.($eDeclaration['limit'] < date('Y-m-d') ? 'class="color-warning"' : '').'>'.\util\DateUi::numeric($eDeclaration['limit']).'</span>';
						$h .= '</div>';
					}

				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	private function getPeriod(string $vatFrequency, array $vatParameters): string {

		switch($vatFrequency) {

			case \farm\Configuration::MONTHLY:

				return s("{month} {year}", [
					'month' => ucfirst(\util\DateUi::getMonthName(mb_substr($vatParameters['from'], 5, 2))),
					'year' => mb_substr($vatParameters['from'], 0, 4)
				]);

			case \farm\Configuration::ANNUALLY:
			case \farm\Configuration::QUARTERLY:

				if(mb_substr($vatParameters['from'], 0, 4) !== mb_substr($vatParameters['to'], 0, 4)) {

					return s("{month1} {year1} à {month2} {year2}", [
						'month1' => ucfirst(\util\DateUi::getMonthName(mb_substr($vatParameters['from'], 5, 2))),
						'month2' => ucfirst(\util\DateUi::getMonthName(mb_substr($vatParameters['to'], 5, 2))),
						'year1' => mb_substr($vatParameters['from'], 0, 4),
						'year2' => mb_substr($vatParameters['to'], 0, 4),
					]);

				}

				return s("{month1} à {month2} {year}", [
					'month1' => ucfirst(\util\DateUi::getMonthName(mb_substr($vatParameters['from'], 5, 2))),
					'month2' => ucfirst(\util\DateUi::getMonthName(mb_substr($vatParameters['to'], 5, 2))),
					'year' => mb_substr($vatParameters['from'], 0, 4),
				]);

			default:
				throw new \Exception('Unknown vatFrequency');

		}
	}

	public function getVatTabs(\farm\Farm $eFarm, array $vatParameters, ?string $selectedTab = NULL): string {

		$baseUrl = \farm\FarmUi::urlConnected($eFarm).'/declaration-de-tva?from='.$vatParameters['from'];

		$h = '<div class="tabs-item">';

			$h .= '<a class="tab-item'.($selectedTab === NULL ? ' selected' : '').'" data-tab="journal" href="'.$baseUrl.'">'.s("Général").'</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'journal-sell' ? ' selected' : '').'" data-tab="journal-sell" href="'.$baseUrl.'&tab=journal-sell">';
					$h .= '<div class="text-center">';
						$h .= s("Écritures de vente");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("TVA collectée").')</span></small>';
					$h .= '</div>';
			$h .= '</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'journal-buy' ? ' selected' : '').'" data-tab="journal-buy" href="'.$baseUrl.'&tab=journal-buy">';
					$h .= '<div class="text-center">';
						$h .= s("Écritures d'achat");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("TVA déductible").')</span></small>';
					$h .= '</div>';
			$h .= '</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'check' ? ' selected' : '').'" data-tab="journal-check" href="'.$baseUrl.'&tab=check">'.s("Contrôle").'</a>';
			if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {
				$textCerfa = s("Formulaire CA12");
			} else {
				$textCerfa = s("Formulaire CA3");
			}
			$h .= '<a class="tab-item'.($selectedTab === 'cerfa' ? ' selected' : '').'" data-tab="cerfa" href="'.$baseUrl.'&tab=cerfa">'.$textCerfa.'</a>';

			$h .= '<a class="tab-item'.($selectedTab === 'history' ? ' selected' : '').'" data-tab="history" href="'.$baseUrl.'&tab=history">'.s("Historique").'</a>';

		$h .= '</div>';

		return $h;
	}

	public function getGeneralTab(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, array $vatParameters, Declaration $eDeclaration): string {

		$h = '<div class="tab-panel selected" data-tab="journal">';

			$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

				$h .= '<dl class="util-presentation util-presentation-2">';

					$h .= '<dt>'.\farm\FarmUi::p('siret')->label.'</dt>';
					$h .= '<dd>'.encode($eFarm['siret']).'</dd>';

					$h .= '<dt>'.\farm\ConfigurationUi::p('vatFrequency')->label.'</dt>';
					$h .= '<dd>';
					$h .= \farm\ConfigurationUi::p('vatFrequency')->values[$eFarm->getConf('vatFrequency')];
					if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {
						$h .= ' - '.s("Formulaire CA12");
					} else {
						$h .= ' - '.s("Formulaire CA3");
					}
					$h .= '</dd>';

					$h .= '<dt>'.\farm\FarmUi::p('legalName')->label.'</dt>';
					$h .= '<dd>'.encode($eFarm['legalName']).'</dd>';

					$h .= '<dt>'.s("Période de déclaration").'</dt>';
					$h .= '<dd>'.s("{startDate} au {endDate}", ['startDate' => \util\DateUi::numeric($vatParameters['from']), 'endDate' => \util\DateUi::numeric($vatParameters['to'])]).'</dd>';

					$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
					$h .= '<dd>'.$eFarm->getLegalAddress('text').'</dd>';

					$h .= '<dt>'.s("Date limite de déclaration").'</dt>';
					$h .= '<dd>';
					$h .= \util\DateUi::numeric($vatParameters['limit']).' ';
					if($eFinancialYear->isCurrent()) {
						$h .= '<sup class="font-xs">'.\Asset::icon('asterisk').'</sup>';
					}
					$h .= '</dd>';
					$h .= '<dt>'.\farm\FarmUi::p('legalEmail')->label.'</dt>';
					$h .= '<dd>'.encode($eFarm['legalEmail']).'</dd>';

					$h .= '<dt>'.\farm\ConfigurationUi::p('vatChargeability')->label.'</dt>';
					$h .= '<dd>'.match($eFarm->getConf('vatChargeability')) {
						\farm\Configuration::CASH => s("TVA sur les encaissements"),
						\farm\Configuration::DEBIT => s("TVA sur les débits"),
						}.'</dd>';

					$h .= '<dt>'.\account\FinancialYearUi::p('taxSystem')->label.'</dt>';
					$h .= '<dd>'.\account\FinancialYearUi::p('taxSystem')->values[$eFinancialYear['taxSystem']].'</dd>';

				$h .= '</dl>';

			$h .= '</div>';

			if($eFarm->getConf('vatChargeability') === \farm\Configuration::DEBIT) {

				$h .= '<div class="util-danger-outline">';
					$h .= s("Attention, {siteName} n'est pas optimisé pour la comptabilité à l'engagement ni l'exigibilité de la TVA avec option sur les débits. Il est donc à votre charge de vous assurez d'avoir saisi vos factures à leur date d'émission (et non à leur date de paiement) pour être en règle avec vos paramètres fiscaux.");
				$h .= '</div>';

			}

		$h .= '</div>';

		$openDate = date('Y-m-d', strtotime($vatParameters['to'].' + 1 day'));
		$closeDate = date('Y-m-d', strtotime($vatParameters['limit'].' + '.\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days'));

		$h .= '<div class="mt-1"><sup class="font-xs">'.\Asset::icon('asterisk').'</sup> '.s("Attention, cette date est indicative et ne prend pas en compte votre situation particulière : référez vous toujours à ce qui est indiqué dans votre compte professionnel sur le <link>site des impôts</link>.", ['link' => '<a href="https://impots.gouv.fr" target="_blank">']).'</div>';

		\Asset::css('vat', 'timeline.css');

		if($eDeclaration->empty()) {
			$eDeclaration = new Declaration([
				'from' => $vatParameters['from'],
				'to' => $vatParameters['to'],
				'limit' => $vatParameters['limit'],
				'declaredAt' => NULL,
				'accountedAt' => NULL,
				'updatedAt' => NULL,
				'paidAt' => NULL,
				'status' => Declaration::DRAFT,
			]);
		}

		$h .= '<h2 class="mt-2">'.s("Les étapes de la déclaration");
		 $h .= '<div class="font-md color-muted pl-1">'.s("période du {start} au {end}", [
			'start' => \util\DateUi::numeric($eDeclaration['from']),
			'end' => \util\DateUi::numeric($eDeclaration['to']),
		]).'</div>';
		 $h .= '</h2>';

		$h .= '<div class="vat-timeline-wrapper stick-xs">';

			// Timeline header
			$h .= '<div class="vat-timeline vat-timeline-header">';

				$h .= '<div class="util-grid-header util-grid-icon text-center">';
					$h .= \Asset::icon('calendar-week');
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="vat-timeline-body">';

				$h .= '<div class="vat-timeline vat-timeline-only">';
					$h .= '<div class="vat-timeline-item">';
						$h .= '<div class="vat-timeline-circle">';
							$h .= s("du {start}<br />au {end}", ['start' => \util\DateUi::numeric($openDate), 'end' => \util\DateUi::numeric($closeDate)]);
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="vat-timeline-action">';
						$h .= '<div class="vat-timeline-action-title">';

							$h .= \Asset::icon('1-circle').' '.s("Ouverture de la déclaration");

							if($eDeclaration->isOpenPeriod() and $eDeclaration['updatedAt'] === NULL) {
								$h .= '<span class="util-badge color-primary bg-info ml-1">'.s("Étape en cours").'</span>';
							} else if($eDeclaration->exists() and $eDeclaration['updatedAt'] !== NULL) {
								$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Déclaration enregistrée").'</span>';
							} else if($eDeclaration->exists() and $eDeclaration->isClosedPeriod()) {
								$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Étape terminée").'</span>';
							} else if($eDeclaration['to'] > date('Y-m-d')) {
								$h .= '<span class="util-badge color-white bg-muted ml-1">'.s("Étape non démarrée").'</span>';
							}

							$h .= '<div class="vat-timeline-action-info">';

								$h .= s("{siteName} pré-remplit certaines cases de la déclaration en fonction de vos écritures comptables sur la période du {from} au {to}.", ['from' => \util\DateUi::numeric($vatParameters['from']), 'to' => \util\DateUi::numeric($vatParameters['to'])]);
								$h .= '<br />';
								$h .= s("Votre déclaration est disponible dès que cette période est terminée, le {value}.", \util\DateUi::numeric($openDate));
								$h .= '<p>'.s("Rendez vous dans l'onglet <link>Formulaire {type}</link> pour la compléter et la mettre à jour, puis enregistrez-la.", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/declaration-de-tva?from='.$vatParameters['from'].'&tab=cerfa">', 'type' => match($eFarm->getConf('vatFrequency')) {
									\farm\Configuration::ANNUALLY => s('CA12'),
									default => s("CA3"),
								}]).'</p>';
								$h .= '<p>'.\Asset::icon('info-circle').' '.s("Tant que vous n'avez pas indiqué sur {siteName} avoir télédéclaré, votre déclaration reste modifiable jusqu'au <b>{value}</b>.", \util\DateUi::numeric($closeDate)).'</p>';

							$h .= '</div>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="vat-timeline vat-timeline-only">';
					$h .= '<div class="vat-timeline-item">';
						$h .= '<div class="vat-timeline-circle">';
							$h .= s("avant le {value}", \util\DateUi::numeric($vatParameters['limit']).'<sup><span class="font-xs">'.\Asset::icon('asterisk').'</span></sup>');
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="vat-timeline-action">';
						$h .= '<div class="vat-timeline-action-title">';

							$h .= \Asset::icon('2-circle').' '.s("Télédéclaration");

							if($eDeclaration->exists()) {
								if($openDate <= date('Y-m-d') and $eDeclaration->isDeclarationOpenPeriod() and $eDeclaration->isDeclared() === FALSE) {
									$h .= '<span class="util-badge color-primary bg-info ml-1">'.s("Étape en cours").'</span>';
								} else if(date('Y-m-d') > $eDeclaration['limit'] or $eDeclaration->isDeclared()) {
									$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Étape terminée").'</span>';
								}
							}

							$h .= '<div class="vat-timeline-action-info">';
								$h .= s("Télédéclarez votre déclaration de TVA sur le site <link>impots.gouv.fr</link>", ['link' => '<a href="https://impots.gouv.fr/" target="_blank">']);
							$h .= '</div>';

						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="vat-timeline vat-timeline-only">';
					$h .= '<div class="vat-timeline-item">';
						$h .= '<div class="vat-timeline-circle">';
							$h .= s("jusqu'au {value}", \util\DateUi::numeric($closeDate));
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="vat-timeline-action">';

						$h .= '<div class="vat-timeline-action-title">';

							$h .= \Asset::icon('3-circle').' '.s("Déclarez sur {siteName}");

							if($eDeclaration->exists()) {
								if($openDate <= date('Y-m-d') and $eDeclaration->isDeclarationOpenPeriod() and $eDeclaration->isDeclared() === FALSE) {
									$h .= '<span class="util-badge color-primary bg-info ml-1">'.s("Étape en cours").'</span>';
								} else if(date('Y-m-d') > $eDeclaration['limit'] or $eDeclaration->isDeclared()) {
									$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Étape terminée").'</span>';
								}
							}

							$h .= '<div class="vat-timeline-action-info">';

								if($eDeclaration->acceptDeclare()) {
									$h .= s("Une fois télédéclarée, <link>enregistrez sur {siteName}</link> que la télédéclaration est faite (ou cliquez sur le bouton ci-dessous).", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/declaration-de-tva?from='.$vatParameters['from'].'&tab=cerfa">']);
								} else {
									$h .= s("Une fois télédéclarée, enregistrez sur {siteName} que la télédéclaration est faite.");
								}
								$h .= '<br />';
								$h .= \Asset::icon('exclamation-triangle').' '.s("Vérifiez que la déclaration sur {siteName} et votre télédéclaration sont bien identiques pour que les écritures comptables de déclaration soient correctes.");
								$h .= '<br />';

								if($eDeclaration->acceptDeclare()) {

									$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doUpdateDeclareStatus" post-id="'.$eDeclaration['id'].'" class="btn btn-xs btn-outline-secondary mt-1" data-confirm="'.s("Vous avez bien tout vérifié, on valide ?").'">'.s("J'ai télédéclaré").'</a>';

								}

							$h .= '</div>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="vat-timeline vat-timeline-only">';
					$h .= '<div class="vat-timeline-item">';
						$h .= '<div class="vat-timeline-circle">';
							$h .= s("après l'étape {value}", \Asset::icon('3-circle'));
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="vat-timeline-action">';

						$h .= '<div class="vat-timeline-action-title">';

							$h .= \Asset::icon('4-circle').' '.s("Comptabilisation de la déclaration");

							if($eDeclaration->isAccounted() === FALSE and $eDeclaration->isDeclared()) {

								$h .= '<span class="util-badge color-primary bg-info ml-1">'.s("Étape en attente").'</span>';

								$h .= '<div class="vat-timeline-action-info">';
									$h .= s("Consultez les <link>écritures proposées par {siteName}</link> pour comptabiliser votre déclaration, basées sur vos écritures comptables et votre déclaration et enregistrez-les en un clic, ou saisissez-les vous-même dans votre livre-journal.", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eDeclaration['id'].'">']);
								$h .= '</div>';

							} else if($eDeclaration->isAccounted()) {

								$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Étape terminée").'</span>';

							}

							$h .= '<div class="vat-timeline-action-info">';
								$h .= s("Consultez les écritures proposées par {siteName} pour comptabiliser votre déclaration, basées sur vos écritures comptables et votre déclaration et enregistrez-les en un clic, ou saisissez-les vous-même dans votre livre-journal.");
							$h .= '</div>';

						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="vat-timeline vat-timeline-only">';
					$h .= '<div class="vat-timeline-item">';
						$h .= '<div class="vat-timeline-circle">';
							$h .= s("après l'opération bancaire de paiement ou remboursement");
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div class="vat-timeline-action">';
						$h .= '<div class="vat-timeline-action-title">';
							$h .= \Asset::icon('5-circle').' ';
							if($eDeclaration->isCredit()) {
								$h .= s("Comptabilisation du remboursement");
							} else {
								$h .= s("Comptabilisation du paiement");
							}
							if($eDeclaration->isAccounted() and $eDeclaration->isPaid() === FALSE) {

								$h .= '<span class="util-badge color-primary bg-info ml-1">'.s("Étape en attente").'</span>';

								$h .= '<div class="vat-timeline-action-info">';
									if($eDeclaration->isCredit()) {
										$h .= s("Une fois le remboursement effectué, importez votre relevé bancaire et enregistrez les écritures liées. {siteName} vous <link>propose les écritures à enregistrer</link> pour cette opération bancaire.", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eDeclaration['id'].'">']);
									} else {
										$h .= s("Une fois le paiement effectué, importez votre relevé bancaire et enregistrez les écritures liées. {siteName} vous <link>propose les écritures à enregistrer</link> pour cette opération bancaire.", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva/operations?id='.$eDeclaration['id'].'">']);
									}

									if($eDeclaration->acceptPay()) {

										$h .= '<br />';
										$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doUpdatePaymentStatus" post-id="'.$eDeclaration['id'].'" class="btn btn-xs btn-outline-secondary mt-1" data-confirm="'.s("Vous avez bien tout vérifié, on valide ?").'">';
										if($eDeclaration->isCredit()) {
											$h .= s("J'ai comptabilisé le remboursement");
										} else {
											$h .= s("J'ai comptabilisé le paiement");
										}
										$h .= '</a>';

									}

								$h .= '</div>';

							} else if($eDeclaration->isPaid()) {

								$h .= '<span class="util-badge color-white bg-success ml-1">'.s("Étape terminée").'</span>';

								$h .= '<div class="vat-timeline-action-info">';
									$h .= s("Cette déclaration est maintenant entièrement terminée !");
								$h .= '</div>';

							} else {

								$h .= '<div class="vat-timeline-action-info">';

									if($eDeclaration->isCredit()) {
										$h .= s("Une fois le remboursement effectué, importez votre relevé bancaire et enregistrez les écritures liées. {siteName} vous propose les écritures à enregistrer pour cette opération bancaire.");
									} else {
										$h .= s("Une fois le paiement effectué, importez votre relevé bancaire et enregistrez les écritures liées. {siteName} vous propose les écritures à enregistrer pour cette opération bancaire.");
									}

								$h .= '</div>';

							}

						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';
		return $h;

	}

	public function getOperationsTab(\farm\Farm $eFarm, string $type, \Collection $cOperation, array $vatParameters): string {

		$hasDifferences = $cOperation->find(fn($eOperation) => $eOperation['operation']->notEmpty() and round($eOperation['operation']['vatRate'], 1) !== round(($eOperation['amount'] / $eOperation['operation']['amount']) * 100, 1))->notEmpty();


		$h = '<div class="tab-panel selected" data-tab="journal-'.$type.'">';

			$h .= $this->displayPeriod($vatParameters);

			if($hasDifferences) {
				$form = new \util\FormUi();

				$h .= '<div class="mb-1">'.$form->checkbox(NULL, '1', [
					'onchange' => 'Vat.toggleJournalFilter(this.checked);',
					'checked' => FALSE,
					'callbackLabel' => fn($input) => $input.' '.s("Filtrer sur les écarts de TVA"),
				]).'</div>';

			}

			$h .= '<div class="stick-sm util-overflow-sm">';

				if($cOperation->empty()) {

					$h .= '<div class="util-empty">'.s("Il n'y a eu aucune écriture comptable enregistrée pour la période du {from} au {to}.", ['from' => \util\DateUi::numeric($vatParameters['from']), 'to' => \util\DateUi::numeric($vatParameters['to'])]).'</div>';

				} else {

					$h .= '<table id="vat-journal" class="tr-even tr-hover">';

						$h .= '<thead class="thead-sticky">';
							$h .= '<tr>';
								$h .= '<th rowspan="2">'.s("Date").'</th>';
								$h .= '<th rowspan="2">'.s("Numéro compte").'</th>';
								$h .= '<th rowspan="2">'.s("Pièce comptable").'</th>';
								$h .= '<th rowspan="2">'.s("Description").'</th>';
								$h .= '<th rowspan="2">'.s("Tiers").'</th>';
								$h .= '<th colspan="2" class="text-center">'.s("Taux TVA (%)").'</th>';
								$h .= '<th rowspan="2" class="text-center">'.s("Montant TTC").'</th>';
								$h .= '<th rowspan="2" class="text-center">'.s("Montant HT").'</th>';
								$h .= '<th rowspan="2" class="text-center">'.s("TVA (€)").'</th>';
							$h .= '</tr>';
							$h .= '<tr>';
								$h .= '<th class="td-min-content text-end rowspaned-center">'.s("déclaré").'</th>';
								$h .= '<th class="td-min-content text-end rowspaned-center">'.s("calculé").'</th>';
							$h .= '</tr>';
						$h .= '</thead>';

						$h .= '<tbody>';
							foreach($cOperation as $eOperation) {

								$eOperationInitial = $eOperation['operation'];

								$h .= '<tr class="">';

									$h .= '<td>';
										if($eOperationInitial->notEmpty()) {
											$h .= \util\DateUi::numeric($eOperationInitial['date']);
										} else {
											$h .= \util\DateUi::numeric($eOperation['date']);
										}
									$h .= '</td>';

									$h .= '<td>';
										$h .= '<div class="journal-operation-description" data-dropdown="bottom" data-dropdown-hover="true">';
										if($eOperation['accountLabel'] !== NULL) {
											$h .= encode(trim($eOperation['accountLabel'], '0'));
										} else {
											$h .= encode($eOperation['account']['class'], 8, 0);
										}
										$h .= '</div>';
										$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<span class="dropdown-item">'.encode($eOperation['account']['class']).' '.encode($eOperation['account']['description']).'</span>';
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td>';
										$h .= '<div class="operation-info">';
											if($eOperationInitial->notEmpty()) {
												if($eOperationInitial['document'] !== NULL) {
													$h .= '<a href="'.new \journal\JournalUi()->getBaseUrl($eFarm, $eOperationInitial['financialYear']).'&document='.urlencode($eOperationInitial['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperationInitial['document']).'</a>';
												}
											} else {
												if($eOperation['document'] !== NULL) {
													$h .= '<a href="'.new \journal\JournalUi()->getBaseUrl($eFarm, $eOperation['financialYear']).'&document='.urlencode($eOperation['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperation['document']).'</a>';
												}
											}
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td class="td-description">';
										$h .= '<div class="description">';
											if($eOperationInitial->notEmpty()) {
												$h .= encode($eOperationInitial['description']);
											} else {
												$h .= encode($eOperation['description']);
											}
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td>';
										if($eOperationInitial->notEmpty()) {
											if($eOperationInitial['thirdParty']->exists() === TRUE) {
												$h .= encode($eOperationInitial['thirdParty']['name']);
											}
										} else {
											if($eOperation['thirdParty']->exists() === TRUE) {
												$h .= encode($eOperation['thirdParty']['name']);
											}
										}
									$h .= '</td>';

									$h .= '<td class="td-min-content text-end">';
										if($eOperationInitial->notEmpty()) {
											$h .= $eOperationInitial['vatRate'];
										} else {
											$h .= '';
										}
									$h .= '</td>';

									if($eOperationInitial->notEmpty()) {
										$calculatedVatRate = round(($eOperation['amount'] / $eOperationInitial['amount']) * 100, 1);
										$class = ($eOperationInitial['vatRate'] !== $calculatedVatRate ? 'color-danger' : '');
									} else {
										$calculatedVatRate = '';
										$class = '';
									}
									$h .= '<td class="td-min-content text-end '.($class).'">';
										$h .= $calculatedVatRate;
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content t-highlight td-vertical-align-top">';
										if($eOperationInitial->notEmpty()) {
											$h .= \util\TextUi::money($eOperationInitial['amount'] + $eOperation['amount']);
										}
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content t-highlight td-vertical-align-top">';
										if($eOperationInitial->notEmpty()) {
											$h .= \util\TextUi::money($eOperationInitial['amount']);
										}
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content t-highlight td-vertical-align-top">';
										$h .= \util\TextUi::money($eOperation['amount']);
									$h .= '</td>';

								$h .= '</tr>';

							}
						$h .= '</tbody>';

					$h .= '</table>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	private function displayPeriod(array $vatParameters): string {

		$h = '<div class="vat-period-block">';
			$h .= '<h3>';
				$h .= s(
				"Période du {from} au {to}",
				['from' => \util\DateUi::numeric($vatParameters['from']), 'to' => \util\DateUi::numeric($vatParameters['to'])]
			);
			$h .= '</h3>';
		$h .= '</div>';

		return $h;

	}

	public function getCheck(array $check, array $vatParameters ): string {

		$taxes = $check['taxes'];
		$sales = $check['sales'];
		$deposits = $check['deposits'];

		$hasIncoherence = FALSE;

		$h = '<div class="tab-panel selected" data-tab="check">';

			$h .= $this->displayPeriod($vatParameters);

			$h .= '<h3>'.s("Contrôles de cohérence").'</h3>';

			$h .= '<table class="tr-even tr-hover vat-width-100">';

				$h .= '<thead>';
					$h .= '<th class="text-center td-min-content">'.s("Taux").'</th>';
					$h .= '<th class="text-end t-highlight">'.s("Montant (HT)").'</th>';
					$h .= '<th class="text-end t-highlight">'.s("Montant de TVA<br />(calculé)").'</th>';
					$h .= '<th class="text-end t-highlight">'.s("TVA collectée<br />(compte {value})", \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT).'</th>';
					$h .= '<th class="text-center td-min-content">'.s("Contrôle").'</th>';
				$h .= '</thead>';

				$h .= '<tbody>';

					$totalCalculated = 0;
					$totalCollected = 0;
					foreach($sales as $sale) {

						$collectedVat = $taxes[\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT][(string)$sale['vatRate']]['amount'] ?? 0;

						$h .= '<tr>';
							$h .= '<th class="text-center td-min-content text-end">'.round($sale['vatRate'], 1).'</th>';
							$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($sale['amount'], precision: 0).'</td>';
							$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($sale['tax'], precision: 0).'</td>';
							$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($collectedVat, precision: 0).'</td>';
							$h .= '<td class="text-center td-min-content">';

								if(round($collectedVat) === round($sale['tax'])) {

									$h .= '<span class="color-success">'.\Asset::icon('check').'</span>';

								} else if((round($collectedVat) - round($sale['tax'])) < 2) {

									$h .= '<span class="color-warning">'.\Asset::icon('exclamation-triangle').'</span>';
									$hasIncoherence = TRUE;

								} else {

									$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
									$hasIncoherence = TRUE;

								}
							$h .= '</td>';
						$h .= '</tr>';

						$totalCalculated += $sale['tax'];
						$totalCollected += $collectedVat;
					}

					$h .= '<tr class="tr-bold">';
						$h .= '<th class="text-center td-min-content text-end">'.s("Totaux").'</th>';
						$h .= '<td class="text-end t-highlight"></td>';
						$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($totalCalculated, precision: 0).'</td>';
						$h .= '<td class="text-end t-highlight">'.\Asset::icon('1-circle').' '.\util\TextUi::money($totalCollected, precision: 0).'</td>';
						$h .= '<td class="text-center">';
							if(round($totalCollected) === round($totalCalculated)) {

								$h .= '<span class="color-success">'.\Asset::icon('check').'</span>';

							} else if((round($totalCollected) - round($totalCalculated)) < 2) {

								$h .= '<span class="color-warning">'.\Asset::icon('exclamation-triangle').'</span>';
								$hasIncoherence = TRUE;

							} else {

								$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
								$hasIncoherence = TRUE;

							}
						$h .= '</td>';
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

			if($hasIncoherence) {

				$h .= '<div class="util-warning-outline vat-width-100">';
					$h .= s("Il y a une incohérence entre les opérations de vente et les opérations de TVA enregistrées dans votre journal : la somme de la TVA sur le compte {value} ne correspond pas avec le montant de TVA qui est calculé.", \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT);
					if((round($totalCollected) - round($totalCalculated)) < 2) {
						$h .= '<p>';
							$h .= s("Cette différence étant faible, vous pourrez utiliser les comptes {charge} ou {product} pour équilibrer vos écritures si nécessaire.", ['charge' => \account\AccountSetting::CHARGES_OTHER_CLASS, 'product' => \account\AccountSetting::PRODUCT_OTHER_CLASS]);
						$h .= '</p>';
					}
				$h .= '</div>';

			} else if($totalCalculated !== 0) {

				$h .= '<div class="util-block-success vat-width-100">'.s("Félicitations ! Vos écritures de TVA sont cohérentes avec vos écritures de ventes, vous pouvez passer à l'étape suivante.").'</div>';

			}

			$abs44566 = 0;
			$immo44562 = 0;
			foreach($taxes as $account => $taxesByRate) {

				if(mb_strpos($account, \account\AccountSetting::VAT_BUY_CLASS_PREFIX) === FALSE) {
					continue;
				}

				switch(mb_substr($account, 4, 1)) {
					case '2':
						foreach($taxesByRate as $tax) {
							$immo44562 += $tax['amount'];
						}
						break;

					case '6':
						foreach($taxesByRate as $tax) {
							$abs44566 += $tax['amount'];
						}
						break;

				}

			}

			$vatCredit = $deposits[\account\AccountSetting::VAT_CREDIT_CLASS];
			$vatDeposit = $deposits[\account\AccountSetting::VAT_DEPOSIT_CLASS];

			$vatBalance = $totalCollected - $abs44566 - $immo44562 - $vatCredit;
			$h .= '<h3>'.s("Récapitulatif").'</h3>';

			$h .= '<table class="vat-summary-table tr-even tr-hover vat-width-100">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th class="text-center">'.s("Compte ou calcul").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Montant").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tr>';
					$h .= '<th>'.s("TVA déductible sur biens et services").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT.'</td>';
					$h .= '<td class="text-end t-highlight">'.\util\TextUi::money(round($abs44566), precision: 0).'</td>';
					$h .= '<td>'.\Asset::icon('2-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("TVA sur immobilisations").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_ASSET_CLASS.'</td>';
					$h .= '<td class="text-end t-highlight">'.\util\TextUi::money(round($immo44562), precision: 0).'</td>';
					$h .= '<td>'.\Asset::icon('3-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("Crédit de TVA à reporter").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_CREDIT_CLASS.'</td>';
					$h .= '<td class="text-end t-highlight">'.\util\TextUi::money(round($vatCredit), precision: 0).'</td>';
					$h .= '<td>'.\Asset::icon('4-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>';
					if($vatBalance > 0) {
						$h .= s("<b>TVA à payer</b> ou TVA à déduire");
					} else {
						$h .= s("TVA à payer ou <b>TVA à déduire</b>");
					}
					$h .= '</th>';
					$h .= '<td class="text-center">';
						$h .= '+ '.\Asset::icon('1-circle').' - '.\Asset::icon('2-circle').' - '.\Asset::icon('3-circle').' - '.\Asset::icon('4-circle');
					$h .= '</td>';
					$h .= '<td class="text-end t-highlight">'.\util\TextUi::money(round($vatBalance), precision: 0).'</td>';
					$h .= '<td>'.\Asset::icon('5-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("Acomptes de TVA").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_DEPOSIT_CLASS.'</td>';
					$h .= '<td class="text-end t-highlight">'.\util\TextUi::money(round($vatDeposit), precision: 0).'</td>';
					$h .= '<td>'.\Asset::icon('6-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr class="tr-bold">';
					$h .= '<th>'.s("Total : ");
					if($vatBalance - $vatDeposit < 0) {
						$h .= s("Crédit de TVA");
					} else {
						$h .= s("TVA à payer");
					}
					$h .= '</th>';
					$h .= '<td class="text-center">+ '.\Asset::icon('5-circle').' -  '.\Asset::icon('6-circle').'</td>';
					$h .= '<th class="text-end t-highlight">'.\util\TextUi::money(abs(round($vatBalance) - round($vatDeposit)), precision: 0).'</th>';
				$h .= '<td></td>';
				$h .= '</tr>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getCerfa(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, array $cerfaData, int $precision, array $vatParameters, bool $hasData, float $adarBase): string {

		$eDeclaration = $cerfaData['eDeclaration'] ?? new Declaration([
			'from' => $vatParameters['from'],
			'to' => $vatParameters['to'],
			'limit' => $vatParameters['limit'],
		]);

		$h = '<div class="tab-panel selected" data-tab="cerfa">';

			$h .= '<div class="util-warning">';
				$h .= s("Ce formulaire est encore expérimental, ne l'utilisez pas pour déclarer votre TVA ou comparez-le à votre déclaration de TVA et signalez les différences sur Discord. Merci !");
			$h .= '</div>';

			$h .= $this->displayPeriod($vatParameters);

			if(date('Y-m-d') <= $vatParameters['to']) {

				$openDate = date('Y-m-d', strtotime($vatParameters['to'].' + 1 day'));
				$h .= '<div class="util-info">';
					$h .= p("La déclaration sera ouverte à compter du <b>{date}</b>. Encore <b>{value} jour</b> à patienter !", "La déclaration sera ouverte à compter du <b>{date}</b>. Encore <b>{value} jours</b> à patienter !", ceil(\util\DateLib::interval($openDate, date('Y-m-d')) / 60 / 60 / 24), ['date' => \util\DateUi::numeric($openDate)]);
				$h .= '</div>';

			} else if(date('Y-m-d') <= date('Y-m-d', strtotime($vatParameters['limit'].' + '.\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days')) and ($eDeclaration->exists() === FALSE or $eDeclaration['status'] !== Declaration::ACCOUNTED)) {

				$h .= '<div class="util-info">';
					$h .= s("La déclaration est encore ouverte jusqu'au <b>{value}</b>. Vous pouvez reporter les informations que vous avez télédéclarées ici afin d'en conserver une trace et de bénéficier de la proposition d'enregistrement comptable de {siteName}.", \util\DateUi::numeric(date('Y-m-d', strtotime($vatParameters['limit'].' + '.\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days'))));
				$h .= '</div>';

			} else if($hasData === FALSE) {
				$h .= '<div class="util-warning-outline">';
					$h .= s("Il semblerait que la période du {from} au {to} ne contienne aucune donnée pertinente d'après vos écritures, mais vous pouvez toujours remplir le formulaire à titre de conservation de données.", ['from' => \util\DateUi::numeric($vatParameters['from']), 'to' => \util\DateUi::numeric($vatParameters['to'])]);
				$h .= '</div>';
			}

			// Infos sur la taxe ADAR
			if($eFarm['startedAt'] === NULL and $eFarm['cFinancialYear']->count() === 1) {
				// On ne connaît pas la date de création et il n'y a que 1 exercice enregistré => Doute

				$h .= '<div class="util-info">';
					$h .= s("L'année de création de votre exploitation n'étant pas connue, la <b>taxe sur le chiffre d'affaire des exploitants agricoles</b> a tout de même été calculée.");
				$h .= '</div>';

			} else if($eFarm['startedAt'] !== NULL and substr($eFarm['startedAt'], 0, 4) === substr($eFinancialYear['startDate'], 0, 4)) {

				$h .= '<div class="util-info">';
				$h .= s("La période de déclaration étant l'année de création de votre exploitation, la <b>taxe sur le chiffre d'affaire des exploitants agricoles</b> n'est pas redevable.");
				$h .= '</div>';

			}

			// Infos sur la taxe adar, la redevance d'abattage et la redevance de découpage
			if($eDeclaration->acceptUpdate()) {

				$h .= '<div class="util-block-help">';
					$arguments = [
						'base' => \util\TextUi::money($adarBase, precision: 0),
						'link1' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000022201444/2010-05-01" target="_blank">',
						'link2' => '<a href="https://bofip.impots.gouv.fr/bofip/122-PGP.html/identifiant%3DBOI-TCA-CAEA-20120912" target="_blank">',
						'link4' => '<a onclick="Vat.scrollTo(\'identifier-4220\')";>',
					];
					if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {

						$h .= s("Une <b>taxe sur le chiffre d'affaires des exploitants agricoles</b> doit être déclarée et acquittée. Pour cet exercice, la base taxable est de {base}. Lire l'<link1>Article 302 bis MB du CGI</link1>, et le <link2>BOI-TCA-CAEA</link2> pour plus de détails. <link4>Aller à la cellule correspondante</link4>.", $arguments);

					} else {

						$h .= s("Une fois par an, une <b>taxe sur le chiffre d'affaires des exploitants agricoles</b> doit être déclarée et acquittée. Pour cet exercice, la base taxable est de {base}. Lire l'<link1>Article 302 bis MB du CGI</link1>, et le <link2>BOI-TCA-CAEA</link2> pour plus de détails. <link4>Aller à la cellule correspondante</link4>.", $arguments);

					}
					if((float)($cerfaData[4220] ?? 0.0) !== 0.0) {
						$h .= '<div class="color-success">'.\Asset::icon('check-lg').' '.s("Vous avez indiqué un montant pour cette taxe.").'</div>';
					}
				$h .= '</div>';
				$h .= '<div class="util-block-help">';
					$h .= s("Une <b>redevance sanitaire d'abattage</b> doit être déclarée et acquittée si vous avez effectué des opérations d'abattage. Lire l'<link1>Article 302 bis N du CGI</link1>, le <link2>BOI-TCA-RSAB</link2>, et les tarifs dans l'<link3>Article 50 terdecies du CGI, annexe IV</link3> pour plus de détails. <link4>Aller à la cellule correspondante</link4>.", [
						'link1' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000022328089/2010-05-08" target="_blank">',
						'link2' => '<a href="https://bofip.impots.gouv.fr/bofip/149-PGP.html/identifiant%3DBOI-TCA-RSAB-20150603" target="_blank">',
						'link3' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000026852293/2013-01-01" target="_blank">',
						'link4' => '<a onclick="Vat.scrollTo(\'identifier-4253\')";>',
					]);
					if((float)($cerfaData[4253] ?? 0.0) !== 0.0) {
						$h .= '<div class="color-success">'.\Asset::icon('check-lg').' '.s("Vous avez indiqué un montant pour cette redevance.").'</div>';
					}
				$h .= '</div>';
				$h .= '<div class="util-block-help">';
					$h .= s("Une <b>redevance sanitaire de découpage</b> doit être déclarée et acquittée si vous avez procédé à des opérations de découpage de viande avec os en France métropolitaine. Lire l'<link1>Article 302 bis S du CGI</link1>, le <link2>BOI-TCA-RSD</link2>, et les tarifs dans l'<link3>302 bis T du CGI, annexe IV</link3> pour plus de détails. <link4>Aller à la cellule correspondante</link4>.", [
						'link1' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000022328110/2010-05-08" target="_blank">',
						'link2' => '<a href="https://bofip.impots.gouv.fr/bofip/2339-PGP.html/identifiant%3DBOI-TCA-RSD-20150603" target="_blank">',
						'link3' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000021664460/2010-01-01" target="_blank">',
						'link4' => '<a onclick="Vat.scrollTo(\'identifier-4254\')";>',
					]);
					if((float)($cerfaData[4254] ?? 0.0) !== 0.0) {
						$h .= '<div class="color-success">'.\Asset::icon('check-lg').' '.s("Vous avez indiqué un montant pour cette redevance.").'</div>';
					}
				$h .= '</div>';

			}

			$h .= '<div class="util-info">';
				$h .= s("Veuillez noter que les cellules marquées <span>ainsi</span> ne sont pas pré-remplies par {siteName}.", ['span' => '<span class="vat-not-auto">']);
			$h .= '</div>';


			if($eFarm->getConf('vatFrequency') === \farm\Configuration::ANNUALLY) {

				$h .= new VatUi()->getCerfaCA12($eFarm, $cerfaData, $precision, $vatParameters);

			} else {

				$h .= new VatUi()->getCerfaCA3($eFarm, $cerfaData, $precision, $vatParameters);

			}

		$h .= '</div>';

		return $h;

	}

	private function getCerfaCA12(\farm\Farm $eFarm, array $data, int $precision, array $vatParameters): string {

		\Asset::js('vat', 'vat-ca12.js');
		$eDeclaration = $data['eDeclaration'] ?? new Declaration(['from' => $vatParameters['from'], 'to' => $vatParameters['to'], 'limit' => $vatParameters['limit']]);
		$isDisabled = ($eDeclaration->acceptUpdate() === FALSE);

		$attributes = $isDisabled ? ['disabled' => 'disabled'] : [];

		$form = new \util\FormUi();

		$h = '';

		if($eDeclaration->exists()) {

			if($eDeclaration['declaredAt'] !== NULL) {

				if($eDeclaration['accountedAt'] !== NULL) {

					$h .= '<div class="util-info">'.s("Déclaration faite le {date} et enregistrée en comptabilité le {accountedAt}.", [
						'date' => \util\DateUi::numeric($eDeclaration['declaredAt'], \util\DateUi::DATE_HOUR_MINUTE),
						'accountedAt' => \util\DateUi::numeric($eDeclaration['accountedAt'], \util\DateUi::DATE_HOUR_MINUTE),
					]).'</div>';

				} else {

					$h .= '<div class="util-info">'.s("Déclaration faite le {date}.", [
						'date' => \util\DateUi::numeric($eDeclaration['declaredAt'], \util\DateUi::DATE_HOUR_MINUTE),
					]).'</div>';

				}

			} else if($eDeclaration['limit'] < date('Y-m-d')) {

				$h .= '<div class="util-danger">'.s("Vous êtes en retard ! Pensez à effectuer votre déclaration sur <link>impots.gouv.fr</link> avant le <b>{date}</b> puis à enregistrer ce formulaire comme déclaré.", ['date' => \util\DateUi::numeric($eDeclaration['limit']), 'link' => '<a href="http://impots.gouv.fr">']).'</div>';

			} else if($eDeclaration['limit'] < date('Y-m-d', strtotime(\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days ago'))) {

				$h .= '<div class="util-warning-outline">'.s("Pensez à effectuer votre déclaration sur <link>impots.gouv.fr</link> avant le <b>{date}</b> puis à enregistrer ce formulaire comme déclaré.", ['date' => \util\DateUi::numeric($eDeclaration['limit']), 'link' => '<a href="http://impots.gouv.fr">']).'</div>';

			}
		}


		$h .= $form->openAjax(\farm\FarmUi::urlConnected($eFarm).'/vat/saveCerfa', ['id' => 'cerfa-12', 'class' => 'vat-cerfa-form', 'data-precision' => $precision]);

			$h .= $form->hidden('from', $vatParameters['from']);
			$h .= $form->hidden('to', $vatParameters['to']);

			$h .= '<div class="stick-sm util-overflow-sm">';

				$h .= '<table class="vat-cerfa">';

					$h .= '<thead>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-chapter-title" colspan="8">'.s("I - TVA BRUTE").'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="3">'.s("Opérations non taxées").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Base HT").'</th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="3">'.s("Opérations non taxées").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Base HT").'</th>';
						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("01").'</td>';
							$h .= '<td>'.s("Achats en franchise").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0037").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0037', $data['0037'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number" rowspan="3">'.s("04").'</td>';
							$h .= '<td rowspan="3">'.s("Livraisons intracommunautaires à destination d’une personne assujettie ").'</td>';
							$h .= '<td class="vat-cerfa-identifier" rowspan="3">'.s("0034").'</td>';
							$h .= '<td class="vat-cerfa-input" rowspan="3">'.$form->number('0034', $data['0034'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("02").'</td>';
							$h .= '<td>'.s("Exportations hors UE").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0032").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0032', $data['0032'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("03").'</td>';
							$h .= '<td>'.s("Autres opérations non imposables").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0033").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0033', $data['0033'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("3A").'</span></td>';
							$h .= '<td>'.s("Ventes à distance taxables dans un autre État membre au profit de personnes non assujetties").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0047").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0047', $data['0047'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("4B").'</span></td>';
							$h .= '<td>'.s("Ventes de biens ou prestations de services réalisées par un assujetti non établi en France (article 283-1 du code général des impôts)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0043").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0043', $data['0043'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("3B").'</span></td>';
							$h .= '<td>'.s("Mises à la consommation de produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0049").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0049', $data['0049'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("4D").'</span></td>';
							$h .= '<td>'.s("Livraisons d’électricité, de gaz naturel, de chaleur ou de froid non imposables en France ").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0029").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0029', $data['0029'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';
					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="1">';

					$h .= '<thead>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="3">'.s("Opérations taxées").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Base HT").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Taxe due").'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title"></th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("- réalisées en France métropolitaine").'</th>';
							$h .= '<th></th>';
							$h .= '<th></th>';
						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("5A").'</td>';
							$h .= '<td>'.s("Taux normal 20%").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0207").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0207-base', $data['0207-base'] ?? 0, ['data-rate' => 20] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0207', $data['0207'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("5B").'</span></td>';
							$h .= '<td>'.s("Taux normal 20% sur les produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0208").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0208-base', $data['0208-base'] ?? 0, ['data-rate' => 20] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0208', $data['0208'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("06").'</td>';
							$h .= '<td>'.s("Taux réduit 5.5%").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0105").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0105-base', $data['0105-base'] ?? 0, ['data-rate' => 5.5] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0105', $data['0105'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("6C").'</td>';
							$h .= '<td>'.s("Taux réduit 10%").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0151").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0151-base', $data['0151-base'] ?? 0, ['data-rate' => 10] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0151', $data['0151'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title"></th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("- réalisées dans les DOM").'</th>';
							$h .= '<th></th>';
							$h .= '<th></th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("07").'</span></td>';
							$h .= '<td>'.s("Taux normal 8.5%").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0201").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0201-base', $data['0201-base'] ?? 0, ['data-rate' => 8.5] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0201', $data['0201'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("08").'</span></td>';
							$h .= '<td>'.s("Taux réduit 2.1%").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0100").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0100-base', $data['0100-base'] ?? 0, ['data-rate' => 2.1] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0100', $data['0100'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title"></th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("- à un autre taux (France métropolitaine ou DOM)").'</th>';
							$h .= '<th></th>';
							$h .= '<th></th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("09").'</span></td>';
							$h .= '<td>'.s("Opérations imposables à un taux particulier").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0950").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0950-base', $data['0950-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0950-tax', $data['0950'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("9A").'</span></td>';
							$h .= '<td>'.s("Taux réduit 13% sur les produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0152").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0152-base', $data['0152-base'] ?? 0, ['data-rate' => 13] + $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0152', $data['0152'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("10").'</span></td>';
							$h .= '<td>'.s("Anciens taux").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0900").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0900-base', $data['0900-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0900', $data['0900'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title"></th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("- autres opérations").'</th>';
							$h .= '<th></th>';
							$h .= '<th></th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("AA").'</span></td>';
							$h .= '<td>'.s("Achats d'électricité, de gaz naturel, de chaleur ou de froid imposables en France").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0030").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0030-base', $data['0030-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0030', $data['0030'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("AB").'</span></td>';
							$h .= '<td>'.s("Achats de biens ou de prestations de services réalisés auprès d’un assujetti non établi en France (article 283-1 du code général des impôts)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0040").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0040-base', $data['0040-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0040', $data['0040'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("AC").'</span></td>';
							$h .= '<td>'.s("Achats de prestations de services auprès d’un assujetti non établi en France (article 283-2 du code général des impôts)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0044").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0044-base', $data['0044-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0044', $data['0044'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("11").'</td>';
							$h .= '<td>'.s("Cessions d'immobilisations").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0970").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0970-base', $data['0970-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0970', $data['0970'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("12").'</span></td>';
							$h .= '<td>'.s("Livraisons à soi-même").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0980").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0980-base', $data['0980-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0980', $data['0980'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("13").'</span></td>';
							$h .= '<td>'.s("Autres opérations imposables").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0981").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0981-base', $data['0981-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0981', $data['0981'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("16").'</td>';
							$h .= '<td colspan="3">'.s("TOTAL DE LA TAXE DUE (ligne 5A à 13)").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('16-number', $data['16-number'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tbody>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title"></th>';
							$h .= '<th class="vat-cerfa-title" colspan="3">'.s("Autre TVA DUE").'</th>';
							$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("17").'</span></td>';
							$h .= '<td colspan="2">'.s("Remboursements provisionnels obtenus en cours d'année ou d'exercice").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0983").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0983', $data['0983'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("18").'</span></td>';
							$h .= '<td colspan="2">';
								$h .= s("TVA antérieurement déduite à reverser");
								$h .= '<div style="display: flex; align-items: center; white-space: nowrap">';
									$h .= s("(<span>dont TVA sur les produits pétroliers {input}</span>)", ['span' => '<span style="display: flex; align-items: center;">', 'input' => $form->number('0600-petrol', $data['0600-petrol'] ?? 0, attributes: ['class' => 'ml-1'] + $attributes)]);
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0600").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0600', $data['0600'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("AD").'</span></td>';
							$h .= '<td colspan="2">'.s("Sommes à ajouter").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0602").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0602', $data['0602'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("19").'</td>';
							$h .= '<td colspan="3">'.s("TOTAL DE LA TVA BRUTE DUE (lignes 16 + 17 + 18 + AD)").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('19-number', $data['19-number'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="4">'.s("II - TVA DÉDUCTIBLE").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("Autres biens et services").'</th>';
						$h .= '<th class="vat-cerfa-title">'.s("Taxe déductible").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("20").'</td>';
						$h .= '<td>'.s("Déductions sur factures (1)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0702").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0702', $data['0702'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("21").'</span></td>';
						$h .= '<td>'.s("Déductions forfaitaires (1)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0704").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0704', $data['0704'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("22").'</td>';
						$h .= '<td>'.s("TOTAL (lignes 20 + 21)").'</td>';
						$h .= '<td></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('22-number', $data['22-number'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("Immobilisations").'</th>';
						$h .= '<th class="vat-cerfa-title"></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("23").'</td>';
						$h .= '<td>'.s("TVA déductible sur immobilisations (1)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0703").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0703', $data['0703'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("Autre TVA à déduire").'</th>';
						$h .= '<th class="vat-cerfa-title"></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("24").'</td>';
						$h .= '<td>'.s("Crédit antérieur non imputé et non remboursé").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0058").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0058', $data['0058'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("25").'</span></td>';
						$h .= '<td>'.s("Ommissions ou compléments de déductions dont régularisations TVA déductible sur les produits pétroliers").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0059").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0059', $data['0059'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("25A").'</span></td>';
						$h .= '<td>';
							$h .= '<div style="display: flex; align-items: center;">';
							$h .= s("(1) Compte-tenu, les cas échéant, du coefficient de déduction {input}", ['input' => $form->number('25A', $data['25A'] ?? 0, ['class' => 'ml-1 vat-cerfa-input'] + $attributes)]);
							$h .= '</div>';
						$h .= '</td>';
						$h .= '<td class=""></td>';
						$h .= '<td class=""></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("AE").'</span></td>';
						$h .= '<td>'.s("Sommes à imputer").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0603").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0603', $data['0603'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("26").'</td>';
						$h .= '<td>'.s("TOTAL DE LA TVA DÉDUCTIBLE (lignes 22 + 23 + 24 + 25 + AE)").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('26-number', $data['26-number'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("2E").'</span></td>';
						$h .= '<td>'.s("Dont TVA déductible sur les produits pétroliers").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0711").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0711', $data['0711'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="3">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="6">'.s("III - TVA NETTE").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="3">'.s("Résultat de la liquidation").'</th>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title">'.s("Taxe déductible").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("28").'</td>';
						$h .= '<td colspan="3">'.s("TVA due : (Ligne 19 - Ligne 26) ou").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8900").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8900', $data['8900'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("29").'</td>';
						$h .= '<td colspan="3">'.s("CRÉDIT : (Ligne 26 - Ligne 19)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0705").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0705', $data['0705'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="5">';
							$h .= '<span class="vat-not-auto">'.s("Imputations / Régularisation des acomptes").'</span>';
						$h .= '</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td></td>';
						$h .= '<td>';
							$h .= '<table>';
								$h .= '<tr>';
									$h .= '<td colspan="3">';
										$h .= ' <a class="mt-1" href="'.\farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/journal/livre-journal?accountLabel='.\account\AccountSetting::VAT_DEPOSIT_CLASS.'" target="_blank">'.s("Voir les écritures d'acompte de TVA dans le livre-journal").' '.\Asset::icon('box-arrow-up-right').' </a>';
									$h .= '</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td></td>';
									$h .= '<td class="text-center">'.s("Col. 1 : Montant<br /><b>effectivement payé</b>").'</td>';
									$h .= '<td class="text-center">'.s("Col. 2 : Montant<br /><b>restant à payer</b>").'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td>'.s("Acompte&nbsp;1").'</td>';
									$h .= '<td class="vat-cerfa-input"><div class="vat-reverse-direction">'.$form->number('deposit[0][paid]', $data['deposit[0][paid]'] ?? 0, $attributes).'</div></td>';
									$h .= '<td class="vat-cerfa-input"><div class="vat-reverse-direction">'.$form->number('deposit[0][not-paid]', $data['deposit[0][not-paid]'] ?? 0, $attributes).'</div></td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td>'.s("Acompte&nbsp;2").'</td>';
									$h .= '<td class="vat-cerfa-input"><div class="vat-reverse-direction">'.$form->number('deposit[1][paid]', $data['deposit[1][paid]'] ?? 0, $attributes).'</div></td>';
									$h .= '<td class="vat-cerfa-input"><div class="vat-reverse-direction">'.$form->number('deposit[1][not-paid]', $data['deposit[1][not-paid]'] ?? 0, $attributes).'</div></td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td></td>';
									$h .= '<td class="">';
										$h .= '<div class="vat-reverse-direction">';
											$h .= s("Tot.&nbsp;1").' '.$form->number('deposit[total][paid]', $data['deposit[total][paid]'] ?? 0, $attributes).'</td>';
										$h .= '</div>';;
									$h .= '<td class="">';
										$h .= '<div class="vat-reverse-direction">';
											$h .= s("Tot.&nbsp;2").' '.$form->number('deposit[total][not-paid]', $data['deposit[total][not-paid]'] ?? 0, $attributes).'</td>';
										$h .= '</div>';
								$h .= '</tr>';
							$h .= '</table>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-number td-vertical-align-top"><span class="vat-not-auto">'.s("30").'</span></td>';
						$h .= '<td class="td-vertical-align-top">';
							$h .= s("Acomptes payés et / ou restant dus<br />(Tot. 1 + Tot. 2)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier td-vertical-align-top">'.s("0018").'</td>';
						$h .= '<td class="vat-cerfa-input td-vertical-align-top">'.$form->number('0018', $data['0018'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="3">'.s("Résultat net").'</th>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title"></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("33").'</td>';
						$h .= '<td colspan="3">'.s("<b>SOLDE DÛ</b> si ligne 28 - (lignes 29 + 30) >= 0").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('33-number', $data['33-number'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("34").'</td>';
						$h .= '<td colspan="3">'.s("<b>EXCÉDENT DE VERSEMENT</b> si ligne 30 - ligne 28 >= 0").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('34-number', $data['34-number'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("35").'</td>';
						$h .= '<td colspan="3">'.s("<b>SOLDE EXCÉDENTAIRE</b> : (lignes 29 + 34)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("0020").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('0020', $data['0020'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="4">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="7">'.s("IV - Décompte des taxes assimilées").' <span class="vat-not-auto">'.s("Cette section est à remplir manuellement").'</span></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="4">'.s("Nature des taxes").'</th>';
						$h .= '<th class="vat-cerfa-title"></th>';
						$h .= '<th class="vat-cerfa-title">'.s("Taxe brute").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("36").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe sur la cession de droits d’exploitation audiovisuelle des manifestations sportives au taux de 5 % (CIBS, art. L455-28)");
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4215-base', $data['4215-base'] ?? 0, ['data-rate' => 5] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4215").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4215', $data['4215'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("37").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe sur le chiffre d’affaires des exploitants agricoles (CGI, art. 302 bis MB)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier" id="identifier-4220">'.s("4220").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4220', $data['4220'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Taxe sur les vidéogrammes (CIBS, art. L452-28)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"</td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("38A").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 1,8025%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4330-base', $data['4330-base'] ?? 0, ['data-rate' => 1.8025] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4330").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4330', $data['4330'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("38B").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 15%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4331-base', $data['4331-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4331").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4331', $data['4331'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Taxe sur les services de contenus audiovisuels à la demande (CIBS, art. L453-25)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"</td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("42").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 5,15%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4229-base', $data['4229-base'] ?? 0, ['data-rate' => 5.15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4229").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4229', $data['4229'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("43").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 15%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4228-base', $data['4228-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4228").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4228', $data['4228'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Taxe sur la la publicité diffusée au moyen de contenus audiovisuels à la demande (CIBS, art. L454-16)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"</td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("43A").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 5,15%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4298-base', $data['4298-base'] ?? 0, ['data-rate' => 5.15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4298").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4298', $data['4298'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("43B").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">'.s("- au taux de 15%").'</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4299-base', $data['4299-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4299").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4299', $data['4299'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("44").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe sur les actes des huissiers de justice (CGI, art. 302 bis Y) (14,89 € par acte accompli à compter du 1er janvier 2017) ");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre d'actes").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4206-base', $data['4206-base'] ?? 0, ['data-fixed-price' => 14.89] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4206").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4206', $data['4206'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("Contribution sur la rente infra-marginale de la production d’électricité due au titre de la période 4 du 01/01/2024 au 31/12/2024");
							$h .= '<table>';
								$h .= '<tr>';
									$h .= '<td rowspan="2">'.s("P4 - Calcul de la marge forfaitaire").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Quantité d'électricité produite sur la période (en MWh)").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Forfait").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Report d'un montant négatif d'une période antérieure").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Marge forfaitaire (minorée du report le cas échéant) (a)").'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-quantity', $data['p4-quantity'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-forfait', $data['p4-forfait'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-report', $data['p4-report'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-a', $data['p4-a'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="4" rowspan="2">'.s("P4 – Sommes déductibles au titre des redevances hydrauliques").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Montant déductible (b)").'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-b', $data['p4-b'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="4" rowspan="2">'.s("P4 – Sommes déductibles au titre du service public de traitement des déchets").'</td>';
									$h .= '<td class="font-sm text-center">'.s("Montant déductible (c)").'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-c', $data['p4-c'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="4" class="text-end">'.s("Taxe due au titre de P4 si (a – b – c) > 0").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-total', $data['total'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="4" class="text-end">'.s("Régularisation au titre d’une période antérieure").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-regularisation', $data['regularisation'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="4" class="text-end">'.s("Montant de la contribution sur la rente infra-marginale de la production d’électricité due au titre de P4 (Taxe due au titre de P4 +/- Régularisation au titre d’une période antérieure) (A)").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('p4-A', $data['p4-A'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
							$h .= '</table>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("44A").'</td>';
						$h .= '<td colspan="4"><b>';
							$h .= s("Total de la contribution sur la rente inframarginale de la production d’électricité due (report de A si > 0)");
						$h .= '</b></td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4315").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4315', $data['4315'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("45").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe due par les employeurs de main-d’œuvre étrangère (CESEDA, art. L436-10)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4314").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4314', $data['4314'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="3">';
							$h .= s("Taxes sur les embarquements ou débarquements (aériens et maritimes) de passagers en Corse");
						$h .= '</th>';
						$h .= '<td class="font-sm text-center">'.s("Nombre de passagers").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("45A").'</td>';
						$h .= '<td colspan="3">';
							$h .= '<span class="ml-1">'.s("- Taxe sur le transport aérien de passagers – Majoration en Corse (CIBS, art. L422-13 et L422-29)").'</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4324-number', $data['4324-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4324").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4324', $data['4324'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("45B").'</td>';
						$h .= '<td colspan="3">';
							$h .= '<span class="ml-1">'.s("- Taxe sur le transport maritime de passagers dans certains territoires côtiers – Embarquement ou débarquement en Corse (CIBS, art. L423-57 et suivants)").'</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4325-number', $data['4325-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4325").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4325', $data['4325'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("46").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe pour le développement de la formation professionnelle dans les métiers de la réparation de l’automobile, du cycle et du motocycle (CGI, art 1609 sexvicies) au taux de 0,75 %");
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4217-base', $data['4217-base'] ?? 0, ['data-rate' => 0.75] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4217").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4217', $data['4217'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("47").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe sur certaines dépenses de publicité au taux de 1 % (CGI, art 302 bis MA)");
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4213-base', $data['4213-base'] ?? 0, ['data-rate' => 1] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4213").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4213', $data['4213'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("4F").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe sur les excédents de provision des entreprises d’assurance de dommages (CGI, art. 235 ter X)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4238").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4238', $data['4238'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("4K").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Contribution due par les gestionnaires des réseaux publics d’électricité (CGCT, art. L 2224-31 I bis)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4236").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4236', $data['4236'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("4L").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe sur les ordres annulés dans le cadre d’opérations à haute fréquence (CGI, art. 235 ter ZD bis)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4239").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4239', $data['4239'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("4M").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe spéciale due en cas de non-respect de l’engagement de conserver pendant 5 ans les parts de FCPR ou FCPI (article 209-0 A du CGI)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4326").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4326', $data['4326'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("4N").'</td>';
						$h .= '<td colspan="2"><b>';
							$h .= s("Taxe sur les réductions de capital consécutives au rachat par certaines sociétés de leurs propres actions (CGI, art. 235 ter XB), au taux de 8 %");
						$h .= '</b></td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4334-base', $data['4334-base'] ?? 0, ['data-rate' => 8] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4334").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4334', $data['4334'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("60A").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Redevance sanitaire d’abattage (CGI, art. 302 bis N à 302 bis R)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier" id="identifier-4253">'.s("4253").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4253', $data['4253'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("60B").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Redevance sanitaire de découpage (CGI, art. 302 bis S à 302 bis W)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier" id="identifier-4254">'.s("4254").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4254', $data['4254'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("61").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Redevance sanitaire pour le contrôle de certaines substances et de leurs résidus (CGI, art. 302 bis WC)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4247").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4247', $data['4247'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("62").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Redevance sanitaire de première mise sur le marché des produits de la pêche ou de l’aquaculture (CGI, art. 302 bis WA)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4248").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4248', $data['4248'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("63").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Redevance sanitaire de transformation des produits de la pêche ou de l’aquaculture (CGI, art. 302 bis WB)");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre de tonnes").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4249-number', $data['4249-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4249").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4249', $data['4249'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("64").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Redevance pour agrément des établissements du secteur de l’alimentation animale (125 € par établissement) (CGI, art. 302 bis WD à WG)");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre d'établissements").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4250-base', $data['4240-base'] ?? 0, ['data-fixed-price' => 125] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4250").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4250', $data['4250'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
						$h .= s("Redevance phytosanitaire à la circulation intracommunautaire et à l’exportation (Code rural et de la pêche maritime, art. L 251-17-1)");
						$h .= '</th>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("65").'</td>';
						$h .= '<td colspan="4">';
							$h .= '<span class="ml-1">';
								$h .= s("– à la circulation intracommunautaire (PPE)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4273").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4273', $data['4273'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("66").'</td>';
						$h .= '<td colspan="4">';
							$h .= '<span class="ml-1">';
								$h .= s("– à l'exportation");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4274").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4274', $data['4274'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
						$h .= s("Taxe sur les produits phytopharmaceutiques (Code rural et de la pêche maritime, art. L 253-8-2)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au taux de 0,9 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable a").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4321-a', $data['4321-a'] ?? 0, ['data-rate' => 0.9] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au taux de 0,1 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable b").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4321-b', $data['4321-b'] ?? 0, ['data-rate' => 0.1] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("66A").'</td>';
						$h .= '<td colspan="4" class="text-end">';
							$h .= s("Total de la taxe sur les produits phytopharmaceutiques due (a x 0,9% + b x 0,1%)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4321").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4321', $data['4321'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe forfaitaire sur les ventes de métaux précieux, de bijoux, d’objets d’art, de collection et d’antiquité (CGI, art. 150 VI à VM)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("67").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– sur les ventes de métaux précieux au taux de 11 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4268-base', $data['4268-base'] ?? 0, ['data-rate' => 11] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4268").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4268', $data['4268'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("68").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– sur les ventes de bijoux, objets d’art, de collection ou d’antiquité au taux de 6 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4270-base', $data['4270-base'] ?? 0, ['data-rate' => 6] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4270").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4270', $data['4270'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("Contribution pour le remboursement de la dette sociale (CRDS) (CGI, art. 1600-0 I)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("69").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– sur les ventes de métaux précieux au taux de 0,5 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4269-base', $data['4269-base'] ?? 0, ['data-rate' => 0.5] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4269").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4269', $data['4269'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("70").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– sur les ventes de bijoux, objets d’art, de collection ou d’antiquité au taux de 0,5 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4271-base', $data['4271-base'] ?? 0, ['data-rate' => 0.5] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4271").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4271', $data['4271'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number td-vertical-align-top" rowspan="3">'.s("70A").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe annuelle sur les véhicules lourds de transport de marchandises (CIBS, art. L421-94)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="4">';
							$h .= '<table>';
								$h .= '<tr>';
									$h .= '<td></td>';
									$h .= '<td></td>';
									$h .= '<td class="font-sm text-center">'.s("Nb de véhicules").'</td>';
									$h .= '<td class="font-sm text-center">'.s("dont nb de véhicules rail-route").'</td>';
									$h .= '<td class="font-sm text-center" colspan="2">'.s("Montant de la taxe").'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td rowspan="2">'.s("1-Véhicules à moteur isolés").'</td>';
									$h .= '<td>'.s("PTAC inférieur à 27t").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1a-number', $data['4303-1a-number'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1a-sub-number', $data['4303-1a-sub-number'] ?? 0, $attributes).'</td>';
									$h .= '<td>'.s("1a").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1a-tax', $data['4303-1a-tax'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td>'.s("PTAC supérieur ou égal à 27t").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1b-number', $data['4303-1b-number'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1b-sub-number', $data['4303-1b-sub-number'] ?? 0, $attributes).'</td>';
									$h .= '<td>'.s("1b").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-1b-tax', $data['4303-1b-tax'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td rowspan="2">'.s("2-Ensembles articulés constitués d’un tracteur et d’une ou plusieurs semi-remorques").'</td>';
									$h .= '<td>'.s("PTRA inférieur à 39t").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2a-number', $data['4303-2a-number'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2a-sub-number', $data['4303-2a-sub-number'] ?? 0, $attributes).'</td>';
									$h .= '<td>'.s("2a").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2a-tax', $data['4303-2a-tax'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td>'.s("PTRA supérieur ou égal à 39t").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2b-number', $data['4303-2b-number'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2b-sub-number', $data['4303-2b-sub-number'] ?? 0, $attributes).'</td>';
									$h .= '<td>'.s("2b").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-2a-tax', $data['4303-2a-tax'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
								$h .= '<tr>';
									$h .= '<td colspan="2">'.s("3-Remorques de la catégorie O4").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-3-number', $data['4303-3-number'] ?? 0, $attributes).'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-3-sub-number', $data['4303-3-sub-number'] ?? 0, $attributes).'</td>';
									$h .= '<td>'.s("3").'</td>';
									$h .= '<td class="vat-cerfa-input">'.$form->number('4303-3-tax', $data['4303-3-tax'] ?? 0, $attributes).'</td>';
								$h .= '</tr>';
							$h .= '</table>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="4">';
							$h .= s("Total de la taxe annuelle sur les véhicules lourds de transport de marchandises due (1a + 1b + 2a + 2b + 3)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4303").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4303', $data['4303'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number td-vertical-align-top" rowspan="6">'.s("70B").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe annuelle sur les émissions de dioxyde de carbone des véhicules de tourisme (CIBS, a du 1° de l’art. L421-94) Une fiche d’aide au calcul (formulaire n°2857-FC-SD) et sa notice sont disponibles sur impots.gouv.fr");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4323").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4323', $data['4323'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre de véhicules relevant du nouveau dispositif d’immatriculation (depuis le 1er mars 2020)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4323-1', $data['4323-1'] ?? 0, $attributes);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre de véhicules ne relevant pas du nouveau dispositif d’immatriculation: (réception européenne, dont la première mise en circulation est intervenue à compter du 1er juin 2004 et non utilisés par le redevable avant le 1er janvier 2006)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4323-2', $data['4323-2'] ?? 0, $attributes);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre d’autres véhicules soumis à la taxe");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4323-3', $data['4323-3'] ?? 0, $attributes);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre de véhicules exonérés dont la source d’énergie est l’électricité, l’hydrogène ou une combinaison des deux");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4323-4', $data['4323-4'] ?? 0, $attributes);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre des autres véhicules exonérés");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4323-5', $data['4323-5'] ?? 0, $attributes);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number td-vertical-align-top" rowspan="2">'.s("70C").'</td>';
						$h .= '<td colspan="4">';
							$h .= s("Taxe annuelle sur les émissions de polluants atmosphériques des véhicules de tourisme (CIBS, b du 1° de l’art. L421-94). Une fiche d’aide au calcul (formulaire n°2858-FC-SD) et sa notice sont disponibles sur impots.gouv.fr");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4313").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4313', $data['4313'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td colspan="3">';
							$h .= s("Nombre de véhicules exonérés");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-input">';
							$h .= $form->number('4313-number', $data['4313-number'] ?? 0);
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("70D").'</td>';
						$h .= '<td colspan="4"><b>';
							$h .= s("Taxe annuelle incitative relative à l’acquisition de véhicules légers à faibles émissions pour les flottes comprenant au moins 100 véhicules (CIBS, 1°bis de l’art. L421-94). Une fiche d’aide au calcul (formulaire n°2854-FC-SD) et sa notice n°2854-FC-NOT-SD sont disponibles sur impots.gouv.fr");
						$h .= '</b></td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4335").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4335', $data['4335'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("Prélèvement sur les paris hippiques");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("71").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’État (CGI, art. 302 bis ZG) au taux de 20,2 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4256-base', $data['4256-base'] ?? 0, ['data-rate' => 20.2] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4256").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4256', $data['4256'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("72").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit des organismes de sécurité sociale (CSS, art. L137-20) au taux de 6,9 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4259-base', $data['4259-base'] ?? 0, ['data-rate' => 6.9] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4259").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4259', $data['4259'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("73").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– engagés depuis l’étranger sur des courses françaises et regroupés en France (CGI, art. 302 bis ZO) au taux de 12 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4255-base', $data['4255-base'] ?? 0, ['data-rate' => 12] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4255").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4255', $data['4255'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("73A").'</td>';
						$h .= '<td colspan="2"><b>';
							$h .= s("Prélèvement sur les paris hippiques portant sur des épreuves passées (CGI, art. 302 bis ZG, II), au taux de 20,2 %");
						$h .= '</b></td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4336-base', $data['4336-base'] ?? 0, ['data-rate' => 20.2] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4336").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4336', $data['4336'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("Redevance due par les opérateurs agréés de paris hippiques en ligne");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("74").'</td>';
						$h .= '<td colspan="4">';
							$h .= '<span class="ml-1">';
								$h .= s("– Enjeux relatifs aux courses de trot (CGI, art. 1609 tertricies) (cf. notice pour les taux applicables)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4266").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4266', $data['4266'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("75").'</td>';
						$h .= '<td colspan="4">';
							$h .= '<span class="ml-1">';
								$h .= s("– Enjeux relatifs aux courses de galop (CGI, art. 1609 tertricies) (cf. notice pour les taux applicables)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4267").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4267', $data['4267'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Prélèvement sur les paris sportifs en ligne");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("76A").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’État (CGI, art. 302 bis ZH) au taux de 33,7 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4309-base', $data['4309-base'] ?? 0, ['data-rate' => 33.7] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4309").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4309', $data['4309'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("77A").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit des organismes de sécurité sociale (CSS, art. L137-21) (cf. notice pour les taux applicables)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4310-number', $data['4310-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4310").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4310', $data['4310'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("78A").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’agence nationale du sport (ANS) (CGI, art. 1609 tricies) au taux de 10,6 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4311-base', $data['4311-base'] ?? 0, ['data-rate' => 10.6] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4311").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4311', $data['4311'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Prélèvements sur les paris sportifs commercialisés en réseau physique de distribution");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("76B").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’État (CGI, art. 302 bis ZH) au taux de 27,9 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4306-base', $data['4306-base'] ?? 0, ['data-rate' => 27.9] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4306").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4306', $data['4306'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("77B").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit des organismes de sécurité sociale (CSS, art. L137-21) (cf. notice pour les taux applicables)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4307-base', $data['4307-base'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4307").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4307', $data['4307'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("78B").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’agence nationale du sport (ANS) (CGI, art. 1609 tricies) au taux de 6,6 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4308-base', $data['4308-base'] ?? 0, ['data-rate' => 6.6] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4308").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4308', $data['4308'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th colspan="4">';
							$h .= s("Prélèvement sur les jeux de cercle en ligne");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("79").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit de l’État (CGI, art. 302 bis ZI) au taux de 1,8 %");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4258-base', $data['4258-base'] ?? 0, ['data-rate' => 1.8] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4258").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4258', $data['4258'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("80").'</td>';
						$h .= '<td colspan="2">';
							$h .= '<span class="ml-1">';
								$h .= s("– au profit des organismes de sécurité sociale (CSS, art. L137-22) (cf. notice pour les taux applicables)");
							$h .= '</span>';
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4261-base', $data['4261-base'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4261").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4261', $data['4261'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("80A").'</td>';
						$h .= '<td colspan="2"><b>';
							$h .= s("Contribution sur les publicités et actions promotionnelles due par les opérateurs de jeux et paris (CSS, art. L137-27), au taux de 15 %");
						$h .= '</b></td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4337-base', $data['4337-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4337").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4337', $data['4337'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("84").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe sur l’exploration d’hydrocarbures calculée selon le barème fixé à l’article 1590 du CGI et perçue au profit des collectivités territoriales");
						$h .= '</td>';
						$h .= '<td class="font-sm text-center">'.s("Base INSEE de la collectivité").'</td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4291").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4291', $data['4291'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					for($i = 0; $i < 5; $i++) {

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td colspan="2">';
								$h .= '<span class="ml-1">';
									$h .= s("– Droits pour le département ou la collectivité territoriale :");
								$h .= '</span>';
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('4291-code['.$i.']', $data['4291-code['.$i.']'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('4291-amount['.$i.']', $data['4291-amount['.$i.']'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
						$h .= '</tr>';

					}

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("88").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Contribution sur les boissons non alcooliques contenant des sucres ajoutés (CGI, art.1613 ter)");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre d'hectolitres").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4294-number', $data['4294-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4294").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4294', $data['4294'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("89").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Contribution sur les boissons non alcooliques (CGI, art. 1613 quater II 1°), 0,54€/hL");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre d'hectolitres").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4296-number', $data['4296-number'] ?? 0,['data-rate-by-hl' => 0.54] + $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4296").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4296', $data['4296'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("90").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Contribution sur les boissons non alcooliques contenant des édulcorants de synthèse (CGI, art. 1613 quater II 2°)");
						$h .= '</td>';
						$h .= '<td>'.s("Nombre d'hectolitres").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4295-number', $data['4295-number'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4295").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4295', $data['4295'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("92").'</td>';
						$h .= '<td>';
							$h .= s("Contribution sur les eaux minérales naturelles (CGI, art. 1582)");
						$h .= '</td>';
						$h .= '<td class="font-sm text-center">'.s("Code INSEE de la commune").'</td>';
						$h .= '<td class="font-sm text-center">'.s("Nombre d'hectolitres").'</td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4293").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4293', $data['4293'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					for($i = 0; $i < 7; $i++) {

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td>';
								$h .= '<span class="ml-1">';
									$h .= s("– Droits pour la commune :");
								$h .= '</span>';
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('4293-code['.$i.']', $data['4293-code['.$i.']'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('4293-quantity['.$i.']', $data['4293-quantity['.$i.']'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('4293-amount['.$i.']', $data['4293-amount['.$i.']'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
						$h .= '</tr>';

					}

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number" rowspan="2">'.s("93").'</td>';
						$h .= '<td colspan="2" rowspan="2">';
							$h .= s("Taxe sur certains services numériques (TSN) (CIBS, art. L453-45 et L453-82)");
						$h .= '</td>';
						$h .= '<td class="font-sm text-center">'.s("Montant dû au titre de la mise en relation (a)").'</td>';
						$h .= '<td class="font-sm text-center">'.s("Montant dû au titre de la publicité (b)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4301").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4301', $data['4301'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4301-a', $data['4301-a'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4301-b', $data['4301-b'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number">'.s("94").'</td>';
						$h .= '<td colspan="2">';
							$h .= s("Taxe sur les exploitants de plateformes de mise en relation par voie électronique en vue de fournir certaines prestations de transport (CGI, article 300 bis)");
						$h .= '</td>';
						$h .= '<td>'.s("Base imposable").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4322-base', $data['4322-base'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("4322").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('4322', $data['4322'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="4">';
							$h .= s("TOTAL DES LIGNES 36 à 94 (à reporter ligne 55)");
						$h .= '</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('total-taxes-assimilees', $data['total-taxes-assimilees'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="5">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="12">'.s("V - CONSOMMATEURS D'ÉNERGIE : RÉGULARISATION D'ACCISE SUR LES ÉNERGIES").' <span class="vat-not-auto">'.s("Cette section est à remplir manuellement").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th></th>';
						$h .= '<th colspan="7">'.s("Crédit d'accise sur les énergies").'</th>';
						$h .= '<th colspan="3">'.s("Versement d'accise sur les énergies").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Crédit constaté (a)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Crédit imputé sur la TVA (dans la limite de la ligne 33) (b)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Reliquat de crédit à rembourser<br />(a-b)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Taxe due").'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur l'électricité").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('electricite', $data['electricite'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8100").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X1', $data['X1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8110").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y1', $data['Y1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8120").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z1', $data['Z1'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur l’électricité").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('electricite-majoration', $data['electricite-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8200").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M1', $data['M1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M4").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8210").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M4', $data['M4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M7").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8220").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M7', $data['M7'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur les gaz naturels").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('gaz', $data['gaz'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8101").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X2', $data['X2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8111").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y2', $data['Y2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8121").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z2', $data['Z2'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur les gaz naturels").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('gaz-majoration', $data['gaz-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8201").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M2', $data['M2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M5").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8211").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M5', $data['M5'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M8").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8221").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M8', $data['M8'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur les charbons").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('charbon', $data['charbon'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8102").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X3', $data['X3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8112").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y3', $data['Y3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8122").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z3', $data['Z3'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur les charbons").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('charbon-majoration', $data['charbon-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8202").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M3', $data['M3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M6").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8212").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M6', $data['M6'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M9").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8222").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M9', $data['M9'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Accise sur les autres produits énergétiques").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('others', $data['others'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("XA").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8105").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('XA', $data['XA'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("YA").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8115").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('YA', $data['YA'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="3"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Accise sur le gazole non routier agricole").'</b></td>';
						$h .= '<td class="vat-cerfa-number" colspan="7"></td>';
						$h .= '<td class="vat-cerfa-number">'.s("ZB").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8125").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('ZB', $data['ZB'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("TOTAL").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('total', $data['total'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("X4").'<div class="font-sm text-center">'.s("(X1+X2+X3+M1+<br />M2+M3+XA)<br /> à reporter ligne<br /> X5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X4', $data['X4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("Y4").'<div class="font-sm text-center">'.s("Y1+Y2+Y3+M4+<br /> M5+M6+YA)<br /> à reporter ligne<br /> Y5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y4', $data['Y4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("Z4").'<div class="font-sm text-center">'.s("(Z1+Z2+Z3+M7+<br /> M8+M9+ZB)<br /> à reporter ligne<br /> Z5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z4', $data['Z4'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="8">'.s("VI - RÉCAPITULATION").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("49").'</td>';
						$h .= '<td>'.s("Solde excédentaire (report de la ligne 35)").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('49-number', $data['0020'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("X5").'</td>';
						$h .= '<td>'.s("Crédit d’accise sur les énergies des consommateurs imputé sur la TVA (report de la ligne X4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8103").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8103', $data['8103'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("50").'</td>';
						$h .= '<td>'.s("Remboursement de crédit de TVA demandé au cadre VII").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8002").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8002', $data['8002'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("54").'</td>';
						$h .= '<td>'.s("TVA nette due (ligne 33 – ligne X5)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8901").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8901', $data['8901'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("51").'</td>';
						$h .= '<td>'.s("Crédit à reporter (cette somme, ligne 49 - 50, est à reporter ligne 24 de la prochaine déclaration CA 12 / CA 12 E)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8003").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8003', $data['8003'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("55").'</td>';
						$h .= '<td>'.s("Taxes assimilées (total lignes 36 à 94)").'</td>';
						$h .= '<td class="vat-cerfa-identifier"></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('55-number', $data['55-number'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("52").'</td>';
						$h .= '<td>'.s("Crédit imputé sur les prochains acomptes").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8004").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8004', $data['8004'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("Z5").'</td>';
						$h .= '<td>'.s("Total de l’accise sur les énergies dû par les consommateurs (report de la ligne Z4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8123").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8123', $data['8123'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("Y5").'</td>';
						$h .= '<td>'.s("Remboursement de reliquat d’accise sur les énergies demandé par les consommateurs (report de la ligne Y4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8113").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8113', $data['8113'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td class="vat-cerfa-identifier text-center" colspan="3">'.s("ATTENTION ! UNE SITUATION DE TVA CRÉDITRICE (LIGNE 49 SERVIE) NE DISPENSE PAS DU PAIEMENT DES TAXES ASSIMILÉES DÉCLARÉES LIGNE 55 ET DE L’ACCISE SUR LES ÉNERGIES DÉCLARÉE LIGNE Z5.").'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td colspan="4" class="text-center"><small>'.s("Acomptes (Indiquez les années des acomptes déduits I. 30)").'</small></td>';

						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td colspan="3"></td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$years = [];
						for($i = (int)date('Y') - 5; $i < (int)date('Y') + 5; $i++) {
							$years[$i] = $i;
						}

						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("58").'</span></td>';
						$h .= '<td colspan="3">';
							$h .= '<div style="display: flex; align-items: center;">';
								$h .= s("Juillet").'&nbsp;:&nbsp;'.$form->select(
									'deposit[0][year]', $years, $data['deposit'][0]['year'] ?? NULL, $attributes
								);
								$h .= '&nbsp;'.s("Décembre").'&nbsp;:&nbsp;'.$form->select(
									'deposit[1][year]', $years, $data['deposit'][1]['year'] ?? NULL, $attributes
								);
								$h .= '</div>';
							$h .= '</td>';
						$h .= '<td class="vat-cerfa-number">'.s("56").'</td>';
						$h .= '<td>'.s("TOTAL À PAYER (lignes 54 + 55 + Z5)").'<br /><small>'.s("(N’oubliez pas d’effectuer le règlement correspondant)").'</small></td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("9992").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('9992', $data['9992'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-title vat-upper" colspan="8">'.s("BASE DE CALCUL DES ACOMPTES DUS AU TITRE DE L’EXERCICE SUIVANT").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("57").'</td>';
						$h .= '<td colspan="6">'.s("TVA [ligne 16 – (lignes 11 + 12 + 22)").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('57-number', $data['57-number'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="8">'.s("VII - Demande de remboursement").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Crédit remboursable dégagé à la clôture de l’année ou de l’exercice (≥ 150 €, ou, < à 150 € uniquement en cas de cession, décès, entrée dans un groupe TVA) (ligne 29)").'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("a").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('reimburse-a', $data['reimburse-a'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Excédent de versement dégagé (ligne 34) ").'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("b").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('reimburse-b', $data['reimburse-b'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Maximum remboursable (a + b)").'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("c").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('reimburse-c', $data['reimburse-c'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Remboursement demandé").'</td>';
						$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("d").'</span></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('reimburse-d', $data['reimburse-d'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Crédit reportable (c – d)").'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("e").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('reimburse-e', $data['reimburse-e'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

			$h .= '</div>';

			$h .= $this->buttons($eFarm, $form, $eDeclaration);

			$h .= $form->close();


		return $h;

	}

	private function getCerfaCA3(\farm\Farm $eFarm, array $data, int $precision, array $vatParameters): string {

		\Asset::js('vat', 'vat-ca3.js');

		$h = '';

		$eDeclaration = $data['eDeclaration'] ?? new Declaration(['from' => $vatParameters['from'], 'to' => $vatParameters['to'], 'limit' => $vatParameters['limit']]);

		$isDisabled = ($eDeclaration->acceptUpdate() === FALSE);

		$attributes = $isDisabled ? ['disabled' => 'disabled'] : [];

		$form = new \util\FormUi();

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= $form->openAjax(\farm\FarmUi::urlConnected($eFarm).'/vat/saveCerfa', ['id' => 'cerfa-3', 'class' => 'vat-cerfa-form', 'data-precision' => $precision]);

				$h .= $form->hidden('from', $vatParameters['from']);
				$h .= $form->hidden('to', $vatParameters['to']);

				$h .= '<table class="vat-cerfa">';

					$h .= '<thead>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-chapter-title" colspan="8">'.s("A - Montant des opérations réalisées").'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="4">'.s("Opérations taxées (HT)").'</th>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="4">'.s("Opérations non taxées").'</th>';
						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("A1").'</td>';
							$h .= '<td>'.s("Ventes, prestations de services").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0979").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0979', $data['0979'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number">'.s("E1").'</td>';
							$h .= '<td>'.s("Exportations hors UE").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0032").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0032', $data['0032'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("A2").'</td>';
							$h .= '<td>'.s("Autres opérations imposables").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0981").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0981', $data['0981'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number">'.s("E2").'</td>';
							$h .= '<td>'.s("Autres opérations non imposables").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0033").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0033', $data['0033'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("A3").'</span></td>';
							$h .= '<td>'.s("Achats de prestations de services réalisés auprès d’un assujetti non établi en France (article 283-2 du code général des impôts)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0044").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0044', $data['0044'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("E3").'</span></td>';
							$h .= '<td>'.s("Ventes à distance taxables dans un autre État membre au profit des personnes non assujetties – Ventes B to C").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0047").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0047', $data['0047'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("A4").'</span></td>';
							$h .= '<td>'.s("Importations (autres que les produits pétroliers)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0056").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0056', $data['0056'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("E4").'</span></td>';
							$h .= '<td>'.s("Importations (autres que les produits pétroliers)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0052").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0052', $data['0052'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("A5").'</span></td>';
							$h .= '<td>'.s("Sorties de régime fiscal suspensif (autres que les produits pétroliers) et sorties de régime particulier douanier uniquement lorsque des livraisons ont eu lieu en cours de régime").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0051").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0051', $data['0051'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("E5").'</span></td>';
							$h .= '<td>'.s("Sorties de régime fiscal suspensif (autres que les produits pétroliers)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0053").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0053', $data['0053'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("B1").'</span></td>';
							$h .= '<td>'.s("Mises à la consommation de produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0048").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0048', $data['0048'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("E6").'</span></td>';
							$h .= '<td>'.s("Importations placées sous régime fiscal suspensif (autres que les produits pétroliers)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0054").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0054', $data['0054'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("B2").'</span></td>';
							$h .= '<td>'.s("Acquisitions intracommunautaires").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0031").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0031', $data['0031'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F1").'</span></td>';
							$h .= '<td>'.s("Acquisitions intracommunautaires").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0055").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0055', $data['0055'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("B3").'</span></td>';
							$h .= '<td>'.s("Achats d’électricité, de gaz naturel, de chaleur ou de froid imposables en France").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0030").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0030', $data['0030'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number">'.s("F2").'</td>';
							$h .= '<td>'.s("Livraisons intracommunautaires à destination d'une personne assujettie – Ventes B to B").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0034").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0034', $data['0034'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("B4").'</span></td>';
							$h .= '<td>'.s("Achats de biens ou de prestations de services réalisés auprès d’un assujetti non établi en France (article 283-1 du code général des impôts) ").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0040").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0040', $data['0040'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F3").'</span></td>';
							$h .= '<td>'.s("Livraisons d’électricité, de gaz naturel, de chaleur ou de froid non imposables en France").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0029").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0029', $data['0029'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("B5").'</span></td>';
							$h .= '<td>'.s("Régularisations (important : cf. <link>notice {icon}</link>)", ['link' => '<a href="https://www.impots.gouv.fr/sites/default/files/formulaires/3310-a-sd/2026/3310-a-sd_5392.pdf" target="_blank">', 'icon' => \Asset::icon('box-arrow-up-right')]).'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0036").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0036', $data['0036'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F4").'</span></td>';
							$h .= '<td>'.s("Mises à la consommation de produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0049").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0049', $data['0049'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F5").'</span></td>';
							$h .= '<td>'.s("Importations de produits pétroliers placées sous régime fiscal suspensif").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0050").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0050', $data['0050'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

							$h .= '<td class="vat-cerfa-number">'.s("F6").'</td>';
							$h .= '<td>'.s("Achats en franchise").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0037").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0037', $data['0037'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F7").'</span></td>';
							$h .= '<td>'.s("Ventes de biens ou prestations de services réalisées par un assujetti non établi en France (article 283-1 du code général des impôts)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0043").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0043', $data['0043'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F8").'</span></td>';
							$h .= '<td>'.s("Régularisations (important : cf. <link>notice {icon}<link>)", ['link' => '<a href="https://www.impots.gouv.fr/sites/default/files/formulaires/3310-a-sd/2026/3310-a-sd_5392.pdf" target="_blank">', 'icon' => \Asset::icon('box-arrow-up-right')]).'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0039").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0039', $data['0039'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("F9").'</span></td>';
							$h .= '<td>'.s("Opérations internes réalisées entre membres d’un assujetti unique").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0061").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0061', $data['0061'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';
					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="B">';

					$h .= '<thead>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-chapter-title" colspan="8">'.s("B - Décompte de la TVA à payer").'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="2">'.s("TVA brute").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Base HT").'</th>';
							$h .= '<th class="vat-cerfa-title">'.s("Taxe due").'</th>';
						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper">'.s("Opérations réalisées en France métropolitaine").'</th>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("08").'</td>';
							$h .= '<td>'.s("Taux normal 20 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0207").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0207-base', $data['0207-base'] ?? 0, $attributes + ['data-rate' => '20']).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0207', $data['0207'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("09").'</td>';
							$h .= '<td>'.s("Taux normal 5.5 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0105").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0105-base', $data['0105-base'] ?? 0, $attributes + ['data-rate' => '5.5']).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0105', $data['0105'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("9B").'</td>';
							$h .= '<td>'.s("Taux normal 10 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0151").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0151-base', $data['0151-base'] ?? 0, $attributes + ['data-rate' => '10']).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0151', $data['0151'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper">'.s("Opérations réalisées dans les DOM").'</th>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("10").'</span></td>';
							$h .= '<td>'.s("Taux normal 8,5 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0201").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0201-base', $data['0201-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0201', $data['0201'] ?? 0, $attributes + ['data-rate' => '8.5']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("11").'</span></td>';
							$h .= '<td>'.s("Taux réduit 2,1 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0100").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0100-base', $data['0100-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0100', $data['0100'] ?? 0, $attributes + ['data-rate' => '2.1']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper">'.s("Opérations imposables à un taux particulier").'</th>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T1").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées dans les DOM et imposables au taux de 1,75 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1120").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1120-base', $data['1120-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1120', $data['1120'] ?? 0, $attributes + ['data-rate' => '1.75']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T2").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées dans les DOM et imposables au taux de 1,05 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1110").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1110-base', $data['1110-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1110', $data['1110'] ?? 0, $attributes + ['data-rate' => '1.05']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("TC").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées en Corse et imposables au taux de 13 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1090").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1090-base', $data['1090-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1090', $data['1090'] ?? 0, $attributes + ['data-rate' => '13']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T3").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées en Corse et imposables au taux de 13 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1081").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1081-base', $data['1081-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1081', $data['1081'] ?? 0, $attributes + ['data-rate' => '13']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T4").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées en Corse et imposables au taux de 2,1 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1050").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1050-base', $data['1050-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1050', $data['1050'] ?? 0, $attributes + ['data-rate' => '2.1']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T5").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées en Corse et imposables au taux de 0,9 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1040").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1040-base', $data['1040-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1040', $data['1040'] ?? 0, $attributes + ['data-rate' => '0.9']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T6").'</span></td>';
							$h .= '<td>'.s("Opérations réalisées en Corse et imposables au taux de 2,1 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("1010").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1010-base', $data['1010-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('1010', $data['1010'] ?? 0, $attributes + ['data-rate' => '2.1']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("T7").'</span></td>';
							$h .= '<td>'.s("Retenue de TVA sur droits d’auteur").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0990").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0990-base', $data['0990-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0990', $data['0990'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("13").'</span></td>';
							$h .= '<td>'.s("Anciens taux").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0900").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0900-base', $data['0900-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0900', $data['0900'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper">'.s("Produits pétroliers").'</th>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("P1").'</span></td>';
							$h .= '<td>'.s("Taux normal 20 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0208").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0208-base', $data['0208-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0208', $data['0208'] ?? 0, $attributes + ['data-rate' => '20']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("P2").'</span></td>';
							$h .= '<td>'.s("Taux réduit 13 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0152").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0152-base', $data['0152-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0152', $data['0152'] ?? 0, $attributes + ['data-rate' => '13']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper">'.s("Importations").'</th>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';
							$h .= '<td class="vat-cerfa-input"></td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I1").'</span></td>';
							$h .= '<td>'.s("Taux normal 20 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0210").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0210-base', $data['0210-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0210', $data['0210'] ?? 0, $attributes + ['data-rate' => '20']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I2").'</span></td>';
							$h .= '<td>'.s("Taux réduit 10 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0211").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0211-base', $data['0211-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0211', $data['0211'] ?? 0, $attributes + ['data-rate' => '10']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I3").'</span></td>';
							$h .= '<td>'.s("Taux réduit 8,5 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0212").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0212-base', $data['0212-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0212', $data['0212'] ?? 0, $attributes + ['data-rate' => '8.5']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I4").'</span></td>';
							$h .= '<td>'.s("Taux réduit 5,5 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0213").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0213-base', $data['0213-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0213', $data['0213'] ?? 0, $attributes + ['data-rate' => '5.5']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I5").'</span></td>';
							$h .= '<td>'.s("Taux réduit 2,1 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0214").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0214-base', $data['0214-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0214', $data['0214'] ?? 0, $attributes + ['data-rate' => '2.1']).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("I6").'</span></td>';
							$h .= '<td>'.s("Taux réduit 1,05 %").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0215").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0215-base', $data['0215-base'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0215', $data['0215'] ?? 0, $attributes + ['data-rate' => '1.05']).'</td>';

						$h .= '</tr>';

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tbody>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("15").'</span></td>';
							$h .= '<td colspan="3">';
								$h .= '<div style="display: flex; align-items: center">';
									$h .= s("TVA antérieurement déduite à reverser");
									$h .= '<div>';
										$h .= '<div style="display: flex;">';
											$h .= s("dont TVA sur les produits pétroliers");
											$h .= $form->number('0600-petrol', $data['0600-petrol'] ?? 0, $attributes);
										$h .= '</div>';
										$h .= '<div style="display: flex;">';
											$h .= s("dont TVA sur les produits importés hors produits pétroliers");
											$h .= $form->number('0600-not-petrol', $data['0600-not-petrol'] ?? 0, $attributes);
										$h .= '</div>';
									$h .= '</div>';
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0600").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0600', $data['0600'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("5B").'</span></td>';
							$h .= '<td colspan="3">';
									$h .= s("Sommes à ajouter, y compris acompte congés");
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0602").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0602', $data['0602'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td style="min-width: 40%;"></td>';
							$h .= '<td class="vat-cerfa-number">'.s("16").'</td>';
							$h .= '<td>'.s("Total de la TVA brute due (lignes 08 à 5B)").'</td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('16-number', $data['16-number'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-number">'.s("17").'</td>';
							$h .= '<td>'.s("Dont TVA sur acquisitions intracommunautaires").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0035").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0035', $data['0035'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<td></td>';
							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("18").'</span></td>';
							$h .= '<td>'.s("Dont TVA sur opérations à destination de Monaco").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0038").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0038', $data['0038'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';


						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"></td>';
							$h .= '<th class="vat-cerfa-title vat-upper" colspan="5">'.s("TVA Déductible").'</th>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("19").'</td>';
							$h .= '<td colspan="3">'.s("Biens constituant des immobilisations").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0703").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0703', $data['0703'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("20").'</td>';
							$h .= '<td colspan="3">'.s("Autres biens et services ").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0702").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0702', $data['0702'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("21").'</span></td>';
							$h .= '<td colspan="3">';
								$h .= s("Autre TVA à déduire");
								$h .= '<ul>';
									$h .= '<li style="display: flex;">'.s("(dont régularisation de TVA sur les produits pétroliers : {input})", ['input' => $form->number('0059-petrol', $data['0059-petrol'] ?? 0, $attributes)]).'</li>';
									$h .= '<li style="display: flex;">'.s("(dont régularisation de TVA sur les produits importés hors produits pétroliers : {input})", ['input' => $form->number('0059-not-petrol', $data['0059-not-petrol'] ?? 0, $attributes)]).'</li>';
									$h .= '<li style="display: flex;">'.s("(dont régularisation de TVA collectée sur autres produits ou PS [cf. notice] ou de TVA déductible : {input})", ['input' => $form->number('0059-other', $data['0059-other'] ?? 0, $attributes)]).'</li>';
								$h .= '</ul>';
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0059").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0059', $data['0059'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number">'.s("22").'</td>';
							$h .= '<td colspan="3">';
								$h .= s("Report du crédit apparaissant ligne 27 de la précédente déclaration");
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("8001").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('8001', $data['8001'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("2C").'</span></td>';
							$h .= '<td colspan="3">';
								$h .= s("Sommes à imputer, y compris acompte congés");
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0603").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0603', $data['0603'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number" rowspan="3"><span class="vat-not-auto">'.s("22A").'</span></td>';
							$h .= '<td rowspan="3">';
								$h .= s("Indiquer le coefficient de taxation unique applicable pour la période s'il est différent de 100 %");
								$h .= $form->number('22a', $data['22a'] ?? 0, $attributes);
							$h .= '</td>';
							$h .= '<td class="vat-cerfa-number">'.s("23").'</td>';
							$h .= '<td>'.s("Total TVA déductible (ligne 19 à 2C)").'</td>';
							$h .= '<td class="vat-cerfa-identifier"></td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('23-number', $data['23-number'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("24").'</span></td>';
							$h .= '<td>'.s("Dont TVA déductible sur importations hors produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0710").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0710', $data['0710'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="vat-cerfa-number"><span class="vat-not-auto">'.s("2E").'</span></td>';
							$h .= '<td>'.s("Dont TVA déductible sur les produits pétroliers").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0711").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0711', $data['0711'] ?? 0, $attributes).'</td>';

						$h .= '</tr>';

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tbody>';

						$h .= '<tr>';
							$h .= '<th class="vat-cerfa-chapter-title"></th>';
							$h .= '<th class="vat-cerfa-chapter-title" colspan="7">'.s("TVA due ou crédit de TVA").'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-number">'.s("25").'</td>';
							$h .= '<td>'.s("Crédit de TVA (ligne 23 – ligne 16)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("0705").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('0705', $data['0705'] ?? 0, $attributes).'</td>';

							$h .= '<td class="vat-cerfa-number">'.s("TD").'</td>';
							$h .= '<td>'.s("TVA due (ligne 16 – ligne 23)").'</td>';
							$h .= '<td class="vat-cerfa-identifier">'.s("8900").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('8900', $data['8900'] ?? 0, $attributes).'</td>';

							$h .= '</tr>';

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa" data-chapter="5">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title"></th>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="11">';
							$h .= s(" CONSOMMATEURS D'ÉNERGIE : RÉGULARISATION D'ACCISE SUR LES ÉNERGIES");
							$h .= ' <span class="vat-not-auto">'.s("Cette section est à remplir manuellement");
						$h .= '</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<th></th>';
						$h .= '<th colspan="7">'.s("Crédit d'accise sur les énergies").'</th>';
						$h .= '<th colspan="3">'.s("Versement d'accise sur les énergies").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Crédit constaté (a)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Crédit imputé sur la TVA (dans la limite de la ligne 33) (b)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Reliquat de crédit à rembourser<br />(a-b)").'</td>';
						$h .= '<td class="font-sm text-center" colspan="3">'.s("Taxe due").'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur l'électricité").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('electricite', $data['electricite'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8100").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X1', $data['X1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8110").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y1', $data['Y1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8120").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z1', $data['Z1'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur l’électricité").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('electricite-majoration', $data['electricite-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M1").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8200").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M1', $data['M1'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M4").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8210").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M4', $data['M4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M7").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8220").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M7', $data['M7'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur les gaz naturels").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('gaz', $data['gaz'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8101").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X2', $data['X2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8111").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y2', $data['Y2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8121").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z2', $data['Z2'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur les gaz naturels").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('gaz-majoration', $data['gaz-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M2").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8201").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M2', $data['M2'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M5").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8211").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M5', $data['M5'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M8").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8221").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M8', $data['M8'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td>'.s("Accise sur les charbons").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('charbon', $data['charbon'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8102").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X3', $data['X3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Y3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8112").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y3', $data['Y3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("Z3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8122").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z3', $data['Z3'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Majoration de l’accise sur les charbons").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('charbon-majoration', $data['charbon-majoration'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M3").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8202").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M3', $data['M3'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M6").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8212").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M6', $data['M6'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("M9").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8222").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('M9', $data['M9'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Accise sur les autres produits énergétiques").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('others', $data['others'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("XA").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8105").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('XA', $data['XA'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("YA").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8115").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('YA', $data['YA'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="3"></td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("Accise sur le gazole non routier agricole").'</b></td>';
						$h .= '<td class="vat-cerfa-number" colspan="7"></td>';
						$h .= '<td class="vat-cerfa-number">'.s("ZB").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8125").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('ZB', $data['ZB'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<td class="vat-cerfa-number"></td>';
						$h .= '<td><b>'.s("TOTAL").'</b></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('total', $data['total'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("X4").'<div class="font-sm text-center">'.s("(X1+X2+X3+M1+<br />M2+M3+XA)<br /> à reporter ligne<br /> X5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('X4', $data['X4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("Y4").'<div class="font-sm text-center">'.s("Y1+Y2+Y3+M4+<br /> M5+M6+YA)<br /> à reporter ligne<br /> Y5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Y4', $data['Y4'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number" colspan="2">'.s("Z4").'<div class="font-sm text-center">'.s("(Z1+Z2+Z3+M7+<br /> M8+M9+ZB)<br /> à reporter ligne<br /> Z5").'</div></td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('Z4', $data['Z4'] ?? 0, $attributes).'</td>';
					$h .= '</tr>';

				$h .= '</table>';

				$h .= '<table class="vat-cerfa">';

					$h .= '<tr>';
						$h .= '<th class="vat-cerfa-chapter-title"></th>';
						$h .= '<th class="vat-cerfa-chapter-title" colspan="7">';
							$h .= s("Détermination du montant à payer et/ou des crédits de TVA et/ou des crédits d'accise sur les énergies");
							$h .= ' <span class="vat-not-auto">'.s("Cette section est à remplir manuellement");
						$h .= '</th>';
					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("26").'</td>';
						$h .= '<td>'.s("Remboursement de crédit de TVA demandé sur formulaire n°3519").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8002").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8002', $data['8002'] ?? 0, $attributes).'</td>';
						$h .= '<td class="vat-cerfa-number">'.s("X5").'</td>';
						$h .= '<td>'.s("Crédit d’accise sur les énergies des consommateurs imputé sur la TVA (report de la ligne X4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8103").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8103', $data['8103'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("AA").'</td>';
						$h .= '<td>'.s("Crédit de TVA transféré à la société tête de groupe sur la déclaration récapitulative 3310 CA3G").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8005").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8005', $data['8005'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("28").'</td>';
						$h .= '<td>'.s("TVA nette due (ligne TD – ligne X5)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8901").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8901', $data['8901'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("27").'</td>';
						$h .= '<td>'.s("Crédit de TVA à reporter (ligne 25 – ligne 26) (Cette somme est à reporter ligne 22 de la prochaine déclaration)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8003").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8003', $data['8003'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("29").'</td>';
						$h .= '<td>'.s("Taxes assimilées calculées sur l’annexe n° 3310 A").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("9979").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('9979', $data['9979'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("Y5").'</td>';
						$h .= '<td>'.s("Remboursement de reliquat d’accise sur les énergies demandé par les consommateurs (report de la ligne Y4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8113").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8113', $data['8113'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("Z5").'</td>';
						$h .= '<td>'.s("Total de l’accise sur les énergies dû par les consommateurs (report de la ligne Z4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8123").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8123', $data['8123'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td class="vat-cerfa-number">'.s("Y6").'</td>';
						$h .= '<td>'.s("Remboursement de reliquat d’accise sur les énergies demandé par les consommateurs, transféré à la société tête de groupe sur la déclaration récapitulative 3310-CA3G (report de la ligne Y4)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("8114").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('8114', $data['8114'] ?? 0, $attributes).'</td>';

						$h .= '<td class="vat-cerfa-number">'.s("AB").'</td>';
						$h .= '<td>'.s("Total à payer acquitté par la société tête de groupe sur la déclaration récapitulative 3310- CA3G (lignes 28 + 29 + Z5)").'</td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("9991").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('9991', $data['9991'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td colspan="4"></td>';
						$h .= '<td class="vat-cerfa-number">'.s("32").'</td>';
						$h .= '<td>'.s("Total à payer (lignes 28 + 29 + Z5 – AB)").'<br /><small>'.s("(N’oubliez pas d’effectuer le règlement correspondant)").'</small></td>';
						$h .= '<td class="vat-cerfa-identifier">'.s("9992").'</td>';
						$h .= '<td class="vat-cerfa-input">'.$form->number('9992', $data['9992'] ?? 0, $attributes).'</td>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<td colspan="8" class="text-center">'.s("Attention ! Une situation de TVA créditrice (ligne 25 servie) ne dispense pas du paiement des taxes assimilées déclarées ligne 29 et de l’accise sur les	énergies déclarée ligne Z5.").'</td>';

					$h .= '</tr>';

				$h .= '</table>';

				$h .= $this->getCA3Annexe($form, $data, $vatParameters, $isDisabled === FALSE);

				$h .= $this->buttons($eFarm, $form, $eDeclaration);

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	private function buttons(\farm\Farm $eFarm, \util\FormUi $form, Declaration $eDeclaration): string {

		if($eDeclaration->acceptUpdate() === FALSE and $eDeclaration->acceptReset() === FALSE and $eDeclaration->acceptDeclare() === FALSE) {
			return '';
		}

		$h = '<div class="vat-form-buttons-container">';

			if($eDeclaration->acceptReset()) {

				$h .= $form->button(s("Réinitialiser"), ['class' => 'btn btn-outline-danger mr-2', 'data-ajax' => \farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doReset', 'post-id' => $eDeclaration['id'], 'data-confirm' => s("En réinitialisant, votre déclaration reviendra à l'état intial, c'est-à-dire celui que {siteName} vous a proposé à partir des écritures comptables. Vous perdrez toutes vos modifications et les ajouts que vous avez faits. Voulez-vous continuer ?")]);

			}

			if($eDeclaration->acceptUpdate()) {

			$h .= $form->submit(s("Sauvegarder"));

			}

			if($eDeclaration->acceptDeclare()) {

				$h .= $form->button(s("J'ai déclaré sur le site des impôts"), ['class' => 'btn btn-secondary ml-2', 'data-ajax' => \farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doUpdateDeclareStatus', 'post-id' => $eDeclaration['id']]);

			}

			if($eDeclaration->exists() and $eDeclaration['declaredAt'] !== NULL) {
				$h .= '<div class="ml-1 util-annotation">'.s("Enregistrée déclarée le {date}<br />par {user}", ['date' => \util\DateUi::numeric($eDeclaration['declaredAt'], \util\DateUi::DATE), 'user' => $eDeclaration['declaredBy']->getName()]).'</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getCA3Annexe(\util\FormUi $form, array $data, array $vatParameters, bool $isUpdatable): string {

		$year = (int)mb_substr($vatParameters['from'], 0, 4);
		$lastYear = (int)mb_substr($vatParameters['from'], 0, 4) - 1;
		$attributes = $isUpdatable ? [] : ['disabled' => 'disabled'];

		$h = '<table class="vat-cerfa" data-chapter="annexe">';

			$h .= '<tr>';
				$h .= '<th class="vat-cerfa-chapter-title"></th>';
				$h .= '<th class="vat-cerfa-chapter-title" colspan="7">';
					$h .= s("TAXES ASSIMILÉES");
				$h .= '</th>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th class="vat-cerfa-chapter-title"></th>';
				$h .= '<th class="vat-cerfa-chapter-title" colspan="7">';
					$h .= s("DÉCOMPTE DES TAXES ASSIMILÉES");
					$h .= ' <span class="vat-not-auto">'.s("Cette section est à remplir manuellement");
				$h .= '</th>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("47").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur certaines dépenses de publicité au taux de 1 % (CGI, art 302 bis MA)");
				$h .= '</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4213-base', $data['4213-base'] ?? 0, ['data-rate' => 1] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4213").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4213', $data['4213'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("48").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur la cession de droits d’exploitation audiovisuelle des manifestations sportives au taux de 5 % (CIBS, art. L455-28)");
				$h .= '</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4215-base', $data['4215-base'] ?? 0, ['data-rate' => 5] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4215").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4215', $data['4215'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("49").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur les excédents de provision des entreprises d’assurances de dommages (CGI, art. 235 ter X)");
				$h .= '</td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4238").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4238', $data['4238'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';


			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("50").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur le chiffre d’affaires des exploitants agricoles (CGI, art. 302 bis MB) (cumul de la partie variable et de la partie forfaitaire) ");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier" id="identifier-4220">'.s("4220").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4220', $data['4220'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("51").'</td>';
				$h .= '<td colspan="2"><b>';
					$h .= s("Taxe sur les réductions de capital consécutives au rachat par certaines sociétés de leurs propres actions (CGI, art. 235 ter XB), au taux de 8 %");
				$h .= '</b></td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4334-base', $data['4334-base'] ?? 0, ['data-rate' => 8] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4334").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4334', $data['4334'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("55").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur la distance parcourue sur le réseau autoroutier concédé (CIBS, art. L421-175)");
				$h .= '</td>';
				$h .= '<td>'.s("Nombre de kilomètres ").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4207-base', $data['4207-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4207").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4207', $data['4207'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4"><b>';
					$h .= s("Taxe sur l’exploitation des infrastructures de transport de longue distance (CIBS, art. L425-1 et suivants)");
				$h .= '</b></td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("56").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td rowspan="2">'.s("Déclaration de la taxe due au titre de {value}", $lastYear).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Base imposable").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Montant de la taxe due (a x 4,6 %)").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Acomptes payés en {value}", $year).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Excédent d'acompte (si b-c < 0) (à reporter colonne b de la ligne 56A)").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Solde restant dû (si b-c > 0) (à reporter colonne c de la ligne 56A)").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="td-min-content">'.s("a").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56-a', $data['56-a'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("b").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56-b', $data['56-b'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("c").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56-c', $data['56-c'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("d").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56-d', $data['56-d'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("e").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56-e', $data['56-e'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("56A").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td rowspan="2">'.s("Acompte {value}", $year).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Montant de l'acompte").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Excédent d’acompte {value} (colonne d de la ligne 56)", $lastYear).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Solde restant dû au titre de la taxe {value} (colonne e de la ligne 56)", $lastYear).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Solde restant dû au titre de la taxe {value} (colonne e de la ligne 56)").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="td-min-content">'.s("a").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56a-a', $data['56a-a'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("b").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56a-b', $data['56a-b'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("c").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56a-c', $data['56a-c'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("d").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56a-d', $data['56a-d'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4328").'</td>';
				$h .= '<td class="vat-cerfa-input">';
					$h .= s("Solde restant dû (a - b + c) si > 0");
					$h .= $form->number('4328', $data['4328'] ?? 0, $attributes);
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("56B").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td rowspan="2">'.s("Solde 2025 (uniquement pour les cessations d'activité ayant lieu en {value})", $year).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Base imposable").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Montant de la taxe due (a x 4,6 %)").'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Acomptes payés en {value}", $year).'</td>';
							$h .= '<td colspan="2" class="font-sm text-center">'.s("Excédent d'acompte (si b-c < 0)").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="td-min-content">'.s("a").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56b-a', $data['56b-a'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("b").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56b-b', $data['56b-b'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("c").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56b-c', $data['56b-c'] ?? 0, $attributes).'</td>';
							$h .= '<td class="td-min-content">'.s("d").'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('56b-d', $data['56b-d'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4329").'</td>';
				$h .= '<td class="vat-cerfa-input">';
					$h .= s("Solde restant dû (si b-c > 0)");
					$h .= $form->number('4329', $data['4329'] ?? 0, $attributes);
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur les vidéogrammes (CIBS, art. L452-28)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("58A").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- au taux de 1,8025 % ").'</span>';
				$h .= '</td>';
				$h .= '<td rowspan="2" class="pl-1">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58a-base', $data['58a-base'] ?? 0, ['data-rate' => 1.8025] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4330").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58a', $data['58a'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("58B").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- au taux de 15 % ").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58b-base', $data['58b-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4331").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58b', $data['58b'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur la mise à disposition de phonogrammes musicaux et de vidéomusiques (CGI, art. 1609 sexdecies C)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("58C").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- à titre onéreux").'</span>';
				$h .= '</td>';
				$h .= '<td rowspan="2" class="pl-1">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58c-base', $data['58c-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4332").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4332', $data['4332'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("58D").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- à titre gratuit").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('58d-base', $data['58d-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4333").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4333', $data['4333'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur la publicité diffusée au moyen de services de contenus audiovisuels à la demande (CIBS, art. L454-16)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("60A").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- au taux de 5,15 %").'</span>';
				$h .= '</td>';
				$h .= '<td rowspan="2" class="pl-1">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('60a-base', $data['60a-base'] ?? 0, ['data-rate' => 5.15] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4298").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('60a', $data['60a'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("60B").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- au taux de 15 %").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('60b-base', $data['60b-base'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4299").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('60b', $data['60b'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("61").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe due par les employeurs de main-d’œuvre étrangère (CESEDA, art. L436-10) ");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4314").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4314', $data['4314'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4"><b>';
					$h .= s("Contribution sur la rente infra-marginale de la production d’électricité");
				$h .= '</b></td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("Solde de la <b>période 4 du 01/01/{value} au 31/12/{value} (P4)</b>", $lastYear);
				$h .= '</td>';
				$h .= '<td colspan="3" class="text-center">';
					$h .= s("Utilisation d’un forfait");
					$h .= '<div class="flex-justify-space-between">';
						$h .= $form->radio('p4-yes', 'yes', s("oui"));
						$h .= $form->radio('p4-no', 'no', s("non"));
					$h .= '</div>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("Technologie utilisée pour la production de l’électricité");
				$h .= '</td>';
				$h .= '<td class="text-center">';
					$h .= s("Quantité d'électricité produite sur la période 4 (en MWh)");
				$h .= '</td>';
				$h .= '<td class="text-center">';
					$h .= s("Report d'un montant négatif d'une période antérieure");
				$h .= '</td>';
				$h .= '<td class="text-center">';
					$h .= s("Marge forfaitaire sur la période 4 minorée du report le cas échéant");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"><span style="visibility: hidden;">0000</span></td>'; // pour prendre l'espace tout de même
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("a.").' ';
					$h .= s("Nucléaire").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4a-quantity', $data['p4a-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4a-report', $data['p4a-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4a-margin', $data['p4a-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("b.").' ';
					$h .= s("Fioul et autres produits pétroliers").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4b-quantity', $data['p4b-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4b-report', $data['p4b-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4b-margin', $data['p4b-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("C.").' ';
					$h .= s("CCG gaz").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4c-quantity', $data['p4c-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4c-report', $data['p4c-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4c-margin', $data['p4c-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("d.").' ';
					$h .= s("Cogénération gaz").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4d-quantity', $data['p4d-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4d-report', $data['p4d-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4d-margin', $data['p4d-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("e.").' ';
					$h .= s("TAC gaz").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4e-quantity', $data['p4e-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4e-report', $data['p4e-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4e-margin', $data['p4e-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("f.").' ';
					$h .= s("Éolien terrestre").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4f-quantity', $data['p4f-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4f-report', $data['p4f-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4f-margin', $data['p4f-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("g.").' ';
					$h .= s("Éolien maritime").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4g-quantity', $data['p4g-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4g-report', $data['p4g-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4g-margin', $data['p4g-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("h.").' ';
					$h .= s("Solaire").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4h-quantity', $data['p4h-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4h-report', $data['p4h-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4h-margin', $data['p4h-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("i.").' ';
					$h .= s("Biogaz").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4i-quantity', $data['p4i-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4i-report', $data['p4i-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4i-margin', $data['p4i-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("j.").' ';
					$h .= s("Cogénération biogaz").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4j-quantity', $data['p4j-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4j-report', $data['p4j-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4j-margin', $data['p4j-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("k.").' ';
					$h .= s("Cogénération biomasse").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4k-quantity', $data['p4k-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4k-report', $data['p4k-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4k-margin', $data['p4k-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("l.").' ';
					$h .= s("Traitement thermique des déchets (y compris en cas de cogénération)").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4l-quantity', $data['p4l-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4l-report', $data['p4l-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4l-margin', $data['p4l-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("m.").' ';
					$h .= s("Hydraulique fil de l’eau").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4m-quantity', $data['p4m-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4m-report', $data['p4m-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4m-margin', $data['p4m-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("n.").' ';
					$h .= s("Gaz de mine").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4n-quantity', $data['p4n-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4n-report', $data['p4n-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4n-margin', $data['p4n-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("o.").' ';
					$h .= s("Géothermie").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4o-quantity', $data['p4o-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4o-report', $data['p4o-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4o-margin', $data['p4o-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("p.").' ';
					$h .= s("Centrales houlomotrices ou hydrocinétiques").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4p-quantity', $data['p4p-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4p-report', $data['p4p-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4p-margin', $data['p4p-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("q.").' ';
					$h .= s("Technologies diverses valorisées conjointement").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4q-quantity', $data['p4q-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4q-report', $data['p4q-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4q-margin', $data['p4q-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("r.").' ';
					$h .= s("Sommes déductibles au titre des redevances hydrauliques (dans la limite de 90 % du montant positif en ligne M)").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4r-quantity', $data['p4r-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4r-report', $data['p4r-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4r-margin', $data['p4r-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("s.").' ';
					$h .= s("Sommes déductibles au titre du service public de traitement des déchets (dans la limite de 90 % du montant des déchets en ligne L)").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4s-quantity', $data['p4s-quantity'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4s-report', $data['p4s-report'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4s-margin', $data['p4s-margin'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3" class="text-end"><b>';
					$h .= s("Total pour la période 4 (total des marges ≥ 0 uniquement)");
				$h .= '</b></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4-total', $data['p4-total'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3" class="text-end"><b>';
					$h .= s("Acompte {value}", $lastYear);
				$h .= '</b></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4-deposit', $data['p4-deposit'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3" class="text-end"><b>';
					$h .= s("Régularisation au titre d’une période antérieure");
				$h .= '</b></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4-regul', $data['p4-regul'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="2" class="text-end">'.s("<b>Montant de la contribution sur la rente infra-marginale de la production d’électricité due
		au titre de P4 </b>(Total période 4 – Acompte {value} +/- Régularisation au titre d’une période antérieure)", $lastYear).'</td>';
				$h .= '<td class="text-end">'.s("A").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('p4-contribution', $data['contribution'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("62").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Total de la contribution sur la rente infra-marginale de la production d’électricité due (report de A si > 0 ) ");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4315").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4315', $data['4315'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("64").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur les actes des huissiers de justice (CGI, art. 302 bis Y) (14,89 € par acte accompli à compter du 1er janvier 2017)");
				$h .= '</td>';
				$h .= '<td>'.s("Nombre d’actes").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4206-base', $data['4206-base'] ?? 0, ['data-fixed-price' => 14.89] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4206").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4206', $data['4206'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= '<b>'.s("Taxe sur les services de communication électronique (CIBS, art. L453-1 et suivants), au taux de 1,3 %").'</b>';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td>'.s("Somme des contreparties des services taxables, au sens de l’art. L453-9 du CIBS (a)").'</td>';
							$h .= '<td>'.s("Dotations aux amortissements déductibles (b)").'</td>';
							$h .= '<td>'.s("Base imposable (c) (fraction de (a-b) > 5 millions d’euros)").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('65-a', $data['65-a'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('65-b', $data['65-b'] ?? 0, $attributes).'</td>';
							$h .= '<td class="vat-cerfa-input">'.$form->number('65-c', $data['65-c'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("65").'</td>';
				$h .= '<td colspan="4" class="text-end">';
					$h .= '<b>'.s("Montant de la taxe due (c x 1,3 %) ").'</b>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4226").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4226', $data['4226'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">';
					$h .= s("Taxes sur les embarquements ou débarquements (<b>aériens et maritimes</b>) de passagers en Corse");
				$h .= '</td>';
				$h .= '<td>'.s("Nombre de passagers").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("66").'</td>';
				$h .= '<td colspan="3">';
					$h .= '<span class="ml-2">'.s("- Taxe sur le transport aérien de passagers – Majoration en Corse (CIBS, art. L422-13 et L422-29)").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4324-count', $data['4324-count'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4324").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4324', $data['4324'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("67").'</td>';
				$h .= '<td colspan="3">';
					$h .= '<span class="ml-2">'.s("- Taxe sur le transport maritime de passagers dans certains territoires côtiers – Embarquement ou débarquement en Corse (CIBS, art. L423-57 et suivants)").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4325-count', $data['4325-count'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4325").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4325', $data['4325'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("68").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe pour le développement de la formation professionnelle dans les métiers de la réparation de l’automobile, du cycle et du motocycle (CGI, art. 1609 sexvicies) au taux de 0,75 %");
				$h .= '</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4217-base', $data['4217-base'] ?? 0, ['data-rate' => 0.75] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4217").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4217', $data['4217'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("69").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur les ordres annulés dans le cadre d’opérations à haute fréquence (CGI, art. 235 ter ZD bis)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4239").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4239', $data['4239'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("70").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe spéciale due en cas de non-respect de l’engagement de conserver pendant 5 ans les parts de FCPR ou FCPI (article 209-0 A du CGI)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4326").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4326', $data['4326'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("76").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Contribution due par les gestionnaires des réseaux publics d’électricité (CGCT, art. L 2224-31 I bis)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4236").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4236', $data['4236'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("80").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Imposition forfaitaire sur les pylônes (CGI, art. 1519 A)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4243").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4243', $data['4243'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe sur les éoliennes maritimes sur le domaine public maritime (DPM) (CGI, art. 1519 B)");
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td class="text-center">'.s("Nom du Parc éolien en DPM").'</td>';
							$h .= '<td class="text-center">'.s("Montant dû").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.$form->text('81[0][label]', $data['81'][0]['label'] ?? '', ['style' => 'width: 100%']).'</td>';
							$h .= '<td class="text-center">'.$form->number('81[0][value]', $data['81'][0]['value'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.$form->text('81[1][label]', $data['81'][1]['label'] ?? '', ['style' => 'width: 100%']).'</td>';
							$h .= '<td class="text-center">'.$form->number('81[1][value]', $data['81'][1]['value'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.$form->text('81[2][label]', $data['81'][2]['label'] ?? '', ['style' => 'width: 100%']).'</td>';
							$h .= '<td class="text-center">'.$form->number('81[2][value]', $data['81'][2]['value'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.$form->text('81[3][label]', $data['81'][3]['label'] ?? '', ['style' => 'width: 100%']).'</td>';
							$h .= '<td class="text-center">'.$form->number('81[3][value]', $data['81'][3]['value'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("81").'</td>';
				$h .= '<td colspan="4" class="text-end">';
					$h .= s("Total de la taxe sur les éoliennes maritimes en DPM");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4244").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4244', $data['4244'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("83").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe pour le financement du fonds de soutien aux collectivités territoriales ayant contracté des produits structurés (CGI, art. 235 ter ZE bis) au taux de 0,0642 % jusqu’en 2025");
				$h .= '</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4252-base', $data['4252-base'] ?? 0, ['data-rate' => 0.0642] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4252").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4252', $data['4252'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("84A").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Redevance sanitaire d’abattage (CGI, art. 302 bis N à 302 bis R)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier" id="identifier-4253">'.s("4253").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4253', $data['4253'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("84B").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Redevance sanitaire de découpage (CGI, art. 302 bis S à 302 bis W)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier" id="identifier-4254">'.s("4254").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4254', $data['4254'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("85").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Redevance sanitaire pour le contrôle de certaines substances et de leurs résidus (CGI, art. 302 bis WC)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4247").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4247', $data['4247'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("86").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Redevance sanitaire de première mise sur le marché des produits de la pêche ou de l’aquaculture (CGI, art. 302 bis WA)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4248").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4248', $data['4248'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("87").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Redevance sanitaire de transformation des produits de la pêche ou de l’aquaculture (CGI, art. 302 bis WB)");
				$h .= '</td>';
				$h .= '<td>'.s("Nombre de tonnes").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4249-qty', $data['4249-qty'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4249").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4249', $data['4249'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("88").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Redevance pour agrément des établissements du secteur de l’alimentation animale (CGI, art. 302 bis WD à WG) (125 € par établissement)");
				$h .= '</td>';
				$h .= '<td>'.s("Nombre d’établissements").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4250-base', $data['4250-base'] ?? 0, ['data-fixed-price' => 125] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4250").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4250', $data['4250'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Redevance phytosanitaire à la circulation intracommunautaire et à l’exportation (Code rural et de la pêche maritime, art. L 251-17-1)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("89").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<span class="ml-2">'.s("– à la circulation intracommunautaire (PPE) (Code rural et de la pêche maritime, art. L 251-17-1)").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4273").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4273', $data['4273'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("90").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<span class="ml-2">'.s("– à l’exportation (Code rural et de la pêche maritime, art. L 251-17-1)").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4274").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4274', $data['4274'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("90A").'</td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur les produits phytopharmaceutiques (Code rural et de la pêche maritime, art. L 253-8-2)");
				$h .= '</td>';
				$h .= '<td>'.s("Total de la taxe due").'</td>';
				$h .= '<td></td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4321").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4321', $data['4321'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe forfaitaire sur les ventes de métaux précieux au taux de 11 % (CGI, art. 150 VI à VM)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("91").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("– sur les ventes de métaux précieux au taux de 11 %").'</span>';
				$h .= '</td>';
				$h .= '<td rowspan="2">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4268-base', $data['4268-base'] ?? 0, ['data-rate' => 11] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4268").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4268', $data['4268'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("92").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- sur les ventes de bijoux, objets d’art, de collection ou d’antiquité au taux de 6 %").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4270-base', $data['4270-base'] ?? 0, ['data-rate' => 6] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4270").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4270', $data['4270'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Contribution pour le remboursement de la dette sociale (CRDS) (CGI, art. 1600-0 I) au taux de 0,5 %");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("93").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- sur les ventes de métaux précieux au taux de 0,5 %").'</span>';
				$h .= '</td>';
				$h .= '<td rowspan="2">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4269-base', $data['4269-base'] ?? 0, ['data-rate' => 0.5] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4269").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4269', $data['4269'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("94").'</td>';
				$h .= '<td colspan="2">';
					$h .= '<span class="ml-2">'.s("- sur les ventes de bijoux, objets d’art, de collection ou d’antiquité (CGI, art. 1600-0 I)").'</span>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4271-base', $data['4271-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4271").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4271', $data['4271'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("95").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Contribution forfaitaire pour alimentation du fonds commun des accidents du travail agricole (CGI, art. 1622)");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4272").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4272', $data['4272'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Prélèvement sur les paris hippiques").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("96").'</td>';
				$h .= '<td colspan="2">'.s("- au profit de l’État au taux de 20,2 % (CGI, art. 302 bis ZG)").'</td>';
				$h .= '<td rowspan="3">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4256-base', $data['4256-base'] ?? 0, ['data-rate' => 20.2] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4256").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4256', $data['4256'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("97").'</td>';
				$h .= '<td colspan="2">'.s("- au profit des organismes de sécurité sociale (CSS, art. L137-20)").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4259-base', $data['4259-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4259").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4259', $data['4259'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("98").'</td>';
				$h .= '<td colspan="2">'.s("- engagés depuis l’étranger sur des courses françaises (CGI, art. 302 bis ZO)").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4255-base', $data['4255-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4255").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4255', $data['4255'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("98A").'</td>';
				$h .= '<td colspan="2"><b>'.s("Prélèvement sur les paris hippiques portant sur des épreuves passées au taux de 20,2 % (CGI, art. 302 bis ZG II)").'</b></td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4336-base', $data['4336-base'] ?? 0, ['data-rate' => 20.2] + $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4336").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4336', $data['4336'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Redevance due par les opérateurs agréés de paris hippiques en ligne").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("99").'</td>';
				$h .= '<td colspan="4"><span class="ml-2">'.s("- Enjeux relatifs aux courses de trot (CGI, art. 1609 tertricies) (cf. notice pour les taux applicables").'</span></td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4266").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4266', $data['4266'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("100").'</td>';
				$h .= '<td colspan="4"><span class="ml-2">'.s("- Enjeux relatifs aux courses de galop (CGI, art. 1609 tertricies) (cf. notice pour les taux applicables)").'</span></td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4267").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4267', $data['4267'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Prélèvements sur les paris sportifs en ligne").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("101A").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit de l’État (CGI, art. 302 bis ZH) au taux de 33,7 %").'</span></td>';
				$h .= '<td rowspan="3">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4309-base', $data['4309-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4309").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4309', $data['4309'] ?? 0, ['data-rate' => 33.7] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("102A").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit des organismes de sécurité sociale (CSS, art. L137-21) (cf. notice pour les taux applicables)").'</span></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4310-base', $data['4310-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4310").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4310', $data['4310'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("103A").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit de l’agence nationale du sport (ANS) (CGI, art. 1609 tricies) au taux de 10,6 % ").'</span></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4311-base', $data['4311-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4311").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4311', $data['4311'] ?? 0, ['data-rate' => 6] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Prélèvements sur les paris sportifs commercialisés en réseau physique de distribution").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("101B").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit de l’État (CGI, art. 302 bis ZH) au taux de 27,9 %").'</span></td>';
				$h .= '<td rowspan="3">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4306-base', $data['4306-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4306").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4306', $data['4306'] ?? 0, ['data-rate' => 27.9] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("102B").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit des organismes de sécurité sociale (CSS, art. L137-21) (cf. notice pour les taux applicables) ").'</span></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4307-base', $data['4307-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4307").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4307', $data['4307'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("103B").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit de l’agence nationale du sport (ANS) (CGI, art. 1609 tricies) au taux de 6,6 %").'</span></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4308-base', $data['4308-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4308").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4308', $data['4308'] ?? 0, ['data-rate' => 6.6] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Prélèvements sur les jeux de cercle en ligne").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("104").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit de l’État (CGI, art. 302 bis ZI) au taux de 1,8 %").'</span></td>';
				$h .= '<td rowspan="2">'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4258-base', $data['4258-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4258").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4258', $data['4258'] ?? 0, ['data-rate' => 1.8] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("105").'</td>';
				$h .= '<td colspan="2"><span class="ml-2">'.s("- au profit des organismes de sécurité sociale (CSS, art. L137-22) (cf. notice pour les taux applicables) ").'</span></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4261-base', $data['4261-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4261").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4261', $data['4261'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("107A").'</td>';
				$h .= '<td colspan="2">'.s("Prélèvement au profit de l’agence nationale du sport (ANS) sur les jeux commercialisés par la Française des jeux (CGI, art. 1609 novovicies) au taux de 5,1 % ").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4312-base', $data['4312-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4312").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4312', $data['4312'] ?? 0, ['data-rate' => 5.1] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("107B").'</td>';
				$h .= '<td colspan="2">'.s("Prélèvement progressif dû par les clubs de jeux (II de l'article 34 de la loi n°2017-1775 du 28 décembre 2017 de finances rectificative pour 2017)").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4290-base', $data['4290-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4290").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4290', $data['4290'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("107C").'</td>';
				$h .= '<td colspan="4">'.s("Sommes constatées par les clubs de jeux au titre des \"orphelins\" (arrêté du 23 février 2021 relatif aux modalités de déclaration et d’encaissement des sommes qualifiées d’orphelins versées par les clubs de jeux").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4304").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4304', $data['4304'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("107D").'</td>';
				$h .= '<td colspan="2"><b>'.s("Contribution sur les publicités et actions promotionnelles due par les opérateurs de jeux et paris (CSS, art. L137-27), au taux de 15 %").'</b></td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4337-base', $data['4337-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4337").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4337', $data['4337'] ?? 0, ['data-rate' => 15] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Contribution sociale généralisée (CGCT, art. L2333-57, CSS, III de l’art. L136-7-1)").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("111").'</td>';
				$h .= '<td colspan="2">'.s("* sur une fraction égale à 68 % du produit brut des jeux des machines à sous au taux de 11,2 %").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4283-base', $data['4283-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4283").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4283', $data['4283'] ?? 0, ['data-rate' => 11.2] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("112").'</td>';
				$h .= '<td colspan="2">'.s("* sur le montant des gains des machines à sous d’un montant supérieur ou égal à 1 500 € réglés aux joueurs par le caissier sous forme de bons de paiement manuels au taux de 13,7 %").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4284-base', $data['4284-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4284").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4284', $data['4284'] ?? 0, ['data-rate' => 13.7] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("113").'</td>';
				$h .= '<td colspan="2">'.s("Contribution pour le remboursement de la dette sociale (CRDS) portant sur le montant du produit total des jeux au taux de 3 % (CGCT, art. L2333-57, articles 18-III et 19 de l’ordonnance n° 96-50 du 24 janvier 1996)").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4285-base', $data['4285-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4285").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4285', $data['4285'] ?? 0, ['data-rate' => 3] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("115").'</td>';
				$h .= '<td colspan="2">'.s("Taxe sur les recettes de l’exploitation du réseau autoroutier concédé (CIBS, art. L421-181)").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4277-base', $data['4277-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4277").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4277', $data['4277'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">';
					$h .= s("Taxe annuelle sur les véhicules lourds de transport de marchandises (CIBS, art. L421-94)");
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td>'.s("Nombre de véhicules ").'</td>';
							$h .= '<td>'.s("dont nombre de véhicules rail-route").'</td>';
							$h .= '<td colspan="2">'.s("Montant de la taxe").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td rowspan="2">'.s("1-Véhicules à moteur isolés").'</td>';
							$h .= '<td>'.s("PTAC inférieur à 27 t ").'</td>';

							$h .= '<td>'.$form->number('4303-1a-number', $data['4303-1-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.$form->number('4303-1a-sub-number', $data['4303-1a-sub-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.s("1a").'</td>';
							$h .= '<td>'.$form->number('4303-1a-tax', $data['4303-1a-tax'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.s("PTAC supérieur ou égal à 27 t").'</td>';
							$h .= '<td>'.$form->number('4303-1b-number]', $data['4303-1b-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.$form->number('4303-1b-sub-number]', $data['4303-1b-sub-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.s("1b").'</td>';
							$h .= '<td>'.$form->number('4303-1b-tax', $data['4303-1b-tax'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td rowspan="2">'.s("2-Ensembles articulés constitués d’un tracteur et d’une ou plusieurs semi-remorques").'</td>';
							$h .= '<td>'.s("PTAC inférieur à 39 t").'</td>';
							$h .= '<td>'.$form->number('4303-2a-number', $data['4303-2a-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.$form->number('4303-2a-sub-number', $data['4303-2a-sub-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.s("2a").'</td>';
							$h .= '<td>'.$form->number('4303-2a-tax', $data['4303-2a-tax'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.s("PTAC supérieur ou égal à 39 t").'</td>';
							$h .= '<td>'.$form->number('4303-2b-number', $data['4303-2b-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.$form->number('4303-2b-sub-number', $data['4303-2b-sub-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.s("2b").'</td>';
							$h .= '<td>'.$form->number('4303-2b-tax', $data['4303-2b-tax'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td colspan="2">'.s("3-Remorques de la catégorie O4").'</td>';
							$h .= '<td>'.$form->number('4303-3-number', $data['4303-3-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.$form->number('4303-3-sub-number', $data['4303-3-sub-number'] ?? 0, $attributes).'</td>';
							$h .= '<td>'.s("3").'</td>';
							$h .= '<td>'.$form->number('4303-3-tax', $data['4303-3-tax'] ?? 0, $attributes).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("116").'</td>';
				$h .= '<td colspan="4" class="text-end">'.s("Total de la taxe annuelle sur les véhicules lourds de transport de marchandises due (1a + 1b + 2a + 2b + 3)").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4303").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4303', $data['4303'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("117").'</td>';
				$h .= '<td colspan="4">'.s("Taxe annuelle sur les émissions de dioxyde de carbone des véhicules de tourisme (CIBS, a du 1° de l’art. L421-
94). Une fiche d’aide au calcul (formulaire n°2857-FC-SD) et sa notice sont disponibles sur impots.gouv.fr").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4323").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4323', $data['4323'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre de véhicules relevant du nouveau dispositif d’immatriculation (depuis le 1er mars 2020)").'</td>';
				$h .= '<td>'.$form->number('4313[0]', $data['4313'][0] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre de véhicules ne relevant pas du nouveau dispositif d’immatriculation: (réception européenne, dont la première mise en circulation est intervenue à compter du 1er juin 2004 et non utilisés par le redevable avant le 1er janvier 2006)").'</td>';
				$h .= '<td>'.$form->number('4313[1]', $data['4313'][1] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre d’autres véhicules soumis à la taxe").'</td>';
				$h .= '<td>'.$form->number('4313[2]', $data['4313'][2] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre de véhicules exonérés dont la source d’énergie est l’électricité, l’hydrogène ou une combinaison des deux").'</td>';
				$h .= '<td>'.$form->number('4313[3]', $data['4313'][3] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre des autres véhicules exonérés").'</td>';
				$h .= '<td>'.$form->number('4313[4]', $data['4313'][4] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("118").'</td>';
				$h .= '<td colspan="4">'.s("Taxe annuelle sur les émissions de polluants atmosphériques des véhicules de tourisme (CIBS, b du 1° de l’art. L421-94). Une fiche d’aide au calcul (formulaire n°2858-FC-SD) et sa notice sont disponibles sur impots.gouv.fr").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4313").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4313[value]', $data['4313']['value'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="3">'.s("Nombre de véhicules exonérés").'</td>';
				$h .= '<td>'.$form->number('4335[number]', $data['4335']['number'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("119").'</td>';
				$h .= '<td colspan="4">'.s("<b>Taxe annuelle incitative relative à l’acquisition de véhicules légers à faibles émissions pour les flottes comprenant au moins 100 véhicules (CIBS, 1°bis de l’art. L421-94).</b> Une fiche d’aide au calcul (formulaire n°2854-FC-SD) et sa notice n°2854-FC-NOT-SD sont disponibles sur impots.gouv.fr").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4335").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4335', $data['4335'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="2">';
					$h .= s("Taxe sur l’exploration d’hydrocarbures calculée selon le barème fixé à l’article 1590 du CGI et perçue au profit des collectivités territoriales");
				$h .= '</td>';
				$h .= '<td class="font-sm text-center">'.s("Base INSEE de la collectivité").'</td>';
				$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			for($i = 0; $i < 3; $i++) {

				$h .= '<tr>';
					$h .= '<td class="vat-cerfa-number"></td>';
					$h .= '<td colspan="2">';
						$h .= '<span class="ml-1">';
							$h .= s("– Droits pour le département ou la collectivité territoriale :");
						$h .= '</span>';
					$h .= '</td>';
					$h .= '<td class="vat-cerfa-input">'.$form->number('4291-code['.$i.']', $data['4291-code['.$i.']'] ?? 0, $attributes).'</td>';
					$h .= '<td class="vat-cerfa-input">'.$form->number('4291-amount['.$i.']', $data['4291-amount['.$i.']'] ?? 0, $attributes).'</td>';
					$h .= '<td class="vat-cerfa-identifier"></td>';
					$h .= '<td class="vat-cerfa-input"></td>';
				$h .= '</tr>';

			}

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("121").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Montant total de la taxe sur l’exploration d’hydrocarbures ");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4291").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4291', $data['4291'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("124").'</td>';
				$h .= '<td colspan="2">'.s("Contribution sur les boissons non alcooliques contenant des sucres ajoutés (CGI, art.1613 ter)").'</td>';
				$h .= '<td>'.s("Nombre d’hectolitres").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4294-base', $data['4294-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4294").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4294', $data['4294'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("125").'</td>';
				$h .= '<td colspan="2">'.s("Contribution sur les boissons non alcooliques (CGI, art. 1613 quater II 1°), 0,54€ / hl").'</td>';
				$h .= '<td>'.s("Nombre d’hectolitres").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4296-number', $data['4296-number'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4296").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4296', $data['4296'] ?? 0, ['data-rate-by-hl' => 0.54] + $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("126").'</td>';
				$h .= '<td colspan="2">'.s("Contribution sur les boissons non alcooliques contenant des édulcorants de synthèse (CGI, art. 1613 quater II 2°),").'</td>';
				$h .= '<td>'.s("Nombre d’hectolitres").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4295-base', $data['4295-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4295").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4295', $data['4295'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td>';
					$h .= s("Contribution sur les eaux minérales naturelles (CGI, art. 1582) ");
				$h .= '</td>';
				$h .= '<td class="font-sm text-center">'.s("Code INSEE de la commune").'</td>';
				$h .= '<td class="font-sm text-center">'.s("Nombre d'hectolitres").'</td>';
				$h .= '<td class="font-sm text-center">'.s("Montant").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			for($i = 0; $i < 6; $i++) {

				$h .= '<tr>';
					$h .= '<td class="vat-cerfa-number"></td>';
					$h .= '<td>';
						$h .= '<span class="ml-1">';
							$h .= s("– Droits pour la commune :");
						$h .= '</span>';
					$h .= '</td>';
					$h .= '<td class="vat-cerfa-input">'.$form->number('4293-code['.$i.']', $data['4293-code['.$i.']'] ?? 0, $attributes).'</td>';
					$h .= '<td class="vat-cerfa-input">'.$form->number('4293-quantity['.$i.']', $data['4293-quantity['.$i.']'] ?? 0, $attributes).'</td>';
					$h .= '<td class="vat-cerfa-input">'.$form->number('4293-amount['.$i.']', $data['4293-amount['.$i.']'] ?? 0, $attributes).'</td>';
					$h .= '<td class="vat-cerfa-identifier"></td>';
					$h .= '<td class="vat-cerfa-input"></td>';
				$h .= '</tr>';

			}

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("128").'</td>';
				$h .= '<td colspan="4">';
					$h .= s("Montant total de la contribution sur les eaux minérales");
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4293").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4293', $data['4293'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("129").'</td>';
				$h .= '<td colspan="2">'.s("Taxe sur les exploitants de plateformes de mise en relation par voie électronique en vue de fournir certaines prestations de transport (CGI, art. 300 bis)").'</td>';
				$h .= '<td>'.s("Base imposable").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4322-base', $data['4322-base'] ?? 0, $attributes).'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4322").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4322', $data['4322'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number"></td>';
				$h .= '<td colspan="4">'.s("Taxe sur certains services numériques (TSN) (CIBS, art. L453-45 et L453-82)").'</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4322").'</td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('4322', $data['4322'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number">'.s("131").'</td>';
				$h .= '<td colspan="4">';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td rowspan="2" style="width: 20%">';
								$h .= s("– Paiement du solde de la taxe due au titre de {value}", $lastYear).'</td>';
							$h .= '</td>';
							$h .= '<td style="width: 20%" class="text-center">'.s("Montant dû au titre de la mise en relation (a)").'</td>';
							$h .= '<td style="width: 20%" class="text-center">'.s("Montant dû au titre de la publicité (b)").'</td>';
							$h .= '<td style="width: 20%" class="text-center">'.s("Acomptes payés en {value} (c)", $lastYear).'</td>';
							$h .= '<td style="width: 20%" class="text-center">'.s("Excédent d’acompte si <small>(a+b-c) <0 (d) (montant à reporter colonne b de la ligne 133)</small>").'</td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.$form->number('4301-a', $data['4301-a'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
							$h .= '<td>'.$form->number('4301-b', $data['4301-b'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
							$h .= '<td>'.$form->number('4301-c', $data['4301-c'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
							$h .= '<td>'.$form->number('4301-total', $data['4301-total'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</td>';
				$h .= '<td class="vat-cerfa-identifier">'.s("4301").'</td>';
				$h .= '<td class="vat-cerfa-input">';
					$h .= '<div class="font-xs text-center">'.s("Solde restant dû si (a+b-c) > 0").'</div>';
					$h .= $form->number('4301', $data['4301'] ?? 0, $attributes);
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number" rowspan="5"></td>';
				$h .= '<td rowspan="2">'.s("Chiffre d’affaires mondial relatif aux services numériques taxables réalisés en {value}", $lastYear).'</td>';
				$h .= '<td class="text-center">'.s("Mise en relation").'</td>';
				$h .= '<td class="text-center">'.s("Publicité").'</td>';
				$h .= '<td class="text-center">'.s("Total").'</td>';
				$h .= '<td rowspan="5" class="vat-cerfa-identifier"></td>';
				$h .= '<td rowspan="5" class="vat-cerfa-input"></td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td>'.$form->number('4300-relation', $data['4300-relation'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
				$h .= '<td>'.$form->number('4300-advertising', $data['4300-advertising'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
				$h .= '<td>'.$form->number('4300-total', $data['4300-total'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td colspan="4" class="text-center">';
					$h .= '<b>'.s("Tableau à compléter uniquement par la société désignée comme « tête de groupe – TSN » (voir notice)").'</b>';
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td colspan="4" class="text-center">'.s("Montant total annuel de la taxe due par chaque société membre du groupe").'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td colspan="4">';
					$h .= '<table class="no-border-bottom">';
						$h .= '<tr>';
							$h .= '<td class="text-center" style="width: 33%">'.s("N° Siren ou à défaut autre identifiant").'</td>';
							$h .= '<td class="text-center" style="width: 33%">'.s("Dénomination").'</td>';
							$h .= '<td class="text-center" style="width: 33%">'.s("Montant de la taxe due").'</td>';
						$h .= '</tr>';
						for($i = 0; $i < 4; $i++) {
							$h .= '<tr>';
								$h .= '<td class="text-center">'.$form->number('4300-siren['.$i.']', $data['4300-siren['.$i.']'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
								$h .= '<td class="text-center">'.$form->number('4300-denomination['.$i.']', $data['4300-denomination['.$i.']'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
								$h .= '<td class="text-center">'.$form->number('4300-amount['.$i.']', $data['4300-amount['.$i.']'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
							$h .= '</tr>';
						}
					$h .= '</table>';
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td class="vat-cerfa-number" rowspan="2">'.s("133").'</td>';
				$h .= '<td rowspan="2">'.s("– Paiement de l’acompte prévu à l’article 1693 quater du CGI dû au titre de la TSN {value}", $year).'</td>';
				$h .= '<td class="text-center">'.s("Montant de l’acompte dû (a)").'</td>';
				$h .= '<td class="text-center">'.s("Excédent d’acompte {value} (report (d) de la ligne 131 (b)", $lastYear).'</td>';
				$h .= '<td class="text-center">'.s("Excédent restant à imputer sur l’acompte suivant ou le solde (si a-b <0)").'</td>';
				$h .= '<td rowspan="2" class="vat-cerfa-identifier">'.s("4300").'</td>';
				$h .= '<td rowspan="2" class="vat-cerfa-input">';
					$h .= '<div class="font-xs text-center">'.s("Solde restant dû si (a+b-c) > 0").'</div>';
					$h .= $form->number('4300', $data['4300'] ?? 0, $attributes);
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td>'.$form->number('4300-a', $data['4300-a'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
				$h .= '<td>'.$form->number('4300-b', $data['4300-b'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
				$h .= '<td>'.$form->number('4300-c', $data['4300-c'] ?? 0, $attributes + ['style' => 'margin: auto;']).'</td>';
			$h .= '</tr>';

			$h .= '<tr class="vat-cerfa-total">';
				$h .= '<td colspan="5" class="text-end">'.s("<b>TOTAL DES LIGNES 47 À 133</b> (à reporter ligne 29 de la CA3)").'</td>';
				$h .= '<td class="vat-cerfa-identifier"></td>';
				$h .= '<td class="vat-cerfa-input">'.$form->number('total-3310A', $data['total-3310A'] ?? 0, $attributes).'</td>';
			$h .= '</tr>';

		$h .= '</table>';

		return $h;

	}

	public function showSuggestedOperations(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Declaration $eDeclaration, \Collection $cOperation, array $cerfaCalculated, array $cerfaDeclared, \account\FinancialYear $eFinancialYearOperations): \Panel {

		if($eDeclaration->acceptAccount()) {
			$title = s("Créer les écritures suite à une déclaration de TVA");
		} else {
			$title = s("Consulter les écritures proposées suite à la déclaration de TVA");
		}
		$panelId = 'panel-create-operations-from-vat-declaration';

		if($eDeclaration['declaredAt'] === NULL) {

			$h = '<div class="util-warning-outline">'.s("Enregistrez d'abord que vous avez rempli votre déclaration.").'</div>';

			return new \Panel(
				id: $panelId,
				title: $title,
				body: $h,
			);

		}

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doCreateOperations',
			[
				'id' => 'vat-declaration-create-operations',
				'class' => 'panel-dialog',
			]
		);

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eDeclaration['id']);

		$h .= '<div class="util-info">';
			$h .= '<p>'.s("{siteName} vous propose les écritures comptables suivantes pour enregistrer votre déclaration de TVA").'</p>';
			$h .= s("Ces écritures sont basées sur : ");
			$h .= '<ul>';
				$h .= '<li>'.s("Les écritures enregistrées au débit et au crédit sur les comptes de TVA (comptes commençant par {value})", \account\AccountSetting::VAT_CLASS).'</li>';
				$h .= '<li>'.s("La présente déclaration de TVA").'</li>';
			$h .= '</ul>';
			$h .= \Asset::icon('exclamation-square', ['class' => 'mr-1']).s("Vérifiez que <link>vos écritures comptables {icon}</link> correspondent bien à <link2>ce que vous avez déclaré {icon}</link2> pour que les écritures proposées soient correctes.",
				[
					'link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?accountLabel='.\account\AccountSetting::VAT_CLASS.'" target="_blank">',
					'link2' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/declaration-de-tva?tab=cerfa&id='.$eDeclaration['id'].'" target="_blank">',
					'icon' => \Asset::icon('box-arrow-up-right'),
				]);
		$h .= '</div>';

		$h .= '<h3 class="mt-2">'.\Asset::icon('1-circle').' '.s("Écritures comptables suggérées d'après vos écritures et votre déclaration").'</h3>';

		if($eDeclaration->isAccounted()) {
			if($eDeclaration['accountedWithOperations']) {
				$h .= '<div class="util-block-success">'.s("Les écritures comptables ont bien été créées au moment de la comptabilisation.").'</div>';
			} else {
				$h .= '<div class="util-block-success">'.s("Vous avez indiqué que les écritures comptables ont bien été créées.").'</div>';
			}
		}

		$h .= '<table class="tr-even tr-hover">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.s("Numéro de compte").'</th>';
					$h .= '<th>'.s("Libellé").'</th>';
					$h .= '<th>'.s("Tiers").'</th>';
					$h .= '<th class="text-end t-highlight">'.s("Débit (D)").'</th>';
					$h .= '<th class="text-end t-highlight">'.s("Crédit (C)").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';
				foreach($cOperation as $eOperation) {
					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<div class="journal-operation-description" data-dropdown="bottom" data-dropdown-hover="true">'.encode($eOperation['accountLabel']).'</div>';
							$h .= '<div class="dropdown-list bg-primary"><span class="dropdown-item">'.encode($eOperation['account']['description']).'</span></div>';
						$h .= '</td>';
						$h .= '<td><div class="description"><span class="'.($eOperation['type'] === \journal\Operation::CREDIT ? 'ml-3' : '').'">'.encode($eOperation['description']).'</span></div></td>';
						$h .= '<td>'.encode($eOperation['thirdParty']['name'] ?? '').'</td>';
						$h .= '<td class="text-end t-highlight td-vertical-align-top">'.($eOperation['type'] === \journal\Operation::DEBIT ? \util\TextUi::money($eOperation['amount']) : '').'</td>';
						$h .= '<td class="text-end t-highlight td-vertical-align-top">'.($eOperation['type'] === \journal\Operation::CREDIT ? \util\TextUi::money($eOperation['amount']) : '').'</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';
		$h .= '</table>';

		$h .= '<h3 class="mt-2">'.\Asset::icon('2-circle').' ';
			if($eDeclaration->isCredit()) {
				$h .= s("Écritures comptables de remboursement");
			} else {
				$h .= s("Écritures comptables de paiement");
			}
		$h .='</h3>';

		if($eDeclaration->isPaid()) {
			if($eDeclaration->isCredit()) {
				$h .= '<div class="util-block-success">'.s("Vous avez enregistré que les écritures comptables de remboursement ont bien été créées.").'</div>';
			} else {
				$h .= '<div class="util-block-success">'.s("Vous avez enregistré que les écritures comptables de paiement ont bien été créées.").'</div>';
			}
		}

		if($eDeclaration->isCredit()) {

			$taxAssimileesCell = match($eDeclaration['cerfa']) {
				\vat\Declaration::CA12 => '55-number',
				\vat\Declaration::CA3 => '9979',
			};
			$taxAssimileesCellText = match($eDeclaration['cerfa']) {
				\vat\Declaration::CA12 => s("ligne 55"),
				\vat\Declaration::CA3 => s("ligne 29"),
			};
			$taxAssimileesValue = $eDeclaration['data'][$taxAssimileesCell];
			if($taxAssimileesValue > 0) {

				$h .= '<p>'.s("Le montant des taxes assimilées à régler est indiqué <b>{cell}</b> : <b>{amount}</b>. Une fois le paiement effectué et visible sur votre compte bancaire, enregistrez le paiement en :", ['amount' => \util\TextUi::money($taxAssimileesValue), 'cell' => $taxAssimileesCellText]).'</p>';
					$h .= '<ul>';
						$h .= '<li>'.s("débitant le compte <b>{value}</b>", \account\AccountSetting::VAT_DEBIT_CLASS).'</li>';
						$h .= '<li>'.s("créditant votre compte de banque <b>{value}</b>", \account\AccountSetting::BANK_ACCOUNT_CLASS).'</li>';
					$h .= '</ul>';

			}

			if((int)$eDeclaration['data'][8002] === 0 and (int)$eDeclaration['data'][8003] > 0) {

				$h .= '<p>'.s("Le crédit de TVA (compte <b>{account}</b>) d'un montant de <b>{amount}</b> sera automatiquement déduit de votre prochaine déclaration.", ['amount' => \util\TextUi::money($eDeclaration['data']['8003']), 'account' => \account\AccountSetting::VAT_CREDIT_CLASS]).'</p>';

			} else if((int)$eDeclaration['data'][8002] > 0 and (int)$eDeclaration['data'][8003] > 0) {

				$h .= '<p>'.s("Vous avez fait une demande de remboursement partiel d'un montant de <b>{value}</b>. À réception du remboursement, vous pourrez le comptabiliser en :", \util\TextUi::money($eDeclaration['data'][8002])).'</p>';

				$h .= '<ul>';

					$h .= '<li>'.s("débitant votre compte de banque <b>{value}</b>", \account\AccountSetting::BANK_ACCOUNT_CLASS).'</li>';
					$h .= '<li>'.s("créditant le compte <b>{value}</b>.", \account\AccountSetting::VAT_REIMBURSE_CLASS).'</li>';

				$h .= '</ul>';

				$h .= '<p>'.s("Le crédit de TVA (compte <b>{account}</b>) d'un montant de <b>{amount}</b> sera automatiquement déduit de votre prochaine déclaration.", ['amount' => \util\TextUi::money($eDeclaration['data']['8003']), 'account' => \account\AccountSetting::VAT_CREDIT_CLASS]).'</p>';

			} else if((int)$eDeclaration['data'][8002] > 0 and (int)$eDeclaration['data'][8003] === 0) {

				$h .= '<p>'.s("Vous avez fait une demande de remboursement total d'un montant de <b>{value}</b>. À réception du remboursement, vous pourrez le comptabiliser en :", \util\TextUi::money($eDeclaration['data'][8002])).'</p>';

				$h .= '<ul>';

					$h .= '<li>'.s("débitant votre compte de banque <b>{value}</b>", \account\AccountSetting::BANK_ACCOUNT_CLASS).'</li>';
					$h .= '<li>'.s("créditant le compte <b>{value}</b>.", \account\AccountSetting::VAT_REIMBURSE_CLASS).'</li>';

				$h .= '</ul>';

			}

		} else if($cOperation->offsetExists(\account\AccountSetting::VAT_DEBIT_CLASS)) {

			$h .= '<p>';
				$h .= s("Le montant total à régler au <b>Trésor public</b> est indiqué <b>ligne 56 (case 9992)</b> de votre déclaration : {value}.", '<b>'.\util\TextUi::money($eDeclaration['data']['9992']).'</b>');
			$h .= '</p>';

			$h .= '<p>';
				$h .= s("Une fois le paiement effectué, vous pouvez saisir les écritures suivantes : ");
			$h .= '</p>';

			$h .= '<ul>';

				$h .= '<li>'.s("Pour la TVA nette due, débitez le compte <b>{vatAccount}</b> et créditez votre compte de banque <b>{bankAccount}</b>.", ['vatAccount' => \account\AccountSetting::VAT_DEBIT_CLASS, 'bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</li>';

				$h .= '<li>'.s("Pour la taxe sur le chiffre d'affaires et toutes les autres taxes assimilées, débitez le compte  <b>{vatAccount}</b> du montant de la taxe et créditez votre compte de banque <b>{bankAccount}</b>.", ['vatAccount' => \account\AccountSetting::VAT_DEBIT_CLASS, 'bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</li>';

			$h .= '</ul>';
			$h .= '<div class="util-info">'.\Asset::icon('exclamation-triangle').' '.s("Pensez à comptabiliser chaque taxe assimilée séparément.").'</div>';

		}

		if($eDeclaration->acceptPay()) {

			if($eDeclaration->isCredit()) {
				$h .= '<div class="util-info">'.s("Confirmez que le remboursement a été comptabilisé pour clôturer votre déclaration de TVA.").'</div>';
			} else {
				$h .= '<div class="util-info">'.s("Confirmez que le paiement a été comptabilisé pour clôturer votre déclaration de TVA.").'</div>';
			}

		}

		if($eDeclaration->acceptAccount()) {
			$h .= '<h3 class="mt-2">'.\Asset::icon('3-circle').' '.s("Exercice comptable").'</h3>';

			if($eFinancialYearOperations->empty()) {
				$h .= '<div class="util-warning-outline">'.s("Il n'y a pas d'exerice comptable qui peut recevoir ces écritures. Veuillez le créer ou enregistrer les écritures vous-même dans le bon exercice.").'</div>';
				$canCreate = FALSE;
			} else {
				$h .= '<p>'.s("L'exercice comptable dans lequel seront enregistrées ces écritures est l'<b>exercice {value}</b>.", \account\FinancialYearUi::getYear($eFinancialYearOperations)).'</p>';
				$h .= '<p>'.s("Si cela ne correspond pas à ce que vous souhaitez faire, enregistrez plutôt les écritures vous-même, à l'aide des écritures suggérées ci-dessus.").'</p>';
				$canCreate = TRUE;
			}

			$h .= '<h3 class="mt-2">'.\Asset::icon('4-circle').' '.s("Enregistrer la déclaration").'</h3>';

			$h .= '<p>'.s("En cliquant sur <b>Bien compris</b>, {siteName} ne fera rien.").'</p>';
			$h .= '<p>'.s("En cliquant sur <b>J'ai créé les écritures</b>, {siteName} enregistrera que vous avez déclaré sur le site des impôts à la date du jour.").'</p>';
			$h .= '<p>'.s("En cliquant sur <b>Créer les écritures de l'étape {value}</b>, {siteName} enregistrera que vous avez déclaré sur le site des impôts à la date du jour et créera automatiquement toutes les écritures présentées dans l'étape {value} dans l'exercice indiqué à l'étape {icon}.", \Asset::icon('1-circle'), \Asset::icon('3-circle')).'</p>';

			if($eDeclaration->isCredit()) {
				$h .= '<div class="util-info">'.s("Les deux dernières actions vous permettront ensuite de clôre le dossier de Déclaration de TVA en indiquant que le remboursement est comptabilisé.").'</div>';
			} else {
				$h .= '<div class="util-info">'.s("Les deux dernières actions vous permettront ensuite de clôre le dossier de Déclaration de TVA en indiquant que le paiement est comptabilisé.").'</div>';
			}

		}

		$saveButton = '<div class="text-end">';

			$saveButton .= $form->button(s("Bien compris"), ['class' => 'btn btn-outline-secondary', 'onclick' => 'Lime.Panel.closeLast()']).' ';

			if($eDeclaration->acceptAccount()) {

				$saveButton .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doUpdateAccountStatus" post-id="'.$eDeclaration['id'].'" class="btn btn-secondary">'.s("J'ai créé les écritures").'</a>';
				$saveButton .= ' ';
				$saveButton .= $form->submit(s("Créer les écritures de l'étape {value}", \Asset::icon('1-circle')), $canCreate ? [] : ['class' => 'btn btn-primary disabled', 'disabled' => 'disabled']);

			}

			if($eDeclaration->acceptPay()) {

				$saveButton .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/vat/declaration:doUpdatePaymentStatus" post-id="'.$eDeclaration['id'].'" post-status="'.Declaration::PAID.'" class="btn btn-secondary">';
					if($eDeclaration->isCredit()) {
						$saveButton .= s("Le remboursement a été comptabilisé");
					} else {
						$saveButton .= s("Le paiement a été comptabilisé");
					}
				$saveButton .= '</a>';

			}

		$saveButton .= '</div>';

		return new \Panel(
			id         : $panelId,
			title      : $title,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			header     : '<h2>'.$title.'</h2>',
			footer     : $saveButton,
		);

	}

	public static function getTranslations(string|int $code, array $params = []): string {

		return match($code) {
			\account\AccountSetting::VAT_ASSET_CLASS => s("TVA déductible sur immobilisations"),
			\account\AccountSetting::VAT_DEDUCTIBLE_INTRACOM_CLASS => s("TVA déductible intracommunautaire"),
			\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT => s("TVA déductible sur achats"),
			\account\AccountSetting::VAT_DEPOSIT_CLASS => s("Acompte de TVA"),
			\account\AccountSetting::VAT_REIMBURSE_CLASS => s("Remboursement de TVA"),
			\account\AccountSetting::PRODUCT_OTHER_CLASS => s("Arrondi déclaration de TVA"),
			\account\AccountSetting::CHARGES_OTHER_CLASS => s("Arrondi déclaration de TVA"),
			\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT => s("TVA collectée"),
			\account\AccountSetting::VAT_TO_PAY_INTRACOM_CLASS => s("TVA due intracommunautaire"),
			\account\AccountSetting::VAT_CREDIT_CLASS => s("Crédit de TVA"),
			\account\AccountSetting::VAT_DEBIT_CLASS => s("TVA à décaisser"),
			\account\AccountSetting::CHARGE_TURNOVER_UNRECUPERABLE_ACCOUNT_CLASS => s("Taxe sur le chiffre d'affaires non récupérable"),
			'document' => s("Déclaration TVA du {from} au {to}", $params),
			'tresor-public' => s("Trésor public"),
		};

	}

}

?>
