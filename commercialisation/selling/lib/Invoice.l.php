<?php
namespace selling;

class InvoiceLib extends InvoiceCrud {

	public static function getPropertiesCreate(): array {
		return ['customer', 'sales', 'date', 'dueDate', 'paymentCondition', 'header', 'footer', 'status'];
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Invoice $e) {

			$properties = ['comment'];

			if($e->isPaymentOnline() === FALSE) {
				$properties[] = 'paymentMethod';
				$properties[] = 'paymentStatus';
			}

			return $properties;

		};
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

		if($search->has('paymentStatus')) {
			if($search->get('paymentStatus') === Invoice::NOT_PAID) {
			Invoice::model()
				->or(
					fn() => $this->wherePaymentStatus(NULL),
					fn() => $this->wherePaymentStatus(Invoice::NOT_PAID)
				);
			} else if($search->get('paymentStatus') === Invoice::PAID) {
				Invoice::model()->wherePaymentStatus(Invoice::PAID);
			}
		}

		$cInvoice = Invoice::model()
			->select(Invoice::getSelection())
			->option('count')
			->whereId('=', $search->get('invoice'), if: $search->get('invoice'))
			->whereFarm($eFarm)
			->whereStatus('LIKE', $search->get('status'), if: $search->get('status'))
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereAccountingHash($search->get('accountingHash'), if: $search->has('accountingHash'))
			->sort($search->buildSort())
			->getCollection($position, $number);

