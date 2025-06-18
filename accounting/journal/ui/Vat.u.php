<?php
namespace journal;

Class VatUi {

	public function __construct() {
		\Asset::css('journal', 'vat.css');
	}

	public function getTitle(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Les journaux de TVA");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#vat-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \accounting\FinancialYear $eFinancialYearSelected, ?ThirdParty $eThirdParty): string {

		$h = '<div id="vat-search" class="util-block-search stick-xs '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

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

		$h = '<tr class="row-bold">';

			$h .= '<td>';
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

			$h .= '<td>';
				$h .= s("Total TVA");
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

			$h .= '<td>';
				if($currentMonth !== NULL) {
					$h .= s("Mois de {month}", ['month' => \util\DateUi::textual($currentMonth.'-01', \util\DateUi::MONTH_YEAR)]);
				} else {
					$h .= s("Total de la période");
				}
			$h .= '</td>';

			$h .= '<td class="text-end">';
			$h .= \util\TextUi::money($totals['withVat']);
			$h .= '</td>';

			$h .= '<td class="text-end">';
				$h .= \util\TextUi::money($totals['withoutVat']);
			$h .= '</td>';

			$h .= '<td class="text-end">';
				$h .= \util\TextUi::money($totals['vat']);
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

		$h .= '</tr>';

		return $h;
	}

	public function getJournal(
		\company\Company $eCompany,
		\accounting\FinancialYear $eFinancialYear,
		array $operations,
		\accounting\FinancialYear $eFinancialYearSelected,
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
			$h .= '</div>';
			$h .= '<div class="tab-panel" data-tab="vat-buy">';
				$h .= $this->getTableContainer($eCompany, $eFinancialYear, $operations['buy'], 'buy', $search);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="vat-sell">';
				$h .= $this->getTableContainer($eCompany, $eFinancialYear, $operations['sell'], 'sell', $search);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function getTableContainer(
		\company\Company $eCompany,
		\accounting\FinancialYear $eFinancialYear,
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

			$h .= '<a href="'.\company\CompanyUi::urlJournal($eCompany).'/vat:pdf?type='.$type.'&financialYear='.$eFinancialYear['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('download').'&nbsp;'.s("Télécharger en PDF").'</a>';

			$h .= $this->getTables($eCompany, $cccOperation, $search);

		$h .= '</div>';

		return $h;
	}

	public function getTables(
		\company\Company $eCompany,
		\Collection $cccOperation,
		\Search $search = new \Search(),
		string $for = 'web',
	): string {

		$h = '';

		$lastAccountLabel = NULL;
		foreach($cccOperation as $currentAccountLabel => $ccOperation) {

			$eOperation = $ccOperation->first()->first();

			if($lastAccountLabel === NULL or $currentAccountLabel !== $lastAccountLabel) {

				$h .= '<div class="account-title">';
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
			$h .= '<table class="tr-even td-vertical-top tr-hover table-bordered table-'.$for.'">';

				$h .= '<thead '.($for === 'web' ? 'class="thead-sticky"' : '').'>';
					$h .= '<tr '.($for === 'pdf' ? 'class="row-header row-upper"' : '').'>';
						$h .= '<th>';
							$label = s("Date");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('date', $label) : $label);
						$h .= '</th>';
						$h .= '<th>'.s("# Opération bancaire").'</th>';
						$h .= '<th>';
							$label = s("Pièce comptable");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('document', $label) : $label);
						$h .= '</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th>';
							$label = s("Description");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('description', $label) : $label);
						$h .= '</th>';
						$h .= '<th class="text-end">'.s("Montant (TTC)").'</th>';
						$h .= '<th class="text-end">'.s("Montant (HT)").'</th>';
						$h .= '<th class="text-end">'.s("TVA").'</th>';
						$h .= '<th class="text-end">'.s("Taux TVA").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

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
								str_starts_with($eOperation['accountLabel'], \Setting::get('accounting\vatBuyClassPrefix'))
							and $eOperationInitial['type'] === OperationElement::CREDIT) {
								$multiplyer = -1;
							} else {
								$multiplyer = 1;
							}
							$monthTotals['withVat'] += $multiplyer * $eOperationInitial['amount'] + $multiplyer * $eOperation['amount'];
							$monthTotals['withoutVat'] += $multiplyer * $eOperationInitial['amount'];
							$monthTotals['vat']+= $multiplyer * $eOperation['amount'];

							if($eOperationInitial['cashflow']->exists() === TRUE) {
								$cashflowLink = \company\CompanyUi::urlBank($eCompany).'/cashflow?id='.$eOperationInitial['cashflow']['id'];
							} else {
								$cashflowLink = NULL;
							}

							$h .= '<tr>';

								$h .= '<td '.($for === 'pdf' ? 'class="text-small"' : '').'>';
									$h .= \util\DateUi::numeric($eOperationInitial['date']);
								$h .= '</td>';

								$h .= '<td>';
								if($eOperationInitial['cashflow']->exists() === TRUE) {
									$h .= '<a href="'.$cashflowLink.'" class="color-text">'.$eOperationInitial['cashflow']['id'].'</a>';
								} else {
									$h .= '';
								}
								$h .= '</td>';

								$h .= '<td>';
									$h .= encode($eOperationInitial['document']);
								$h .= '</td>';

								$h .= '<td>';
									if($eOperationInitial['thirdParty']->exists() === TRUE) {
										$h .= encode($eOperationInitial['thirdParty']['name']);
									}
								$h .= '</td>';

								$h .= '<td>';
									$h .= encode($eOperationInitial['description']);
								$h .= '</td>';

								$h .= '<td class="text-end">';
										$h .= \util\TextUi::money($multiplyer * ($eOperationInitial['amount'] + $eOperation['amount']));
								$h .= '</td>';

								$h .= '<td class="text-end">';
										$h .= \util\TextUi::money($multiplyer * $eOperationInitial['amount']);
								$h .= '</td>';

								$h .= '<td class="text-end">';
										$h .= \util\TextUi::money($multiplyer * $eOperation['amount']);
								$h .= '</td>';

								$h .= '<td class="text-center">';
										$h .= $eOperationInitial['vatRate'];
								$h .= '</td>';

							$h .= '</tr>';
						}

						$bigTotals['withVat'] += $monthTotals['withVat'];
						$bigTotals['withoutVat'] += $monthTotals['withoutVat'];
						$bigTotals['vat'] += $monthTotals['vat'];

						$h .= self::getMonthTotal($currentMonth, $monthTotals);

					}

					$h .= self::getMonthTotal(NULL, $bigTotals);

				$h .= '</tbody>';
			$h .= '</table>';
		}
		return $h;
	}
}
