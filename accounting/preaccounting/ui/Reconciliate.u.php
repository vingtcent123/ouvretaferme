<?php
namespace preaccounting;

Class ReconciliateUi {

	public function __construct() {
		\Asset::css('preaccounting', 'reconciliate.css');
		\Asset::js('preaccounting', 'reconciliate.js');
	}

	public function tableByCashflow(\farm\Farm $eFarm, \Collection $ccSuggestion, \Collection $cMethod, string $selectedTab): string {

		$h = '';
		$form = new \util\FormUi();

		$elements = [];
		foreach($ccSuggestion as $cSuggestion) {

			$cSuggestion->sort(['weight' => SORT_DESC]);

			$eSuggestion = $cSuggestion->first();
			$eCashflow = $eSuggestion['cashflow'];
			if($eSuggestion['invoice']->notEmpty()) {
				if($selectedTab !== 'invoice') {
					continue;
				}
				$element = [
					'suggestion' => $eSuggestion,
					'date'=> $eSuggestion['invoice']['date'],
					'amount'=> $eSuggestion['invoice']['priceIncludingVat'],
					'customer'=> $eSuggestion['invoice']['customer']->getName(),
					'customerType'=> $eSuggestion['invoice']['customer']['type'],
					'reference'=> s("Facture {value}", encode($eSuggestion['invoice']['name'])),
					'confidence' => $this->confidenceValue($eSuggestion),
				];
			} else if($eSuggestion['sale']->notEmpty()) {
				if($selectedTab !== 'sale') {
					continue;
				}
				$element = [
					'suggestion' => $eSuggestion,
					'date'=> $eSuggestion['sale']['deliveredAt'],
					'amount'=> $eSuggestion['sale']['priceIncludingVat'],
					'customer'=> $eSuggestion['sale']['customer']->getName(),
					'customerType'=> $eSuggestion['sale']['customer']['type'],
					'reference'=> s("Vente {value}", encode($eSuggestion['invoice']['name'])),
					'confidence' => $this->confidenceValue($eSuggestion),
				];
			} else if($eSuggestion['operation']->notEmpty()) {
				if($selectedTab !== 'operation') {
					continue;
				}
				$element = [
					'suggestion' => $eSuggestion,
					'date'=> $eSuggestion['operation']['date'],
					'amount'=> $eSuggestion['operation']['amount'],
					'customer'=> $eSuggestion['operation']['thirdParty']['name'],
					'customerType'=> '',
					'reference'=> s("Écriture : {value}", encode($eSuggestion['invoice']['name'])),
					'confidence' => $this->confidenceValue($eSuggestion),
				];
			}

			$elements[] = $element;
		}

		usort($elements, fn($element1, $element2) => $element1['confidence'] <=> $element2['confidence']);
		$elements = array_reverse($elements);

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="reconciliate-table" data-batch="#batch-reconciliate">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox">';
						$h .= '</th>';
						$h .= '<th>'.\Asset::icon('calendar-range').' '.s("Date").'</th>';
						$h .= '<th>'.s("Rapprochement").'</th>';
						$h .= '<th># '.s("Référence").'</th>';
						$h .= '<th class="td-min-content text-end highlight-stick-right">'.\Asset::icon('currency-euro').'&nbsp;'.s("Montant").'</th>';
						$h .= '<th class="text-center">'.s("Indice<br/>de confiance").'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance entre les dates ?").'">'.\Asset::icon('calendar-range').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le tiers ?").'">'.\Asset::icon('person').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec la référence ?").'">#</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le montant ?").'">'.\Asset::icon('currency-euro').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le moyen de paiement ?").'">'.\Asset::icon('wallet2').'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$currentConfidence = NULL;
				foreach($elements as $element) {

					if($currentConfidence === NULL or $currentConfidence[0] !== $element['confidence'][0]) {

						$currentConfidence = $element['confidence'];
						$h .= '<tbody data-confidence="'.$currentConfidence[0].'">';

							$h .= '<tr class="tr-title row-header">';
								$h .= '<td class="td-checkbox">';
									$h .= '<label>';
										$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="reconciliate" data-confidence="'.$currentConfidence[0].'" onclick="Reconciliate.toggleGroupSelection(this)"/>';
									$h .= '</label>';
								$h .= '</td>';
								$h .= '<td colspan="3">'.s("Indice de confiance {value}", '<span class="color-'.$currentConfidence[1].'" style="font-size: 1.5rem;">'.\Asset::icon($currentConfidence[0].'-circle-fill').'</span>').'</td>';
								$h .= '<td class="text-end highlight-stick-right"></td>';
								$h .= '<td></td>';
								$h .= '<td></td>';
								$h .= '<td></td>';
								$h .= '<td></td>';
								$h .= '<td></td>';
								$h .= '<td></td>';
							$h .= '</tr>';

					}

					$eSuggestion = $element['suggestion'];
					$eCashflow = $element['suggestion']['cashflow'];
					$batch = [];
					if($eSuggestion->acceptIgnore()) {
						$batch[] = 'accept-ignore';
					}
					if($eSuggestion->acceptReconciliate()) {
						$batch[] = 'accept-reconciliate';
					}

					$onclick = 'onclick="Reconciliate.updateSelection(this)"';

					$h .= '<tbody>';

						$h .= '<tr class="row-bold" '.$onclick.'>';
							$h .= '<td class="td-checkbox"></td>';
							$h .= '<td>'.\util\DateUi::numeric($element['date']).'</td>';
							$h .= '<td>';
								$h .= '<div class="reconciliate-badge-container"><div class="reconciliate-badge util-badge bg-'.($element['customerType'] ?? '').'">'.\Asset::icon('person').'</div> <div>'.encode($element['customer']).'</div></div>';
							$h .= '</td>';
							$h .= '<td>'.encode($element['reference']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($element['amount']).'</td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="td-checkbox">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSuggestion['id'].'" batch-type="reconciliate" oninput="Reconciliate.changeSelection(this)" data-batch-amount="'.($eCashflow['amount'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'" data-confidence="'.$currentConfidence[0].'"/>';
							$h .= '</td>';

							$h .= '<td '.$onclick.'>'.\util\DateUi::numeric($eCashflow['date']).'</td>';
							$h .= '<td colspan="2" '.$onclick.'>';
								$h .= '<div class="reconciliate-badge-container"><div class="reconciliate-badge util-badge bg-accounting">'.\Asset::icon('piggy-bank').'</div> <div>'.encode($eCashflow['memo']).'</div></div>';
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right" '.$onclick.'>'.\util\TextUi::money($eCashflow['amount']).'</td>';
							$h .= '<td class="text-center td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->confidence($eSuggestion).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::DATE).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->reason($eSuggestion,  $element,\preaccounting\Suggestion::THIRD_PARTY).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::REFERENCE).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::AMOUNT).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::PAYMENT_METHOD).'</td>';
						$h .= '</tr>';

						$attributes = [
							'post-id' => $eSuggestion['id'],
						];

						$h .= '<tr>';

							$h .= '<td class="td-checkbox" '.$onclick.'></td>';
							$h .= '<td '.$onclick.'></td>';
							$h .= '<td>'.$form->dynamicField($eSuggestion, 'paymentMethod', function($d) use($form, $cMethod, $eSuggestion) {
								$d->values = $cMethod;
								$d->default = fn() => $eSuggestion['paymentMethod'];
								$d->attributes['onchange'] = 'Reconciliate.updatePaymentMethod(this);';
								$d->attributes['data-suggestion'] = $eSuggestion['id'];
								if($eSuggestion['paymentMethod']->notEmpty()) {
									$d->attributes['mandatory'] = TRUE;
								}
							});
							$h .= '</td>';

							$h .= '<td '.$onclick.'>';

								$attributes['data-confirm'] = s("Confirmez-vous ce rapprochement ?");
								$h .= '<a class="btn btn-secondary btn-sm"  data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doReconciliate" '.attrs($attributes).'>';
									$h .= \Asset::icon('hand-thumbs-up');
									$h .= '<span class="hide-sm-down"> '.s("Rapprocher").'</span>';
								$h .= '</a>';
								$h .= '  ';
								$attributes['data-confirm'] = s("Confirmez-vous ignorer ce rapprochement ? Il ne vous sera plus jamais proposé.");
								$h .= '<a class="btn btn-outline-secondary btn-sm"  data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doIgnore" '.attrs($attributes).'>';
									$h .= \Asset::icon('hand-thumbs-down');
									$h .= '<span class="hide-sm-down"> '.s("Ignorer").'</span>';
								$h .= '</a>';

							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right" '.$onclick.'></td>';

						$h .= '</tr>';

					$h .= '</tbody>';
				}

			$h .= '</table>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm);


		return $h;

	}

	public function confidenceValue(Suggestion $eSuggestion): array {

		$count = 0;
		$class = 'success';
		foreach([Suggestion::THIRD_PARTY, Suggestion::REFERENCE] as $reason) {
			if($eSuggestion['reason']->get() & $reason) {
				$count++;
			}
		}

		// Montant exact
		if($eSuggestion['reason']->get() & Suggestion::AMOUNT) {
			if(
				$eSuggestion['cashflow']['amount'] === ($eSuggestion['invoice']['priceIncludingVat'] ?? 0) or
				$eSuggestion['cashflow']['amount'] === ($eSuggestion['sale']['priceIncludingVat'] ?? 0)
			) {
				$count++;
			}
		}

		if($eSuggestion['reason']->get() & Suggestion::PAYMENT_METHOD) {
			$count++;
		}
		if($eSuggestion['reason']->get() & Suggestion::DATE) {
			$count++;
		}

		// Perte d'un point de confiance
		if(
			($eSuggestion['reason']->get() & Suggestion::THIRD_PARTY) === FALSE and
			($eSuggestion['reason']->get() & Suggestion::REFERENCE) === FALSE
		) {
			$count--;
		}

		// Correspondance exacte tiers + référence + montant
		if(
			$count < 4 and
			($eSuggestion['reason']->get() & Suggestion::THIRD_PARTY) and
			($eSuggestion['reason']->get() & Suggestion::REFERENCE) and
			($eSuggestion['reason']->get() & Suggestion::AMOUNT) and
			(
				round($eSuggestion['cashflow']['amount'], 2) === round($eSuggestion['invoice']['priceIncludingVat'], 2) or
				round($eSuggestion['cashflow']['amount'], 2) === round($eSuggestion['invoice']['priceIncludingVat'], 2)
			)
		) {
			$count = 4;
		}

		$class = match($count) {
			5 => 'success',
			4 => 'success',
			3 => 'careful',
			2 => 'warning',
			1 => 'danger',
			0 => 'danger',
		};

		return [$count, $class];

	}

	public function confidence(Suggestion $eSuggestion) {

		list($count, $class) = $this->confidenceValue($eSuggestion);

		return '<span class="reconciliate-confidence fs-2 color-'.$class.'">'.\Asset::icon($count.'-circle-fill').'</span>';

	}

	public function tableByOperations(\farm\Farm $eFarm, \Collection $ccSuggestion): string {

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="reconciliate-table" data-batch="#batch-reconciliate">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="reconciliate" onclick="Reconciliate.toggleGroupSelection(this)"/>';
							$h .= '</label>';
						$h .= '</th>';
						$h .= '<th>'.\Asset::icon('calendar-range').' '.s("Date").'</th>';
						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th>'.\Asset::icon('person').' '.s("Client").'</th>';
						$h .= '<th>'.\Asset::icon('123').' '.s("Libellé").'</th>';
						$h .= '<th class="td-min-content text-center" title="'.s("Débit / Crédit").'">'.s("D/C").'</th>';
						$h .= '<th class="td-min-content text-end highlight-stick-right">'.\Asset::icon('currency-euro').'&nbsp;'.s("Montant").'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le tiers ?").'">'.\Asset::icon('person').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le montant ?").'">'.\Asset::icon('currency-euro').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec la référence ?").'">'.\Asset::icon('123').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance entre les dates ?").'">'.\Asset::icon('calendar-range').'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';


				foreach($ccSuggestion as $cSuggestion) {
					$cSuggestion->sort(['weight' => SORT_DESC]);

					$eSuggestion = $cSuggestion->first();
					$eCashflow = $eSuggestion['cashflow'];
					$eOperation = $eSuggestion['operation'];

					if($eOperation['cOperationLinked']->notEmpty()) {
						$amount = ($eOperation['type'] === \journal\Operation::CREDIT ? -1 * $eOperation['amount'] : $eOperation['amount']);
						foreach($eOperation['cOperationLinked'] as $eOperationLinked) {
							$amount += ($eOperationLinked['type'] === \journal\Operation::CREDIT ? -1 * $eOperationLinked['amount'] : $eOperationLinked['amount']);
						}
						$amount = round($amount, 2);
						$eOperation['amount'] = abs($amount);
					}

					$element = [
						'date'=> $eSuggestion['operation']['date'],
						'amount'=> $eSuggestion['operation']['amount'],
						'customer'=> $eSuggestion['operation']['thirdParty']['name'],
						'reference'=> $eSuggestion['operation']['description'],
					];

					$onclick = 'onclick="Reconciliate.updateSelection(this)"';

					$h .= '<tbody>';

						$h .= '<tr class="tr-title" '.$onclick.'>';
							$h .= '<td class="td-checkbox"></td>';
							$h .= '<td>'.\util\DateUi::numeric($eOperation['date']).'</td>';
							$h .= '<td>';
							$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
								if($eOperation['accountLabel'] !== NULL) {
									$text = encode($eOperation['accountLabel']);
								} else {
									$text = encode(str_pad($eOperation['account']['class'], 8, 0));
								}
								$h .= $text;
							$h .= '</div>';
							$h .= new \account\AccountUi()->getDropdownTitle($eOperation['account']);
							$h .= '</td>';
							$h .= '<td>'.encode($eOperation['thirdParty']['name']).'</td>';
							$h .= '<td>'.encode($eOperation['description']).'</td>';
							$h .= '<td class="text-center">'.match($eOperation['type']) {
								\journal\Operation::CREDIT => s("C"),
								\journal\Operation::DEBIT => s("D"),
							}.'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($eOperation['amount']).'</td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';
						$h .= '</tr>';

						$h .= '<tr>';

							$h .= '<td class="td-checkbox">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSuggestion['id'].'" batch-type="reconciliate" oninput="Reconciliate.changeSelection(this)" data-batch-amount="'.($eCashflow['amount'] ?? 0.0).'"/>';
							$h .= '</td>';

							$h .= '<td '.$onclick.'>'.\util\DateUi::numeric($eCashflow['date']).'</td>';
							$h .= '<td colspan=4" '.$onclick.'>'.encode($eCashflow['memo']).'</td>';
							$h .= '<td class="text-end highlight-stick-right" '.$onclick.'>'.\util\TextUi::money($eCashflow['amount']).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::THIRD_PARTY).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::AMOUNT).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::REFERENCE).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::DATE).'</td>';
							$h .= '<td>';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';

									$attributes = [
										'post-id' => $eSuggestion['id'],
										'class' => 'dropdown-item',
									];
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doReconciliate" '.\attrs($attributes).'>'.\Asset::icon('hand-thumbs-up').' '.s("Rapprocher").'</a>';
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doIgnore" '.\attrs($attributes).'>'.\Asset::icon('hand-thumbs-down').' '.s("Ignorer").'</a>';
								$h .= '</div>';

							$h .= '</td>';

						$h .= '</tr>';

					$h .= '</tbody>';
				}

			$h .= '</table>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm);


		return $h;

	}

	public function getBatch(\farm\Farm $eFarm): string {

		$urlIgnore = \company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doIgnoreCollection';
		$title = s("Pour les suggestions sélectionnées");

		$menu = '<div class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number"></span>';
				$menu .= ' <span class="batch-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</div>';


		$menu .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:reconciliate" data-ajax-method="get" data-batch-test="accept-reconciliate" data-batch-contains="get" data-batch-not-contains="hide" class="batch-item">';
			$menu .= \Asset::icon('hand-thumbs-up');
			$menu .= '<span>'.s("Rapprocher").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-reconciliate" data-batch-contains="count" data-batch-only="hide"></span></span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax="'.$urlIgnore.'" data-batch-test="accept-ignore" data-batch-contains="post" data-batch-not-contains="hide" data-confirm="'.s("Confirmez-vous ignorer ces suggestions ? Elles ne vous seront plus jamais proposées.").'"  class="batch-ignore batch-item">';
			$menu .= \Asset::icon('hand-thumbs-down');
			$menu .= '<span>'.s("Ignore").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-ignore" data-batch-contains="count" data-batch-only="hide"></span></span>';
		$menu .= '</a>';

		return \util\BatchUi::group('batch-reconciliate', $menu, title: $title);

	}

	public function reason(\preaccounting\Suggestion $eSuggestion, array $element, int $bit): string {

		if($eSuggestion['reason']->get() & $bit) {

			if($bit === \preaccounting\Suggestion::AMOUNT and round($eSuggestion['cashflow']['amount'], 2) !== round($element['amount'], 2)) {
				return '<span class="color-warning" title="'.s("Attention à la différence de montant constatée").'">'.\Asset::icon('exclamation-triangle').'</span>';
			}
			return '<span class="color-success">'.\Asset::icon('check-lg').'</span>';
		}

		return '<span class="color-muted">'.\Asset::icon('question-lg').'</span>';

	}


	public function reconciliate(\farm\Farm $eFarm, \Collection $cSuggestion): \Panel {

		$urlReconciliate = \company\CompanyUi::urlFarm($eFarm).'/preaccounting/reconciliate:doReconciliateCollection';

		$form = new \util\FormUi();

		$h = $form->openAjax($urlReconciliate, ['id' => 'suggestion-reconciliate']);

		$h .= '<div class="util-block-important">';
			$h .= '<p>';
				$h .= p("Vous vous apprêtez à rapprocher {value} vente", "Vous vous apprêtez à rapprocher {value} ventes", $cSuggestion->count());
			$h .= '</p>';

			$h .= '<p>';
				\Asset::css('selling', 'sale.css');
				$h .= s("Chaque vente deviendra <span>payée</span> avec le moyen de paiement indiqué, et l'opération bancaire sera rattachée à la vente.", ['span' => '<span class="util-badge sale-payment-status sale-payment-status-success">']);
			$h .= '</p>';
		$h .= '</div>';



			foreach($cSuggestion as $eSuggestion) {
				$h .= $form->hidden('ids[]', $eSuggestion['id']);
			}

			$h .= $form->submit(\Asset::icon('hand-thumbs-up').' '.s("Rapprocher"), ['data-waiter' => s("Rapprochement en cours...")]);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-suggestion-reconciliate',
			title: s("Valider les suggestions de rapprochement"),
			body: $h
		);

	}
	
}
