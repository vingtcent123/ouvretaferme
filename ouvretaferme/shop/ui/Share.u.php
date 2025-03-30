<?php
namespace shop;

class ShareUi {

	public function getList(Shop $eShop, \Collection $cShare): string {

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

			$h .= $this->getFarms($eShop, $cShare);

		}

		return $h;

	}


	public function getFarms(Shop $eShop, \Collection $cShare): string {

		$h = '<div class="dates-item-wrapper stick-sm util-overflow-sm">';

			$h .= '<table class="sale-item-table tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Producteur").'</th>';
						$h .= '<th>'.s("Activité").'</th>';
						if($eShop->canWrite()) {
							$h .= '<th></th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cShare as $eShare) {

						$eFarm = $eShare['farm'];

						$h .= '<tr>';

							$h .= '<td class="td-min-content">';
								$h .= \farm\FarmUi::getVignette($eFarm, '2rem');
							$h .= '</td>';

							$h .= '<td class="td-min-content">';
								$h .= encode($eFarm['name']);
							$h .= '</td>';

							$h .= '<td>';
								$h .= ($eShare['label'] === NULL) ? '/' : encode($eShare['label']);
							$h .= '</td>';

							if($eShop->canWrite()) {

								$h .= '<td class="text-end" style="white-space: nowrap">';

									$h .= '<a class="btn btn-outline-primary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<a href="/shop/share:update?id='.$eShare['id'].'" class="dropdown-item">'.s("Définir l'activité du producteur").'</a>';
										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<a data-ajax="/shop/:doDeleteSharedFarm" post-id="'.$eShop['id'].'" post-farm="'.$eFarm['id'].'" data-confirm="'.s("Le producteur n'aura plus accès à la boutique et les clients ne pourront plus commander ses produits. Voulez-vous continuer ?").'" class="dropdown-item">'.s("Retirer le producteur de la boutique").'</a>';
									$h .= '</div>';

								$h .= '</td>';

							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

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
			title: s("Mettre à jour"),
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