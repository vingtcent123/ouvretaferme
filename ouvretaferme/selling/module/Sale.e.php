<?php
namespace selling;

class Sale extends SaleElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => ['name', 'email', 'color', 'user', 'type', 'destination', 'discount', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity'],
			'shop' => ['fqn', 'name', 'email', 'paymentOfflineHow', 'paymentTransferHow', 'shipping', 'shippingUntil', 'orderMin'],
			'shopDate' => ['status', 'deliveryDate', 'orderStartAt', 'orderEndAt'],
			'shopPoint' => ['type', 'name'],
			'farm' => ['name', 'url', 'vignette', 'banner', 'featureDocument'],
			'invoice' => ['emailedAt', 'createdAt', 'paymentStatus', 'priceExcludingVat', 'generation'],
			'marketParent' => [
				'customer' => ['name']
			],
		];

	}

	public static function validateBatch(\Collection $cSale): void {

		if($cSale->empty()) {
			throw new \FailAction('selling\Sale::sales.check');
		} else {

			$eFarm = $cSale->first()['farm'];

			foreach($cSale as $eSale) {

				if($eSale['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

			}
		}

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

	public function copyAddressFromUser(\user\User $eUser): void {

		$this->merge([
			'deliveryStreet1' => $eUser['street1'],
			'deliveryStreet2' => $eUser['street2'],
			'deliveryPostcode' => $eUser['postcode'],
			'deliveryCity' => $eUser['city'],
		]);

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canManage(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canBasket(): bool {

		return (
			$this->empty() or (
				$this['shop']->notEmpty() and
				$this['paymentMethod'] === NULL
			)
		);

	}

	public function canUpdate(): bool {

		return (
			$this->canWrite() and
			$this['marketParent']->empty()
		);

	}

	public function canUpdateCustomer(): bool {
		return $this->canWrite();
	}

	public function canAccess(): bool {

		$this->expects(['customer', 'farm', 'marketParent']);

		return $this['marketParent']->empty() and (
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
		return in_array($this['preparationStatus'], [Sale::CANCELED, Sale::DELIVERED, Sale::BASKET]);
	}

	public function isOpen(): bool {
		return $this->isClosed() === FALSE;
	}

	public function canWriteItems(): bool {

		$this->expects(['preparationStatus']);

		return (
			in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]) and
			$this['marketParent']->empty()
		);

	}

	public function canWritePaymentMethod(): bool {

		return (
			$this['marketParent']->notEmpty() and
			$this['paymentMethod'] !== Sale::ONLINE_CARD
		);

	}

	public function canWriteDeliveredAt(): bool {

		$this->expects(['preparationStatus', 'from', 'marketParent']);

		return (
			$this['preparationStatus'] !== Sale::DELIVERED and
			$this['from'] === Sale::USER and
			$this['marketParent']->empty()
		);

	}

	public function canWritePreparationStatus(): bool {

		return $this->canWrite();

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

		$this->expects(['market', 'paymentMethod', 'invoice']);

		return (
			$this['market'] === FALSE and (
				$this['invoice']->notEmpty() or // Paiement par facturation
				$this->isPaymentOnline() // Ou paiement CB
			)
		);

	}

	public function isPaymentOnline(): bool {

		$this->expects(['paymentMethod']);

		return ($this['paymentMethod'] === Sale::ONLINE_CARD);

	}

	public function canCancelDelivered(): bool {

		$this->expects(['deliveredAt', 'invoice']);

		return (
			$this['invoice']->empty() and
			time() - strtotime($this['deliveredAt']) < 86400 * 45
		);

	}

	public function canAnyDocument(): bool {

		return (
			$this->canDeliveryNote() or
			$this->canOrderForm() or
			$this->canInvoice()
		);

	}

	public function canDocument(string $type): bool {

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->canDeliveryNote(),
			Pdf::ORDER_FORM => $this->canOrderForm(),
			Pdf::INVOICE => $this->canInvoice(),
		};

	}

	public function canGenerateDocument(string $type): bool {

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->canGenerateDeliveryNote(),
			Pdf::ORDER_FORM => $this->canGenerateOrderForm(),
			Pdf::INVOICE => $this->canGenerateInvoice()
		};

	}

	public function canRegenerateDocument(string $type): bool {

		$this->expects(['invoice']);

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->canGenerateDocument($type),
			Pdf::ORDER_FORM => $this->canGenerateDocument($type),
			Pdf::INVOICE => $this['invoice']->notEmpty()
		};

	}

	public function canOrderForm(): bool {

		return (
			$this['customer']->notEmpty() and
			$this['customer']['destination'] !== Customer::COLLECTIVE and
			$this['market'] === FALSE and
			$this['marketParent']->empty() and
			$this['shop']->empty() and
			$this['farm']->hasFeatureDocument($this['type'])
		);

	}

	public function canGenerateOrderForm(): bool {

		return $this->canOrderForm() and in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED]);

	}

	public function canInvoice(): bool {

		return (
			$this['customer']->notEmpty() and
			$this['customer']['destination'] !== Customer::COLLECTIVE and
			$this['market'] === FALSE and
			$this['marketParent']->empty()
		);

	}

	public function canGenerateInvoice(): bool {
		return $this->canInvoice() and (
			$this['invoice']->empty() and
			$this['preparationStatus'] === Sale::DELIVERED
		);
	}

	public function canDeliveryNote(): bool {

		return $this->canOrderForm();

	}

	public function canGenerateDeliveryNote(): bool {

		return $this->canDeliveryNote() and ($this['preparationStatus'] === Sale::DELIVERED);

	}

	public function canAssociateShop(): bool {
		return (
			$this['type'] === Sale::PRIVATE and
			$this['market'] === FALSE and
			$this['marketParent']->empty() and
			$this['shop']->empty()
		);
	}

	public function canDissociateShop(): bool {
		return $this['shop']->notEmpty();
	}

	public function canCustomerCancel(): bool {

		return (
			// Seulement certains types de paiement
			(
				in_array($this['paymentMethod'], [NULL, Sale::OFFLINE, Sale::TRANSFER]) or
				($this['paymentMethod'] === Sale::ONLINE_CARD and in_array($this['paymentStatus'], [Sale::UNDEFINED, Sale::FAILED]))
			) and
			// Seulement pour les commandes en brouillon ou confirmées
			in_array($this['preparationStatus'], [\selling\Sale::DRAFT, \selling\Sale::BASKET, \selling\Sale::CONFIRMED]) and
			($this['shop'] === NULL or $this['shopDate']['orderEndAt'] > date('Y-m-d H:i:s'))
		);

	}

	public function canDuplicate(): bool {

		return (
			$this['from'] === self::USER and
			$this['marketParent']->empty()
		);

	}

	public function canRemote(): bool {
		return $this->canRead() or GET('key') === \Setting::get('selling\remoteKey');
	}

	public function getNumber(): string {
		return $this['document'];
	}

	public function getOrderForm(): string {
		$this->expects(['document']);
		return 'DE'.$this['document'];
	}

	public function getDeliveryNote(): string {
		$this->expects(['document']);
		return 'BL'.$this['document'];
	}

	public function canDeleteSale(): bool {

		return (
			$this->canDeleteStatus() and
			$this->canDeletePaymentStatus() and
			$this->canDeleteMarket()
		);

	}

	public function canDeleteMarket(): bool {

		$this->expects(['market', 'marketSales']);

		return ($this['market'] === FALSE or $this['marketSales'] === 0);

	}

	public function canDeleteMarketSale(): bool {

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

	public function canDeleteStatus(): bool {

		$this->expects(['preparationStatus', 'marketParent']);

		return (
			in_array($this['preparationStatus'], $this->getDeleteStatuses()) and
			($this['preparationStatus'] !== Sale::BASKET or ($this['shopDate']->canOrder() === FALSE))
		);

	}

	public function getDeleteStatuses(): array {
		return [Sale::DRAFT, Sale::BASKET, Sale::CONFIRMED, Sale::CANCELED];
	}

	public function canDeletePaymentStatus() {
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

	public function canStatusDelivered(): bool {

		return in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [Sale::DRAFT] : [Sale::CONFIRMED, Sale::PREPARED]);

	}

	public function canStatusConfirmed(): bool {

		if(in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [] : [Sale::BASKET, Sale::DRAFT, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING, Sale::CANCELED]) === FALSE) {
			return FALSE;
		}

		if($this['invoice']->notEmpty()) {
			return FALSE;
		}

		if(
			$this['preparationStatus'] === Sale::BASKET and
			$this['shopDate']->canOrder() === FALSE
		) {
			return FALSE;
		}

		return TRUE;

	}

	public function canStatusCancel(): bool {

		if(in_array($this['preparationStatus'], $this['marketParent']->notEmpty() ? [Sale::DRAFT, Sale::DELIVERED] : [Sale::BASKET, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING]) === FALSE) {
			return FALSE;
		}

		if($this['invoice']->notEmpty()) {
			return FALSE;
		}

		if(
			$this['preparationStatus'] === Sale::BASKET and
			$this['shopDate']->canOrder() === FALSE
		) {
			return FALSE;
		}

		return TRUE;

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$fw = new \FailWatch();

		return parent::build($properties, $input, $callbacks + [

			'customer.check' => function(Customer $eCustomer): bool {

				$this->expects(['farm']);

				if(
					$eCustomer->notEmpty() and
					Customer::model()
						->select(['id', 'type', 'destination', 'discount'])
						->whereFarm($this['farm'])
						->whereStatus(Customer::ACTIVE)
						->get($eCustomer)
				) {
					$this['type'] = $eCustomer['type'];
					$this['discount'] = $eCustomer['discount'];
					return TRUE;
				} else {
					return FALSE;
				}

			},

			'customer.market' => function(Customer $eCustomer) use ($fw): bool {

				$this->expects(['market']);

				if($fw->has('Sale::customer.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				if($this['market']) {
					return ($eCustomer['destination'] === Customer::COLLECTIVE);
				} else {
					return TRUE;
				}

			},

			'paymentMethod.check' => function(?string $paymentMethod): bool {

				if($this->canWritePaymentMethod() === FALSE) {
					return FALSE;
				}

				return (
					$paymentMethod === NULL or
					in_array($paymentMethod, [Sale::CARD, Sale::CHECK, Sale::CASH, Sale::TRANSFER])
				);

			},

			'preparationStatus.check' => function(string $preparationStatus): bool {

				$this->expects(['preparationStatus', 'deliveredAt', 'market', 'marketParent']);

				if($this->canWritePreparationStatus() === FALSE) {
					return FALSE;
				}

				$this['oldStatus'] = $this['preparationStatus'];

				return match($preparationStatus) {
					Sale::DRAFT => in_array($this['oldStatus'], $this['marketParent']->notEmpty() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]),
					Sale::CONFIRMED => $this->canStatusConfirmed(),
					Sale::SELLING => in_array($this['oldStatus'], $this['market'] ? [Sale::CONFIRMED, Sale::DELIVERED] : []),
					Sale::PREPARED => in_array($this['oldStatus'], $this['marketParent']->notEmpty() ? [] : [Sale::CONFIRMED]),
					Sale::CANCELED => $this->canStatusCancel(),
					Sale::DELIVERED => $this->canStatusDelivered(),
				};

				if($test === FALSE) {
					return FALSE;
				}

				return TRUE;

			},

			'preparationStatus.market' => function(string $preparationStatus): bool {

				$this->expects(['farm', 'market']);

				if($this['market'] === FALSE) {
					return TRUE;
				}

				// On vérifie qu'il n'y a pas de vente active pour autoriser à revenir en arrière dans le statut
				if(
					$this['oldStatus'] === Sale::SELLING and
					in_array($preparationStatus, [Sale::CANCELED, Sale::DRAFT, Sale::CONFIRMED])
				) {

					return Sale::model()
						->whereFarm($this['farm'])
						->wherePreparationStatus('IN', [Sale::DELIVERED, Sale::DRAFT])
						->whereMarketParent($this)
						->exists() === FALSE;

				}

				return TRUE;

			},

			'deliveredAt.check' => function(?string &$date) use ($for): bool {

				try {
					$this->expects(['from']);
				}
				catch(\Exception) {
					return FALSE;
				}

				if($this['from'] === Sale::SHOP) { // La date est gérée directement dans la boutique

					$this->expects([
						'shopDate' => ['deliveryDate']
					]);

					$date = $this['shopDate']['deliveryDate'];

					return TRUE;

				} else {
					if(
						($for === 'create') or
						($for === 'update' and $this->canWriteDeliveredAt())
					) {
						return \Filter::check('date', $date);
					} else {
						return FALSE;
					}
				}

			},

			'shopDate.check' => function(\shop\Date &$eDate): bool {

				try {
					$this->expects(['from']);
				}
				catch(\Exception) {
					return FALSE;
				}

				if($this['from'] === Sale::SHOP) {

					$eDate = \shop\DateLib::getById($eDate);

					if($eDate->empty()) {
						return FALSE;
					} else {

						$eShop = \shop\ShopLib::getById($eDate['shop']);
						$this['shop'] = $eShop;

						return $eShop->canWrite();

					}

				} else {
					$this['shop'] = new \shop\Shop();
					$this['shopDate'] = new \shop\Date();
					return TRUE;
				}

			},

			'shopPoint.check' => function(\shop\Point $ePoint): bool {

				$this->expects(['shopDate']);

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

			},

			'shipping.check' => function(mixed $shipping): bool {

				return (
					$shipping === NULL or
					\Filter::check(['float32', 'min' => 0.1], $shipping)
				);

			},

			'shippingVatRate.check' => function(?float $vat): bool {
				return (
					$vat === NULL or
					in_array($vat, \Setting::get('selling\vatRates'))
				);
			},

			'preparationStatus.checkOutOfDraft' => function(string $preparationStatus): bool {

				$this->expects(['preparationStatus', 'deliveredAt']);

				if($this['preparationStatus'] !== Sale::DRAFT) {
					return TRUE;
				}

				return ($this['deliveredAt'] !== NULL);

			},

			'orderFormValidUntil.check' => function(?string &$date): bool {

				return (
					$date === NULL or (
						\Filter::check('date', $date) and
						$date >= currentDate()
					)
				);

			},

			'products.check' => function(mixed $products): bool {

				if(is_array($products) === FALSE) {
					return FALSE;
				}

				$this->expects(['shopDate']);

				$this['basket'] = \shop\BasketLib::checkProductsAndStock($products, $this['shopDate']);

				return ($this['basket'] !== []);


			}

		]);

	}

}
?>