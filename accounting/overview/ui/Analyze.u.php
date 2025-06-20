<?php
namespace overview;

class AnalyzeUi {

	public static function getTitle(\farm\Farm $eFarm): string {

		$categories = new \company\CompanyUi()->getAnalyzeCategories($eFarm);
		$selectedView = \Setting::get('main\viewAnalyze');

		return \main\MainUi::getDropdownMenuTitle($categories, $selectedView);

	}
}
?>
