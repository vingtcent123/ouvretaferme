<?php
namespace company;

Class CompanyCronLib extends CompanyCronCrud {

	const RECONCILIATE = 'reconciliate';
	const FEC_IMPORT = 'fec-import';
	const FINANCIAL_YEAR_GENERATE_OPENING = 'financial-year-generate-opening';
	const FINANCIAL_YEAR_GENERATE_CLOSING = 'financial-year-generate-closing';

	public static function addConfiguration(\farm\Farm $eFarm, string $action, string $status, ?int $id = NULL): void {

		$eCompanyCron = new CompanyCron(['farm' => $eFarm, 'action' => $action, 'status' => $status, 'element' => $id]);

		CompanyCron::model()->insert($eCompanyCron);

	}

}
