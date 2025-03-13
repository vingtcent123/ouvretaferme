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

		if(\shop\Shop::isEmbed()) {
			$data .= 'Disallow: /'."\n";
		} else {
			$data .= 'Disallow: '."\n";
		}

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
					->where(GET('fqn'))
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
		'/shop/public/embed.js',
		'@priority' => 1
	]], function($data) {
/*

		$eDate = \shop\DateLib::getMostRelevantByShop($data->eShop, one: TRUE);

		$content = new \website\WidgetUi()->getShop($data->eShop, $eDate);
		$content = str_replace("\n", "", $content);
		$content = addcslashes($content, '\'\\');

		echo <<<END

		document.getElementById("otf-shop").innerHTML = '$content';

END;

 */
		header('Content-Type: application/javascript');
		header('Sec-Fetch-Dest: script');
		header('Sec-Fetch-Mode: no-cors');
		header('Sec-Fetch-Site: cross-site');
		header('Sec-GPC: 1');

		$url = \shop\ShopUi::url($data->eShop, force: 'embed');

		echo <<<END

		String.prototype.setArgument = function(name, value) {
		
			let location = this;
		
			const regex = new RegExp('([\&\?])'+ name +'=([^\&]*)', 'i');
		
			if(location.match(regex)) {
		
				location = location.replace(regex, '$1'+ name +'='+ encodeURIComponent(value));
		
			} else {
		
				location = location + (location .indexOf('?') === -1 ? '?' : '&');
		
				if(typeof value !== 'undefined') {
					location = location + name +'='+ encodeURIComponent(value);
				} else {
					location = location + name;
				}
		
			}
		
			return location;
		
		};

		String.prototype.removeArgument = function(name) {
		
			let location = this;

			const regex = new RegExp('([\&\?])'+ name.replace('[', '\\\\[').replace(']', '\\\\]') +'(=[a-z0-9/\.\%\:\\\\-\\\\\\\\+]*)*[&]?', 'gi');
			location = location.replace(regex, '$1');
			location = location.replace('?&', '?');
			location = location.replace('&&', '&');
		
			if(
				location.charAt(location.length - 1) === '?' ||
				location.charAt(location.length - 1) === '&'
			) {
				location = location.substring(0, location.length - 1);
			}
		
			return location;
		
		};

		let otfDate = null;
		let otfPage = null;
		
		location.search
			.substr(1)
			.split("&")
			.forEach(function(item) {
			
				const tmp = item.split("=");
				
				switch(tmp[0]) {
					case 'otfDate' :
						otfDate = decodeURIComponent(tmp[1])
						break;
					case 'otfPage' :
						otfPage = decodeURIComponent(tmp[1])
						break;
				}
				
			});
		
		let url = '$url';
		let parent = window.location.href;
			
		if(otfDate !== null && /^\d+$/.test(otfDate)) {
			url += '/'+ otfDate;
			parent = parent.removeArgument('otfDate');
			if(['confirmation', 'paiement'].includes(otfPage)) {
				url += '/'+ otfPage;
				parent = parent.removeArgument('otfPage');
			}
		}
	
		url = url.setArgument('parent', parent);
		
		history.replaceState(history.state, '', parent);
	
		const otfIframe = document.createElement("iframe");
		let otfIframeHeight = 500;
		otfIframe.src = url;
		otfIframe.style.width = "1px";
		otfIframe.style.minWidth = "100%";
		otfIframe.style.border = "none";
		otfIframe.style.height = otfIframeHeight +"px";
		document.getElementById("otf-shop").appendChild(otfIframe);

		window.addEventListener('message', function(e) {

			 let message = e.data;

			 if (
				  message.height &&
				  message.height !== otfIframeHeight
			 ) {
				  otfIframe.style.height = (message.height + 50) +'px';
				  otfIframeHeight = message.height;
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

		$data->eCustomer = \shop\SaleLib::getShopCustomer($data->eShop, $data->eUserOnline);

		if($data->eShop->canAccess($data->eCustomer) === FALSE) {
			throw new ViewAction($data, path: ':denied');
		}

		$data->isModifying = GET('modify', 'bool', FALSE);

		$data->cDate = \shop\DateLib::getMostRelevantByShop($data->eShop);

		if($data->cDate->notEmpty()) {

			$data->eDateSelected = $data->cDate[GET('date', 'int')] ?? $data->cDate->first();
			$data->eSaleExisting = \shop\SaleLib::getSaleForDate($data->eDateSelected, $data->eCustomer);

			// Cas où le client n'a pas finalisé la commande et retourne sur la boutique
			if(
				$data->eShop->canWrite() === FALSE or
				get_exists('customize') === FALSE
			) {

				if(
					$data->isModifying === FALSE and
					$data->eDateSelected['isOrderable'] and
					$data->eSaleExisting->notEmpty() and
					$data->eSaleExisting['preparationStatus'] === \selling\Sale::BASKET
				) {
					throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDateSelected, 'confirmation'));
				}

			}

			$data->discount = \shop\SaleLib::getDiscount($data->eDateSelected, $data->eSaleExisting, $data->eCustomer);

			$ccProduct = \shop\ProductLib::getByDate($data->eDateSelected, $data->eCustomer, eSaleExclude: $data->isModifying ? $data->eSaleExisting : new \selling\Sale(), withIngredients: TRUE, public: TRUE);

			// Multi producteur pas géré pour l'instant
			$cProduct = $ccProduct->empty() ? new Collection() : $ccProduct->first();

			foreach($cProduct as $eProduct) {
				$eProduct['reallyAvailable'] = \shop\ProductLib::getReallyAvailable($eProduct, $eProduct['product'], $data->eSaleExisting);
			}

			\shop\ProductLib::applyDiscount($cProduct, $data->discount);

			$data->eDateSelected['cProduct'] = $cProduct;
			$data->eDateSelected['farm'] = $data->eShop['farm'];

			$data->eDateSelected['ccPoint'] = $data->eShop['ccPoint'];
			$data->eDateSelected['ccPoint']->filter(fn($ePoint) => in_array($ePoint['id'], $data->eDateSelected['points']), depth: 2);

		} else {
			$data->eDateSelected = new \shop\Date();
		}

		$data->cCategory = \selling\CategoryLib::getByFarm($data->eShop['farm']);

		throw new ViewAction($data, path: ':shop');

	});

