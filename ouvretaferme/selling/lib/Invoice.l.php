<?php
namespace selling;

class InvoiceLib extends InvoiceCrud {

	public static function getPropertiesCreate(): array {
		return ['customer', 'sales', 'date', 'paymentCondition', 'header', 'footer'];
	}

	public static function getPropertiesUpdate(): array {
		return ['description', 'paymentMethod', 'paymentStatus'];
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
			$cCustomer = CustomerLib::getFromQuery($search->get('customer'), $eFarm, withCollective: FALSE);
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

	public static function getByCustomers(\Collection $cCustomer, ?int $limit = NULL): \Collection {

		return Invoice::model()
			->select(Invoice::getSelection() + [
				'farm' => ['name', 'url', 'vignette']
			])
			->whereCustomer('IN', $cCustomer)
			->sort(['id' => SORT_DESC])
			->getCollection(0, $limit);

	}

	public static function getPendingTransfer(\farm\Farm $eFarm, string $month): int {

		$ePaymentMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::TRANSFER);

		return Sale::model()
			->join(Payment::model(), 'm1.id = m2.sale')
			->where('m2.method IS NOT NULL AND m2.method = '.$ePaymentMethod['id'])
			->where('m1.farm', $eFarm)
			->whereShop('!=', NULL)
			->wherePreparationStatus(Sale::DELIVERED)
			->whereDeliveredAt('LIKE', $month.'%')
			->whereInvoice(NULL)
			->getValue(new \Sql('COUNT(*)'));

	}

	public static function existsQualifiedSales(\farm\Farm $eFarm): bool {

		return Sale::model()
			->whereFarm($eFarm)
			->wherePreparationStatus(Sale::DELIVERED)
			->whereMarket(FALSE)
			->whereMarketParent(NULL)
			->exists();

	}

	public static function createCollection(\Collection $c): void {

		foreach($c as $e) {
			$e['generation'] = Invoice::WAITING;
			self::create($e);
		}

	}

	public static function create(Invoice $e): void {

		$e->expects([
			'farm',
			'customer',
			'date', 'paymentCondition',
			'cSale', 'sales',
			'generation'
		]);

		Invoice::model()->beginTransaction();

			$e['document'] = ConfigurationLib::getNextDocumentInvoices($e['farm']);
			$e['taxes'] = $e['cSale']->first()['taxes'];
			$e['hasVat'] = $e['cSale']->first()['hasVat'];
			$e['organic'] = FALSE;
			$e['conversion'] = FALSE;
			$e['createdAt'] = Invoice::model()->now(); // Besoin de la date pour pouvoir envoyer le PDF par e-mail dans la foulée

			$vatByRate = [];
			$rates = [];

			$priceExcludingVat = 0.0;
			$priceIncludingVat = 0.0;

			foreach($e['cSale'] as $eSale) {

				if($eSale['organic']) {
					$e['organic'] = TRUE;
				}

				if($eSale['conversion']) {
					$e['conversion'] = TRUE;
				}

				// Calcul de la somme de TVA sur les différentes ventes
				if($eSale['vatByRate']) {

					foreach($eSale['vatByRate'] as ['vat' => $vat, 'vatRate' => $vatRate, 'amount' => $amount]) {

						if(array_key_exists((string)$vatRate, $rates) === FALSE) {
							$rates[(string)$vatRate] = count($vatByRate);
						}

						$key = $rates[(string)$vatRate];

						$vatByRate[$key] ??= [
							'vatRate' => $vatRate,
							'amount' => 0.0
						];

						$vatByRate[$key]['amount'] += $amount;

					}

				}

				$priceExcludingVat += $eSale['priceExcludingVat'];
				$priceIncludingVat += $eSale['priceIncludingVat'];

			}

			$totalVat = 0.0;

			array_walk($vatByRate, function(&$entry) use($e, &$totalVat) {

				$entry['amount'] = round($entry['amount'], 2);

				$entry['vat'] = match($e['taxes']) {
					Invoice::INCLUDING => round($entry['amount'] - $entry['amount'] / (1 + $entry['vatRate'] / 100), 2),
					Invoice::EXCLUDING => round($entry['amount'] * $entry['vatRate'] / 100, 2),
				};

				$totalVat += $entry['vat'];

			});

			$totalVat = round($totalVat, 2);

			$e->merge([
				'vatByRate' => $vatByRate,
				'vat' => $totalVat,
				'priceExcludingVat' => match($e['taxes']) {
					Invoice::INCLUDING => $priceIncludingVat - $totalVat,
					Invoice::EXCLUDING => $priceExcludingVat,
				},
				'priceIncludingVat' => match($e['taxes']) {
					Invoice::INCLUDING => $priceIncludingVat,
					Invoice::EXCLUDING => $priceExcludingVat + $totalVat,
				},
			]);

			$e['name'] = $e->getInvoice($e['farm']);

			parent::create($e);

			Sale::model()
				->whereId('IN', $e['cSale'])
				->update([
					'invoice' => $e
				]);

		Invoice::model()->commit();

		if($e['generation'] === Invoice::NOW) {
			self::generate($e);
		}

	}