		return [$cInvoice, Invoice::model()->found()];

	}

	public static function getLastDate(\farm\Farm $eFarm): ?string {

		return Invoice::model()
			->whereFarm($eFarm)
			->getValue(new \Sql('MAX(date)'));

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
			->select(Invoice::getSelection())
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
			->whereProfile('!=', Sale::MARKET)
			->whereMarketParent(NULL)
			->exists();

	}

	public static function createCollection(\Collection $c): void {

		foreach($c as $e) {
			self::create($e);
		}

	}

	public static function create(Invoice $e): void {

		$e->expects([
			'farm',
			'customer',
			'date', 'paymentCondition',
			'cSale', 'sales',
			'status'
		]);

		Invoice::model()->beginTransaction();

			$e['document'] = NULL;

			$e['generation'] = match($e['status']) {
				Invoice::DRAFT => NULL,
				Invoice::CONFIRMED => $e['generation'] ?? Invoice::WAITING,
			};

			$e['taxes'] = $e['cSale']->first()['taxes'];
			$e['hasVat'] = $e['cSale']->first()['hasVat'];
			$e['organic'] = FALSE;
			$e['conversion'] = FALSE;
			$e['createdAt'] = Invoice::model()->now(); // Besoin de la date pour pouvoir envoyer le PDF par e-mail dans la foulée

			$cSale = $e['cSale'];

			$vatByRate = [];
			$rates = [];

			$priceExcludingVat = 0.0;
			$priceIncludingVat = 0.0;

			foreach($cSale as $eSale) {

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

			$e['paymentMethod'] = $cSale->first()['cPayment']->first()['method'] ?? NULL;
			$e['paymentStatus'] = $cSale->first()['paymentStatus'] ?? Invoice::NOT_PAID;

			parent::create($e);

			Sale::model()
				->whereId('IN', $e['cSale'])
				->update([
					'secured' => TRUE,
					'invoice' => $e
				]);

		Invoice::model()->commit();

		if($e['generation'] === Invoice::NOW) {

			$e['customer'] = CustomerLib::getById($e['customer']['id']);
			$e['farm'] = \farm\FarmLib::getById($e['farm']['id']);

			self::generate($e);

		}

	}

	public static function updateStatusDelivered(Invoice $e, \Collection $cSale): bool {

		Invoice::model()->beginTransaction();

			if(
				Invoice::model()
					->whereStatus(Invoice::GENERATED)
					->update($e, [
						'status' => Invoice::DELIVERED,
						'emailedAt' => new \Sql('NOW()')
					]) === 0
			) {

				Invoice::model()->commit();
				return FALSE;

			}

		Invoice::model()->commit();

		return TRUE;

	}

	public static function updateStatus(Invoice $e, string $newStatus): void {

		if($e['status'] === $newStatus) {
			return;
		}

		$e['oldStatus'] = $e['status'];
		$e['status'] = $newStatus;

		self::update($e, ['status']);

	}

	public static function updateStatusCollection(\Collection $c, string $newStatus): void {

		Invoice::model()->beginTransaction();

		foreach($c as $e) {
			self::updateStatus($e, $newStatus);
		}

		Invoice::model()->commit();

	}

	public static function updatePaymentCollection(\Collection $c, array $payment): void {

		foreach($c as $e) {

			Invoice::model()->beginTransaction();

			$e['paymentMethod'] = $payment['paymentMethod'];
			$e['paymentStatus'] = $payment['paymentStatus'];

			self::update($e, array_keys($payment));

			Invoice::model()->commit();
		}

	}

	public static function update(Invoice $e, array $properties): void {

		Invoice::model()->beginTransaction();

			if(in_array('status', $properties)) {

				$e->expects(['oldStatus']);

				if($e['status'] !== $e['oldStatus']) {

					if($e['status'] === Invoice::CONFIRMED) {
						$e['generation'] = Invoice::WAITING;
						$properties[] = 'generation';
					}

				} else {
					array_delete($properties, 'status');
				}

			}

			parent::update($e, $properties);

			$updateSale = [];

			if(in_array('paymentStatus', $properties)) {
				$updateSale['paymentStatus'] = $e['paymentStatus'];
			}

			if(in_array('status', $properties)) {

				switch($e['status']) {

					case Invoice::CANCELED :

						Sale::model()
							->whereFarm($e['farm'])
							->whereInvoice($e)
							->update([
								'invoice' => NULL
							]);

						break;

				}

			}

			if($updateSale) {

				$updateSaleProperties = array_keys($updateSale);

				$cSale = SaleLib::getByIds($e['sales']);

				foreach($cSale as $eSale) {

					SaleLib::update($eSale->merge($updateSale), $updateSaleProperties);

					if(in_array('paymentMethod', $properties)) {
						PaymentLib::putBySale($eSale, $e['paymentMethod']);
					}
				}

			}

		Invoice::model()->commit();

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

	public static function generateFail() {

		// Si la génération dure depuis plus d'une heure c'est que ça a planté
		$cInvoice = Invoice::model()
			->select(Invoice::getSelection())
			->whereStatus(Invoice::CONFIRMED)
			->whereGeneration(Invoice::PROCESSING)
			->whereGenerationAt('<', new \Sql('NOW() - INTERVAL 1 HOUR'))
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			Invoice::model()->update($eInvoice, [
				'generation' => Invoice::FAIL,
				'status' => Invoice::DRAFT
			]);

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
				'generation' => Invoice::PROCESSING,
				'generationAt' => currentDatetime()
			]) === 0) {
			return;
		}

		self::associateDocument($e);

		Invoice::model()->beginTransaction();

			if($e['cSale']->count() === 1) {

				$callback = function() use ($e) {
					$e['customer'] = CustomerLib::getById($e['customer']['id']);
					return FacturXLib::generate($e, PdfLib::build('/selling/pdf:getDocumentInvoice?id='.$e['id']));
				};

				$ePdfContent = \selling\PdfLib::generateDocument(Pdf::INVOICE, $e['cSale']->first(), $callback);

			} else {

				$ePdfContent = \selling\PdfLib::generateInvoice($e);

			}

			$e['content'] = $ePdfContent;
			$e['status'] = Invoice::GENERATED;
			$e['generation'] = Invoice::SUCCESS;

			Invoice::model()
				->select('content', 'createdAt', 'emailedAt', 'status', 'generation')
				->update($e);

		Invoice::model()->commit();

	}

	public static function associateDocument(Invoice $eInvoice): void {

		Invoice::model()->beginTransaction();

			Invoice::model()
				->select('document')
				->get($eInvoice);

			if($eInvoice['document'] === NULL) {

				$eInvoice['document'] = \farm\ConfigurationLib::getNextDocumentInvoices($eInvoice['farm']);
				$eInvoice['name'] = $eInvoice->getInvoice($eInvoice['farm']);

				Invoice::model()
					->select('document', 'name')
					->update($eInvoice);

			}

		Invoice::model()->commit();

	}

	public static function buildCollectionForInvoice(\farm\Farm $eFarm, Invoice $eInvoiceBase, array $sales): \Collection {

		$cInvoice = new \Collection();

		foreach($sales as $list) {

			$ids = explode(',', $list);

			// Get customer based on the first ID
			$eCustomer = Sale::model()
				->select([
					'customer' => ['destination']
				])
				->whereId(first($ids))
				->whereFarm($eFarm)
				->getValue('customer')
				->validate('acceptInvoice');

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

	public static function updateReadyForAccountingCollection(\Collection $cInvoice, ?bool $readyForAccounting): void {

		Invoice::model()
			->whereId('IN', $cInvoice->getIds())
			->update(['readyForAccounting' => $readyForAccounting]);

	}

}
?>
