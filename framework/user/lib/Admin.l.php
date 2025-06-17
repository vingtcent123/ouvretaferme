<?php
namespace user;

/**
 * Users admin
 */
class AdminLib {

	/**
	 * Get all users
	 *
	 */
	public static function getUsers(int $page, \Search $search): array {

		$number = 100;
		$position = $page * $number;

		User::model()
			->select([
				'id',
				'firstName', 'lastName', 'visibility',
				'vignette',
				'createdAt', 'ping', 'email', 'status',
				'role' => ['name', 'emoji'],
				'auths' => \user\UserAuth::model()
					->select('type')
					->delegateCollection('user'),

			]);

		if($search->get('lastName')) {
			User::model()->whereLastName('LIKE', '%'.$search->get('lastName').'%');
		}

		if($search->get('email')) {
			User::model()->whereEmail('LIKE', '%'.$search->get('email').'%');
		}

		if($search->get('id')) {
			User::model()->whereId($search->get('id'));
		}

		$search->validateSort(['id', 'email', 'lastName', 'ping'], 'id-');

		User::model()
			->sort($search->buildSort())
			->option('count');

		$cUser = User::model()->getCollection($position, $number);

		return [$cUser, User::model()->found()];

	}
	
}
?>
