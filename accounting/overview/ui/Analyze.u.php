<?php
namespace overview;

class AnalyzeUi {

	public static function getTitle(\farm\Farm $eFarm, string $selectedView): string {

		$categories = new \company\CompanyUi()->getAnalyzeCategories($eFarm);

		return \company\CompanyUi::getDropdownMenuTitle($categories, $selectedView);

	}
}
?>
