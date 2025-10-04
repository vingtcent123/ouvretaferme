<?php
namespace journal;

Class VatUi {

	public function __construct() {
		\Asset::css('journal', 'vat.css');
		\Asset::css('company', 'company.css');
	}

	public function getTitle(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, bool $hasWaitingOperations): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Les journaux de TVA");
			$h .= '</h1>';

			$h .= '<div>';

				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#vat-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';

				if(
					$eFinancialYear['status'] === \account\FinancialYearElement::OPEN
					and $eFarm->canManage()
					and $hasWaitingOperations
				) {
					$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/vatDeclaration:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer une déclaration").'</a> ';
				}

		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYearSelected, ?\account\ThirdParty $eThirdParty): string {

		$h = '<div id="vat-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH.'?financialYear='.$eFinancialYearSelected['id'];

		$statuses = OperationUi::p('type')->values;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

			$h .= '<div>';
					$h .= $form->month('date', $search->get('date'), ['placeholder' => s("Mois")]);
					$h .= $form->text('accountLabel', $search->get('accountLabel'), ['placeholder' => s("Classe de compte")]);
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Description")]);
					$h .= $form->select('type', $statuses, $search->get('type'), ['placeholder' => s("Type")]);
					$h .= $form->dynamicField(new Operation(['thirdParty' => $eThirdParty]), 'thirdParty', function($d) use($form) {
						$d->autocompleteDispatch = '[data-third-party="form-search"]';
						$d->attributes['data-index'] = 0;
						$d->attributes['data-third-party'] = 'form-search';
					});
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Pièce comptable")]);
					$h .= $form->checkbox('cashflowFilter', 1, ['checked' => $search->get('cashflowFilter'), 'callbackLabel' => fn($input) => $input.' '.s("Filtrer les écritures non rattachées")]);
				$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	private static function getMonthTotal(?string $currentMonth, array $totals): string {

		$h = '<tr class="row-bold tr-border-top">';

			$h .= '<td colspan="2">';
				$h .= s("Total TVA");
			$h .= '</td>';

			$h .= '<td>';
				if($currentMonth !== NULL) {
					$h .= s("Mois de {month}", ['month' => \util\DateUi::textual($currentMonth.'-01', \util\DateUi::MONTH_YEAR)]);
				} else {
					$h .= s("Total de la période");
				}
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

			$h .= '<td class="text-end highlight-stick-right">';
			$h .= \util\TextUi::money($totals['withVat']);
			$h .= '</td>';

			$h .= '<td class="text-end highlight-stick-left">';
				$h .= \util\TextUi::money($totals['withoutVat']);
			$h .= '</td>';

			$h .= '<td class="text-end highlight-stick-right">';
				$h .= \util\TextUi::money($totals['vat']);
			$h .= '</td>';

		$h .= '</tr>';

		return $h;
	}

	public function getJournal(
		\farm\Farm $eFarm,
		\account\FinancialYear $eFinancialYear,
		array $operations,
		array $vatDeclarationData,
		?array $currentVatPeriod,
		\Search $search = new \Search(),
	): string {

		if($operations['buy']->empty() === TRUE and $operations['sell']->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-info">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		$h = '<div class="tabs-h" id="journal-vat" onrender="'.encode('Lime.Tab.restore(this, "vat-buy")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="vat-buy" onclick="Lime.Tab.select(this)">'.s("Achats").'</a>';
				$h .= '<a class="tab-item" data-tab="vat-sell" onclick="Lime.Tab.select(this)">'.s("Ventes").'</a>';
				$h .= '<a class="tab-item" data-tab="vat-statement" onclick="Lime.Tab.select(this)">'.s("Déclarations").'</a>';
			$h .= '</div>';
			$h .= '<div class="tab-panel" data-tab="vat-buy">';
				$h .= $this->getTableContainer($eFarm, $eFinancialYear, $operations['buy'], 'buy', $search);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="vat-sell">';
				$h .= $this->getTableContainer($eFarm, $eFinancialYear, $operations['sell'], 'sell', $search);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="vat-statement">';
				$h .= $this->getStatementContainer($eFarm, $vatDeclarationData, $eFinancialYear, $currentVatPeriod);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function getStatementContainer(\farm\Farm $eFarm, array $vatDeclarationData, \account\FinancialYear $eFinancialYear, ?array $currentVatPeriod): string {

		$cVatDeclaration = $vatDeclarationData['cVatDeclaration'];
		$cOperationWaiting = $vatDeclarationData['cOperationWaiting'];

		$h = '';

		$h .= '<h3>'.s("Déclarations de TVA").'</h3>';

		$h .= self::getVatDeclarations($eFinancialYear, $eFarm, $cVatDeclaration, $currentVatPeriod);

		if($cOperationWaiting->count() > 0) {

			$h .= '<h3>'.s("Écritures en attente de déclaration").'</h3>';

			$h .= '<div class="util-info">'.p(
					"<b>{value}</b> écriture n'a pas encore été intégrée dans une déclaration de TVA.",
					"<b>{value}</b> écritures n'ont pas encore été intégrées dans une déclaration de TVA.",
					$cOperationWaiting->count()
				).'</div>';

			$h .= new JournalUi()->getTableContainer(
				$eFarm, journalCode: '', cOperation: $cOperationWaiting, eFinancialYearSelected: $eFinancialYear,
				hide: ['actions', 'document'],
				show: ['vatAdjustement', 'period' => $eFinancialYear['lastPeriod']],
			);

		}

		return $h;

	}

	private function getVatDeclarations(\account\FinancialYear $eFinancialYear, \farm\Farm $eFarm, \Collection $cVatDeclaration, ?array $currentVatPeriod): string {

		$currentText = $currentVatPeriod !== NULL
			? s("La prochaine période à déclarer commence le {startDate} et se termine le {endDate}.", [
				'startDate' => '<b>'.\util\DateUi::numeric($currentVatPeriod['start']).'</b>',
				'endDate' => '<b>'.\util\DateUi::numeric($currentVatPeriod['end']).'</b>',
			])
		 : '';

		if($cVatDeclaration->count() === 0) {

			return '<div class="util-info">'.s("Vous n'avez pas encore déclaré la TVA pendant cet exercice comptable.").'<br />'.$currentText.'</div>'
				.'<div class="util-block-help">'
					.s("La TVA peut être déclarée une fois la période révolue.<br />Pour mettre à jour la fréquence de déclaration, rendez-vous dans Comptabilité > Paramétrage > Les exercices comptables > <link>Modifier</link>.",
					['link' => '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:update?id='.$eFinancialYear['id'].'">'])
				.'</div>';

		}

		$h = '<div class="util-info">'.$currentText.'</div>';

		$h .= '<table class="tr-even td-vertical-top tr-hover table-bordered">';

			$h .= '<thead class="thead-sticky">';
				$h .= '<tr>';
					$h .= '<th>'.s("Période").'</th>';
					$h .= '<th>'.s("Date de validation").'</th>';
					$h .= '<th class="text-end">'.s("TVA collectée").'</th>';
					$h .= '<th class="text-end">'.s("TVA déductible").'</th>';
					$h .= '<th class="text-end">'.s("TVA due").'</th>';
					$h .= '<th>'.s("Type").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
				foreach($cVatDeclaration as $eVatDeclaration) {

					$h .= '<tr>';
						$h .= '<td>'.s("{startDate} au {endDate}", ['startDate' => \util\DateUi::numeric($eVatDeclaration['startDate']), 'endDate' => \util\DateUi::numeric($eVatDeclaration['endDate'])]).'</td>';
						$h .= '<td>'.\util\DateUi::numeric($eVatDeclaration['createdAt'], \util\DateUi::DATE).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['collectedVat']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['deductibleVat']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['dueVat']).'</td>';
						$h .= '<td>'.VatDeclarationUi::p('type')->values[$eVatDeclaration['type']].'</td>';
						$h .= '<td><a href="'.PdfUi::urlVatDeclaration($eFarm, $eVatDeclaration).'" data-ajax-navigation="never" >'.\Asset::icon('file-pdf').' '.s("Télécharger en PDF").'</a></td>';
					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		return $h;
	}

	private function getTableContainer(
		\farm\Farm $eFarm,
		\account\FinancialYear $eFinancialYear,
		\Collection $cccOperation,
		string $type,
		\Search $search = new \Search(),
	): string {

		if($cccOperation->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-info">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/vat:pdf?type='.$type.'&financialYear='.$eFinancialYear['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('file-pdf').'&nbsp;'.s("Télécharger en PDF").'</a>';

			$h .= $this->getTables($eFarm, $cccOperation, $search);

		$h .= '</div>';

		return $h;
	}

	public function getTables(
		\farm\Farm $eFarm,
		\Collection $cccOperation,
		\Search $search = new \Search(),
		string $for = 'web',
	): string {

		$h = '';

		$lastAccountLabel = NULL;
		foreach($cccOperation as $currentAccountLabel => $ccOperation) {

			$eOperation = $ccOperation->first()->first();

			if($lastAccountLabel === NULL or $currentAccountLabel !== $lastAccountLabel) {

				$h .= '<div class="vat-account-title">';
					$h .= '<div class="title">'.s("Compte").'</div>';
					$h .= '<div class="label">'.encode($eOperation['accountLabel']).'&nbsp;-&nbsp;'.encode($eOperation['account']['description']).'</div>';
				$h .= '</div>';

				$lastAccountLabel = $currentAccountLabel;

			}

			$bigTotals = [
				'withVat' => 0,
				'withoutVat' => 0,
				'vat' => 0,
			];
			$h .= '<table class="td-vertical-top tbody-hover table-'.$for.' no-background">';

				$h .= '<thead '.($for === 'web' ? 'class="thead-sticky"' : '').'>';
					$h .= '<tr '.($for === 'pdf' ? 'class="row-header row-upper"' : '').'>';
						$h .= '<th>';
							$label = s("Date");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('date', $label) : $label);
						$h .= '</th>';
						$h .= '<th>';
							$label = s("Pièce comptable");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('document', $label) : $label);
						$h .= '</th>';
						$h .= '<th>';
							$label = s("Description");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('description', $label) : $label);
						$h .= '</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th class="td-min-content text-end rowspaned-center">'.s("Taux TVA").'</th>';
						$h .= '<th class="text-end td-min-content highlight-stick-right rowspaned-center">'.s("Montant (TTC)").'</th>';
						$h .= '<th class="text-end td-min-content highlight-stick-left rowspaned-center">'.s("Montant (HT)").'</th>';
						$h .= '<th class="text-end td-min-content highlight-stick-right rowspaned-center">'.s("TVA").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$currentMonth = NULL;

				$monthTotals = [
					'withVat' => 0,
					'withoutVat' => 0,
					'vat' => 0,
				];

				foreach($ccOperation as $month => $cOperation) {

					foreach($cOperation as $eOperation) {

						if($currentMonth !== NULL and $currentMonth !== $month) {

							$monthTotals = [
								'withVat' => 0,
								'withoutVat' => 0,
								'vat' => 0,
							];
						}

						$currentMonth = $month;

						$eOperationInitial = $eOperation['operation'];
						if(
							str_starts_with($eOperation['accountLabel'], \account\AccountSetting::VAT_BUY_CLASS_PREFIX)
						and $eOperationInitial['type'] === OperationElement::CREDIT) {
							$multiplyer = -1;
						} else {
							$multiplyer = 1;
						}
						$monthTotals['withVat'] += $multiplyer * $eOperationInitial['amount'] + $multiplyer * $eOperation['amount'];
						$monthTotals['withoutVat'] += $multiplyer * $eOperationInitial['amount'];
						$monthTotals['vat']+= $multiplyer * $eOperation['amount'];

						$h .= '<tbody>';
							$h .= '<tr class="tr-border-top">';

								$h .= '<td '.($for === 'pdf' ? 'class="text-small"' : '').'>';
									$h .= \util\DateUi::numeric($eOperationInitial['date']);
								$h .= '</td>';

								$h .= '<td>';
									$h .= '<div class="operation-info">';
										if($eOperationInitial['document'] !== NULL) {
											$h .= '<a href="'.new JournalUi()->getBaseUrl($eFarm, $eOperationInitial['financialYear']).'&document='.urlencode($eOperationInitial['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperationInitial['document']).'</a>';
										}
									$h .= '</div>';
								$h .= '</td>';

								$h .= '<td class="td-description">';
									$h .= '<div class="description">';
										$h .= encode($eOperationInitial['description']);
									$h .= '</div>';
								$h .= '</td>';

								$h .= '<td>';
									if($eOperationInitial['thirdParty']->exists() === TRUE) {
										$h .= encode($eOperationInitial['thirdParty']['name']);
									}
								$h .= '</td>';

								$h .= '<td class="td-min-content text-end">';
										$h .= $eOperationInitial['vatRate'];
								$h .= '</td>';

								$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
										$h .= \util\TextUi::money($multiplyer * ($eOperationInitial['amount'] + $eOperation['amount']));
								$h .= '</td>';

								$h .= '<td class="text-end td-min-content highlight-stick-left td-vertical-align-top">';
										$h .= \util\TextUi::money($multiplyer * $eOperationInitial['amount']);
								$h .= '</td>';

								$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
										$h .= \util\TextUi::money($multiplyer * $eOperation['amount']);
								$h .= '</td>';

							$h .= '</tr>';

						$h .= '</tbody>';
					}

					$bigTotals['withVat'] += $monthTotals['withVat'];
					$bigTotals['withoutVat'] += $monthTotals['withoutVat'];
					$bigTotals['vat'] += $monthTotals['vat'];

					$h .= self::getMonthTotal($currentMonth, $monthTotals);

				}

				$h .= self::getMonthTotal(NULL, $bigTotals);

			$h .= '</table>';
		}
		return $h;
	}
}
