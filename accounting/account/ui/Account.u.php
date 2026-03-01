<?php
namespace account;

class AccountUi {

	public function __construct() {

		\Asset::css('company', 'company.css');
		\Asset::css('account', 'account.css');

		\Asset::js('account', 'account.js');
		\Asset::js('account', 'settings.js');

	}

	public function getManageTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les numéros de compte");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#account-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/account:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un compte personnalisé").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="account-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Numéro de compte").'</legend>';
					$h .= $form->text('classPrefix', $search->get('classPrefix'), ['placeholder' => s("Numéro de compte")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Libellé").'</legend>';
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Libellé")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Compte de TVA").'</legend>';
					$h .= $form->select('vatFilter', [
						0 => s("Peu importe"),
						1 => s("Avec compte de TVA"),
					], (int)$search->get('vatFilter'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Comptes personnalisés").'</legend>';
					$h .= $form->select('customFilter', [
						0 => s("Peu importe"),
						1 => s("Uniquement ceux-là"),
					], (int)$search->get('customFilter'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function list(\farm\Farm $eFarm, \Collection $cAccount, \Collection $cJournalCode): string {

		\Asset::css('company' , 'company.css');

		if($cAccount->empty() === TRUE) {
			return '<div class="util-empty">'.s("Aucun compte n'a encore été enregistré").'</div>';
		}

		$displayProductsCount = $cAccount->match(fn($eAccount) => ($eAccount['nProductPro'] ?? 0) > 0 or ($eAccount['nProductPrivate'] ?? 0) > 0);
		$displayOperationsCount = $cAccount->match(fn($eAccount) => (array_sum($eAccount['operationByFinancialYear']) ?? 0) > 0);
		$displayThirdParty = $cAccount->match(fn($eAccount) => $eAccount['thirdParty']->notEmpty() > 0);
		$displayActions = $cAccount->match(fn($eAccount) => $eAccount->acceptDelete()) > 0;
		$displayActive = $cAccount->match(fn($eAccount) => $eAccount['custom'] === FALSE) > 0;

		\Asset::css('util', 'batch.css');
		\Asset::js('util', 'batch.js');

		$h = '<div class="util-block-help">';
			$h .= s("Il est possible de créer des numéros de compte personnalisés, par exemple pour créer un compte-courant par associé. Cela vous permettra de mieux analyser vos flux.");
		$h .= '</div>';

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table id="account-list" class="tr-even tr-hover" data-batch="#batch-journal">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox" rowspan="2">';
							$h .= '<input type="checkbox" name="batch[]" batch-type="item" value="" oninput="AccountSettings.toggleSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th rowspan="2">';
							$h .= s("Numéro de compte");
						$h .= '</th>';
						$h .= '<th rowspan="2">';
							$h .= s("Libellé");
						$h .= '</th>';
						$h .= '<th rowspan="2" class="text-center">';
							$h .= s("Journal");
						$h .= '</th>';
						if($displayThirdParty) {
							$h .= '<th rowspan="2">'.s("Tiers").'</th>';
						}
						$h .= '<th colspan="2" class="text-center t-highlight">';
							$h .= s("TVA");
						$h .= '</th>';

						if($displayOperationsCount) {
							if($eFarm['cFinancialYear']->count() >= 2) {
								$colspan = 2;
							} else {
								$colspan = 1;
							}
							$h .= '<th colspan="'.$colspan.'" class="text-center t-highlight">';
								$h .= s("Opérations");
							$h .= '</th>';
						}

						if($displayProductsCount) {
							$h .= '<th colspan="2" class="text-center t-highlight">';
								$h .= s("Produits");
							$h .= '</th>';
						}

						if($displayActive) {
							$h .= '<th rowspan="2">'.s("Activé").'</th>';
						}

						if($displayActions) {
							$h .= '<th rowspan="2"></th>';
						}
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="t-highlight">';
							$h .= s("Compte");
						$h .= '</th>';
						$h .= '<th class="t-highlight">';
							$h .= s("Taux");
						$h .= '</th>';
						$financialYears = [];
						$nFinancialYear = 0;
						if($displayOperationsCount) {
							$nFinancialYear = 0;
							foreach($eFarm['cFinancialYear'] as $eFinancialYear) {
								if($nFinancialYear >= 2) {
									break;
								}
								if($eFarm['cFinancialYear']->count() >= 2) {
									if($nFinancialYear === 0) {
										$class = 'text-end t-highlight';
									} else {
										$class = 'text-end t-highlight';
									}
								} else {
									$class = 'text-center t-highlight';
								}
								$nFinancialYear++;
								$financialYears[] = $eFinancialYear['id'];
								$h .= '<th class="'.$class.'">'.$eFinancialYear->getLabel().'</th>';
							}
						}
						if($displayProductsCount) {
							$h .= '<th class="text-center t-highlight">';
								$h .= s("Particulier");
							$h .= '</th>';
							$h .= '<th class="text-center t-highlight">';
								$h .= s("Pro");
							$h .= '</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cAccount as $eAccount) {

					$classNumber = strlen($eAccount['class']) - 2;

					$h .= '<tr name="account-'.$eAccount['id'].'" class="'.($eAccount['visible'] ? '' : 'account_not-visible').' '.($eAccount['status'] !== Account::ACTIVE ? 'tr-disabled' : '').'">';

						$h .= '<td class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eAccount['id'].'"oninput="AccountSettings.changeSelection(this)"/>';
							$h .= '</label>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<span class="ml-'.$classNumber.'">';
								$h .= $classNumber === 0 ? '<b>' : '';
									if($eAccount->acceptQuickUpdate('description')) {
										$eAccount->setQuickAttribute('farm', $eFarm['id']);
										$eAccount->setQuickAttribute('property', 'class');
										$h .= $eAccount->quick('class', encode($eAccount['class']));
									} else {
										$h .= encode($eAccount['class']);
									}
								$h .= $classNumber === 0 ? '</b>' : '';
							$h .= '</span>';
						$h .= '</td>';


						$h .= '<td>';
							$h .= '<span class="ml-'.$classNumber.'">';
								$h .= $classNumber === 0 ? '<b>' : '';
									if($eAccount->acceptQuickUpdate('description')) {
										$eAccount->setQuickAttribute('farm', $eFarm['id']);
										$eAccount->setQuickAttribute('property', 'description');
										$h .= $eAccount->quick('description', encode($eAccount['description']));
									} else {
										$h .= encode($eAccount['description']);
									}
								$h .= $classNumber === 0 ? '</b>' : '';
						$h .= '</td>';

						$h .= '<td class="td-min-content text-center">';
							if($eAccount->acceptQuickUpdate('journalCode')) {
								$eAccount->setQuickAttribute('farm', $eFarm['id']);
								$eAccount->setQuickAttribute('property', 'journalCode');
								$h .= $eAccount->quick('journalCode', $eAccount['journalCode']->notEmpty() ? new \journal\JournalCodeUi()->getColoredName($eAccount['journalCode']) : \Asset::icon('plus-circle'));
							}
						$h .= '</td>';

						if($displayThirdParty) {
							$h .= '<td class="text-center">';
								if($eAccount['thirdParty']->notEmpty()) {
									$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/thirdParty?name='.urlencode($eAccount['thirdParty']['name']).'">'.encode($eAccount['thirdParty']['name']).'</a>';
								}
							$h .= '</td>';
						}

						$h .= '<td class="t-highlight">';
							if($eAccount['vatAccount']->notEmpty()) {
								$h .= '<a '.attr('onclick', 'AccountSettings.scrollTo('.$eAccount['vatAccount']['id'].');').'>'.encode($eAccount['vatAccount']['class']).'</a>';
							}
						$h .= '</td>';

						$h .= '<td class="text-center td-min-content t-highlight">';

							if(strlen($eAccount['class']) >= 3) {

								if($eAccount['vatRate'] !== NULL) {
									$vatRate = $eAccount['vatRate'].'%';
								} else if($eAccount['vatAccount']->exists() === TRUE) {
									$vatRate = '<span class="color-muted" title="'.s("Taux de TVA par défaut").'">'.\Asset::icon('magic').' ';
										$vatRate .= encode($eAccount['vatAccount']['vatRate'] ?? 0).'%';
									$vatRate .= '</span>';
								} else  {
									$vatRate = NULL;
								}

								if($vatRate === NULL) {
									$h .= '<span class="color-muted">'.s("N/A").'</span>';
								} else if($eAccount->acceptQuickUpdate('vatRate')) {
									$eAccount->setQuickAttribute('farm', $eFarm['id']);
									$eAccount->setQuickAttribute('property', 'vatRate');
									$h .= $eAccount->quick('vatRate', $vatRate);
								} else {
									$h .= $vatRate;
								}
							}

						$h .= '</td>';

						if($displayOperationsCount) {

							$nFinancialYearCurrent = 0;
							foreach($financialYears as $financialYear) {

								$eFinancialYear = $eFarm['cFinancialYear']->offsetGet($financialYear);

								if($nFinancialYear === 1) {
									$class = 't-highlight';
								} else if($nFinancialYearCurrent === 0) {
									$class = 't-highlight';
								} else {
									$class = 't-highlight';
								}
								$h .= '<td class="text-end '.$class.'">';

									if(($eAccount['operationByFinancialYear'][$financialYear] ?? 0) > 0) {
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm, $eFinancialYear).'/livre-journal?accountLabel='.$eAccount['class'].'"  title="'.s("Filtrer les opérations sur ce numéro de compte").'">'.($eAccount['operationByFinancialYear'][$financialYear] ?? 0).'</a>';
									}

								$h .= '</td>';

								$nFinancialYearCurrent++;
							}

						}

						if($displayProductsCount) {
							$h .= '<td class="text-center t-highlight"><a href="'.new \farm\FarmUi()->urlSellingProductsAll($eFarm).'?proAccount='.$eAccount['id'].'">'.(($eAccount['nProductPro'] ?? 0) > 0 ? $eAccount['nProductPro'] : '').'</a></td>';
							$h .= '<td class="text-center t-highlight"><a href="'.new \farm\FarmUi()->urlSellingProductsAll($eFarm).'?privateAccount='.$eAccount['id'].'">'.(($eAccount['nProductPrivate'] ?? 0) > 0 ? $eAccount['nProductPrivate'] : '').'</td>';
						}

						if($displayActive) {
							$h .= '<td>';
							if($eAccount['custom'] === TRUE) {
								$h .= \util\TextUi::switch([
										'id' => 'product-switch-'.$eAccount['id'],
										'data-ajax' => $eAccount->canWrite() ? \company\CompanyUi::urlAccount($eFarm).'/account:doUpdateStatus' : NULL,
										'post-id' => $eAccount['id'],
										'post-status' => ($eAccount['status'] === Account::ACTIVE) ? Account::INACTIVE : Account::ACTIVE
									], $eAccount['status'] === Account::ACTIVE);
								$h .= '</td>';
							}
						}


						if($displayActions) {
							$h .= '<td>';
								if($eAccount->acceptDelete()) {
										$message = s("Confirmez-vous la suppression de ce numéro de compte ?");
										$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/account:doDelete" post-id="'.$eAccount['id'].'" data-confirm="'.$message.'" class="btn btn-outline-secondary btn-outline-danger">'.\Asset::icon('trash').'</a>';
								}
							$h .= '</td>';
						}
					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $cJournalCode);

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cJournalCode): string {

		$menu = '<a data-dropdown="top-start" class="batch-journal-code batch-item">';
			$menu .= \Asset::icon('journal-bookmark');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Journal").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';

			$menu .= '<div class="dropdown-title">'.s("Changer de journal").'</div>';
			foreach($cJournalCode as $eJournalCode) {
				$menu .= '<a style="margin: 0.25rem;" data-ajax-submit="'.\company\CompanyUi::urlAccount($eFarm).'/account:doUpdateJournalCollection" data-ajax-target="#batch-journal-form" post-journal-code="'.$eJournalCode['id'].'" class="dropdown-item">'.new \journal\JournalCodeUi()->getColoredName($eJournalCode).'</a>';
			}
			$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateJournalCollection" data-ajax-target="#batch-journal-form" post-journal-code="" class="dropdown-item"><i>'.s("Pas de journal").'</i></a>';
		$menu .= '</div>';

		return \util\BatchUi::group('batch-journal', $menu, '', title: s("Pour les numéros de compte sélectionnés"));

	}
	public function getDropdownTitle(Account $eAccount, ?string $more = NULL): string {

		$h = '<div class="dropdown-list bg-primary">';
			$h .= '<span class="dropdown-item">'.encode($eAccount['class']).' '.encode($eAccount['description']).''.$more.'</span>';
		$h .= '</div>';

		return $h;

	}

	public static function getVatRuleByAccount(Account $eAccount, FinancialYear $eFinancialYear): ?string {

		if($eAccount['class'] !== NULL) {

			foreach(AccountSetting::VAT_RULES_ACCOUNTS as $vatCode => $accounts) {

				foreach($accounts as $account) {

					if(str_starts_with('!', $account)) {
						$exclude = TRUE;
					} else {
						$exclude = FALSE;
					}

					if(AccountLabelLib::isFromClass($eAccount['class'], (string)$account)) {
						if($exclude) {
							break;
						}
						return $vatCode;
					}

				}
			}
		}

		if($eAccount['vatAccount']->empty()) {
			return \journal\Operation::VAT_HC;
		}

		return \journal\Operation::VAT_STD;

	}

	public static function getAutocomplete(\farm\Farm $eFarm, Account|\company\GenericAccount $eAccount, \Search $search = new \Search()): array {

		\Asset::css('media', 'media.css');

		$itemHtml = encode($eAccount['class'].' '.$eAccount['description']);
		if(
			$search->get('classPrefix')
			and $search->get('classPrefix') === (string)AccountSetting::VAT_CLASS
			and $eAccount['vatRate'] !== NULL
		) {
			$itemHtml .= ' ('.$eAccount['vatRate'].'%)';
		}

		$autocomplete = [
			'value' => $eAccount['id'],
			'itemHtml' => $itemHtml,
			'itemText' => $eAccount['class'].' '.$eAccount['description'],
		];

		if($search->get('withDetails')) {
			$autocomplete['class'] = encode($eAccount['class']);
			$autocomplete['description'] = encode($eAccount['description']);
			$autocomplete['farm'] = $eFarm['id'];
		}
		if($search->get('withJournal')) {
			$autocomplete['journalCode'] = ($eAccount['journalCode']['id'] ?? NULL);
		}
		if($search->get('withVat')) {

			$eAccount->expects(['vatAccount']);

			$vatRate = 0.0;
			$vatClass = '';
			if($eAccount['vatAccount']->exists() === TRUE) {
				$vatClass = $eAccount['vatAccount']['class'];
				if($eAccount['vatRate'] !== NULL) {
					$vatRate = $eAccount['vatRate'];
				} else {
					$vatRate = $eAccount['vatAccount']['vatRate'];
				}
			}

			$autocomplete['vatRate'] = $vatRate;
			$autocomplete['vatClass'] = $vatClass;
			$autocomplete['vatRule'] = self::getVatRuleByAccount($eAccount, $eFarm['eFinancialYear'] ?? new FinancialYear());
		}

		return $autocomplete;

	}

	public static function getAutocompleteWithout(\farm\Farm $eFarm): array {

		$text = s("Sans numéro de compte");

		return [
			'value' => 0,
			'class' => '',
			'description' => $text,
			'vatRate' => 0,
			'vatClass' => '',
			'farm' => $eFarm['id'],
			'itemHtml' => $text,
			'itemText' => $text,
			'journalCode' => NULL,
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('gear');
		$item .= '<div>'.s("Gérer les comptes").'</div>';

		return [
			'type' => 'link',
			'link' => \company\CompanyUi::urlAccount($eFarm).'/account',
			'itemHtml' => $item,
			'target' => '_blank',
		];

	}

	public function query(\PropertyDescriber $d, \farm\Farm $eFarm, bool $multiple = FALSE, array|\Closure $query = []): void {

		$d->prepend = \Asset::icon('journal-text');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir le numéro de compte...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = function(\util\FormUi $form, $e) use ($eFarm, $query) {

			if($eFarm->empty()) {
				$eFarm = $e['farm'];
			}

			if($query instanceof \Closure) {
				$query = $query($e);
			}

			return \company\CompanyUi::urlAccount($eFarm).'/account:query?'.http_build_query($query);
		};

		$d->autocompleteResults = function(Account|\company\GenericAccount $eAccount, $e = NULL) use ($eFarm) {
			if($eFarm->empty() and $e !== NULL) {
				$eFarm = $e['farm'];
			}
			if($eAccount['id'] === 0) {
				return self::getAutocompleteWithout($eFarm);
			}
			return self::getAutocomplete($eFarm, $eAccount);
		};

	}

	public static function getAutocompleteLabel(string $query, \farm\Farm $eFarm, string $label): array {

		\Asset::css('media', 'media.css');

		return [
			'value' => $label,
			'farm' => $eFarm['id'],
			'itemHtml' => str_replace($query, '<b>'.$query.'</b>', $label),
			'itemText' => encode($label),
		];

	}

	public function queryLabel(\PropertyDescriber $d, \farm\Farm $eFarm, ?string $query, bool $multiple = FALSE): void {

		$d->prepend = \Asset::icon('123');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir le numéro de compte...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlAccount($eFarm).'/account:queryLabel';
		$d->autocompleteResults = function(string $label) use ($eFarm, $query) {
			return self::getAutocompleteLabel($query, $eFarm, $label);
		};

		$d->autocompleteTextual = TRUE;
	}

	public function create(\farm\Farm $eFarm, Account $eAccount): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/account:doCreate', ['id' => 'account-account-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eAccount, ['class*', 'description*', 'thirdParty*', 'vatAccount', 'vatRate'], ['class*' => function(\PropertyDescriber $d) {
			$d->after = NULL;
		}]);

		$h .= $form->group(
			content: $form->submit(s("Créer le numéro de compte"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-account-create',
			title: s("Ajouter un numéro de compte personnalisé"),
			body: $h
		);

	}

	public static function getSummaryBalanceCategories(): array {
		return [
			['min' => 10, 'max' => 12, 'name' => s("Capital, report, résultat")],
			['min' => 13, 'max' => 13, 'name' => s("Subventions d'investissement")],
			['min' => 14, 'max' => 15, 'name' => s("Provisions")],
			['min' => 16, 'max' => 16, 'name' => s("Emprunts")],
			['min' => 17, 'max' => 18, 'name' => s("Dettes rattachées, comptes de liaisons")],
			['min' => 20, 'max' => 20, 'name' => s("Immobilisations incorporelles")],
			['min' => 21, 'max' => 24, 'name' => s("Immobilisations corporelles et en cours")],
			['min' => 25, 'max' => 27, 'name' => s("Participations et autres immo. financières")],
			['min' => 28, 'max' => 28, 'name' => s("Amortissments")],
			['min' => 29, 'max' => 29, 'name' => s("Provisions pour dépréciations")],
			['min' => 30, 'max' => 30, 'name' => s("Approvisionnements et marchandises")],
			['min' => 31, 'max' => 32, 'name' => s("Animaux")],
			['min' => 33, 'max' => 34, 'name' => s("Végétaux en terre")],
			['min' => 35, 'max' => 36, 'name' => s("En cours de production")],
			['min' => 37, 'max' => 37, 'name' => s("Produits")],
			['min' => 38, 'max' => 38, 'name' => s("Inventaire permanent")],
			['min' => 40, 'max' => 40, 'name' => s("Fournisseurs")],
			['min' => 41, 'max' => 41, 'name' => s("Clients")],
			['min' => 42, 'max' => 42, 'name' => s("Personnels")],
			['min' => 43, 'max' => 43, 'name' => s("MSA et autres organismes sociaux")],
			['min' => 44, 'max' => 44, 'name' => s("État et autres collectivités publiques")],
			['min' => 45, 'max' => 45, 'name' => s("Groupe, communautés d'exploitation")],
			['min' => 46, 'max' => 46, 'name' => s("Débiteurs et créditeurs divers")],
			['min' => 47, 'max' => 47, 'name' => s("Comptes transitoires")],
			['min' => 48, 'max' => 48, 'name' => s("Comptes de régularisation")],
			['min' => 49, 'max' => 49, 'name' => s("Provisions pour dépréciation")],
			['min' => 50, 'max' => 50, 'name' => s("Valeurs mobilières de placement")],
			['min' => 51, 'max' => 51, 'name' => s("Banques")],
			['min' => 52, 'max' => 52, 'name' => s("Instruments de trésorerie")],
			['min' => 53, 'max' => 53, 'name' => s("Caisse")],
			['min' => 54, 'max' => 54, 'name' => s("Règles d'avance")],
			['min' => 58, 'max' => 58, 'name' => s("Virements internes")],
			['min' => 59, 'max' => 59, 'name' => s("Provisions pour dépréciation")],
			['min' => 603, 'max' => 603, 'name' => s("Variation des stocks")],
			['min' => 60, 'max' => 60, 'name' => s("Achats")],
			['min' => 61, 'max' => 62, 'name' => s("Charges externes")],
			['min' => 63, 'max' => 63, 'name' => s("Impôts et taxes")],
			['min' => 64, 'max' => 64, 'name' => s("Charges de personnels")],
			['min' => 65, 'max' => 65, 'name' => s("Autres charges de gestion")],
			['min' => 66, 'max' => 66, 'name' => s("Charges financières")],
			['min' => 67, 'max' => 67, 'name' => s("Charges exceptionnelles")],
			['min' => 68, 'max' => 68, 'name' => s("Dotations aux amortissements")],
			['min' => 69, 'max' => 69, 'name' => s("IS et participation des salariés")],
			['min' => 70, 'max' => 70, 'name' => s("Ventes")],
			['min' => 71, 'max' => 72, 'name' => s("Variation inventaire")],
			['min' => 73, 'max' => 73, 'name' => s("Production immobilisée")],
			['min' => 74, 'max' => 74, 'name' => s("Produits nets partiels")],
			['min' => 75, 'max' => 75, 'name' => s("Indemnités et subventions")],
			['min' => 76, 'max' => 76, 'name' => s("Produits financiers")],
			['min' => 77, 'max' => 77, 'name' => s("Produits exceptionnels")],
			['min' => 78, 'max' => 78, 'name' => s("Reprises sur amortissements")],
			['min' => 79, 'max' => 79, 'name' => s("Transferts de charges")],
		];
	}

	public static function getPassifBalanceCategories(): array {

		return [
			'capitaux-propres' => [
				'name' => s("Capitaux propres"),
				'categories' => [
					[
						'name' => s("Capital social"),
						'accounts' => [1015],
					],
					[
						'name' => s("Primes d'émission, de fusion, d'apport"),
						'accounts' => [108],
					],
					[
						'name' => s("Écarts de réévaluation"),
						'accounts' => [],
					],
					[
						'name' => s("Réserves"),
						'accounts' => [106],
					],
					[
						'name' => s("Résultat de l'exercice (bénéfice ou perte)"),
						'accounts' => [120, 129],
					],
					[
						'name' => s("Subventions d'investissement"),
						'accounts' => [131, 138, 139],
					],
					[
						'name' => s("Amortissements dérogatoires"),
						'accounts' => [145],
					],
					[
						'name' => s("Autres provisions réglementées"),
						'accounts' => [],
					],
				]
			],
			'provisions' => [
				'name' => s("Provisions"),
				'categories' => [
					[
						'name' => s("Provisions pour risques et charges"),
						'accounts' => [15],
					],
				]
			],
			'dettes' => [
				'name' => s("Dettes"),
				'categories' => [
					[
						'name' => s("Dettes financières"),
						'accounts' => [164],
					],
					[
						'name' => s("Avances et acomptes reçus sur commandes"),
						'accounts' => [419],
					],
					[
						'name' => s("Autres dettes"),
						'accounts' => [401, 408, 421, 455],
					],
					[
						'name' => s("Instruments de trésorerie"),
						'accounts' => [],
					],
					[
						'name' => s("Produits constatés d'avance"),
						'accounts' => [487],
					],
					[
						'name' => s("Écarts de conversion - Passif"),
						'accounts' => [],
					],
				],
			],
		];

	}
	public static function getActifBalanceCategories(): array {

		return [
			'actif-immobilise' => [
				'name' => s("Actif immobilisé"),
				'categories' => [
					[
						'name' => s("Capital souscrit non appelé"),
						'accounts' => [109],
					],
					[
						'name' => s("Immobilisations incorporelles"),
						'accounts' => [201, /*203, */205, 206, 207, 208, /*232, */237],
					],
					[
						'name' => s("Immobilisations corporelles hors biens vivants"),
						'accounts' => [211, 212, 213, 214, 215, 218, 231, 238],
					],
					[
						'name' => s("Immobilisations corporelles biens vivants"),
						'accounts' => [241, 243, 246],
					],
					[
						'name' => s("Immobilisations financières"),
						'accounts' => [261, 266, 267, 268, 271, 272, 273, 274, 275, 276, 277],
					],
				]
			],
			'actif-circulant' => [
				'name' => s("Actif circulant"),
				'categories' => [
					[
						'name' => s("Biens vivants et en-cours (cycle long)"),
						'accounts' => [31, 341, 332, 338],
					],
					[
						'name' => s("Biens vivants et en-cours (cycle court)"),
						'accounts' => [32, 348],
					],
					[
						'name' => s("Stocks"),
						'accounts' => [30, 371, 374, 375, 376],
					],
					[
						'name' => s("Avances & acomptes versés / commandes créances"),
						'accounts' => [],
					],
					[
						'name' => s("Créances"),
						'accounts' => [411, 46, 445, /*455*/],
					],
					[
						'name' => s("Valeurs mobilières de placement"),
						'accounts' => [50],
					],
					[
						'name' => s("Instruments de trésorerie"),
						'accounts' => [],
					],
					[
						'name' => s("Disponibilités"),
						'accounts' => [512, 53, 531],
					],
					[
						'name' => s("Charges constatées d'avance"),
						'accounts' => [486],
					],
					[
						'name' => s("Charges à répartir sur plusieurs exercices"),
						'accounts' => [],
					],
					[
						'name' => s("Écarts de conversion - Actif"),
						'accounts' => [],
					],
				],
			],
		];

	}

	public static function getLabelByAccount(int $account): string {

		switch($account) {
			case 109:
				return s("Apporteurs, capital souscrit non appelé");
			case 201:
				return s("Frais d'établissement");
			case 205:
				return s("Cessions et droits");
			case 206:
				return s("Droit au bail");
			case 207:
				return s("Fonds commercial");
			case 208:
				return s("Autres immo incorporelles");
			case 237:
				return s("Avances et acomptes versés");
			case 211:
				return s("Terrains");
			case 212:
				return s("Aménagements fonciers");
			case 213:
				return s("Améliorations du fonds");
			case 214:
				return s("Constructions");
			case 215:
				return s("Installations techniques, matériel et outillage");
			case 218:
				return s("Autres");
			case 231:
				return s("Immobilisations corporelles en cours");
			case 238: // PAS SURE
				return s("Avances et acomptes");
			case 241:
				return s("Animaux reproducteurs (adultes)");
			case 242:
				return s("Animaux reproducteurs (jeunes de renouvel.)");
			case 243:
				return s("Animaux de service");
			case 246:
				return s("Plantations pérennes");
			case 31:
				return s("Animaux cycle long");
			case 341:
				return s("Avances aux cultures");
			case 332:
				return s("Pépinières (cycle long)");
			case 338:
				return s("Autres végétaux en terre (cycle long)");
			case 32:
				return s("Animaux cycle court");
			case 348:
				return s("Pépinières (cycle court)");
			case 30:
				return s("Stocks");
			case 371:
				return s("Stocks produits intermédiaires végétaux");
			case 374:
				return s("Stocks produits finis végétaux");
			case 375:
				return s("Stocks produits animaux");
			case 376:
				return s("Stocks produits finis transformés");
			case 411:
				return s("Créances (clients)");
			case 46:
				return s("Débiteurs créditeurs divers");
			case 445:
				return s("État (TVA et taxes assimilées)");
			case 455:
				return s("Associés - compte courant");
			case 50:
				return s("Placement");
			case 512:
				return s("Banque");
			case 53:
			case 531:
				return s("Caisse");
			case 486:
				return s("Charges constatées d'avance");
			case 1015:
				return s("Capital social");
			case 108:
				return s("Compte de l'exploitant");
			case 106:
				return s("Réserves");
			case 120:
				return s("Résultat d'exercice (bénéfice)");
			case 129:
				return s("Résultat d'exercice (perte)");
			case 131:
				return s("Subvention d'investissement");
			case 138:
				return s("Autres types de subventions");
			case 139:
				return s("Amortissement de subventions d'investissements et autres");
			case 145:
				return s("Amortissements dérogatoires");
			case 15:
				return s("Provisions");
			case 164:
				return s("Emprunts");
			case 419:
				return s("Acomptes perçus");
			case 401:
				return s("Dettes (fournisseurs)");
			case 408:
				return s("Fournisseurs");
			case 421:
				return s("Personnel (rémunérations dues)");
			case 487:
				return s("Produit constaté d'avance");

		}

		return '';
	}


	public static function p(string $property): \PropertyDescriber {

		$d = Account::model()->describer($property, [
			'class' => s("Numéro de compte"),
			'journalCode' => s("Code journal"),
			'description' => s("Libellé"),
			'custom' => s("Personnalisé"),
			'vatAccount' => s("Compte de TVA"),
			'vatRate' => s("Taux de TVA"),
			'thirdParty' => s("Tiers"),
		]);

		switch($property) {

			case 'vatAccount':
				$d->autocompleteBody = function (\util\FormUi $form, Account $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'vatAccount'];
				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: ['classPrefix' => AccountSetting::VAT_CLASS]);
				break;

			case 'journalCode':
				$d->values = fn(Account $e) => $e['cJournalCode'] ?? $e->expects(['cJournalCode']);
				break;

			case 'vatRate':
				$d->field = 'select';
				$d->values = fn(Account $e) => array_map(fn($val) => s("{value} %", $val), array_values(\selling\SellingSetting::getVatRates($e['eFarm'])));
				$d->default = fn(Account $e) => array_find_key((\selling\SellingSetting::getVatRates($e['eFarm'])), function($vat) use($e) {
					return $vat === ($e['vatRate'] ?? $e['vatAccount']['vatRate'] ?? NULL);
				});
				$d->before = fn(\util\FormUi $form, Account $e) => $e->exists() ? '<p>'.s("{class} - {description}", ['class' => $e['class'], 'description' => $e['description']]).'</p>' : NULL;
				$d->after = fn(\util\FormUi $form, Account $e) => $e->exists() ? \util\FormUi::info((s("Taux de TVA en vigueur dans le pays configuré pour votre ferme"))).'<p>'.\Asset::icon('info-circle').' '.s("Attention, la modification du taux de TVA pour ce numéro de compte n'est pas rétroactive et n'aura aucune incidence sur les écritures comptables précédemment créées.").'</p>' : NULL;
				break;

			case 'class':
				$d->attributes['minlength'] = 4;
				$d->attributes['maxlength'] = 8 ;
				$d->after = \util\FormUi::info(\Asset::icon('exclamation-triangle').' '.s("Attention ! En modifiant le numéro, toutes les écritures de ce numéro seront modifiées en conséquence."));
				$d->attributes['oninput'] = 'Account.checkForThirdParty("'.AccountSetting::ASSOCIATE_ACCOUNT_CLASS.'");';
				break;

			case 'thirdParty':
				$d->autocompleteBody = function(\util\FormUi $form, Account $e) {
					return [
					];
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', 'farm\Farm'));
		}
		return $d;

	}

}

?>
