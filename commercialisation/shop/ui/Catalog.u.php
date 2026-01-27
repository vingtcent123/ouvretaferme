<?php
namespace shop;

class CatalogUi {

	public function getList(\farm\Farm $eFarm, \Collection $cCatalog, array $products, Catalog $eCatalogSelected) {

		$h = $this->getCatalogs($cCatalog, $products, $eCatalogSelected);

		if($eCatalogSelected->empty()) {
			$h .= '<div class="util-info">'.s("Sélectionnez un catalogue pour voir les produits associés !").'</div>';
			return $h;
		}

		$eCatalogSelected->expects(['cProduct', 'cCategory']);

		[
			'cProduct' => $cProduct,
			'cCategory' => $cCategory,
		] = $eCatalogSelected;

		$hasCustomPrice = $eCatalogSelected['cProduct']->contains(fn($eProduct) => (
			$eProduct['parent'] === FALSE and
			$eProduct['price'] !== $eProduct['product'][$eProduct['type'].'Price']
		));

		$h .= '<div class="mb-1 text-end">';
			$h .= '<a href="/shop/product:createCollection?catalog='.$eCatalogSelected['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des produits").'</a> ';
			$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.encode($eCatalogSelected['name']).'</div>';
				$h .= '<a href="/shop/catalog:update?id='.$eCatalogSelected['id'].'" class="dropdown-item">'.s("Modifier le catalogue").'</a>';
				$h .= '<a href="/selling/sale:createCollection?farm='.$eFarm['id'].'&catalog='.$eCatalogSelected['id'].'" class="dropdown-item">'.s("Créer une vente depuis le catalogue").'</a>';
				if($hasCustomPrice) {
					$h .= '<a data-ajax="/shop/catalog:doSynchronizePrices" post-id='.$eCatalogSelected['id'].'" data-confirm="'.s("Les prix différents des prix de base seront actualisés. Voulez-vous continuer ?").'" class="dropdown-item">'.s("Synchroniser les prix du catalogue<br/> avec les prix de base des produits").'</a>';
				}
				$h .= '<div class="dropdown-divider"></div>';
				$h .= '<div class="dropdown-subtitle">'.\Asset::icon('exclamation-circle').'  '.s("Zone de danger").'  '.\Asset::icon('exclamation-circle').'</div>';
				$h .= '<a data-ajax="/shop/catalog:doDelete" post-id="'.$eCatalogSelected['id'].'" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer définitivement ce catalogue ? Vous ne pourrez plus y accéder mais il restera actif sur les ventes où il est actuellement configuré.").'" class="dropdown-item">'.s("Supprimer le catalogue").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		if($eCatalogSelected['comment']) {
			$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Commentaire").'</h4>';
				$h .= encode($eCatalogSelected['comment']).' &raquo;';
			$h .= '</div>';
		}

		if($cProduct->empty()) {

			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Ce catalogue de vente est encore vide").'</h4>';
				$h .= '<p>'.s("Ajoutez les produits qui sont actuellement disponibles à la vente dans votre ferme avant de déployer le catalogue sur vos boutiques en ligne.").'</p>';
				$h .= '<a href="/shop/product:createCollection?catalog='.$eCatalogSelected['id'].'" class="btn btn-secondary">'.s("Ajouter des produits").'</a>';
			$h .= '</div>';

		} else {
			$h .= new \shop\ProductUi()->getUpdateCatalog($eFarm, $eCatalogSelected, $cProduct, $cCategory);
		}

		return $h;

	}

