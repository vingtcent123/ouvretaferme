<?php
namespace company;

class Company extends CompanyElement {

	protected static array $companies = [];

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	public function active(): bool {
		return ($this['status'] === Company::ACTIVE);
	}

	public function isCashAccounting(): bool {

		$this->expects(['accountingType']);

		return $this['accountingType'] === CompanyElement::CASH;

	}
	public function isAccrualAccounting(): bool {

		$this->expects(['accountingType']);

		return $this['accountingType'] === CompanyElement::ACCRUAL;

	}

	public function getEmployee(): Employee {

		$this->expects(['id']);

		return EmployeeLib::getOnline()[$this['id']] ?? new Employee();

	}

	public function getView(string $name): mixed {
		return $this->getEmployee()[$name];
	}

	// Peut gérer l'entreprise
	public function canCreate(): bool {

		return TRUE;

	}

	public function canManage(): bool {
		if($this->empty()) {
			return FALSE;
		}

		if($this['status'] === CompanyElement::CLOSED) {
			return FALSE;
		}

		return $this->isRole(EmployeeElement::OWNER);

	}

	public function canView(): bool {
		if($this->empty()) {
			return FALSE;
		}

		if($this['status'] === CompanyElement::CLOSED) {
			return FALSE;
		}

		return ($this->canWrite() or $this->isRole(EmployeeElement::EMPLOYEE));

	}

	public function canWrite(): bool {
		if($this->empty() or array_key_exists('id', $this->getArrayCopy()) === FALSE) {
			return FALSE;
		}

		if($this['status'] === CompanyElement::CLOSED) {
			return FALSE;
		}

		return ($this->canManage() or $this->isRole(EmployeeElement::OWNER));

	}

	public function isRole(string $role): bool {

		if($this->empty()) {
			return FALSE;
		}

		$eEmployee = $this->getEmployee();

		return (
			$eEmployee->notEmpty() and
			$eEmployee['role'] === $role
		);

	}

	public function canRemote(): bool {
		return GET('key') === \Setting::get('main\remoteKey') || LIME_ENV === 'dev';
	}

	// Peut voir les données personnelles des clients et la page de gestion d'équipe
	public function canPersonalData(): bool {
		return $this->canWrite();
	}


	public function getHomeUrl(): string {

    return CompanyUi::urlJournal($this).'/';

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.empty', function(?string $name): bool {

				return $name !== NULL and mb_strlen($name) > 0;

			})
			->setCallback('siret.empty', function(?string $siret): bool {

				return $siret !== NULL and mb_strlen($siret) > 0;

			})
			->setCallback('siret.exists', function(?string $siret): bool {

				$eCompany = CompanyLib::getBySiret($siret);
				return $eCompany->exists() === FALSE;

			});
		parent::build($properties, $input, $p);

	}

}
?>
