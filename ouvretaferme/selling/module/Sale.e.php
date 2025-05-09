<?php
namespace selling;

class Sale extends SaleElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => ['name', 'email', 'phone', 'color', 'user', 'type', 'destination', 'discount', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceRegistration', 'invoiceVat'],
			'shop' => ['fqn', 'shared', 'name', 'email', 'emailNewSale', 'emailEndDate', 'approximate', 'paymentCard', 'hasPayment', 'paymentOfflineHow', 'paymentTransferHow', 'shipping', 'shippingUntil', 'orderMin', 'embedOnly', 'embedUrl'],
			'shopDate' => \shop\Date::getSelection(),
			'shopPoint' => ['type', 'name'],
			'farm' => ['name', 'url', 'vignette', 'banner', 'featureDocument', 'hasSales'],
			'price' => fn($e) => $e['type'] === Sale::PRO ? $e['priceExcludingVat'] : $e['priceIncludingVat'],
			'invoice' => ['name', 'emailedAt', 'createdAt', 'paymentStatus', 'priceExcludingVat', 'generation'],
			'compositionOf' => ['name'],
			'marketParent' => [
				'customer' => ['type', 'name']
			],
			'cPayment' => Payment::model()
				->select(Payment::getSelection() + ['method' => ['id', 'fqn', 'name', 'status']])
				->delegateCollection('sale', 'id', function(\Collection $cPayment) {
					foreach([Payment::SUCCESS, Payment::PENDING, Payment::FAILURE] as $status) {
						$hasStatus = $cPayment->contains(fn($ePayment) => $ePayment['status'] === $status);
						if($hasStatus) {
							return $cPayment->filter(fn($ePayment) => $ePayment['status'] === $status);
						}
					}
					return new \Collection();
				}),
		];

	}

	public static function validateBatch(\Collection $cSale): void {

		if($cSale->empty()) {
			throw new \FailAction('selling\Sale::sales.check');
		}

	}

	public function isComposition(): bool {

		$this->expects(['compositionOf']);

		return $this['compositionOf']->notEmpty();

	}

	public function getDeliveryAddress(string $separator = "\n"): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this['deliveryStreet1'].$separator;
		if($this['deliveryStreet2'] !== NULL) {
			$address .= $this['deliveryStreet2'].$separator;
		}
		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		return $address;

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

	public function copyAddressFromUser(\user\User $eUser, &$properties = []): void {

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

		$this->expects(['marketParent', 'preparationStatus']);

		return (
			$this->canWrite() and
			$this['marketParent']->empty()
		);

	}

	public function canUpdatePreparationStatus(): bool {

		return $this->canWrite();

	}

	public function acceptUpdateCustomer(): bool {
		return (
			$this['compositionOf']->empty() and
			$this['shopShared'] === FALSE
		);
	}

	public function canUpdateCustomer(): bool {

		return $this->canWrite();

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
			$this['market'] and
			in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED])
		);

	}

	public function isMarket(): bool {

		return $this['market'];

	}

	public function isMarketSelling(): bool {

		return (
			$this['market'] and
			in_array($this['preparationStatus'], [Sale::SELLING])
		);

	}

	public function isMarketClosed(): bool {

		return (
			$this['market'] and
			$this->isClosed()
		);

	}

	public function isClosed(): bool {
		if($this->isComposition()) {
			return $this->acceptWriteComposition() === FALSE;
		} else {
			return in_array($this['preparationStatus'], [Sale::CANCELED, Sale::DELIVERED, Sale::BASKET]);
		}
	}

	public function isOpen(): bool {
		return $this->isClosed() === FALSE;
	}

	public function acceptUpdateItems(): bool {

		$this->expects(['market', 'marketParent', 'preparationStatus', 'deliveredAt']);

		if($this['marketParent']->notEmpty()) {
			return FALSE;
		}

		if(in_array($this['preparationStatus'], [Sale::COMPOSITION, Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]) === FALSE) {
			return FALSE;
		}

		if($this->isComposition()) {
			return $this->acceptWriteComposition();
		} else {
			return TRUE;
		}

	}

	public function acceptWriteComposition(): bool {

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

	public function acceptWritePaymentMethod(): bool {

		return (
			$this['market'] === FALSE and
			$this['preparationStatus'] !== Sale::CANCELED
		);

	}

	public function acceptWriteDeliveredAt(): bool {

		$this->expects(['preparationStatus', 'from', 'marketParent']);

		return (
			$this['preparationStatus'] !== Sale::DELIVERED and
			$this['from'] === Sale::USER and
			$this['marketParent']->empty()
		);

	}

	public function acceptWritePreparationStatus(): bool {

		return (
			$this['compositionOf']->empty() and
			(
				$this['shopDate']->empty() or
				$this['shopDate']['isOrderable'] === FALSE
			)
		);

	}

	public function hasDiscount(): bool {

		$this->expects(['market', 'marketParent']);

		return (
			$this['market'] === FALSE and
			$this['marketParent']->empty()
		);

	}

	public function hasShipping(): bool {

		$this->expects(['market', 'marketParent']);

		return (
			$this['market'] === FALSE and
			$this['marketParent']->empty()
		);

	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function hasPaymentStatus(): bool {

		$this->expects(['market', 'invoice']);

		return (
			$this['market'] === FALSE and (
				$this['invoice']->notEmpty() or // Paiement par facturation
				$this->isPaymentOnline() // Ou paiement CB
			)
		);

	}

	public function isPaymentOnline(): bool {

		$this->expects(['cPayment']);

		if($this['cPayment']->empty()) {
			return FALSE;
		}

		$ePayment = $this['cPayment']->first();

		if($ePayment['method']->exists() === FALSE) {
			return FALSE;
		}

		$ePayment->expects(['method' => ['fqn']]);

		return ($ePayment['method']['fqn'] === \payment\MethodLib::ONLINE_CARD);

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
			$this['market'] === FALSE and
			$this['marketParent']->empty()
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
			$this['market'] === FALSE and
			$this['marketParent']->empty() and
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
			$this['marketParent']->empty() and
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
			$this['from'] === self::USER and
			$this['compositionOf']->empty() and
			$this['marketParent']->empty()
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
			$this->acceptWriteComposition()
		);

	}

	public function acceptDeleteMarket(): bool {

		$this->expects(['market', 'marketSales']);

		return ($this['market'] === FALSE or $this['marketSales'] === 0);

	}

	public function acceptDeleteMarketSale(): bool {

		$this->expects(['marketParent']);

		if($this['marketParent']->notEmpty()) {

			// On ne peut pas supprimer une vente qui contient des articles
			return Item::model()
				->whereSale($this)
				->exists() === FALSE;

		} else {
			return TRUE;
		}

	}

	public function acceptDeleteStatus(): bool {

		$this->expects(['preparationStatus', 'marketParent']);

		return (
			in_array($this['preparationStatus'], $this->getDeleteStatuses()) and
			($this['preparationStatus'] !== Sale::BASKET or ($this['shopDate']->acceptOrder() === FALSE))
		);

	}

	public function getDeleteStatuses(): array {
		return [Sale::COMPOSITION, Sale::DRAFT, Sale::BASKET, Sale::CONFIRMED, Sale::CANCELED];
	}

	public function acceptDeletePaymentStatus() {
		$this->expects(['paymentStatus']);
		return in_array($this['paymentStatus'], $this->getDeletePaymentStatuses());
	}

	public function getDeletePaymentStatuses() {
		return [Sale::UNDEFINED];
	}

	public function checkMarketSelling() {

		if(
			$this->isMarketSelling() === FALSE and
			$this->isMarketClosed() === FALSE
		) {
			if($this['market']) {
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
			$this->acceptWritePreparationStatus() and
			$this['market'] === FALSE and
			in_array($this['preparationStatus'], [Sale::CONFIRMED])
		);

	}

	public function acceptStatusDelivered(): bool {

		return (
			$this->acceptWritePreparationStatus() and
			in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [Sale::DRAFT] : [Sale::CONFIRMED, Sale::PREPARED])
		);

	}

	public function acceptStatusSelling(): bool {

		return (
			$this['market'] and
			$this['preparationStatus'] === Sale::CONFIRMED
		);

	}

	public function acceptStatusConfirmed(): bool {

		if(in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [] : [Sale::BASKET, Sale::DRAFT, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING, Sale::CANCELED]) === FALSE) {
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

		if(in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [Sale::DRAFT, Sale::DELIVERED] : [Sale::BASKET, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING]) === FALSE) {
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
		if($this['cPayment']->count() === 0) {
			return TRUE;
		}

		// Plus d'un seul paiement => NON
		if($this['cPayment']->count() > 1) {
			return FALSE;
		}

		$ePayment = $this['cPayment']->first();
		if($ePayment['method']->exists() === FALSE) {
			return TRUE;
		}
		if($ePayment['method']['fqn'] === \payment\MethodLib::TRANSFER) {
			return TRUE;
		}

		if(
			$ePayment['method']['fqn'] === \payment\MethodLib::ONLINE_CARD
			and in_array($this['paymentStatus'], [Sale::UNDEFINED, Sale::FAILED])
		) {
			return TRUE;
		}

		return FALSE;

	}

	public function acceptStatusDraft(): bool {

		return (
			$this->acceptWritePreparationStatus() and
			in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING])
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$fw = new \FailWatch();

		$p
			->setCallback('market.prepare', function(bool &$market): bool {

				if($this['compositionOf']->notEmpty()) {
					$this['market'] = FALSE;
				}

				return TRUE;

			})
			->setCallback('customer.check', function(Customer $eCustomer) use($p): bool {

				$this->expects(['farm']);

				if($p->for === 'update') {

					$this->expects(['marketParent']);

					if($eCustomer->empty()) {

						if($this['marketParent']->notEmpty()) {
							return TRUE;
						} else {
							return FALSE;
						}

					}

				} else {

					if($eCustomer->empty()) {

						if($this['compositionOf']->empty()) {
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
						->select(['id', 'type', 'destination', 'discount'])
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

				if($this['compositionOf']->notEmpty()) {
					return TRUE;
				}

				$this->expects(['market']);

				if($fw->has('Sale::customer.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				if($this['market']) {
					return ($eCustomer['destination'] === Customer::COLLECTIVE);
				} else {
					return TRUE;
				}

			})
			->setCallback('preparationStatus.check', function(string $preparationStatus): bool {

				$this->expects(['preparationStatus', 'deliveredAt', 'market', 'marketParent']);

				if($this->acceptWritePreparationStatus() === FALSE) {
					return FALSE;
				}

				$this['oldPreparationStatus'] = $this['preparationStatus'];

				return match($preparationStatus) {
					Sale::DRAFT => in_array($this['oldPreparationStatus'], $this['marketParent']->notEmpty() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]),
					Sale::CONFIRMED => $this->acceptStatusConfirmed(),
					Sale::SELLING => in_array($this['oldPreparationStatus'], $this['market'] ? [Sale::CONFIRMED, Sale::DELIVERED] : []),
					Sale::PREPARED => in_array($this['oldPreparationStatus'], $this['marketParent']->notEmpty() ? [] : [Sale::CONFIRMED]),
					Sale::CANCELED => $this->acceptStatusCanceled(),
					Sale::DELIVERED => $this->acceptStatusDelivered(),
				};

				if($test === FALSE) {
					return FALSE;
				}

				return TRUE;

			})
			->setCallback('preparationStatus.market', function(string $preparationStatus): bool {

				$this->expects(['farm', 'market']);

				if($this['market'] === FALSE) {
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

				try {
					$this->expects(['from']);
				}
				catch(\Exception) {
					return FALSE;
				}

				// La date est gérée directement dans la boutique
				if(
					$this['compositionOf']->empty() and
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
						($p->for === 'update' and $this->acceptWriteDeliveredAt())
					) {

						return \Filter::check('date', $date);

					} else {
						return FALSE;
					}

				}

			})
			->setCallback('deliveredAt.composition', function(string $date) use($p): bool {

				if($this['compositionOf']->empty()) {
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

				if($this['compositionOf']->empty()) {
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

						return $eShop->canWrite();

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


			});
		
		parent::build($properties, $input, $p);

	}

}
?>
