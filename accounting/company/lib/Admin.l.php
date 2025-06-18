<?php
namespace company;

/**
 * Companys admin
 */
class AdminLib {

	/**
	 * Get all companies
	 *
	 */
	public static function getCompanies(int $page, \Search $search): array {

		$number = 100;
		$position = $page * $number;

		if($search->get('id')) {
			Company::model()->whereId($search->get('id'));
		}

		if($search->get('user')) {

			$cUser = \user\UserLib::getFromQuery($search->get('user'));

			$cCompanyOwned = CompanyLib::getByUsers($cUser);

			Company::model()->whereId('IN', $cCompanyOwned);

		}

		if($search->get('name')) {
			Company::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		$properties = Company::getSelection();
		$properties['cEmployee'] = Employee::model()
			->select([
				'user' => ['firstName', 'lastName', 'visibility'],
			])
			->whereStatus(Employee::IN)
			->delegateCollection('company');

		$search->validateSort(['name', 'id']);

		Company::model()
			->select($properties)
			->sort($search->buildSort())
			->option('count');

		$cCompany = Company::model()->getCollection($position, $number);

		return [$cCompany, Company::model()->found()];

	}
	
}
?>
