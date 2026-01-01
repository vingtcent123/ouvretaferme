<?php
namespace selling;

class CustomerUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'customer.js');

		\Asset::css('sequence', 'crop.css');

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

	public static function getType(Customer|Sale|CustomerGroup $eCustomer): string {

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

		$h = '<div id="customer-search" class="util-block-search '.($search->empty(['cGroup']) ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlSellingCustomers($eFarm);

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Nom").'</legend>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom de client")]);
				$h .= '</fieldset>';
				if($search->get('cGroup')->notEmpty()) {
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Groupe").'</legend>';
						$h .= $form->select('group', $search->get('cGroup'), $search->get('group'));
					$h .= '</fieldset>';
				}
				$h .= '<fieldset>';
					$h .= '<legend>'.s("E-mail").'</legend>';
					$h .= $form->email('email', $search->get('email'), ['placeholder' => s("E-mail de client")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Catégorie").'</legend>';
					$h .= $form->select('category', self::getCategories(), $search->get('category'));
				$h .= '</fieldset>';
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-outline-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCustomer, \Collection $cCustomerGroup, ?int $nCustomer = NULL, \Search $search = new \Search(), array $hide = [], ?int $page = NULL) {

		if($cCustomer->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucun client à afficher.").'</div>';
		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$h = '<table class="customer-item-table tr-even stick-xs" data-batch="#batch-customer">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2" class="td-checkbox">';
						$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
							$h .= '<input type="checkbox" class="batch-all" onclick="Customer.toggleSelection(this)"/>';
						$h .= '</label>';
					$h .= '</th>';
					$h .= '<th rowspan="2">';
						$label = s("Prénom");
						$h .= ($search ? $search->linkSort('firstName', $label) : $label).' / ';
						$label = s("Nom");
						$h .= ($search ? $search->linkSort('lastName', $label) : $label);
					$h .= '</th>';
					if(in_array('sales', $hide) === FALSE) {
						$h .= '<th colspan="2" class="text-center hide-xs-down highlight">'.s("Ventes").'</th>';
					}
					if(in_array('prices', $hide) === FALSE) {
						$h .= '<th rowspan="2" class="customer-item-grid">'.s("Prix personnalisés").'</th>';
					}
					$h .= '<th rowspan="2" class="customer-item-contact">'.s("Contact").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					if(in_array('actions', $hide) === FALSE) {
						$h .= '<th rowspan="2"></th>';
					}
				$h .= '</tr>';
				if(in_array('sales', $hide) === FALSE) {
					$h .= '<tr>';
						$h .= '<th class="text-end hide-xs-down highlight-stick-right">'.$year.'</th>';
						$h .= '<th class="text-end hide-xs-down customer-item-year-before highlight-stick-left">'.$yearBefore.'</th>';
					$h .= '</tr>';
				}
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cCustomer as $eCustomer) {

					$batch = [];

					if($eCustomer['status'] === Customer::INACTIVE) {
						$batch[] = 'not-active';
					}

					if($eCustomer->isCollective()) {
						$batch[] = 'not-group';
					}

					if($eCustomer->isPrivate()) {
						$batch[] = 'not-pro';
					}

					if($eCustomer->isPro()) {
						$batch[] = 'not-private';
					}

					$h .= '<tr class="'.($eCustomer['status'] === Customer::INACTIVE ? 'tr-disabled' : '').'">';

						$h .= '<td class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eCustomer['id'].'" oninput="Customer.changeSelection()" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</label>';
						$h .= '</td>';

						$h .= '<td>';

							$h .= '<div class="customer-item-info">';
								$h .= '<div>';

									$h .= '<a href="/client/'.$eCustomer['id'].'">'.encode($eCustomer->getName()).'</a>';
									if($eCustomer['user']->notEmpty()) {
										$h .= ' <span title="'.s("Ce client a un compte client à partir duquel il peut se connecter").'">'.\Asset::icon('person-circle').'</span> ';
									}
									$h .= '<div class="util-annotation">';
										$h .= self::getCategory($eCustomer);
										if($eCustomer['color']) {
											$h .= ' | '.CustomerUi::getColorCircle($eCustomer);
										}
									$h .= '</div>';

								$h .= '</div>';

								$h .= '<div class="customer-item-label">';
									if(in_array('actions', $hide) === FALSE and $eCustomer['invite']->notEmpty()) {
										$h .= '<span class="util-badge bg-primary">'.\Asset::icon('person-fill').' '.s("invitation envoyée").'</span> ';
									}
									$h .= $this->getGroups($eCustomer);
								$h .= '</div>';

							$h .= '</div>';

						$h .= '</td>';

						if(in_array('sales', $hide) === FALSE) {

							$eSaleTotal = $eCustomer['eSaleTotal'];

							$h .= '<td class="text-end hide-xs-down highlight-stick-right">';
								if($eSaleTotal->notEmpty() and $eSaleTotal['year']) {
									$amount = \util\TextUi::money($eSaleTotal['year'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							$h .= '</td>';

							$h .= '<td class="text-end hide-xs-down customer-item-year-before highlight-stick-left">';
								if($eSaleTotal->notEmpty() and $eSaleTotal['yearBefore']) {
									$amount = \util\TextUi::money($eSaleTotal['yearBefore'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						}

						if(in_array('prices', $hide) === FALSE) {

							$h .= '<td class="customer-item-grid">';
								if($eCustomer['prices'] > 0) {
									$h .= p("{value} prix", "{value} prix", $eCustomer['prices']);
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						}

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

						if(in_array('actions', $hide) === FALSE) {
							$h .= '<td class="customer-item-actions">';
								$h .= $this->getUpdate($eCustomer, 'btn-outline-secondary');
							$h .= '</td>';
						}

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		if($nCustomer !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nCustomer / 100);
		}

		$h .= $this->getBatch($eFarm, $cCustomerGroup);

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cCustomerGroup): string {

		$menu = '';

		if($cCustomerGroup->count() > 0) {

			$menu .= '<a data-dropdown="top-start" class="batch-group batch-item">';
				$menu .= \Asset::icon('tag');
				$menu .= '<span>'.s("Groupe").'</span>';
			$menu .= '</a>';

			$menu .= '<div class="dropdown-list bg-secondary">';
				$menu .= '<div class="dropdown-title">'.s("Modifier les groupes").'</div>';
				foreach($cCustomerGroup as $eCustomerGroup) {
					$menu .= '<div class="dropdown-subtitle batch-type batch-'.$eCustomerGroup['type'].'">';
						$menu .= '<span class="util-badge" style="background-color: '.$eCustomerGroup['color'].'">'.encode($eCustomerGroup['name']).'</span>';
					$menu .= '</div>';
					$menu .= '<div class="dropdown-items-2 batch-type batch-'.$eCustomerGroup['type'].'">';
						$menu .= '<a data-ajax-submit="/selling/customer:doUpdateGroupAssociateCollection" data-ajax-target="#batch-customer-form" post-group="'.$eCustomerGroup['id'].'" class="dropdown-item">'.\Asset::icon('plus').' '.s(text: "Ajouter").'</a>';
						$menu .= '<a data-ajax-submit="/selling/customer:doUpdateGroupDissociateCollection" data-ajax-target="#batch-customer-form" post-group="'.$eCustomerGroup['id'].'" class="dropdown-item">'.\Asset::icon('x').' '.s("Retirer").'</a>';
					$menu .= '</div>';
				}
			$menu .= '</div>';

		}

		$menu .= '<a data-url-collection="/selling/sale:createCollection?farm='.$eFarm['id'].'" data-url="/selling/sale:create?farm='.$eFarm['id'].'&customer=" class="batch-sale batch-item">';
			$menu .= \Asset::icon('plus-circle');
			$menu .= '<span>'.s("Créer une vente").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/customer:doUpdateStatusCollection" post-status="'.Customer::ACTIVE.'" data-confirm="'.s("Activer ces clients ?").'" class="batch-item">';
			$menu .= \Asset::icon('toggle-on');
			$menu .= '<span>'.s("Activer").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/customer:doUpdateStatusCollection" post-status="'.Customer::INACTIVE.'" data-confirm="'.s("Désactiver ces clients ?").'" class="batch-item">';
			$menu .= \Asset::icon('toggle-off');
			$menu .= '<span>'.s("Désactiver").'</span>';
		$menu .= '</a>';

		return \util\BatchUi::group('batch-customer', $menu, title: s("Pour les clients sélectionnés"));

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

	public function getOne(Customer $eCustomer, \Collection $cSale): string {

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
						if($eCustomer['contact']->isEmailValid()) {
							$h .= ' <span class="color-secondary" title="'.s("E-mail vérifié").'">'.\Asset::icon('check-circle-fill', ['class' => 'asset-icon-lg']).'</span>';
						} else if($eCustomer['contact']->isEmailBlocked()) {
							$h .= ' <span class="color-danger" title="'.s("E-mail en erreur").'">'.\Asset::icon('x-circle-fill', ['class' => 'asset-icon-lg']).'</span>';
						}
					$h .= '</dd>';

					if($eCustomer['type'] === Customer::PRO) {

						$h .= '<dt>'.s("Facturation").'</dt>';
						$h .= '<dd>';

							if($eCustomer->hasInvoiceAddress()) {
								$h .= '<address>';
									$h .= encode($eCustomer->getLegalName()).'<br/>';
									$h .= $eCustomer->getInvoiceAddress('html');
								$h .= '</address>';
							}

						$h .= '</dd>';

					}

					if($eCustomer['discount'] > 0) {
						$h .= '<dt>'.s("Remise commerciale").'</dt>';
						$h .= '<dd>'.s("{value} %", $eCustomer['discount']).'</dd>';
					}

					if($eCustomer['groups']) {
						$h .= '<dt>'.p("Groupe", "Groupes", count($eCustomer['groups'])).'</dt>';
						$h .= '<dd>';
							$h .= $this->getGroups($eCustomer);
						$h .= '</dd>';
					}
				$h .= '</dl>';
			$h .= '</div>';

		} else {

			if($cSale->empty()) {
				$h .= '<div class="util-block-help">';
					$h .= '<h3>'.s("Point de vente aux particuliers").'</h3>';
					$h .= '<p>'.s("Le logiciel de caisse proposé par Ouvretaferme peut être utilisé pour ce point de vente aux particuliers.").'</p>';
					$h .= '<a href="/doc/selling:market" class="btn btn-secondary">'.s("En savoir plus sur le logiciel de caisse").'</a>';
				$h .= '</div>';
			} else {
				$h .= '<div class="util-block">'.s("Ce client est un point de vente aux particuliers.").'</div>';
			}
		}

		return $h;

	}

	public function getTabs(Customer $eCustomer, \Collection $cSaleTurnover, \Collection $cGrid, \Collection $cGridGroup, \Collection $cSale, \Collection $cEmail, \Collection $cInvoice, \Collection $cPaymentMethod): string {

		$h = '<div class="tabs-h" id="customer-tabs-wrapper" onrender="'.encode('Lime.Tab.restore(this, "sales")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="sales" onclick="Lime.Tab.select(this)">';
					$h .= s("Ventes").' <span class="tab-item-count">'.$cSale->count().'</span>';
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="grid" onclick="Lime.Tab.select(this)">';
					$h .= s("Prix personnalisés").' <span class="tab-item-count">'.$cGrid->count().'</span>';
					if($cGridGroup->count() > 0) {
						$h .= '  +<span class="tab-item-count">'.$cGridGroup->count().'</span>';
					}
				$h .= '</a>';
				if($cInvoice->notEmpty()) {
					$h .= '<a class="tab-item" data-tab="invoices" onclick="Lime.Tab.select(this)">';
						$h .= s("Factures").' <span class="tab-item-count">'.$cInvoice->count().'</span>';
					$h .= '</a>';
				}
				if($eCustomer['contact']->notEmpty()) {
					$h .= '<a class="tab-item" data-tab="emails" onclick="Lime.Tab.select(this)">';
						$h .= s("E-mails");
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

					$h .= new \selling\SaleUi()->getList($eCustomer['farm'], $cSale, hide: ['customer'], show: ['average'], cPaymentMethod: $cPaymentMethod);

					if($cSale->empty()) {

						$h .= '<a href="/selling/sale:create?farm='.$eCustomer['farm']['id'].'&customer='.$eCustomer['id'].'" class="btn btn-primary btn-lg">'.s("Créer une première vente").'</a>';
					}

				$h .= '</div>';

				$h .= '<div data-tab="grid" class="tab-panel">';
					$h .= new \selling\GridUi()->getGridByCustomer($eCustomer, $cGrid, $cGridGroup);
				$h .= '</div>';

				if($cInvoice->notEmpty()) {
					$h .= '<div data-tab="invoices" class="tab-panel">';
						$h .= new \selling\InvoiceUi()->getList($cInvoice, hide: ['customer']);
					$h .= '</div>';
				}

				if($eCustomer['contact']->notEmpty()) {
					$h .= '<div data-tab="emails" class="tab-panel">';

						$h .= new \mail\ContactUi()->getOpt($eCustomer['contact']);

						if($cEmail->notEmpty()) {

							$h .= '<h3>'.s("E-mails envoyés dans les 12 derniers mois").'</h3>';
							$h .= new \mail\EmailUi()->getList($cEmail, hide: ['customer']);

						}
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
			$h .= '<h2>'.s("Les boutiques de mes producteurs").'</h2>';
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

	public function create(Customer $eCustomer): \Panel {

		$eCustomer->expects(['nGroup', 'farm', 'user']);

		$form = new \util\FormUi();

		$eCustomer['type'] = NULL;
		$eCustomer['destination'] = NULL;

		$eFarm = $eCustomer['farm'];

		$h = '';

		$h .= $form->openAjax('/selling/customer:doCreate', ['class' => 'customer-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroup($eCustomer, 'category*', function(\PropertyDescriber $d) use($eFarm) {

				if($eFarm->getConf('hasVat')) {
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

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-customer-update',
			title: s("Modifier un client"),
			body: $h
		);

	}

	public function getGroupField(\util\FormUi $form, Customer $eCustomer): string {

		$h = '<div id="customer-group-field">';
			$h .= $form->dynamicGroup($eCustomer, 'groups');
		$h .= '</div>';

		return $h;

	}

	protected function write(string $action, \util\FormUi $form, Customer $eCustomer) {

		$eCustomer->expects(['user', 'nGroup']);

		$h = '';

		if($action === 'update' and $eCustomer['nGroup'] > 0) {

			$h = '<div class="customer-form-category customer-form-pro customer-form-private">';
				$h .= $this->getGroupField($form, $eCustomer);
			$h .= '</div>';

		}

		$h .= '<div class="util-block bg-background-light customer-form-type">';
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
				$h .= $form->dynamicGroups($eCustomer, ['siret', 'vatNumber']);
			$h .= '</div>';
			if($action === 'update') {
				$h .= '<div class="customer-form-category customer-form-private customer-form-pro">';
					$h .= $form->dynamicGroup($eCustomer, 'discount');
				$h .= '</div>';
				$h .= '<div class="customer-form-category customer-form-pro">';
					$h .= $form->group(
						s("Personnaliser les adresses e-mail pour l'envoi de certains documents"),
						$form->dynamicField($eCustomer, 'orderFormEmail').
						$form->dynamicField($eCustomer, 'deliveryNoteEmail').
						$form->dynamicField($eCustomer, 'invoiceEmail')
					);
				$h .= '</div>';
				$h .= '<div class="customer-form-category customer-form-private customer-form-pro">';
					$h .= $form->dynamicGroup($eCustomer, 'defaultPaymentMethod');
				$h .= '</div>';
				$h .= '<div class="customer-form-category customer-form-collective customer-form-pro">';
					$h .= $form->dynamicGroup($eCustomer, 'color');
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getGroups(Customer $eCustomer): string {

		$h = '';

		foreach($eCustomer['cGroup?']() as $eCustomerGroup) {
			$eCustomerGroup['farm'] = $eCustomer['farm'];
			$h .= ' '.CustomerGroupUi::link($eCustomerGroup).' ';
		}

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Customer::model()->describer($property, [
			'name' => s("Nom"),
			'firstName' => s("Prénom"),
			'lastName' => s("Nom"),
			'email' => s("Adresse e-mail"),
			'groups' => s("Groupe"),
			'orderFormEmail' => s("Adresse e-mail pour l'envoi des devis"),
			'deliveryNoteEmail' => s("Adresse e-mail pour l'envoi des bons de livraison"),
			'invoiceEmail' => s("Adresse e-mail pour l'envoi des factures"),
			'category' => s("Type de client"),
			'farm' => s("Ferme"),
			'discount' => s("Remise commerciale"),
			'defaultPaymentMethod' => s("Moyen de paiement par défaut"),
			'legalName' => s("Raison sociale"),
			'phone' => s("Numéro de téléphone"),
			'color' => s("Couleur de représentation"),
			'siret' => s("Numéro d'immatriculation SIRET"),
			'vatNumber' => s("Numéro de TVA intracommunautaire"),
		]);

		switch($property) {

			case 'category' :
				$d->field = 'radio';
				$d->values = function(Customer $e) {

					$values = [];
					$values[Customer::PRIVATE] = s("Client particulier");
					$values[Customer::PRO] = s("Client professionnel");

					if($e['type'] === NULL) {
						$values[Customer::COLLECTIVE] = s("Point de vente pour les particuliers").'<span class="hide-sm-up"><br/></span><span class="hide-xs-down">  </span><small class="color-muted">'.s("(marché, vente à la ferme, AMAP, ...)").'</small><br/><div class="btn btn-xs btn-selling" style="margin-top: 0.5rem">'.\Asset::icon('cart4').' '.s("Logiciel de caisse").'</div>';
					}


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

			case 'groups' :
				$d->autocompleteDefault = fn(Customer $e) => ($e['cGroup?'] ?? $e->expects(['cGroup?']))();
				$d->autocompleteBody = function(\util\FormUi $form, Customer $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type'],
					];
				};
				new \selling\CustomerGroupUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'groups'];
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

			case 'orderFormEmail' :
			case 'deliveryNoteEmail' :
			case 'invoiceEmail' :

				$label = match($property) {
					'orderFormEmail' => s("Devis"),
					'deliveryNoteEmail' => s("Bons de livraison"),
					'invoiceEmail' => s("Factures")
				};

				$d->inputGroup['style'] = 'margin-bottom: 0.25rem';
				$d->prepend = \util\FormUi::info($label);
				$d->placeholder = s("Utiliser l'adresse e-mail par défaut");
				break;

			case 'invoiceCountry' :
				$d->values = fn(Customer $e) => \user\Country::form();
				$d->attributes = fn(\util\FormUi $form, Customer $e) => [
					'group' => is_array(\user\Country::form()),
				];
				break;

			case 'discount' :
				$d->append = s("%");
				$d->after = \util\FormUi::info(s("Si vous modifiez la remise commerciale, elle s'appliquera automatiquement à toutes les futures ventes créées pour ce client."));
				break;

			case 'defaultPaymentMethod' :
				$d->values = fn(Customer $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->placeholder = s("Non défini");
				$d->labelAfter = \util\FormUi::info(s("Ce moyen de paiement sera associé par défaut aux ventes créées pour ce client"));
				break;

		}

		return $d;

	}

}
?>
