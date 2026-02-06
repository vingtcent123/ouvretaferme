<?php
namespace cash;

class Register extends RegisterElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'account' => \account\Account::getSelection(),
			'bankAccount' => \account\Account::getSelection(),
			'paymentMethod' => fn($e) => \payment\MethodLib::ask($e['paymentMethod'], \farm\Farm::getConnected()),
		];

	}

	public function acceptOperation(string $source, string $type): bool {

		$this->expects([
			'paymentMethod' => ['fqn']
		]);

		if(
			($source === Cash::BANK and $type === Cash::CREDIT) or
			($source === Cash::PRIVATE)
		) {
			return ($this['paymentMethod']['fqn'] === \payment\MethodLib::CASH);
		}

		return TRUE;

	}

	public function acceptCash(): bool {

		return ($this['status'] === Register::ACTIVE);

	}

	public function acceptDelete(): bool {

		$this->expects(['operations']);

		return ($this['operations'] === 0);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
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