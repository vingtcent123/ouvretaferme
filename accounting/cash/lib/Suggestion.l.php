<?php
namespace cash;

class SuggestionLib extends CashCrud {

	public static function getForCashflow(\payment\Method $eMethod, string $dateAfter): \Collection {

		switch($eMethod['fqn']) {

			case \payment\MethodLib::CASH :

				\bank\Cashflow::model()->or(
					fn() => $this->whereMemo('LIKE', 'vrst %'),
					fn() => $this->whereMemo('LIKE', 'versement %'),
					fn() => $this->whereMemo('LIKE', 'vers%espece%'),
					fn() => $this->whereMemo('LIKE', 'ret%espece%'),
					fn() => $this->whereMemo('LIKE', 'ret%dab%'),
					fn() => $this->whereMemo('LIKE', 'ret%distrib%'),
				);

				break;

			case \payment\MethodLib::CHECK :

				\bank\Cashflow::model()->or(
					fn() => $this->whereMemo('LIKE', 'rem%cheq'),
					fn() => $this->whereMemo('LIKE', 'rem%chq'),
				);

				break;

			default :
				return new \Collection();

		}

		return self::getWaiting(Cash::BANK_CASHFLOW)
			->whereDate('>', $dateAfter)
			->getCollection();

	}

	public static function getForInvoice(\payment\Method $eMethod, string $dateAfter): \Collection {

		return self::getWaiting(Cash::SELL_INVOICE)
			->where('m1.method', $eMethod)
			->where('m1.paidAt', '>', $dateAfter)
			->getCollection();

	}

	public static function getForSale(\payment\Method $eMethod, string $dateAfter): \Collection {

		return self::getWaiting(Cash::SELL_SALE)
			->whereMethod($eMethod)
			->where('m2.paidAt', '>', $dateAfter)
			->getCollection();

	}

	public static function ignoreByMethod(\payment\Method $eMethod, string $dateAfter): void {

		Cash::model()->beginTransaction();

			$cCashflow = self::getForCashflow($eMethod, $dateAfter);

			if($cCashflow->notEmpty()) {

				\bank\Cashflow::model()
					->whereId('IN', $cCashflow)
					->update([
						'statusCash' => \selling\Payment::IGNORED
					]);

			}

		Cash::model()->commit();

		Cash::model()->beginTransaction();

			$cPayment = self::getForInvoice($eMethod, $dateAfter);

			if($cPayment->notEmpty()) {

				\selling\Payment::model()
					->whereId('IN', $cPayment)
					->update([
						'statusCash' => \selling\Payment::IGNORED
					]);

			}

		Cash::model()->commit();

		Cash::model()->beginTransaction();

			$cPayment = self::getForSale($eMethod, $dateAfter);

			if($cPayment->notEmpty()) {

				\selling\Payment::model()
					->whereId('IN', $cPayment)
					->update([
						'statusCash' => \selling\Payment::IGNORED
					]);

			}

		Cash::model()->commit();

	}

	public static function import(Register $eRegister, string $source, int $reference): void {

		Cash::model()->beginTransaction();

			$eCash = new Cash([
				'register' => $eRegister,
			]);

			$eCash->merge(
				self::getWaiting($source)
					->where('m1.id', $reference)
					->get()
			);

			$eCash['financialYear'] = \account\FinancialYearLib::getByDate($eCash['date']);

			if($eCash['financialYear']->empty()) {
				Cash::model()->rollBack();
				Cash::fail('date.financialYear');
				return;
			}

			switch($source) {

				case Cash::SELL_INVOICE :

					$ePayment = \selling\PaymentLib::getById($eCash['payment']);

					$eInvoice = \selling\InvoiceLib::getById($eCash['invoice']);

					$eInvoice['cSale'] = \selling\SaleLib::getByIds($eInvoice['sales']);
					$eInvoice['cItem'] = \selling\SaleLib::getItemsBySales($eInvoice['cSale']);

					self::importCreateFromRatios($eCash, $eInvoice, $ePayment);

					\selling\InvoiceLib::close($eInvoice);

					break;
				case Cash::SELL_SALE :

					$ePayment = \selling\PaymentLib::getById($eCash['payment']);

					$eSale = \selling\SaleLib::getById($eCash['sale']);
					$eSale['cItem'] = \selling\SaleLib::getItems($eSale);

					self::importCreateFromRatios($eCash, $eSale, $ePayment);

					\selling\SaleLib::close($eSale);

					break;

				case Cash::BANK_CASHFLOW :
					self::importCreate($eCash);
					break;

			}

		Cash::model()->commit();

	}

	public static function importCreateFromRatios(Cash $eCash, \selling\Sale|\selling\Invoice $e, \selling\Payment $ePayment): void {

		$ratios = \preaccounting\AccountingLib::computeRatios(
			$e,
			\account\AccountLib::getAll(),
			$ePayment
		);

		$amounts = [];

		foreach($ratios['amountsExcludingVat'] as ['vatRate' => $vatRate, 'amount' => $amount]) {

			$amounts[(string)$vatRate] ??= [
				'amountExcludingVat' => 0.0,
				'vat' => 0.0
			];

			$amounts[(string)$vatRate]['amountExcludingVat'] += $amount;

		}

		foreach($ratios['amountsVat'] as ['vatRate' => $vatRate, 'amount' => $amount]) {
			$amounts[(string)$vatRate]['vat'] += $amount;
		}

		foreach($amounts as $vatRate => $amount) {

			$amount['amountExcludingVat'] = round($amount['amountExcludingVat'], 2);
			$amount['hasVat'] = $e['hasVat'];
			$amount['vat'] = round($amount['vat'], 2);
			$amount['vatRate'] = (float)$vatRate;
			$amount['amountIncludingVat'] = round($amount['amountExcludingVat'] + $amount['vat'], 2);

			$eCashCreate = (clone $eCash)->merge($amount);

			self::importCreate($eCashCreate);

		}

	}

