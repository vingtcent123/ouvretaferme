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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'email.check' => function(string $email): bool {

				return \Filter::check('email', $email);

			},

			'id.prepare' => function(int &$id): bool {

				if($id === 0) {
					$id = NULL;
				}

				return TRUE;

			},

			'id.check' => function(?int $id): bool {

				if($id === NULL) {
					return TRUE;
				}

				$this->expects(['farm']);

				// On vérifie qu'on est sur la même ferme
				return Farmer::model()
					->whereId($id)
					->whereFarm($this['farm']['id'])
					->exists();

			},

			'role.prepare' => function(?string $role): bool {

				return ($role !== NULL);

			},

			'email.duplicate' => function(string $email): bool {

				$this->expects(['farm']);

				$eUser = \user\UserLib::getByEmail($email);

				if($eUser->empty()) {
					return TRUE;
				}

				return Farmer::model()
					->whereUser($eUser)
					->whereFarm($this['farm'])
					->exists() === FALSE;

			},

			'email.set' => function(string $email): bool {
				$this['email'] = $email;
				return TRUE;
			},

			'status.can' => function(string $status): bool {

				$this->expects(['farmGhost']);

				return $this['farmGhost'];

			}

		]);

	}

}
?>