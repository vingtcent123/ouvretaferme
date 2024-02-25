<?php
namespace farm;

class TipLib extends TipCrud {

	public static function getList(): array {
		return array_merge(self::getPrivate(), array_keys(self::getPublic()));
	}

	public static function getPrivate(): array {
		return ['sequence-weeks'];
	}

	public static function getPublic(): array {

		return [

			'action-customize' => [

			],
			'planning-checkboxes' => [
				'minSeniority' => 10
			],
			'plant-customize' => [
				'match' => fn(\user\User $eUser, Farm $eFarm) => \plant\Plant::model()
					->whereFarm($eFarm)
					->whereFqn(NULL)
					->exists() === FALSE
			],
			'feature-rotation' => [
				'minSeniority' => 100
			],
			'feature-seeds' => [
				'minSeniority' => 10
			],
			'feature-team' => [
				'minSeniority' => 10,
				'match' => fn(\user\User $eUser, Farm $eFarm) => Farmer::model()
						->whereFarm($eFarm)
						->count() === 1
			],
			'feature-time-disable' => [
				'match' => fn(\user\User $eUser, Farm $eFarm) => ($eFarm['featureTime'] === TRUE)
			],
			'feature-tools' => [
				'minSeniority' => 15,
				'match' => fn(\user\User $eUser, Farm $eFarm) => Tool::model()
						->whereFarm($eFarm)
						->exists() === FALSE
			],
			'feature-website' => [
				'minSeniority' => 20,
				'match' => fn(\user\User $eUser, Farm $eFarm) => \website\Website::model()
					->whereFarm($eFarm)
					->exists() === FALSE
			],
			'selling-market' => [
				'minSeniority' => 15,
				'match' => fn(\user\User $eUser, Farm $eFarm) => \selling\Sale::model()
						->whereFarm($eFarm)
						->whereMarket(TRUE)
						->exists() === FALSE
			],
			'selling-pdf' => [
				'minSeniority' => 15,
				'match' => fn(\user\User $eUser, Farm $eFarm) => \selling\Pdf::model()
						->whereFarm($eFarm)
						->exists() === FALSE
			],
			'selling-shop' => [
				'minSeniority' => 15,
				'match' => fn(\user\User $eUser, Farm $eFarm) => \shop\Shop::model()
						->whereFarm($eFarm)
						->exists() === FALSE
			],
			'series-duplicate' => [
				'minSeniority' => 50
			],
			'series-harvest' => [
				'minSeniority' => 5
			],
			'series-forecast' => [
				'minSeniority' => 3,
				'match' => fn(\user\User $eUser, Farm $eFarm) => \plant\Forecast::model()
						->whereFarm($eFarm)
						->exists() === FALSE
			],
			'blog' => [
				'minSeniority' => 8
			]

		];

	}

	public static function pickPosition(\user\User $eUser): string {

		$eTip = Tip::model()
			->select('pickPosition')
			->whereUser($eUser)
			->get();

		if($eTip->empty()) {

			$position = 0;

			$eTip = new Tip([
				'user' => $eUser
			]);

			Tip::model()
				->option('add-replace')
				->insert($eTip);

		} else {

			$position = $eTip['pickPosition'] + 1;

			Tip::model()
				->whereUser($eUser)
				->update([
					'pickPosition' => new \Sql('pickPosition + 1')
				]);

		}

		$list = array_keys(self::getPublic());
		$id = $list[$position % count($list)];

		self::changeStatus($eUser, $id, 'shown', FALSE);

		return $id;

	}

	public static function pickOne(\user\User $eUser, string $id): ?string {
		return self::changeStatus($eUser, $id, 'shown', FALSE) ? $id : NULL;
	}

	public static function pickRandom(\user\User $eUser, Farm $eFarm): ?string {

		if($eUser['id'] !== 1) {

			if(
				$eUser['seniority'] >= 200 or
				$eUser['seniority'] < 2
			) {
				return NULL;
			}

			if(\session\SessionLib::exists('tip')) {
				return NULL;
			}

			\session\SessionLib::set('tip', TRUE);

		}

		if($eFarm->isRole(Farmer::OWNER) === FALSE) {
			return NULL;
		}

		$tips = self::getPublic();

		$eTip = Tip::model()
			->select('list', 'lastSeniority')
			->whereUser($eUser)
			->get();

		if($eTip->notEmpty()) {

			// Maximum une astuce tous les deux jours
			if($eUser['id'] !== 1) {

				if($eUser['seniority'] < $eTip['lastSeniority'] + 2) {
					return NULL;
				}

			}

			foreach($eTip['list'] as $id => $status) {
				unset($tips[$id]);
			}

		}

		foreach($tips as $id => $condition) {

			if(isset($condition['minSeniority'])) {

				if($condition['minSeniority'] >= $eUser['seniority']) {
					unset($tips[$id]);
				}

			}

		}

		if($tips) {

			$ids = array_keys($tips);
			shuffle($ids);
			$id = first($ids);

			if(
				isset($tips[$id]['match']) and
				$tips[$id]['match']($eUser, $eFarm) === FALSE
			) {
				self::changeStatus($eUser, $id, 'unmatched');
				return NULL;
			} else {
				self::changeStatus($eUser, $id, 'shown');
				return $id;
			}

		} else {
			return NULL;
		}

	}

	public static function changeStatus(\user\User $eUser, string $id, string $newStatus, bool $override = TRUE): bool {

		if(in_array($newStatus, ['shown', 'closed', 'clicked', 'unmatched']) === FALSE) {
			throw new \Exception('Unknown status');
		}

		Tip::model()->beginTransaction();

		$eTip = Tip::model()
			->select('list')
			->whereUser($eUser)
			->get();

		$list = $eTip->empty() ? [] : $eTip['list'];

		if($override) {
			$list[$id] = $newStatus;
		} else {

			if(array_key_exists($id, $list)) {
				Tip::model()->rollBack();
				return FALSE;
			}

			$list += [
				$id => $newStatus
			];

		}

		$values = array_count_values($list);
		$values['list'] = $list;
		$values['lastSeniority'] = $eUser['seniority'];

		if($eTip->empty()) {

			$eTip = new Tip([
				'user' => $eUser
			] + $values);

			Tip::model()
				->option('add-replace')
				->insert($eTip);

		} else {

			Tip::model()
				->whereUser($eUser)
				->update($values);

		}

		Tip::model()->commit();

		return TRUE;


	}

}
?>