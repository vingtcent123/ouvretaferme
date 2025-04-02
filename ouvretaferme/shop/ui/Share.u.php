<?php
namespace shop;

class ShareUi {

	public function getList(\farm\Farm $eFarm, Shop $eShop, \Collection $cShare): string {

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

			$h .= $this->getFarms($eFarm, $eShop, $cShare);

		}

		return $h;

	}


	public function getFarms(\farm\Farm $eFarm, Shop $eShop, \Collection $cShare): string {

		$h = '<div class="util-overflow-lg">';

			$h .= '<table class="shop-share-list tbody-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th colspan="2">'.s("Producteur").'</th>';
						$h .= '<th>'.s("Activité").'</th>';
						$h .= '<th class="highlight">'.s("Catalogues associés").'</th>';
						if($eShop->canWrite()) {
							$h .= '<th>'.s("Position").'</th>';
							$h .= '<th></th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';


				foreach($cShare as $eShare) {

					$cRange = $eShop['cRange'][$eShare['farm']['id']] ?? new \Collection();
					$selected = ($eShare['farm']['id'] === $eFarm['id']);

					$rows = $cRange->count() + (int)$selected;

					$h .= '<tbody '.($selected ? 'class="selected"' : '').'>';
						$h .= '<tr>';
							$h .= '<td class="td-min-content" rowspan="'.$rows.'">';
								$h .= '<b>'.$eShare['position'].'.</b>';
							$h .= '</td>';

							$h .= '<td class="td-min-content" rowspan="'.$rows.'">';
								$h .= \farm\FarmUi::getVignette($eShare['farm'], '2rem');
							$h .= '</td>';

							$h .= '<td class="td-min-content" rowspan="'.$rows.'">';
								$h .= encode($eShare['farm']['name']);
							$h .= '</td>';

							$h .= '<td rowspan="'.$rows.'">';
								$h .= ($eShare['label'] === NULL) ? '/' : encode($eShare['label']);
							$h .= '</td>';


							if($cRange->notEmpty()) {
								$h .= $this->getRange($cRange->first());
							} else {
								$h .= '<td class="highlight">';
									if($selected) {
										$h .= '<a href="/shop/range:create?farm='.$eFarm['id'].'&shop='.$eShop['id'].'" class="btn btn-primary">'.s("Associer un catalogue").'</a>';
									} else {
										$h .= '-';
									}
								$h .= '</td>';
							}

							if($eShop->canWrite()) {

								$h .= '<td class="td-min-content" rowspan="'.$rows.'">';

									if($eShare['position'] > 1) {
										$h .= '<a data-ajax="/shop/share:doIncrementPosition" post-id='.$eShare['id'].'" post-increment="-1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-up').'</a> ';
									} else {
										$h .= '<a class="btn btn-sm disabled">'.\Asset::icon('arrow-up').'</a> ';
									}

									if($eShare['position'] !== $cShare->count()) {
										$h .= '<a data-ajax="/shop/share:doIncrementPosition" post-id='.$eShare['id'].'" post-increment="1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-down').'</a> ';
									} else {
										$h .= '<a class="btn btn-sm disabled">'.\Asset::icon('arrow-down').'</a> ';
									}
								$h .= '</td>';

								$h .= '<td class="text-end" style="white-space: nowrap" rowspan="'.$rows.'">';

									$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<a href="/shop/share:update?id='.$eShare['id'].'" class="dropdown-item">'.s("Configurer le producteur").'</a>';
										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<a data-ajax="/shop/:doDeleteSharedFarm" post-id="'.$eShop['id'].'" post-farm="'.$eShare['farm']['id'].'" data-confirm="'.s("Le producteur n'aura plus accès à la boutique et les clients ne pourront plus commander ses produits. Voulez-vous continuer ?").'" class="dropdown-item">'.s("Retirer le producteur de la boutique").'</a>';
									$h .= '</div>';

								$h .= '</td>';

							}

						$h .= '</tr>';

							foreach($cRange->slice(1) as $eRange) {
								$h .= '<tr>';
									$h .= $this->getRange($eRange);
								$h .= '</tr>';
							}

							if($selected) {

								$h .= '<tr>';
									$h .= '<td class="highlight">';
										$h .= '<a href="/shop/range:create?farm='.$eFarm['id'].'&shop='.$eShop['id'].'" class="btn btn-primary btn-sm">'.s("Associer un autre catalogue").'</a>';
									$h .= '</td>';
								$h .= '</tr>';

							}



					$h .= '</tbody>';

				}


			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	protected function getRange(Range $eRange): string {

		$eCatalog = $eRange['catalog'];

		$h = '<td class="highlight">';
			$h .= '<div class="shop-share-range">';
				$h .= '<span>'.encode($eCatalog['name']).'</span>';
				$h .= new RangeUi()->toggle($eRange);
			$h .= '</div>';
		$h .= '</td>';

		return $h;

	}

	public function update(Share $eShare): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/share:doUpdate');

		$h .= $form->hidden('id', $eShare['id']);
		$h .= $form->dynamicGroup($eShare, 'label');

		$h .= $form->group(
			content: $form->submit(s("Modifier"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-share-update',
			title: s("Configurer le producteur"),
			body: $h
		);
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Share::model()->describer($property, [
			'label' => s("Activité"),
		]);

		switch($property) {

			case 'label' ;
				$d->placeholder = s("Exemple : Maraîcher, Arboricultrice...");
				$d->labelAfter = \util\FormUi::info(s("Si elle est défini, l'activité de ce producteur sera indiquée aux clients sur la boutique"));
				break;

		}

		return $d;

	}
}