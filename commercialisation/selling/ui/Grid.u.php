<?php
namespace selling;

class GridUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'grid.js');

	}

	public function create(Grid $e): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/grid:doCreate', ['id' => 'grid-create']);

			$h .= $form->hidden('farm', $e['farm']['id']);

			if($e['product']->notEmpty()) {
				$h .= $form->hidden('product', $e['product']['id']);
				$product = ProductUi::getVignette($e['product'], '2rem').'  '.encode($e['product']['name']);
			} else {
				$product = $form->dynamicField($e, 'product');
			}

			$h .= $form->group(
				self::p('product')->label,
				$product
			);

			if($e['customer']->notEmpty()) {

				$h .= $form->hidden('customer', $e['customer']['id']);

				$h .= $form->group(
					self::p('customer')->label,
					'<b>'.encode($e['customer']->getName()).'</b>  (<a href="'.\util\HttpUi::removeArgument(LIME_REQUEST, 'customer').'">'.s("changer").'</a>)'
				);
			} else if($e['group']->notEmpty()) {

				$h .= $form->hidden('group', $e['group']['id']);

				$h .= $form->group(
					self::p('group')->label,
					'<span class="util-badge" style="background-color: '.$e['group']['color'].'">'.encode($e['group']['name']).'</span>  (<a href="'.\util\HttpUi::removeArgument(LIME_REQUEST, 'group').'">'.s("changer").'</a>)'
				);
			} else {

				$h .= $form->group(
					s("Pour"),
					'<div class="mb-1">'.$form->dynamicField($e, 'customer').'</div>'.
					'<div>'.$form->dynamicField($e, 'group').'</div>'
				);

			}

			if(
				$e['product']->notEmpty() and
				($e['customer']->notEmpty() or $e['group']->notEmpty())
			) {

				$h .= $form->dynamicGroup($e, 'price');

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-grid-create',
			title: s("Ajouter un prix"),
			body: $h
		);

	}

	public function getGridByProduct(Product $eProduct, \Collection $cGrid): string {

		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Prix personnalisés par client").'</h3>';
			$h .= '<div>';
				$h .= '<a href="/doc/selling:pricing" class="btn btn-outline-primary">'.\asset::Icon('person-raised-hand').' '.s("Aide").'</a> ';
				$h .= '<a href="/selling/grid:create?farm='.$eProduct['farm']['id'].'&product='.$eProduct['id'].'" class="btn btn-outline-primary">'.s("Ajouter un prix").'</a>';
				if($cGrid->notEmpty()) {
					$h .= ' <a data-ajax="/selling/grid:doDeleteByProduct" post-id="'.$eProduct['id'].'" class="btn btn-outline-danger" data-confirm="'.s("Confirmer la suppression de tous les prix personnalisés ?").'">'.s("Tout supprimer").'</a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		if($cGrid->empty()) {

			$h .= '<div class="util-empty">'.s("Aucune personnalisation de prix pour ce produit.").'</div>';

		} else {

			$h .= '<div class="util-overflow-sm">';

				$h .= '<table class="customer-price tr-even">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th colspan="2">'.s("Clients").'</th>';
							$h .= '<th>'.s("Prix").'</th>';
							$h .= '<th>'.s("Depuis le").'</th>';
							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';
					$h .= '<tbody>';

						foreach($cGrid as $eGrid) {

							// Pas de changement par rapport aux prix de base
							if($eGrid['price'] === NULL) {
								continue;
							}

							$taxes = $eProduct['farm']->getConf('hasVat') ? CustomerUi::getTaxes($eGrid->getType()) : '';

							$h .= '<tr>';

								$h .= '<td class="td-min-content">';
									if($eGrid['customer']->notEmpty()) {
										$h .= CustomerUi::link($eGrid['customer']);
									} else if($eGrid['group']->notEmpty()) {
										$h .= CustomerGroupUi::link($eGrid['group']);
									}
								$h .= '</td>';

								$h .= '<td class="font-sm">';
									if($eGrid['customer']->notEmpty()) {
										$h .= CustomerUi::getCategory($eGrid['customer']);
									} else if($eGrid['group']->notEmpty()) {
										$h .= CustomerUi::getType($eGrid['group']);
									}
								$h .= '</td>';

								$h .= '<td>';
									if($eGrid['priceInitial'] !== NULL) {
										$h .= new PriceUi()->priceWithoutDiscount($eGrid['priceInitial'], unit: ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit'], short: TRUE));
									}
									$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$taxes.\selling\UnitUi::getBy($eProduct['unit'], short: TRUE) : '-');
								$h .= '</td>';

								$h .= '<td>';
									$h .= \util\DateUi::numeric($eGrid['updatedAt'], \util\DateUi::DATE);
								$h .= '</td>';

								$h .= '<td class="td-min-content">';
									$h .= ' <a data-ajax="/selling/grid:doDelete" post-id="'.$eGrid['id'].'" class="btn btn-danger" data-confirm="'.s("Confirmer la suppression de ce prix personnalisé ?").'">'.\Asset::icon('trash').'</a>';
								$h .= '</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';
				$h .= '</table>';

			$h .= '</div>';

		}

		return $h;

	}

	public function getGridByCustomer(Customer $eCustomer, \Collection $cGrid, \Collection $cGridGroup): string {

		$h = '<div class="util-title">';

			if($cGrid->empty()) {
				$h .= '<div class="util-empty">'.s("Vous n'avez pas personnalisé de prix pour ce client.").'</div>';
			} else {
				$h .= '<div>';

					if($cGridGroup->notEmpty()) {
						$h .= '<h3>'.s("Prix personnalisés pour ce client").'</h3>';
					}

				$h .= '</div>';
			}

			$h .= '<div>';
				$h .= '<a href="/doc/selling:pricing" class="btn btn-outline-primary">'.\asset::Icon('person-raised-hand').' '.s("Aide").'</a> ';
				$h .= '<a href="/selling/grid:create?farm='.$eCustomer['farm']['id'].'&customer='.$eCustomer['id'].'" class="btn btn-outline-primary">'.s("Ajouter un prix").'</a> ';
				if($cGrid->notEmpty()) {
					$h .= '<a data-ajax="/selling/grid:doDeleteByCustomer" post-id="'.$eCustomer['id'].'" class="btn btn-outline-danger" data-confirm="'.s("Confirmer la suppression de l'ensemble des prix personnalisés pour ce client ?").'">'.s("Tout supprimer").'</a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= $this->getGridWithProduct($eCustomer, $cGrid);
		$h .= $this->getGridByGroups($eCustomer, $cGrid, $cGridGroup);

		return $h;

	}

	public function getGridByGroups(Customer $eCustomer, \Collection $cCustomerGroup, \Collection $cGridGroup): string {

		if($cGridGroup->empty()) {
			return '';
		}

		$h = '<h3>'.s("Autres prix personnalisés applicables à ce client").'</h3>';

		$h .= '<div class="util-info">';
			$h .= p("Ce client appartient à un groupe qui lui permet de bénéficier d'autres prix personnalisés par rapport aux prix de base.", "Ce client appartient à des groupes qui lui permettent de bénéficier d'autres prix personnalisés par rapport aux prix de base.", count($eCustomer['groups']));
		$h .= '</div>';

		$h .= $this->getGridWithProduct($eCustomer, $cGridGroup, exclude: $cCustomerGroup->getColumnCollection('product')->getIds(), hide: ['actions'], show: ['group']);

		return $h;

	}

	public function getGridByGroup(CustomerGroup $eCustomerGroup, \Collection $cGrid): string {

		$h = '<div class="util-title">';

			if($cGrid->empty()) {
				$h .= '<div class="util-empty">'.s("Vous n'avez pas personnalisé de prix pour ce groupe.").'</div>';
			} else {
				$h .= '<div></div>';
			}

			$h .= '<div>';
				$h .= '<a href="/doc/selling:pricing" class="btn btn-outline-primary">'.\asset::Icon('person-raised-hand').' '.s("Aide").'</a> ';
				$h .= '<a href="/selling/grid:create?farm='.$eCustomerGroup['farm']['id'].'&group='.$eCustomerGroup['id'].'" class="btn btn-outline-primary">'.s("Ajouter un prix").'</a> ';
				if($cGrid->notEmpty()) {
					$h .= '<a data-ajax="/selling/grid:doDeleteByGroup" post-id="'.$eCustomerGroup['id'].'" class="btn btn-outline-danger" data-confirm="'.s("Confirmer la suppression de l'ensemble des prix personnalisés pour ce groupe ?").'">'.s("Tout supprimer").'</a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= $this->getGridWithProduct($eCustomerGroup, $cGrid);

		return $h;

	}

	public function getGridWithProduct(Customer|CustomerGroup $eSource, \Collection $cGrid, ?array $exclude = NULL, array $hide = [], array $show = []): string {

		if($cGrid->empty()) {
			return '';
		}

		$h = '<div class="util-overflow-md mb-3">';

			$h .= '<table class="customer-price tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th>'.s("Produit").'</th>';
						if(in_array('group', $show)) {
							$h .= '<th>'.s("Groupe").'</th>';
						}
						$h .= '<th class="text-end">'.s("Prix de base").'</th>';
						$h .= '<th>'.s("Prix personnalisé").'</th>';
						$h .= '<th>'.s("Depuis le").'</th>';
						if(in_array('actions', $hide) === FALSE) {
							$h .= '<th></th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cGrid as $eGrid) {

						// Pas de changement par rapport aux prix de base
						if($eGrid['price'] === NULL) {
							continue;
						}

						$eProduct = $eGrid['product'];

						$taxes = $eSource['farm']->getConf('hasVat') ? CustomerUi::getTaxes($eSource['type']) : '';
						$priceSuffix = $taxes.\selling\UnitUi::getBy($eProduct['unit'], short: TRUE);

						$isExcluded = ($exclude !== NULL and in_array($eGrid['product']['id'], $exclude));

						$h .= '<tr '.($isExcluded ? 'style="opacity: 0.2"' : '').'>';

							$h .= '<td class="td-min-content">';
								$h .= ProductUi::getVignette($eProduct, '3rem');
							$h .= '</td>';

							$h .= '<td>';
								$h .= '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
								if($eProduct['additional']) {
									$h .= '<div><small><u>'.encode($eProduct['additional']).'</u></div>';
								}
							$h .= '</td>';

							if(in_array('group', $show)) {
								$h .= '<td>'.CustomerGroupUi::link($eGrid['group']).'</td>';
							}

							$h .= '<td class="text-end">';
								$defaultPrice = $eProduct[$eSource['type'].'Price'];
								if($defaultPrice !== NULL) {
									$h .= \util\TextUi::money($defaultPrice).' '.$priceSuffix;
								}
							$h .= '</td>';

							$h .= '<td>';
								if($eGrid['priceInitial'] !== NULL) {
									$h .= new PriceUi()->priceWithoutDiscount($eGrid['priceInitial'], unit: ' '.$priceSuffix);
								}
								$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$priceSuffix : '-');
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eGrid['updatedAt'], \util\DateUi::DATE);
							$h .= '</td>';

							if(in_array('actions', $hide) === FALSE) {
								$h .= '<td class="td-min-content">';
									$h .= ' <a data-ajax="/selling/grid:doDelete" post-id="'.$eGrid['id'].'" class="btn btn-danger" data-confirm="'.s("Confirmer la suppression de ce prix personnalisé ?").'">'.\Asset::icon('trash').'</a>';
								$h .= '</td>';
							}

						$h .= '</tr>';

						if($exclude !== NULL) {
							$exclude[] = $eProduct['id'];
						}

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Grid::model()->describer($property, [
			'product' => s("Produit"),
			'group' => s("Clients"),
			'customer' => s("Client"),
			'price' => s("Prix"),
			'priceDiscount' => s("Prix remisé"),
		]);

		switch($property) {

			case 'product' :
				$d->attributes['id'] = 'grid-write-product';
				$d->autocompleteDispatch = '#grid-write-product';
				$d->autocompleteBody = function(\util\FormUi $form, Grid $e) {

					$e->expects([
						'farm',
						'customer',
						'group'
					]);

					return [
						'farm' => $e['farm']['id'],
						'type' => $e['customer']['type'] ?? $e['group']['type'] ?? NULL
					];

				};
				new ProductUi()->query($d);
				break;

			case 'customer':
				$d->attributes['id'] = 'grid-write-customer';
				$d->autocompleteDispatch = '#grid-write-customer';
				$d->autocompleteBody = function(\util\FormUi $form, Grid $e) {
					return [
						'farm' => $e['farm']['id'],
					];
				};
				new \selling\CustomerUi()->query($d);
				$d->prepend = s("Un client");
				break;

			case 'group':
				$d->values = fn(Grid $e) => $e['cGroup'] ?? $e->expects(['cGroup']);
				$d->prepend = s("Un groupe de clients");
				$d->attributes = [
					'onchange' => 'Grid.changeGroup(this);'
				];
				break;

			case 'price':
				$d->field = function(\util\FormUi $form, Grid $e) {

					$taxes = $e['farm']->getConf('hasVat') ? CustomerUi::getTaxes($e->getType()) : '';
					$unit = s("€ {taxes}", ['taxes' => $taxes.\selling\UnitUi::getBy($e['product']['unit'], short: TRUE)]);

					$price = ($e['priceInitial'] ?? NULL) !== NULL ? $e['priceInitial'] : $e['price'] ?? '';
					$priceDiscount = ($e['priceInitial'] ?? NULL) !== NULL ? $e['price'] ?? '' : '';

					$addon = $form->addon($unit);
					$addon .= $form->addon(new PriceUi()->getDiscountTrashAddon('grid'));

					$h = $form->inputGroup(
						$form->number('price', $price, [
							'step' => 0.01,
							'onrender' => 'this.focus();',
							'onfocus' => 'this.select()'
						]).
						$form->addon($unit),
					);
					$h .= new PriceUi()->getDiscountLink('grid', hasDiscountPrice: empty($priceDiscount) === FALSE);


					$h .= $form->inputGroup(
						$form->addon(s("Prix remisé")).
						$form->number('priceDiscount', $priceDiscount, ['step' => 0.01]).$addon,
						['class' => (empty($priceDiscount) ? ' hide' : ''), 'data-price-discount' => 'grid', 'data-wrapper' => 'priceDiscount']
					);

					return $h;

				};
				break;
		}

		return $d;

	}

}
?>
