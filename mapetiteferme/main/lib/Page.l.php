<?php
namespace main;

class PageLib {

	public static function common(\stdClass $data): void {

		if(\Route::getRequestedWith() === 'cli') {
			return;
		}

		$data->browserObsolete = \util\HttpLib::isObsolete();

		if(LIME_REQUEST_PATH !== '/maintenance' and \Setting::get('main\maintenance') === TRUE) {
			throw new \RedirectAction('/maintenance');
		}

		if(str_starts_with(LIME_PAGE_REQUESTED, 'public/')) {
			$data->eUserOnline = new \user\User();
			$data->userDeletedAt = NULL;
			$data->isLogged = FALSE;
			return;
		}

    // Open session
    \session\SessionLib::setCache(new \RedisCache());
    \session\SessionLib::init();

		// Add user to page
		$data->eUserOnline = \user\ConnectionLib::getOnline();

		// Add log info
		$data->isLogged = \user\ConnectionLib::isLogged();

		\user\UserLib::registerPrivileges($data->eUserOnline);

		if($data->isLogged) {

			$data->userDeletedAt = \session\SessionLib::get('userDeletedAt');
			$data->cCompanyUser = \company\CompanyLib::getOnline();

		} else {

			$data->userDeletedAt = NULL;
			$data->cCompanyUser = new \Collection();

		}

		$data->eCompany = \company\CompanyLib::getById(REQUEST('company'));

		if($data->eCompany->empty() === FALSE) {
			\company\CompanyLib::connectSpecificDatabaseAndServer($data->eCompany);
		}

		$data->nCompanyUser = $data->cCompanyUser->count();

		$data->logInExternal = \user\ConnectionLib::checkLoginExternal();

		// In some specific cases of redirections after network login we need to load datas before displaying a message
		if(\Feature::get('user\ban')) {

			$error = GET('error');
			if(
				in_array($error, ['user:connectionBanned', 'user:signUpBanned']) and
				\session\SessionLib::exists('activeBanForUser')
			) {
				$data->errorOptions = \session\SessionLib::get('activeBanForUser');
				\session\SessionLib::delete('activeBanForUser');
			}
		}

		$data->tip = NULL;

	}

}
?>
