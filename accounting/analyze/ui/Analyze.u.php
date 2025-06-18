<?php
namespace analyze;

class AnalyzeUi {

	public static function getTitle(\company\Company $eCompany): string {

		$categories = new \company\CompanyUi()->getAnalyzeCategories($eCompany);
		$selectedView = \Setting::get('main\viewAnalyze');

		return \main\MainUi::getDropdownMenuTitle($categories, $selectedView);

	}
}
?>
