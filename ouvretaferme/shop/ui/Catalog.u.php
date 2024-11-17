<?php
namespace shop;

class CatalogUi {

	public function getList(\farm\Farm $eFarm, \Collection $cCatalog, Catalog $eCatalogSelected) {

		$h = $this->getCatalogs($cCatalog, $eCatalogSelected);

		if($eCatalogSelected->empty()) {
			$h .= '<div class="util-info">'.s("Sélectionnez un catalogue pour voir les produits associés !").'</div>';
			return $h;
		}

		$eCatalogSelected->expects(['cProduct', 'cCategory']);

		[
			'cProduct' => $cProduct,
			'cCategory' => $cCategory,
		] = $eCatalogSelected;

		$h .= '<div class="util-action">';

			$h .= '<div>';
				if($cProduct->notEmpty()) {
					$h .= '<h2>';
						$h .= p("{value} produit", "{value} produits", $cProduct->count());
					$h .= '</h2>';
				} else {
				}
			$h .= '</div>';
			$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.encode($eCatalogSelected['name']).'</div>';
				$h .= '<a href="/shop/catalog:update?id='.$eCatalogSelected['id'].'" class="dropdown-item">'.s("Modifier le catalogue").'</a>';
				$h .= '<div class="dropdown-divider"></div>';
				$h .= '<a data-ajax="/shop/catalog:doDelete" post-id="'.$eCatalogSelected['id'].'" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer ce catalogue ? Vous ne pourrez plus y accéder mais il restera actif sur les ventes où il est actuellement configuré.").'" class="dropdown-item">'.s("Supprimer le catalogue").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		if($cProduct->empty()) {

			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Ce catalogue de vente est encore vide").'</h4>';
				$h .= '<p>'.s("Ajoutez les produits qui sont actuellement disponibles à la vente dans votre ferme avant de déployer le catalogue sur vos boutiques en ligne.").'</p>';
				$h .= '<a href="/shop/product:create?catalog='.$eCatalogSelected['id'].'" class="btn btn-secondary">'.s("Ajouter des produits").'</a>';
			$h .= '</div>';

		} else {
			$h .= (new \shop\ProductUi())->getUpdateList($eCatalogSelected, $cProduct, $cCategory);
		}

		return $h;

	}

	protected function getCatalogs(\Collection $cCatalog, Catalog $eCatalogSelected): string {

		$h = '';

		if($cCatalog->notEmpty()) {

			$h .= '<div class="tabs-item">';

				foreach($cCatalog as $eCatalog) {

					$url = \util\HttpUi::setArgument(LIME_REQUEST, 'catalog', $eCatalog['id'], FALSE);

					$h .= '<a href="'.$url.'" class="tab-item '.(($eCatalogSelected->notEmpty() and $eCatalogSelected['id'] === $eCatalog['id']) ? 'selected' : '').'">'.encode($eCatalog['name']).'</a>';

				}

			$h .= '</div>';

		}

		return $h;

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
			title: s("Créer un nouveau catalogue"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Catalog $eCatalog): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/catalog:doUpdate');

			$h .= $form->hidden('id', $eCatalog['id']);
			$h .= $form->dynamicGroups($eCatalog, ['name']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un catalogue"),
			body: $h,
			close: 'reload'
		);

	}

	protected function getTypeDescriber(\farm\Farm $eFarm, string $for) {

		return function(\PropertyDescriber $d) use ($eFarm, $for) {

			$d->values = $eFarm->getSelling('hasVat') ?
				[
					Shop::PRIVATE => s("Utiliser les prix particuliers").' <span class="util-annotation">'.s("affichage TTC sur le catalogue").'</span>',
					Shop::PRO => s("Utiliser les prix professionnels").' <span class="util-annotation">'.s("affichage HT sur le catalogue").'</span>',
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
		]);

		switch($property) {

			case 'type' :
				$d->values = function(Catalog $e) {

					return $e['farm']->getSelling('hasVat') ?
						[
							Shop::PRIVATE => s("Utiliser les prix particuliers").' <span class="util-annotation">'.s("affichage TTC sur le catalogue").'</span>',
							Shop::PRO => s("Utiliser les prix professionnels").' <span class="util-annotation">'.s("affichage HT sur le catalogue").'</span>',
						] :
						[
							Shop::PRIVATE => s("Utiliser les prix particuliers"),
							Shop::PRO => s("Utiliser les prix professionnels"),
						];

				};
				break;

			case 'productsList' :
				$d->field = function(\util\FormUi $form, Catalog $e) {
					return (new ProductUi())->getCreateList($form, $e['farm'], $e['type'], $e['cProduct'], $e['cCategory']);
				};
				break;

		}

		return $d;

	}


}
?>
