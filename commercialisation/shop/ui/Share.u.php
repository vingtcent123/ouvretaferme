<?php
namespace shop;

class ShareUi {

	public function getList(\farm\Farm $eFarm, Shop $eShop, \Collection $cShare, \Collection $cDepartment): string {

		$h = '';

		if($cShare->empty()) {

			if($eShop->canWrite()) {

				$h .= '<div class="util-block-help">';
					$h .= '<h4>'.s("Inviter des producteurs sur la boutique").'</h4>';
					$h .= '<p>'.s("Il n'y a encore aucun producteur sur votre boutique collective.<br/>Vous pouvez envoyer des invitations, en commençant par votre ferme ?").'</p>';
					$h .= '<a href="/shop/:invite?id='.$eShop['id'].'" class="btn btn-secondary">'.s("Envoyer des invitations").'</a>';
				$h .= '</div>';

			} else {
				$h .= '<div class="util-empty">'.s("Il n'y a pas encore de producteur sur cette boutique.").'</div>';
			}

		} else {

			$h .= '<div class="util-title">';
				$h .= '<div>';
				$h .= '</div>';

				if($eShop->canWrite()) {
					$h .= '<div>';
						$h .= '<a href="/shop/:invite?id='.$eShop['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Inviter des producteurs").'</a>';
					$h .= '</div>';
				}
			$h .= '</div>';

			$h .= $this->getFarms($eFarm, $eShop, $cShare, $cDepartment);

		}

		return $h;

	}


	public function getFarms(\farm\Farm $eFarm, Shop $eShop, \Collection $cShare, \Collection $cDepartment): string {

		$h = '<div class="util-overflow-md">';

			$h .= '<table class="shop-share-list">';

				foreach($cShare as $eShare) {

					$cRange = $eShop['ccRange'][$eShare['farm']['id']] ?? new \Collection();
					$selected = ($eShare['farm']['id'] === $eFarm['id']);

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td class="td-min-content">';
								$h .= '<b>'.$eShare['position'].'.</b>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= \farm\FarmUi::getVignette($eShare['farm'], '3rem');
								$h .= '<span class="font-xl ml-1">'.encode($eShare['farm']['name']).'</span>';
								if($eShare['label'] !== NULL) {
									$h .= '  <small class="color-muted">'.$eShare->quick('label', ($eShare['label'] === NULL) ? '-' : encode($eShare['label'])).'</small>';
								}
								if($selected) {
									$h .= '  <span class="util-badge bg-primary">'.s("Votre ferme").'</span>';
								}
							$h .= '</td>';


							$h .= '<td class="td-min-content">';

								if($eShop->canWrite()) {

									if($eShare['position'] > 1) {
										$h .= '<a data-ajax="/shop/share:doIncrementPosition" post-id='.$eShare['id'].'" post-increment="-1" class="btn btn-secondary">'.\Asset::icon('arrow-up').'</a>  ';
									} else {
										$h .= '<a class="btn disabled">'.\Asset::icon('arrow-up').'</a>  ';
									}

									if($eShare['position'] !== $cShare->count()) {
										$h .= '<a data-ajax="/shop/share:doIncrementPosition" post-id='.$eShare['id'].'" post-increment="1" class="btn btn-secondary">'.\Asset::icon('arrow-down').'</a>  ';
									} else {
										$h .= '<a class="btn disabled">'.\Asset::icon('arrow-down').'</a>  ';
									}

									$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<a href="/shop/share:update?id='.$eShare['id'].'" class="dropdown-item">'.s("Paramétrer le producteur").'</a>';
										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<a data-ajax="/shop/share:doDelete" post-id="'.$eShare['id'].'" data-confirm="'.s("Le producteur n'aura plus accès à la boutique et les clients ne pourront plus commander ses produits. Voulez-vous continuer ?").'" class="dropdown-item">'.s("Retirer le producteur de la boutique").'</a>';
									$h .= '</div>';

								} else {

									if($eShare->isSelf()) {

										$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
										$h .= '<div class="dropdown-list bg-primary">';
											$h .= '<a href="/shop/share:update?id='.$eShare['id'].'" class="dropdown-item">'.s("Paramétrer la boutique").'</a>';
											$h .= '<div class="dropdown-divider"></div>';
											$h .= '<a data-ajax="/shop/share:doDelete" post-id="'.$eShare['id'].'" data-confirm="'.s("Voulez-vous réellement quitter cette boutique collective ?").'" class="dropdown-item">'.s("Quitter cette boutique").'</a>';
										$h .= '</div>';

									}

								}

							$h .= '</td>';

						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td colspan="4">';

								if($cRange->notEmpty()) {

									$h .= '<table>';
										$h .= '<thead>';

											$h .= '<tr>';
												$h .= '<th>'.s("Catalogue").'</th>';
												$h .= '<th style="width: 20rem">';
													if($cDepartment->notEmpty()) {
														$h .= s("Rayon");
													}
												$h .= '</th>';
												$h .= '<th>'.s("Activation").'</th>';
												$h .= '<th>'.s("Actions").'</th>';
											$h .= '</tr>';

										$h .= '</thead>';
										$h .= '<tbody>';

											foreach($cRange as $eRange) {
												$h .= '<tr>';
													$h .= $this->getRange($eRange, $cDepartment);
												$h .= '</tr>';
											}

										$h .= '</tbody>';
									$h .= '</table>';

								}

								if($selected) {

									$h .= '<a href="/shop/range:create?farm='.$eFarm['id'].'&shop='.$eShop['id'].'" class="btn btn-primary btn-sm mb-1">';
										if($cRange->empty()) {
											$h .= s("Associer un premier catalogue à la boutique");
										} else {
											$h .= s("Associer un autre catalogue");
										}
									$h .= '</a>';

								} else {

									if($cRange->empty()){
										$h .= '<div class="util-empty">'.s("Aucun catalogue associé à la boutique").'</div>';
									}

								}

							$h .= '</td>';
						$h .= '</tr>';

					$h .= '</tbody>';

				}


			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	protected function getRange(Range $eRange, \Collection $cDepartment): string {

		$eCatalog = $eRange['catalog'];

		$h = '<td>';
			$h .= encode($eCatalog['name']);
			$h .= ' <small class="color-muted">/ '.p("{value} produit", "{value} produits", $eRange['catalog']['products']).'</small>';
		$h .= '</td>';
		$h .= '<td>';

			if($cDepartment->notEmpty()) {

				$department = $eRange['department']->empty() ? s("Pas de rayonnage") : encode($cDepartment[$eRange['department']['id']]['name']);

				if($eRange->canWrite()) {
					if($eRange['department']->notEmpty()) {
						$h .= DepartmentUi::getVignette($cDepartment[$eRange['department']['id']], '1.75rem').'  ';
					}

					$h .= '<a data-dropdown="bottom-start" class="shop-share-range-department dropdown-toggle">';
						$h .= $department;
					$h .= '</a>';
					$h .= '<div class="dropdown-list bg-secondary">';
						foreach($cDepartment as $eDepartment) {
							$h .= '<a data-ajax="/shop/range:doUpdateDepartment" post-id="'.$eRange['id'].'" post-department="'.$eDepartment['id'].'" class="dropdown-item '.(($eRange['department']->notEmpty() and $eDepartment['id'] === $eRange['department']['id']) ? 'selected' : '').'">'.DepartmentUi::getVignette($eDepartment, '2rem').'  '.encode($eDepartment['name']).'</a>';
						}
						$h .= '<a data-ajax="/shop/range:doUpdateDepartment" post-id="'.$eRange['id'].'" post-department="" class="dropdown-item '.($eRange['department']->empty() ? 'selected' : '').'"><i>'.s("Pas de rayonnage").'</i></a>';
					$h .= '</div>';

				} else {
					$h .= $department;
				}

			}
		$h .= '</td>';
		$h .= '<td class="td-min-content">';
			$h .= new RangeUi()->toggle($eRange);
		$h .= '</td>';
		$h .= '<td class="td-min-content">';
			$h .= '<a href="/shop/catalog:show?id='.$eCatalog['id'].'" class="btn btn-outline-secondary" title="'.s("Consulter le contenu du catalogue").'">'.\Asset::icon('search').'</a> ';
			if($eRange->canWrite()) {
				$h .= '<a href="/shop/range:dissociate?id='.$eRange['id'].'" class="btn btn-outline-secondary" title="'.s("Dissocier le catalogue de la boutique").'">'.\Asset::icon('trash-fill').'</a>';
			} else {
				$h .= '<a class="btn btn-outline-secondary disabled">'.\Asset::icon('trash-fill').'</a>';
			}
		$h .= '</td>';

		return $h;

	}

	public function update(Share $eShare): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/share:doUpdate');

		$h .= $form->hidden('id', $eShare['id']);

		if($eShare->isSelf()) {
			$h .= ShopUi::getPaymentMethodInfo();
			$h .= $form->dynamicGroup($eShare, 'paymentMethod');
		} else {
			$h .= $form->dynamicGroup($eShare, 'label');
		}

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-share-update',
			title: $eShare->isSelf() ? s("Paramétrer la boutique") : s("Paramétrer le producteur"),
			body: $h
		);
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Share::model()->describer($property, [
			'label' => s("Activité"),
			'paymentMethod' => s("Moyen de paiement"),
		]);

		switch($property) {

			case 'label' ;
				$d->placeholder = s("Ex. : Maraîcher, Arboricultrice...");
				$d->labelAfter = \util\FormUi::info(s("Si elle est défini, l'activité de ce producteur sera indiquée aux clients sur la boutique"));
				break;

			case 'paymentMethod' ;
				$d->values = fn(Share $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->placeholder = s("Non défini");
				break;

		}

		return $d;

	}
}