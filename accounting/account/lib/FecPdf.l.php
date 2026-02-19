<?php
namespace account;

Class FecPdfLib {

	public static function generateAttestation(\farm\Farm $eFarm): string {

		$footer = \account\PdfUi::getFooter();
		$title = new \account\PdfUi()->getName(new FinancialYear(), 'fec-attestation');

		return self::build(\Lime::getUrl().\farm\FarmUi::urlConnected($eFarm).'/account/financialYear/pdf:attestation?', $title, $footer, NULL);

	}

	private static function build(string $url, ?string $title, ?string $footer, ?bool $landscape): string {

		if(OTF_DEMO) {
			return '';
		}

		return \Cache::redis()->lock(
			'pdf-'.$url, function () use ($footer, $title, $url, $landscape) {

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
			if($landscape !== NULL) {
				$args .= ' "--landscape=1"';
			}

			if(LIME_ENV === 'dev') {
				//dd('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');
			}

			exec('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');

			$content = file_get_contents($file);

			unlink($file);

			return $content;

		}, fn() => throw new \FailAction('account\FecPdf::fileLocked'), 5);

	}


}