	public static function regenerate(Invoice $e): void {

		$affected = Invoice::model()
			->whereGeneration('IN', [Invoice::FAIL, Invoice::SUCCESS])
			->update($e, [
				'generation' => Invoice::NOW
			]);

		if($affected) {
			self::generate($e);
		}

	}
	public static function generateWaiting() {

		$cInvoice = Invoice::model()
			->select(Invoice::getSelection())
			->whereGeneration(Invoice::WAITING)
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			$eInvoice['cSale'] = SaleLib::getByIds($eInvoice['sales']);

			self::generate($eInvoice);

		}

	}

	public static function generate(Invoice $e): void {

		$e->expects([
			'cSale'
		]);

		$e->merge([
			'createdAt' => Invoice::model()->now(),
			'emailedAt' => NULL
		]);

		if(Invoice::model()
			->whereGeneration('IN', [Invoice::NOW, Invoice::WAITING])
			->update($e, [
				'generation' => Invoice::PROCESSING
			]) === 0) {
			return;
		}

		if($e['cSale']->count() === 1) {

			$ePdf = \selling\PdfLib::generate(Pdf::INVOICE, $e['cSale']->first());

			$e['content'] = $ePdf['content'];

		} else {

			$cPdf = \selling\PdfLib::generateInvoice($e);

			$e['content'] = $cPdf->first()['content'];

		}

		$e['generation'] = Invoice::SUCCESS;

		Invoice::model()
			->select('content', 'createdAt', 'emailedAt', 'generation')
			->update($e);

	}

	public static function buildCollectionForInvoice(\farm\Farm $eFarm, Invoice $eInvoiceBase, array $sales): \Collection {

		$cInvoice = new \Collection();

		foreach($sales as $list) {

			$ids = explode(',', $list);

			// Get customer based on the first ID
			$eCustomer = Sale::model()
				->whereId(first($ids))
				->whereFarm($eFarm)
				->getValue('customer');

			$cSale = SaleLib::getForInvoice($eCustomer, $ids);

			if($cSale->count() !== count($ids)) {
				Invoice::fail('sales.check');
				return new \Collection();
			}

			$eInvoice = (clone $eInvoiceBase);
			$eInvoice['customer'] = $eCustomer;
			$eInvoice['farm'] = $eFarm;
			$eInvoice['cSale'] = $cSale;
			$eInvoice['sales'] = $cSale->getIds();

			$cInvoice[] = $eInvoice;

		}

		return $cInvoice;

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

	public static function deleteCollection(\Collection $cInvoice): void {

		foreach($cInvoice as $eInvoice) {
			self::delete($eInvoice);
		}

	}

}
?>
