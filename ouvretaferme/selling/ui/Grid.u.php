<?php
namespace selling;

class GridUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');

	}

	public function getGridByProduct(Product $eProduct, \Collection $cGrid): string {

		if($eProduct['pro'] === FALSE) {
			return '';
		}

		$h = '<div class="h-line">';
			$h .= '<h3>'.s("Grille tarifaire personnalisée pour les pros").'</h3>';
			$h .= '<div>';
				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-primary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.encode($eProduct->getName()).'</div>';
					if($cGrid->notEmpty()) {
						$h .= '<a href="/selling/product:updateGrid?id='.$eProduct['id'].'" class="dropdown-item">'.s("Modifier la grille").'</a>';
						$h .= '<a data-ajax="/selling/product:doDeleteGrid" post-id="'.$eProduct['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de l'ensemble de la grille personnalisée pour ce client ?").'">'.s("Supprimer toute la grille personnalisée").'</a>';
					} else {
						$h .= '<a href="/selling/product:updateGrid?id='.$eProduct['id'].'" class="dropdown-item">'.s("Personnaliser la grille").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		if($cGrid->empty()) {

			$h .= '<div class="util-info">'.s("Aucune personnalisation de prix pour ce produit.").'</div>';

		} else {

			$h .= '<div class="util-info">';
				$h .= s("Uniquement les prix personnalisés pour ce produit et en cours de validité.");
			$h .= '</div>';

			$h .= '<table class="customer-price tr-even">';

			$h .= '<tr>';
				$h .= '<th>'.s("Client").'</th>';
				$h .= '<th>'.s("Prix").'</th>';
				$h .= '<th>'.s("Colis").'</th>';
				$h .= '<th>'.s("Depuis le...").'</th>';
			$h .= '</tr>';

			foreach($cGrid as $eGrid) {

				// Pas de changement par rapport aux prix de base
				if($eGrid['price'] === NULL and $eGrid['packaging'] === NULL) {
					continue;
				}

				$eCustomer = $eGrid['customer'];

				$taxes = $eProduct['farm']['selling']['hasVat'] ? CustomerUi::getTaxes($eCustomer['type']) : '';
				$unit = ProductUi::p('unit')->values[$eProduct['unit']];

				$h .= '<tr>';

					$h .= '<td>';
						$h .= CustomerUi::link($eCustomer);
						$h .= ' <span class="util-annotation">'.CustomerUi::getCategory($eCustomer).'</span>';
					$h .= '</td>';

					$h .= '<td>';
						$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$taxes.' / '.$unit : '-');
					$h .= '</td>';

					$h .= '<td>';
						$h .= $eGrid->quick('packaging', $eGrid['packaging'] ? $eGrid['packaging'].' '.$unit : '-');
					$h .= '</td>';

					$h .= '<td>';
						$h .= \util\DateUi::numeric($eGrid['updatedAt'], \util\DateUi::DATE);
					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</table>';

		}

		return $h;

	}

	public function getGridByCustomer(Customer $eCustomer, \Collection $cGrid): string {

		if($eCustomer['type'] === Customer::PRIVATE) {
			return '';
		}

		$h = '<div class="h-line">';

			if($cGrid->empty()) {

				$h .= '<div class="util-info">'.s("Aucune personnalisation de prix pour ce client.").'</div>';

			} else {

				$h .= '<div class="util-info">';
					$h .= s("Uniquement les prix personnalisés pour ce client et en cours de validité.");
				$h .= '</div>';

			}
			$h .= '<div>';
				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list bg-secondary">';
					$h .= '<div class="dropdown-title">'.encode($eCustomer['name']).'</div>';
					if($cGrid->notEmpty()) {
						$h .= '<a href="/selling/customer:updateGrid?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Modifier la grille").'</a>';
						$h .= '<a data-ajax="/selling/customer:doDeleteGrid" post-id="'.$eCustomer['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de l'ensemble de la grille personnalisée pour ce client ?").'">'.s("Supprimer toute la grille personnalisée").'</a>';
					} else {
						$h .= '<a href="/selling/customer:updateGrid?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Personnaliser la grille").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		if($cGrid->notEmpty()) {

			$h .= '<table class="customer-price tr-even">';

			$h .= '<tr>';
				$h .= '<th class="customer-price-vignette"></th>';
				$h .= '<th>'.s("Produit").'</th>';
				$h .= '<th>'.s("Prix").'</th>';
				if($eCustomer['type'] === Customer::PRO) {
					$h .= '<th>'.s("Colis").'</th>';
				}
			$h .= '</tr>';

			foreach($cGrid as $eGrid) {

				// Pas de changement par rapport aux prix de base
				if($eGrid['price'] === NULL and $eGrid['packaging'] === NULL) {
					continue;
				}

				$eProduct = $eGrid['product'];

				$taxes = $eCustomer['farm']['selling']['hasVat'] ? CustomerUi::getTaxes($eCustomer['type']) : '';

				$unit = ProductUi::p('unit')->values[$eProduct['unit']];

				$h .= '<tr>';

					$h .= '<td class="customer-price-vignette">';
						$h .= ProductUi::getVignette($eProduct, '4rem');
					$h .= '</td>';

					$h .= '<td>';
						$h .= '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
						if($eProduct['size']) {
							$h .= '<div><small>'.s("Calibre {value}", '<u>'.encode($eProduct['size']).'</u>').'</small></div>';
						}
					$h .= '</td>';

					$h .= '<td>';
						$h .= '<div>';
							$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$taxes.' / '.$unit : '-');
						$h .= '</div>';
						$defaultPrice = $eProduct[$eCustomer['type'].'Price'];
						if($defaultPrice !== NULL) {
							$h .= '<small class="color-muted">';
								$h .= s("Base : {value}", ['value' => \util\TextUi::money($defaultPrice)]);
							$h .= '</small>';
						}
					$h .= '</td>';

					if($eCustomer['type'] === Customer::PRO) {

						$h .= '<td>';

							$h .= '<div>';
								$h .= $eGrid->quick('packaging', $eGrid['packaging'] ? $eGrid['packaging'].' '.$unit : '-');
							$h .= '</div>';
							if($eProduct['proPackaging'] !== NULL) {
								$h .= '<small class="color-muted">';
									$h .= s("Base : {value} {unit}", ['value' => $eProduct['proPackaging'], 'unit' => $unit]);
								$h .= '</small>';
							}
						$h .= '</td>';

					}

				$h .= '</tr>';

			}

			$h .= '</table>';

		}

		return $h;

	}

	public function updateByCustomer(Customer $eCustomer, \Collection $cProduct): \Panel {

		$form = new \util\FormUi();

		$h = $form->hidden('id', $eCustomer['id']);

		$h .= '<p class="util-info">'.s("Les prix et conditionnements définis ici ne s'appliquent qu'à {value} et ont la priorité sur les valeurs de base.", encode($eCustomer['name'])).'</p>';

		$h .= '<table class="tr-bordered stick-xs">';

		$h .= '<tr>';
			$h .= '<th class="customer-price-vignette"></th>';
			$h .= '<th>'.s("Produit").'</th>';
			$h .= '<th>'.s("Prix").'</th>';
			if($eCustomer['type'] === Customer::PRO) {
				$h .= '<th>'.s("Colis").'</th>';
			}
		$h .= '</tr>';

		foreach($cProduct as $eProduct) {

			$eGrid = $eProduct['eGrid'];

			$unit = ProductUi::p('unit')->values[$eProduct['unit']];
			$taxes = $eCustomer['farm']['selling']['hasVat'] ? CustomerUi::getTaxes($eCustomer['type']) : '';

			$h .= '<tr>';

				$h .= '<td class="customer-price-vignette">';
					$h .= ProductUi::getVignette($eProduct, '4rem');
				$h .= '</td>';

				$h .= '<td>';
					$h .= '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
					if($eProduct['size']) {
						$h .= '<div><small>'.s("Calibre {value}", '<u>'.encode($eProduct['size']).'</u>').'</small></div>';
					}
				$h .= '</td>';

				$defaultPrice = $eProduct[$eCustomer['type'].'Price'];

				$h .= '<td data-wrapper="price['.$eProduct['id'].']">';
					$h .= '<div>';
						$h .= $form->inputGroup(
							$form->number('price['.$eProduct['id'].']', $eGrid['price'] ?? '', ['step' => 0.01]).
							$form->addon('€ '.$taxes.' / '.$unit)
						);
					$h .= '</div>';
					if($defaultPrice !== NULL) {
						$h .= '<small class="color-muted">';
							$h .= s("Base : {value}", ['value' => \util\TextUi::money($defaultPrice)]);
						$h .= '</small>';
					}
				$h .= '</td>';

				if($eCustomer['type'] === Customer::PRO) {

					$h .= '<td data-wrapper="packaging['.$eProduct['id'].']">';
						$h .= '<div>';
							$h .= $form->inputGroup(
								$form->number('packaging['.$eProduct['id'].']', $eGrid['packaging'] ?? '', ['step' => 0.01]).
								$form->addon($unit)
							);
						$h .= '</div>';
						if($eProduct['proPackaging'] !== NULL) {
							$h .= '<small class="color-muted">';
								$h .= s("Base : {value} {unit}", ['value' => $eProduct['proPackaging'], 'unit' => $unit]);
							$h .= '</small>';
						}
					$h .= '</td>';

				}

			$h .= '</tr>';

		}

		$h .= '</table>';

		return new \Panel(
			title: s("Personnaliser la grille tarifaire"),
			dialogOpen: $form->openAjax('/selling/customer:doUpdateGrid', ['class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			subTitle: (new CustomerUi())->getPanelHeader($eCustomer),
			footer: $form->submit(s("Enregistrer")),
			close: 'reload'
		);

	}

	public function updateByProduct(Product $eProduct, \Collection $cCustomer): \Panel {

		$form = new \util\FormUi();

		$h = $form->hidden('id', $eProduct['id']);

		$h .= '<table class="tr-bordered stick-xs">';

		$h .= '<tr>';
			$h .= '<th>'.s("Client").'</th>';
			$h .= '<th>'.s("Prix").'</th>';
			$h .= '<th>'.s("Colis").'</th>';
		$h .= '</tr>';

		foreach($cCustomer as $eCustomer) {

			$eGrid = $eCustomer['eGrid'];

			$unit = ProductUi::p('unit')->values[$eProduct['unit']];
			$taxes = $eProduct['farm']['selling']['hasVat'] ? CustomerUi::getTaxes($eCustomer['type']) : '';

			$h .= '<tr>';

				$h .= '<td>';
					$h .= CustomerUi::link($eCustomer);
				$h .= '</td>';

				$h .= '<td data-wrapper="price['.$eCustomer['id'].']">';
					$h .= $form->inputGroup(
						$form->number('price['.$eCustomer['id'].']', $eGrid['price'] ?? '', ['step' => 0.01]).
						$form->addon('€ '.$taxes.' / '.$unit)
					);
				$h .= '</td>';

				$h .= '<td data-wrapper="packaging['.$eCustomer['id'].']">';

					if($eCustomer['type'] === Customer::PRO) {
						$h .= $form->inputGroup(
							$form->number('packaging['.$eCustomer['id'].']', $eGrid['packaging'] ?? '', ['step' => 0.01]).
							$form->addon($unit)
						);
					} else {
						$h .= '-';
					}

				$h .= '</td>';

			$h .= '</tr>';

		}

		$h .= '</table>';

		return new \Panel(
			title: s("Personnaliser la grille tarifaire"),
			dialogOpen: $form->openAjax('/selling/product:doUpdateGrid', ['class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			subTitle: (new ProductUi())->getPanelHeader($eProduct),
			footer: $form->submit(s("Enregistrer")),
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Grid::model()->describer($property);

		return $d;

	}

}
?>
