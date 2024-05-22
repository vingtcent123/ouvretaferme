<?php
namespace selling;

class PdfLib extends PdfCrud {

	public static function getOne(Sale $eSale, string $type): Pdf {

		return Pdf::model()
			->select(Pdf::getSelection())
			->whereSale($eSale)
			->whereType($type)
			->get();

	}

	public static function getBySale(Sale $eSale): \Collection {

		return Pdf::model()
			->select(Pdf::getSelection())
			->whereSale($eSale)
			->getCollection(NULL, NULL, 'type');

	}

	public static function getContentBySale(Sale $eSale, string $type): ?string {

		$ePdf = Pdf::model()
			->select([
				'content' => ['binary']
			])
			->whereSale($eSale)
			->whereType($type)
			->get();

		if(
			$ePdf->notEmpty() and
			$ePdf['content']->notEmpty()
		) {
			return $ePdf['content']['binary'];
		} else {
			return NULL;
		}

	}

	public static function getContentByInvoice(Invoice $eInvoice): ?string {

		PdfContent::model()
			->select('binary')
			->get($eInvoice['content']);

		return $eInvoice['content']['binary'];

	}

	public static function sendBySale(\farm\Farm $eFarm, Sale $eSale, string $type): void {

		$eFarm->expects([
			'name',
			'selling' => ['legalEmail']
		]);

		$eSale->expects([
			'customer' => [
				'email',
				'user'
			]
		]);

		$eCustomer = $eSale['customer'];

		if(in_array($type, [Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM]) === FALSE) {
			throw new \Exception('Invalid type');
		}

		$customerEmail = $eCustomer['email'] ?? $eCustomer['user']['email'] ?? NULL;

		if($customerEmail === NULL) {
			Pdf::fail('noCustomerEmail');
			return;
		}

		if($eFarm['selling']['legalEmail'] === NULL) {
			Pdf::fail('noFarmEmail');
			return;
		}

		$ePdf = Pdf::model()
			->select(Pdf::getSelection())
			->select([
				'content' => ['binary']
			])
			->whereSale($eSale)
			->whereType($type)
			->whereContent('!=', NULL)
			->whereEmailedAt(NULL)
			->get();

		if($ePdf->empty()) {
			Pdf::fail('fileEmpty');
			return;
		}

		if($ePdf['emailedAt'] !== NULL) {
			Pdf::fail('fileAlreadySent');
			return;
		}

		if(
			$ePdf->canSend() === FALSE or
			Pdf::model()->update($ePdf, ['emailedAt' => new \Sql('NOW()')]) === 0
		) {
			Pdf::fail('fileAlreadySent');
			return;
		}

		switch($type) {

			case Pdf::ORDER_FORM :
				$template = \mail\CustomizeLib::getTemplate($eFarm, \mail\Customize::SALE_ORDER_FORM);
				$content = (new PdfUi())->getOrderFormMail($eFarm, $eSale, $template);
				break;

			case Pdf::DELIVERY_NOTE :
				$template = \mail\CustomizeLib::getTemplate($eFarm, \mail\Customize::SALE_DELIVERY_NOTE);
				$content = (new PdfUi())->getDeliveryNoteMail($eFarm, $eSale, $template);
				break;

		}

		$libMail = (new \mail\MailLib());

		if($eFarm['selling']['documentCopy']) {
			$libMail->addBcc($eFarm['selling']['legalEmail']);
		}

		$libMail
			->setFromName($eFarm['name'])
			->addTo($customerEmail)
			->setReplyTo($eFarm['selling']['legalEmail'])
			->setContent(...$content)
			->addAttachment($ePdf['content']['binary'], $eSale->getDeliveryNote().'.pdf', 'application/pdf')
			->send('document');

	}

	public static function sendByInvoice(\farm\Farm $eFarm, Invoice $eInvoice): void {

		$eFarm->expects([
			'name',
			'selling' => ['legalEmail']
		]);

		$eInvoice->expects([
			'customer' => ['email']
		]);

		$eCustomer = $eInvoice['customer'];

		if($eCustomer['email'] === NULL) {
			Pdf::fail('noCustomerEmail');
			return;
		}

		if($eFarm['selling']['legalEmail'] === NULL) {
			Pdf::fail('noFarmEmail');
			return;
		}

		$ePdfContent = $eInvoice['content'];

		if(PdfContent::model()
			->select('binary')
			->get($ePdfContent) === FALSE) {
			Invoice::fail('fileEmpty');
			return;
		}

		if($eInvoice['emailedAt'] !== NULL) {
			Invoice::fail('fileAlreadySent');
			return;
		}

		if($eInvoice->acceptSend() === FALSE) {
			Invoice::fail('fileTooOld');
			return;
		}

		if(Invoice::model()->update($eInvoice, ['emailedAt' => new \Sql('NOW()')]) === 0) {
			Invoice::fail('fileAlreadySent');
			return;
		}

		$cSale = SaleLib::getByInvoice($eInvoice);

		$template = \mail\CustomizeLib::getTemplate($eFarm, \mail\Customize::SALE_INVOICE);
		$content = (new PdfUi())->getInvoiceMail($eFarm, $eInvoice, $cSale, $template);

		$libMail = (new \mail\MailLib());

		if($eFarm['selling']['documentCopy']) {
			$libMail->addBcc($eFarm['selling']['legalEmail']);
		}

		$libMail
			->setFromName($eFarm['name'])
			->addTo($eCustomer['email'])
			->setReplyTo($eFarm['selling']['legalEmail'])
			->setContent(...$content)
			->addAttachment($ePdfContent['binary'], $eInvoice->getInvoice().'.pdf', 'application/pdf')
			->send('document');

	}

