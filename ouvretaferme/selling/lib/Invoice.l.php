<?php
namespace selling;

class InvoiceLib extends InvoiceCrud {

	public static function getPropertiesCreate(): array {
		return ['customer', 'sales', 'date', 'paymentCondition'];
	}

	public static function getPropertiesUpdate(): array {
		return ['description', 'paymentStatus'];
	}

	public static function getByFarm(\farm\Farm $eFarm, bool $selectSales = FALSE, int $page = 0, \Search $search = new \Search()): array {

		if($search->get('document')) {

			$document = $search->get('document');
			if(stripos($document, 'FA') === 0 or stripos($document, 'AV') === 0) {
				$document = substr($document, 2);
			}

			Invoice::model()->whereDocument($document);

		}

		if($search->get('customer')) {
			$cCustomer = CustomerLib::getFromQuery($search->get('customer'), $eFarm);
			Invoice::model()->whereCustomer('IN', $cCustomer);
		}

		$search->validateSort(['id', 'customer', 'date', 'priceExcludingVat'], 'id-');

		$number = 100;
		$position = $page * $number;

		if($selectSales) {
			Invoice::model()->select([
				'cSale' => Sale::model()
					->select(['id', 'document'])
					->sort('id')
					->delegateCollection('invoice')
			]);
		}

		$cInvoice = Invoice::model()
			->select(Invoice::getSelection())
			->option('count')
			->whereFarm($eFarm)
			->wherePaymentStatus('LIKE', $search->get('paymentStatus'), if: $search->get('paymentStatus'))
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->sort($search->buildSort())
			->getCollection($position, $number);

		return [$cInvoice, Invoice::model()->found()];

	}

	public static function getByCustomer(Customer $eCustomer, bool $selectSales = FALSE): \Collection {

		if($selectSales) {
			Invoice::model()->select([
				'cSale' => Sale::model()
					->select(['id', 'document'])
					->sort('id')
					->delegateCollection('invoice')
			]);
		}

		return Invoice::model()
			->select(Invoice::getSelection())
			->whereCustomer($eCustomer)
			->sort(['id' => SORT_DESC])
			->getCollection();

	}

	public static function create(Invoice $e): void {

		$e->expects([
			'farm',
			'customer',
			'date', 'paymentCondition',
			'cSale'
		]);

		Invoice::model()->beginTransaction();

			$e['document'] = ConfigurationLib::getNextDocument($e['farm'], 'documentInvoices');
			$e['taxes'] = $e['cSale']->first()['taxes'];
			$e['hasVat'] = $e['cSale']->first()['hasVat'];
			$e['organic'] = FALSE;
			$e['createdAt'] = Invoice::model()->now(); // Besoin de la date pour pouvoir envoyer le PDF par e-mail dans la foulée

			$totalVat = 0.0;
			$vatByRate = [];
			$rates = [];

			$priceExcludingVat = 0.0;
			$priceIncludingVat = 0.0;

			foreach($e['cSale'] as $eSale) {

				$totalVat += $eSale['vat'];

				if($eSale['organic']) {
					$e['organic'] = TRUE;
				}

				// Calcul de la somme de TVA sur les différentes ventes
				if($eSale['vatByRate']) {

					foreach($eSale['vatByRate'] as ['vat' => $vat, 'vatRate' => $vatRate, 'amount' => $amount]) {

						if(array_key_exists((string)$vatRate, $rates) === FALSE) {
							$rates[(string)$vatRate] = count($vatByRate);
						}

						$key = $rates[(string)$vatRate];

						$vatByRate[$key] ??= [
							'vat' => 0.0,
							'vatRate' => $vatRate,
							'amount' => 0.0
						];

						$vatByRate[$key]['vat'] += $vat;
						$vatByRate[$key]['amount'] += $amount;

					}

				}

				$priceExcludingVat += $eSale['priceExcludingVat'];
				$priceIncludingVat += $eSale['priceIncludingVat'];

			}

			array_walk($vatByRate, function(&$entry) {
				$entry['vat'] = round($entry['vat'], 2);
				$entry['amount'] = round($entry['amount'], 2);
			});

			$e->merge([
				'vatByRate' => $vatByRate,
				'vat' => $totalVat,
				'priceExcludingVat' => $priceExcludingVat,
				'priceIncludingVat' => $priceIncludingVat,
			]);

			parent::create($e);

			Sale::model()
				->whereId('IN', $e['cSale'])
				->update([
					'invoice' => $e
				]);

		Invoice::model()->commit();

		self::generate($e);

	}

	public static function generate(Invoice $e): void {

		$e->expects([
			'cSale'
		]);

		$e->merge([
			'createdAt' => Invoice::model()->now(),
			'emailedAt' => NULL
		]);

		if($e['cSale']->count() === 1) {

			$ePdf = \selling\PdfLib::generate(Pdf::INVOICE, $e['cSale']->first());

			$e['content'] = $ePdf['content'];

		} else {

			$cPdf = \selling\PdfLib::generateInvoice($e);

			$e['content'] = $cPdf->first()['content'];

		}

		Invoice::model()
			->select('content', 'createdAt', 'emailedAt')
			->update($e);

	}

	public static function delete(Invoice $e): void {

		$e->expects(['id']);

		Invoice::model()->beginTransaction();

			if(Invoice::model()
				->select(['farm', 'content'])
				->get($e)) {

				Pdf::model()
					->whereContent($e['content'])
					->delete();

				if($e['content']->notEmpty()) {
					PdfContent::model()->delete($e['content']);
				}

				Sale::model()
					->whereFarm($e['farm'])
					->whereInvoice($e)
					->update([
						'invoice' => NULL
					]);

				Invoice::model()->delete($e);

			}

		Invoice::model()->commit();

	}

}
?>
