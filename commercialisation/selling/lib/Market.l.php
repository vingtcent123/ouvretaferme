<?php
namespace selling;

class MarketLib {

	public static function getLast(Sale $eSale): array {

		$eSale->expects(['farm', 'customer']);

		$query = fn($comparator, $sort, $number): \Collection => Sale::model()
			->select([
				'id',
				'priceIncludingVat',
				'marketSales',
				'hasVat', 'taxes',
				'deliveredAt'
			])
			->whereProfile(Sale::MARKET)
			->wherePriceIncludingVat('!=', NULL)
			->wherePreparationStatus('IN', [Sale::DELIVERED, Sale::SELLING])
			->whereDeliveredAt($comparator, $eSale['deliveredAt'])
			->whereFarm($eSale['farm'])
			->whereCustomer($eSale['customer'])
			->sort(['deliveredAt' => $sort])
			->getCollection(0, $number);

		$cSaleAfter = $query('>', SORT_ASC, 2);
		$cSaleBefore = $query('<', SORT_DESC, 4 - $cSaleAfter->count());

		return [
			$cSaleAfter->reverse(),
			$cSaleBefore,
		];

	}

	public static function getItemStats(\Collection $cSale): \Collection {

		return Item::model()
			->select([
				'product',
				'sales' => new \Sql('COUNT(DISTINCT sale)', 'int'),
				'last' => new \Sql('MAX(createdAt)')
			])
			->whereSale('IN', $cSale)
			->whereIngredientOf(NULL)
			->group('product')
			->getCollection(index: 'product');

	}

	public static function getByHour(Sale $eSale): array {

		$eSale->expects(['farm', 'customer']);

		$cSale = Sale::model()
			->select([
				'hour' => new \Sql('SUBSTRING(createdAt, 1, 13)'),
				'sales' => new \Sql('COUNT(*)', 'int'),
				'turnover' => new \Sql('SUM(priceIncludingVat)', 'float')
			])
			->whereMarketParent($eSale)
			->wherePreparationStatus(Sale::DELIVERED)
			->group('hour')
			->sort(['hour' => SORT_ASC])
			->getCollection(index: 'hour');

		if($cSale->empty()) {
			return [];
		}

		$firstHour = $cSale->first()['hour'];
		$lastHour = $cSale->last()['hour'];

		$values = [];

		for($hour = $firstHour; $hour <= $lastHour; $hour = date('Y-m-d H', strtotime($hour.':00:00 + 1 HOUR'))) {

			$hourDigit = substr($hour, 11, 2);

			$values[$hour] = $cSale->offsetExists($hour) ? [
				'hour' => $hourDigit,
				'sales' => $cSale[$hour]['sales'],
				'turnover' => $cSale[$hour]['turnover']
			] : [
				'hour' => $hourDigit,
				'sales' => 0,
				'turnover' => 0
			];

		}

		return $values;

	}

	public static function checkNewPrices(Sale $eSale, array $post): \Collection {

		if($eSale->isMarket() === FALSE) {
			throw new \NotExpectedAction('Not a market');
		}

		$cItem = new \Collection();

		foreach($post as $id => $newPrice) {

			if($newPrice === '') {
				continue;
			}

			if(Item::model()->check('unitPrice', $newPrice) === FALSE) {
				Item::model()->rollBack();
				throw new \NotExpectedAction('Invalid price');
			}

			$cItem[] = new Item([
				'id' => (int)$id,
				'unitPrice' => (float)$newPrice
			]);

		}

		return $cItem;

	}

	public static function updateMarketPrices(Sale $eSale, \Collection $cItem): void {

		Item::model()->beginTransaction();

		foreach($cItem as $eItem) {

			// On modifie le prix unitaire sans se soucier de la consistance avec les quantités et le montant total
			// En effet les prix de vente peuvent ne pas être homogènes entre les clients
			Item::model()
				->select('unitPrice')
				->whereSale($eSale)
				->update($eItem);

		}


		Item::model()->commit();

	}

