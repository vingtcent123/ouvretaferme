<?php
namespace mail;

class CampaignLib extends CampaignCrud {

	public static function getPropertiesCreate(): array {

		return ['email'];

	}

	public static function countByFarm(\farm\Farm $eFarm): int {

		return Campaign::model()
			->whereFarm($eFarm)
			->count();

	}

	public static function getByFarm(\farm\Farm $eFarm, ?int $page = NULL): \Collection {

		$number = ($page === NULL) ? NULL : 100;
		$position = ($page === NULL) ? NULL : $page * $number;

		return Campaign::model()
			->select(Campaign::getSelection())
			->whereFarm($eFarm)
			->getCollection($position, $number);

	}

	public static function synchronizeCustomer(\selling\Customer $eCustomer): void {

		$active = \selling\Customer::model()
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->whereStatus(\selling\Customer::ACTIVE)
			->exists();

		Campaign::model()
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->update([
				'activeCustomer' => $active
			]);

	}

}
?>
