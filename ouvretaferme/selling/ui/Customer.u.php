<?php
namespace selling;

class CustomerUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'customer.js');

		\Asset::css('production', 'crop.css');

	}

	public static function link(Customer $eCustomer, bool $newTab = FALSE): string {
		if($eCustomer->empty()) {
			return encode($eCustomer->getName());
		} else {
			return '<a href="'.self::url($eCustomer).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eCustomer->getName()).'</a>';
		}
	}

	public static function getCategory(Customer $eCustomer): string {

		return match($eCustomer['type']) {

			Customer::PRO => self::getCategories()[Customer::PRO],
			Customer::PRIVATE => match($eCustomer['destination']) {
				Customer::INDIVIDUAL => self::getCategories()[Customer::PRIVATE],
				Customer::COLLECTIVE => self::getCategories()[Customer::COLLECTIVE]
			}

		};

	}

	public static function getType(Customer|Sale $eCustomer): string {

		return match($eCustomer['type']) {

			Customer::PRO => self::getCategories()[Customer::PRO],
			Customer::PRIVATE => self::getCategories()[Customer::PRIVATE]

		};

	}

	public static function getCategories(): array {

		return [
			Customer::PRO => s("Professionnel"),
			Customer::PRIVATE => s("Particulier"),
			Customer::COLLECTIVE => s("Point de vente")
		];

	}

	public static function getColorCircle(Customer $eCustomer, ?string $size = NULL): string {

		\Asset::css('selling', 'customer.css');

		$eCustomer->expects(['color']);

		if($eCustomer['color'] !== NULL) {
			return '<div class="customer-color-circle" style="background-color: '.($eCustomer['color'] ?? 'var(--muted)').'; '.($size ? 'width: '.$size.'; height: '.$size.';' : '').'"></div>';
		} else {
			return '';
		}

	}

	public static function url(Customer $eCustomer): string {

		$eCustomer->expects(['id']);

		return '/client/'.$eCustomer['id'];

	}

	public static function urlOptIn(Customer $eCustomer, bool $consent): string {
		return '/client/'.$eCustomer['id'].'/optIn?hash='.$eCustomer->getOptInHash().'&consent='.($consent ? 1 : 0);
	}

	public static function getTaxes(string $type): string {

		return match($type) {
			Customer::PRIVATE => s("TTC"),
			Customer::PRO => s("HT"),
		};

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('person-bounding-box');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez un nom de client");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'customer'];

		$d->autocompleteUrl = '/selling/customer:query';
		$d->autocompleteResults = function(Customer $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Customer $eCustomer): array {

		\Asset::css('media', 'media.css');

		$item = '<div>'.encode($eCustomer->getName()).'<br/><small class="color-muted">'.self::getCategory($eCustomer).'</small></div>';

		return [
			'value' => $eCustomer['id'],
			'discount' => $eCustomer['discount'],
			'type' => $eCustomer['type'],
			'itemHtml' => $item,
			'itemText' => $eCustomer['name'].' / '.$eCustomer->getTextCategory(short: TRUE)
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Ajouter un nouveau client").'</div>';

		return [
			'type' => 'link',
			'link' => '/selling/customer:create?farm='.$eFarm['id'],
			'itemHtml' => $item
		];

	}

	public static function getPanelHeader(Customer $eCustomer): string {

		return '<div class="panel-header-subtitle">'.encode($eCustomer->getName()).'</div>';

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="customer-search" class="util-block-search stick-xs '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlSellingCustomer($eFarm);

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du client")]);
					$h .= $form->email('email', $search->get('email'), ['placeholder' => s("E-mail du client")]);
					$h .= $form->select('category', self::getCategories(), $search->get('category'), ['placeholder' => s("Catégorie")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCustomer, ?int $nCustomer = NULL, \Search $search = new \Search(), ?int $page = NULL) {

		if($cCustomer->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucun client à afficher.").'</div>';
		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$h = '<table class="customer-item-table tr-even stick-xs">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2">';
						$label = s("Prénom");
						$h .= ($search ? $search->linkSort('firstName', $label) : $label).' / ';
						$label = s("Nom");
						$h .= ($search ? $search->linkSort('lastName', $label) : $label);
					$h .= '</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Compte client").'</th>';
					$h .= '<th colspan="2" class="text-center highlight">'.s("Ventes").'</th>';
					$h .= '<th rowspan="2" class="customer-item-grid">'.s("Grille tarifaire").'</th>';
					$h .= '<th rowspan="2" class="customer-item-contact">'.s("Contact").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					$h .= '<th rowspan="2"></th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-end highlight-stick-right">'.$year.'</th>';
					$h .= '<th class="text-end customer-item-year-before highlight-stick-left">'.$yearBefore.'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cCustomer as $eCustomer) {

					$eSaleTotal = $eCustomer['eSaleTotal'];

					$h .= '<tr>';

						$h .= '<td class="customer-item-name">';
							$h .= '<a href="/client/'.$eCustomer['id'].'">'.encode($eCustomer->getName()).'</a>';
							$h .= '<div class="util-annotation">';
								$h .= self::getCategory($eCustomer);
								if($eCustomer['color']) {
									$h .= ' | '.CustomerUi::getColorCircle($eCustomer);
								}
							$h .= '</div>';
						$h .= '</td>';

						$h .= '<td class="text-center">';
							if($eCustomer['user']->notEmpty()) {
								$h .= '<b>'.s("oui").'</b>';
							} else if($eCustomer['invite']->notEmpty()) {
								$h .= s("invitation envoyée");
							} else {
								$h .= s("non");
							}
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-right">';
							if($eSaleTotal->notEmpty() and $eSaleTotal['year']) {
								$amount = \util\TextUi::money($eSaleTotal['year'], precision: 0);
								$h .= $eFarm->canAnalyze() ? '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
							} else {
								$h .= '-';
							}
						$h .= '</td>';

						$h .= '<td class="text-end customer-item-year-before highlight-stick-left">';
							if($eSaleTotal->notEmpty() and $eSaleTotal['yearBefore']) {
								$amount = \util\TextUi::money($eSaleTotal['yearBefore'], precision: 0);
								$h .= $eFarm->canAnalyze() ? '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
							} else {
								$h .= '-';
							}
						$h .= '</td>';

						$h .= '<td class="customer-item-grid">';
							if($eCustomer['prices'] > 0) {
								$h .= p("{value} prix", "{value} prix", $eCustomer['prices']);
							} else {
								$h .= '-';
							}
						$h .= '</td>';

						$h .= '<td class="customer-item-contact">';
							if($eCustomer['phone']) {
								$h .= '<div>';
									$h .= encode($eCustomer['phone']);
								$h .= '</div>';
							} else {
								$h .= '-';
							}
						$h .= '</td>';

						$h .= '<td class="customer-item-status td-min-content">';
							$h .= $this->toggle($eCustomer);
						$h .= '</td>';

						$h .= '<td class="customer-item-actions">';
							$h .= $this->getUpdate($eCustomer, 'btn-outline-secondary');
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		if($nCustomer !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nCustomer / 100);
		}

		return $h;

	}

	public function toggle(Customer $eCustomer) {

		return \util\TextUi::switch([
			'id' => 'customer-switch-'.$eCustomer['id'],
			'data-ajax' => $eCustomer->canManage() ? '/selling/customer:doUpdateStatus' : NULL,
			'post-id' => $eCustomer['id'],
			'post-status' => ($eCustomer['status'] === Customer::ACTIVE) ? Customer::INACTIVE : Customer::ACTIVE
		], $eCustomer['status'] === Customer::ACTIVE);

	}

	public function displayTitle(Customer $eCustomer): string {

		$h = '<div class="util-action">';

			$h .= '<div>';

				$h .= '<h1 style="margin-bottom:0.25rem;">';
					if($eCustomer['color']) {
						$h .= CustomerUi::getColorCircle($eCustomer).' ';
					}
					$h .= $eCustomer->getName();
				$h .= '</h1>';

				$h .= '<div>';
					$h .= $this->toggle($eCustomer);
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div>';
				$h .= $this->getUpdate($eCustomer, 'btn-primary');
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function display(Customer $eCustomer, \Collection $cSaleTurnover, \Collection $cGrid, \Collection $cSale, \Collection $cInvoice, \Collection $cPaymentMethod): string {

		$eCustomer->expects(['invite']);

		$h = '';

		if($eCustomer['invite']->isPending() and $eCustomer['invite']->canWrite()) {
			$h .= '<div class="util-block stick-xs">';
				$h .= '<h3>'.s("Invitation").'</h3>';
				$h .= '<p>';
					if($eCustomer['invite']->isValid()) {
						$h .= s("Vous avez invité ce client à créer son compte client avec l'adresse e-mail {email}. Le client n'a pas encore suivi les instructions présentes dans le mail que nous lui avons envoyé. Il a jusqu'au {date} pour le faire, après quoi cette invitation sera effacée.", ['email' => '<u>'.encode($eCustomer['invite']['email']).'</u>', 'date' => \util\DateUi::numeric($eCustomer['invite']['expiresAt'])]);
					} else {
						$h .= s("Vous avez invité ce client à créer son compte client avec l'adresse e-mail {email} mais cette invitation a expiré le {date}.", ['email' => '<u>'.encode($eCustomer['invite']['email']).'</u>', 'date' => \util\DateUi::numeric($eCustomer['invite']['expiresAt'])]);
					}
				$h .= '</p>';
				$h .= '<p>'.s("Si votre client ne retrouve pas l'e-mail qu'il a reçu, vous pouvez lui communiquer directement ce lien :").'</p>';
				$h .= '<div class="input-group">';
					$h .= '<div class="form-control" id="invite-url">'.$eCustomer['invite']->getLink().'</div>';
					$h .= '<a onclick="doCopy(this)" data-selector="#invite-url" data-message="'.s("Copié !").'" class="btn btn-primary">'.\Asset::icon('clipboard').' '.s("Copier").'</a>';
				$h .= '</div>';
				$h .= '<br/><br/>';
				$h .= '<a data-ajax="/farm/invite:doDelete" post-id="'.$eCustomer['invite']['id'].'" class="btn btn-secondary">'.s("Annuler l'invitation").'</a>';
			$h .= '</div>';
		}

		if($eCustomer['destination'] !== Customer::COLLECTIVE) {

			$type = self::getCategory($eCustomer);

			$h .= '<div class="util-block stick-xs">';
				$h .= '<dl class="util-presentation util-presentation-2">';
					$h .= '<dt>'.s("Catégorie").'</dt>';
					$h .= '<dd>'.$type.'</dd>';
					$h .= '<dt>'.s("Téléphone").'</dt>';
					$h .= '<dd>'.($eCustomer['phone'] !== NULL ? encode($eCustomer['phone']) : '').'</dd>';
					$h .= '<dt>'.s("Compte client").'</dt>';
					$h .= '<dd>'.($eCustomer['user']->notEmpty() ? \Asset::icon('person-fill').' '.encode($eCustomer['user']['email']) : s("Non")).'</dd>';
					$h .= '<dt>'.s("Adresse e-mail").'</dt>';
					$h .= '<dd>';
						$email = $eCustomer['email'] ?? NULL;
						if($email !== NULL) {
							$h .= '<a href="mailto:'.encode($email).'">'.encode($email).'</a>';
						}
					$h .= '</dd>';
					if($eCustomer['type'] === Customer::PRO) {

						$h .= '<dt>'.s("Facturation").'</dt>';
						$h .= '<dd>';

							if($eCustomer->hasInvoiceAddress()) {
								$h .= '<address>';
									$h .= encode($eCustomer->getLegalName()).'<br/>';
									$h .= nl2br(encode($eCustomer->getInvoiceAddress()));
								$h .= '</address>';
							}

						$h .= '</dd>';

					}
					$h .= '<dt>'.s("Communication par e-mail").'</dt>';
					$h .= '<dd>';
						$h .= s("Opt-out {value}", $eCustomer['emailOptOut'] ? \Asset::icon('check-circle') : \Asset::icon('x-circle')).'<br/>';
						$h .= s("Opt-in {value}", $eCustomer['emailOptIn'] === NULL ? \Asset::icon('question-circle') : ($eCustomer['emailOptIn'] ? \Asset::icon('check-circle') : \Asset::icon('x-circle')));
					$h .= '</dd>';
					$h .= '<dt>'.s("Remise commerciale").'</dt>';
					$h .= '<dd>'.($eCustomer['discount'] > 0 ? s("{value} %", $eCustomer['discount']) : '').'</dd>';
				$h .= '</dl>';
			$h .= '</div>';

		} else {
			$h .= '<div class="util-block">'.s("Ce client est un point de vente aux particuliers.").'</div>';
		}

		$h .= '<div class="tabs-h" id="customer-tabs-wrapper" onrender="'.encode('Lime.Tab.restore(this, "sales")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="sales" onclick="Lime.Tab.select(this)">';
					$h .= s("Ventes");
				$h .= '</a>';
				if($cInvoice->notEmpty()) {
					$h .= '<a class="tab-item" data-tab="invoices" onclick="Lime.Tab.select(this)">';
						$h .= s("Factures");
					$h .= '</a>';
				}
				if($eCustomer->canGrid()) {
					$h .= '<a class="tab-item" data-tab="grid" onclick="Lime.Tab.select(this)">';
						$h .= s("Grille tarifaire");
					$h .= '</a>';
				}
			$h .= '</div>';

			$h .= '<div>';
				$h .= '<div data-tab="sales" class="tab-panel selected">';

					if(
						$eCustomer['farm']->canAnalyze() and
						$cSaleTurnover->notEmpty()
					) {
						$h .= new AnalyzeUi()->getCustomerTurnover($cSaleTurnover, NULL, $eCustomer);
					}

					$h .= new \selling\SaleUi()->getList($eCustomer['farm'], $cSale, hide: ['customer'], cPaymentMethod: $cPaymentMethod);
				$h .= '</div>';

				if($cInvoice->notEmpty()) {
					$h .= '<div data-tab="invoices" class="tab-panel">';
						$h .= new \selling\InvoiceUi()->getList($cInvoice, hide: ['customer']);
					$h .= '</div>';
				}

				if($eCustomer->canGrid()) {
					$h .= '<div data-tab="grid" class="tab-panel">';
						$h .= new \selling\GridUi()->getGridByCustomer($eCustomer, $cGrid);
					$h .= '</div>';
				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getUpdate(Customer $eCustomer, string $btn): string {

		if($eCustomer->canWrite() === FALSE) {
			return '';
		}

		$eCustomer->expects(['invite']);

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.encode($eCustomer->getName()).'</div>';
			$h .= '<a href="/selling/sale:create?farm='.$eCustomer['farm']['id'].'&customer='.$eCustomer['id'].'" class="dropdown-item">'.s("Créer une vente").'</a>';
			$h .= '<a href="/selling/customer:update?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Modifier le client").'</a>';

			if($eCustomer->canManage()) {

				if(
					$eCustomer['destination'] !== Customer::COLLECTIVE and
					$eCustomer['user']->empty() and
					$eCustomer['invite']->empty()
				) {
					$h .= '<a href="/farm/invite:createCustomer?customer='.$eCustomer['id'].'" class="dropdown-item">'.s("Inviter à créer un compte client").'</a>';
				}

				if($eCustomer['type'] === Customer::PRO) {
					$h .= '<a href="/selling/customer:updateGrid?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Personnaliser la grille tarifaire").'</a>';
				}

			}

			if(
				$eCustomer->canManage() or
				$eCustomer->canDelete()
			) {

				$h .= '<div class="dropdown-divider"></div>';

				if($eCustomer->canDelete()) {
					$h .= '<a data-ajax="/selling/customer:doDelete" post-id="'.$eCustomer['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression du client ?").'">'.s("Supprimer le client").'</a>';
				}

			}
		$h .= '</div>';

		return $h;

	}

	public function getHome(\Collection $cCustomerPro, \Collection $cShop, \Collection $cSale, \Collection $cInvoice): string {

		$h = '';

		if($cCustomerPro->notEmpty()) {

			$h .= '<h2>';
				$h .= s("Mes producteurs");
				$h .= ' <small class="color-muted">'.s("en tant que professionnel").'</small>';
			$h .= '</h2>';

			$h .= new \selling\OrderUi()->getPro($cCustomerPro);

		}

		if($cShop->notEmpty()) {
			$h .= '<h2>'.s("Les prochaines ventes de mes producteurs").'</h2>';
			$h .= new \shop\ShopUi()->getWidgetCollection($cShop);

		}

		if($cSale->notEmpty()) {

			$h .= '<h2>';
				$h .= s("Mes dernières commandes");
				if($cCustomerPro->notEmpty()) {
					$h .= ' <small class="color-muted">'.s("en tant que particulier").'</small>';
				}
			$h .= '</h2>';

			$h .= new \selling\OrderUi()->getSales($cSale, Customer::PRIVATE);

			if($cSale->count() === 5) {

				$h .= '<div>';
					$h .= '<a href="/commandes/particuliers" class="btn btn-secondary">'.\Asset::icon('search').' '.s("Toutes mes commandes").'</a>';
				$h .= '</div>';

			}

			$h .= '<br/>';

		}

		if($cInvoice->notEmpty()) {

			$h .= '<h2>';
				$h .= s("Mes dernières factures");
				if($cCustomerPro->notEmpty()) {
					$h .= ' <small class="color-muted">'.s("en tant que particulier").'</small>';
				}
			$h .= '</h2>';

			$h .= new \selling\OrderUi()->getInvoices($cInvoice, Customer::PRIVATE);

			if($cInvoice->count() === 5) {

				$h .= '<div>';
					$h .= '<a href="/factures/particuliers" class="btn btn-secondary">'.\Asset::icon('search').' '.s("Toutes mes factures").'</a>';
				$h .= '</div>';

			}

			$h .= '<br/>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$eCustomer = new Customer([
			'farm' => $eFarm,
			'type' => NULL,
			'destination' => NULL,
			'user' => new \user\User()
		]);

		$h = '';

		$h .= $form->openAjax('/selling/customer:doCreate', ['class' => 'customer-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroup($eCustomer, 'category*', function(\PropertyDescriber $d) use($eFarm) {

				if($eFarm->getSelling('hasVat')) {
					$d->after = \util\FormUi::info(s("Veuillez noter que les prix sur les documents de vente sont affichés en TTC pour les clients particuliers et en HT pour les clients professionnels. Seuls les points de vente aux particuliers sont compatibles avec le logiciel de caisse."));
				}

			});

			$h .= $this->write('create', $form, $eCustomer);

			$h .= $form->group(
				content: $form->submit(s("Créer le client"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-customer-create',
			title: s("Ajouter un client"),
			body: $h
		);

	}

	public function update(Customer $eCustomer): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$formClass = ($eCustomer['destination'] === Customer::COLLECTIVE) ? 'customer-form-'.Customer::COLLECTIVE : 'customer-form-'.$eCustomer['type'];

		$h .= $form->openAjax('/selling/customer:doUpdate', ['class' => $formClass]);

			$h .= $form->hidden('id', $eCustomer['id']);

			if($eCustomer['destination'] !== Customer::COLLECTIVE) {
				$h .= $form->dynamicGroup($eCustomer, 'category');
			}
			$h .= $this->write('update', $form, $eCustomer);

			$h .= '<div class="customer-form-category customer-form-private customer-form-pro">';
				$h .= '<div class="util-block bg-background-light">';
					$h .= $form->group(content: '<h4>'.s("Gestion de la communication par e-mail").'</h4>');
					$h .= $form->dynamicGroup($eCustomer, 'emailOptOut');
					$h .= $form->group(
						self::p('emailOptIn')->label.self::p('emailOptIn')->labelAfter,
						$eCustomer->getEmailOptIn()
					);
				$h .= '</div>';
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-customer-update',
			title: s("Modifier un client"),
			body: $h
		);

	}

	protected function write(string $action, \util\FormUi $form, Customer $eCustomer) {

		$eCustomer->expects(['user']);

		$h = '<div class="util-block bg-background-light customer-form-type">';
			$h .= '<div class="customer-form-category customer-form-pro">';
				$h .= $form->group(content: '<h4>'.s("Client professionnel").'</h4>');
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-private">';
				$h .= $form->group(content: '<h4>'.s("Client particulier").'</h4>');
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-collective">';
				$h .= $form->group(content: '<h4>'.s("Point de vente pour les particuliers").'</h4>');
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-pro customer-form-collective">';
				$h .= $form->dynamicGroup($eCustomer, match($action) {
					'create' => 'name*',
					'update' => 'name*'
				});
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-private">';
				$h .= $form->dynamicGroups($eCustomer, match($action) {
					'create' => ['firstName', 'lastName*'],
					'update' => ['firstName', 'lastName*']
				});
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-pro">';
				$h .= $form->dynamicGroups($eCustomer, ['legalName']);
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-private customer-form-pro">';
				$h .= $form->dynamicGroups($eCustomer, ['email', 'phone']);
			$h .= '</div>';
			$h .= '<div class="customer-form-category customer-form-pro">';
				$h .= $form->addressGroup(s("Adresse de facturation"), 'invoice', $eCustomer);
				$h .= $form->dynamicGroups($eCustomer, ['invoiceRegistration', 'invoiceVat']);
			$h .= '</div>';
			if($action === 'update') {
				$h .= '<div class="customer-form-category customer-form-private customer-form-pro">';
					$h .= $form->dynamicGroup($eCustomer, 'discount');
				$h .= '</div>';
				$h .= '<div class="customer-form-category customer-form-collective customer-form-pro">';
					$h .= $form->dynamicGroup($eCustomer, 'color');
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function updateOptIn(\Collection $cCustomer): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/customer:doUpdateOptIn');

			$h .= '<h4>'.s("Recevoir les communications des producteurs").'</h4>';

			$h .= '<p class="util-info">';
				$h .= s("Vos producteurs sont susceptibles de vous envoyer des communications par e-mail, selon une fréquence et un contenu qu'ils choisissent eux-mêmes. Vous pouvez choisir de recevoir ces communications ou les refuser.");
			$h .= '</p>';

			foreach($cCustomer as $eCustomer) {

				$h .= $form->group(
					\farm\FarmUi::link($eCustomer['farm'], TRUE),
					$form->yesNo('customer['.$eCustomer['id'].']', $eCustomer['emailOptIn'] ?? TRUE, [
						'yes' => s("Oui, les recevoir"),
						'no' => s("Ne rien recevoir")
					])
				);

			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer mes préférences"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-customer-email',
			title: s("Préférences de communication par e-mail"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Customer::model()->describer($property, [
			'name' => s("Nom"),
			'firstName' => s("Prénom"),
			'lastName' => s("Nom"),
			'email' => s("Adresse e-mail"),
			'category' => s("Catégorie"),
			'market' => s("Marché"),
			'farm' => s("Ferme"),
			'discount' => s("Remise commerciale"),
			'legalName' => s("Raison sociale"),
			'phone' => s("Numéro de téléphone"),
			'color' => s("Couleur de représentation"),
			'invoiceRegistration' => s("Numéro d'immatriculation SIRET"),
			'invoiceVat' => s("Numéro de TVA intracommunautaire"),
			'emailOptOut' => s("Opt-out"),
			'emailOptIn' => s("Opt-in"),
		]);

		switch($property) {

			case 'category' :
				$d->field = 'radio';
				$d->values = function(Customer $e) {

					$values = [];
					$values[Customer::PRIVATE] = s("Client particulier");

					if($e['type'] === NULL) {
						$values[Customer::COLLECTIVE] = s("Point de vente pour les particuliers").'<br/><small style="color: var(--muted) ">'.\Asset::icon('arrow-return-right').' '.s("Marché / Vente à la ferme / AMAP").'</small>';
					}

					$values[Customer::PRO] = s("Client professionnel");

					return $values;

				};
				$d->default = function(Customer $e) {
					return $e->empty() ? NULL : ($e['destination'] === Customer::COLLECTIVE ? Customer::COLLECTIVE : $e['type']);
				};
				$d->after = fn(\util\FormUi $form, Customer $e) => $e->offsetExists('id') ? \util\FormUi::info(s("La modification de catégorie n'est pas rétroactive sur les ventes que vous auriez déjà créées pour ce client.")) : '';
				$d->attributes = [
					'mandatory' => TRUE,
					'callbackRadioAttributes' => fn() => ['oninput' => 'Customer.changeCategory(this)']
				];
				break;

			case 'firstName' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(\Asset::icon('person-circle').' '.s("Le client a aussi la main sur son prénom et est susceptible de le modifier de lui-même.")).'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'lastName' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(\Asset::icon('person-circle').' '.s("Le client a aussi la main sur son nom et est susceptible de le modifier de lui-même.")).'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'phone' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(\Asset::icon('person-circle').' '.s("Le client a aussi la main sur son numéro de téléphone et est susceptible de le modifier de lui-même.")).'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'legalName' :
				$d->attributes = [
					'placeholder' => s("Ex. : SARL Le Bon Légume"),
				];
				$d->after = \util\FormUi::info(s("Laisser vide si elle est identique au nom du client"));
				break;

			case 'email' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(\Asset::icon('person-circle').' '.s("Le client a aussi la main sur son adresse e-mail et est susceptible de le modifier de lui-même.")).'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'discount' :
				$d->append = s("%");
				$d->after = \util\FormUi::info(s("Cette remise commerciale s'applique automatiquement au prix par défaut de tous les produits commandés par ce client."));
				break;

			case 'emailOptOut' :
				$d->field = 'yesNo';
				$d->labelAfter = \util\FormUi::info(s("Envoyer des communications par e-mail à ce client"));
				break;

			case 'emailOptIn' :
				$d->labelAfter = \util\FormUi::info(s("Consentement du client pour recevoir des communications par e-mail"));
				break;

		}

		return $d;

	}

}
?>
