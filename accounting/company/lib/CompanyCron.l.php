<?php
namespace company;

Class CompanyCronLib extends CompanyCronCrud {

	const RECONCILIATE = 'reconciliate';
	const FEC_IMPORT = 'fec-import';

	public static function addConfiguration(\farm\Farm $eFarm, string $action, string $status): void {

		$eCompanyCron = new CompanyCron(['farm' => $eFarm, 'action' => $action, 'status' => $status]);

		CompanyCron::model()->insert($eCompanyCron);

	}

}
