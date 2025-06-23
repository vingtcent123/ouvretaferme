<?php
namespace overview;

class OverviewUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
	}

	public function getFinancialsTitle(\farm\Farm $eFarm, string $selectedView): string {

		$categories = $this->getFinancialsCategories($eFarm);
		return $this->getDropdownMenuTitle($categories, $selectedView);

	}

	public function getStatementsTitle(\farm\Farm $eFarm, string $selectedView): string {

		$categories = $this->getStatementsCategories($eFarm);
		return $this->getDropdownMenuTitle($categories, $selectedView);

	}

	public function getFinancialsCategories(\farm\Farm $eFarm): array {

		return [
			'bank' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:bank',
				'label' => s("Trésorerie"),
				'longLabel' => s("Suivi de la trésorerie"),
			],
			'charges' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:charges',
				'label' => s("Charges"),
				'longLabel' => s("Suivi des charges"),
			],
			'result' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:result',
				'label' => s("Résultat"),
				'longLabel' => s("Suivi du résultat"),
			],
		];

	}

	public function getStatementsCategories(\farm\Farm $eFarm): array {

		return [
			\farm\Farmer::BALANCE_SHEET => [
				'url' => CompanyUi::urlOverview($eFarm).'/statements:bilans',
				'label' => s("Bilans"),
				'longLabel' => s("Les bilans"),
			],
			\farm\Farmer::TRIAL_BALANCE => [
				'url' => CompanyUi::urlOverview($eFarm).'/statements:balances',
				'label' => s("Balances"),
				'longLabel' => s("Les balances"),
			],
		];

	}


	public function getDropdownMenuTitle(array $categories, string $selectedView): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';

				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView]['longLabel'].' '.'<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
				$h .= '</a>';

				$h .= '<div class="dropdown-list bg-primary">';

					foreach($categories as $category => $categoryData) {
						$h .= '<a href="'.$categoryData['url'].'" class="dropdown-item '.($category === $selectedView ? 'selected' : '').'">'.$categoryData['longLabel'].'</a> ';
					}

				$h .= '</div>';

			$h .= '</h1>';

		$h .= '</div>';

		return $h;
	}
	public function number(mixed $number, ?string $valueIfEmpty, ?int $decimals = NULL): string {

		if(is_null($number) === true or $number === 0 or $number === 0.0) {

			if(is_null($valueIfEmpty) === FALSE) {
				return $valueIfEmpty;
			}

			return number_format(0, $decimals ?? 2, '.', ' ');

		}

		return number_format($number, $decimals ?? 2, '.', ' ');

	}

}
?>
