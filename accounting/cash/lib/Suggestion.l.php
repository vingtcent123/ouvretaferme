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

		return \bank\Cashflow::model()
			->select([
				'id',
				'date',
				'source' => fn() => Cash::BANK_MANUAL,
				'type' => fn($e) => match($e['type']) {
					\bank\Cashflow::CREDIT => Cash::DEBIT,
					\bank\Cashflow::DEBIT => Cash::CREDIT,
				},
				'amountIncludingVat' => new \Sql('amount'),
				'description' => new \Sql('memo'),
				'customer' => fn() => new \selling\Customer()
			])
			->whereStatus('!=', \bank\Cashflow::DELETED)
			->whereStatusCash(\bank\Cashflow::WAITING)
			->whereDate('>', $dateAfter)
			->getCollection();

	}

	public static function getForInvoice(\farm\Farm $eFarm, \payment\Method $eMethod, string $dateAfter): \Collection {

		return \selling\Payment::model()
			->join(\selling\Invoice::model()
				->select([
					'id',
					'number',
					'priceIncludingVat', 'priceExcludingVat',
					'vat',
					'vatByRate',
					'customer' => \selling\CustomerElement::getSelection(),
					'description' => fn($e) => \selling\InvoiceUi::getName($e)
				]), 'm1.invoice = m2.id')
			->select([
				'amountIncludingVat',
				'type' => fn($e) => ($e['amountIncludingVat'] > 0) ? Cash::CREDIT : Cash::DEBIT,
				'date' => new \Sql('m1.paidAt'),
				'invoice',
				'source' => fn() => Cash::SELL_INVOICE,
			])
			->where('m1.farm', $eFarm)
			->where('m1.source', \selling\Payment::INVOICE)
			->where('m1.method', $eMethod)
			->where('m1.paidAt', '>', $dateAfter)
			->where('m2.status', 'IN', [\selling\Invoice::GENERATED, \selling\Invoice::DELIVERED])
			->where('m2.statusCash', \selling\Invoice::WAITING)
			->getCollection();

	}

	public static function getForSale(\farm\Farm $eFarm, \payment\Method $eMethod, string $dateAfter): \Collection {

		return \selling\Payment::model()
			->join(\selling\Sale::model()->select([
				'id', 'document', 'profile', 'priceIncludingVat', 'priceExcludingVat', 'vat', 'vatByRate', 'compositionEndAt',
				'customer' => \selling\CustomerElement::getSelection(),
				'description' => fn($e) => \selling\SaleUi::getName($e)
			]), 'm1.sale = m2.id')
			->select([
				'id' => new \Sql('m2.id'),
				'date' => new \Sql('m1.paidAt'),
				'sale',
				'source' => fn() => Cash::SELL_SALE,
				'type' => fn($e) => ($e['amountIncludingVat'] > 0) ? Cash::CREDIT : Cash::DEBIT,
				'amountIncludingVat',
			])
			->whereMethod($eMethod)
			->where('m1.farm', $eFarm)
			->where('m2.paidAt', '>', $dateAfter)
			->where('m2.createdAt', '>', $dateAfter)
			->where('m2.profile', 'IN', [\selling\Sale::SALE, \selling\Sale::MARKET])
			->where('m2.invoice', NULL)
			->whereStatus(\selling\Payment::PAID)
			->whereStatusCash(\selling\Payment::WAITING)
			->getCollection();

	}

}
?>
