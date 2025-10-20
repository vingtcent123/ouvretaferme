<?php
namespace overview;

Class VatUi {

	public function __construct() {
	}

	public function getTitle(\Collection $cFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("La TVA");
			$h .= '</h1>';

			if($cFinancialYear->count() > 1) {
				$h .= '<div>';
					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#vat-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

}

?>
