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
					$h .= '<th>'.$search->linkSort('name', s("Produit")).'</th>';
					$h .= '<th></th>';
					$h .= '<th class="text-center" colspan="2">'.s("Stock").'</th>';
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

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:decrement?id='.$eProduct['id'].'" class="product-item-stock-button">-</a>';
					$h .= '</td>';

					$h .= '<td class="td-min-content text-end">';
						$h .= $eProduct['stock'];
					$h .= '</td>';

					$h .= '<td class="product-item-unit">';
						$h .= \main\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:increment?id='.$eProduct['id'].'" class="product-item-stock-button">+</a>';
					$h .= '</td>';

					$h .= '<td class="product-item-stock-updated">';

						$h .= match(substr($eProduct['stockUpdatedAt'], 0, 10)) {
							currentDate() => s("Aujourd'hui"),
							date('Y-m-d', strtotime('-1 DAY')) => s("Hier"),
							default => \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::DATE)
						};

						$h .= s(", {value}", \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::TIME_HOUR_MINUTE));

						if($eProduct['stockExpired']) {
							$h .= '<div class="color-warning" style="font-size: 0.9rem">'.\Asset::icon('exclamation-triangle-fill').' '.s("Mis à jour il y a plus d'une semaine").'</div>';
						}

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

	public function increment(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			'+',
			s("Ajouter au stock"),
			s("Augmenter le stock")
		);

	}

	public function decrement(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			'-',
			s("Retirer du stock"),
			s("Diminuer le stock")
		);

	}

	public function crement(Product $eProduct, string $sign, string $label, string $header): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/stock:doCrement');

			$h .= $form->hidden('id', $eProduct['id']);
			$h .= $form->hidden('sign', $sign);

			$h .= $form->group(
				$label,
				$form->inputGroup(
					'<div class="input-group-addon">'.$sign.'</div>'.
					$form->dynamicField(new Stock(), 'newValue', function(\PropertyDescriber $d) {
						$d->attributes['onrender'] = 'this.focus();';
					}).
					'<div class="input-group-addon">'.\main\UnitUi::getNeutral($eProduct['unit']).'</div>'
				)
			);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: $header,
			body: $h,
			subTitle: (new ProductUi())->getPanelHeader($eProduct)
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Stock::model()->describer($property);

		switch($property) {

		}

		return $d;

	}

}
?>
