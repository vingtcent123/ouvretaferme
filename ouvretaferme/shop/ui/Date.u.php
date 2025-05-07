<?php
namespace shop;

class DateUi {

	public function __construct() {

		\Asset::css('shop', 'date.css');

		\Asset::css('shop', 'manage.css');
		\Asset::js('shop', 'manage.js');

	}

	public static function name(Date $e): string {
		return s("Livraison du {value}", lcfirst(\util\DateUi::getDayName(date('N', strtotime($e['deliveryDate'])))).' '.\util\DateUi::textual($e['deliveryDate']));
	}

	public function toggle(Date $eDate) {

		return \util\TextUi::switch([
			'id' => 'date-switch-'.$eDate['id'],
			'disabled' => $eDate->canWrite() === FALSE,
			'data-ajax' => $eDate->canWrite() ? '/shop/date:doUpdateStatus' : NULL,
			'post-id' => $eDate['id'],
			'post-status' => ($eDate['status'] === Date::ACTIVE) ? Date::INACTIVE : Date::ACTIVE
		], $eDate['status'] === Date::ACTIVE, s("En ligne"), ("Hors ligne"));

	}

	public function togglePoint(Date $eDate, Point $ePoint, bool $selected) {

		return \util\TextUi::switch([
			'id' => 'point-switch-'.$ePoint['id'],
			'disabled' => $eDate->canWrite() === FALSE,
			'data-ajax' => $eDate->canWrite() ? '/shop/date:doUpdatePoint' : NULL,
			'post-id' => $eDate['id'],
			'post-point' => $ePoint['id'],
			'post-status' => $selected ? 0 : 1
		], $selected, s("Activé"), s("Désactivé"));

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

			// L'intervalle entre la précédente livraison et toutes les autres dates est appliqué sur la nouvelle livraison.
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
			$h .= '<div class="util-overflow-xs">';
				$h .= '<div class="shop-header-date-days">';
					foreach($cDate as $eDate) {

						$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).(Shop::isEmbed() ? '?embed' : '').'" class="'.($eDate['id'] === $eDateSelected['id'] ? 'selected' : '').'">';
							$h .= '<div class="shop-header-date-day-name">'.\util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).'</div>';
							$h .= '<div class="shop-header-date-day-value">'.\util\DateUi::textual($eDate['deliveryDate'], \util\DateUi::DAY_MONTH).'</div>';
						$h .= '</a>';

					}
				$h .= '</div>';
			$h .= '</div>';

		} else {
			$h .= $this->getEmptyPeriod();
		}

		return $h;

	}

	public function getDeliveryPeriod(Date $eDate, string $for = 'next', string $cssPrefix = 'shop'): string {

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

			$calendar = match($cssPrefix) {
				'shop' => \Asset::icon('calendar3'),
				'website-widget' => '📅'
			};

			$h .= '<h4>'.$calendar.'&nbsp;&nbsp;'.$title.'</h4>';
			$h .= '<div class="'.$cssPrefix.'-header-date-day">';
				$h .= \util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).' '.\util\DateUi::textual($eDate['deliveryDate']).'<br/>';
			$h .= '</div>';

		} else {
			$h .= $this->getEmptyPeriod();
		}

		return $h;

	}

	protected function getEmptyPeriod(string $cssPrefix = 'shop'): string {

		$h = '<h4>'.\Asset::icon('calendar3').'&nbsp;&nbsp;'.s("Prochaine vente").'</h4>';

		$h .= '<div class="'.$cssPrefix.'-header-date-content">';
			$h .= s("Date à venir bientôt !");
		$h .= '</div>';

		return $h;

	}

	public function getOrderPeriod(Date $eDate): string {

		$h = '';

		if($eDate->acceptOrder()) {
			$h .= s("Commande possible jusqu'au {date}", ['date' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderEndAt'])))).' '.\util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
		} else if($eDate->acceptOrderSoon()) {
			$h .= s("Commandes ouvertes du {from} jusqu'au {to}", ['from' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderStartAt'])))).' '.\util\DateUi::textual($eDate['orderStartAt'], \util\DateUi::DAY_MONTH | \util\DateUi::TIME_HOUR_MINUTE), 'to' => lcfirst(\util\DateUi::getDayName(date('N', strtotime($eDate['orderEndAt'])))).' '.\util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DAY_MONTH | \util\DateUi::TIME_HOUR_MINUTE)]);
		} else if($eDate->isExpired()) {
			$h .= s("Vente est terminée, et il n'y a pas encore d'autre vente ouverte");
		}

		if($eDate->isOrderSoonExpired()) {
			$h .= '<br/>'.\Asset::icon('exclamation-circle').'  '.s("Attention, il ne reste plus que quelques minutes pour finaliser votre commande, ne tardez pas !");
		}

		return $h;

	}

	public function getOrderLimits(Shop $eShop, \Collection $ccPoint): string {

		$points = $ccPoint->reduce(fn($c, $n) => $n + $c->count(), 0);

		$h = '';

		$orderMin = $ccPoint->getColumn('orderMin');
		if(count($orderMin) !== $points) { // Pas de valeur pour tous les points, on ajoute la valeur par défaut
			$orderMin[] = $eShop['orderMin'];
		}

		$orderMin = array_unique(array_merge([$eShop['orderMin']], $ccPoint->getColumn('orderMin')));

		if(count($orderMin) === 1) {

			$value = $orderMin[0];

			if($value > 0) {
				$h .= s("Minimum de commande de {value} €", $value);
			}

		} else {

			$min = min($orderMin);
			$max = max($orderMin);

			if($max > 0) {

				if($min > 0) {
					$h .= s("Minimum de commande compris entre {min} € et {max} € selon le mode de retrait", ['min' => $min, 'max' => $max]);
				}

			}

		}

		return $h;

	}

	public function create(Date $e, \Collection $cProduct, Date $eDateBase = new Date()): string {

		$form = new \util\FormUi([
			'columnBreak' => 'md',
			'firstColumnSize' => 25
		]);

		if($e['cCatalog']->empty()) {
			$e['source'] = Date::DIRECT;
		} else {
			$e['source'] = NULL;
		}

		// $eDateBase est la date de référence sur laquelle baser la nouvelle date à créer.
		if($eDateBase->notEmpty()) {

			$this->calculateDates($e, $eDateBase);

			// Setter les produits sélectionnés et leur prix s'ils sont toujours disponibles
			foreach($eDateBase['cProduct'] as $eProduct) {

				if($cProduct->offsetExists($eProduct['product']['id'])) {

					$cProduct[$eProduct['product']['id']]['checked'] = TRUE;

					if($eProduct['price'] !== NULL) {
						$cProduct[$eProduct['product']['id']]['privatePrice'] = $eProduct['price'];
					}

				}

			}

			$e['points'] = $eDateBase['points'];
			$e['source'] ??= $eDateBase['source'];

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

			if($e['shop']['hasPoint']) {
				$h .= $form->dynamicGroup($e, 'points*');
			}

			$h .= $this->getOrderField('create', $form, $e);
			$h .= $form->dynamicGroup($e, 'deliveryDate*');

			$grid = match($e['shop']['type']) {
				Shop::PRO => s("Professionnels"),
				Shop::PRIVATE => s("Particuliers")
			};

			$h .= $form->group(
				s("Grille tarifaire"),
				$form->fake($grid)
			);

			if($e['shop']['shared'] === FALSE) {

				if($e['cCatalog']->notEmpty()) {
					$h .= $form->dynamicGroup($e, 'source*');
					$h .= '<div data-ref="date-catalog" class="'.($e['source'] === Date::CATALOG ? '' : 'hide').'">';
						$h .= $form->dynamicGroup($e, 'catalogs*');
					$h .= '</div>';
				} else {
					$h .= $form->hidden('source', Date::DIRECT);
				}

				$h .= '<div data-ref="date-direct" class="'.($e['source'] === Date::DIRECT ? '' : 'hide').'">';
					$h .= '<h3 class="mt-2">'.self::p('productsList')->label.'</h3>';
					$h .= $form->dynamicField($e, 'productsList');
				$h .= '</div>';

			} else {

				if($e['cCatalog']->empty()) {
					$h .= $form->group(
						self::p('catalogs')->label,
						'<div class="util-block-help">'.s("Vos producteurs n'ont pas encore connecté de catalogue à cette boutique. Vous devez d'abord battre le rappel des troupes avant de créer une première livraison !").'</div>'
					);
				} else {
					$h .= $form->dynamicGroup($e, 'catalogs*');
				}
			}

			$h .= '<br/>';

			if(
				$e['shop']->isPersonal() or
				$e['cCatalog']->notEmpty()
			) {

				$h .= $form->group(
					content: '<p class="util-danger">'.s("Veuillez corriger les erreurs en rouge pour continuer.").'</p>'.
					$form->submit(s("Ajouter la livraison"))
				);

			}

		$h .= $form->close();

		return $h;
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

		if(
			$eDate['shop']['hasPoint'] and
			$eDate->isExpired() === FALSE
		) {
			$h .= $form->dynamicGroup($eDate, 'points');
		}

		$h .= $this->getOrderField('update', $form, $eDate);
		$h .= $form->dynamicGroup($eDate, 'deliveryDate');
		$h .= $form->dynamicGroup($eDate, 'description');

		$h .= $form->group(
			content: $form->submit(s("Modifier"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-date-update',
			title: s("Paramétrer la livraison"),
			body: $h
		);
	}

	public function getPoints(\util\FormUi $form, Date $eDate): string {

		$eDate->expects(['farm', 'ccPoint']);

		$eShop = $eDate['shop'];
		$ccPoint = $eDate['ccPoint'];

		if($ccPoint->empty()) {
			$h = '<div class="util-block-util-block-help">';
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
						$h .= '<label class="date-points-label" for="'.$attributes['id'].'">';
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
			return '<div class="util-empty">'.s("Il n'y a aucune vente à afficher.").'</div>';
		}

		$hasFarmTaxes = $eFarm->getSelling('hasVat');
		$hasSameTaxes = ($hasFarmTaxes and count(array_count_values($cDate->getColumn('type'))) === 1);

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.s("Ventes").'</th>';
						$h .= '<th class="text-end highlight">'.s("Montant").''.($hasSameTaxes ? ' <span class="util-annotation">'.$cDate->first()->getTaxes().'</span>' : '').'</th>';
						$h .= '<th class="text-end hide-md-down">'.s("Panier moyen").''.($hasSameTaxes ? ' <span class="util-annotation">'.$cDate->first()->getTaxes().'</span>' : '').'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cDate as $eDate) {

						if($hasFarmTaxes) {
							$taxes = ($hasSameTaxes ? '' : ' <span class="util-annotation">'.$eDate->getTaxes().'</span>');
						}

						$h .= '<tr>';

							$h .= '<td class="td-min-content">';
								$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eDate).'" class="btn btn-outline-primary" style="width: 100%">';
									$h .= '<span class="hide-xs-down">'.\util\DateUi::textual($eDate['deliveryDate']).'</span>';
									$h .= '<span class="hide-sm-up">'.\util\DateUi::numeric($eDate['deliveryDate']).'</span>';
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td>';
								if($eDate['status'] === Date::INACTIVE) {
									$h .= '<span class="color-danger">'.s("Vente hors ligne").'</span>';
								} else {
									$h .= $this->getStatus($eShop, $eDate);
								}
							$h .= '</td>';

							$h .= '<td class="text-end">';
								$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eDate).'?tab=sales">'.$eDate['sales']['countValid'].'</a>';
							$h .= '</td>';

							$h .= '<td class="text-end highlight" style="white-space: nowrap">';
								if($eDate['sales']['countValid'] > 0) {

									if($hasFarmTaxes) {

										$h .= match($eDate['type']) {
											Date::PRIVATE => $eDate['sales']['amountValidIncludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidIncludingVat']).$taxes : '-',
											Date::PRO => $eDate['sales']['amountValidExcludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidExcludingVat']).$taxes : '-'
										};

									} else {
										$h .= $eDate['sales']['amountValidExcludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidExcludingVat']) : '-';
									}

								}
							$h .= '</td>';

							$h .= '<td class="text-end hide-md-down">';

								if($eDate['sales']['countValid'] > 0) {

									if($hasFarmTaxes) {

										$h .= match($eDate['type']) {
											Date::PRIVATE => $eDate['sales']['amountValidIncludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidIncludingVat'] / $eDate['sales']['countValid'], precision: 0).$taxes : '-',
											Date::PRO => $eDate['sales']['amountValidExcludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidExcludingVat'] / $eDate['sales']['countValid'], precision: 0).$taxes : '-'
										};

									} else {
										$h .= $eDate['sales']['amountValidExcludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidExcludingVat'] / $eDate['sales']['countValid'], precision: 0) : '-';
									}

								}

							$h .= '</td>';

							$h .= '<td class="text-end" style="white-space: nowrap">';

								if($eDate['sales']['countValid'] > 0) {

									$h .= '<a href="/shop/date:downloadSales?id='.$eDate['id'].'&farm='.$eDate['farm']['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary" title="'.s("Exporter les commandes").'">'.\Asset::icon('download').'  '.s("PDF").'</a> ';

								}


								if($eDate->canWrite() or $eShop->canWrite()) {

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

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';

			$h .= '<div class="dropdown-title">'.\util\DateUi::textual($eDate['deliveryDate']).'</div>';

			if($eDate->canWrite()) {

				$h .= '<a href="/shop/date:update?id='.$eDate['id'].'" class="dropdown-item">'.s("Paramétrer la livraison").'</a>';
				if($eDate->isDirect()) {
					$h .= '<a href="/shop/product:createCollection?date='.$eDate['id'].'" class="dropdown-item">'.s("Ajouter des produits à la livraison").'</a>';
				}

			}

			if($eShop->canWrite()) {
				$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eDate['farm']['id'].'&date='.$eDate['id'].'" class="dropdown-item">'.s("Nouvelle livraison à partir de celle-ci").'</a>';
			}

			if($eDate->canWrite() and $sales === 0) {
				$h .= '<div class="dropdown-divider"></div>';
				$h .= '<a data-ajax="/shop/date:doDelete" post-id="'.$eDate['id'].'" post-farm="'.$eDate['farm']['id'].'" post-shop="'.$eShop['id'].'" class="dropdown-item" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer cette livraison ?").'">'.s("Supprimer la livraison").'</a>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getStatus(Shop $eShop, Date $eDate, bool $withColor = TRUE): string {

		$h = '';
		$now = currentDatetime();

		$textDelivered = '<div class="color-success">'.\Asset::icon('check-lg').' '.s("Livré").'</div>';

		if($eShop['status'] === Shop::CLOSED) {
			$h .= '<div class="color-danger">'.\Asset::icon('exclamation-triangle-fill').' '.s("Boutique fermée").'</div>';
		} else if($eDate['status'] === Date::CLOSED) {
			$h .= $textDelivered;
		} else {

			if($eDate['orderStartAt'] < $now and $eDate['orderEndAt'] > $now) {
				$h .= '<span class="color-order">'.s("Prises de commande ouvertes encore {value}", \util\DateUi::secondToDuration(strtotime($eDate['orderEndAt']) - time(), \util\DateUi::AGO, maxNumber: 1)).'</span>';
			} else if($eDate['orderEndAt'] < $now) {
				if(currentDate() === $eDate['deliveryDate']) {
					$h .= s("Livraison aujourd'hui");
				} else if(currentDate() < $eDate['deliveryDate']) {
					$h .= s("En attente de livraison le {value}", \util\DateUi::numeric($eDate['deliveryDate']));
				} else {
					$h .= $textDelivered;
				}
			} else {
				$h .= s("Ouverture des ventes dans {value}", \util\DateUi::secondToDuration(strtotime($eDate['orderStartAt']) - time(), \util\DateUi::AGO, maxNumber: 1));
			}

		}

		return $withColor ? $h : strip_tags($h);

	}
	
	public function getContent(\farm\Farm $eFarm, Shop $eShop, Date $eDate, \Collection $cSale): string {

		$h = '<div class="tabs-h" id="shop-date-tabs" onrender="'.encode('Lime.Tab.restore(this, "products"'.(get_exists('tab') ? ', "'.GET('tab', ['products', 'sales'], 'products').'"' : '').')').'">';

			$h .= '<div class="tabs-item">';

				$h .= '<a class="tab-item" data-tab="products" onclick="Lime.Tab.select(this)">';
					$h .= s("Produits");
					if($eDate['nProduct'] > 0) {
						$h .= '<span class="tab-item-count">'.$eDate['nProduct'].'</span>';
					}
				$h .= '</a>';

				if($eShop['shared']) {

					$h .= '<a class="tab-item" data-tab="farms" onclick="Lime.Tab.select(this)">';
						$h .= s("Producteurs");
						if($eShop['ccRange']->count() > 0) {
							$h .= '<span class="tab-item-count">'.$eShop['ccRange']->count().'</span>';
						}
					$h .= '</a>';

				}

				$h .= '<a class="tab-item" data-tab="sales" onclick="Lime.Tab.select(this)">';
					$h .= s("Ventes");
					if($cSale->notEmpty()) {
						$h .= '<span class="tab-item-count">'.$cSale
							->find(fn($eSale) => in_array($eSale['preparationStatus'], [\selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED]))
							->count().'</span>';
					}
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="points" onclick="Lime.Tab.select(this)">';
					$h .= s("Modes de livraison");
					if($eShop['hasPoint']) {
						$h .= '<span class="tab-item-count">'.(($eDate['ccPoint'][Point::HOME] ?? new \Collection())->count() + ($eDate['ccPoint'][Point::PLACE] ?? new \Collection())->count()).'</span>';
					}
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="products">';
				$h .= $this->getProducts($eFarm, $eShop, $eDate);
			$h .= '</div>';

			if($eShop['shared']) {

				$h .= '<div class="tab-panel" data-tab="farms">';
					$h .= $this->getFarms($eFarm, $eShop, $eDate, $eShop['cDepartment'], $eShop['ccRange']);
				$h .= '</div>';

			}

			$actions = '';

			$eDate['shop'] = $eShop;

			if($eShop['shared']) {
				$actions .= self::getSearchFarm($eFarm, $eShop, $eDate);
			}

			if(
				$eDate->acceptOrder() and
				$eDate->acceptNotShared()
			) {
				$actions .= '<a href="/selling/sale:create?farm='.$eDate['farm']['id'].'&shopDate='.$eDate['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter une commande").'</a> ';
			}

			if(
				$cSale->notEmpty() and
				($eShop->isPersonal() or $eShop->canWrite() === FALSE) // L'administrateur ne peut pas télécharger de PDF
			) {
				$actions .= '<a href="/shop/date:downloadSales?id='.$eDate['id'].'&farm='.$eFarm['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('download').' '.s("Télécharger en PDF").'</a>';
			}

			$h .= '<div class="tab-panel" data-tab="sales">';

				if($actions) {

					$h .= '<div class="util-title">';

						$h .= '<div></div>';
						$h .= '<div class="flex-align-center">';
							$h .= $actions;
						$h .= '</div>';

					$h .= '</div>';

				}

				if($cSale->empty()) {
					$h .= '<div class="util-info">'.s("Aucune vente n'a encore été enregistrée !").'</div>';
				} else {

					$h .= new \selling\SaleUi()->getList(
						$eFarm,
						$cSale,
						hide: array_merge(['deliveredAt', 'documents', 'items'], $cSale->match(fn($eSale) => $eSale['paymentMethod'] !== NULL) ? [] : ['paymentMethod']),
						dynamicHide: ['paymentMethod' => ''],
						show: ['point'],
						hasSubtitles: FALSE,
						segment: ($eDate['ccPoint']->reduce(fn($c, $n) => $n + $c->count(), 0) > 1) ? 'point' : NULL
					);

				}
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="points">';
				if($eShop['hasPoint']) {
					$h .= new PointUi()->getByDate($eShop, $eDate, $eDate['ccPoint']);
				} else {
					$h .= new ShopUi()->updateInactivePoint($eShop);
				}
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getFarms(\farm\Farm $eFarm, Shop $eShop, Date $eDate, \Collection $cDepartment, \Collection $ccRange): string {

		if($ccRange->empty()) {
			return '<div class="util-empty">'.s("Aucun producteur n'a associé de catalogue à cette boutique.").'</div>';
		}

		$h = '<div class="util-overflow-xs">';
			$h .= '<table class="tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th>'.s("Producteur").'</th>';
						$h .= '<th class="highlight-stick-right">'.s("Catalogue").'</th>';
						if($cDepartment->notEmpty()) {
							$h .= '<th class="highlight-stick-both">';
								$h .= s("Rayon");
							$h .= '</th>';
						}
						$h .= '<th class="highlight-stick-left"></th>';
					$h .= '</tr>';

				$h .= '</thead>';

				foreach($eShop['cShare'] as $eShare) {

					$cRange = clone ($ccRange[$eShare['farm']['id']] ?? new \Collection());
					$rangeCatalogs = $cRange->getColumnCollection('catalog')->getIds();

					// Ajout des catalogues de la ferme pas dans range
					foreach($eDate['cCatalog'] as $eCatalog) {

						if(
							$eCatalog['farm']['id'] === $eShare['farm']['id'] and
							in_array($eCatalog['id'], $rangeCatalogs) === FALSE
						) {
							$cRange[] = new Range([
								'id' => NULL,
								'catalog' => $eCatalog,
								'department' => new Department()
							]);
						}

					}

					if($cRange->empty()) {
						continue;
					}

					foreach($cRange as $eRange) {
						$h .= '<tr>';
							$h .= $this->getCatalog($eFarm, $eDate, $eShare, $eRange, $cDepartment);
						$h .= '</tr>';
					}

				}


			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	protected function getCatalog(\farm\Farm $eFarm, Date $eDate, Share $eShare, Range $eRange, \Collection $cDepartment): string {

		$eRange->expects(['id', 'catalog', 'department']);

		$isFarmSelected = ($eFarm['id'] === $eShare['farm']['id']);

		$h = '<td>';
			$h .= \farm\FarmUi::getVignette($eShare['farm'], '2rem').'  ';
			$h .= encode($eShare['farm']['name']);
			if($isFarmSelected) {
				$h .= '  <span class="util-badge bg-primary">'.s("Votre ferme").'</span>';
			}
		$h .= '</td>';
		$h .= '<td class="td-border highlight-stick-right">';
			$h .= '<a href="/shop/catalog:show?id='.$eRange['catalog']['id'].'">'.encode($eRange['catalog']['name']).'</a>';
			$h .= ' <small class="color-muted">/ '.p("{value} produit", "{value} produits", $eRange['catalog']['products']).'</small>';
		$h .= '</td>';

		if($cDepartment->notEmpty()) {
			$h .= '<td class="td-border highlight-stick-both">';
				if($eRange['department']->notEmpty()) {
					$h .= DepartmentUi::getVignette($cDepartment[$eRange['department']['id']], '1.75rem').'  '.encode($cDepartment[$eRange['department']['id']]['name']);
				} else {
					$h .= '-';
				}
			$h .= '</td>';
		}

		$h .= '<td class="td-border highlight-stick-left">';

			$isRangeSelected = in_array($eRange['catalog']['id'], $eDate['catalogs']);
			$canUpdateRange = ($eDate->canWrite() or $isFarmSelected);

			$h .= \util\TextUi::switch([
				'id' => 'catalog-switch-'.$eRange['catalog']['id'],
				'disabled' => $canUpdateRange === FALSE,
				'data-ajax' => $canUpdateRange ? '/shop/date:doUpdateCatalog' : NULL,
				'post-date' => $eDate['id'],
				'post-catalog' => $eRange['catalog']['id'],
				'post-status' => $isRangeSelected ? 0 : 1
			], $isRangeSelected, s("Activé"), ("Désactivé"));

		$h .= '</td>';

		return $h;

	}

	public function getProducts(\farm\Farm $eFarm, Shop $eShop, Date $eDate): string {

		$h = '';

		if($eShop['shared']) {

			$h .= '<div class="util-title">';

				$h .= '<div></div>';
				$h .= '<div>';
					$h .= self::getSearchFarm($eFarm, $eShop, $eDate);
				$h .= '</div>';

			$h .= '</div>';

		}

		if(currentDate() > $eDate['deliveryDate']) {
			$h .= $this->getDeliveredProducts($eFarm, $eShop, $eDate);
		} else {
			$h .= $this->getPendingProducts($eFarm, $eShop, $eDate);
		}

		return $h;

	}

	private static function getSearchFarm(\farm\Farm $eFarm, Shop $eShop, Date $eDate): string {

		$eShop->expects(['cShare', 'eFarmSelected']);

		$cShare = $eShop['cShare'];

		$id = 'search-'.microtime(TRUE);

		$h = '<div class="input-group">';
			$h .= '<a data-dropdown="bottom-end" data-dropdown-id="'.$id.'" class="btn btn-outline-primary dropdown-toggle">';
				$h .= \Asset::icon('search').'  ';
				$h .= $eShop['eFarmSelected']->empty() ? s("Producteur") : encode($eShop['eFarmSelected']['name']);
			$h .= '</a>';

			if($eShop['eFarmSelected']->notEmpty()) {
				$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eDate).'?farm=" class="btn btn-primary">'.\Asset::icon('x-lg').'</a>';
			}
		$h .= '</div>';
		$h .= '<div class="dropdown-list" data-dropdown-id="'.$id.'-list">';
			foreach($cShare as $eShare) {
				$h .= '<a href="'.ShopUi::adminDateUrl($eFarm, $eDate).'?farm='.$eShare['farm']['id'].'" class="dropdown-item '.($eShare['farm']->is($eShop['eFarmSelected']) ? 'selected' : '').'">'.encode($eShare['farm']['name']).'</a>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getDeliveredProducts(\farm\Farm $eFarm, Shop $eShop, Date $eDate): string {

		$h = '';

		if($eDate['nProduct'] === 0) {
			$h .= '<div class="util-empty">'.s("Cette vente est terminée et aucun produit n'a été vendu.").'</div>';
		} else {
			$h .= '<div class="util-info">'.s("Cette vente est terminée et seule la liste des produits qui ont été vendus est consultable.").'</div>';
		}

		$h .= new ProductUi()->getUpdateDate($eFarm, $eShop, $eDate, TRUE);

		return $h;

	}

	public function getPendingProducts(\farm\Farm $eFarm, Shop $eShop, Date $eDate): string {

		$cCatalog = $eDate['cCatalog'];

		$h = '';

		if($eDate->acceptNotShared()) {

			$h .= '<div class="util-title">';

				if($cCatalog->notEmpty()) {

					$catalogs = [];

					foreach($cCatalog as $eCatalog) {

						if($eCatalog['status'] === Catalog::DELETED) {
							$catalogs[] = '<span class="btn btn-lg btn-primary disabled" style="margin: 0.125rem 0">'.encode($eCatalog['name']).'</span>';
						} else {
							$catalogs[] = '<a href="'.\farm\FarmUi::urlShopCatalog($eFarm).'?catalog='.$eCatalog['id'].'" class="btn btn-lg btn-primary" style="margin: 0.125rem 0">'.\Asset::icon('pencil-fill', ['class' => 'asset-icon-flip-h']).'  '.encode($eCatalog['name']).'</a>';
						}
					}

					$h .= '<h2>'.p("Catalogue {value}", "Catalogues {value}", count($catalogs), ['value' => implode(' ', $catalogs)]).'</h2>';

				} else {
					$h .= '<div></div>';
				}

				$h .= '<div>';

					$h .= ' <a href="/shop/product:createCollection?date='.$eDate['id'].'" class="btn btn-primary">';
						$h .= \Asset::icon('plus-circle').' ';
						if($cCatalog->notEmpty()) {
							$h .= s("Ajouter des produits hors catalogue");
						} else if($eDate['nProduct'] === 0) {
							$h .= s("Ajouter des produits à la vente");
						} else {
							$h .= s("Ajouter des produits");
						}
					$h .= '</a>';

				$h .= '</div>';

			$h .= '</div>';

		}

		foreach($cCatalog as $eCatalog) {

			if($eCatalog['status'] === Catalog::DELETED) {
				$h .= '<div class="util-danger">'.s("Le catalogue {value} a été supprimé.", '<u>'.encode($eCatalog['name']).'</u>').'</div>';
			}

		}

		if($eDate['nProduct'] === 0) {

			if($cCatalog->notEmpty()) {
				$h .= '<div class="util-empty">'.p("Il n'y a aucun produit dans le catalogue !", "Il n'y a aucun produit dans les catalogues !", $cCatalog->count()).'</div>';
			} else {
				$h .= '<div class="util-empty">'.s("Il n'y a aucun produit disponible à la vente !").'</div>';
			}

		} else {
			$h .= new ProductUi()->getUpdateDate($eFarm, $eShop, $eDate, FALSE);
		}

		return $h;

	}

	public function getDetails(Shop $eShop, Date $eDate): string {
		
		$h = '<div class="util-block" style="margin-bottom: 2rem">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>';
					$h .= s("Lien de la livraison");
				$h .= '</dt>';
				$h .= '<dd class="util-presentation-fill">';
					$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).'" id="date-url">'.ShopUi::dateUrl($eShop, $eDate).'</a>';
					$h .= '  <a onclick="doCopy(this)" data-selector="#date-url" data-message="'.s("Copié !").'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('clipboard').' '.s("Copier").'</a>';
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Statut de la livraison");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->getStatus($eShop, $eDate);
				$h .= '</dd>';

				$h .= '<dt style="align-self: center">';
					$h .= s("Visibilité");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->toggle($eDate);
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Prise des commandes");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->getOrderHours($eDate);
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Grille tarifaire");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= ShopUi::p('type')->values[$eDate['type']];
				$h .= '</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if($eDate['description'] !== NULL) {
			$h .= '<div class="util-block" style="margin-bottom: 2rem">';
				$h .= '<h4>'.s("Complément d'information pour cette vente").'</h4>';
				$h .= new \editor\EditorUi()->value($eDate['description']);
			$h .= '</div>';
		}

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Date::model()->describer($property, [
			'orderStartAt' => s("Ouverture des commandes"),
			'orderEndAt' => s("Fin des commandes"),
			'deliveryDate' => s("Livraison des commandes"),
			'source' => s("Gamme de produits proposée à la vente"),
			'catalogs' => s("Choisir les catalogues proposés à la vente"),
			'description' => s("Complément d'information"),
			'productsList' => s("Choisir les produits proposés à la vente"),
			'status' => s("Statut"),
			'points' => s("Modes de livraison activés"),
		]);

		switch($property) {

			case 'status' :
				$d->field = 'switch';
				$d->attributes = [
					'labelOn' => s("En ligne - visible pour tous"),
					'labelOff' => s("Hors ligne - visible seulement de vous"),
				];
				break;

			case 'source' :
				$d->values = [
					Date::CATALOG => s("Passer par les catalogues"),
					Date::DIRECT => s("Choisir mes produits"),
				];
				$d->attributes = [
					'callbackRadioAttributes' => function() {
						return [
							'onchange' => 'DateManage.changeSource(this)',
						];
					}
				];
				break;

			case 'description' ;
				$d->label .= \util\FormUi::info(s("Utilisez cet espace pour donner à vos clients des informations valables uniquement pour cette livraison, comme par exemple <i>Dernière vente avant nos congés annuels !</i>"));
				break;

			case 'deliveryDate' ;
				$d->prepend = s("Le");
				break;

			case 'catalogs' :
				$d->field = function(\util\FormUi $form, Date $e) {

					$e->expects(['cCatalog']);

					$h = '<div class="field-radio-group">';

							foreach($e['cCatalog'] as $eCatalog) {

								$eCatalog->expects('selected');

								$attributes = [
									'id' => 'checkbox-catalog-'.$eCatalog['id'],
									'checked' => $eCatalog['selected']
								];

								$h .= '<div class="date-points-item">';

									$h .= '<label class="shop-select">';
										$h .= $form->checkbox('catalogs[]', $eCatalog['id'], $attributes);
									$h .= '</label>';

									$h .= '<label class="date-points-label" for="'.$attributes['id'].'">';
										$h .= encode($eCatalog['name']).' <small>/ '.p("{value} produit", "{value} produits", $eCatalog['products']).'</small>';
									$h .= '</label>';

								$h .= '</div>';

							}

					$h .= '</div>';

					return $h;

				};
				break;

			case 'productsList' :
				$d->field = function(\util\FormUi $form, Date $e) {
					return new \selling\ItemUi()->getCreateList(
						$e['cProduct'], $e['cCategory'],
						fn($cProduct) => ProductUi::getCreateByCategory($form, $e['farm'], $e['type'], $cProduct)
					);
				};
				$d->group = [
					'wrapper' => 'productsList',
					'for' => FALSE
				];
				break;

			case 'points':
				$d->field = function(\util\FormUi $form, Date $e) {
					return new DateUi()->getPoints($form, $e);
				};
				break;

		}

		return $d;

	}
}