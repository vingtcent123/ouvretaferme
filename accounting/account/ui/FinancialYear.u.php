<?php
namespace account;

class FinancialYearUi {

	public function __construct() {

		\Asset::js('account', 'financialYear.js');
		\Asset::css('account', 'financialYear.css');
		\Asset::css('company', 'company.css');

	}

	public function getManageTitle(\farm\Farm $eFarm, \Collection $cFinancialYearOpen): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les exercices comptables");
			$h .= '</h1>';

			$h .= '<div>';
			if($cFinancialYearOpen->count() < 2) {
				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un exercice comptable").'</a> ';
			}
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getCloseTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/etats-financiers/"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Clôturer un exercice comptable");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function getOpenTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/etats-financiers/"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Créer un bilan d'ouverture");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function createForm(\farm\Farm $eFarm, FinancialYear $eFinancialYear): string {

		$form = new \util\FormUi();

		$h = '';

		if(GET('message') === 'FinancialYear::toCreate') {
			$h .= '<div class="util-info">';
				$h .= s("Avant de démarrer, votre ferme a besoin d'un premier exercice comptable. Vous pouvez le créer ci-dessous :");
			$h .= '</div>';
		}

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doCreate', ['id' => 'account-financialYear-create', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups($eFinancialYear, ['startDate*', 'endDate*']);

			$h .= $form->group(
				self::p('accountingType')->label.\util\FormUi::asterisk(),
				$form->radios('accountingType', self::p('accountingType')->values, $eFinancialYear['accountingType'] ?? NULL, attributes: ['mandatory' => TRUE, 'callbackRadioAttributes' => function($option, $key) {
					if(FEATURE_ACCOUNTING_ACCRUAL === FALSE and $key === FinancialYear::ACCRUAL) {
						return ['disabled' => 'disabled'];
					}
					return [];
				}])
			);

			$h .= $form->dynamicGroups($eFinancialYear, ['hasVat*', 'vatFrequency', 'legalCategory*', 'associates*', 'taxSystem*'],  [
				'hasVat*' => function($d) use($form) {
					$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'FinancialYear.changeHasVat(this)'];
				},
				'vatFrequency' => function($d) use($form, $eFinancialYear) {
					$d->group['class'] = ($eFinancialYear['hasVat'] ?? NULL) ? '' : 'hide';
				},
				'legalCategory*' => function($d) use($form, $eFinancialYear) {
					$d->attributes['onclick'] = 'FinancialYear.changeLegalCategory(this)';
					$d->group['class'] = ($eFinancialYear['hasVat'] ?? NULL) ? '' : 'hide';
				},
				'associates*' => function($d) use($form, $eFinancialYear) {
					$d->group['class'] = (($eFinancialYear['legalCategory'] ?? NULL) === \company\CompanySetting::CATEGORIE_GAEC) ? '' : 'hide';
				},
			]);

			$h .= $form->group(
				content: $form->submit(s("Créer l'exercice"))
			);

		$h .= $form->close();

		return $h;

	}

	public function create(\farm\Farm $eFarm, FinancialYear $eFinancialYear): \Panel {

		return new \Panel(
			id: 'panel-account-financialYear-create',
			title: s("Créer un exercice comptable"),
			body: new FinancialYearUi()->createForm($eFarm, $eFinancialYear),
		);

	}

	public function update(\farm\Farm $eFarm, FinancialYear $eFinancialYear): \Panel {

		$form = new \util\FormUi();

		$h = '';

		if($eFinancialYear['nOperation'] > 0) {
			$h .= '<div class="util-block-info">';
				$h .= '<h4>'.s("Attention").'</h4>';
				$h .= s("Certains paramètres ne sont plus modifiables car il y a des écritures comptables enregistrées pour cet exercice.");
			$h .= '</div>';
		}

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doUpdate', ['id' => 'account-financialYear-update', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('id', $eFinancialYear['id']);

			$h .= $form->group(
				self::p('accountingType')->label.\util\FormUi::asterisk(),
				$form->radios('accountingType', self::p('accountingType')->values, $eFinancialYear['accountingType'], attributes: ['mandatory' => TRUE, 'callbackRadioAttributes' => function($option, $key) {
					if(FEATURE_ACCOUNTING_ACCRUAL === FALSE and $key === FinancialYear::ACCRUAL) {
						return ['disabled' => 'disabled'];
					}
					return [];
				}])
			);
			$h .= $form->dynamicGroups($eFinancialYear, ['startDate*', 'endDate*', 'hasVat*', 'vatFrequency*', 'legalCategory*', 'associates*', 'taxSystem*'], [
				'startDate*' => function($d) use($form, $eFinancialYear) {
					if($eFinancialYear['nOperation'] > 0) {
						$d->attributes['disabled'] = 'disabled';
					}
				},
				'endDate*' => function($d) use($form, $eFinancialYear) {
					if($eFinancialYear['nOperation'] > 0) {
						$d->attributes['disabled'] = 'disabled';
					}
				},
					'hasVat*' => function($d) use($form) {
					$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'FinancialYear.changeHasVat(this)'];
				},
				'vatFrequency' => function($d) use($form, $eFinancialYear) {
					$d->group['class'] = $eFinancialYear['hasVat'] ? '' : 'hide';
				},
				'legalCategory*' => function($d) use($form, $eFinancialYear) {
					$d->attributes['onclick'] = 'FinancialYear.changeLegalCategory(this)';
					$d->group['class'] = $eFinancialYear['hasVat'] ? '' : 'hide';
				},
				'associates*' => function($d) use($form, $eFinancialYear) {
					$d->group['class'] = $eFinancialYear['legalCategory'] === \company\CompanySetting::CATEGORIE_GAEC ? '' : 'hide';
				},
			]);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-account-financialYear-update',
			title: s("Modifier un exercice comptable"),
			body: $h
		);

	}

	private function retainedEarnings(\Collection $cOperation, FinancialYear $eFinancialYear, FinancialYear $eFinancialYearPrevious): string {

		$h = '<h3>'.\Asset::icon('1-circle').' '.s("Report des soldes de l'exercice {year}", ['year' => self::getYear($eFinancialYearPrevious)]).'</h3>';

		if($eFinancialYearPrevious->empty()) {

			$h .= '<div class="util-empty">';
				$h .= s("Il n'y a aucune écriture à reprendre.");
			$h .= '</div>';

			return $h;

		}

		if($cOperation->empty()) {

			$h .= '<div class="util-empty">';
				$h .= s("Il n'y a aucune écriture à reprendre, avez-vous bien effectué la saisie comptable de l'exercice précédent ainsi que son bilan de clôture ?");
			$h .= '</div>';

			return $h;
		}

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financialYear-item-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th>'.s("Libellé du compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit").'</th>';
						$h .= '<th>'.s("Libellé écriture").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				$totalDebit = 0;
				$totalCredit = 0;

					foreach($cOperation as $eOperation) {

						$h .= '<tr>';

							$h .= '<td>'.\util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE).'</td>';
							$h .= '<td>'.encode($eOperation['accountLabel']).'</td>';
							$h .= '<td>'.encode($eOperation['account']['description']).'</td>';

							$h .= '<td class="text-end highlight-stick-right">';
								if($eOperation['type'] === \journal\Operation::DEBIT) {
									$h .= \util\TextUi::money(abs($eOperation['amount']));
									$totalDebit += $eOperation['amount'];
								} else {
									$h .= '';
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left">';
								if($eOperation['type'] === \journal\Operation::CREDIT) {
									$h .= \util\TextUi::money($eOperation['amount']);
									$totalCredit += $eOperation['amount'];
								} else {
									$h .= '';
								}
							$h .= '</td>';

							$h .= '<td class="td-min-content">'.new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious).'</td>';

						$h .= '</tr>';

					}

					$h .= '<tr class="tr-bold row-highlight">';

						$h .= '<td></td>';
						$h .= '<td></td>';

						$h .= '<td>'.s("Totaux").'</td>';

						$h .= '<td class="text-end highlight-stick-right">';
							$h .= \util\TextUi::money(abs($totalDebit));
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-left">';
							$h .= \util\TextUi::money($totalCredit);
						$h .= '</td>';

						$h .= '<td></td>';

					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	private function result(\Collection $cOperationResult, FinancialYear $eFinancialYear, FinancialYear $eFinancialYearPrevious): string {

		$h = '<h3>'.\Asset::icon('2-circle').' '.s("Enregistrement et affectation automatique du résultat de l'exercice {year}", ['year' => self::getYear($eFinancialYearPrevious)]).'</h3>';

		if($cOperationResult->empty()) {
			return $h.'<div class="util-empty">'.s("Il n'y a rien à enregistrer").'</div>';
		}



		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financialYear-item-table tr-even tr-hover">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th>'.s("Libellé du compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit").'</th>';
						$h .= '<th>'.s("Libellé écriture").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cOperationResult as $eOperationResult) {

						$h .= '<tr>';

							$h .= '<td>'.\util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE).'</td>';
							$h .= '<td>'.encode($eOperationResult['accountLabel']).'</td>';
							$h .= '<td>'.encode($eOperationResult['account']['description']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">';
								if($eOperationResult['type'] === \journal\Operation::DEBIT) {
									$h .= \util\TextUi::money($eOperationResult['amount']);
								} else {
									$h .= '';
								}
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-left">';
								if($eOperationResult['type'] === \journal\Operation::CREDIT) {
									$h .= \util\TextUi::money($eOperationResult['amount']);
								} else {
									$h .= '';
								}
							$h .= '</td>';
							$h .= '<td>'.encode($eOperationResult['description']).'</td>';

						$h .= '</tr>';

					}
				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	private function reversal(\util\FormUi $form, \Collection $cJournalCode, \Collection $ccOperation, FinancialYear $eFinancialYear, FinancialYear $eFinancialYearPrevious): string {

		$h = '<h3>'.\Asset::icon('3-circle').' '.s("Extournes de l'exercice {year}", ['year' => self::getYear($eFinancialYearPrevious)]).'</h3>';

		if($eFinancialYearPrevious->empty()) {

			$h .= '<div class="util-empty">'.s("Il n'y a aucune écriture à extourner").'</div>';

			return $h;
		}

		if($cJournalCode->empty()) {

			$h .= '<div class="util-info">'.s("Vous n'avez aucun journal extournable. Il n'y a donc aucune écriture à extourner.").'</div>';

			return $h;

		}

		$hasExtournableOperations = $ccOperation->find(fn($cOperation) => $cOperation->notEmpty())->notEmpty();

		if($hasExtournableOperations === FALSE) {

			$h .= '<div class="util-info">'.s("Aucun journal extournable n'a d'écriture à extourner.").'</div>';
			return $h;

		}

		$h .= '<div class="util-block-help">'.s("Sélectionnez les journaux dont les écritures doivent être extournées").'</div>';

		foreach($cJournalCode as $eJournalCode) {

			$h .= '<h4>';
				$journal = new \journal\JournalCodeUi()->getColoredName($eJournalCode);
				$title = s("Journal {value}", $journal);
				$h .= $form->checkbox('journalCode[]', $eJournalCode['id'], ['callbackLabel' => fn($input) => $input.' '.$title]);
			$h .= '</h4>';

			$cOperation = $ccOperation[$eJournalCode['id']] ?? new \Collection();

			if($cOperation->empty()) {

				$h .= '<div class="util-info">';
					$h .= s("Il n'y a aucune écriture à extourner dans ce journal.");
				$h .= '</div>';

				continue;

			}

			$h .= '<div class="stick-sm util-overflow-sm">';

				$h .= '<table class="financialYear-item-table tr-even tr-hover">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Numéro de compte").'</th>';
							$h .= '<th>'.s("Libellé du compte").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Débit").'</th>';
							$h .= '<th class="text-end highlight-stick-left">'.s("Crédit").'</th>';
							$h .= '<th>'.s("Libellé écriture").'</th>';

						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

					$totalDebit = 0;
					$totalCredit = 0;

						foreach($cOperation as $eOperation) {

							$h .= '<tr>';

								$h .= '<td>'.\util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE).'</td>';
								$h .= '<td>'.encode($eOperation['accountLabel']).'</td>';
								$h .= '<td>'.encode($eOperation['account']['description']).'</td>';

								$h .= '<td class="text-end highlight-stick-right">';
									if($eOperation['type'] === \journal\Operation::DEBIT) {
										$h .= \util\TextUi::money($eOperation['amount']);
										$totalDebit += $eOperation['amount'];
									} else {
										$h .= '';
									}
								$h .= '</td>';

								$h .= '<td class="text-end highlight-stick-left">';
									if($eOperation['type'] === \journal\Operation::CREDIT) {
										$h .= \util\TextUi::money($eOperation['amount']);
										$totalCredit += $eOperation['amount'];
									} else {
										$h .= '';
									}
								$h .= '</td>';

								$h .= '<td>'.new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious).'</td>';

							$h .= '</tr>';

						}

						$h .= '<tr class="tr-bold row-highlight">';

							$h .= '<td></td>';
							$h .= '<td></td>';

							$h .= '<td>'.s("Totaux").'</td>';

							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($totalDebit);
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left">';
								$h .= \util\TextUi::money($totalCredit);
							$h .= '</td>';

							$h .= '<td></td>';

						$h .= '</tr>';

					$h .= '</tbody>';

				$h .= '</table>';
			$h .= '</div>';
		}

		return $h;

	}

	public function open(
		\farm\Farm $eFarm,
		FinancialYear $eFinancialYear,
		FinancialYear $eFinancialYearPrevious,
		\Collection $cOperation,
		\Collection $cOperationResult,
		\Collection $cJournalCode,
		\Collection $ccOperationReversed,
	): string {

		$form = new \util\FormUi();

		$h  = $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doOpen');

			$h .= $form->hidden('id', $eFinancialYear['id']);

			$h .= $this->retainedEarnings($cOperation, $eFinancialYear, $eFinancialYearPrevious);

			$h .= $this->result($cOperationResult, $eFinancialYear, $eFinancialYearPrevious);

			if($eFinancialYear->isCashAccounting() === FALSE) {
				$h .= $this->reversal($form, $cJournalCode, $ccOperationReversed, $eFinancialYear, $eFinancialYearPrevious);
			}


			if($eFinancialYearPrevious->empty()) {

				$h .= $form->submit(s("Ouvrir l'exercice"), ['data-waiter' => s("Ouverture en cours...")]);

			} else {

				$h .= $form->submit(s("Générer les écritures et le bilan d'ouverture"), ['data-waiter' => s("Ouverture en cours...")]);

			}

		$h .= $form->close();

		return $h;

	}

	public function getOpeningDescription(FinancialYear $eFinancialYear): string {
		return s("À nouveau exercice {value}", $eFinancialYear->getLabel());
	}
	public function getOpeningResult(FinancialYear $eFinancialYear): string {
		return s("Résultat exercice {value}", $eFinancialYear->getLabel());
	}
	public function getOpeningAffectResult(FinancialYear $eFinancialYear): string {
		return s("Affectation résultat exercice {value}", $eFinancialYear->getLabel());
	}

	public function close(\farm\Farm $eFarm, FinancialYear $eFinancialYear, \Collection $cDeferral, \Collection $cAssetGrant, \Collection $cAsset, array $accountsToSettle): string {

		$form = new \util\FormUi();

		$h = '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Exercice").'</dt>';
				$h .= '<dd>'.self::getYear($eFinancialYear).' ('.s("du {startDate} au {endDate}", [
					'startDate' => \util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE),
					'endDate' => \util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE),
				]).')</dd>';

				$h .= '<dt>'.self::p('hasVat')->label.'</dt>';
				$h .= '<dd>'.($eFinancialYear['hasVat'] ? s("Oui") : s("Non")).'</dd>';

				$h .= '<dt>'.self::p('taxSystem')->label.'</dt>';
				$h .= '<dd>'.self::p('taxSystem')->values[$eFinancialYear['taxSystem']].'</dd>';

				$h .= '<dt>'.self::p('vatFrequency')->label.'</dt>';
				$h .= '<dd>'.($eFinancialYear['vatFrequency'] ? self::p('vatFrequency')->values[$eFinancialYear['vatFrequency']] : '').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doClose', ['id' => 'account-financialYear-close', 'autocomplete' => 'off']);

			if($accountsToSettle['farmersAccount'] < 0.0) {
				$h .= '<div class="util-danger">';
					$h .= '<h3>'.s("Comptes de l'exploitant").'</h3>';
					$h .= s("Attention, le compte {farmersAccount} est débiteur de {amount}. Soldez-le avant la clôture de l'exercice !", ['farmersAccount' => '<b>'.AccountSetting::FARMER_S_ACCOUNT_CLASS.'</b>', 'amount' => \util\TextUi::money(abs($accountsToSettle['farmersAccount']))]);
				$h .= '</div>';
			}
			if(array_reduce($accountsToSettle['waitingAccounts'], function($sum, $waitingAccount) {
				return $waitingAccount['total'] + $sum;
			}, 0) > 0.0) {
				$h .= '<div class="util-danger">';
					$h .= '<h3>'.s("Comptes d'attente").'</h3>';
					$h .= s("Attention, un ou plusieurs comptes d'attente, dont le numéro de compte commence par <b>{accounts}</b>, ne sont pas soldés. Soldez-les avant la clôture de l'exercice !", ['accounts' => join('</b>, <b>', AccountSetting::WAITING_ACCOUNT_CLASSES)]);
				$h .= '</div>';

			}

			$step = 0;

			// Visualisation des amortissements
			if($cAsset->notEmpty()) {
				$step++;
				$h .= new \asset\AssetUi()->listForClosing($eFarm, $eFinancialYear, $cAsset, $step);
			}

			// Visualisation des subventions
			if($cAssetGrant->notEmpty()) {
				$step++;
				$h .= new \asset\AssetUi()->listGrantsForClosing($eFarm, $eFinancialYear, $cAssetGrant, $step);
			}

			if($eFinancialYear->isCashAccounting() === FALSE) {

				$step++;
				$h .= '<h3 class="mt-2">'.\Asset::icon($step.'-circle').' '.s("Factures Non Parvenues (FNP ou Charges à payer) et Factures à Établir (FAE ou Produits à Recevoir, PAR)").'</h3>';

				$h .= '<div class="util-block-help">';
					$h .= s("Pour comptabiliser vos FNP et FAE, saisissez-les sur l'exercice à imputer en utilisant les comptes suivants, respectivement, si c'est une FNP ou une FAE : ");
					$h .= '<ul>';
						$h .= '<li>'.s("Le compte <b>{charge}</b> (au crédit) ou <b>{product}</b> (au débit)", ['charge' => AccountSetting::CHARGE_ACCOUNT_CLASS, 'product' => AccountSetting::PRODUCT_ACCOUNT_CLASS]).'</li>';
						$h .= '<li>'.s("Les comptes de TVA <b>{charge}</b> (au crédit) ou <b>{product}</b> (au débit)", ['charge' => AccountSetting::VAT_CHARGES_TO_PAY_CLASS, 'product' => AccountSetting::VAT_CHARGES_TO_COLLECT_CLASS]).'</li>';
						$h .= '<li>'.s("Les comptes de contrepartie <b>{charge}</b> (au débit) ou <b>{product}</b> (au crédit)", [
							'charge' => join(', ', [AccountSetting::THIRD_ACCOUNT_SUPPLIER_TO_PAY_CLASS, AccountSetting::EMPLOYEE_TO_PAY_CLASS, AccountSetting::SOCIAL_TO_PAY_CLASS, AccountSetting::TAXES_TO_PAY_CLASS, AccountSetting::INTERESTS_TO_PAY_CLASS]),
							'product' => join(', ', [AccountSetting::THIRD_ACCOUNT_RECEIVABLE_TO_GET_CLASS, AccountSetting::SOCIAL_TO_GET_CLASS, AccountSetting::TAXES_TO_GET_CLASS, AccountSetting::INTERESTS_TO_GET_CLASS])
						]).'</li>';
					$h .= '</ul>';
					$h .= s("En les ajoutant dans un journal dit \"extournable\", lors de l'ouverture de l'exercice suivant, toutes les opérations de contrepassation seront automatiquement reprises.");
				$h .= '</div>';

				// Étape PCA et CCA
				$step++;
				$h .= new \journal\DeferralUi()->listForClosing($eFarm, $cDeferral, $step);

			}

			$step++;
			$h .= '<h3 class="mt-2">'.\Asset::icon($step.'-circle').' '.s("Confirmer la clôture de l'exercice comptable {year}", ['year' => self::getYear($eFinancialYear)]).'</h3>';

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('id', $eFinancialYear['id']);

			if($eFinancialYear['cImport']->notEmpty() and $eFinancialYear['cImport']->find(fn($e) => in_array($e['status'], [Import::WAITING, Import::IN_PROGRESS, Import::FEEDBACK_TO_TREAT, Import::FEEDBACK_REQUESTED]))->notEmpty()) {
				$h .= '<div class="util-block-danger">';
					$h .= s("Attention, un import de fichier FEC est en cours (non terminé, non annulé). En clôturant l'exercice, cet import sera annulé.");
					$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/fec:import" class="btn btn-transparent ml-1">'.s("Voir l'import").'</a>';
				$h .= '</div>';
			}

			$h .= '<div>'.$form->submit(
				s("Clôturer l'exercice comptable {year}", ['year' => self::getYear($eFinancialYear)]), ['data-waiter' => s("Clôture en cours...")],
			).'</div>';

		$h .= $form->close();

		return $h;

	}

	public static function getYear(\account\FinancialYear $eFinancialYear): string {

		if($eFinancialYear->empty()) {
			return '';
		}

		if(substr($eFinancialYear['startDate'], 0, 4) === substr($eFinancialYear['endDate'], 0, 4)) {
			return substr($eFinancialYear['startDate'], 0, 4);
		}

		return substr($eFinancialYear['startDate'], 0, 4).' - '.substr($eFinancialYear['endDate'], 0, 4);

	}

	public function view(\farm\Farm $eFarm, FinancialYear $eFinancialYear): string {

		\Asset::css('account', 'financialYear.css');

		$h = '<div class="financial-year-block'.(GET('id', 'int') === $eFinancialYear['id'] ? ' financial-year-highlight' : '').'">';

			$h .= '<div class="financial-year-title">';

				$h .= '<div class="financial-year-dates" financial-year-dates>';
					$h .= \util\DateUi::numeric($eFinancialYear['startDate']).'<div class="mb-1 mt-1">'.\Asset::icon('arrow-down-circle-fill').'</div>'.\util\DateUi::numeric($eFinancialYear['endDate']);
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="financial-year-details">';

				$action = '';

				if($eFinancialYear->acceptImportFec()) {
					$action .= '<a href="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/fec:import" class="btn btn-primary btn-md">';
						if($eFinancialYear['cImport']->notEmpty()) {
							$action .= s("Import FEC");
						} else {
							$action .= s("Importer un fichier FEC");
						}
					$action .= '</a>';
				}

				if($eFinancialYear['status'] === FinancialYearElement::OPEN) {

					if($eFinancialYear['closeDate'] !== NULL) {

						$icon = '<span class="color-danger">'.\Asset::icon('unlock-fill').'</span>';
						$label = '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Rouvert").'</span>';

						if($eFinancialYear->acceptReClose()) {
							$action .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/:doReclose" post-id="'.$eFinancialYear['id'].'" class="btn btn-outline-danger btn-md">';
								$action .= s("Refermer");
							$action .= '</a>';
						}

					} else if($eFinancialYear['openDate'] === NULL) {

						$icon = \Asset::icon('clock-history');
						$label = s("En attente d'ouverture");

						if($eFinancialYear->acceptOpen()) {
							$action = '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:open?id='.$eFinancialYear['id'].'" class="btn btn-secondary btn-md">';
								$action .= s("Réaliser le bilan d'ouverture");
							$action .= '</a>';
						}

					} else {

						$icon = \Asset::icon('play-fill');
						$label = s("En cours");

						if($eFinancialYear->isOpen() and $eFinancialYear->acceptClose() and $eFinancialYear['endDate'] < date('Y-m-d')) {
							$action = '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:close?id='.$eFinancialYear['id'].'" class="btn btn-secondary btn-md">';
								$action .= s("Réaliser le bilan de clôture");
							$action .= '</a>';
						}
					}

				} else {

					$icon = \Asset::icon('lock-fill');
					$label = s("Clôturé");

					/*if($eFinancialYear->acceptReOpen()) {
						$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/:doReopen" post-id="'.$eFinancialYear['id'].'" data-confirm="'.s("Voulez-vous réellement rouvrir cet exercice ? N'effectuez cette action que si vous maîtrisez ce que vous faites et, de préférence, pour une durée limitée.").'" class="dropdown-item">';
							$h .= '<span class="bg-danger btn color-white">'.\Asset::icon('exclamation-triangle').' '.s("Rouvrir").'</span>';
						$h .= '</a>';
					}*/
				}

				$h .= '<div class="financial-year-details-element">';
					$h .= '<div class="financial-year-detail-title">';
						$h .= '<h3>'.$icon.' '.$label.'</h3>';
						$h .= '<div class="financial-year-buttons">';

							if($eFinancialYear->acceptUpdate()) {

								$h .= '<a class="btn btn-primary" href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:update?id='.$eFinancialYear['id'].'">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a>';

							}

						$h .= '</div>';
					$h .= '</div>';

					$h .= '<div class="financial-year-actions">';

						$h .= $action;

						if($eFinancialYear->acceptImportFec()) {

							$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/fec:import" class="btn btn-primary btn-md">';
								$h .= s("Importer un fichier FEC");
							$h .= '</a>';

						}

					$h .= '</div>';
				$h .= '</div>';
				$h .= '<div class="financial-year-details-more">';

					$h .= '<ul class="mt-1">';

						$h .= '<li>';
							$h .= s("Régime {value}", '<b>'.self::p('taxSystem')->values[$eFinancialYear['taxSystem']].'</b>');
						$h .= '</li>';

						$h .= '<li>';
							$h .= $eFinancialYear['hasVat'] ? s("Redevable de la TVA") : s("Non redevable de la TVA");
						$h .= '</li>';

						if($eFinancialYear['hasVat']) {
							$h .= '<li>';
								$h .= s("Déclaration de TVA {value}", '<b>'.self::p('vatFrequency')->values[$eFinancialYear['vatFrequency']].'</b>');
							$h .= '</li>';
						}
					$h .= '</ul>';

					$h .= '<div class="util-buttons">';

						$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/etats-financiers/declaration-de-tva" class="util-button">';
							$h .= '<h5>'.s("Déclarations de TVA").'</h5>';
							$h .= \Asset::icon('pencil');
						$h .= '</a>';

						$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/etats-financiers/tresorerie" class="util-button">';
							$h .= '<h5>'.s("Trésorerie").'</h5>';
							$h .= \Asset::icon('bank');
						$h .= '</a>';
					$h .= '</div>';

				$h .= '</div>';

				if($eFinancialYear['cImport']->notEmpty()) {

					foreach($eFinancialYear['cImport'] as $eImport) {

						$h .= '<div class="financial-year-details-element">';

							if($eImport['status'] === Import::DONE) {

								$h .= '<div>';
									$h .= \Asset::icon('journal-check');
									$h .= ' ';
									$h .= s("Import FEC terminé");
								$h .= '</div>';

							} else if(in_array($eImport['status'], [Import::WAITING, Import::FEEDBACK_TO_TREAT, Import::IN_PROGRESS])) {

								$h .= '<div>';
									$h .= \Asset::icon('journal-arrow-up');
									$h .= ' ';
									$h .= s("Import FEC en cours");
								$h .= '</div>';
								$h .= '<div>';
									$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/fec:import" class="btn btn-primary btn-md">';
										$h .= s("Voir l'import");
									$h .= '</a>';
								$h .= '</div>';

							} else if($eImport['status'] === Import::FEEDBACK_REQUESTED) {

								$h .= '<div>';
									$h .= \Asset::icon('exclamation-triangle');
									$h .= ' ';
									$h .= s("Action en attente pour l'import FEC");
								$h .= '</div>';
								$h .= '<div>';
									$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm, $eFinancialYear).'/financialYear/fec:import" class="btn btn-warning btn-md">';
										$h .= s("Voir l'import");
									$h .= '</a>';
								$h .= '</div>';

							}
						$h .= '</div>';

					}

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = FinancialYear::model()->describer($property, [
			'startDate' => s("Date de début"),
			'endDate' => s("Date de fin"),
			'hasVat' => s("Êtes-vous redevable de la TVA ?"),
			'vatFrequency' => s("Fréquence de déclaration de TVA"),
			'taxSystem' => s("Régime fiscal"),
			'accountingType' => s("Type de comptabilité"),
			'legalCategory' => s("Catégorie juridique"),
			'associates' => s("Nombre d'associé·e·s"),
		]);

		switch($property) {

			case 'accountingType' :
				$accrual = '<h4>'.s("Comptabilité à l'engagement").'</h4>';
				$accrual .= '<ul>';
					$accrual .= '<li>'.s("Généralement choisie lorsque l'exploitation est imposée au régime réel.").'</li>';
				$accrual .= '</ul>';
				$accrual .= '<p>'.\Asset::icon('arrow-right').' <i>'.s("Idéal pour tenir une comptabilité complète.").'</i></p>';
				if(FEATURE_ACCOUNTING_ACCRUAL === FALSE) {
					$accrual .= '<p>'.\Asset::icon('exclamation-triangle').' <i>'.s("Cette option n'est pas encore disponible.").'</i></p>';
				}

				$cash = '<h4>'.s("Comptabilité de trésorerie").'</h4>';
				$cash .= '<ul>';
					$cash .= '<li>'.s("Généralement lorsque l'exploitation est imposée au régime du micro-BA ou au réel simplifié").'</li>';
				$cash .= '</ul>';
				$cash .= '<p>'.\Asset::icon('arrow-right').' <i>'.s("Idéal pour tenir une comptabilité de façon simple.").'</i></p>';

				$d->values = [
					FinancialYear::CASH => $cash,
					FinancialYear::ACCRUAL => $accrual,
				];

				break;

			case 'startDate' :
			case 'endDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'hasVat' :
				$d->field = 'yesNo';
				break;

			case 'vatFrequency' :
				$d->values = [
					FinancialYear::MONTHLY => s("Mensuelle"),
					FinancialYear::QUARTERLY => s("Trimestrielle"),
					FinancialYear::ANNUALLY => s("Annuelle"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'taxSystem':
				$d->values = [
					FinancialYear::MICRO_BA => s("Micro-BA"),
					FinancialYear::BA_REEL_SIMPLIFIE => s("Réel simplifié agricole"),
					FinancialYear::BA_REEL_NORMAL => s("Réel normal agricole"),
					FinancialYear::AUTRE_BIC => s("BIC"),
					FinancialYear::AUTRE_BNC => s("BNC"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'legalCategory':
				$d->field = 'select';
				$d->values = [
					\company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL => s("Entreprise individuelle"),
					6598 => s("Exploitation agricole à responsabilité limitée (EARL)"),
					\company\CompanySetting::CATEGORIE_GAEC => s("Groupement agricole d'exploitation en commun (GAEC)"),
					5500 => s("Société Anonyme"),
					5710 => s("SAS"),
					6597 => s("Société civile d'exploitation agricole (SCEA)"),
					6317 => s("Société coopérative agricole (SCA)"),
					2200 => s("Société de fait"),
					2300 => s("Société en participation"),
					6318 => s("Union de sociétés coopératives agricoles"),
					9999 => s("Autre structure juridique"),
				];
				break;

			case 'associates':
				$d->field = 'select';
				$d->attributes['mandatory'] = TRUE;
				$d->values = [];
				for($i = 1; $i < 10; $i++) {
					$d->values[$i] = $i;
				}
				break;

			case 'status':
				$d->values = [
					FinancialYear::OPEN => \Asset::icon('unlock').' '.s("Ouvert"),
					FinancialYear::CLOSE => \Asset::icon('lock').' '.s("Clôturé"),
				];
				break;
		}

		return $d;

	}
}

?>
