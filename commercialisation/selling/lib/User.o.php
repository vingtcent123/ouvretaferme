<?php
namespace selling;

class UserObserverLib {

	public static function signUpCreate(\user\User $eUser) {

		Customer::model()
			->whereEmail($eUser['email'])
			->update([
				'user' => $eUser,
			]);

	}

	/**
	 * Lorsque l'utilisateur est mis à jour, modifier ses données clients particulier
	 */
	public static function update(\user\User $eUser, array $properties): void {

		$values = [];

		if(in_array('firstName', $properties) or in_array('lastName', $properties)) {

			$eUser->expects(['firstName', 'lastName']);

			$values['firstName'] = $eUser['firstName'];
			$values['lastName'] = $eUser['lastName'];
			$values['name'] = CustomerLib::getNameFromUser($eUser);

		}

		if(in_array('phone', $properties)) {
			$values['phone'] = $eUser['phone'];
		}

		if(in_array('email', $properties)) {
			$values['email'] = $eUser['email'];
		}

		foreach(['invoice', 'delivery'] as $type) {

			$address = [$type.'Street1', $type.'Street2', $type.'Postcode', $type.'City', $type.'Country'];

			if(array_intersect($address, $properties)) {

				$eUser->expects($address);

				foreach($address as $property) {
					$values[$property] = $eUser[$property];
				}

			}

		}

		if($values === []) {
			return;
		}

		Customer::model()
			->whereUser($eUser)
			->update($values);

	}

}
?>
