<?php
namespace cash;

class Register extends RegisterElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'openedSince' => new \Sql('closedAt + INTERVAL 1 DAY'),
			'account' => \account\Account::getSelection(),
			'bankAccount' => \account\Account::getSelection(),
			'paymentMethod' => fn($e) => \payment\MethodLib::ask($e['paymentMethod'], \farm\Farm::getConnected()),
			'pending?' => fn($e) => fn($key) => RegisterLib::countPending($e)[$key]
		];

	}

	public function acceptOperation(string $source, string $type): bool {

		$this->expects([
			'paymentMethod' => ['fqn']
		]);

		if($source === Cash::PRIVATE) {
			return ($this['paymentMethod']['fqn'] === \payment\MethodLib::CASH);
		}

		if($source === Cash::BANK_MANUAL and $type === Cash::CREDIT) {
			return ($this['paymentMethod']['fqn'] === \payment\MethodLib::CASH);
		}

		if($source === Cash::BUY_MANUAL) {
			return ($this['paymentMethod']['fqn'] !== \payment\MethodLib::CHECK);
		}

		return TRUE;

	}

	public function acceptCash(): bool {

		return ($this['status'] === Register::ACTIVE);

	}

	public function acceptUpdateBalance(): bool {
		return (
			$this['status'] === Register::ACTIVE and
			$this['paymentMethod']['fqn'] === \payment\MethodLib::CASH and
			$this['pending?']('draft') === 0
		);
	}

	public function acceptClose(): bool {
		return ($this['status'] === Register::ACTIVE);
	}

	public function getCloseDate(): ?string {

		if($this['closedAt'] === NULL) {
			return NULL;
		}

		$date = date('Y-m-t', strtotime($this['closedAt']));

		if($date === $this['closedAt']) {
			$date = date('Y-m-t', strtotime($date) + 86400);
		}

		if(
			$date > currentDate() or
			($this['pending?']('firstDraft') !== NULL and $this['pending?']('firstDraft') <= $date)
		) {
			return NULL;
		} else {
			return $date;
		}

	}

	public function acceptDelete(): bool {

		$this->expects(['operations']);

		return ($this['operations'] <= CashSetting::DELETE_LIMIT);

	}

	public function isClosedByDate(string $date): bool {

		$this->expects(['closedAt']);

		return ($date <= $this['closedAt']);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod) {

				$this->expects(['cPaymentMethod']);


				return (
					$eMethod->notEmpty() and
					$this['cPaymentMethod']->offsetExists($eMethod['id'])
				);

			})
			->setCallback('account.check', function(\account\Account $eAccount) {

				if($eAccount->empty()) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount);

				return (
					$eAccount->notEmpty() and
					\account\AccountLabelLib::isFromClasses($eAccount['class'], CashSetting::CLASSES)
				);

			})
			->setCallback('bankAccount.check', function(\account\Account $eAccount) {

				if($eAccount->empty()) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount);

				return (
					$eAccount->notEmpty() and
					\account\AccountLabelLib::isFromClasses($eAccount['class'], [\account\AccountSetting::BANK_ACCOUNT_CLASS])
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>