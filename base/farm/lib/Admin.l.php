<?php
namespace farm;

/**
 * Farms admin
 */
class AdminLib {

	/**
	 * Get all farms
	 *
	 */
	public static function getFarms(int $page, \Search $search): array {

		$number = 100;
		$position = $page * $number;

		if($search->get('id')) {
			Farm::model()->whereId($search->get('id'));
		}

		if($search->get('membership')) {
			Farm::model()->whereMembership($search->get('membership'));
		}

		if($search->get('user')) {

			$cUser = \user\UserLib::getFromQuery($search->get('user'));

			$cFarmOwned = FarmLib::getByUsers($cUser);

			Farm::model()->whereId('IN', $cFarmOwned);

		}

		if($search->get('userId')) {

			$eUser = \user\UserLib::getById($search->get('userId'));

			$cFarmOwned = FarmLib::getByUser($eUser);

			Farm::model()->whereId('IN', $cFarmOwned);

		}

		if($search->get('name')) {
			Farm::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		$properties = Farm::getSelection();
		$properties['cFarmer'] = Farmer::model()
			->select([
				'farmGhost',
				'user' => ['firstName', 'lastName', 'visibility'],
				'role'
			])
			->whereStatus(Farmer::IN)
			->delegateCollection('farm');

		$search->validateSort(['name', 'id']);

		Farm::model()
			->select($properties)
			->sort($search->buildSort())
			->option('count');

		$cFarm = Farm::model()->getCollection($position, $number);

		return [$cFarm, Farm::model()->found()];

	}
	
}
?>
