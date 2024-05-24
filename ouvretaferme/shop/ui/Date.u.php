<?php
namespace shop;

class DateUi {

	public function __construct() {

		\Asset::css('shop', 'date.css');

	}

	public static function name(Date $e): string {
		return s("Vente du {value}", lcfirst(\util\DateUi::getDayName(date('N', strtotime($e['deliveryDate'])))).' '.\util\DateUi::textual($e['deliveryDate']));
	}

	public function toggle(Date $eDate) {

		return \util\TextUi::switch([
			'id' => 'date-switch-'.$eDate['id'],
			'data-ajax' => $eDate->canWrite() ? '/shop/date:doUpdateStatus' : NULL,
			'post-id' => $eDate['id'],
			'post-status' => ($eDate['status'] === Date::ACTIVE) ? Date::CLOSED : Date::ACTIVE
		], $eDate['status'] === Date::ACTIVE, s("En ligne"), ("Hors ligne"));

	}

	public function togglePoint(Date $eDate, Point $ePoint, bool $selected) {

		return \util\TextUi::switch([
			'id' => 'point-switch-'.$ePoint['id'],
			'data-ajax' => '/shop/date:doUpdatePoint',
			'post-id' => $eDate['id'],
			'post-point' => $ePoint['id'],
			'post-status' => $selected ? 0 : 1
		], $selected);

	}

	private function calculateDates(Date $eDate, Date $eDateBase): void {

		$frequency = $eDate['shop']['frequency'];

		// Calculer les dates en décalant si pertinent
		if($frequency === Shop::WEEKLY) {

			$eDate['orderStartAt'] = date('Y-m-d H:i:s', strtotime($eDateBase['orderStartAt'].' + 7 days'));
			$eDate['orderEndAt'] = date('Y-m-d H:i:s', strtotime($eDateBase['orderEndAt'].' + 7 days'));
			$eDate['deliveryDate'] = date('Y-m-d', strtotime($eDateBase['deliveryDate'].' + 7 days'));

		} else if($frequency === Shop::BIMONTHLY) {

			$eDate['orderStartAt'] = date('Y-m-d H:i:s', strtotime($eDateBase['orderStartAt'].' + 14 days'));
			$eDate['orderEndAt'] = date('Y-m-d H:i:s', strtotime($eDateBase['orderEndAt'].' + 14 days'));
			$eDate['deliveryDate'] = date('Y-m-d', strtotime($eDateBase['deliveryDate'].' + 14 days'));

		} else if($frequency === Shop::MONTHLY) {

			// Calcul du premier jour du mois suivant en partant du dernier jour du mois de référence.
			$lastDayOfReferenceMonth = date('Y-m-t', strtotime($eDateBase['deliveryDate']));
			$firstDayOfNextMonth = date('Y-m-01', strtotime($lastDayOfReferenceMonth.' + 1 day'));

			// Informations sur le mois suivant.
			$dayName = date('l', strtotime($eDateBase['deliveryDate']));
			$nextMonth = substr($firstDayOfNextMonth, 5, 2);
			$nextMonthName = date('F', strtotime($firstDayOfNextMonth));
			$nextMonthYear = date('Y', strtotime($firstDayOfNextMonth));

			// Combientième [lundi|mardi...] du mois.
			$dayTh = (int)ceil((int)date('d', strtotime($eDateBase['deliveryDate'])) / 7);

			// Si $dayTh $dayName du mois $nextMonth existe, le prendre, sinon prendre le dernier $dayName du $nextMonth.
			$referenceDay = date('Y-m-d H:i:s', mktime(0, 0, 0, $nextMonth, 7 * $dayTh));
			$referenceDate = new \DateTime($referenceDay);
			if($referenceDate->format('l') !== $dayName) { // On prend le même jour le plus proche
				$referenceDate = $referenceDate->modify('next '.$dayName);
			}

			// Si ce jour n'est pas dans le bon mois, récupérer le dernier du $nextMonth.
			if((int)$referenceDate->format('m') !== $nextMonth) {
				$referenceDay = date('Y-m-d H:i:s', strtotime("last ".$dayName." of ".$nextMonthName." ".$nextMonthYear));
				$referenceDate = new \DateTime($referenceDay);
			}

			// Reconstruction du jour de livraison recherché.
			$eDate['deliveryDate'] = $referenceDate->format('Y-m-d');

			// L'intervalle entre la précédente date de livraison et toutes les autres dates est appliqué sur la nouvelle date de livraison.
			foreach(['orderStartAt', 'orderEndAt'] as $dateField) {
				$newDeliveryDate = new \DateTime($eDate['deliveryDate']);
				if(\util\DateLib::compare($eDateBase['deliveryDate'], $eDateBase[$dateField]) < 0) {
					$signe = '+';
				} else {
					$signe = '-';
				}
				$interval = abs((int)(\util\DateLib::interval($eDateBase[$dateField], $eDateBase['deliveryDate']) / 60 / 60 / 24));
				$eDate[$dateField] = $newDeliveryDate
					->modify($signe.' '.$interval.' days')
					->format('Y-m-d').' '.substr($eDateBase[$dateField], 11, 2).':'.substr($eDateBase[$dateField], 14, 2).':00';
			}
		}

	}

