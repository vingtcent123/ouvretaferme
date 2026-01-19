<?php
namespace overview;

Class PdfLib {

	public static function getContentByPdf(\account\PdfContent $ePdfContent): ?string {

		$path = \storage\DriverLib::directory().'/'.new \media\PdfContentUi()->getBasenameByHash($ePdfContent['hash']);

		return file_get_contents($path);

	}

	public static function build(string $url, ?string $title, ?string $footer): string {

		if(OTF_DEMO) {
			return '';
		}

		return \Cache::redis()->lock(
			'pdf-'.$url, function () use ($footer, $title, $url) {

			$file = tempnam('/tmp', 'pdf-').'.pdf';

			$url .= str_contains($url, '?') ? '&' : '?';
			$url .= 'remoteKey='.\Lime::getRemoteKey('accounting');

			$args = '"--url='.$url.'"';
			$args .= ' "--destination='.$file.'"';

			if($footer !== NULL) {
				$args .= ' "--footer='.rawurlencode($footer).'"';
			}
			$args .= ' "--header= "';
			if($title !== NULL) {
				$args .= ' "--title='.rawurlencode($title).'"';
			}

			if(LIME_ENV === 'dev') {
				//dd('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');
			}

			exec('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');

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

		$ePdfContent = new \account\PdfContent();
		\account\PdfContent::model()->insert($ePdfContent);

		$content = self::generateDocument($eFarm, $eFinancialYear, $type);

		$hash = NULL;
		new \media\PdfContentLib()->send($ePdfContent, $hash, $content, 'pdf');

		\account\Pdf::model()->update($ePdf, [
			'content' => $ePdfContent
		]);

		$ePdf['content'] = $ePdfContent;

		return $ePdf;

	}

	public static function generateDocument(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type): string {

		// Pour le bilan d'ouverture on récupère le bilan de clôture de l'exercice précédent.
		$eFarm['eFinancialYear'] = $eFinancialYear;

		$document = match($type) {
			\account\FinancialYearDocumentLib::BALANCE_SHEET => '/overview/pdf/balanceSheet',
			\account\FinancialYearDocumentLib::OPENING => '/overview/pdf/balanceSheet',
			\account\FinancialYearDocumentLib::OPENING_DETAILED => '/overview/pdf/balanceSheet',
			\account\FinancialYearDocumentLib::CLOSING => '/overview/pdf/balanceSheet',
			\account\FinancialYearDocumentLib::CLOSING_DETAILED => '/overview/pdf/balanceSheet',
			\account\FinancialYearDocumentLib::INCOME_STATEMENT => '/overview/pdf/incomeStatement',
			\account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => '/overview/pdf/incomeStatement',
			\account\FinancialYearDocumentLib::SIG => '/overview/pdf/sig',
			\account\FinancialYearDocumentLib::ASSET_AMORTIZATION => '/asset/pdf:amortization',
			\account\FinancialYearDocumentLib::ASSET_ACQUISITION => '/asset/pdf:acquisition',
			\account\FinancialYearDocumentLib::BALANCE => '/journal/pdf:balance',
			\account\FinancialYearDocumentLib::BALANCE_DETAILED => '/journal/pdf:balance',
			default => throw new \Exception('Unknown document type'),
		};

		$footer = \account\PdfUi::getFooter();
		$title = new \account\PdfUi()->getName($eFinancialYear, $type);

		return self::build(\Lime::getUrl().\company\CompanyUi::urlFarm($eFarm).$document.'?type='.$type, $title, $footer);

	}
}
