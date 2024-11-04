<?php

use selling\Customer;

(new Page())
	->cli('index', function($data) {

		$cCustomer = \selling\Customer::model()
			->select(['id', 'type', 'destination', 'name'])
			->whereType(\selling\Customer::PRIVATE)
			->whereDestination(\selling\Customer::INDIVIDUAL)
			->whereFirstName(NULL)
			->whereLastName(NULL)
			->getCollection();

		foreach($cCustomer as $eCustomer) {

			$parts = preg_split('/\s+/si', $eCustomer['name']);

			// Vérification des majuscules
			$lastName = [];
			$firstName = [];

			foreach($parts as $part) {

				if(mb_strtoupper($part) === $part) {
					$lastName[] = $part;
				} else {
					$firstName[] = $part;
				}

			}

			if(count($lastName) > 0 and count($firstName) > 0) {
				$eCustomer['lastName'] = implode(' ', $lastName);
				$eCustomer['firstName'] = implode(' ', $firstName);
			} else {

				if(count($parts) === 1) {
					$eCustomer['firstName'] = NULL;
					$eCustomer['lastName'] = $eCustomer['name'];
				} else {

					$eCustomer['firstName'] = $parts[0];
					$eCustomer['lastName'] = implode(' ', array_slice($parts, 1));

				}

			}

			echo $eCustomer['id'].' : '.$eCustomer['firstName'].' -> '.$eCustomer['lastName']."\n";

			Customer::model()
				->select('firstName', 'lastName')
				->update($eCustomer);

		}

	});
?>