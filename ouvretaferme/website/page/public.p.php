<?php
(new Page())
	->get('/public/:404', function($data) {

		$data->notFound = dev\ErrorPhpLib::notFound();

		if(Route::getRequestedWith() === 'http') {

			$action = new ViewAction($data, ':nothing');
			$action->setStatusCode(404);

		} else {

			$action = new DataAction($data->notFound);

		}

		throw $action;

	});

(new Page(function($data) {

		\session\SessionLib::init();

		$data->origin = GET('origin', ['internal', 'external'], function() {
			throw new NotExpectedAction('Invalid origin');
		});
		$domain = GET('domain');

		$data->eWebsite = match($data->origin) {
			'internal' => \website\WebsiteLib::getByInternalDomain($domain),
			'external' => \website\WebsiteLib::getByDomain($domain)
		};

		if(
			$data->eWebsite->empty() or
			$data->eWebsite->canRead() === FALSE
		) {
			throw new ViewAction($data, ':nothing');
		}

		$data->url = \website\WebsiteUi::url($data->eWebsite);
		$id = GET('customDesign', default: $data->eWebsite['customDesign']['id']);

		$eDesignCustom = \website\DesignLib::getById($id);
		if($eDesignCustom->empty() === FALSE) {
			$data->eWebsite['customDesign'] = $eDesignCustom;
		}

		$data->cMenu = \website\MenuLib::getByWebsite($data->eWebsite);

	}))
	->get([[
		'/public/{domain}/robots.txt',
		'@priority' => 1
	]], function() {

		$data = 'User-agent: *'."\n";
		$data .= 'Disallow: '.Setting::get('main\robotsDisallow').''."\n";

		throw new DataAction($data, 'text/txt');

	})
	->get('/public/{domain}', function($data) {
		throw new PermanentRedirectAction(LIME_REQUEST_PATH.'/'.LIME_REQUEST_ARGS);
	})
	->get('/public/{domain}/:test', function($data) {

		if(in_array($data->eWebsite['domainStatus'], [\website\Website::CONFIGURED_UNSECURED, \website\Website::FAILURE_UNSECURED, \website\Website::CONFIGURED_SECURED, \website\Website::FAILURE_SECURED])) {
			throw new DataAction('OK');
		} else {
			throw new ViewAction($data, ':nothing');
		}

	})
	->get(['/public/{domain}/', '/public/{domain}/{page}'], function($data) {

		if(
			LIME_ENV === 'prod' and (
				($data->eWebsite['domainStatus'] === \website\Website::PINGED_SECURED and $data->origin === 'internal') or
				($data->eWebsite['domainStatus'] !== \website\Website::PINGED_SECURED and $data->origin === 'external')
			)
		) {
			throw new PermanentRedirectAction($data->url.'/'.GET('page').LIME_REQUEST_ARGS);
		}

		$data->eWebpage = \website\WebpageLib::getByUrl($data->eWebsite, GET('page'));
		\website\WidgetLib::fill($data->eWebpage);

		$data->eWebpageNews = \website\WebpageLib::getNewsByWebsite($data->eWebsite);

		if(
			$data->eWebpage->notEmpty() and
			$data->eWebpage['template']['fqn'] === 'news'
		) {
			$data->cNews = \website\NewsLib::getByWebsite($data->eWebsite, limit: 20);
		} else {

			if($data->eWebpageNews['status'] === \website\Webpage::ACTIVE) {
				$data->cNews = \website\NewsLib::getByWebsite($data->eWebsite, limit: 3);
			}

		}

		if(
			$data->eWebpage->empty() or
			$data->eWebpage->canRead() === FALSE
		) {
			$action = new ViewAction($data, ':404');
			$action->setStatusCode(404);
			throw $action;
		}

		throw new ViewAction($data, ':public');

	});
?>
