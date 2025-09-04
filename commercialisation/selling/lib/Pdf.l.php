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
				'content' => ['hash']
			])
			->whereSale($eSale)
			->whereType($type)
			->get();

		if(
			$ePdf->notEmpty() and
			$ePdf['content']->notEmpty() and
			$ePdf['content']['hash']
		) {
			return self::getContentByPdf($ePdf['content']);
		} else {
			return NULL;
		}

	}

	public static function getContentByInvoice(Invoice $eInvoice): ?string {

		PdfContent::model()
			->select('hash')
			->get($eInvoice['content']);

		return self::getContentByPdf($eInvoice['content']);

	}

	public static function getContentByPdf(PdfContent $ePdfContent): ?string {

		$path = \storage\DriverLib::directory().'/'.new \media\PdfContentUi()->getBasenameByHash($ePdfContent['hash']);
		return file_get_contents($path);

	}

	public static function sendBySale(\farm\Farm $eFarm, Sale $eSale, string $type): void {

		$eFarm->expects([
			'name',
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

		$customerEmail = match($type) {
			Pdf::DELIVERY_NOTE => $eCustomer['deliveryNoteEmail'],
			Pdf::ORDER_FORM => $eCustomer['orderFormEmail'],
		};
		$customerEmail ??= $eCustomer['email'] ?? NULL;

		if($customerEmail === NULL) {
			Pdf::fail('noCustomerEmail');
			return;
		}

		if($eFarm['legalEmail'] === NULL) {
			Pdf::fail('noFarmEmail');
			return;
		}

		$ePdf = Pdf::model()
			->select(Pdf::getSelection())
			->select([
				'content' => ['hash']
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

				$template = NULL;

				if($eSale['type'] === Sale::PRO) {
					$customize = \mail\Customize::SALE_ORDER_FORM_PRO;
					$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_ORDER_FORM_PRO);
				}

				if($template === NULL) {
					$customize = \mail\Customize::SALE_ORDER_FORM_PRIVATE;
					$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_ORDER_FORM_PRIVATE);
				}

				$content = new PdfUi()->getOrderFormMail($eFarm, $eSale, $customize, $template);
				break;

			case Pdf::DELIVERY_NOTE :

				$template = NULL;

				if($eSale['type'] === Sale::PRO) {
					$customize = \mail\Customize::SALE_DELIVERY_NOTE_PRO;
					$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_DELIVERY_NOTE_PRO);
				}

				if($template === NULL) {
					$customize = \mail\Customize::SALE_DELIVERY_NOTE_PRIVATE;
					$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_DELIVERY_NOTE_PRIVATE);
				}

				$content = new PdfUi()->getDeliveryNoteMail($eFarm, $eSale, $customize, $template);
				break;

		}

		$libSend = new \mail\SendLib();

		if($eFarm->getSelling('documentCopy')) {
			$libSend->setBcc($eFarm['legalEmail']);
		}

		$pdf = self::getContentByPdf($ePdf['content']);

		$libSend
			->setFarm($eFarm)
			->setCustomer($eCustomer)
			->setFromName($eFarm['name'])
			->setTo($customerEmail)
			->setReplyTo($eFarm['legalEmail'])
			->setContent(...$content)
			->addAttachment($pdf, $eSale->getDeliveryNote($eFarm).'.pdf', 'application/pdf')
			->send();

	}

	public static function sendByInvoice(\farm\Farm $eFarm, Invoice $eInvoice): void {

		$eFarm->expects([
			'name',
		]);

		$eInvoice->expects([
			'customer' => ['email']
		]);

		$eCustomer = $eInvoice['customer'];
		$customerEmail = $eCustomer['invoiceEmail'] ?? $eCustomer['email'];

		if($customerEmail === NULL) {
			Pdf::fail('noCustomerEmail');
			return;
		}

		if($eFarm['legalEmail'] === NULL) {
			Pdf::fail('noFarmEmail');
			return;
		}

		$ePdfContent = $eInvoice['content'];

		if(PdfContent::model()
			->select('hash')
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

		$template = NULL;

		if($eInvoice['taxes'] === Invoice::EXCLUDING) {
			$customize = \mail\Customize::SALE_INVOICE_PRO;
			$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_INVOICE_PRO);
		}

		if($template === NULL) {
			$customize = \mail\Customize::SALE_INVOICE_PRIVATE;
			$template = \mail\CustomizeLib::getTemplateByFarm($eFarm, \mail\Customize::SALE_INVOICE_PRIVATE);
		}

		$content = new PdfUi()->getInvoiceMail($eFarm, $eInvoice, $cSale, $customize, $template);

		$libSend = new \mail\SendLib();

		if($eFarm->getSelling('documentCopy')) {
			$libSend->setBcc($eFarm['legalEmail']);
		}

		$pdf = self::getContentByPdf($ePdfContent);

		$libSend
			->setFarm($eFarm)
			->setCustomer($eCustomer)
			->setFromName($eFarm['name'])
			->setTo($customerEmail)
			->setReplyTo($eFarm['legalEmail'])
			->setContent(...$content)
			->addAttachment($pdf, $eInvoice['name'].'.pdf', 'application/pdf')
			->send();

	}

	public static function generate(string $type, Sale $eSale, ?\Closure $callback = NULL): Pdf {

		$eSale->expects(['farm']);

		$callback ??= fn() => self::build('/selling/pdf:getDocument?id='.$eSale['id'].'&type='.$type);

		$content = $callback();

		Pdf::model()->beginTransaction();

		$ePdfContent = new PdfContent();
		PdfContent::model()->insert($ePdfContent);

		$hash = NULL;
		new \media\PdfContentLib()->send($ePdfContent, $hash, $content, 'pdf');

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

		$content = FacturXLib::generate($eInvoice, self::build('/selling/pdf:getDocumentInvoice?id='.$eInvoice['id']));

		Pdf::model()->beginTransaction();

		$ePdfContent = new PdfContent();
		PdfContent::model()->insert($ePdfContent);

		$hash = NULL;
		new \media\PdfContentLib()->send($ePdfContent, $hash, $content, 'pdf');

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

	public static function buildLabels(\farm\Farm $eFarm, \Collection $cSale, string $filename): string {

		$queryString = implode('&', $cSale->toArray(fn($eSale) => 'sales[]='.$eSale['id'])).'&filename='.$filename;

		return self::build('/selling/pdf:getLabels?id='.$eFarm['id'].'&'.$queryString);

	}

	public static function build($url, ?string $filename = NULL): string {

		if(OTF_DEMO) {
			return '';
		}

		return \Cache::redis()->lock('pdf-'.$url, function() use($filename, $url) {

			$file = tempnam('/tmp', 'pdf-').'.pdf';

			$url .= str_contains($url, '?') ? '&' : '?';
			$url .= 'remoteKey='.\Lime::getRemoteKey('selling');

			$args = '"--url='.\Lime::getUrl().$url.'"';
			$args .= ' "--destination='.$file.'"';
			$args .= ' "--filename='.$filename.'"';

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
			->where('createdAt < NOW() - INTERVAL '.SellingSetting::DOCUMENT_EXPIRES.' MONTH')
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
