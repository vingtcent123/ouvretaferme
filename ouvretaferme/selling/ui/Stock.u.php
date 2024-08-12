<?php
namespace selling;

class StockUi {

	public function __construct() {

		\Asset::css('selling', 'product.css');
		\Asset::js('selling', 'product.js');

	}

	public function getList(\Collection $cProduct, \Search $search) {

		$h = '';

		$h .= '<div class="product-item-wrapper stick-xs">';

		$h .= '<table class="product-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="product-item-vignette"></th>';
					$h .= '<th>'.$search->linkSort('name', s("Produit"), SORT_ASC).'</th>';
					$h .= '<th class="text-end td-min-content">'.s("Stock").'</th>';
					$h .= '<th></th>';
					$h .= '<th>'.$search->linkSort('stockUpdatedAt', s("Mis à jour"), SORT_DESC).'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$h .= '<tr>';
				
					$h .= '<td class="product-item-vignette">';
						$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '4rem');
					$h .= '</td>';

					$h .= '<td class="product-item-name">';
						$h .= ProductUi::getInfos($eProduct);
					$h .= '</td>';

					$h .= '<td class="product-item-stock text-end td-min-content">';
						$h .= $eProduct['stock'];
					$h .= '</td>';

					$h .= '<td class="product-item-unit">';
						$h .= \main\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="product-item-stock-updated">';
						$h .= match(substr($eProduct['stockUpdatedAt'], 0, 10)) {
							currentDate() => s("Aujourd'hui"),
							date('Y-m-d', strtotime('-1 DAY')) => s("Hier"),
							default => \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::DATE)
						};
						$h .= s(", {value}", \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::TIME_HOUR_MINUTE));
					$h .= '</td>';

					$h .= '<td>';
						$h .= 'mouvements à intégrer ?<br/>';
						$h .= 'derniers mouvements ?';
					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

}
?>