	public static function generate(string $type, Sale $eSale): Pdf {

		$eSale->expects(['farm']);

		$content = self::build('/selling/pdf:getDocument?id='.$eSale['id'].'&type='.$type);

		Pdf::model()->beginTransaction();

		$ePdfContent = new PdfContent([
			'binary' => $content
		]);

		PdfContent::model()->insert($ePdfContent);

		$ePdf = new Pdf([
			'type' => $type,
			'sale' => $eSale,
			'farm' => $eSale['farm'],
			'content' => $ePdfContent,
			'createdAt' => Pdf::model()->now() // Besoin de la date pour pouvoir envoyer le PDF par e-mail dans la foulée
		]);

		Pdf::model()
			->option('add-replace')
			->insert($ePdf);

		Pdf::model()->commit();

		return $ePdf;

	}

	public static function generateInvoice(Invoice $eInvoice): \Collection {

		$eInvoice->expects(['farm', 'sales']);

		$content = self::build('/selling/pdf:getDocumentInvoice?id='.$eInvoice['id']);

		Pdf::model()->beginTransaction();

		$ePdfContent = new PdfContent([
			'binary' => $content
		]);

		PdfContent::model()->insert($ePdfContent);

		$cPdf = new \Collection();

		$pdf = [
			'used' => $eInvoice['cSale']->count(),
			'farm' => $eInvoice['farm'],
			'content' => $ePdfContent,
			'type' => Pdf::INVOICE
		];

		foreach($eInvoice['cSale'] as $eSale) {

			$cPdf[] = new Pdf($pdf + [
				'sale' => $eSale,
			]);

		}

		Pdf::model()
			->option('add-replace')
			->insert($cPdf);

		Pdf::model()->commit();

		return $cPdf;

	}

	public static function buildLabels(\farm\Farm $eFarm, \Collection $cSale): string {

		$queryString = implode('&', $cSale->toArray(fn($eSale) => 'sales[]='.$eSale['id']));

		return self::build('/selling/pdf:getLabels?id='.$eFarm['id'].'&'.$queryString);

	}

	public static function build($url): string {

		if(OTF_DEMO) {
			return '';
		}

		return \Cache::redis()->lock('pdf-'.$url, function() use ($url) {

			$file = tempnam('/tmp', 'pdf-').'.pdf';

			$url .= str_contains($url, '?') ? '&' : '?';
			$url .= 'key='.\Setting::get('selling\remoteKey');

			$args = '"--url='.\Lime::getUrl().$url.'"';
			$args .= ' "--destination='.$file.'"';

			exec('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');

			$content = file_get_contents($file);

			unlink($file);

			return $content;

		}, fn() => throw new \FailAction('selling\Pdf::fileLocked'), 5);

	}

	public static function delete(Pdf $e): void {

		$e->expects(['farm', 'type', 'content']);

		if($e['type'] === Pdf::INVOICE) {
			throw new \Exception('Can not use for invoices');
		}

		Pdf::model()->beginTransaction();

			if($e['content']->notEmpty()) {
				PdfContent::model()->delete($e['content']);
			}

			Pdf::model()->delete($e);

		Pdf::model()->commit();

	}

	public static function clean() {

		// Nettoyage des PDF
		Pdf::model()
			->where('createdAt < NOW() - INTERVAL '.\Setting::get('selling\documentExpires').' MONTH')
			->whereContent('!=', NULL)
			->update([
				'content' => NULL
			]);

		// Suppression du contenu nettoyé
		PdfContent::model()
			->join(Pdf::model(), 'm1.id = m2.content', 'LEFT')
			->where('m2.content IS NULL')
			->delete();

		// Suppression de la référence au PDF dans la facture
		Invoice::model()
			->join(PdfContent::model(), 'm1.content = m2.id', 'LEFT')
			->where('m2.id IS NULL')
			->update([
				'content' => NULL
			]);

	}

}
?>
