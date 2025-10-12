<?php
new Page()
	->get('/shop/public/', function($data) {
		throw new RedirectAction(Lime::getUrl());
	})
	->get([[
		'/shop/public/robots.txt',
		'@priority' => 1
	]], function($data) {

		$data = 'User-agent: *'."\n";
		$data .= 'Disallow: '."\n";

		throw new DataAction($data, 'text/txt');

	});

new Page(function($data) {

		// On vérifie les redirections
		if(get_exists('fqn')) {

			try {

				$eRedirect = \shop\Redirect::model()
					->select([
						'shop' => ['fqn']
					])
					->whereFqn(GET('fqn'))
					->get();

			}
			// Impossible de gérer les problèmes utf8 avec CONVERT() pour une raison inconnue
			catch(Exception) {
				$eRedirect = new \shop\Redirect();
			}

			if($eRedirect->notEmpty()) {
				throw new PermanentRedirectAction(\shop\ShopUi::url($eRedirect['shop']));
			}

		}

		try {

			if(get_exists('fqn')) {
				$data->eShop = \shop\ShopLib::getByFqn(GET('fqn'));
			} else if(get_exists('id')) {
				$data->eShop = \shop\ShopLib::getById(GET('id'));
			} else {
				throw new NotExpectedAction('Missing parameter');
			}

		}
		// Impossible de gérer les problèmes utf8 avec CONVERT() pour une raison inconnue
		catch(Exception) {
			$data->eShop = new \shop\Shop();
		}

		if($data->eShop->empty()) {
			$action = new ViewAction($data, '/error:404');
			$action->setStatusCode(404);
			throw $action;
		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

	})
	->get([[
		'/shop/public/embed-limited.js',
		'@priority' => 1
	]], function($data) {


		$eDate = \shop\DateLib::getMostRelevantByShop($data->eShop, one: TRUE);

		$content = new \website\WidgetUi()->getShop($data->eShop, $eDate);
		$content = str_replace("\n", "", $content);
		$content = addcslashes($content, '\'\\');

		$css = Lime::getUrl().(string)Asset::getCssPath('website', 'widget.css');
		$key = random_int(0, 1000000);

		echo <<<END

		let otfCss$key  = document.createElement('link');
		otfCss$key.rel  = 'stylesheet';
		otfCss$key.type = 'text/css';
		otfCss$key.href = '$css';
		
		document.getElementsByTagName("head")[0].appendChild(otfCss$key);
		
		document.getElementById("otf-limited").innerHTML = '$content';
END;

	})
	->get([[
		'/shop/public/embed-full.js',
		'@priority' => 1
	]], function($data) {

		if(
			$data->eShop['embedUrl'] === NULL and
			$data->eShop->canWrite() === FALSE
		) {
			return;
		}

		header('Content-Type: application/javascript');
		header('Sec-Fetch-Dest: script');
		header('Sec-Fetch-Mode: no-cors');
		header('Sec-Fetch-Site: cross-site');
		header('Sec-GPC: 1');

		$url = \shop\ShopUi::url($data->eShop).'?embed';
		$key = random_int(0, 1000000);

		echo <<<END

		let otfIframe$key = document.createElement("iframe");
		let otfIframeHeight$key = 500;
		otfIframe$key.src = "$url";
		otfIframe$key.style.width = "1px";
		otfIframe$key.style.minWidth = "100%";
		otfIframe$key.style.border = "none";
		otfIframe$key.style.height = otfIframeHeight$key +"px";
		document.getElementById("otf-full").appendChild(otfIframe$key);
		
		window.addEventListener('message', function(e) {
		
			  let message = e.data;
		
			  if (
					 message.height &&
					 message.height !== otfIframeHeight$key
			  ) {
					 otfIframe$key.style.height = (message.height + 50) +'px';
					 otfIframeHeight$key = message.height;
			  }
		
		},false);
END;

	})
	->get('/shop/public/{fqn}:conditions', function($data) {

		throw new ViewAction($data);

	})
	->get([
		'/shop/public/{fqn}',
		'/shop/public/{fqn}/{date}',
	], function($data) {

		$data->eCustomer = \selling\CustomerLib::getByUserAndFarm($data->eUserOnline, $data->eShop['farm']);

		if($data->eShop->canCustomerRead($data->eCustomer) === FALSE) {
			throw new ViewAction($data, path: ':denied');
		}

		$data->isModifying = GET('modify', 'bool', FALSE);

		if($data->isModifying === FALSE) {
			$data->eShop->validateEmbed();
		}

		if($data->eShop['shared']) {

			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);

			$data->cCustomerExisting = \selling\CustomerLib::getByUserAndFarms($data->eUserOnline, $data->eShop['cShare']->getColumnCollection('farm'));

		} else {
			$data->cCustomerExisting = new Collection([
				$data->eShop['farm']['id'] => $data->eCustomer
			]);
		}

		$data->cDate = \shop\DateLib::getMostRelevantByShop($data->eShop);

		if($data->cDate->notEmpty()) {

			$data->eDateSelected = $data->cDate[GET('date', 'int')] ?? $data->cDate->first();

			$data->cSaleExisting = \shop\SaleLib::getByCustomersForDate($data->eShop, $data->eDateSelected, $data->cCustomerExisting);
			$data->eSaleReference = $data->cSaleExisting->notEmpty() ? $data->cSaleExisting->first() : new \selling\Sale();
			
			$data->cItemExisting = \selling\SaleLib::getItemsBySales($data->cSaleExisting);

			if(
				$data->isModifying and
				$data->cSaleExisting->empty()
			) {
				$data->isModifying = FALSE;
			}

			// Cas où le client n'a pas finalisé la commande et retourne sur la boutique
			if(
				$data->eShop->canWrite() === FALSE or
				get_exists('customize') === FALSE
			) {

				if(
					$data->isModifying === FALSE and
					$data->eDateSelected['isOrderable'] and
					$data->cSaleExisting->notEmpty() and
					$data->cSaleExisting->first()['preparationStatus'] === \selling\Sale::BASKET
				) {
					throw new RedirectAction(\shop\ShopUi::confirmationUrl($data->eShop, $data->eDateSelected));
				}

			}

			$data->discounts = \shop\SaleLib::getDiscounts($data->cSaleExisting, $data->cCustomerExisting);

			$cProduct = \shop\ProductLib::getByDate($data->eDateSelected, $data->eCustomer, cSaleExclude: $data->isModifying ? $data->cSaleExisting : new Collection(), withIngredients: TRUE, public: TRUE);

			$cProductAvailable = new Collection();

			foreach($cProduct as $key => $eProduct) {

				$reallyAvailable = \shop\ProductLib::getReallyAvailable($eProduct, $eProduct['product'], $data->cItemExisting);

				if(
					($data->eShop['outOfStock'] === \shop\Shop::SHOW) or
					($data->eShop['outOfStock'] === \shop\Shop::HIDE and $reallyAvailable !== 0.0)
				) {
					$cProductAvailable[] = $eProduct->merge([
						'reallyAvailable' => $reallyAvailable
					]);
				}

			}

			\shop\ProductLib::applyIndexing($data->eShop, $data->eDateSelected, $cProductAvailable);

			$data->eDateSelected['farm'] = $data->eShop['farm'];

			$data->eDateSelected['ccPoint'] = $data->eShop['ccPoint'];
			$data->eDateSelected['ccPoint']->filter(fn($ePoint) => in_array($ePoint['id'], $data->eDateSelected['points']), depth: 2);

			$data->cCategory = \selling\CategoryLib::getByFarm($data->eShop['farm']);

			if($data->isModifying) {
				$data->basketProducts = \shop\BasketLib::getProductsFromItem($data->cItemExisting);
			} else {
				$data->basketProducts = \shop\BasketLib::getProductsFromQuery();
			}

			$data->canBasket = $data->eSaleReference->canBasket($data->eShop);

		} else {
			$data->eDateSelected = new \shop\Date();
		}

		throw new ViewAction($data, path: ':shop');

	});

