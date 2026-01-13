<?php
namespace account;

class PdfUi {

	public function __construct() {


	}

	public function getFilename(\account\FinancialYear $eFinancialYear, string $type): string {

		return $this->getName($eFinancialYear, $type);

	}

	public function getName(\account\FinancialYear $eFinancialYear, string $type): string {

		return match($type) {
			\account\Pdf::FINANCIAL_YEAR_OPENING => s("bilan-ouverture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			\account\Pdf::FINANCIAL_YEAR_CLOSING => s("bilan-cloture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
		};

	}

	public function getTitle(string $type): string {

		return match($type) {
			\account\Pdf::FINANCIAL_YEAR_OPENING => s("Bilan d'ouverture"),
			\account\Pdf::FINANCIAL_YEAR_CLOSING => s("Bilan de clôture"),
		};

	}

	public static function getHeader(\farm\Farm $eFarm, string $title, \account\FinancialYear $eFinancialYear): string {

		$borderColor = '#D5D5D5';
		$h = '<style>
        html {
          -webkit-print-color-adjust: exact;
          font-family: "Open Sans", sans-serif;
          font-weight: 400;
          color: #212529;
          line-height: 1;
        }
        body.template-pdf {
        	line-height: 1;
        }
				.pdf-document-header {
					display: grid;
					grid-column-gap: 1rem;
					grid-template-columns: 2fr 1fr;
					overflow: hidden;
					margin: 1cm auto;
					border-radius: 0.15cm;
					border: 1px solid '.$borderColor.';
					background-color: #F5F7F5FF;
					width: 19cm;
					height: 3cm;
					font-size: 12px;
					align-content: center;
				}
				.pdf-document-header > div > h2 {
					margin: 0.5cm auto 0.25cm;
				}
				.pdf-document-header > div > h3 {
					margin-bottom: 0.1cm;
				}
				.pdf-document-header > div > * {
					text-align: center;
					margin: auto;
				}
				.pdf-document-header-details > table {
			    width: 100%;
			    line-height: 1.4;
				}
        .td-content {
        	background-color: white;
        	border: 1px solid #d5d5d5;
        	text-align: center;
        	width: 40%;
        }
				.pdf-document-title {
			    align-content: center;
			    font-weight: bold;
			    text-align: center;
			    margin: auto;
			    font-size: 0.8cm;
				}
        </style>';
		$h .= '<div class="pdf-document-header">';

			$h .= '<div>';
				$h .= '<h2 class="pdf-document-title">'.$title.'</h2>';
				$h .= '<h3>'.encode($eFarm['legalName'] ?? $eFarm['name']).'</h3>';
				if($eFarm['siret'] !== NULL) {
					$h .= '<div>'.encode($eFarm['siret']).'</div>';
				}
			$h .= '</div>';

			$h .= '<div class="pdf-document-header-details">';

				$h .= '<table>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Devise").'</td>';
						$h .= '<td class="td-content">'.s("EURO").'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td></td>';
						$h .= '<td style="text-align: center; font-weight: bold;">'.s("EXERCICE").'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Du").'</td>';
						$h .= '<td class="td-content">'.\util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Au").'</td>';
						$h .= '<td class="td-content">'.\util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE).'</td>';
					$h .= '</tr>';
				$h .= '</table>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function getFooter(): string {

		$date = \util\DateUi::numeric(date('Y-m-d H:i:s'));

		$h = '<style>
        html {
          -webkit-print-color-adjust: exact;
          font-family: "Open Sans", sans-serif;
          font-weight: 400;
          color: #212529;
          line-height: 1;
        }
        .footer-container {
					display: grid;
					grid-column-gap: 1rem;
					grid-template-columns: 1fr 1fr 1fr;
					margin: 0 auto; 
					width: 18cm; 
					font-size: 12px; 
        }
        a {
        	color: inherit;
        	text-decoration: none;
        }
      </style>';
		$h .= '<div class="footer-container">';
			$h .= '<span>'.$date.'</span>';
			$h .= '<span class="pageNumber" style="text-align: center;"></span>';
			$h .= '<span style="text-align: end;"><a href="'.\Lime::getUrl().'">'.\Lime::getName().'</a></span>';
		$h .= '</div>';

		return $h;
	}


}

?>
