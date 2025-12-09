<?php
namespace invoicing;

Class ReconciliateUi {

	public function __construct() {
		\Asset::css('invoicing', 'reconciliate.css');
		\Asset::js('invoicing', 'reconciliate.js');
	}

	public function table(\farm\Farm $eFarm, \Collection $ccSuggestion): string {

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
						$h .= '<th>'.\Asset::icon('file-person').' '.s("Client").'</th>';
						$h .= '<th>'.\Asset::icon('123').' '.s("Libellé").'</th>';
						$h .= '<th class="td-min-content text-end highlight-stick-right">'.\Asset::icon('currency-euro').'&nbsp;'.s("Montant").'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le tiers ?").'">'.\Asset::icon('file-person').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec le montant ?").'">'.\Asset::icon('currency-euro').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance avec la référence ?").'">'.\Asset::icon('123').'</th>';
						$h .= '<th class="td-min-content" title="'.s("Correspondance entre les dates ?").'">'.\Asset::icon('calendar-range').'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';


				foreach($ccSuggestion as $cSuggestion) {
					$cSuggestion->sort(['weight' => SORT_DESC]);

					$eSuggestion = $cSuggestion->first();
					$eOperation = $eSuggestion['operation'];
					$eCashflow = $eSuggestion['cashflow'];

					$onclick = 'onclick="Reconciliate.updateSelection(this)"';

					$h .= '<tbody>';

						$h .= '<tr class="tr-title" '.$onclick.'>';
							$h .= '<td class="td-checkbox"></td>';
							$h .= '<td>'.\util\DateUi::numeric($eOperation['date']).'</td>';
							$h .= '<td>'.encode($eOperation['thirdParty']['name']).'</td>';
							$h .= '<td>'.encode($eOperation['description']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($eOperation['amount']).'</td>';
							$h .= '<td></td>';
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
							$h .= '<td colspan="2" '.$onclick.'>'.encode($eCashflow['memo']).'</td>';
							$h .= '<td class="text-end highlight-stick-right" '.$onclick.'>'.\util\TextUi::money($eCashflow['amount']).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion, Suggestion::THIRD_PARTY).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion, Suggestion::AMOUNT).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion, Suggestion::REFERENCE).'</td>';
							$h .= '<td class="td-min-content" '.$onclick.'>'.$this->reason($eSuggestion, Suggestion::DATE).'</td>';
							$h .= '<td>';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';

									$attributes = [
										'post-id' => $eSuggestion['id'],
										'class' => 'dropdown-item',
									];
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/invoicing/reconciliate:doReconciliate" '.attrs($attributes).'>'.\Asset::icon('hand-thumbs-up').' '.s("Rapprocher").'</a>';
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/invoicing/reconciliate:doIgnore" '.attrs($attributes).'>'.\Asset::icon('hand-thumbs-down').' '.s("Ignorer").'</a>';
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

		$urlReconciliate = \company\CompanyUi::urlFarm($eFarm).'/invoicing/reconciliate:doReconciliateCollection';
		$urlIgnore = \company\CompanyUi::urlFarm($eFarm).'/invoicing/reconciliate:doIgnoreCollection';
		$title = s("Pour les suggestions sélectionnées");

		$menu = '<a href="javascript: void(0);" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number"></span>';
				$menu .= ' <span class="batch-menu-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';


		$menu .= '<a data-ajax-submit="'.$urlReconciliate.'" class="batch-menu-import batch-menu-item">'.\Asset::icon('hand-thumbs-up').'<span>'.s("Rapprocher").'</span></a>';

		$menu .= '<a data-ajax-submit="'.$urlIgnore.'" class="batch-menu-ignore batch-menu-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';

		return \util\BatchUi::group('batch-reconciliate', $menu, title: $title);

	}

	public function reason(Suggestion $eSuggestion, int $bit): string {

		if($eSuggestion['reason']->get() & $bit) {

			if($bit === Suggestion::AMOUNT and $eSuggestion['cashflow']['amount'] !== $eSuggestion['operation']['amount']) {
				return '<span class="color-warning" title="'.s("Attention à la légère différence de montant constatée").'">'.\Asset::icon('exclamation-triangle').'</span>';
			}
			return '<span class="color-success">'.\Asset::icon('check-lg').'</span>';
		}

		return'';

	}
}
