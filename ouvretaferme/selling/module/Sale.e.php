<?php
namespace selling;

class Sale extends SaleElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'shop' => ['fqn', 'shared', 'name', 'email', 'emailNewSale', 'emailEndDate', 'approximate', 'paymentCard', 'hasPayment', 'paymentOfflineHow', 'paymentTransferHow', 'shipping', 'shippingUntil', 'orderMin', 'embedOnly', 'embedUrl', 'farm' => ['name']],
			'shopDate' => \shop\Date::getSelection(),
			'shopPoint' => ['type', 'name'],
			'farm' => ['name', 'url', 'vignette', 'banner', 'featureDocument', 'hasSales'],
			'price' => fn($e) => $e['type'] === Sale::PRO ? $e['priceExcludingVat'] : $e['priceIncludingVat'],
			'invoice' => ['name', 'emailedAt', 'createdAt', 'priceExcludingVat', 'generation'],
			'compositionOf' => ['name'],
			'marketParent' => [
				'customer' => ['type', 'name']
			],
			'paymentStatus',
			'paymentMethod' => \payment\Method::getSelection(),
		];

	}

	public static function validateBatch(\Collection $cSale): void {

		if($cSale->empty()) {
			throw new \FailAction('selling\Sale::sales.check');
		}

	}

	public function isSale(): bool {

		$this->expects(['origin']);

		return $this['origin'] === Sale::SALE;

	}

	public function isMarket(): bool {

		$this->expects(['origin']);

		return $this['origin'] === Sale::MARKET;

	}

	public function isMarketSale(): bool {

		$this->expects(['origin']);

		return $this['origin'] === Sale::SALE_MARKET;

	}

	public function isComposition(): bool {

		$this->expects(['origin']);

		return $this['origin'] === Sale::COMPOSITION;

	}

	public function getDeliveryAddress(string $type = 'text'): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this['deliveryStreet1']."\n";
		if($this['deliveryStreet2'] !== NULL) {
			$address .= $this['deliveryStreet2']."\n";
		}
		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function getDeliveryAddressLink(): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		return 'https://www.google.com/maps/place/'.str_replace('%0A', ',', urlencode($this->getDeliveryAddress(','))).'/';

	}

	public function hasDeliveryAddress(): bool {
		return ($this['deliveryCity'] !== NULL);
	}

	public function copyAddressFromUser(\user\User $eUser, array &$properties = []): void {

		$this->merge([
			'deliveryStreet1' => $eUser['street1'],
			'deliveryStreet2' => $eUser['street2'],
			'deliveryPostcode' => $eUser['postcode'],
			'deliveryCity' => $eUser['city'],
		]);

		$properties = array_merge($properties, ['deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity']);

	}

	public function emptyAddress(&$properties = []): void {

		$this->merge([
			'deliveryStreet1' => NULL,
			'deliveryStreet2' => NULL,
			'deliveryPostcode' => NULL,
			'deliveryCity' => NULL,
		]);

		$properties = array_merge($properties, ['deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity']);

	}

	public function canRead(): bool {

		$this->expects(['farm', 'shop', 'shopShared']);

		if($this['farm']->canSelling()) {
			return TRUE;
		}

		if(
			$this['shop']->notEmpty() and
			$this['shopShared']
		) {

			return \shop\ShareLib::match($this['shop'], $this['farm']);

		}

		return FALSE;

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canDocument(string $type): bool {

		$this->expects(['farm']);

		return match($type) {
			Pdf::DELIVERY_NOTE => $this['farm']->canSelling(),
			default => $this['farm']->canManage()
		};

	}

	public function canManage(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canBasket(\shop\Shop $eShop): bool {

		return (
			$this->empty() or (
				$this['shop']->notEmpty() and
				$this['shop']['id'] === $eShop['id'] and
				$this['preparationStatus'] === Sale::BASKET
			)
		);

	}

	public function canUpdate(): bool {

		$this->expects(['preparationStatus']);

		return (
			$this->canWrite() and
			$this->isMarketSale() === FALSE
		);

	}

	public function canUpdatePreparationStatus(): bool {

		return $this->canWrite();

	}

	public function acceptUpdateCustomer(): bool {
		return (
			$this->isComposition() === FALSE and
			$this->isClosed() === FALSE and
			$this['shopShared'] === FALSE
		);
	}

	public function canUpdateCustomer(): bool {

		return $this->canWrite();

	}

	public function acceptUpdateMarketSalePayment(): bool {

		$this->expects(['paymentMethod', 'origin']);

		return (
			$this->isMarketSale() and
			$this['preparationStatus'] === Sale::DRAFT
		);

	}

	public function acceptUpdatePayment(): bool {

		$this->expects(['paymentMethod', 'invoice']);

		return (
			$this->isMarket() === FALSE and
			($this['paymentMethod']->empty() or $this->isPaymentOnline() === FALSE) and
			$this['preparationStatus'] !== Sale::CANCELED and
			$this['invoice']->empty()
		);

	}

	public function canAccess(): bool {

		$this->expects(['customer', 'farm', 'stats']);

		return $this['stats'] and (
			// Ferme
			$this->canRead() or
			// Client
			(
				\user\ConnectionLib::getOnline()->notEmpty() and
				$this['customer']['user']->notEmpty() and
				$this['customer']['user']['id'] === \user\ConnectionLib::getOnline()['id']
			)
		);

	}

	public function isPro(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRO;
	}

	public function isPrivate(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRIVATE;
	}

	public function isMarketPreparing(): bool {

		return (
			$this->isMarket() and
			in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED])
		);

	}

	public function isMarketSelling(): bool {

		return (
			$this->isMarket() and
			in_array($this['preparationStatus'], [Sale::SELLING])
		);

	}

	public function isMarketClosed(): bool {

		return (
			$this->isMarket() and
			$this->isLocked()
		);

	}

	public function isLocked(): bool {
		if($this->isComposition()) {
			return $this->acceptUpdateComposition() === FALSE;
		} else {
			return in_array($this['preparationStatus'], [Sale::CANCELED, Sale::DELIVERED, Sale::BASKET, Sale::CLOSED]);
		}
	}

	public function isOpen(): bool {
		return $this->isLocked() === FALSE;
	}

	public function isClosed(): bool {
		return $this['preparationStatus'] === Sale::CLOSED;
	}

	public function acceptUpdateItems(): bool {

		$this->expects(['preparationStatus', 'deliveredAt']);

		if($this->isMarketSale()) {
			return FALSE;
		}

		if(in_array($this['preparationStatus'], [Sale::COMPOSITION, Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]) === FALSE) {
			return FALSE;
		}

		if($this->isComposition()) {
			return $this->acceptUpdateComposition();
		} else {
			return TRUE;
		}

	}

	public function acceptUpdateComposition(): bool {

		if($this->isComposition()) {
			return self::testWriteComposition($this['deliveredAt']);
		} else {
			return TRUE;
		}

	}

	public static function testWriteComposition(string $date): bool {
		return $date >= date('Y-m-d', strtotime('NOW - '.\Setting::get('compositionLocked').' DAYS'));
	}

	public function acceptCreateItems(): bool {

		return $this->acceptUpdateItems();

	}

	public function acceptUpdateDeliveredAt(): bool {

		$this->expects(['preparationStatus', 'shop']);

		return (
			$this['preparationStatus'] !== Sale::DELIVERED and
			$this['shop']->empty() and
			$this->isMarketSale() === FALSE
		);

	}

	public function acceptUpdatePreparationStatus(): bool {

		return (
			$this->isComposition() === FALSE and
			(
				$this['shopDate']->empty() or
				$this['customer']['user']->is(\user\ConnectionLib::getOnline()) === FALSE or
				$this['shopDate']['isOrderable'] === FALSE
			)
		);

	}

	public function acceptUpdateShopPoint(): bool {

		return (
			$this->isLocked() === FALSE and
			$this['shop']->notEmpty()
		);

	}

	public function acceptDiscount(): bool {

		return (
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE
		);

	}

	public function acceptShipping(): bool {

		return (
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE and
			$this->isComposition() === FALSE and
			$this['shopShared'] === FALSE
		);

	}

	public function acceptUpdateShipping(): bool {

		return (
			$this->isLocked() === FALSE and
			$this->acceptShipping()
		);

	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function hasPaymentStatus(): bool {

		$this->expects(['invoice']);

		return (
			$this->isMarket() === FALSE and (
				$this['invoice']->notEmpty() or // Paiement par facturation
				$this->isPaymentOnline() // Ou paiement CB
			)
		);

	}

	public function isPaymentOnline(): bool {

		if($this['paymentMethod']->empty()) {
			return FALSE;
		}

		$this->expects(['paymentMethod' => ['fqn']]);

		return ($this['paymentMethod']['fqn'] === \payment\MethodLib::ONLINE_CARD);

	}

	public function acceptCancelDelivered(): bool {

		$this->expects(['preparationStatus', 'deliveredAt', 'invoice']);

		return (
			$this['preparationStatus'] === Sale::DELIVERED and
			$this['invoice']->empty() and
			time() - strtotime($this['deliveredAt']) < 86400 * 45
		);

	}

	public function acceptAnyDocument(): bool {

		return (
			$this->acceptDeliveryNote() or
			$this->acceptOrderForm() or
			$this->acceptInvoice()
		);

	}

	public function acceptDocument(string $type): bool {

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->acceptDeliveryNote(),
			Pdf::ORDER_FORM => $this->acceptOrderForm(),
			Pdf::INVOICE => $this->acceptInvoice(),
		};

	}

	public function acceptGenerateDocument(string $type): bool {

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->acceptGenerateDeliveryNote(),
			Pdf::ORDER_FORM => $this->acceptGenerateOrderForm(),
			Pdf::INVOICE => $this->acceptGenerateInvoice()
		};

	}

	public function acceptRegenerateDocument(string $type): bool {

		$this->expects(['invoice']);

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->acceptGenerateDocument($type),
			Pdf::ORDER_FORM => $this->acceptGenerateDocument($type),
			Pdf::INVOICE => $this['invoice']->notEmpty()
		};

	}

	public function acceptOrderForm(): bool {

		return
			$this->acceptDeliveryNote() and
			$this['shop']->empty();

	}

	public function acceptGenerateOrderForm(): bool {

		return $this->acceptOrderForm() and in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED]);

	}

	public function acceptInvoice(): bool {

		return (
			$this['customer']->notEmpty() and
			$this['customer']['destination'] !== Customer::COLLECTIVE and
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE
		);

	}

	public function acceptGenerateInvoice(): bool {
		return $this->acceptInvoice() and (
			$this['invoice']->empty() and
			$this['preparationStatus'] === Sale::DELIVERED
		);
	}

	public function acceptDeliveryNote(): bool {

		return (
			$this['customer']->notEmpty() and
			$this['customer']['destination'] !== Customer::COLLECTIVE and
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE and
			$this['farm']->hasFeatureDocument($this['type'])
		);

	}

	public function acceptGenerateDeliveryNote(): bool {

		return $this->acceptDeliveryNote() and ($this['preparationStatus'] === Sale::DELIVERED);

	}

	public function acceptAssociateShop(): bool {
		return (
			$this->isComposition() === FALSE and
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE and
			$this['shop']->empty()
		);
	}

	public function acceptDissociateShop(): bool {
		return (
			$this['shop']->notEmpty() and
			$this['shopShared'] === FALSE
		);
	}

	public function acceptDuplicate(): bool {

		return (
			// Il n'est pas possible de dupliquer une vente d'une boutique pour éviter de créer des incohérence au seins des boutiques et des disponibilités
			$this['shop']->empty() and
			$this->isComposition() === FALSE and
			$this->isMarketSale() === FALSE
		);

	}

	public function canRemote(): bool {
		return GET('key') === \Setting::get('selling\remoteKey') or $this->canRead();
	}

	public function getNumber(): string {
		return $this['document'];
	}

	public function getOrderForm(\farm\Farm $eFarm): string {

		$this->expects(['document']);

		$code = $eFarm->getSelling('orderFormPrefix');

		return Configuration::getNumber($code, $this['document']);

	}

	public function getDeliveryNote(\farm\Farm $eFarm): string {

		$this->expects(['document']);

		$code = $eFarm->getSelling('deliveryNotePrefix');

		return Configuration::getNumber($code, $this['document']);

	}

	public function acceptDelete(): bool {

		return (
			$this->acceptDeleteStatus() and
			$this->acceptDeletePaymentStatus() and
			$this->acceptDeleteMarket() and
			$this->acceptUpdateComposition()
		);

	}

	public function acceptDeleteMarket(): bool {

		$this->expects(['marketSales']);

		return ($this->isMarket() === FALSE or $this['marketSales'] === 0);

	}

	public function acceptDeleteMarketSale(): bool {

		if($this->isMarketSale()) {

			// On ne peut pas supprimer une vente qui contient des articles
			return Item::model()
				->whereSale($this)
				->exists() === FALSE;

		} else {
			return TRUE;
		}

	}

	public function acceptDeleteStatus(): bool {

		$this->expects(['preparationStatus']);

		return (
			in_array($this['preparationStatus'], $this->getDeleteStatuses()) and
			($this['preparationStatus'] !== Sale::BASKET or ($this['shopDate']->acceptOrder() === FALSE))
		);

	}

	public function getDeleteStatuses(): array {
		return [Sale::COMPOSITION, Sale::DRAFT, Sale::BASKET, Sale::CONFIRMED, Sale::CANCELED];
	}

	public function acceptDeletePaymentStatus() {
		return in_array($this['paymentStatus'], [NULL, Sale::NOT_PAID]);
	}

	public function checkMarketSelling() {

		if(
			$this->isMarketSelling() === FALSE and
			$this->isMarketClosed() === FALSE
		) {
			if($this->isMarket()) {
				throw new \RedirectAction(\selling\SaleUi::url($this).'?error=selling:Sale::canNotSell');
			} else {
				throw new \NotExpectedAction('Not a market sale');
			}
		}

	}

	public function getTaxes(): string {

		$this->expects(['hasVat', 'taxes']);

		if($this['hasVat']) {
			return SaleUi::p('taxes')->values[$this['taxes']];
		} else {
			return '';
		}

	}

	public function getTaxesFromType(): string {

		return match($this['type']) {
			Sale::PRO => Sale::EXCLUDING,
			Sale::PRIVATE => Sale::INCLUDING
		};

	}

	public function acceptStatusPrepared(): bool {

		return (
			$this->acceptUpdatePreparationStatus() and
			$this->isMarket() === FALSE and
			in_array($this['preparationStatus'], [Sale::CONFIRMED])
		);

	}

	public function acceptStatusDelivered(): bool {

		return (
			$this->acceptUpdatePreparationStatus() and
			in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::DRAFT] : [Sale::CONFIRMED, Sale::PREPARED])
		);

	}

	public function acceptStatusSelling(): bool {

		return (
			$this->isMarket() and
			in_array($this['preparationStatus'], [Sale::CONFIRMED, Sale::DELIVERED])
		);

	}

	public function acceptStatusConfirmed(): bool {

		if(in_array($this['preparationStatus'], $this->isMarketSale() ? [] : [Sale::BASKET, Sale::DRAFT, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING, Sale::CANCELED, Sale::EXPIRED]) === FALSE) {
			return FALSE;
		}

		if($this['invoice']->notEmpty()) {
			return FALSE;
		}

		if(
			$this['preparationStatus'] === Sale::BASKET and
			$this['shopDate']->acceptOrder() === TRUE
		) {
			return FALSE;
		}

		return TRUE;

	}

	public function acceptStatusCanceled(): bool {

		if(in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::DRAFT, Sale::DELIVERED] : [Sale::BASKET, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING]) === FALSE) {
			return FALSE;
		}

		if($this['invoice']->notEmpty()) {
			return FALSE;
		}

		return TRUE;

	}

	public function acceptStatusCanceledByCustomer(): bool {

		// Autres que les commandes en brouillon ou confirmées => NON
		if(
			in_array($this['preparationStatus'], [\selling\Sale::DRAFT, \selling\Sale::BASKET, \selling\Sale::CONFIRMED]) === FALSE
			or ($this['shop'] !== NULL and $this['shopDate']['orderEndAt'] <= date('Y-m-d H:i:s'))
		) {
			return FALSE;
		}

		// Pas de paiement enregistré => OUI
		if($this['paymentMethod']->empty()) {
			return TRUE;
		}

		// Non payé
		if($this['paymentStatus'] !== Sale::PAID) {
			return TRUE;
		}

		// Paiement en ligne
		if(
			$this['paymentMethod']['fqn'] === \payment\MethodLib::ONLINE_CARD
			and in_array($this['paymentStatus'], [NULL, Sale::NOT_PAID])
		) {
			return TRUE;
		}

		if($this['paymentMethod']['fqn'] === \payment\MethodLib::TRANSFER) {
			return TRUE;
		}

		return FALSE;

	}

	public function acceptStatusDraft(): bool {

		return (
			$this->acceptUpdatePreparationStatus() and
			in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING])
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$fw = new \FailWatch();

		$p
			->setCallback('customer.check', function(Customer $eCustomer) use($p): bool {

				$this->expects(['farm']);

				if($p->for === 'update') {

					if($eCustomer->empty()) {

						if($this->isMarketSale()) {
							return TRUE;
						} else {
							return FALSE;
						}

					}

				} else {

					if($eCustomer->empty()) {

						if($this->isComposition() === FALSE) {
							return FALSE;
						} else {

							$this['type'] = $this['compositionOf']['private'] ? Sale::PRIVATE : Sale::PRO;
							$this['taxes'] = $this->getTaxesFromType();
							$this['hasVat'] = $this['farm']->getSelling('hasVat');
							$this['discount'] = 0;

							return TRUE;

						}
					}

				}

				if(
					Customer::model()
						->select(['id', 'type', 'destination', 'discount', 'defaultPaymentMethod'])
						->whereFarm($this['farm'])
						->whereStatus(Customer::ACTIVE)
						->get($eCustomer)
				) {

					$this['type'] = $eCustomer['type'];
					$this['taxes'] = $this->getTaxesFromType();
					$this['hasVat'] = $this['farm']->getSelling('hasVat');
					$this['discount'] = $eCustomer['discount'];

					return TRUE;
				} else {
					return FALSE;
				}

			})
			->setCallback('customer.market', function(Customer $eCustomer) use($fw): bool {

				if($this->isComposition()) {
					return TRUE;
				}

				$this->expects(['origin']);

				if($fw->has('Sale::customer.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				if($this->isMarket()) {
					return ($eCustomer['destination'] === Customer::COLLECTIVE);
				} else {
					return TRUE;
				}

			})
			->setCallback('preparationStatus.check', function(string $preparationStatus): bool {

				$this->expects(['preparationStatus', 'deliveredAt', 'origin', 'marketParent']);

				if($this->acceptUpdatePreparationStatus() === FALSE) {
					return FALSE;
				}

				$this['oldPreparationStatus'] = $this['preparationStatus'];

				return match($preparationStatus) {
					Sale::DRAFT => in_array($this['oldPreparationStatus'], $this->isMarketSale() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]),
					Sale::CONFIRMED => $this->acceptStatusConfirmed(),
					Sale::SELLING => in_array($this['oldPreparationStatus'], $this->isMarket() ? [Sale::CONFIRMED, Sale::DELIVERED] : []),
					Sale::PREPARED => in_array($this['oldPreparationStatus'], $this->isMarketSale() ? [] : [Sale::CONFIRMED]),
					Sale::CANCELED => $this->acceptStatusCanceled(),
					Sale::DELIVERED => $this->acceptStatusDelivered(),
				};

			})
			->setCallback('preparationStatus.market', function(string $preparationStatus): bool {

				$this->expects(['farm', 'origin']);

				if($this->isMarket() === FALSE) {
					return TRUE;
				}

				// On vérifie qu'il n'y a pas de vente active pour autoriser à revenir en arrière dans le statut
				if(
					$this['oldPreparationStatus'] === Sale::SELLING and
					in_array($preparationStatus, [Sale::CANCELED, Sale::DRAFT, Sale::CONFIRMED])
				) {

					return Sale::model()
						->whereFarm($this['farm'])
						->wherePreparationStatus('IN', [Sale::DELIVERED, Sale::DRAFT])
						->whereMarketParent($this)
						->exists() === FALSE;

				}

				return TRUE;

			})
			->setCallback('deliveredAt.check', function(string &$date) use($p): bool {

				// La date est gérée directement dans la boutique
				if(
					$this->isComposition() === FALSE and
					$this['shopDate']->notEmpty()
				) {

					$this->expects([
						'shopDate' => ['deliveryDate']
					]);

					$date = $this['shopDate']['deliveryDate'];

					return TRUE;

				} else {

					if(
						($p->for === 'create') or
						($p->for === 'update' and $this->acceptUpdateDeliveredAt())
					) {

						return \Filter::check('date', $date);

					} else {
						return FALSE;
					}

				}

			})
			->setCallback('deliveredAt.composition', function(string $date) use($p): bool {

				if($this->isComposition() === FALSE) {
					return TRUE;
				} else {
					return Sale::model()
						->whereCompositionOf($this['compositionOf'])
						->whereDeliveredAt($date)
						->whereId('!=', $this, if: $p->for === 'update')
						->exists() === FALSE;
				}


			})
			->setCallback('deliveredAt.compositionTooLate', function(string $date) use($p): bool {

				if($this->isComposition() === FALSE) {
					return TRUE;
				} else {
					return Sale::testWriteComposition($date);
				}


			})
			->setCallback('shopDate.check', function(\shop\Date &$eDate): bool {

				if($eDate->notEmpty()) {

					$eDate = \shop\DateLib::getById($eDate);

					if($eDate->empty()) {
						return FALSE;
					} else {

						$eShop = \shop\ShopLib::getById($eDate['shop']);
						$this['shop'] = $eShop;
						$this['shopShared'] = $eShop['shared'];

						if($eShop['shared']) {
							return $eShop->canShareRead($this['farm']);
						} else {
							return $eShop->canWrite();
						}

					}

				} else {
					$this['shop'] = new \shop\Shop();
					$this['shopShared'] = FALSE;
					$this['shopDate'] = new \shop\Date();
					return TRUE;
				}

			})
			->setCallback('shopPointPermissive.check', function(mixed $point): bool {

				$this->expects(['farm']);

				if($point === '') {
					$this['shopPointPermissive'] = new \shop\Point();
					return TRUE;
				}

				$this['shopPointPermissive'] = \shop\Point::model()
					->select(\shop\Point::getSelection())
					->whereId($point)
					->whereFarm($this['farm'])
					->get();

				return $this['shopPointPermissive']->notEmpty();

			})
			->setCallback('shopPoint.check', function(\shop\Point &$ePoint): bool {

				$this->expects([
					'shop' => ['hasPoint'],
					'shopDate'
				]);

				if(
					$this['shop']['hasPoint'] === FALSE or
					$this['shopDate']['ccPoint']->empty()
				) {
					$ePoint = new \shop\Point();
					return TRUE;
				}

				$eDate = $this['shopDate'];

				if($eDate->empty()) {
					return $ePoint->empty();
				} else {

					$eDate->expects(['points']);

					if(
						$ePoint->notEmpty() and
						in_array($ePoint['id'], $eDate['points'])
					) {

						return \shop\Point::model()
							->select(\shop\Point::getSelection())
							->get($ePoint);

					} else {
						return FALSE;
					}

				}

			})
			->setCallback('shipping.check', function(mixed $shipping): bool {

				return (
					$shipping === NULL or
					\Filter::check(['float32', 'min' => 0.1], $shipping)
				);

			})
			->setCallback('shippingVatRate.check', function(?float $vat): bool {
				return (
					$vat === NULL or
					in_array($vat, \Setting::get('selling\vatRates'))
				);
			})
			->setCallback('preparationStatus.checkOutOfDraft', function(string $preparationStatus): bool {

				$this->expects(['preparationStatus', 'deliveredAt']);

				if($this['preparationStatus'] !== Sale::DRAFT) {
					return TRUE;
				}

				return ($this['deliveredAt'] !== NULL);

			})
			->setCallback('orderFormValidUntil.check', function(?string &$date): bool {

				return (
					$date === NULL or (
						\Filter::check('date', $date) and
						$date >= currentDate()
					)
				);

			})
			->setCallback('productsList.check', function(mixed $list) use($p): bool {

				if($p->isInvalid('customer')) {
					return TRUE;
				}

				$fw = new \FailWatch();

				$this['cItemCreate'] = \selling\ItemLib::build($this, $_POST, FALSE);

				if($fw->ko()) {
					Item::fail('createCollectionError');
				}

				return TRUE;

			})
			->setCallback('productsBasket.check', function(): bool {

				$products = POST('products', 'array');

				if($products === []) {
					return FALSE;
				}

				$this->expects([
					'shopDate' => ['cProduct'],
					'cItem'
				]);

				$warning = FALSE;
				$this['basket'] = \shop\BasketLib::checkAvailableProducts($products, $this['shopDate']['cProduct'], $this['cItem'], $warning);

				return ($this['basket'] !== [] and $warning === FALSE);

			})
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm']);

				$this['paymentMethodBuilt'] = TRUE;

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			})
			->setCallback('paymentStatus.check', function(?string &$status) use($p): bool {

				$p->expectsBuilt('paymentMethod');

				if($this['paymentMethod']->empty()) {
					$status = NULL;
					return TRUE;
				} else {
					return in_array($status, [Sale::PAID, Sale::NOT_PAID]);
				}

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