	public static function importCreate(Cash $eCash): void {

		CashLib::create($eCash);

		switch($eCash['source']) {

			case Cash::BANK_CASHFLOW :

				\bank\Cashflow::model()->update($eCash['cashflow'], [
					'statusCash' => \bank\Cashflow::VALID
				]);

				break;

			case Cash::SELL_INVOICE :
			case Cash::SELL_SALE :

				\selling\Payment::model()->update($eCash['payment'], [
					'statusCash' => \selling\Payment::VALID
				]);

				break;

		}

	}

	public static function ignore(string $source, int $reference): void {

		self::getModule($source)
			->whereId($reference)
			->where('m1.statusCash', \selling\Payment::WAITING)
			->update([
				'statusCash' => \selling\Payment::IGNORED
			]);

	}

	protected static function getWaiting(string $source): \ModuleModel {

		switch($source) {

			case Cash::BANK_CASHFLOW :

				return self::getModule($source)
					->select([
						'reference' => new \Sql('m1.id', 'int'),
						'cashflow' => fn($e) => new \bank\Cashflow(['id' => $e['reference']]),
						'date',
						'source' => fn() => Cash::BANK_CASHFLOW,
						'type' => fn($e) => match($e['type']) {
							\bank\Cashflow::CREDIT => Cash::DEBIT,
							\bank\Cashflow::DEBIT => Cash::CREDIT,
						},
						'amountIncludingVat' => new \Sql('-1 * amount'),
						'description' => new \Sql('memo'),
						'customer' => fn() => new \selling\Customer(),
					])
					->where('m1.statusCash', \bank\Cashflow::WAITING);

			case Cash::SELL_INVOICE :

				$eFarm = \farm\Farm::getConnected();

				return self::getModule($source)
					->join(\selling\Invoice::model()
						->select([
							'customer' => \selling\CustomerElement::getSelection(),
						]), 'm1.invoice = m2.id')
					->select([
						'reference' => new \Sql('m1.id', 'int'),
						'payment' => fn($e) => new \selling\Payment(['id' => $e['reference']]),
						'amountIncludingVat',
						'type' => fn($e) => ($e['amountIncludingVat'] > 0) ? Cash::CREDIT : Cash::DEBIT,
						'date' => new \Sql('m1.paidAt'),
						'invoice' => [
							'number',
							'priceIncludingVat', 'priceExcludingVat',
							'vat',
							'vatByRate',
							'closed',
						],
						'source' => fn() => Cash::SELL_INVOICE,
						'description' => fn($e) => \selling\InvoiceUi::getName($e['invoice'])
					])
					->where('m2.status', 'IN', [\selling\Invoice::GENERATED, \selling\Invoice::DELIVERED])
					->where('m1.statusCash', \selling\Payment::WAITING)
					->where('m1.farm', $eFarm)
					->where('m1.source', \selling\Payment::INVOICE);

			case Cash::SELL_SALE :

				$eFarm = \farm\Farm::getConnected();

				return self::getModule(Cash::SELL_SALE)
					->join(\selling\Sale::model()->select([
						'customer' => \selling\CustomerElement::getSelection(),
					]), 'm1.sale = m2.id')
					->select([
						'reference' => new \Sql('m1.id', 'int'),
						'payment' => fn($e) => new \selling\Payment(['id' => $e['reference']]),
						'date' => new \Sql('m1.paidAt'),
						'sale' => ['document', 'profile', 'priceIncludingVat', 'priceExcludingVat', 'vat', 'vatByRate', 'compositionEndAt', 'closed'],
						'source' => fn() => Cash::SELL_SALE,
						'type' => fn($e) => ($e['amountIncludingVat'] > 0) ? Cash::CREDIT : Cash::DEBIT,
						'amountIncludingVat',
						'description' => fn($e) => \selling\SaleUi::getName($e['sale'])
					])
					->where('m1.statusCash', \selling\Payment::WAITING)
					->where('m1.farm', $eFarm)
					->where('m2.profile', 'IN', [\selling\Sale::SALE, \selling\Sale::MARKET])
					->where('m2.invoice', NULL);

		}


	}

	protected static function getModule(string $source): \ModuleModel {

		switch($source) {

			case Cash::BANK_CASHFLOW :
				return \bank\Cashflow::model()
					->where('m1.status', '!=', \bank\Cashflow::DELETED);

			case Cash::SELL_INVOICE :
				return \selling\Payment::model()
					->where('m1.status', \selling\Payment::PAID)
					->where('m1.source', \selling\Payment::INVOICE);

			case Cash::SELL_SALE :
				return \selling\Payment::model()
					->where('m1.status', \selling\Payment::PAID)
					->where('m1.source', \selling\Payment::SALE);

		}


	}

}
?>
