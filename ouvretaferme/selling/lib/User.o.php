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

			$values['name'] = CustomerLib::getNameFromUser($eUser);

		}

		if(in_array('phone', $properties)) {
			$values['phone'] = $eUser['phone'];
		}

		if(in_array('email', $properties)) {
			$values['email'] = $eUser['email'];
		}

		$address = ['street1', 'street2', 'postcode', 'city'];

		if(array_intersect($address, $properties)) {

			$eUser->expects($address);

			foreach($address as $property) {
				$values['delivery'.ucfirst($property)] = $eUser[$property];
			}

		}

		if($values === []) {
			return;
		}

		Customer::model()
			->whereType(Customer::PRIVATE)
			->whereUser($eUser)
			->update($values);

	}

}
?>
