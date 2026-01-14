<?php
namespace overview;

Class PdfLib {

	public static function getContentByPdf(\account\PdfContent $ePdfContent): ?string {

		$path = \storage\DriverLib::directory().'/'.new \media\PdfContentUi()->getBasenameByHash($ePdfContent['hash']);

		return file_get_contents($path);

	}

	public static function build(string $url, ?string $title, ?string $header, ?string $footer): string {

		if(OTF_DEMO) {
			return '';
		}

		return \Cache::redis()->lock(
			'pdf-'.$url, function () use ($header, $footer, $title, $url) {

			$file = tempnam('/tmp', 'pdf-').'.pdf';

			$url .= str_contains($url, '?') ? '&' : '?';
			$url .= 'remoteKey='.\Lime::getRemoteKey('accounting');

			$args = '"--url='.$url.'"';
			$args .= ' "--destination='.$file.'"';
			if($title !== NULL) {
				$args .= ' "--title='.rawurlencode($title).'"';
			}
			if($header !== NULL) {
				$args .= ' "--header='.rawurlencode($header).'"';
			}
			if($footer !== NULL) {
				$args .= ' "--footer='.rawurlencode($footer).'"';
			}

			exec('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');

			if(LIME_ENV === 'dev') {
				//d('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');
			}

			$content = file_get_contents($file);

			unlink($file);

			return $content;

		}, fn() => throw new \FailAction('overview\Pdf::fileLocked'), 5);

	}

	public static function generate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type): \account\Pdf {

		\account\Pdf::model()->beginTransaction();

			$ePdf = new \account\Pdf([
				'name' => new \account\PdfUi()->getName($eFinancialYear, $type),
				'type' => $type,
				'financialYear' => $eFinancialYear,
				'createdAt' => \account\Pdf::model()->now()
			]);

			\account\Pdf::model()
				->whereFinancialYear($eFinancialYear)
				->whereType($type)
				->delete();

			\account\Pdf::model()->insert($ePdf);

		\account\Pdf::model()->commit();

		// Pour le bilan d'ouverture on récupère le bilan de clôture de l'exercice précédent.
		$eFarm['eFinancialYear'] = $eFinancialYear;

		$document = match($type) {
			\account\FinancialYearDocumentLib::BALANCE => 'balanceSheet',
			\account\FinancialYearDocumentLib::OPENING => 'balanceSheet',
			\account\FinancialYearDocumentLib::OPENING_DETAILED => 'balanceSheet',
			\account\FinancialYearDocumentLib::CLOSING => 'balanceSheet',
			\account\FinancialYearDocumentLib::CLOSING_DETAILED => 'balanceSheet',
			\account\FinancialYearDocumentLib::INCOME_STATEMENT => 'incomeStatement',
			\account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => 'incomeStatement',
			\account\FinancialYearDocumentLib::SIG => 'sig',
			default => throw new \Exception('Unknown document type'),
		};

		$title = new \account\PdfUi()->getTitle($type, $eFinancialYear->isClosed() === FALSE);
		$footer = \account\PdfUi::getFooter();
		$header = \account\PdfUi::getHeader($eFarm, $title, $eFinancialYear);

		$ePdfContent = self::generateDocument(
			self::build(\Lime::getUrl().\company\CompanyUi::urlFarm($eFarm).'/overview/pdf/'.$document.'&type='.$type, $title, $header, $footer)
		);

		\account\Pdf::model()->update($ePdf, [
			'content' => $ePdfContent
		]);

		$ePdf['content'] = $ePdfContent;

		return $ePdf;

	}
	public static function generateDocument($content): \account\PdfContent {

		$ePdfContent = new \account\PdfContent();
		\account\PdfContent::model()->insert($ePdfContent);

		$hash = NULL;
		new \media\PdfContentLib()->send($ePdfContent, $hash, $content, 'pdf');

		return $ePdfContent;

	}
}