new Page(function($data) {

		$data->step = NULL;

		$data->eShop = \shop\ShopLib::getByFqn(GET('fqn'))->validate('isOpen');
		$data->eShop['farm'] = \farm\FarmLib::getById($data->eShop['farm']);
		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		$data->eDate = \shop\DateLib::getById(GET('date'))->validateProperty('shop', $data->eShop);

		$data->eCustomer = \selling\CustomerLib::getByUserAndFarm($data->eUserOnline, $data->eShop['farm']);

		if($data->eShop->canCustomerRead($data->eCustomer) === FALSE) {
			throw new ViewAction($data, path: ':denied');
		}

		if($data->eShop['shared']) {

			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['cFarm'] = $data->eShop['cShare']->getColumnCollection('farm', index: 'farm');

			$data->cCustomerExisting = \selling\CustomerLib::getByUserAndFarms($data->eUserOnline, $data->eShop['cFarm']);

		} else {

			$data->eShop['cFarm'] = new Collection([
				$data->eShop['farm']['id'] => $data->eShop['farm']
			]);

			$data->cCustomerExisting = new Collection([
				$data->eShop['farm']['id'] => $data->eCustomer
			]);

		}

		$data->cSaleExisting = \shop\SaleLib::getByCustomersForDate($data->eShop, $data->eDate, $data->cCustomerExisting);
		$data->eSaleReference = $data->cSaleExisting->notEmpty() ? $data->cSaleExisting->first() : new \selling\Sale();

		$data->cItemExisting = \selling\SaleLib::getItemsBySales($data->cSaleExisting, withIngredients: TRUE, public: TRUE);

		$data->discounts = \shop\SaleLib::getDiscounts($data->cSaleExisting, $data->cCustomerExisting);

		$data->eDate['shop'] = $data->eShop;

		$data->eDate['cProduct'] = \shop\ProductLib::getByDate($data->eDate, $data->eCustomer, cSaleExclude: $data->cSaleExisting, public: TRUE);

		$data->eDate['ccPoint'] = $data->eShop['ccPoint'];
		$data->eDate['ccPoint']->filter(fn($ePoint) => in_array($ePoint['id'], $data->eDate['points']), depth: 2);

		if($data->cSaleExisting->notEmpty()) {
			$data->cSaleExisting->setColumn('shopDate', $data->eDate);
		}

		$data->canBasket = $data->eSaleReference->canBasket($data->eShop);

		$data->isModifying = GET('modify', 'bool', FALSE);

		$data->validateLogged = function() use($data) {

			if($data->isLogged === FALSE) {

				$data->eUserOnline = new \user\User();
				user\ConnectionLib::loadSignUp($data);
				$data->eRole = \shop\ShopLib::getRoleForSignUp();

				throw new ViewAction($data, ':authenticate');

			}

		};

		$data->validateOrder = function() use($data) {

			if($data->eDate->acceptOrder() === FALSE) {
				throw new RedirectAction(\shop\ShopUi::url($data->eShop).'?error=shop:Date::canNotOrder');
			}

		};

		$data->validateSale = function(?Action $action = NULL) use($data) {

			if($data->cSaleExisting->empty()) {

				if(\shop\SaleLib::hasExpired($data->eShop, $data->eDate, $data->cCustomerExisting)) {
					throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate).'?error=selling:Sale::productsBasket.expired');
				} else {
					throw $action ?? new RedirectAction(\shop\ShopUi::url($data->eShop));
				}

			}

		};

		$data->validatePayment = function() use($data) {

			if(
				$data->eSaleReference['paymentStatus'] !== \selling\Sale::PAID and
				$data->eSaleReference['onlinePaymentStatus'] !== \selling\Sale::FAILURE and // On affiche la page de confirmation si le paiement est en échec
				$data->eSaleReference['preparationStatus'] === \selling\Sale::BASKET
			) {
				throw new RedirectAction(\shop\ShopUi::paymentUrl($data->eShop, $data->eDate));
			}

		};

	})
	->get('/shop/public/{fqn}/{date}/panier', function($data) {

		$data->step = \shop\BasketUi::STEP_SUMMARY;

		($data->validateOrder)();
		($data->validateLogged)();

		if(
			$data->canBasket === FALSE and
			$data->isModifying === FALSE
		) {
			throw new ViewAction($data, ':basketExisting');
		}

		$data->hasPoint = (
			$data->eShop['hasPoint'] and
			$data->eDate['ccPoint']->notEmpty()
		);
		$data->ePointSelected = \shop\PointLib::getSelected($data->eShop, $data->eDate['ccPoint'], $data->eCustomer, $data->eSaleReference);

		$data->basketProducts = \shop\BasketLib::getProductsFromQuery();

		throw new ViewAction($data);

	})
	->match(['get', 'post'], '/shop/public/{fqn}/{date}/paiement', function($data) {

		$data->step = \shop\BasketUi::STEP_PAYMENT;

		($data->validateLogged)();
		($data->validateSale)();

		// Si la vente est déjà payée ou validée, on ne peut pas changer de moyen de paiement
		if($data->eSaleReference['preparationStatus'] !== \selling\Sale::BASKET) {
			throw new RedirectAction(\shop\ShopUi::confirmationUrl($data->eShop, $data->eDate));
		}

		// Si le paiement est désactivé, on retourne sur le panier
		if($data->eShop['hasPayment'] === FALSE) {
			throw new RedirectAction(\shop\ShopUi::basketUrl($data->eShop, $data->eDate));
		}

		$eFarm = $data->eShop['farm'];
		$data->eCustomer = \selling\CustomerLib::getByUserAndFarm($data->eUserOnline, $eFarm);

		$data->eStripeFarm = \payment\StripeLib::getByFarm($eFarm);

		$data->eSaleReference['isApproximate'] = (
			$data->eShop->isApproximate() and
			\selling\Item::containsApproximate($data->cItemExisting)
		);

		throw new ViewAction($data);

	})
	->get('/shop/public/{fqn}/{date}/confirmation', function($data) {

		$data->step = \shop\BasketUi::STEP_CONFIRMATION;

		($data->validateLogged)();
		($data->validateSale)(new ViewAction($data, ':confirmationEmpty'));

		($data->validatePayment)();

		$data->eSaleReference['shopPoint'] = \shop\PointLib::getById($data->eSaleReference['shopPoint']);

		$data->eSaleReference['isApproximate'] = (
			$data->eShop->isApproximate() and
			\selling\Item::containsApproximate($data->cItemExisting)
		);

		throw new ViewAction($data);

	})
	->post('/shop/public/{fqn}/{date}/:doCreatePayment', function($data) {

		($data->validateLogged)();
		($data->validateSale)();

		// Si la vente est déjà payée, on ne peut pas changer de moyen de paiement
		if($data->eSaleReference['paymentStatus'] === \selling\Sale::PAID) {
			throw new RedirectAction(\shop\ShopUi::confirmationUrl($data->eShop, $data->eDate));
		}

		if($data->eSaleReference['shopPoint']->notEmpty()) {
			$data->eSaleReference['shopPoint'] = $data->eShop['ccPoint']->find(fn($ePoint) => $ePoint['id'] === $data->eSaleReference['shopPoint']['id'], depth: 2, limit: 1, default: new \shop\Point());
		}

		$data->payment = POST('payment');

		if(in_array($data->payment, $data->eShop->getPayments($data->eSaleReference['shopPoint'])) === FALSE) {
			throw new NotExpectedAction('Invalid payment for shop');
		}

		$data->eSaleReference['cItem'] = $data->cItemExisting;

		$data->eSaleReference['shop']['farm'] = $data->eSaleReference['farm'];

		try {
			$url = \shop\SaleLib::createPayment($data->payment, $data->eSaleReference);
		} catch(Exception $e) {
			\dev\ErrorPhpLib::handle($e);
			throw new FailAction($data->eDate->canWrite() ? 'shop\Shop::payment.createOwner' : 'shop\Shop::payment.create', ['message' => $e->getMessage()]);
		}

		throw new RedirectAction($url);

	})
	->post('/shop/public/{fqn}/{date}/:getBasket', function($data) {

		($data->validateOrder)();

		if(
			$data->eSaleReference->canBasket($data->eShop) === FALSE and
			$data->isModifying === FALSE
		) {
			throw new RedirectAction(\shop\ShopUi::url($data->eShop));
		}

		$data->discounts = \shop\SaleLib::getDiscounts($data->cSaleExisting, $data->cCustomerExisting);
		$data->basket = \shop\BasketLib::checkAvailableProducts(POST('products', 'array', []), $data->eDate['cProduct'], $data->cItemExisting);

		if($data->basket === []) {
			throw new RedirectAction(\shop\ShopUi::url($data->eShop));
		}


		[$data->basketByFarm, $data->price, $data->approximate] = \shop\BasketLib::reorganizeByFarm($data->eShop, $data->basket, $data->discounts);

		$data->basketProducts = \shop\BasketLib::getProductsFromBasket($data->basket);

		throw new ViewAction($data);

	})
	->post('/shop/public/{fqn}/{date}/:doCreateSale', function($data) {

		\user\ConnectionLib::checkLogged();

		if($data->cSaleExisting->notEmpty()) {
			throw new RedirectAction(\shop\ShopUi::paymentUrl($data->eShop, $data->eDate));
		}

		if(
			$data->eShop['terms'] and
			$data->eShop['termsField'] and
			POST('terms', 'bool') === FALSE
		) {
			throw new FailAction('shop\Sale::terms');
		}

		$eSaleReference = new \selling\Sale([
			'shop' => $data->eShop,
			'shopDate' => $data->eDate,
			'shopShared' => $data->eShop['shared'],
			'cItem' => new Collection()
		]);

		$fw = new FailWatch();

		if($data->eUserOnline['phone'] === NULL) {
			\selling\Sale::fail('phone.check');
		}

		$properties = ['productsBasket', 'shopPoint'];
		if($data->eShop['comment']) {
			$properties[] = 'shopComment';
		}

		$eSaleReference->build($properties, $_POST);

		if($fw->has('Sale::productsBasket.check')) {
			throw new RedirectAction(\shop\ShopUi::basketUrl($data->eShop, $data->eDate).'?error=selling:Sale::productsBasket.check');
		}

		$fw->validate();

		if(
			$eSaleReference['shopPoint']->notEmpty() and
			$eSaleReference['shopPoint']['type'] === \shop\Point::HOME and
			$data->eUserOnline->hasAddress() === FALSE
		) {
			\selling\Sale::fail('address.check');
		}

		$fw->validate();

		$url = \shop\SaleLib::createForShop($eSaleReference, $data->eUserOnline, $data->discounts);

		$fw->validate();

		throw new RedirectAction($url);

	})
	->post('/shop/public/{fqn}/{date}/:doUpdatePayment', function($data) {

		\user\ConnectionLib::checkLogged();
		($data->validateSale)();

		if($data->eSaleReference->acceptUpdatePaymentByCustomer()) {

			\shop\SaleLib::changePaymentForShop($data->eSaleReference);

			$link = \shop\ShopUi::paymentUrl($data->eShop, $data->eDate);
		} else {
			$link = \shop\ShopUi::confirmationUrl($data->eShop, $data->eDate);
		}


		throw new RedirectAction($link);

	})
	->post('/shop/public/{fqn}/{date}/:doUpdateBasket', function($data) {

		\user\ConnectionLib::checkLogged();
		($data->validateSale)();

		$fw = new FailWatch();

		if(\shop\SaleLib::canUpdateForShop($data->eSaleReference) === FALSE) {
			throw new FailAction('shop\Sale::update.payment');
		}

		if(
			$data->eShop['terms'] and
			$data->eShop['termsField'] and
			POST('terms', 'bool') === FALSE
		) {
			throw new FailAction('shop\Sale::terms');
		}

		$properties = ['productsBasket', 'shopPoint'];
		if($data->eShop['comment']) {
			$properties[] = 'shopComment';
		}

		$data->eSaleReference->merge([
			'shop' => $data->eShop,
			'shopDate' => $data->eDate,
			'cItem' => $data->cItemExisting
		]);

		$data->eSaleReference->build($properties, $_POST);

		if($fw->has('Sale::productsBasket.check')) {
			throw new RedirectAction(\shop\ShopUi::basketUrl($data->eShop, $data->eDate).'?modify=1&error=selling:Sale::productsBasket.check');
		}

		$fw->validate();

		if(
			$data->eSaleReference['shopPoint']->notEmpty() and
			$data->eSaleReference['shopPoint']['type'] === \shop\Point::HOME and
			$data->eUserOnline->hasAddress() === FALSE
		) {
			\selling\Sale::fail('address.check');
		}

		$fw->validate();

		$url = \shop\SaleLib::updateForShop($data->eSaleReference, $data->cSaleExisting, $data->eUserOnline, $data->discounts);

		$fw->validate();

		throw new RedirectAction($url);

	})
	->post('/shop/public/{fqn}/{date}/:doCancelCustomer', function($data) {

		\shop\SaleLib::cancelForShop($data->eShop, $data->eDate, $data->eCustomer);

		throw new ViewAction($data);

	});

new \selling\ProductPage()
	->getElement(fn() => \user\ConnectionLib::getOnline())
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdatePhone', ['phone'], fn($data) => throw new ViewAction($data))
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdateAddress', ['street1', 'street2', 'postcode', 'city', 'addressMandatory'], fn($data) => throw new ViewAction($data));

new \user\UserPage()
	->getElement(fn() => \user\ConnectionLib::getOnline())
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdatePhone', ['phone'], fn($data) => throw new ViewAction($data))
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdateAddress', ['street1', 'street2', 'postcode', 'city', 'addressMandatory'], fn($data) => throw new ViewAction($data));
?>