	public static function updateSaleMarket(Sale $eSaleMarket): void {

		Item::model()->beginTransaction();

		Sale::model()
			->select(Sale::getSelection())
			->get($eSaleMarket);

		$cItemMarket = SaleLib::getItems($eSaleMarket);

		$cItemSaled = Item::model()
			->select([
				'parent',
				'totalNumber' => new \Sql('SUM(number)', 'float'),
				'totalPrice' => new \Sql('SUM(price)', 'float'),
				'totalPriceStats' => new \Sql('SUM(priceStats)', 'float'),
			])
			->whereParent('IN', $cItemMarket)
			->whereStatus(Sale::DELIVERED)
			->group('parent')
			->getCollection(NULL, NULL, 'parent');

		foreach($cItemMarket as $eItemMarket) {

			if($cItemSaled->offsetExists($eItemMarket['id'])) {

				$eItemSaled = $cItemSaled[$eItemMarket['id']];

				$eItemMarket->merge([
					'number' => round($eItemSaled['totalNumber'], 2),
					'price' => round($eItemSaled['totalPrice'], 2),
					'priceStats' => round($eItemSaled['totalPriceStats'], 2)
				]);

			} else {
				$eItemMarket->merge([
					'number' => 0.0,
					'price' => 0.0,
					'priceStats' => 0.0
				]);
			}

			Item::model()
				->select('number', 'price', 'priceStats')
				->update($eItemMarket);

			if($eItemMarket['productComposition']) {
				ItemLib::updateIngredients($eItemMarket);
			}

		}

		Sale::model()->update($eSaleMarket, [
			'marketSales' => Sale::model()
				->whereMarketParent($eSaleMarket)
				->wherePreparationStatus(Sale::DELIVERED)
				->count()
		]);

		SaleLib::recalculate($eSaleMarket);

		Item::model()->commit();

	}

	public static function close(Sale $eSale): void {

		if($eSale['preparationStatus'] !== Sale::SELLING) {
			return;
		}

		Sale::model()->beginTransaction();

			$eSale['oldPreparationStatus'] = Sale::SELLING;
			$eSale['preparationStatus'] = Sale::DELIVERED;
			$eSale['closed'] = TRUE;

			SaleLib::update($eSale, ['preparationStatus', 'closed']);

			$cSaleMarket = Sale::model()
				->select(SaleElement::getSelection())
				->whereFarm($eSale['farm'])
				->whereMarketParent($eSale)
				->wherePreparationStatus(Sale::DELIVERED)
				->getCollection();

			foreach($cSaleMarket as $eSaleMarket) {

				$eSaleMarket['closed'] = TRUE;

				SaleLib::update($eSaleMarket, ['closed']);

			}

		Sale::model()->commit();

	}

	public static function doPaidMarketSale(Sale $eSale): void {

		Sale::model()->beginTransaction();

			$eSale['cPayment'] = PaymentLib::getBySale($eSale);

			if($eSale['cPayment']->empty()) {

				Sale::model()->rollBack();
				\Fail::log('selling\Market::emptyPayment');
				return;

			} else if(self::hasPaymentConsistency($eSale) === FALSE) {

				Sale::model()->rollBack();
				\Fail::log('selling\Market::inconsistencyTotal');
				return;

			}

			self::doPaymentStatusMarketSale($eSale, Sale::PAID);

		Sale::model()->commit();

	}

	public static function doNotPaidMarketSale(Sale $eSale): void {

		Sale::model()->beginTransaction();

			$eSale['cPayment'] = PaymentLib::getBySale($eSale);

			if(
				$eSale['cPayment']->notEmpty() and
				self::hasPaymentConsistency($eSale) === FALSE
			) {
				Sale::model()->rollBack();
				\Fail::log('selling\Market::inconsistencyTotal');
				return;
			}

			// On sort la vente du logiciel de caisse
			$eSale['profile'] = Sale::SALE;
			$eSale['marketParent'] = new Sale();
			$eSale['stats'] = TRUE;

			Sale::model()
				->select('profile', 'marketParent', 'stats')
				->update($eSale);

			Item::model()
				->whereSale($eSale)
				->update([
					'parent' => new Item(),
					'stats' => TRUE
				]);

			self::doPaymentStatusMarketSale($eSale, $eSale['cPayment']->notEmpty() ? Sale::NOT_PAID : NULL);

		Sale::model()->commit();

	}

	public static function hasPaymentConsistency(Sale $eSale): bool {

		$totalPayments = $eSale['cPayment']->reduce(fn($e, $v) => $v + $e['amountIncludingVat'], 0);

		return ($totalPayments === $eSale['priceIncludingVat']);

	}

	public static function doPaymentStatusMarketSale(Sale $eSale, ?string $paymentStatus): void {

		// Supprime les moyens de paiement vide / à 0€
		PaymentLib::cleanBySale($eSale);

		$properties = ['preparationStatus', 'paymentStatus'];

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = Sale::DELIVERED;
		$eSale['paymentStatus'] = $paymentStatus;

		SaleLib::update($eSale, $properties);

	}


	public static function sendTicket(Sale $eSale, string $email): void {

		$eFarm = $eSale['farm'];

		new \mail\SendLib()
			->setFarm($eFarm)
			->setReplyTo($eFarm['legalEmail'])
			->setFromName($eFarm['name'])
			->setTo($email)
			->setContent(...\shop\MailUi::getSaleMarketTicket($eSale))
			->send();

		$eSale['closed'] = TRUE;

		SaleLib::update($eSale, ['closed']);

	}
}
?>
