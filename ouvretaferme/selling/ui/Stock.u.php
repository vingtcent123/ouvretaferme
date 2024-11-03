<?php
namespace selling;

class StockUi {

	public function __construct() {

		\Asset::css('selling', 'stock.css');

	}

	public function getNotes(\farm\Farm $eFarm): string {

		if($eFarm['stockNotes'] === NULL) {
			return '';
		}

		$h = '<div class="stock-description '.($eFarm['stockNotes'] === '' ? 'stock-description-empty' : '').' util-block">';

			$h .= '<span class="stock-description-icon">'.\Asset::icon('chat-right-text').'</span>';
			$h .= '<div>';
				$h .= '<div class="util-action">';
					if($eFarm['stockNotes'] !== '') {
						$h .= '<h4>'.\user\UserUi::getVignette($eFarm['stockNotesUpdatedBy'], '1.75rem').'  '.self::getDate($eFarm['stockNotesUpdatedAt']).'</h4>';
						$h .= '<a href="/selling/stock:updateNote?id='.$eFarm['id'].'" class="btn btn-secondary">'.s("Modifier").'</a>';
					} else {
						$h .= '<div class="color-muted">'.s("Pas de notes de stock en ce moment").'</div>';
						$h .= '<a href="/selling/stock:updateNote?id='.$eFarm['id'].'" class="btn btn-secondary">'.s("Ajouter").'</a>';
					}
				$h .= '</div>';

				if($eFarm['stockNotes'] !== '') {
					$h .= nl2br(encode($eFarm['stockNotes']));
				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getList(\Collection $cProduct, \Collection $cStockBookmark, \Collection $ccItemPast, \Collection $cItemFuture, \Search $search): string {

		$today = currentDate();
		$yesterday = date('Y-m-d', strtotime('yesterday'));

		$h = '';

		$h .= '<div class="util-overflow-sm stick-xs">';

		$h .= '<table class="stock-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th></th>';
					$h .= '<th>'.$search->linkSort('name', s("Produit")).'</th>';
					$h .= '<th></th>';
					$h .= '<th class="text-center" colspan="2">'.s("Stock").'</th>';
					$h .= '<th></th>';
					$h .= '<th colspan="2">'.$search->linkSort('stockUpdatedAt', s("Mis à jour"), SORT_DESC).'</th>';
					if($ccItemPast->notEmpty()) {
						$h .= '<th class="text-center">'.s("Vendu aujourd'hui").'</th>';
						$h .= '<th class="text-center hide-sm-down">'.s("Vendu hier").'</th>';
					}
					if($cItemFuture->notEmpty()) {
						$h .= '<th class="text-center highlight hide-sm-down">'.s("Ventes à venir").'</th>';
					}
					$h .= '<th></th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$cItemPast = $ccItemPast[$eProduct['id']] ?? new \Collection();

				$bookmarks = $cStockBookmark->offsetExists($eProduct['id']) ? $cStockBookmark[$eProduct['id']]['number'] : 0;

				$h .= '<tr>';

					$h .= '<td class="td-min-content">';
						$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '2.5rem');
					$h .= '</td>';

					$h .= '<td class="stock-item-name">';
						$h .= ProductUi::getInfos($eProduct, includeQuality: FALSE);

						if($bookmarks > 0) {
							$h .= '  <a href="/selling/stock:bookmarks?id='.$eProduct['id'].'" style="opacity: 0.33" title="'.p("{value} récolte en mémoire pour ce stock", "{value} récoltes en mémoire pour ce stock", $bookmarks).'">'.\Asset::icon('star-fill').'</a>';
						}

					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-decrement">';
						if($eProduct['stock'] > 0.0) {
							$h .= '<a href="/selling/stock:decrement?id='.$eProduct['id'].'" class="stock-item-button" title="'.s("Diminuer le stock").'">-</a>';
						}
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-value">';
						$h .= '<a href="/selling/stock:update?id='.$eProduct['id'].'" title="'.s("Corriger le stock").'">'.round($eProduct['stock']).'</a>';
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-unit">';
						$h .= '<a href="/selling/stock:update?id='.$eProduct['id'].'" title="'.s("Corriger le stock").'">'.\main\UnitUi::getSingular($eProduct['unit'], short: TRUE).'</a>';
						$h .= '<div class="stock-item-pencil">'.\Asset::icon('pencil-fill').'</div>';
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-increment">';
						$h .= '<a href="/selling/stock:increment?id='.$eProduct['id'].'" class="stock-item-button" title="'.s("Augmenter le stock").'">+</a>';
					$h .= '</td>';

					if($eProduct['stockLast']->notEmpty()) {

						$eStock = $eProduct['stockLast'];

						$h .= '<td class="td-min-content">';
							$h .= \user\UserUi::getVignette($eStock['createdBy'], '1.5rem');
						$h .= '</td>';
						$h .= '<td class="stock-item-stock-updated">';

							$h .= '<a href="/selling/stock:history?id='.$eProduct['id'].'" class="color-text">';

								$h .= $this->getDate($eStock['createdAt']);

								if($eStock['delta'] !== 0.0 and $eStock['delta'] !== NULL) {
									$h .= ', <b>'.($eStock['delta'] > 0 ? '+' : '').$eStock['delta'].'</b>';
								}

							$h .= '</a>';

							if($eProduct['stockExpired']) {
								$h .= '<div class="stock-item-stock-updated-comment">'.\Asset::icon('alarm').' '.s("Il y a plus d'une semaine").'</div>';
							} else {

								if($eStock['comment']) {
									$h .= '<div class="stock-item-stock-updated-comment">'.encode($eStock['comment']).'</div>';
								}

							}

						$h .= '</td>';

					} else {
						$h .= '<td colspan="2">';
							$h .= '/';
						$h .= '</td>';
					}

					if($ccItemPast->notEmpty()) {

						$h .= '<td class="text-center">';

							if($cItemPast->offsetExists($today)) {

								$hide = $eProduct['last']->empty() ? FALSE : ($eProduct['last']['minus'] !== NULL and $eProduct['last']['minus'] >= $today);

								$value = round($cItemPast[$today]['quantity'], 2);

								$h .= '<div class="stock-item-main">';
									$h .= '<a data-ajax="/selling/stock:doUpdate" post-id="'.$eProduct['id'].'" post-sign="-" post-new-value="'.$value.'" post-comment="'.s("Ventes").'" class="btn btn-sm btn-outline-primary" '.($hide ? 'data-confirm="'.s("Votre stock est peut-être déjà à jour, voulez-vous toujours retrancher les quantités livrées aujourd'hui du stock ?").'"' : '').' title="'.s("Retrancher du stock").'">- '.\main\UnitUi::getValue($value, $eProduct['unit'], short: TRUE).'</a>';
								$h .= '</div>';

							}

							foreach($eProduct['cProductSiblings'] as $eProductSibling) {
								$h .= $this->getProductQuantity($ccItemPast[$eProductSibling['id']][$today] ?? new Item(), $eProductSibling, 'sibling');
							}

						$h .= '</td>';

						$h .= '<td class="text-center hide-sm-down">';

							if($cItemPast->offsetExists($yesterday)) {

								$hide = $eProduct['last']->empty() ? FALSE : ($eProduct['last']['minus'] !== NULL and $eProduct['last']['minus'] >= $yesterday);

								$value = round($cItemPast[$yesterday]['quantity'], 2);

								$h .= '<div class="stock-item-main">';
									$h .= '<a data-ajax="/selling/stock:doUpdate" post-id="'.$eProduct['id'].'" post-sign="-" post-new-value="'.$value.'" post-comment="'.s("Ventes").'" class="btn btn-sm btn-outline-primary" '.($hide ? 'data-confirm="'.s("Votre stock est peut-être déjà à jour, voulez-vous toujours retrancher les quantités livrées hier du stock ?").'"' : '').' title="'.s("Retrancher du stock").'">- '.\main\UnitUi::getValue($value, $eProduct['unit'], short: TRUE).'</a>';
								$h .= '</div>';

							}

							foreach($eProduct['cProductSiblings'] as $eProductSibling) {
								$h .= $this->getProductQuantity($ccItemPast[$eProductSibling['id']][$yesterday] ?? new Item(), $eProductSibling, 'sibling');
							}

						$h .= '</td>';

					}

					if($cItemFuture->notEmpty()) {

						$h .= '<td class="highlight text-center hide-sm-down">';

							$h .= $this->getProductQuantity($cItemFuture[$eProduct['id']] ?? new Item(), $eProduct, 'main');

							foreach($eProduct['cProductSiblings'] as $eProductSibling) {
								$h .= $this->getProductQuantity($cItemFuture[$eProductSibling['id']] ?? new Item(), $eProductSibling, 'sibling');
							}

						$h .= '</td>';

					}

					$h .= '<td class="stock-item-actions">';

						if($eProduct->canWrite()) {

							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($eProduct->getName()).'</div>';
								$h .= '<a href="/selling/stock:update?id='.$eProduct['id'].'" class="dropdown-item">'.s("Corriger le stock").'</a>';
								$h .= '<a href="/selling/stock:history?id='.$eProduct['id'].'" class="dropdown-item">'.s("Voir l'historique du stock").'</a>';
								if($bookmarks) {
									$h .= '<a href="/selling/stock:bookmarks?id='.$eProduct['id'].'" class="dropdown-item">'.s("Voir les récoltes en mémoire").'</a>';
								}
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="selling/product:doDisableStock" post-id="'.$eProduct['id'].'" class="dropdown-item">'.\Asset::icon('box').'  '.s("Désactiver le suivi du stock").'</a>';
							$h .= '</div>';

						}

					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	protected function getProductQuantity(Item $eItem, Product $eProduct, string $class): string {

		if($eItem->empty()) {
			return '';
		}

		return '<div class="stock-item-'.$class.'">- '.\main\UnitUi::getValue($eItem['quantity'], $eProduct['unit'], short: TRUE).'</div>';

	}

	public function getHistory(Product $eProduct, \Collection $cStock): \Panel {

		if($cStock->empty()) {
			$h = '<div class="util-info">'.s("Il n'y a aucun historique pour le stock de ce produit.").'</div>';
		} else {

			$h = '';

			$h .= '<div class="stick-xs">';

			$h .= '<table class="stock-item-table tr-bordered tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th class="text-center" colspan="2">'.s("Stock").'</th>';
						$h .= '<th>'.s("Variation").'</th>';
						$h .= '<th>'.s("Commentaire").'</th>';
						$h .= '<th>'.s("Par").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cStock as $eStock) {

					$h .= '<tr>';

						$h .= '<td class="stock-item-name">';
							$h .= $this->getDate($eStock['createdAt']);
						$h .= '</td>';

						$h .= '<td class="td-min-content stock-item-value">';
							$h .= $eStock['newValue'];
						$h .= '</td>';

						$h .= '<td class="td-min-content stock-item-unit">';
							$h .= \main\UnitUi::getSingular($eProduct['unit'], short: TRUE);
						$h .= '</td>';

						$h .= '<td class="stock-item-stock-updated color-muted">';
							if($eStock['delta'] !== 0.0) {
								$h .= ($eStock['delta'] > 0 ? '+' : '').$eStock['delta'];
							}
						$h .= '</td>';

						$h .= '<td class="stock-item-comment">';

							if($eStock['comment'] !== NULL) {
								$h .= encode($eStock['comment']);
							}

						$h .= '</td>';

						$h .= '<td class="stock-item-name">';
							$h .= \user\UserUi::getVignette($eStock['createdBy'], '2rem').' ';
							$h .= \user\UserUi::name($eStock['createdBy']);
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= '</div>';

		}

		return new \Panel(
			title: s("Historique du stock"),
			body: $h,
			subTitle: ProductUi::getPanelHeader($eProduct)
		);
	}

	public function getBookmarks(Product $eProduct, \Collection $cBookmark): \Panel {

		if($cBookmark->empty()) {
			$h = '<div class="util-info">'.s("Il n'y a aucune récolte en mémoire pour le stock de ce produit.").'</div>';
		} else {

			$h = '<p class="util-info">'.s("Les récoltes réalisés sur les espèces suivantes augmentent automatiquement le stock du produit {value}.", '<u>'.encode($eProduct['name']).'</u>').'</p>';

			$h .= '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="stock-item-table tr-bordered tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th>'.s("Espèce").'</th>';
						$h .= '<th>'.s("Variété").'</th>';
						$h .= '<th>'.s("Unité").'</th>';
						$h .= '<th>'.s("Calibre").'</th>';
						$h .= '<th>'.s("Par").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cBookmark as $eBookmark) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= \plant\PlantUi::getVignette($eBookmark['plant'], '2rem').'  ';
							$h .= encode($eBookmark['plant']['name']);
						$h .= '</td>';

						$h .= '<td>';
							if($eBookmark['variety']->notEmpty()) {
								$h .= encode($eBookmark['variety']['name']);
							} else {
								$h .= '<span class="color-muted">/</span>';
							}
						$h .= '</td>';

						$h .= '<td class="product-item-unit">';
							$h .= \main\UnitUi::getSingular($eBookmark['unit'], short: TRUE);
						$h .= '</td>';

						$h .= '<td>';
							if($eBookmark['size']->notEmpty()) {
								$h .= encode($eBookmark['size']['name']);
							} else {
								$h .= '<span class="color-muted">/</span>';
							}
						$h .= '</td>';

						$h .= '<td class="bookmark-item-name">';
							$h .= \user\UserUi::getVignette($eBookmark['createdBy'], '2rem').' ';
							$h .= \user\UserUi::name($eBookmark['createdBy']);
						$h .= '</td>';

						$h .= '<td class="text-end">';
							$h .= '<a data-ajax="/selling/stock:doDeleteBookmark" post-id="'.$eBookmark['id'].'" data-confirm="'.s("Ne plus garder en mémoire ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= '</div>';

		}

		return new \Panel(
			id: 'panel-stock-bookmark',
			title: s("Récoltes en mémoire"),
			body: $h,
			subTitle: ProductUi::getPanelHeader($eProduct),
			footer: '<div class="text-end"><a data-ajax="/selling/stock:doDeleteBookmarks" post-id="'.$eProduct['id'].'" class="btn btn-danger" data-confirm="'.s("Vous préférence à la récolte pour ce stock seront effacées. Voulez-vous continuer ?").'">'.s("Tout supprimer").'</a></div>'
		);
	}

	public function add(\farm\Farm $eFarm): \Panel {

		$eProduct = new Product([
			'farm' => $eFarm
		]);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/product:doEnableStock', ['autocomplete' => 'off']);

			$h .= $form->group(
				s("Produit"),
				$form->dynamicField($eProduct, 'id', function($d) use ($eFarm) {
					$d->autocompleteBody = function(\util\FormUi $form, Product $e) {

						$e->expects(['farm']);

						return [
							'farm' => $e['farm']['id'],
							'stock' => 'enable'
						];

					};
				})
			);

			$h .= $form->group(
				content: $form->submit(s("Activer le suivi du stock"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-stock-add',
			title: s("Activer le suivi du stock pour un produit"),
			body: $h
		);

	}

	public static function getExpired(Product $eProduct): string {

		if($eProduct['stockExpired']) {
			return '<span class="product-item-stock-expired" title="'.s("Mis à jour il y a plus d'une semaine").'">'.\Asset::icon('alarm').'</span>';
		} else {
			return '';
		}

	}

	public static function getDate(string $value): string {

		$date = match(substr($value, 0, 10)) {
			currentDate() => s("Aujourd'hui"),
			date('Y-m-d', strtotime('-1 DAY')) => s("Hier"),
			default => \util\DateUi::numeric($value, \util\DateUi::DATE)
		};

		return s("{date} à {time}", ['date' => $date, 'time' => \util\DateUi::numeric($value, \util\DateUi::TIME_HOUR_MINUTE)]);

	}

	public function update(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			NULL,
			s("Nouvelle valeur"),
			s("Modifier le stock")
		);

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

	public function crement(Product $eProduct, ?string $sign, string $label, string $header): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/stock:doUpdate');

			$h .= $form->hidden('id', $eProduct['id']);

			if($sign !== NULL) {
				$h .= $form->hidden('sign', $sign);
			}

			$h .= $form->group(
				$label,
				$form->inputGroup(
					($sign ? '<div class="input-group-addon">'.$sign.'</div>' : '').
					$form->dynamicField(new Stock(), 'newValue', function(\PropertyDescriber $d) use ($eProduct, $sign) {
						$d->attributes['onrender'] = 'this.focus();';
						if($sign === NULL) {
							$d->placeholder = $eProduct['stock'];
						}
					}).
					'<div class="input-group-addon">'.\main\UnitUi::getNeutral($eProduct['unit']).'</div>'
				)
			);

			$h .= $form->dynamicGroup(new Stock(), 'comment');

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: $header,
			body: $h,
			subTitle: ProductUi::getPanelHeader($eProduct)
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Stock::model()->describer($property, [
			'comment' => s("Commentaire")
		]);

		switch($property) {

			case 'comment' :
				$d->placeholder = s("Tapez ici un commentaire facultatif sur l'évolution du stock");
				break;

		}

		return $d;

	}

}
?>
