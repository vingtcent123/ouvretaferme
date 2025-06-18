<?php
namespace company;

class EmployeeLib extends EmployeeCrud {

	private static ?\Collection $cEmployeeOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['id', 'email', 'role'];
	}

	public static function getPropertiesUpdate(): array {
		return ['role'];
	}

	public static function getOnline(): \Collection {

		if(self::$cEmployeeOnline === NULL) {
			$eUser = \user\ConnectionLib::getOnline();
			self::$cEmployeeOnline = self::getByUser($eUser);
		}

		return self::$cEmployeeOnline;

	}

	public static function getOnlineByCompany(Company $eCompany): Employee {
		return self::getOnline()[$eCompany['id']] ?? new Employee();
	}

	public static function create(Employee $e): void {

		$e->expects(['id']);

    $eInvite = new Invite([
      'company' => $e['company'],
      'employee' => $e
    ]);

    $fw = new \FailWatch();

    $eInvite->buildProperty('email', $e['email']);

    if($fw->ko()) {
      return;
    }

		try {

			Employee::model()->beginTransaction();

			if($e['id'] === NULL) {

				Employee::model()->insert($e);

			} else {

				Employee::model()->update($e, [
					'status' => Employee::INVITED
				]);

			}

			if($eInvite->notEmpty()) {
				InviteLib::create($eInvite);
			}

			Employee::model()->commit();


		} catch(\DuplicateException $e) {

			Employee::model()->rollBack();
			Employee::fail('duplicate');

		}

	}

	public static function isEmployee(\user\User $eUser, Company $eCompany, ?string $status = Employee::IN): bool {

		if($status !== NULL) {
			Employee::model()->whereStatus($status);
		}

		return Employee::model()
			->whereUser($eUser)
			->whereCompany($eCompany)
			->whereCompanyStatus(Employee::ACTIVE)
			->exists();

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Employee::model()
			->select(Employee::getSelection())
			->whereUser($eUser)
			->whereCompanyStatus(Employee::ACTIVE)
			->whereStatus(Employee::IN)
			->getCollection(NULL, NULL, 'company');

	}

	public static function getByCompany(Company $eCompany, bool $onlyInvite = FALSE): \Collection {

		$sort = [];

		$eUserOnline = \user\ConnectionLib::getOnline();

		if($eUserOnline->notEmpty()) {
			$sort[] = new \Sql('user = '.$eUserOnline['id'].' DESC');
		}

		$sort['createdAt'] = SORT_ASC;

		Employee::model()->sort($sort);

    if($onlyInvite) {
			Employee::model()
				->select([
					'invite' => Invite::model()
						->select(Invite::getSelection())
						->whereStatus(Invite::PENDING)
						->delegateElement('employee')
				])
				->whereStatus(Employee::INVITED);
		} else {
			Employee::model()->whereStatus(Employee::IN);
		}

		return Employee::model()
			->select(Employee::getSelection())
			->whereCompany($eCompany)
			->getCollection();

	}

	public static function associateUser(Employee $e, \user\User $eUser): void {

		Employee::model()
			->whereStatus(Employee::INVITED)
			->update($e, [
				'user' => $eUser,
				'status' => Employee::IN
			]);

	}

	public static function delete(Employee $e): void {

		if(
			$e['user']->notEmpty() and
			$e['user']['id'] === \user\ConnectionLib::getOnline()['id']
		) {
			Employee::fail('deleteItself');
			return;
		}

		Employee::model()->beginTransaction();

		parent::delete($e);

		Invite::model()
			->whereEmployee($e)
			->delete();

		Employee::model()->commit();

	}

	public static function register(Company $eCompany): void {

		$eEmployee = self::getOnlineByCompany($eCompany);

		$properties = array_filter(Employee::model()->getProperties(), fn($property) => str_starts_with($property, 'view'));

		foreach($properties as $property) {
			\Setting::set('main\\'.$property, $eEmployee->notEmpty() ? $eEmployee[$property] : Employee::model()->getDefaultValue($property));
		}

	}

	/**
	 * Get financialYear from $financialYear or from Employee::$viewFinancialYear
	 */
	public static function getDynamicFinancialYear(Company $eCompany, int $financialYear): array {

		$cFinancialYear = \accounting\FinancialYearLib::getAll();

		if($cFinancialYear->empty()) {
			throw new \RedirectAction(CompanyUi::urlAccounting($eCompany).'/financialYear/?redirect=1');
		}

		if($financialYear) {

			$eFinancialYear = \accounting\FinancialYearLib::getById($financialYear);

			if($eFinancialYear->exists() === FALSE) {
				$eFinancialYear = \accounting\FinancialYearLib::selectDefaultFinancialYear();
				$financialYear = $eFinancialYear['id'];
			}

			self::setView('viewFinancialYear', $eCompany, $financialYear);

			return [$cFinancialYear, $eFinancialYear];

		} else {

			if($eCompany->getView('viewFinancialYear') === NULL) {
				return [$cFinancialYear, $cFinancialYear->first()];
			}

			return [$cFinancialYear, \accounting\FinancialYearLib::getById($eCompany->getView('viewFinancialYear'))];

		}

	}
	public static function setView(string $field, Company $eCompany, mixed $newView): mixed {

		$eEmployee = $eCompany->getEmployee();

		if($eEmployee->empty()) {
			return $newView;
		}

		if($newView === $eEmployee[$field]) {
			return $eEmployee[$field];
		}

		if(Employee::model()->check($field, $newView)) {

			$eEmployee[$field] = $newView;

			Employee::model()
			      ->select($field)
			      ->update($eEmployee);

		}

		return $eEmployee[$field];


	}

}
