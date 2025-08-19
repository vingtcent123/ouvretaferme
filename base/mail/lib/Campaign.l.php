<?php
namespace mail;

class CampaignLib extends CampaignCrud {

	public static function getPropertiesCreate(): array {

		return ['scheduledAt', 'subject', 'content', 'to'];

	}

	public static function getPropertiesUpdate(): array {

		return ['scheduledAt', 'subject', 'content', 'to'];

	}

	public static function create(Campaign $e): void {

		$e['scheduled'] = count($e['to']);

		parent::create($e);

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
			->sort([
				'scheduledAt' => SORT_DESC,
				'id' => SORT_DESC
			])
			->getCollection($position, $number);

	}

	public static function getLastByFarm(\farm\Farm $eFarm, string $source): \Collection {

		return Campaign::model()
			->select(Campaign::getSelection())
			->whereFarm($eFarm)
			->whereSource($source)
			->sort([
				'scheduledAt' => SORT_DESC,
				'id' => SORT_DESC
			])
			->getCollection(0, 5);

	}

	public static function getLimits(): \Collection {
/*
		return Campaign::model()
			->select(Campaign::getSelection())
			->whereFarm($eFarm)
			->getCollection($position, $number);
*/
	}

}
?>
