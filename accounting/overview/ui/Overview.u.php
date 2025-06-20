<?php
namespace overview;

class OverviewUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
	}

	public function getTitle(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$categories = \company\CompanyUi::getOverviewCategories($eFarm);
		$selectedView = \Setting::get('main\viewOverview');

		return \main\MainUi::getDropdownMenuTitle($categories, $selectedView);

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
