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

		// Demo
		if(OTF_DEMO) {

			if((int)date('H') === 4) {
				throw new \RedirectAction(\Lime::getUrl().'/maintenance:demo');
			}

			\farm\DemoLib::activate();

			// Open session
			\session\SessionLib::init();

			if(\user\ConnectionLib::getOnline()->empty()) {
				\user\ConnectionLib::logInUser(new \user\User(['id' => \farm\DemoLib::USER]));
			}

		} else {

			// Open session
			\session\SessionLib::setCache(new \RedisCache());
			\session\SessionLib::init();

		}

		// Add user to page
		$data->eUserOnline = \user\ConnectionLib::getOnline();

		// Add log info
		$data->isLogged = \user\ConnectionLib::isLogged();

		\user\UserLib::registerPrivileges($data->eUserOnline);

		if($data->isLogged) {

			$data->userDeletedAt = \session\SessionLib::get('userDeletedAt');
			$data->cFarmUser = \farm\FarmLib::getOnline();

		} else {

			$data->userDeletedAt = NULL;
			$data->cFarmUser = new \Collection();

		}

		$data->nFarmUser = $data->cFarmUser->count();

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
