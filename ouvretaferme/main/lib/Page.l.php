<?php
namespace main;

class PageLib {

	public static function common(\stdClass $data): void {

		if(\Route::getRequestedWith() === 'cli') {
			return;
		}

		$data->browserObsolete = \util\HttpLib::isObsolete();

		if(LIME_REQUEST_PATH !== '/maintenance' and MainSetting::MAINTENANCE === TRUE) {
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

		if($data->isLogged) {

			$data->userDeletedAt = \session\SessionLib::get('userDeletedAt');
			$data->cFarmUser = \farm\FarmLib::getOnline();

		} else {

			$data->userDeletedAt = NULL;
			$data->cFarmUser = new \Collection();

		}

		$data->logInExternal = \user\ConnectionLib::checkLoginExternal();

		if(
			REQUEST('app') === 'accounting'
			and mb_strpos(SERVER('REQUEST_URI'), '/company/public:create') === FALSE
			and mb_strpos(SERVER('REQUEST_URI'), '/comptabilite/inactive') === FALSE
		) {

			$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'));
			if($data->pageType !== 'remote') {
				\user\ConnectionLib::checkLogged();
			}

			// On ne peut pas utiliser le même test que dans Farm.php car la feature est toujours activée en dev
			$canAccounting = ($data->eFarm->notEmpty() and in_array($data->eFarm['id'], \company\CompanySetting::$accountingBetaTestFarms));
			$hasAccounting = ($canAccounting and	$data->eFarm->hasAccounting());

			if(
				$data->pageType !== 'remote' and $canAccounting === FALSE
			) {
				throw new \RedirectAction('/comptabilite/inactive?farm='.$data->eFarm['id']);
			}

			if($hasAccounting) {

				\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);

			} else if(mb_strpos(SERVER('REQUEST_URI'), '/public:') === FALSE) {

				throw new \RedirectAction('/company/public:create?farm='.$data->eFarm['id']);

			}
		}

		$data->nFarmUser = $data->cFarmUser->count();

		// In some specific cases of redirections after network login we need to load datas before displaying a message
		if(\user\UserSetting::$featureBan) {

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
