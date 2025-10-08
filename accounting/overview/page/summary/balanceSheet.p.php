<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'detailed' => GET('detailed'),
	], GET('sort'));

})
	->get('index', function($data) {

		$data->cOperation = \overview\BalanceSheetLib::getData($data->eFinancialYear);

		if($data->search->get('detailed')) {
			$data->cOperationDetail = \overview\BalanceSheetLib::getDetailData($data->eFinancialYear);
		} else {
			$data->cOperationDetail = new Collection();
		}

		// Attention, le résultat doit être calculé en live SI l'exercice comptable n'est pas encore clôturé (= pas d'entrée 12x dans les écritures)
		if($data->eFinancialYear->canUpdate()) {
			$data->result = \overview\IncomeStatementLib::computeResult($data->eFinancialYear);
		} else {
			$data->result = NULL;
		}

		$threeNumbersClasses = $data->cOperation->getColumn('class');
		$twoNumbersClasses = array_map(fn($class) => substr($class, 0, 2), $threeNumbersClasses);
		// Si certaines classes exactes existent (pour le détail), les prendre
		$completeNumbersClasses = array_map(fn($class) => trim($class, '0'), $data->cOperationDetail->getColumn('class'));
		$classes = array_unique(array_merge(
			$threeNumbersClasses, $twoNumbersClasses, $completeNumbersClasses,
			[\account\AccountSetting::PROFIT_CLASS, \account\AccountSetting::LOSS_CLASS, \account\AccountSetting::RESULT_CLASS])
		);

		$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

		throw new ViewAction($data);

	});
?>
