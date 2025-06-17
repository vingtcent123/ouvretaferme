<?php
namespace farm;

class Farmer extends FarmerElement {

	public static function getSelection(): array {

		return [
			'user' => [
				'name' => new \Sql('IF(firstName IS NULL, lastName, CONCAT(firstName, " ", lastName))'),
				'email', 'firstName', 'lastName', 'visibility', 'vignette', 'createdAt'
			],
		] + parent::getSelection();

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canUpdateRole(): bool {

		$this->expects(['user', 'farm', 'farmGhost']);
		$eUserOnline = \user\ConnectionLib::getOnline();

		return (
			$this->canWrite() and
			$this['farmGhost'] === FALSE and
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

				$this->expects(['farm']);

				// On vérifie qu'on est sur la même ferme
				return Farmer::model()
					->whereId($id)
					->whereFarm($this['farm']['id'])
					->exists();

			})
			->setCallback('role.prepare', function(?string $role): bool {

				return ($role !== NULL);

			})
			->setCallback('email.duplicate', function(string $email): bool {

				$this->expects(['farm']);

				$eUser = \user\UserLib::getByEmail($email);

				if($eUser->empty()) {
					return TRUE;
				}

				return Farmer::model()
					->whereUser($eUser)
					->whereFarm($this['farm'])
					->exists() === FALSE;

			})
			->setCallback('email.set', function(string $email): bool {
				$this['email'] = $email;
				return TRUE;
			})
			->setCallback('status.can', function(string $status): bool {

				$this->expects(['farmGhost']);

				return $this['farmGhost'];

			});

		parent::build($properties, $input, $p);

	}

}
?>