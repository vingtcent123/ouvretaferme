<?php
namespace farm;

class Invite extends InviteElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farmer' => ['user'],
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canCreate(): bool {

		$this->expects(['farm', 'customer']);
		return (
			$this->canWrite() and
			$this['customer']['destination'] !== \selling\Customer::COLLECTIVE
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function isValid(): bool {

		if($this->empty()) {
			return FALSE;
		}

		$this->expects(['status', 'expiresAt']);

		return (
			$this['status'] === Invite::PENDING and
			$this['expiresAt'] >= currentDate()
		);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'email.duplicate' => function(string $email): bool {

				$this->expects(['farm']);

				return Invite::model()
					->whereFarm($this['farm'])
					->whereEmail($email)
					->whereStatus(Invite::PENDING)
					->exists() === FALSE;

			},

			'email.duplicateCustomer' => function(string $email): bool {

				$this->expects(['farm', 'type']);

				if($this['type'] !== Invite::CUSTOMER) {
					return TRUE;
				}

				$eUser = \user\UserLib::getByEmail($email);

				if($eUser->empty()) {
					return TRUE;
				}

				return \selling\Customer::model()
					->whereUser($eUser)
					->whereFarm($this['farm'])
					->exists() === FALSE;

			}

		]);

	}

}
?>