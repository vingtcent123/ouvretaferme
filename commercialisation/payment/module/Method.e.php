<?php
namespace payment;

class Method extends MethodElement {

	public function canRead(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->empty() === FALSE and
			$this['farm']->canWrite()
		);

	}

	public function canUse(): bool {

		return (
			($this['farm']->empty() or $this['farm']->canWrite())
			and $this['status'] === Method::ACTIVE
		);

	}

	public function acceptRestrictions(): bool {
		return in_array($this['fqn'], [MethodLib::ONLINE_CARD, MethodLib::TRANSFER]);
	}

	public function canDelete(): bool {

		return ($this['farm']->empty() or $this['farm']->canWrite());

	}

	public function acceptDelete(): bool {

		return $this['fqn'] === NULL;

	}

	public function acceptManualUpdate(): bool {

		if($this->empty()) {
			return TRUE;
		}

		$this->expects(['status', 'fqn']);

		return (
			$this['status'] === Method::ACTIVE and
			$this['fqn'] !== \payment\MethodLib::ONLINE_CARD
		);

	}

	public function isOnline(): bool {

		$this->expects(['online']);

		return $this['online'] === TRUE;

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('limitCustomers.prepare', fn(mixed &$customers) => \selling\CustomerLib::buildCollection($this, $customers, FALSE))
			->setCallback('excludeCustomers.prepare', fn(mixed &$customers) => \selling\CustomerLib::buildCollection($this, $customers, FALSE))
			->setCallback('limitGroups.prepare', fn(mixed &$groups) => \selling\CustomerGroupLib::buildCollection($this, $groups, FALSE))
			->setCallback('excludeGroups.prepare', fn(mixed &$groups) => \selling\CustomerGroupLib::buildCollection($this, $groups, FALSE))
			->setCallback('excludeCustomers.consistency', function($customers): bool {

				if(
					($this['limitCustomers'] === [] and $this['limitGroups'] === [] and $this['excludeGroups'] === [] and $customers === []) or
					($this['limitCustomers'] or $this['limitGroups']) xor ($this['excludeGroups'] or $customers)
				) {
					return TRUE;
				} else {
					return FALSE;
				}

			});

		parent::build($properties, $input, $p);

	}
}
?>
