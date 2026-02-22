<?php
namespace company;

Class CompanyCronLib extends CompanyCronCrud {

	const RECONCILIATE = 'reconciliate';
	const FEC_IMPORT = 'fec-import';
	const FINANCIAL_YEAR_GENERATE_DOCUMENT = 'financial-year-generate-document';
	const SUPER_PDP_INITIALIZE = 'super-pdp-initialize';

	public static function addConfiguration(\farm\Farm $eFarm, string $action, string $status, ?int $id = NULL): void {

		$eCompanyCron = new CompanyCron(['farm' => $eFarm, 'action' => $action, 'status' => $status, 'element' => $id]);

		CompanyCron::model()->insert($eCompanyCron);

	}
	public static function updateConfiguration(\farm\Farm $eFarm, string $action, string $status, ?int $id = NULL): void {

		CompanyCron::model()
			->whereFarm($eFarm)
			->whereAction($action)
			->whereElement($id)
			->update(['status' => $status]);

	}

}
