<?php
namespace bank;

class BankUi {

	public function __construct() {
	}

	public function getBankTitle(\accounting\FinancialYear $eFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Les opérations bancaires");
			$h .= '</h1>';

			if($eFinancialYear->notEmpty()) {

				$h .= '<div>';
					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#cashflow-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function bankLabel(): string {

		return s("Banque");

	}

	public function cashLabel(): string {

		return s("Caisse");

	}

}
?>
