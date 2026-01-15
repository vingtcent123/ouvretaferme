<?php
namespace shop;

class PointUi {

	public function __construct() {

		\Asset::css('shop', 'point.css');

	}

	public function createFirst(Shop $eShop): string {

		$h = '<div class="util-block-help">';
			$h .= '<h4>'.s("Bienvenue sur la nouvelle boutique de votre ferme !").'</h4>';
			$h .= '<p>'.s("Vous avez créé avec succès la boutique qui vous permettra de vendre votre production en ligne.").'<br/>'.s("Avant de créer votre première vente, choisissez la façon dont vous souhaitez livrer vos clients, à domicile ou en point de retrait.").'</p>';
			$h .= '<p class="mb-2"><a href="'.\farm\FarmUi::urlShopPoint($eShop['farm']).'" class="btn btn-secondary">'.s("Configurer les modes de livraison").'</a></p>';
			$h .= '<p>'.s("Vous pouvez également <link>désactiver le choix du mode de livraison</link> pour vos clients lorsqu'ils commandent sur la boutique, ce sera à vous de les informer de la façon dont ils peuvent retirer leurs commandes.", ['link' => '<a data-ajax="/shop/:doUpdatePoint" post-id="'.$eShop['id'].'" post-has-point="0" data-confirm="'.s("Souhaitez-vous réellement réactiver le choix du mode de livraison sur votre boutique ?").'">']).'</p>';
		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cc, array $pointsUsed = []): string {

		$h = '';

		$h .= '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<h3>'.s("Livraison en point de retrait").'</h3>';
				$h .= '<div class="util-block stick-xs">';

					if($cc->offsetExists(Point::PLACE)) {
						$h .= $this->getPoints('write', new Shop(), $cc[Point::PLACE], pointsUsed: $pointsUsed);
					} else {

						$h .= '<div class="util-empty">';
							if($eFarm->canManage()) {
								$h .= s("La livraison en point de retrait collectif n'est pas activée sur votre ferme. Pour l'activer, ajoutez un premier point de retrait !");
							} else {
								$h .= s("La livraison en point de retrait collectif n'est pas activée sur la ferme.");
							}
						$h .= '</div>';

					}

					if($eFarm->canManage()) {
						$h .= '<a href="/shop/point:create?farm='.$eFarm['id'].'&type='.Point::PLACE.'" class="btn btn-outline-primary">'.\Asset::icon('house-fill').' '.s("Ajouter un point de retrait").'</a>';
					}

				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h3>'.s("Livraison à domicile").'</h3>';
				$h .= '<div class="util-block stick-xs">';

					if($cc->offsetExists(Point::HOME)) {
						$h .= $this->getPoints('write', new Shop(), $cc[Point::HOME], pointsUsed: $pointsUsed);
					} else {

						$h .= '<div class="util-empty">';
							if($eFarm->canManage()) {
								$h .= s("La livraison à domicile n'est pas activée sur votre ferme. Pour l'activer, créez une première tournée avec les zones géographiques dans lesquelles vous acceptez de livrer vos clients !");
							} else {
								$h .= s("La livraison à domicile n'est pas activée sur la ferme.");
							}
						$h .= '</div>';

					}

					if($eFarm->canManage()) {
						$h .= '<div style="margin-bottom: 0.5rem"><a href="/shop/point:create?farm='.$eFarm['id'].'&type='.Point::HOME.'&mode='.Point::TOUR.'" class="btn btn-outline-primary">'.\Asset::icon('geo-alt-fill').' '.s("Ajouter une tournée de livraison").'</a></div>';
						$h .= '<div><a href="/shop/point:create?farm='.$eFarm['id'].'&type='.Point::HOME.'&mode='.Point::SHIPPING.'" class="btn btn-outline-primary">'.\Asset::icon('box-seam').' '.s("Ajouter une expédition par transporteur").'</a></div>';
					}

				$h .= '</div>';
			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getField(Shop $eShop, \Collection $cc, Point $ePointSelected = new Point()): string {

		$hasHome = $cc->offsetExists(Point::HOME);
		$hasPlace = $cc->offsetExists(Point::PLACE);

		if($hasHome and $hasPlace) {
			return $this->getFieldBoth($eShop, $cc, $ePointSelected);
		} else if($hasHome) {
			return $this->getFieldHome($eShop, $cc[Point::HOME], $ePointSelected);
		} else if($hasPlace) {
			return $this->getFieldPlace($eShop, $cc[Point::PLACE], $ePointSelected);
		} else {
			throw new \Exception('No point');
		}

	}

	public function getFieldBoth(Shop $eShop, \Collection $cc, Point $ePointSelected) {

		$h = '<h2>'.s("Mode de livraison").'</h2>';

		$h .= '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<h3>'.s("Livraison en point de retrait").'</h3>';
				$h .= '<div class="util-block point-place-wrapper">';
					$h .= $this->getPoints('update', $eShop, $cc[Point::PLACE], ePointSelected: $ePointSelected);
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';

				$cPointTour = $cc[Point::HOME]->find(fn($e) => $e['mode'] === Point::TOUR);
				$cPointShipping = $cc[Point::HOME]->find(fn($e) => $e['mode'] === Point::SHIPPING);

				if($cPointTour->notEmpty()) {

					$h .= '<h3>'.s("Livraison à domicile").'</h3>';
					$h .= '<div class="util-block point-home-wrapper">';

						$h .= '<p>'.s("Vous pouvez choisir la livraison à domicile si vous habitez :").'</p>';

						$h .= $this->getPoints('update', $eShop, $cPointTour, ePointSelected: $ePointSelected);

						$h .= '<p>'.s("<b>Attention !</b> Si votre adresse ne correspond pas à l'une de ces zones, votre commande pourra être annulée.").'</p>';

					$h .= '</div>';

				}

				if($cPointShipping->notEmpty()) {

					$h .= '<h3>'.s("Livraison par transporteur").'</h3>';
					$h .= '<div class="util-block point-home-wrapper">';

						$h .= $this->getPoints('update', $eShop, $cPointShipping, ePointSelected: $ePointSelected);

					$h .= '</div>';

				}

			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getFieldHome(Shop $eShop, \Collection $c, Point $ePointSelected) {

		$cPointTour = $c->find(fn($e) => $e['mode'] === Point::TOUR);
		$cPointShipping = $c->find(fn($e) => $e['mode'] === Point::SHIPPING);

		$h = '<h2>'.s("Mode de livraison").'</h2>';

			if($cPointTour->notEmpty()) {

				$h .= '<div class="util-block point-home-wrapper">';
					if($cPointShipping->notEmpty()) {
						$h .= '<h3>'.s("Livraison à domicile").'</h3>';
						$h .= '<p class="mb-1">'.s("Vous êtes éligible à la livraison à domicile si vous habitez dans l'une des zones suivantes :").'</p>';
					} else {
						$h .= '<h3>'.s("Livraison uniquement à domicile").'</h3>';
						$h .= '<p class="mb-1">'.s("Vous êtes éligible à la livraison à domicile si vous habitez dans l'une des zones suivantes :").'</p>';
					}

					$h .= $this->getPoints('update', $eShop, $cPointTour, ePointSelected: $ePointSelected);
					$h .= '<p class="util-info">'.s("<b>Attention !</b><br/>Si votre adresse ne correspond pas à l'une de ces zones, votre commande sera annulée.").'</p>';
				$h .= '</div>';
			}

			if($cPointShipping->notEmpty()) {

				$h .= '<div class="util-block point-home-wrapper">';
				if($cPointTour->notEmpty()) {
					$h .= '<h3>'.s("Livraison par transporteur").'</h3>';
				}
				$h .= $this->getPoints('update', $eShop, $cPointShipping, ePointSelected: $ePointSelected);
				$h .= '</div>';
			}


		$h .= '<br/>';

		return $h;

	}

	public function getFieldPlace(Shop $eShop, \Collection $c, Point $ePointSelected) {

		if($c->count() === 1) {
			$ePointSelected = $c->first();
		}

		if($c->count() > 1) {
			$h = '<h2>'.s("Choisissez un point de livraison").'</h2>';
		} else {
			$h = '<h2>'.s("Point de livraison").'</h2>';
		}

		$h .= '<div class="util-block point-place-wrapper">';
			$h .= $this->getPoints('update', $eShop, $c, ePointSelected: $ePointSelected);
		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getByDate(Shop $eShop, Date $eDate, \Collection $cc): string {

		$h = '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<h2>'.s("Livraison en point de retrait").'</h2>';
				$h .= '<div class="util-block stick-xs">';

					if($eShop['ccPoint']->offsetExists(Point::PLACE)) {
						$h .= $this->getPoints('date', $eShop, $eShop['ccPoint'][Point::PLACE], cPointSelected: $cc[Point::PLACE] ?? new \Collection(), eDate: $eDate);
					} else {
						$h .= '<div class="util-empty">';
							$h .= s("La livraison en point de retrait collectif n'est pas activée pour cette vente !");
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h2>'.s("Livraison à domicile").'</h2>';
				$h .= '<div class="util-block stick-xs">';

					if($eShop['ccPoint']->offsetExists(Point::HOME)) {
						$h .= $this->getPoints('date', $eShop, $eShop['ccPoint'][Point::HOME], cPointSelected: $cc[Point::HOME] ?? new \Collection(), eDate: $eDate);
					} else {
						$h .= '<div class="util-empty">';
							$h .= s("La livraison à domicile n'est pas activée dans cette boutique !");
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getPoints(string $mode, Shop $eShop, \Collection $c, Point $ePointSelected = new Point(), \Collection $cPointSelected = new \Collection(), Date $eDate = new Date(), array $pointsUsed = []): string {

		if($ePointSelected->notEmpty()) {
			$cPointSelected[] = $ePointSelected;
		}

		$h = '<div class="point-list point-list-'.$mode.'">';

			foreach($c as $e) {
				$h .= $this->getPoint($mode, $eShop, $e, $cPointSelected, $eDate, $pointsUsed);
			}

		$h .= '</div>';

		return $h;

	}

	public function getPoint(string $mode, Shop $eShop, Point $e, \Collection $cPointSelected = new \Collection(), Date $eDate = new Date(), array $pointsUsed = []): string {

		$tag = ($mode === 'update') ? 'label' : 'div';

		$selected = $e->empty() ?
			FALSE :
			$cPointSelected->match(fn($ePoint) => $ePoint['id'] === $e['id']);

		if($mode === 'update') {

			$icon = '<div class="point-update">';
				$icon .= '<input type="radio" name="shopPoint" value="'.$e['id'].'" '.($selected ? 'checked' : '').'/>';
				$icon .= '<div class="point-update-content">';
					$icon .= \Asset::icon('check-lg');
				$icon .= '</div>';
			$icon .= '</div>';

		} else {

			$icon = match($e['type']) {
				Point::PLACE => \Asset::icon('house-fill', ['class' => 'point-icon']),
				Point::HOME => match($e['mode']) {
					Point::SHIPPING => \Asset::icon('box-seam', ['class' => 'point-icon']),
					Point::TOUR => \Asset::icon('geo-alt-fill', ['class' => 'point-icon'])
				},
			};

		}

		$orderMin = $e['orderMin'] ?? ($eShop->empty() ? NULL : $eShop['orderMin']);
		$shipping = $e['shipping'] ?? ($eShop->empty() ? NULL : $eShop['shipping']);
		$shippingUntil = $e['shippingUntil'] ?? ($eShop->empty() ? NULL : $eShop['shippingUntil']);

		$h = '<'.$tag.' class="point-element" data-order-min="'.$orderMin.'" data-shipping="'.$shipping.'" data-shipping-until="'.$shippingUntil.'">';

			$h .= '<div class="point-name">';
				$h .= $icon;
				$h .= '<div>';
					$h .= '<h4>'.encode($e['name']).'</h4>';
					if($e['description'] !== NULL) {
						$h .= '<span style="margin-top: 0.25rem">'.encode($e['description']).'</span>';
					}
					if($e['type'] === Point::HOME and $e['mode'] === Point::TOUR) {
						$h .= '<div style="margin-top: 0.25rem">'.nl2br(encode($e['zone'])).'</div>';
					}

					if($e['type'] === Point::PLACE) {
						$h .= '<div class="point-address">';
							if($e['address']) {
								$h .= nl2br(encode($e['address'])).'<br/>';
							}
							$h .= encode($e['place']);
						$h .= '</div>';
					}
				$h .= '</div>';

				if($mode === 'write' and $e->canWrite()) {

					$h .= '<div>';
						if(array_key_exists($e['id'], $pointsUsed)) {
							$h .= '<div class="point-name-used" title="'.s("Sur le dernier mois").'">'.\Asset::icon('check', ['class' => 'hide-md-down']).' '.p("Activé récemment<br/>sur {value} boutique", "Activé récemment<br/>sur {value} boutiques", count($pointsUsed[$e['id']])).'</div>';
						}
						$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle btn btn-outline-primary">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.encode($e['name']).'</div>';
							$h .= '<a href="/shop/point:update?id='.$e['id'].'" class="dropdown-item">'.s("Modifier").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a data-ajax="/shop/point:doDelete" post-id="'.$e['id'].'" data-confirm="'.s("Souhaitez-vous réellement supprimer définitivement ce mode de livraison pour les ventes à venir ?").'" class="dropdown-item">'.s("Supprimer").'</a>';
						$h .= '</div>';

					$h .= '</div>';

				} else if($mode === 'date') {

					$h .= '<div>';
						$h .= new DateUi()->togglePoint($eDate, $e, $selected);
					$h .= '</div>';

				} else {
					$h .= '<div></div>';
				}

			$h .= '</div>';

			if($mode === 'update' or $mode === 'write') {

				$badges = '';

				if($orderMin > 0) {
					$badges .= '<span class="point-order-min util-badge color-text">'.s("Minimum de commande : {value} €", $orderMin).'</span>';
				}

				if($shipping > 0) {
					if($shippingUntil > 0) {
						$badges .= ' <span class="point-shipping util-badge color-text">';
							$badges .= s("Frais de livraison : <charged>{value} €</charged> et <free>offerts au delà de {until} € de commande</free>", ['value' => $shipping, 'until' => $shippingUntil, 'charged' => ($mode === 'write') ? '<span>' : '<span class="point-shipping-charged">', 'free' => ($mode === 'write') ? '<span>' : '<span class="point-shipping-free">']);
					} else {
						$badges .= ' <span class="point-shipping util-badge color-text">';
							$badges .= s("Frais de livraison : {value} €", $shipping);
						$badges .= '</span>';
					}
				}

				if($badges) {
					$h .= '<div class="point-badge">'.$badges.'</div>';
				}

			}

		$h .= '</'.$tag.'>';

		return $h;

	}

	public function create(Point $e): \Panel {

		$e->expects([
			'farm',
			'type'
		]);

		$form = new \util\FormUi();

		$h = '';

		if($e['type'] === Point::HOME) {

			$h .= '<div class="util-block-info">';
				$h .= match($e['mode']) {
					Point::TOUR => '<h4>'.s("Une tournée est une livraison qui vous effectuez vous-même pour livrer vos clients à domicile").'</h4><p>'.s("Indiquez les zones géographiques que vous autorisez pour la livraison à domicile des commandes. Cela peut être une liste de villes, de départements ou toute autre localité pertinente pour votre activité. Lorsqu'ils commandent, vos clients s'engagent à donner une adresse qui se situe dans les zones que vous avez ainsi définies.").'</p>',
					Point::SHIPPING => '<h4>'.s("Une expédition par transporteur est une livraison que vous effectuez par l'intermédiaire d'un prestataire").'</h4><p>'.s("C'est à vous d'informer vos clients des délais de livraison et de choisir un prestataire adapté à votre situation.").'</p>',
				};
			$h .= '</div>';


		}

		$h .= $form->openAjax('/shop/point:doCreate');

			$h .= $form->hidden('farm', $e['farm']['id']);
			$h .= $form->hidden('type', $e['type']);
			$h .= $form->hidden('mode', $e['mode']);

			$h .= match($e['type']) {
				Point::PLACE => $form->dynamicGroups($e, ['name', 'description', 'place', 'address']),
				Point::HOME => match($e['mode']) {
					Point::SHIPPING => $form->dynamicGroups($e, ['name', 'description']),
					Point::TOUR => $form->dynamicGroups($e, ['name', 'zone'])
				}
			};

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-point-create',
			title: match($e['type']) {
				Point::PLACE => s("Ajouter un point de retrait"),
				Point::HOME => match($e['mode']) {
					Point::TOUR => s("Ajouter une tournée de livraison à domicile"),
					Point::SHIPPING => s("Ajouter une expédition par transporteur"),
				},
			},
			body: $h
		);

	}

	public function update(Point $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/point:doUpdate');

			$h .= $form->hidden('id', $e['id']);

			$h .= $form->dynamicGroups($e, match($e['type']) {
				Point::PLACE => ['name', 'description', 'place', 'address'],
				Point::HOME => match($e['mode']) {
					Point::SHIPPING => ['name', 'description'],
					Point::TOUR => ['name', 'zone']
				}
			});

			$h .= '<div class="util-block-gradient">';

				$title = match($e['type']) {
					Point::PLACE => s("Personnalisation du point de retrait"),
					Point::HOME => match($e['mode']) {
						Point::TOUR => s("Personnalisation de la tournée"),
						Point::SHIPPING => s("Personnalisation de l'expédition"),
					}
				};

				$h .= $form->group(content: '<h3>'.$title.'</h3>');

				$h .= $form->dynamicGroups($e, ['paymentOffline', 'paymentTransfer']);
				if($e['stripe']->notEmpty()) {
					$h .= $form->dynamicGroups($e, ['paymentCard']);
				}

				$h .= $form->dynamicGroups($e, ['orderMin', 'shipping', 'shippingUntil']);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-point-update',
			title: match($e['type']) {
				Point::PLACE => s("Modifier un point de retrait"),
				Point::HOME => match($e['mode']) {
					Point::TOUR => s("Modifier une tournée de livraison à domicile"),
					Point::SHIPPING => s("Modifier une expédition par transporteur"),
				},
			},
			body: $h
		);

	}


	public static function p(string $property): \PropertyDescriber {

		$d = Point::model()->describer($property, [
			'zone' => s("Zones de livraison"),
			'name' => s("Nom"),
			'description' => fn(Point $e) => match($e['type']) {
				Point::PLACE => s("Informations sur le point de retrait"),
				Point::HOME => s("Informations sur l'expédition"),
			},
			'place' => s("Ville du point de retrait"),
			'address' => s("Adresse du point de retrait"),
			'orderMin' => s("Montant minimal de commande"),
			'shipping' => s("Frais de livraison par commande"),
			'shippingUntil' => s("Montant minimal de commande au delà duquel les frais de livraison sont offerts"),
			'paymentOffline' => s("Activer le choix du paiement en direct"),
			'paymentTransfer' => s("Activer le choix du paiement par virement bancaire"),
			'paymentCard' => s("Activer le choix du paiement en ligne avec {icon} Stripe", ['icon' => \Asset::icon('stripe')]),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					Point::HOME => s("Livraison à domicile"),
					Point::PLACE => s("Livraison en point de retrait")
				];
				break;

			case 'name' :
				$d->label = fn($e) => match($e['type']) {
					Point::HOME => match($e['mode']) {
						Point::SHIPPING => s("Nom du transporteur"),
						Point::TOUR => s("Nom de la tournée"),
					},
					Point::PLACE => s("Nom du point de retrait")
				};
				$d->placeholder = fn($e) => match($e['type']) {
					Point::HOME => match($e['mode']) {
						Point::SHIPPING => s("Exemple : Expédition par voie postale"),
						Point::TOUR => s("Exemple : Tournée du mardi"),
					},
					Point::PLACE => s("Exemple : Maison des Citoyens, À la ferme, ...")
				};
				break;

			case 'description' :
				$d->placeholder = fn(Point $e) => match($e['type']) {
					Point::HOME => s("Exemple : Livraison sous 72 heures"),
					Point::PLACE => s("Exemple : Retrait des commandes de HH:MM à HH:MM"),
				};
				$d->after = fn(\util\FormUi $form, Point $e) => \util\FormUi::info(match($e['type']) {
					Point::HOME => s("Vous pouvez indiquer ici toute information utile communiquer sur l'expédition à vos clients."),
					Point::PLACE => s("Vous pouvez indiquer ici toute information utile spécifique à ce point de retrait, pour être communiquée à vos clients."),
				});
				break;

			case 'place' :
				$d->placeholder = s("Ex. : Saint-Alban");
				break;

			case 'address' :
				$d->placeholder = s("Ex. : 12 rue sous les Augustins");
				break;

			case 'zone' :
				$d->placeholder = s("Ex. :\n- Ce village\n- Cet autre village\n- Ce départment\n\nNous assurons la livraison en vélo.");
				$d->after = s("Indiquez une zone géographique par ligne. Vous pouvez également ajouter du texte si vous souhaitez apporter des précisions supplémentaires à vos clients.");
				break;

			case 'paymentOffline' :
			case 'paymentTransfer' :
			case 'paymentCard' :
				$d->field = 'radio';
				$d->values = fn(Point $e) => [
					1 => s("oui"),
					0 => s("non"),
				];
				$d->placeholder = s("la valeur choisie pour la boutique");
				break;

			case 'orderMin' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Si ce champ est laissé vide, le montant minimal de commande choisi pour la boutique s'applique."));
				break;

			case 'shipping' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Si ce champ est laissé vide, les frais de livraison choisis pour la boutique s'appliquent."));
				break;

			case 'shippingUntil' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Les frais de livraison ne sont pas facturés si la commande des clients excède ce montant. Si ce champ est laissé vide, le montant minimal choisi pour la boutique s'applique."));
				break;

		}

		return $d;

	}
}
?>
