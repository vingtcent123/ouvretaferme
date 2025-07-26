<?php
namespace pdf;

class PdfLib extends \pdf\PdfCrud {

	public static function build(string $url, ?string $title, ?string $header, ?string $footer): string {

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
				d('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');
			}

			$content = file_get_contents($file);

			unlink($file);

			return $content;

		}, fn() => throw new \FailAction('journal\Pdf::fileLocked'), 5
		);

	}

	private static function generateContent(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type, ?string $more): string {

		switch($type) {
			case PdfElement::OVERVIEW_BALANCE_SUMMARY;
				$url = \company\CompanyUi::urlOverview($eFarm).'/pdf/balance:summary?financialYear='.$eFinancialYear['id'];
				$title = \overview\PdfUi::getTitle();
				break;

			case PdfElement::OVERVIEW_BALANCE_OPENING;
				$url = \company\CompanyUi::urlOverview($eFarm).'/pdf/balance:opening?financialYear='.$eFinancialYear['id'];
				$title = \overview\PdfUi::getBalanceOpeningTitle();
				break;

			case PdfElement::JOURNAL_INDEX:
				$url = \company\CompanyUi::urlJournal($eFarm).'/pdf/?financialYear='.$eFinancialYear['id'];
				$title = \journal\PdfUi::getJournalTitle();
				break;

			case PdfElement::JOURNAL_BOOK:
				$url = \company\CompanyUi::urlJournal($eFarm).'/pdf/book?financialYear='.$eFinancialYear['id'];
				$title = \journal\PdfUi::getBookTitle();
				break;

			case PdfElement::JOURNAL_TVA_BUY:
				$url = \company\CompanyUi::urlJournal($eFarm).'/pdf/vat?financialYear='.$eFinancialYear['id'].'&type=buy';
				$title = \journal\PdfUi::getVatTitle(PdfElement::JOURNAL_TVA_BUY);
				break;

			case PdfElement::JOURNAL_TVA_SELL:
				$url = \company\CompanyUi::urlJournal($eFarm).'/pdf/vat?financialYear='.$eFinancialYear['id'].'&type=sell';
				$title = \journal\PdfUi::getVatTitle(PdfElement::JOURNAL_TVA_SELL);
				break;

			case PdfElement::VAT_STATEMENT:
				$url = \company\CompanyUi::urlJournal($eFarm).'/pdf/vatDeclaration?id='.$more;
				$title = \journal\PdfUi::getVatDeclarationTitle();
				break;

			default:
				throw new \NotExpectedAction('Unknown pdf type');
		}
		$footer = PdfUi::getFooter();
		$header = PdfUi::getHeader($title, $eFinancialYear);

		if(strpos($url, '?') === FALSE) {
			$url .= '?';
		} else {
			$url .= '&';
		}
		$url .= 'remoteKey='.\Lime::getRemoteKey('accounting');

		return self::build($url, $title, $header, $footer);

	}

	public static function generate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type, ?string $more = NULL): ?string {

		if($eFinancialYear['status'] === \account\FinancialYearElement::CLOSE) {

			$ePdf = Pdf::model()
				->select(Pdf::getSelection() + ['content' => Content::getSelection()])
				->whereFinancialYear($eFinancialYear)
				->whereType($type)
				->get();

			if(
				$ePdf->notEmpty() and
				$ePdf['content']->notEmpty() and
				$ePdf['content']['hash']
			) {

				$ePdf['used'] = new \Sql('used + 1');

				Pdf::model()
					->select(['used'])
					->update($ePdf);

				return self::getContentByPdf($ePdf['content']);
			}
		}

		try {

			$content = self::generateContent($eFarm, $eFinancialYear, $type, $more);

		} catch(\Exception) {

			return NULL;

		}

		if($eFinancialYear['status'] === \account\FinancialYearElement::CLOSE) {

			\pdf\Pdf::model()->beginTransaction();

			$eContent = new \pdf\Content();
			\pdf\Content::model()->insert($eContent);

			$hash = NULL;
			new \media\PdfContentLib()->send($eContent, $hash, $content, 'pdf');

			$ePdf = new \pdf\Pdf(['content' => $eContent, 'type' => $type, 'financialYear' => $eFinancialYear]);

			\pdf\Pdf::model()
			   ->option('add-replace')
			   ->insert($ePdf);

			\pdf\Pdf::model()->commit();

		}

		return $content;
	}

	public static function getContentByPdf(Content $eContent): ?string {

		$path = \storage\DriverLib::directory().'/'.new \media\PdfContentUi()->getBasenameByHash($eContent['hash']);
		return file_get_contents($path);

	}

}
?>
