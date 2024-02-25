<?php
/**
 * Template pour PDF
 */
class PdfTemplate extends Template {

	protected $lang = 'fr';

	public function __construct() {

		parent::__construct();

		\Asset::css('util', 'font-open-sans.css');
		\Asset::css('main', 'design.css');
		\Asset::css('main', 'pdf.css');

	}

	public function build(Closure $callback): mixed {

		ob_start();
		$callback->call($this);
		$stream = ob_get_clean();

		return $this->buildHtml($stream);

	}

	protected function buildHtml(string $stream): string {

		$h = '<!DOCTYPE html>';
		$h .= '<html lang="'.$this->lang.'">';

		$h .= '<head>';
			$h .= '<base href="'.\Lime::getProtocol().'://'.SERVER('HTTP_HOST').'">';
			$h .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
			$h .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
			$h .= Asset::importHtml();
		$h .= '</head>';

			$h .= '<body class="template-pdf">';
				$h .= $stream;
			$h .= '</body>';

		$h .= '</html>';

		return $h;

	}

}

?>
