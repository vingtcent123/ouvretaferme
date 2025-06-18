<?php
namespace company;

class Employee extends EmployeeElement {

	public static function getSelection(): array {

		return [
			'id',
			'user' => [
				'name' => new \Sql('IF(firstName IS NULL, lastName, CONCAT(firstName, " ", lastName))'),
				'email', 'firstName', 'lastName', 'visibility', 'vignette', 'createdAt'
			],
			'role',
		] + parent::getSelection();

	}

	public function canUpdateRole(): bool {

		$this->expects(['user', 'company']);
		$eUserOnline = \user\ConnectionLib::getOnline();

		return (
			$this->canWrite() and
			$eUserOnline->notEmpty() and
			($eUserOnline['id'] !== $this['user']['id'])
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('email.check', function(string $email): bool {

				return \Filter::check('email', $email);

			})
			->setCallback('id.prepare', function(int &$id): bool {

				if($id === 0) {
					$id = NULL;
				}

				return TRUE;

			})
			->setCallback('id.check', function(?int $id): bool {

				if($id === NULL) {
					return TRUE;
				}

				$this->expects(['company']);

				// On vérifie qu'on est sur la même entreprise
				return Employee::model()
					->whereId($id)
					->whereCompany($this['company']['id'])
					->exists();

			})
			->setCallback('role.prepare', function(?string $role): bool {

				return ($role !== NULL);

			})
			->setCallback('email.duplicate', function(string $email): bool {

				$this->expects(['company']);

				$eUser = \user\UserLib::getByEmail($email);

				if($eUser->empty()) {
					return TRUE;
				}

				return Employee::model()
						->whereUser($eUser)
						->whereCompany($this['company'])
						->exists() === FALSE;

			})
			->setCallback('email.set', function(string $email): bool {
				$this['email'] = $email;
				return TRUE;
			});

		parent::build($properties, $input, $p);

	}

}
?>
