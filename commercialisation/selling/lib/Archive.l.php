<?php
namespace selling;

class ArchiveLib extends ArchiveCrud {

	public static function getPropertiesCreate(): array {
		return ['from', 'to'];
	}

	public static function create(Archive $e): void {

		$output = self::getCsv($e);
		$csv = \util\CsvLib::toCsv($output);
		$csv = trim($csv);

		$e['csv'] = $csv;
		$e['sha256'] = hash('sha256', $csv);

		parent::create($e);

	}

	public static function getCsv(Archive $e): array {

		$cSale = Sale::model()
			->select([
				'id', 'document',
				'invoice' => ['number'],
				'priceIncludingVat', 'priceExcludingVat', 'discount',
				'preparationStatus', 'paymentStatus',
				'createdAt'
			])
			->whereFarm($e['farm'])
			->whereProfile('IN', [Sale::SALE, Sale::SALE_MARKET])
			->whereType(Sale::PRIVATE)
			->whereSecured(TRUE)
			->whereCreatedAt('BETWEEN', new \Sql(Sale::model()->format($e['from']).' AND '.Sale::model()->format($e['to'])))
			->sort(['id' => SORT_ASC])
			->getCollection(index: 'id');

		$output = new ArchiveUi()->getCsvTransactionHeader();

		$cInvoice = $cSale->getColumnCollection('invoice');

		foreach($cSale as $eSale) {

			$output[] = [
				ArchiveUi::getSaleReference($eSale),
				$eSale['invoice']->notEmpty() ? ArchiveUi::getInvoiceReference($eSale['invoice']) : NULL,
				substr($eSale['createdAt'], 0, 16),
				\util\TextUi::csvNumber($eSale['priceIncludingVat']),
				\util\TextUi::csvNumber($eSale['priceExcludingVat']),
				\util\TextUi::csvNumber($eSale['discount']),
				SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']],
				$eSale['paymentStatus'] ? SaleUi::p('paymentStatus')->values[$eSale['paymentStatus']] : NULL
			];

		}

		$cPayment = Payment::model()
			->select(Payment::getSelection() + [
				'sale' => ['document'],
				'invoice' => ['number']
			])
			->whereFarm($e['farm'])
			->whereStatus(Payment::PAID)
			->or(
				fn() => $this->whereSale('IN', $cSale->find(fn($eSale) => $eSale['invoice']->empty())),
				fn() => $this->whereInvoice('IN', $cInvoice),
			)
			->sort(['id' => SORT_ASC])
			->getCollection();


		$output = array_merge($output, new ArchiveUi()->getCsvPaymentHeader());

		foreach($cPayment as $ePayment) {

			$output[] = [
				ArchiveUi::getReference($ePayment),
				$ePayment['method']['name'],
				\util\TextUi::csvNumber($ePayment['amountIncludingVat']),
				$ePayment['paidAt'],
			];

		}

		$cItem = Item::model()
			->select(['sale', 'name', 'number', 'packaging', 'vatRate', 'unitPrice', 'price'])
			->whereIngredientOf(NULL)
			->whereFarm($e['farm'])
			->whereSale('IN', $cSale->getIds())
			->sort(['id' => SORT_ASC])
			->getCollection();

		$output = array_merge($output, new ArchiveUi()->getCsvItemHeader());

		foreach($cItem as $eItem) {

			$output[] = [
				ArchiveUi::getSaleReference($cSale[$eItem['sale']['id']]),
				$eItem['name'],
				$eItem['number'] * ($eItem['packaging'] ?? 1),
				\util\TextUi::csvNumber($eItem['unitPrice']),
				\util\TextUi::csvNumber($eItem['price']),
				\util\TextUi::csvNumber(\util\AmountUi::fromIncluding($eItem['price'], $eItem['vatRate'])),
				\util\TextUi::csvNumber($eItem['vatRate']),
			];

		}

		return $output;

	}

	public static function getList(\farm\Farm $eFarm): \Collection {

		return Archive::model()
			->select(Archive::getSelection())
			->whereFarm($eFarm)
			->sort([
				'id' => SORT_DESC
			])
			->getCollection();

	}

}
?>
