<?php
namespace selling;

class Sale extends SaleElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'shop' => ['fqn', 'shared', 'name', 'email', 'emailNewSale', 'emailEndDate', 'approximate', 'paymentCard', 'hasPayment', 'paymentOfflineHow', 'paymentTransferHow', 'shipping', 'shippingUntil', 'orderMin', 'embedOnly', 'embedUrl', 'farm' => ['name', 'legalEmail']],
			'shopDate' => \shop\Date::getSelection(),
			'shopPoint' => ['type', 'name'],
			'farm' => ['name', 'siret', 'legalName', 'legalEmail', 'legalCity', 'legalCountry', 'url', 'vignette', 'emailBanner', 'emailFooter', 'hasSales', 'hasAccounting'],
			'price' => fn($e) => $e['type'] === Sale::PRO ? $e['priceExcludingVat'] : $e['priceIncludingVat'],
			'invoice' => ['number', 'emailedAt', 'paidAt', 'sales', 'date', 'content', 'createdAt', 'priceExcludingVat', 'generation', 'status'],
			'compositionOf' => ['name'],
			'marketParent' => [
				'customer' => ['type', 'name']
			],
			'paymentStatus',
			'cPayment' => PaymentLib::delegateBySale(),
		];

	}

	public static function validateBatch(\Collection $cSale): void {

		if($cSale->empty()) {
			throw new \FailAction('selling\Sale::sales.check');
		}

	}

	public function isSale(): bool {

		$this->expects(['profile']);

		return $this['profile'] === Sale::SALE;

	}

	public function isMarket(): bool {

		$this->expects(['profile']);

		return $this['profile'] === Sale::MARKET;

	}

	public function isMarketSale(): bool {

		$this->expects(['profile']);

		return $this['profile'] === Sale::SALE_MARKET;

	}

	public function hasSuccessfulPayment(): bool {

		$this->expects(['cPayment']);

		return $this['cPayment']->find(fn($ePayment) => $ePayment->isPaid(), limit: 1)->notEmpty();

	}

	public function isComposition(): bool {

		$this->expects(['profile']);

		return $this['profile'] === Sale::COMPOSITION;

	}

	public function canSendTicket(): bool {

		$this->expects(['profile', 'price', 'preparationStatus']);

		return (
			$this->isMarketSale() and
			$this['price'] > 0 and
			$this['preparationStatus'] === Sale::DELIVERED and
			$this['closed'] === FALSE
		);

	}

	public function getDeliveryAddress(string $type = 'text', \farm\Farm $eFarm = new \farm\Farm()): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this['deliveryStreet1']."\n";
		if($this['deliveryStreet2'] !== NULL) {
			$address .= $this['deliveryStreet2']."\n";
		}
		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		if(
			$this['deliveryCountry']->notEmpty() and
			($eFarm->empty() or $this['deliveryCountry']->is($eFarm['legalCountry']) === FALSE)
		) {
			$address .= "\n".\user\Country::ask($this['deliveryCountry'])['name'];
		}

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function getDeliveryAddressLink(): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		return 'https://www.google.fr/maps/place/'.str_replace('%0A', ',', urlencode($this->getDeliveryAddress())).'/';

	}

	public function hasDeliveryAddress(): bool {
		return ($this['deliveryCity'] !== NULL);
	}

	public function emptyDeliveryAddress(&$properties = []): void {

		$this->merge([
			'deliveryStreet1' => NULL,
			'deliveryStreet2' => NULL,
			'deliveryPostcode' => NULL,
			'deliveryCity' => NULL,
			'deliveryCountry' => NULL,
		]);

		$properties = array_merge($properties, ['deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry']);

	}

	public static function calculateDiscount(float $amount, int $discount): float {
		return round($amount * $discount / 100, 2);
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

			return (
				$this['shop']->canRead() and
				\shop\ShareLib::match($this['shop'], $this['farm'])
			);

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
			$this['closed'] === FALSE and
			$this['shopShared'] === FALSE and
			$this['invoice']->empty()
		);
	}

	public function canUpdateCustomer(): bool {

		return $this->canWrite();

	}

	public function acceptSecuring(): bool {

		return (
			$this['secured'] === FALSE and
			$this['type'] === Sale::PRIVATE
		);

	}

	public function acceptUpdateMarketSalePayment(): bool {

		$this->expects(['profile']);

		return (
			$this['closed'] === FALSE and
			$this->isMarketSale() and
			$this['preparationStatus'] === Sale::DRAFT
		);

	}

	public function acceptUpdatePayment(): bool {

		$this->expects(['cPayment', 'invoice']);

		return (
			$this->isMarket() === FALSE and
			$this->isComposition() === FALSE and
			$this['preparationStatus'] !== Sale::CANCELED and
			$this['closed'] === FALSE and
			$this['invoice']->empty()
		);

	}

	public function acceptUpdatePaymentStatus(): bool {

		return (
			$this->acceptUpdatePayment() and
			$this['cPayment']->notEmpty()
		);

	}

	public function acceptDocumentTarget(): bool {

		$this->expects(['type', 'farm']);

		$target = $this['farm']->getConf('documentTarget');

		return match($this['type']) {
			\selling\Sale::PRO => in_array($target, [\farm\Configuration::ALL, \farm\Configuration::PRO]),
			\selling\Sale::PRIVATE => in_array($target, [\farm\Configuration::ALL, \farm\Configuration::PRIVATE]),
		};

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
			return (
				$this['closed'] or
				in_array($this['preparationStatus'], [Sale::CANCELED, Sale::DELIVERED, Sale::BASKET])
			);
		}
	}

	public function isOpen(): bool {
		return $this->isLocked() === FALSE;
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
		return $date >= date('Y-m-d', strtotime('NOW - '.SellingSetting::COMPOSITION_LOCKED.' DAYS'));
	}

	public function acceptCreateItems(): bool {

		return $this->acceptUpdateItems();

	}

	public function acceptUpdateDeliveredAt(): bool {

		$this->expects(['preparationStatus', 'shop']);

		return (
			$this['preparationStatus'] !== Sale::DELIVERED and
			$this->acceptUpdatePreparationStatus() and
			$this->isMarketSale() === FALSE
		);

	}

	public function acceptUpdatePreparationStatus(): bool {

		return (
			$this['closed'] === FALSE and
			$this['invoice']->empty() and
			$this->isReadonly() === FALSE and
			$this->isComposition() === FALSE and
			(
				$this['shopDate']->empty() or
				$this['customer']['user']->is($this['createdBy']) === FALSE or
				$this['shopDate']->acceptSaleUpdatePreparationStatus()
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
		return $this->isSale();
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

	// Une vente est online seulement si le paiement lié est validé.
	public function isPaymentOnline(?string $onlineStatus = Payment::SUCCESS): bool {

		$this->expects(['cPayment']);

		if($this['cPayment']->empty()) {
			return FALSE;
		}

		foreach($this['cPayment'] as $ePayment) {
			if(
				$ePayment['method']->isOnline() and
				($onlineStatus === NULL or $ePayment['onlineStatus'] === $onlineStatus)
			) {
				return TRUE;
			}
		}

		return FALSE;

	}

	public function extractFirstValidPayment(): Payment {

		$this->expects(['cPayment']);

		if($this['cPayment']->empty()) {
			return new Payment();
		}

		foreach($this['cPayment'] as $ePayment) {
			if($ePayment->isPaid()) {
				return $ePayment;
			}
		}

		return new Payment();
	}

	public function isReadonly(): bool {

		return (
			(int)max(
				substr($this['statusAt'] ?? '0000-00-00', 0, 4),
				substr($this['deliveredAt'], 0, 4)
			) < (int)date('Y') - 1
		);

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

	public function acceptGenerateDocument(string $type): bool {

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->acceptGenerateDeliveryNote(),
			Pdf::ORDER_FORM => $this->acceptGenerateOrderForm(),
		};

	}

	public function acceptRegenerateDocument(string $type): bool {

		$this->expects(['invoice']);

		return match($type) {
			Pdf::DELIVERY_NOTE => $this->acceptGenerateDocument($type),
			Pdf::ORDER_FORM => $this->acceptGenerateDocument($type)
		};

	}

	public function acceptOrderForm(): bool {

		return
			$this->acceptCommonDocument() and
			$this['shop']->empty();

	}

	public function acceptGenerateOrderForm(): bool {

		return (
			$this->acceptOrderForm() and
			in_array($this['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED])
		);

	}

	public function acceptInvoice(): bool {

		return (
			$this['customer']->acceptInvoice() and
			$this->isSale()
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
			in_array($this['nature'], [Sale::MIXED, Sale::GOOD]) and
			$this->acceptCommonDocument()
		);

	}

	public function acceptCommonDocument(): bool {

		return (
			$this['customer']->notEmpty() and
			$this['customer']['destination'] !== Customer::COLLECTIVE and
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE and
			$this->acceptDocumentTarget()
		);

	}

	public function acceptGenerateDeliveryNote(): bool {

		return $this->acceptDeliveryNote() and in_array($this['preparationStatus'], [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED]);

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
			$this['shop']->notEmpty()
		);
	}

	public function acceptDuplicate(): bool {

		return (
			$this->isComposition() === FALSE and
			$this->isMarketSale() === FALSE
		);

	}

	public function getOrderForm(\farm\Farm $eFarm): string {

		$this->expects(['document']);

		return \farm\Configuration::getNumber(SellingSetting::ORDER_FORM, $this['document']);

	}

	public function getDeliveryNote(\farm\Farm $eFarm): string {

		$this->expects(['document']);

		return \farm\Configuration::getNumber(SellingSetting::DELIVERY_NOTE, $this['document']);

	}

	public function acceptDelete(): bool {

		return (
			$this['secured'] === FALSE and
			$this->acceptDeleteStatus() and
			$this->acceptDeletePaymentStatus() and
			$this->acceptUpdateComposition()
		);

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
		return [Sale::COMPOSITION, Sale::CONFIRMED, Sale::PREPARED, Sale::DRAFT, Sale::BASKET];
	}

	public function acceptDeletePaymentStatus() {
		return in_array($this['paymentStatus'], [NULL, Sale::NOT_PAID, Sale::NEVER_PAID]);
	}

	public function checkMarketSelling() {

		if(
			$this->isMarketSelling() === FALSE and
			$this->isMarketClosed() === FALSE
		) {
			if($this->isMarket()) {
				throw new \RedirectAction(\selling\SaleUi::url($this).'?error=selling\\Sale::canNotSell');
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
			$this['closed'] === FALSE and
			$this->acceptUpdatePreparationStatus() and
			$this->isMarket() === FALSE and
			$this->isMarketSale() === FALSE and
			in_array($this['preparationStatus'], [Sale::CONFIRMED, Sale::DRAFT])
		);

	}

	public function acceptStatusDelivered(): bool {

		return (
			$this['closed'] === FALSE and
			$this->acceptUpdatePreparationStatus() and
			$this->isMarket() === FALSE and
			in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::DRAFT] : [Sale::CONFIRMED, Sale::PREPARED, Sale::DRAFT])
		);

	}

	public function acceptStatusSelling(): bool {

		return (
			$this['closed'] === FALSE and
			$this->isMarket() and
			in_array($this['preparationStatus'], [Sale::CONFIRMED, Sale::DELIVERED])
		);

	}

	public function acceptStatusConfirmed(): bool {

		if(
			$this['closed'] or
			in_array($this['preparationStatus'], $this->isMarketSale() ? [] : [Sale::BASKET, Sale::DRAFT, Sale::PREPARED, Sale::DELIVERED, Sale::CANCELED, Sale::EXPIRED]) === FALSE
		) {
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

		if(
			$this['closed'] or
			in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::DRAFT, Sale::DELIVERED] : [Sale::BASKET, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::SELLING]) === FALSE
		) {
			return FALSE;
		}

		if($this['invoice']->notEmpty()) {
			return FALSE;
		}

		return TRUE;

	}

	public function acceptUpdatePaymentByCustomer(): bool {

		$this->expects([
			'shopDate' => ['deliveryDate']
		]);

		return (
			$this['preparationStatus'] === Sale::CONFIRMED and
			$this['paymentStatus'] !== Sale::PAID and
			$this['shop']['hasPayment'] and
			$this['shop']['shared'] === FALSE and
			$this['shopDate']['deliveryDate'] !== NULL
		);

	}

	public function acceptUpdateByCustomer(): bool {

		// Autres que les commandes en brouillon ou confirmées => NON
		if(
			in_array($this['preparationStatus'], [\selling\Sale::DRAFT, \selling\Sale::BASKET, \selling\Sale::CONFIRMED]) === FALSE	or
			(
				$this['shop']->notEmpty() !== NULL and
				$this['shopDate']->acceptCustomerCancel() === FALSE
			)
		) {
			return FALSE;
		}

		// Pas de paiement enregistré => OUI
		if($this['cPayment']->empty()) {
			return TRUE;
		}

		// Non payé
		if($this['paymentStatus'] !== Sale::PAID) {
			return TRUE;
		}

		// Paiement en ligne
		if(
			$this['cPayment']->find(fn($ePayment) => $ePayment['method']['fqn'] === \payment\MethodLib::ONLINE_CARD)->notEmpty()
			and in_array($this['paymentStatus'], [NULL, Sale::NOT_PAID])
		) {
			return TRUE;
		}

		if($this['cPayment']->find(fn($ePayment) => $ePayment['method']['fqn'] === \payment\MethodLib::TRANSFER)->notEmpty()) {
			return TRUE;
		}

		return FALSE;

	}

	public function acceptStatusDraft(): bool {

		return (
			$this->acceptUpdatePreparationStatus() and
			in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED])
		);

	}

	//-------- Accounting features -------

	public function acceptAccountingIgnore(): bool {
		return $this['accountingHash'] === NULL;
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
							$this['hasVat'] = $this['farm']->getConf('hasVat');
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
					$this['hasVat'] = $this['farm']->getConf('hasVat');
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

				$this->expects(['profile']);

				if($fw->has('Sale::customer.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				if($this->isMarket()) {
					return ($eCustomer['destination'] === Customer::COLLECTIVE);
				} else {
					return TRUE;
				}

			})
			->setCallback('preparationStatus.check', function(?string $preparationStatus) use ($p, $fw): bool {

				if($p->isInvalid('customer')) {
					return TRUE;
				}

				switch($p->for) {

					case 'create' :
						if($this['customer']['destination'] === Customer::COLLECTIVE) {
							return ($preparationStatus === NULL);
						} else {
							return in_array($preparationStatus, [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED]);
						}

					case 'update' :

						$this->expects(['preparationStatus', 'deliveredAt', 'profile', 'marketParent']);

						if($this->acceptUpdatePreparationStatus() === FALSE) {
							return FALSE;
						}

						return match($preparationStatus) {
							Sale::DRAFT => in_array($this['preparationStatus'], $this->isMarketSale() ? [Sale::CANCELED, Sale::DELIVERED] : [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING]),
							Sale::CONFIRMED => $this->acceptStatusConfirmed(),
							Sale::SELLING => in_array($this['preparationStatus'], $this->isMarket() ? [Sale::CONFIRMED, Sale::DELIVERED] : []),
							Sale::PREPARED => in_array($this['preparationStatus'], $this->isMarketSale() ? [] : [Sale::CONFIRMED]),
							Sale::CANCELED => $this->acceptStatusCanceled(),
							Sale::DELIVERED => $this->acceptStatusDelivered(),
							default => FALSE
						};

				}

			})
			->setCallback('deliveredAt.check', function(string &$date) use($p): bool {

				// Erreur dans la date de vente, on ignore cette vérification
				// Ce test ne pose pas de problème sur les ventes réalisées hors boutique
				if($p->isInvalid('shopDate')) {
					return TRUE;
				}

				// La date est gérée directement dans la boutique
				if(
					$this->isComposition() === FALSE and
					$this['shopDate']->notEmpty() and
					$this['shopDate']['deliveryDate'] !== NULL
				) {

					$this->expects([
						'shopDate' => ['deliveryDate']
					]);

					$date = $this['shopDate']['deliveryDate'];

					return TRUE;

				} else {

					if(
						$this->isComposition() or
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
			->setCallback('discount.prepare', function(mixed &$value): bool {
				if($value === NULL) {
					$value = 0;
				}
				return TRUE;
			})
			->setCallback('shopDate.check', function(\shop\Date &$eDate): bool {

				if($eDate->notEmpty()) {

					$eDate = \shop\DateLib::getById($eDate);

					if(
						$eDate->empty() or
						$eDate['type'] !== $this['type']
					) {
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
					in_array($vat, SellingSetting::getVatRates($this['farm']))
				);
			})
			->setCallback('orderFormValidUntil.check', function(?string &$date): bool {

				return (
					$date === NULL or (
						\Filter::check('date', $date) and
						$date >= currentDate()
					)
				);

			})
			->setCallback('deliveryNoteDate.check', function(?string &$date): bool {

				return \Filter::check('date', $date);

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
			->setCallback('paymentMethod.check', function(): bool {

				$this->expects(['customer', 'farm', 'priceIncludingVat']);

				$eMethod = \payment\MethodLib::getById(POST('method', 'int'));

				if($eMethod->empty()) {
					$this['cPayment'] = new \Collection();
					return TRUE;
				}

				if(\payment\MethodLib::isSelectable($this['farm'], $eMethod) === FALSE) {
					return FALSE;
				}

				$this['cPayment'] = new \Collection([new Payment([
					'sale' => $this,
					'customer' => $this['customer'],
					'farm' => $this['farm'],
					'checkoutId' => NULL,
					'method' => $eMethod,
					'amountIncludingVat' => $this['priceIncludingVat'],
					'onlineStatus' => NULL,
				])]);
				return TRUE;

			})
			->setCallback('paymentStatus.check', function(?string &$status) use($p): bool {

				if($p->isInvalid('paymentMethod')) {
					return TRUE;
				}

				$this->expects(['farm', 'cPayment']);

				if($this['cPayment']->empty()) {
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
