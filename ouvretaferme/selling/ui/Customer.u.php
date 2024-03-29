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
			return self::name($eCustomer);
		} else {
			return '<a href="'.self::url($eCustomer).'" '.($newTab ? 'target="_blank"' : '').'>'.self::name($eCustomer).'</a>';
		}
	}

	public static function name(Customer $eCustomer): string {
		if($eCustomer->empty()) {
			return s("Client anonyme");
		} else {
			return encode($eCustomer['name']);
		}
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

		$d->placeholder ??= s("Tapez un nom de client...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'customer'];

		$d->autocompleteUrl = '/selling/customer:query';
		$d->autocompleteResults = function(Customer $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Customer $eCustomer): array {

		\Asset::css('media', 'media.css');

		$item = '<div>'.encode($eCustomer['name']).'<br/><small class="color-muted">'.self::p('type')->values[$eCustomer['type']].'</small></div>';

		return [
			'value' => $eCustomer['id'],
			'discount' => $eCustomer['discount'],
			'type' => $eCustomer['type'],
			'itemHtml' => $item,
			'itemText' => $eCustomer['name']
		];

	}

	public function getPanelHeader(Customer $eCustomer): string {

		return '<div class="customer-panel-header">'.encode($eCustomer['name']).'</div>';

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="customer-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlSellingCustomer($eFarm);

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du client")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCustomer, ?int $nCustomer = NULL, \Search $search = new \Search(), ?int $page = NULL) {

		if($cCustomer->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucun client à afficher.").'</div>';
		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$h = '<table class="customer-item-table tr-bordered tr-even stick-xs">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2">'.$search->linkSort('name', s("Nom")).'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Compte client").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Ventes").'</th>';
					$h .= '<th rowspan="2" class="customer-item-grid">'.s("Grille tarifaire").'</th>';
					$h .= '<th rowspan="2" class="customer-item-contact">'.s("Contact").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Actif").'</th>';
					$h .= '<th rowspan="2"></th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-end">'.$year.'</th>';
					$h .= '<th class="text-end customer-item-year-before">'.$yearBefore.'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cCustomer as $eCustomer) {

					$eSaleTotal = $eCustomer['eSaleTotal'];

					$h .= '<tr';
						if($eCustomer['status'] === Customer::INACTIVE) {
							$h .= ' class="color-muted"';
						}
					$h .= '>';

						$h .= '<td class="customer-item-name">';
							$h .= '<a href="/client/'.$eCustomer['id'].'">'.encode($eCustomer['name']).'</a>';
							$h .= '<div class="util-annotation">';
								$h .= self::p('type')->values[$eCustomer['type']];
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

						$h .= '<td class="text-end">';
							if($eSaleTotal->notEmpty() and $eSaleTotal['year']) {
								$amount = \util\TextUi::money($eSaleTotal['year'], precision: 0);
								$h .= $eFarm->canAnalyze() ? '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
							} else {
								$h .= '-';
							}
						$h .= '</td>';

						$h .= '<td class="text-end customer-item-year-before">';
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

						$h .= '<td class="customer-item-status text-center">';
							$h .= match($eCustomer['status']) {
								Customer::ACTIVE => '<span class="color-success" title="'.s("Actif").'">'.\Asset::icon('check-lg').'</span>',
								Customer::INACTIVE => '<span class="color-warning" title="'.s("Désactivé").'">'.\Asset::icon('pause-fill').'</span>'
							};
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

	public function getOne(Customer $eCustomer, \Collection $cSaleTurnover, \Collection $cGrid, \Collection $cSale, \Collection $cInvoice): string {

		$eCustomer->expects(['invite']);

		$h = '<div class="util-action">';

			$h .= '<h1>';
				if($eCustomer['color']) {
					$h .= CustomerUi::getColorCircle($eCustomer).' ';
				}
				$h .= encode($eCustomer['name']);
			$h .= '</h1>';

			$h .= '<div>';
				$h .= $this->getUpdate($eCustomer, 'btn-primary');
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-action-subtitle">';
			$h .= match($eCustomer['status']) {
				Customer::ACTIVE => '<span class="color-success">'.\Asset::icon('check-lg').'</span> '.s("Client actif"),
				Customer::INACTIVE => '<span class="color-warning">'.\Asset::icon('pause-fill').'</span> '.s("Client désactivé")
			};
		$h .= '</div>';

		if($eCustomer['invite']->isValid() and $eCustomer['invite']->canWrite()) {
			$h .= '<div class="util-block-gradient color-secondary">';
				$h .= '<p>'.\Asset::icon('info-circle').' '.s("Vous avez invité ce client à créer son compte client avec l'adresse e-mail {email}. Le client n'a pas encore créé son compte client en suivant les instructions présentes dans le mail que nous lui avons envoyé. Il a jusqu'au {date} pour le faire, après quoi cette invitation sera effacée.", ['email' => '<u>'.encode($eCustomer['invite']['email']).'</u>', 'date' => \util\DateUi::numeric($eCustomer['invite']['expiresAt'])]).'</p>';
				$h .= '<a data-ajax="/farm/invite:doDelete" post-id="'.$eCustomer['invite']['id'].'" class="btn btn-secondary">'.s("Annuler l'invitation").'</a>';
			$h .= '</div>';
		}

		$type = self::p('type')->values[$eCustomer['type']];

		$h .= '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Catégorie").'</dt>';
				$h .= '<dd>'.$type.'</dd>';
				$h .= '<dt>'.s("Téléphone").'</dt>';
				$h .= '<dd>'.($eCustomer['phone'] ? encode($eCustomer['phone']) : '').'</dd>';
				$h .= '<dt>'.s("Compte client").'</dt>';
				$h .= '<dd>'.($eCustomer['user']->notEmpty() ? \Asset::icon('person-fill').' '.encode($eCustomer['user']['email']) : s("Non")).'</dd>';
				$h .= '<dt>'.s("Adresse e-mail").'</dt>';
				$h .= '<dd>';
					$email = $eCustomer['email'] ?? $eCustomer['user']['email'] ?? NULL;
					if($email !== NULL) {
						$h .= '<a href="mailto:'.encode($email).'">'.encode($email).'</a>';
					}
				$h .= '</dd>';
				if($eCustomer['type'] === Customer::PRO) {

					$h .= '<dt>'.s("Facturation").'</dt>';
					$h .= '<dd>';

						if($eCustomer->hasInvoiceAddress()) {
							$h .= '<address>';
								$h .= encode($eCustomer['legalName'] ?? $eCustomer['name']).'<br/>';
								$h .= nl2br(encode($eCustomer->getInvoiceAddress()));
							$h .= '</address>';
						}

					$h .= '</dd>';

					$h .= '<dt>'.s("Interlocuteur").'</dt>';
					$h .= '<dd>'.($eCustomer['contact'] ? encode($eCustomer['contact']) : '').'</dd>';

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
						$h .= (new AnalyzeUi())->getCustomerTurnover($cSaleTurnover, NULL, $eCustomer);
					}

					$h .= (new \selling\SaleUi())->getList($eCustomer['farm'], $cSale, hide: ['customer']);
				$h .= '</div>';

				if($cInvoice->notEmpty()) {
					$h .= '<div data-tab="invoices" class="tab-panel">';
						$h .= (new \selling\InvoiceUi())->getList($cInvoice, hide: ['customer']);
					$h .= '</div>';
				}

				if($eCustomer->canGrid()) {
					$h .= '<div data-tab="grid" class="tab-panel">';
						$h .= (new \selling\GridUi())->getGridByCustomer($eCustomer, $cGrid);
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
			$h .= '<div class="dropdown-title">'.encode($eCustomer['name']).'</div>';
			$h .= '<a href="/selling/customer:update?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Modifier le client").'</a>';

			if($eCustomer->canManage()) {

				if($eCustomer['user']->empty() and $eCustomer['invite']->empty()) {
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

				if($eCustomer->canManage()) {

					$h .= match($eCustomer['status']) {
						Customer::INACTIVE => '<a data-ajax="/selling/customer:doUpdateStatus" post-id="'.$eCustomer['id'].'" post-status="'.Customer::ACTIVE.'" class="dropdown-item">'.s("Réactiver le client").'</a>',
						Customer::ACTIVE => '<a data-ajax="/selling/customer:doUpdateStatus" post-id="'.$eCustomer['id'].'" post-status="'.Customer::INACTIVE.'" class="dropdown-item">'.s("Désactiver le client").'</a>'
					};

				}

				if($eCustomer->canDelete()) {
					$h .= '<a data-ajax="/selling/customer:doDelete" post-id="'.$eCustomer['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression du client ?").'">'.s("Supprimer le client").'</a>';
				}

			}
		$h .= '</div>';

		return $h;

	}

	public function getHome(\Collection $cCustomerPro, \Collection $cShop, \Collection $cSale): string {

		$h = '';

		if($cCustomerPro->notEmpty()) {

			$h .= '<h2>';
				$h .= s("Mes producteurs");
				$h .= ' <small class="color-muted">'.s("en tant que professionnel").'</small>';
			$h .= '</h2>';

			$h .= (new \selling\OrderUi())->getPro($cCustomerPro);

		}

		if($cShop->notEmpty()) {
			$h .= '<h2>'.s("Les prochaines ventes de mes producteurs").'</h2>';
			$h .= (new \shop\ShopUi())->getWidgetCollection($cShop);

		}


		if($cSale->notEmpty()) {

			$h .= '<h2>';
				$h .= s("Mes dernières commandes");
				if($cCustomerPro->notEmpty()) {
					$h .= ' <small class="color-muted">'.s("en tant que particulier").'</small>';
				}
			$h .= '</h2>';

			$h .= (new \selling\OrderUi())->getListForPrivate($cSale);

			if($cSale->count() === 5) {

				$h .= '<div>';
					$h .= '<a href="/commandes/particuliers" class="btn btn-secondary">'.\Asset::icon('search').' '.s("Toutes mes commandes").'</a>';
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
			'user' => new \user\User()
		]);

		$h = '';

		$h .= $form->openAjax('/selling/customer:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($eCustomer, ['type*', 'name*', 'email', 'phone'], [
				'type' => function(\PropertyDescriber $d) use ($eFarm) {

					if($eFarm['selling']['hasVat']) {
						$d->after = \util\FormUi::info(s("Les prix sur les documents de ventes sont affichés en TTC pour les clients particuliers et en HT pour les clients pros."));
					}

				}
			]);

			$h .= '<div class="util-block-flat bg-background-light customer-pro '.($eCustomer['type'] === Customer::PRO ? '' : 'hide').'">';
				$h .= $form->group(content: '<h4>'.s("Gestion des clients professionnels").'</h4>');
				$h .= $form->dynamicGroups($eCustomer, ['contact', 'legalName']);
				$h .= $form->addressGroup(s("Adresse de facturation"), 'invoice', $eCustomer);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Créer le client"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter un client"),
			body: $h
		);

	}

	public function update(Customer $eCustomer): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/customer:doUpdate', ['class' => 'customer-form-'.$eCustomer['type']]);

			$h .= $form->hidden('id', $eCustomer['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eCustomer['farm'], TRUE)
			);

			$h .= $form->dynamicGroups($eCustomer, ['type', 'name', 'email', 'phone']);
			$h .= '<div class="util-block-flat bg-background-light customer-pro '.($eCustomer['type'] === Customer::PRO ? '' : 'hide').'">';
				$h .= $form->group(content: '<h4>'.s("Gestion des clients professionnels").'</h4>');
				$h .= $form->dynamicGroups($eCustomer, ['contact', 'legalName']);
				$h .= $form->addressGroup(s("Adresse de facturation"), 'invoice', $eCustomer);
			$h .= '</div>';
			$h .= $form->dynamicGroups($eCustomer, ['discount', 'color']);

			$h .= '<div class="util-block-flat bg-background-light">';
				$h .= $form->group(content: '<h4>'.s("Gestion de la communication par e-mail").'</h4>');
				$h .= $form->dynamicGroup($eCustomer, 'emailOptOut');
				$h .= $form->group(
					self::p('emailOptIn')->label.self::p('emailOptIn')->labelAfter,
					$eCustomer->getEmailOptIn()
				);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un client"),
			body: $h
		);

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
			title: s("Préférences de communication par e-mail"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Customer::model()->describer($property, [
			'name' => s("Nom du client"),
			'email' => s("Adresse e-mail"),
			'type' => s("Catégorie"),
			'market' => s("Marché"),
			'contact' => s("Interlocuteur principal"),
			'farm' => s("Ferme"),
			'discount' => s("Remise commerciale"),
			'legalName' => s("Raison sociale"),
			'phone' => s("Numéro de téléphone"),
			'color' => s("Couleur de représentation"),
			'emailOptOut' => s("Opt-out"),
			'emailOptIn' => s("Opt-in"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					Customer::PRIVATE => s("Particulier"),
					Customer::PRO => s("Pro"),
				];
				$d->after = fn(\util\FormUi $form, Customer $e) => $e->offsetExists('id') ? \util\FormUi::info(s("La modification de catégorie n'est pas rétroactive sur les ventes que vous auriez déjà créées pour ce client.")) : '';
				$d->attributes += [
					'callbackRadioAttributes' => function() {
						return ['oninput' => 'Customer.changeType(this)'];
					}
				];
				break;

			case 'name' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(s("Le client a aussi la main sur son nom et est susceptible de le modifier de lui-même."), 'person-circle').'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'phone' :
				$d->after = function(\util\FormUi $form, Customer $e) {
					$e->expects(['user']);
					if($e['user']->notEmpty()) {
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(s("Le client a aussi la main sur son numéro de téléphone et est susceptible de le modifier de lui-même."), 'person-circle').'</div>';
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
						return '<div class="customer-form-itself-private">'.\util\FormUi::info(s("Le client a aussi la main sur son adresse e-mail et est susceptible de le modifier de lui-même."), 'person-circle').'</div>';
					} else {
						return NULL;
					}
				};
				break;

			case 'discount' :
				$d->append = s("%");
				$d->after = fn(\util\FormUi $form, Customer $e) => match($e['type']) {
					Customer::PRIVATE => \util\FormUi::info(s("Cette remise commerciale s'applique automatiquement à tous les produits commandés par ce client.")),
					Customer::PRO => \util\FormUi::info(s("Cette remise commerciale s'applique automatiquement à tous les produits commandés par ce client."))
				};
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