new Page(function($data) {

		$data->eShop = \shop\ShopLib::getByFqn(GET('fqn'))->validate('isOpen');
		$data->eShop['farm'] = \farm\FarmLib::getById($data->eShop['farm']);
		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		$data->eDate = \shop\DateLib::getById(GET('date'))->validateProperty('shop', $data->eShop);
		$data->eCustomer = \shop\SaleLib::getShopCustomer($data->eShop, $data->eUserOnline);

		if($data->eShop->canAccess($data->eCustomer) === FALSE) {
			throw new ViewAction($data, path: ':denied');
		}

		$data->eSaleExisting = \shop\SaleLib::getSaleForDate($data->eDate, $data->eCustomer);

		$data->discount = \shop\SaleLib::getDiscount($data->eDate, $data->eSaleExisting, $data->eCustomer);

		$data->eDate['shop'] = $data->eShop;

		$ccProduct = \shop\ProductLib::getByDate($data->eDate, $data->eCustomer, eSaleExclude: $data->eSaleExisting);

		// Multi producteur pas géré pour le moment
		$data->eDate['cProduct'] = $ccProduct->empty() ? new Collection() : $ccProduct->first();
		\shop\ProductLib::applyDiscount($data->eDate['cProduct'], $data->discount);

		$data->eDate['ccPoint'] = $data->eShop['ccPoint'];
		$data->eDate['ccPoint']->filter(fn($ePoint) => in_array($ePoint['id'], $data->eDate['points']), depth: 2);

		if($data->eSaleExisting->notEmpty()) {
			$data->eSaleExisting['shopDate'] = $data->eDate;
		}

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

			if($data->eDate->canOrder() === FALSE) {
				throw new RedirectAction(\shop\ShopUi::url($data->eShop).'?error=shop:Date::canNotOrder');
			}

		};

		$data->validateSale = function() use($data) {

			if(
				$data->eSaleExisting->empty() or
				$data->eSaleExisting['shop']['id'] !== $data->eShop['id']
			) {
				throw new RedirectAction(\shop\ShopUi::url($data->eShop));
			}

		};

		$data->validatePayment = function() use($data) {

			if(
				($data->eSaleExisting['preparationStatus'] === \selling\Sale::BASKET and $data->eSaleExisting['paymentMethod'] === NULL) or
				($data->eSaleExisting['paymentMethod'] === \selling\Sale::ONLINE_CARD and $data->eSaleExisting['paymentStatus'] === \selling\Sale::UNDEFINED)
			) {
				throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement'));
			}

		};

	})
	->get('/shop/public/{fqn}/{date}/panier', function($data) {

		($data->validateOrder)();
		($data->validateLogged)();

		if(
			$data->eSaleExisting->canBasket($data->eShop) === FALSE and
			$data->isModifying === FALSE
		) {
			throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'confirmation'));
		}

		$data->hasPoint = (
			$data->eShop['hasPoint'] and
			$data->eDate['ccPoint']->notEmpty()
		);
		$data->ePointSelected = \shop\PointLib::getSelected($data->eShop, $data->eDate['ccPoint'], $data->eCustomer, $data->eSaleExisting);

		throw new ViewAction($data);

	})
	->match(['get', 'post'], '/shop/public/{fqn}/{date}/paiement', function($data) {

		($data->validateLogged)();
		($data->validateSale)();

		// Si la vente est déjà payée, on ne peut pas changer de moyen de paiement
		if($data->eSaleExisting['paymentStatus'] === \selling\Sale::PAID) {
			throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'confirmation'));
		}

		// Si le paiement est désactivé, on retourne sur le panier
		if($data->eShop['hasPayment'] === FALSE) {
			throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'panier'));
		}

		$eFarm = $data->eShop['farm'];
		$data->eCustomer = \selling\CustomerLib::getByUserAndFarm($data->eUserOnline, $eFarm);

		$data->eStripeFarm = \payment\StripeLib::getByFarm($eFarm);

		throw new ViewAction($data);

	})
	->get('/shop/public/{fqn}/{date}/confirmation', function($data) {

		($data->validateLogged)();
		($data->validateSale)();
		($data->validatePayment)();

		$data->eSaleExisting['cItem'] = \selling\SaleLib::getItems($data->eSaleExisting, withIngredients: TRUE, public: TRUE);
		$data->eSaleExisting['shopPoint'] = \shop\PointLib::getById($data->eSaleExisting['shopPoint']);

		throw new ViewAction($data);

	})
	->post('/shop/public/{fqn}/{date}/:doCreatePayment', function($data) {

		($data->validateLogged)();
		($data->validateSale)();

		// Si la vente est déjà payée, on ne peut pas changer de moyen de paiement
		if($data->eSaleExisting['paymentStatus'] === \selling\Sale::PAID) {
			throw new RedirectAction(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'confirmation'));
		}

		$data->eSaleExisting['shopPoint'] = $data->eShop['ccPoint']->find(fn($ePoint) => $ePoint['id'] === $data->eSaleExisting['shopPoint']['id'], depth: 2, limit: 1, default: new \shop\Point());

		$data->payment = POST('payment');

		if(in_array($data->payment, $data->eShop->getPayments($data->eSaleExisting['shopPoint'])) === FALSE) {
			throw new NotExpectedAction('Invalid payment for shop');
		}

		try {
			throw new RedirectAction(\shop\SaleLib::createPayment($data->payment, $data->eDate, $data->eSaleExisting));
		} catch(Exception $e) {
			throw new FailAction($data->eDate->canWrite() ? 'shop\Shop::payment.createOwner' : 'shop\Shop::payment.create', ['message' => $e->getMessage()]);
		}

	})
	->post('/shop/public/{fqn}/{date}/:getBasket', function($data) {

		($data->validateOrder)();

		if(
			$data->eSaleExisting->canBasket($data->eShop) === FALSE and
			$data->isModifying === FALSE
		) {
			throw new RedirectAction(\shop\ShopUi::url($data->eShop));
		}

		$data->basket = \shop\BasketLib::checkAvailableProducts(POST('products', 'array', []), $data->eDate['cProduct'], $data->eSaleExisting);

		if($data->basket === []) {
			throw new RedirectAction(\shop\ShopUi::url($data->eShop));
		}

		$data->price = round(array_reduce($data->basket, function($total, $item) {
			return $total + $item['product']['price'] * $item['number'] * ($item['product']['packaging'] ?? 1);
		}, 0), 2);

		throw new ViewAction($data);

	})
	->post('/shop/public/{fqn}/{date}/:doCreateSale', function($data) {

		\user\ConnectionLib::checkLogged();

		if($data->eSaleExisting->empty()) {

			if(
				$data->eShop['terms'] and
				$data->eShop['termsField'] and
				POST('terms', 'bool') === FALSE
			) {
				throw new FailAction('shop\Sale::terms');
			}

			$eSale = new \selling\Sale([
				'shop' => $data->eShop,
				'shopDate' => $data->eDate,
			]);

			$fw = new FailWatch();

			if($data->eUserOnline['phone'] === NULL) {
				\selling\Sale::fail('phone.check');
			}

			$eSale->build(['productsBasket', 'shopPoint'], $_POST);

			$fw->validate();

			if(
				$eSale['shopPoint']->notEmpty() and
				$eSale['shopPoint']['type'] === \shop\Point::HOME and
				$data->eUserOnline->hasAddress() === FALSE
			) {
				\selling\Sale::fail('address.check');
			}

			$fw->validate();

			$url = \shop\SaleLib::createForShop($eSale, $data->eUserOnline);

			$fw->validate();

		} else {
			$url = \shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement');
		}

		throw new RedirectAction($url);

	})
	->post('/shop/public/{fqn}/{date}/:doUpdateBasket', function($data) {

		\user\ConnectionLib::checkLogged();
		($data->validateSale)();

		$fw = new FailWatch();

		if(\shop\SaleLib::canUpdateForShop($data->eSaleExisting) === FALSE) {
			throw new FailAction('shop\Sale::update.payment');
		}

		if(
			$data->eShop['terms'] and
			$data->eShop['termsField'] and
			POST('terms', 'bool') === FALSE
		) {
			throw new FailAction('shop\Sale::terms');
		}

		$data->eSaleExisting['shop'] = $data->eShop;
		$data->eSaleExisting['shopDate'] = $data->eDate;
		$data->eSaleExisting->build(['productsBasket', 'shopPoint'], $_POST);

		$fw->validate();

		if(
			$data->eSaleExisting['shopPoint']->notEmpty() and
			$data->eSaleExisting['shopPoint']['type'] === \shop\Point::HOME and
			$data->eUserOnline->hasAddress() === FALSE
		) {
			\selling\Sale::fail('address.check');
		}

		$fw->validate();

		$url = \shop\SaleLib::updateForShop($data->eSaleExisting, $data->eUserOnline);

		$fw->validate();

		throw new RedirectAction($url);

	})
	->post('/shop/public/{fqn}/{date}/:doCancelSale', function($data) {

		\user\ConnectionLib::checkLogged();
		($data->validateSale)();

		\shop\SaleLib::cancel($data->eSaleExisting);

		throw new ViewAction($data);

	}) ;

new \user\UserPage()
	->getElement(fn() => \user\ConnectionLib::getOnline())
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdatePhone', ['phone'], fn($data) => throw new ViewAction($data))
	->doUpdateProperties('/shop/public/{fqn}/{date}/:doUpdateAddress', ['street1', 'street2', 'postcode', 'city', 'addressMandatory'], fn($data) => throw new ViewAction($data));
?>
