<?php
namespace preaccounting;

Class ReconciliateUi {

	public function __construct() {
		\Asset::css('preaccounting', 'reconciliate.css');
		\Asset::js('preaccounting', 'reconciliate.js');
	}

	public function list(\farm\Farm $eFarm, \Collection $ccSuggestion, \Collection $cMethod): string {

		$h = '';
		$form = new \util\FormUi();

		$elements = [];
		foreach($ccSuggestion as $cSuggestion) {

			$cSuggestion->sort(['weight' => SORT_DESC]);

			$eSuggestion = $cSuggestion->first();

			if($eSuggestion['invoice']->notEmpty()) {

				$eCustomer = $eSuggestion['invoice']['customer'];
				$reference = '<a href="'.\farm\FarmUi::urlSellingInvoices($eFarm).'?invoice='.$eSuggestion['invoice']['id'].'">'.s("Facture {value}", encode($eSuggestion['invoice']['number'])).'</a>';
				$date = $eSuggestion['invoice']['date'];
				$amount = $eSuggestion['invoice']['priceIncludingVat'];

			} else if($eSuggestion['payment']['source'] === \selling\Payment::INVOICE) {

				$eCustomer = $eSuggestion['payment']['invoice']['customer'];
				$reference = '<a href="'.\farm\FarmUi::urlSellingInvoices($eFarm).'?invoice='.$eSuggestion['payment']['invoice']['id'].'">'.s("Facture {value}", encode($eSuggestion['payment']['invoice']['number'])).'</a>';
				$date = $eSuggestion['payment']['invoice']['date'];
				$amount = $eSuggestion['payment']['invoice']['priceIncludingVat'];

			} else {

				$eCustomer = $eSuggestion['payment']['sale']['customer'];
				$reference = '<a href="'.\selling\SaleUi::url($eSuggestion['payment']['sale']).'">'.s("Vente {value}", encode($eSuggestion['payment']['sale']['document'])).'</a>';
				$date = $eSuggestion['payment']['sale']['deliveredAt'];
				$amount = $eSuggestion['payment']['sale']['priceIncludingVat'];

			}

			$element = [
				'suggestion' => $eSuggestion,
				'date'=> $date,
				'amount'=> $amount,
				'customer'=> $eCustomer->getName(),
				'customerType'=> $eCustomer['type'],
				'reference'=> $reference,
				'confidence' => $this->confidenceValue($eSuggestion),
			];

			$elements[] = $element;
		}

		usort($elements, fn($element1, $element2) => $element1['confidence'] <=> $element2['confidence']);
		$elements = array_reverse($elements);

		$h .= '<div class="stick-md util-overflow-md">';

			$h .= '<table class="reconciliate-table" data-batch="#batch-reconciliate">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox">';
						$h .= '</th>';
						$h .= '<th>'.\Asset::icon('calendar-range').' '.s("Date").'</th>';
						$h .= '<th>'.s("Rapprochement").'</th>';
						$h .= '<th># '.s("Référence").'</th>';
						$h .= '<th class="td-min-content text-end highlight-stick-right">'.\Asset::icon('currency-euro').'&nbsp;'.s("Montant").'</th>';
						$h .= '<th class="text-center">'.s("Confiance").'</th>';
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

							$h .= '<tr class="tr-title tr-header">';
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

					$h .= '<tbody>';

						$h .= '<tr class="tr-bold">';
							$h .= '<td class="td-checkbox"></td>';
							$h .= '<td>'.\util\DateUi::numeric($element['date']).'</td>';
							$h .= '<td>';
								$h .= '<div class="reconciliate-badge-container"><div class="reconciliate-badge util-badge bg-'.($element['customerType'] ?? '').'">'.\Asset::icon('person').'</div> <div>'.encode($element['customer']).'</div></div>';
							$h .= '</td>';
							$h .= '<td>'.$element['reference'].'</td>';
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

							$h .= '<td>'.\util\DateUi::numeric($eCashflow['date']).'</td>';
							$h .= '<td colspan="2">';
								$h .= '<div class="reconciliate-badge-container"><div class="reconciliate-badge util-badge bg-accounting">'.\Asset::icon('bank').'</div> <div>'.encode($eCashflow->getMemo()).'</div></div>';
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($eCashflow['amount']).'</td>';
							$h .= '<td class="text-center td-vertical-align-top" rowspan="2">'.$this->confidence($eSuggestion).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2">'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::DATE).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2">'.$this->reason($eSuggestion,  $element,\preaccounting\Suggestion::THIRD_PARTY).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2">'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::REFERENCE).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2">'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::AMOUNT).'</td>';
							$h .= '<td class="td-min-content td-vertical-align-top" rowspan="2">'.$this->reason($eSuggestion,  $element, \preaccounting\Suggestion::PAYMENT_METHOD).'</td>';
						$h .= '</tr>';

						$attributes = [
							'post-id' => $eSuggestion['id'],
						];

						$h .= '<tr>';

							$h .= '<td class="td-checkbox"></td>';
							$h .= '<td></td>';
							$h .= '<td>';

								$h .= $form->openAjax(\farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:doUpdatePaymentMethod', ['id' => 'preaccounting-payment', 'class' => 'flex-justify-space-between']);
								$h .= $form->hidden('id', $eSuggestion['id']);
								$h .= $form->inputGroup(
									$form->dynamicField($eSuggestion, 'paymentMethod', function($d) use($form, $cMethod, $eSuggestion) {
										$d->values = $cMethod;
										$d->default = fn() => $eSuggestion['paymentMethod'];
										if($eSuggestion['paymentMethod']->notEmpty()) {
											$d->attributes['mandatory'] = TRUE;
										}
									}).
									$form->submit(s("Modifier"), ['class' => 'btn btn-outline-primary'])
								);
								$h .= $form->close();

							$h .= '</td>';

							$h .= '<td>';

								$attributes['class'] = 'btn  btn-secondary btn-sm';
								if($eSuggestion->acceptReconciliate() === FALSE) {
									$attributes['disabled'] = 'disabled';
									$attributes['class'] .= ' disabled';
								} else {
									$attributes['data-confirm'] = s("Confirmez-vous ce rapprochement ?");
								}
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:doReconciliate" '.attrs($attributes).'>';
									$h .= \Asset::icon('hand-thumbs-up');
									$h .= '<span class="hide-sm-down"> '.s("Rapprocher").'</span>';
								$h .= '</a>';
								$h .= '  ';
								$attributes['data-confirm'] = s("Confirmez-vous ignorer ce rapprochement ? Il ne vous sera plus jamais proposé.");
								$h .= '<a class="btn btn-outline-secondary btn-sm"  data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:doIgnore" '.attrs($attributes).'>';
									$h .= \Asset::icon('hand-thumbs-down');
									$h .= '<span class="hide-sm-down"> '.s("Ignorer").'</span>';
								$h .= '</a>';

							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right"></td>';

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

		[$count, $class] = $this->confidenceValue($eSuggestion);

		return '<span class="reconciliate-confidence fs-2 color-'.$class.'">'.\Asset::icon($count.'-circle-fill').'</span>';

	}

	public function getBatch(\farm\Farm $eFarm): string {

		$urlIgnore = \farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:doIgnoreCollection';
		$title = s("Pour les suggestions sélectionnées");

		$menu = '<div class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number"></span>';
				$menu .= ' <span class="batch-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</div>';


		$menu .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:reconciliate" data-ajax-method="get" data-batch-test="accept-reconciliate" data-batch-contains="get" data-batch-not-contains="hide" class="batch-item">';
			$menu .= \Asset::icon('hand-thumbs-up');
			$menu .= '<span>'.s("Rapprocher").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-reconciliate" data-batch-contains="count" data-batch-only="hide"></span></span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax="'.$urlIgnore.'" data-batch-test="accept-ignore" data-batch-contains="post" data-batch-not-contains="hide" data-confirm="'.s("Confirmez-vous ignorer ces suggestions ? Elles ne vous seront plus jamais proposées.").'"  class="batch-ignore batch-item">';
			$menu .= \Asset::icon('hand-thumbs-down');
			$menu .= '<span>'.s("Ignorer").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-ignore" data-batch-contains="count" data-batch-only="hide"></span></span>';
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

		\Asset::css('selling', 'payment.css');

		$urlReconciliate = \farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:doReconciliateCollection';

		$form = new \util\FormUi();

		$h = $form->openAjax($urlReconciliate, ['id' => 'suggestion-reconciliate']);

			$h .= '<div class="util-block-info">';
				$h .= '<p>';
					$h .= p("Vous vous apprêtez à rapprocher {value} facture.", "Vous vous apprêtez à rapprocher {value} factures.", $cSuggestion->count());
				$h .= '</p>';

				$h .= '<p>';
					\Asset::css('selling', 'sale.css');
					$h .= s("Chaque facture deviendra <span>payée</span> avec le moyen de paiement indiqué, et l'opération bancaire sera rattachée à la facture.", ['span' => '<span class="util-badge payment-status payment-status-success">']);
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