	public function getDeliveryPeriods(Shop $eShop, \Collection $cDate, Date $eDateSelected): string {

		$h = '';

		if($cDate->notEmpty()) {

			$h .= '<h3>'.\Asset::icon('calendar3').'&nbsp;&nbsp;'.s("Prochaines ventes").'</h3>';
			$h .= '<div class="shop-header-date-days">';
				foreach($cDate as $eDate) {

					$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).'" class="'.($eDate['id'] === $eDateSelected['id'] ? 'selected' : '').'">';
						$h .= '<div class="shop-header-date-day-name">'.\util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).'</div>';
						$h .= '<div class="shop-header-date-day-value">'.\util\DateUi::textual($eDate['deliveryDate'], \util\DateUi::DAY_MONTH).'</div>';
					$h .= '</a>';

				}
			$h .= '</div>';

		} else {
			$h .= $this->getEmptyPeriod();
		}

		return $h;

	}

	public function getDeliveryPeriod(Date $eDate, string $for = 'next'): string {

		$h = '';

		if($eDate->notEmpty()) {

			if($for === 'next') {

				if($eDate['deliveryDate'] >= date('Y-m-d')) {
					$title = s("Prochaine vente");
				} else {
					$title = s("Dernière vente");
				}

			} else {
				$title = s("Date de retrait");
			}

			$h .= '<h4>'.\Asset::icon('calendar3').'&nbsp;&nbsp;'.$title.'</h4>';
			$h .= '<div class="shop-header-date-day">';
				$h .= \util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).' '.\util\DateUi::textual($eDate['deliveryDate']).'<br/>';
			$h .= '</div>';

		} else {
			$h .= $this->getEmptyPeriod();
		}

		return $h;

	}

	protected function getEmptyPeriod(): string {

		$h = '<h4>'.\Asset::icon('calendar3').'&nbsp;&nbsp;'.s("Prochaine vente").'</h4>';

		$h .= '<div class="shop-header-date-content">';
			$h .= s("Date à venir bientôt !");
		$h .= '</div>';

		return $h;

	}

	public function getOrderPeriod(Date $eDate): string {

		$h = '';

		if($eDate->canOrder()) {
			$h .= s("Les prises de commande en ligne sont possibles jusqu'au {date} !", ['date' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderEndAt'])))).' '.\util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
		} else if($eDate->canOrderSoon()) {
			$h .= s("Les prises de commande en ligne seront possibles du {from} jusqu'au {to} !", ['from' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderStartAt'])))).' '.\util\DateUi::textual($eDate['orderStartAt'], \util\DateUi::DAY_MONTH | \util\DateUi::TIME_HOUR_MINUTE), 'to' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderEndAt'])))).' '.\util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DAY_MONTH | \util\DateUi::TIME_HOUR_MINUTE)]);
		}

		if($eDate->isOrderSoonExpired()) {
			$h .= '<br/>'.\Asset::icon('exclamation-circle').' '.s("Attention, il ne vous reste plus que quelques minutes pour finaliser votre commande, ne tardez pas.");
		}

		return $h;

	}

	public function getOrderLimits(Shop $eShop): string {

		$eShop->expects(['ccPoint']);

		$points = $eShop['ccPoint']->reduce(fn($c, $n) => $n + $c->count(), 0);

		$h = '';

		$orderMin = $eShop['ccPoint']->getColumn('orderMin');
		if(count($orderMin) !== $points) { // Pas de valeur pour tous les points, on ajoute la valeur par défaut
			$orderMin[] = $eShop['orderMin'];
		}

		$orderMin = array_unique(array_merge([$eShop['orderMin']], $eShop['ccPoint']->getColumn('orderMin')));

		if(count($orderMin) === 1) {

			$value = $orderMin[0];

			if($value > 0) {
				$h .= ' '.s("Un minimum de commande de {value} € est demandé.", $value);
			}

		} else {

			$min = min($orderMin);
			$max = max($orderMin);

			if($max > 0) {

				if($min > 0) {
					$h .= ' '.s("En fonction du mode de retrait, un minimum de commande compris entre {min} € et {max} € sera demandé.", ['min' => $min, 'max' => $max]);
				} else {
					$h .= ' '.s("En fonction du mode de retrait, un minimum de commande pourra être demandé.");
				}

			}

		}

		return $h;

	}

	public function create(Date $e, \Collection $cProduct, Date $eDateBase = new Date()): \Panel {

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		// $eDateBase est la date de référence sur laquelle baser la nouvelle date à créer.
		if($eDateBase->notEmpty()) {

			$this->calculateDates($e, $eDateBase);

			// Setter les produits sélectionnés et leur prix s'ils sont toujours disponibles
			foreach($eDateBase['cProduct'] as $eProduct) {

				if($cProduct->offsetExists($eProduct['product']['id'])) {
					$cProduct[$eProduct['product']['id']]['checked'] = TRUE;
					$cProduct[$eProduct['product']['id']]['privatePrice'] = $eProduct['price'];
				}

			}

			$e['points'] = $eDateBase['points'];

		} else {
			$e['points'] = [];
		}

		$e['cProduct'] = $cProduct;

		$h = '';

		$h .= $form->openAjax('/shop/date:doCreate', ['id' => 'shop-date-create']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('shop', $e['shop']);
			$h .= $form->hidden('farm', $e['farm']);
			$h .= $form->hidden('copied', $eDateBase->notEmpty());

			$h .= $form->dynamicGroups($e, ['status', 'points*']);
			$h .= $this->getOrderField('create', $form, $e);
			$h .= $form->dynamicGroup($e, 'deliveryDate*');

			$h .= $form->group(
				p("Produit proposé à la vente", "Produits proposés à la vente", $cProduct->count()).$form->asterisk(),
				$form->dynamicField($e, 'products'),
				['wrapper' => 'products']
			);

			$h .= '<br/>';

			$h .= $form->group(
				content: '<p class="util-danger">'.s("Veuillez corriger les erreurs en rouge pour continuer.").'</p>'.
				$form->submit(s("Créer la date"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Préparer une nouvelle de vente"),
			body: $h
		);
	}

	protected function getOrderField(string $action, \util\FormUi $form, Date $eDate): string {

		$h = '<div class="input-group">';
			$h .= $form->addon(s("Du"));
			$h .= $form->dynamicField($eDate, 'orderStartAt');
			$h .= $form->addon(s("au"));
			$h .= $form->dynamicField($eDate, 'orderEndAt');
		$h .= '</div>';

		$asterisk = ($action === 'create') ? $form->asterisk() : '';

		return $form->group(s("Période de prise des commandes en ligne").$asterisk, $h);
	}

	public function update(Date $eDate): \Panel {

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		$h = '';

		$h .= $form->openAjax('/shop/date:doUpdate', ['id' => 'shop-date-update']);

		$h .= $form->hidden('id', $eDate['id']);

		if($eDate->isExpired() === FALSE) {
			$h .= $form->dynamicGroup($eDate, 'points');
		}

		$h .= $this->getOrderField('update', $form, $eDate);
		$h .= $form->dynamicGroup($eDate, 'deliveryDate');

		$h .= $form->group(
			content: $form->submit(s("Modifier"))
		);

		$h .= $form->close();

		return new \Panel(
			title: s("Paramétrer une date"),
			body: $h
		);
	}

	public static function getProducts(\util\FormUi $form, Date $eDate): string {

		\Asset::css('shop', 'shop.css');
		\Asset::css('shop', 'manage.css');
		\Asset::js('shop', 'manage.js');

		$eDate->expects(['farm', 'cProduct']);

		$eFarm = $eDate['farm'];
		$cProduct = $eDate['cProduct'];

		if($cProduct->empty()) {
			$h = '<div class="util-block-requirement">';
				$h .= '<p>'.s("Avant d'enregistrer une nouvelle date, vous devez renseigner les produits que vous souhaitez proposer à la vente dans votre ferme !").'</p>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
			$h .= '</div>';
			return $h;
		}

		$h = '<div class="stick-xs">';

			$h .= '<div class="date-products-item util-grid-header">';

				$h .= '<div class="shop-select">';
					if($cProduct->count() > 2) {
						$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this, \'[name^="products[]"]\', node => DateManage.selectProduct(node))').'"  title="'.s("Tout cocher / Tout décocher").'"/>';
					}
				$h .= '</div>';
				$h .= '<div>';
					$h .= s("Produit");
				$h .= '</div>';
				$h .= '<div class="date-products-item-unit text-end">'.s("Multiple de vente").'</div>';
				$h .= '<div class="date-products-item-price text-end">'.s("Prix").'</div>';
				$h .= '<div>'.s("Stock").'</div>';

			$h .= '</div>';

			$h .= '<div class="date-products-body">';
				foreach($cProduct as $eProduct) {

					$step = \selling\ProductUi::getStep($eProduct);
					$checked = $eProduct['checked'] ?? FALSE;

					$attributes = [
						'id' => 'checkbox-'.$eProduct['id'],
						'onclick' => 'DateManage.selectProduct(this)'
					];

					if($eProduct['checked'] ?? FALSE) {
						$attributes['checked'] = $checked;
					}

					$eShopProduct = new Product([
						'product' => $eProduct,
						'price' => $eProduct['privatePrice'],
						'stock' => NULL,
					]);

					$h .= '<div class="date-products-item '.($checked ? 'selected' : '').'">';

						$h .= '<label class="shop-select">';
							$h .= $form->inputCheckbox('products['.$eProduct['id'].']', $eProduct['id'], $attributes);
						$h .= '</label>';
						$h .= '<label class="date-products-item-product" for="'.$attributes['id'].'">';
							$h .= \selling\ProductUi::getVignette($eProduct, '2rem');
							$h .= '&nbsp;&nbsp;';
							$h .= \selling\ProductUi::link($eProduct, TRUE);
						$h .= '</label>';
						$h .= '<label class="date-products-item-unit text-end" for="'.$attributes['id'].'">';
							$h .= \main\UnitUi::getValue($step, $eProduct['unit']);
						$h .= '</label>';
						$h .= '<div data-wrapper="price['.$eProduct['id'].']" class="date-products-item-price '.($checked ? '' : 'hidden').'">';
							$h .= $form->dynamicField($eShopProduct, 'price['.$eProduct['id'].']', function($d) use ($eProduct) {
							});
						$h .= '</div>';
						$h .= '<div data-wrapper="stock['.$eProduct['id'].']" class="date-products-item-stock '.($checked ? '' : 'hidden').'">';
							$h .= $form->dynamicField($eShopProduct, 'stock', function($d) use ($eProduct) {
								$d->name = 'stock['.$eProduct['id'].']';
							});
						$h .= '</div>';

					$h .= '</div>';

				}
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function getPoints(\util\FormUi $form, Date $eDate): string {

		\Asset::css('shop', 'shop.css');
		\Asset::css('shop', 'manage.css');
		\Asset::js('shop', 'manage.js');

		$eDate->expects(['farm', 'ccPoint']);

		$eShop = $eDate['shop'];
		$ccPoint = $eDate['ccPoint'];

		if($ccPoint->empty()) {
			$h = '<div class="util-block-requirement">';
				$h .= '<p>'.s("Avant d'enregistrer une nouvelle date, vous devez renseigner les modes de livraisons disponibles pour vos clients !").'</p>';
				$h .= '<a href="'.ShopUi::adminUrl($eDate['farm'], $eShop).'&tab=points" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
			$h .= '</div>';
			return $h;
		}

		$h = '<div class="field-radio-group">';

			foreach($ccPoint as $type => $cPoint) {

				$h .= '<div class="date-points-title">';
					$h .= PointUi::p('type')->values[$type];
				$h .= '</div>';

				foreach($cPoint as $ePoint) {

					$checked = in_array($ePoint['id'], $eDate['points']);

					$attributes = [
						'id' => 'checkbox-'.$ePoint['id'],
						'checked' => $checked
					];

					$h .= '<div class="date-points-item '.($checked ? 'selected' : '').'">';

						$h .= '<label class="shop-select">';
							$h .= $form->inputCheckbox('points[]', $ePoint['id'], $attributes);
						$h .= '</label>';
						$h .= '<label for="'.$attributes['id'].'">';
							$h .= match($type) {
								Point::HOME => nl2br(encode($ePoint['zone'])),
								Point::PLACE => encode($ePoint['name']).' <small class="color-muted">'.encode($ePoint['address']).' '.encode($ePoint['place']).'</small>'
							};
						$h .= '</label>';

					$h .= '</div>';

				}

			}

		$h .= '</div>';

		return $h;

	}

	public function getOrderHours(Date $eDate): string {

		$h = '';
		if(substr($eDate['orderStartAt'], 0, 10) !== substr($eDate['orderEndAt'], 0, 10)) {

			$h .= s("du {date} à {hour}", [
				'date' => \util\DateUi::textual($eDate['orderStartAt'], \util\DateUi::DAY_MONTH),
				'hour' => substr($eDate['orderStartAt'], 11, 5),
			]);
			$h .= '<br />';
			$h .= s("au {date} à {hour}", [
				'date' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DAY_MONTH),
				'hour' => substr($eDate['orderEndAt'], 11, 5),
			]);

		} else {

			$h .= \util\DateUi::textual($eDate['orderStartAt'], \util\DateUi::DAY_MONTH);
			$h .= '<br />';
			$h .= s("{hourStart} à {hourEnd}", [
				'hourStart' => substr($eDate['orderStartAt'], 11, 5),
				'hourEnd' => substr($eDate['orderEndAt'], 11, 5),
				]);

		}

		return $h;
	}

	public function getList(\farm\Farm $eFarm, Shop $eShop, \Collection $cDate): string {

		if($cDate->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune vente à afficher.").'</div>';
		}

		$h = '<div class="dates-item-wrapper stick-sm util-overflow-sm">';

			$h .= '<table class="sale-item-table tr-bordered tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Date de vente").'</th>';
						$h .= '<th>'.s("Ouverture des ventes").'</th>';
						$h .= '<th class="text-end">'.s("Produits").'</th>';
						$h .= '<th class="text-end" colspan="2">'.s("Commandes").'</th>';
						$h .= '<th class="text-end">';
							$h .= s("Montant");
							if($eFarm['selling']['hasVat']) {
								$h .= ' '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
							}
						$h .= '</th>';
						$h .= '<th></th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cDate as $eDate) {
						$h .= '<tr>';

							$h .= '<td class="td-min-content">';
								$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eShop, $eDate).'" class="btn btn-outline-primary" style="width: 100%">';
									$h .= '<span class="hide-xs-down">'.\util\DateUi::textual($eDate['deliveryDate']).'</span>';
									$h .= '<span class="hide-sm-up">'.\util\DateUi::numeric($eDate['deliveryDate']).'</span>';
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= $this->getStatus($eShop, $eDate);
							$h .= '</td>';

							$h .= '<td class="text-end td-min-content">';
								$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eShop, $eDate).'?tab=products">'.$eDate['products'].'</a>';
							$h .= '</td>';

							$h .= '<td class="text-end">';
								$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eShop, $eDate).'?tab=sales">'.$eDate['sales']['countValid'].'</a>';
							$h .= '</td>';

							$h .= '<td class="td-min-content">';

								if($eDate['sales']['countValid'] > 0) {

									$h .= '<a href="/shop/date:downloadSales?id='.$eDate['id'].'&farm='.$eDate['farm']['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary" title="'.s("Exporter les commandes").'">'.\Asset::icon('filetype-pdf').'</a>';

								}

							$h .= '</td>';

							$h .= '<td class="text-end">';
								if($eDate['sales']['countValid'] > 0) {
									$h .= $eDate['sales']['amountValidIncludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidIncludingVat']) : '-';
								}
							$h .= '</td>';

							$h .= '<td class="text-end">';

								if(
									$eDate->canWrite() or
									$eDate['sales']['count'] > 0
								) {

									$h .= $this->getMenu($eShop, $eDate, $eDate['sales']['count'], 'btn-outline-secondary');

								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getMenu(Shop $eShop, Date $eDate, int $sales, string $btn): string {

		$eDate->expects(['farm']);

		$h = '<div>';
			$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
			$h .= '<div class="dropdown-list">';

				$h .= '<div class="dropdown-title">'.\util\DateUi::textual($eDate['deliveryDate']).'</div>';

				if($eDate->canWrite()) {

					$h .= '<a href="/shop/date:update?id='.$eDate['id'].'" class="dropdown-item">'.s("Paramétrer la vente").'</a>';
					$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eDate['farm']['id'].'&date='.$eDate['id'].'" class="dropdown-item">'.s("Nouvelle vente à partir de celle-ci").'</a>';

					if($sales === 0) {
						$h .= '<div class="dropdown-divider"></div>';
						$h .= '<a data-ajax="/shop/date:doDelete" post-id="'.$eDate['id'].'" post-farm="'.$eDate['farm']['id'].'" post-shop="'.$eShop['id'].'" class="dropdown-item" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer cette vente ?").'">'.s("Supprimer la vente").'</a>';
					}

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getStatus(Shop $eShop, Date $eDate, bool $withColor = TRUE): string {

		$h = '';
		$now = currentDatetime();

		if($eShop['status'] === Shop::CLOSED) {
			$h .= '<div class="color-danger">'.\Asset::icon('exclamation-triangle-fill').' '.s("Boutique fermée").'</div>';
		} else {

			if($eDate['status'] === Date::CLOSED) {
				$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle-fill').' '.s("Vente hors ligne").'</span>';
			} else if($eDate['deliveryDate'] < $now) {
				$h .= '<span class="color-success">'.s("Vente terminée").'</span>';
			} else if($eDate['orderEndAt'] <= $now) {
				$h .= '<span class="color-order">'.s("Ventes fermées").'</span>';
			} else if($eDate['orderStartAt'] < $now and $eDate['orderEndAt'] > $now) {
				$h .= '<span class="color-order">'.s("Ventes ouvertes encore {value}", \util\DateUi::secondToDuration(strtotime($eDate['orderEndAt']) - time(), \util\DateUi::AGO, maxNumber: 1)).'</span>';
			} else if($eShop['status'] === Shop::OPEN) {
				$h .= s("Ouverture des ventes dans {value}", \util\DateUi::secondToDuration(strtotime($eDate['orderStartAt']) - time(), \util\DateUi::AGO, maxNumber: 1));
			}

		}

		return $withColor ? $h : strip_tags($h);

	}
	
	public function getContent(\farm\Farm $eFarm, Shop $eShop, Date $eDate, \Collection $cSale): string {

		$cProduct = $eDate['cProduct'];
		$h = '<div class="tabs-h" id="shop-date-tabs" onrender="'.encode('Lime.Tab.restore(this, "products"'.(get_exists('tab') ? ', "'.GET('tab', ['products', 'sales'], 'products').'"' : '').')').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item" data-tab="products" onclick="Lime.Tab.select(this)">';
					$h .= s("Produits");
					if($cProduct->notEmpty()) {
						$h .= ' ('.$cProduct->count().')';
					}
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="sales" onclick="Lime.Tab.select(this)">';
					$h .= s("Commandes");
					if($cSale->notEmpty()) {
						$h .= ' ('.$cSale
								->find(fn($eSale) => in_array($eSale['preparationStatus'], [\selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED]))
								->count().')';
					}
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="points" onclick="Lime.Tab.select(this)">';
					$h .= s("Modes de livraison").' ('.(($eDate['ccPoint'][Point::HOME] ?? new \Collection())->count() + ($eDate['ccPoint'][Point::PLACE] ?? new \Collection())->count()).')';
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="products">';

				$h .= '<div class="util-action">';
				
					$h .= '<div></div>';
				
					$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eDate['shop'], $eDate).'/product:create" class="btn btn-primary">';
						$h .= \Asset::icon('plus-circle').' ';
						if($cProduct->empty()) {
							$h .= s("Ajouter des produits à la vente");
						} else {
							$h .= s("Ajouter d'autres produits à la vente");
						}
					$h .= '</a>';

				$h .= '</div>';

				$h .= (new \shop\ProductUi())->getUpdateList($eDate, $cProduct);
				$h .= '<br />';
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="sales">';

				$h .= '<div class="util-action">';

					$h .= '<div></div>';

					$h .= '<a href="/shop/date:downloadSales?id='.$eDate['id'].'&farm='.$eDate['farm']['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('filetype-pdf').' '.s("Exporter les commandes").'</a>';

				$h .= '</div>';

				if($cSale->empty()) {
					$h .= '<div class="util-info">'.s("Aucune commande n'a encore été enregistrée pour cette vente !").'</div>';
				} else {
					$h .= (new \selling\SaleUi())->getList($eFarm, $cSale, hide: ['deliveredAt', 'documents', 'items'], show: ['point'], dynamicHide: ['paymentMethod' => '']);
				}
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="points">';
				$h .= (new PointUi())->getByDate($eShop, $eDate, $eDate['ccPoint']);
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getDetails(Shop $eShop, Date $eDate): string {
		
		$h = '<div class="util-block" style="margin-bottom: 2rem">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>';
					$h .= s("Statut de la vente");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->toggle($eDate);
				$h .= '</dd>';

				$h .= '<dt style="grid-row: span 2">';
					$h .= s("Prise des commandes");
				$h .= '</dt>';
				$h .= '<dd style="grid-row: span 2">';
					$h .= $this->getOrderHours($eDate);
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Ouverture des ventes");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->getStatus($eShop, $eDate);
				$h .= '</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Date::model()->describer($property, [
			'orderStartAt' => s("Ouverture des commandes"),
			'orderEndAt' => s("Fin des commandes"),
			'deliveryDate' => s("Date de livraison des commandes"),
			'status' => s("Statut"),
			'points' => s("Modes de livraison pour cette date"),
		]);

		switch($property) {

			case 'status' :
				$d->field = 'switch';
				$d->attributes = [
					'labelOn' => s("En ligne - visible pour tous"),
					'labelOff' => s("Hors ligne - visible seulement de vous"),
				];
				break;

			case 'deliveryDate' ;
				$d->prepend = s("Le");
				$d->labelAfter = \util\FormUi::info(s("Doit avoir lieu après la fin de la prise des commandes"));
				break;

			case 'products':
				$d->field = function(\util\FormUi $form, Date $e) {
					return (new DateUi())->getProducts($form, $e);
				};
				break;

			case 'points':
				$d->field = function(\util\FormUi $form, Date $e) {
					return (new DateUi())->getPoints($form, $e);
				};
				$d->labelAfter = \util\FormUi::info(s("Sélectionnez au moins un mode de livraison pour cette vente."));
				break;

		}

		return $d;

	}
}