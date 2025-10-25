<?php
namespace selling;

class PreparationUi {

	public function __construct() {

		\Asset::css('selling', 'sale.css');
		\Asset::css('selling', 'preparation.css');

		\Asset::js('selling', 'sale.js');

	}

	public function getHeader(Sale $eSale, array $preparing): string {

		[
			'before' => $eSaleBefore,
			'after' => $eSaleAfter
		] = $preparing;

		$h = '<div class="sale-preparing-wrapper stick-xs">';

			if($eSaleBefore->notEmpty()) {
				$h .= '<a href="'.SaleUi::url($eSaleBefore).'" class="sale-preparing-before">';
					$h .= '<div class="sale-preparing-customer">'.s("Commande précédente").'</div>';
					$h .= '<div class="sale-preparing-arrow">';
						$h .= \Asset::icon('chevron-left');
					$h .= '</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-preparing-before"></div>';
			}

			$eFarmer = $eSale['farm']->getFarmer();

			$h .= '<div class="sale-preparing-title">';
				$h .= '<h4>'.s("Préparation de la commande {position} / {count}", $preparing).'</h4>';
				$h .= '<a data-ajax="/farm/farmer:doUpdateSellingPreparing" post-id="'.$eFarmer['id'].'" class="btn btn-transparent" data-confirm="'.s("Quitte le mode de préparation des commandes ?").'">'.s("Quitter").'  '.\Asset::icon('escape').'</a>';
			$h .= '</div>';

			if($eSaleAfter->notEmpty()) {
				$h .= '<a href="'.SaleUi::url($eSaleAfter).'" class="sale-preparing-after">';
					$h .= '<div class="sale-preparing-arrow">';
						$h .= \Asset::icon('chevron-right');
					$h .= '</div>';
					$h .= '<div class="sale-preparing-customer">'.s("Commande suivante").'</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-preparing-after"></div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getSummary(Sale $eSale, \Collection $cItem, array $preparing): string {

		$h = '<div class="sale-preparing-summary" '.attr('onrender', 'history.removeArgument("prepare")').'>';

			$h .= '<div class="sale-preparing-summary-info">';
				if($eSale['preparationStatus'] === Sale::CONFIRMED) {
					$h .= '<h4>'.s("Articles").'</h4>';
					$h .= '<span>'.$cItem->count().'</span>';
				} else if($preparing['before']->notEmpty()) {
					$h .= '<a href="'.SaleUi::url($preparing['before']).'" class="btn btn-preparation-status-confirmed-button"><div class="sale-preparing-arrow" style="border-color: var(--text)">'.\Asset::icon('chevron-left').'</div></a>';
				}
			$h .= '</div>';

			$h .= '<div>';
				if($eSale['preparationStatus'] === Sale::CONFIRMED) {
					$h .= '<a data-ajax="/selling/sale:doUpdatePreparedCollection" post-ids="'.$eSale['id'].'" class="btn btn-lg sale-preparation-status-confirmed-button">'.s("Commande préparée").'<div class="sale-preparing-arrow mt-1">'.\Asset::icon('hand-index').'</div></a>';
				} else if($preparing['after']->notEmpty() or $preparing['before']->notEmpty()) {
					$h .= '<span class="btn btn-lg btn-readonly sale-preparation-status-prepared-button">'.s("Commande préparée").'<div class="sale-preparing-arrow mt-1">'.\Asset::icon('check-lg').'</div></span>';
				} else {
					$eFarmer = $eSale['farm']->getFarmer();
					$h .= '<a data-ajax="/farm/farmer:doUpdateSellingPreparing" post-id="'.$eFarmer['id'].'" class="btn btn-lg btn-success">'.s("Ok, plus de commande à préparer.<br/>Terminer la préparation").'<div class="sale-preparing-arrow mt-1">'.\Asset::icon('stop-fill').'</div></a>';
				}
			$h .= '</div>';

			$h .= '<div class="sale-preparing-summary-info">';

				if($eSale['preparationStatus'] === Sale::CONFIRMED) {

					if($eSale['hasVat']) {

						switch($eSale['taxes']) {

							case Sale::INCLUDING :

								$h .= '<h4>'.s("Total TTC").'</h4>';
								$h .= '<span>'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</span>';

								break;

							case Sale::EXCLUDING :

								$h .= '<h4>'.s("Total HT").'</h4>';
								$h .= '<span>'.\util\TextUi::money($eSale['priceExcludingVat'] ?? 0).'</span>';

								break;

						}

					} else {

						$h .= '<h4>'.s("Total").'</h4>';
						$h .= '<span>'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</span>';

					}

				} else if($preparing['after']->notEmpty()) {
					$h .= '<a href="'.SaleUi::url($preparing['after']).'" class="btn btn-preparation-status-confirmed-button"><div class="sale-preparing-arrow" style="border-color: var(--text)">'.\Asset::icon('chevron-right').'</div></a>';
				}
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

}
?>
