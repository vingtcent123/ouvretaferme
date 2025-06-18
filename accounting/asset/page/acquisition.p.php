<?php
new Page()
	->get('index', function($data) {

		\Setting::set('main\viewAsset', 'acquisition');

		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canView');

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
?>