	protected function getCatalogs(\Collection $cCatalog, array $products, Catalog $eCatalogSelected): string {

		$h = '';

		if($cCatalog->notEmpty()) {

			$list = function(string $class) use ($cCatalog, $eCatalogSelected, $products) {

				$h = '';

				foreach($cCatalog as $eCatalog) {

					$url = \util\HttpUi::setArgument(LIME_REQUEST, 'catalog', $eCatalog['id'], FALSE);

					$h .= '<a href="'.$url.'" class="'.$class.' '.(($eCatalogSelected->notEmpty() and $eCatalogSelected['id'] === $eCatalog['id']) ? 'selected' : '').'">'.encode($eCatalog['name']).' <small class="'.$class.'-count">'.($products[$eCatalog['id']] ?? 0).'</small></a>';

				}

				return $h;

			};

			if($cCatalog->count() > 5) {

				$h .= '<div class="btn-group mb-1">';
					$h .= '<div class="btn btn-group-addon btn-outline-primary">'.s("Catalogue").'</div>';
					$h .= '<a class="dropdown-toggle btn btn-primary" data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-id="product-dropdown-categories">';
						$h .= encode($cCatalog[$eCatalogSelected['id']]['name']);
						$h .= '<small class="dropdown-item-count">'.($products[$eCatalogSelected->notEmpty() ? $eCatalogSelected['id'] : NULL] ?? 0).'</small>';
					$h .= '</a>';
				$h .= '</div>';
				$h .= '<div class="dropdown-list '.($cCatalog->count() > 10 ? 'dropdown-list-2' : '').'" data-dropdown-id="product-dropdown-categories-list">';
					$h .= '<div class="dropdown-title">'.s("Catalogues").'</div>';
					$h .= $list('dropdown-item');
				$h .= '</div>';

			} else {

				$h .= '<div class="tabs-item">';
					$h .= $list('tab-item');
				$h .= '</div>';

			}

		}

		return $h;

	}

	public function getOne(Catalog $eCatalog): \Panel {

		if($eCatalog['cProduct']->empty()) {
			$h = '<div class="util-empty">'.s("Ce catalogue de vente est vide.").'</div>';
		} else {
			$h = new \shop\ProductUi()->getUpdateCatalog($eCatalog['farm'], $eCatalog, $eCatalog['cProduct'], $eCatalog['cCategory']);
		}

		return new \Panel(
			id: 'panel-catalog-show',
			title: encode($eCatalog['name']),
			body: $h
		);

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eCatalog = new Catalog([
			'farm' => $eFarm
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/catalog:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eCatalog, ['name*', 'type*']);
			$h .= $form->group(
				content: $form->submit(s("Créer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-catalog-create',
			title: s("Créer un nouveau catalogue"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Catalog $eCatalog): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/catalog:doUpdate');

			$h .= $form->hidden('id', $eCatalog['id']);
			$h .= $form->dynamicGroups($eCatalog, ['name', 'comment']);
			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-catalog-update',
			title: s("Modifier un catalogue"),
			body: $h,
			close: 'reload'
		);

	}

	protected function getTypeDescriber(\farm\Farm $eFarm, string $for) {

		return function(\PropertyDescriber $d) use($eFarm, $for) {

			$d->values = $eFarm->getConf('hasVat') ?
				[
					Shop::PRIVATE => s("Utiliser les prix particuliers").' <span class="util-annotation">'.s("/ affichage TTC sur le catalogue").'</span>',
					Shop::PRO => s("Utiliser les prix professionnels").' <span class="util-annotation">'.s("/ affichage HT sur le catalogue").'</span>',
				] :
				[
					Shop::PRIVATE => s("Utiliser les prix particuliers"),
					Shop::PRO => s("Utiliser les prix professionnels"),
				];

		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Catalog::model()->describer($property, [
			'type' => s("Grille tarifaire"),
			'name' => s("Nom du catalogue"),
			'comment' => s("Observations"),
		]);

		switch($property) {

			case 'type' :
				$d->values = function(Catalog $e) {

					return $e['farm']->getConf('hasVat') ?
						[
							Shop::PRIVATE => s("Utiliser les prix particuliers").' <span class="util-annotation">'.s("/ affichage TTC sur le catalogue").'</span>',
							Shop::PRO => s("Utiliser les prix professionnels").' <span class="util-annotation">'.s("/ affichage HT sur le catalogue").'</span>',
						] :
						[
							Shop::PRIVATE => s("Utiliser les prix particuliers"),
							Shop::PRO => s("Utiliser les prix professionnels"),
						];

				};
				break;

			case 'productsList' :
				$d->field = function(\util\FormUi $form, Catalog $e) {
					return new \selling\ItemUi()->getCreateList(
						$e['cProduct'], $e['cCategory'],
						fn($cProduct) => ProductUi::getCreateByCategory($form, $e['farm'], $e['type'], $cProduct)
					);
				};
				break;

		}

		return $d;

	}


}
?>
