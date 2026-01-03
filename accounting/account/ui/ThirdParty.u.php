<?php
namespace account;

class ThirdPartyUi {

	public function __construct() {
		\Asset::js('account', 'thirdParty.js');
		\Asset::css('company', 'company.css');
	}

	public function getThirdPartyTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les tiers");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.\attr('onclick', 'Lime.Search.toggle("#thirdParty-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/thirdParty:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un tiers").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getOperationDescription(ThirdParty $eThirdParty, string $thirdPartyType): string {

		return match($thirdPartyType) {
			'client' => s("Client {name}", ['name' => $eThirdParty['name']]),
			'supplier' => s("Fournisseur {name}", ['name' => $eThirdParty['name']]),
		};

	}

	public function create(\farm\Farm $eFarm, ThirdParty $eThirdParty): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/thirdParty:doCreate', ['id' => 'journal-thirdParty-create', 'autocomplete' => 'off', 'onrender' => 'ThirdParty.focusInput();']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eThirdParty, ['name*', 'customer']);

		$h .= $form->group(
			content: $form->submit(s("Créer le tiers"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-journal-thirdParty-create',
			title: s("Ajouter un tiers"),
			body: $h
		);

	}

	public static function list(\farm\Farm $eFarm, \Collection $cThirdParty, \Search $search): string {

		if($cThirdParty->empty() === TRUE) {

			if($search->empty()) {

				return '<div class="util-info">'.
					s("Aucun tiers n'a encore été créé.").
					'</div>';

			}

			return '<div class="util-info">'.
				s("Aucun tiers n'a été trouvé avec ces critères de recherche.").
				'</div>';

		}

		$isAccrual = (FEATURE_ACCOUNTING_ACCRUAL and ($eFarm['eFinancialYear']['accountingType'] === FinancialYear::ACCRUAL));

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th rowspan="2">';
							$label = s("#");
							$h .= ($search ? $search->linkSort('id', $label) : $label);
						$h .= '</th>';
						$h .= '<th rowspan="2">';
							$label = s("Nom");
							$h .= ($search ? $search->linkSort('name', $label) : $label);
						$h .= '</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';

						if($isAccrual) {
							$h .= '<th rowspan="2">'.s("Compte Client").'</th>';
						}

						if($isAccrual) {
							$h .= '<th rowspan="2">'.s("Compte Fournisseur").'</th>';
						}

						$h .= '<th class="text-center" colspan="2">'.s("Écritures comptables").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$count = 0;
						$financialYears = [];
						foreach($eFarm['cFinancialYear'] as $eFinancialYear) {
							if($count >= 2) {
								break;
							}
							$count++;
							$financialYears[] = $eFinancialYear['id'];
							$h .= '<th class="text-end '.($count === 1 ? 'highlight-stick-right' : 'highlight-stick-left').'">'.$eFinancialYear->getLabel().'</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cThirdParty as $eThirdParty) {

						$eThirdParty->setQuickAttribute('farm', $eFarm['id']);

						$h .= '<tr>';

						$h .= '<td>';
								$h .= $eThirdParty['id'];
							$h .= '</td>';

							$h .= '<td>';
								$h .= $eThirdParty->quick('name', encode($eThirdParty['name']));
							$h .= '</td>';

								$h .= '<td>';
									$h .= $eThirdParty->quick('customer', $eThirdParty['customer']->exists() ? encode($eThirdParty['customer']['name']) : '<span class="undefined">'.s("Non renseigné").'</span>');
								$h .= '</td>';

								if($isAccrual) {

									$h .= '<td>';
										$h .= $eThirdParty->quick('clientAccountLabel', $eThirdParty['clientAccountLabel'] ? encode($eThirdParty['clientAccountLabel']) : '<span class="undefined">'.s("Non défini").'</span>');
									$h .= '</td>';

								}

								if($isAccrual) {

									$h .= '<td>';
										$h .= $eThirdParty->quick('supplierAccountLabel', $eThirdParty['supplierAccountLabel'] ? encode($eThirdParty['supplierAccountLabel']) : '<span class="undefined">'.s("Non défini").'</span>');
									$h .= '</td>';

								}

							foreach($financialYears as $financialYear) {

								$eFinancialYear = $eFarm['cFinancialYear']->offsetGet($financialYear);

								$h .= '<td class="text-end '.($financialYear === first($financialYears) ? 'highlight-stick-right ' : 'highlight-stick-left').'">';

									if(($eThirdParty['operations'][$financialYear] ?? 0) > 0) {
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm, $eFinancialYear).'/livre-journal?thirdParty='.$eThirdParty['id'].'"  title="'.s("Filtrer les opérations sur ce tiers").'">'.($eThirdParty['operations'][$financialYear] ?? 0).'</a>';
									}

								$h .= '</td>';

							}

							$h .= '<td class="td-min-content">';
								if($eThirdParty['operations']['all'] === 0) {
									$attributes = [
										'data-ajax' => \company\CompanyUi::urlAccount($eFarm).'/thirdParty:doDelete',
										'post-id' => $eThirdParty['id'],
										'data-confirm' => s("Confirmez-vous la suppression du tiers {value} ?", $eThirdParty['name']),
										'class' => 'btn btn-outline-secondary btn-outline-danger'.($eThirdParty['operations']['all'] > 0 ? ' disabled' : ''),
									];
									$h .= '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>';
								}
							$h .= '</td>';

						$h .= '</tr>';

					}
				$h .= '</tbody>';

			$h .= '</table>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="thirdParty-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Nom").'</legend>';
						$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);
				$h .= '</fieldset>';
				$h .= '<div class="util-search-submit">';
						$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
						$h .= '<a href="'.$url.'" class="btn btn-outline-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}
	public static function getAutocomplete(\farm\Farm $eFarm, ThirdParty $eThirdParty): array {

		\Asset::css('media', 'media.css');

		return [
			'value' => $eThirdParty['id'],
			'clientAccountLabel' => $eThirdParty['clientAccountLabel'],
			'supplierAccountLabel' => $eThirdParty['supplierAccountLabel'],
			'farm' => $eFarm['id'],
			'itemHtml' => $eThirdParty['name'],
			'itemText' => $eThirdParty['name']
		];

	}

	public function query(\PropertyDescriber $d, \farm\Farm $eFarm, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('person-rolodex');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tiers...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'thirdParty'];

		$d->autocompleteUrl = \company\CompanyUi::urlAccount($eFarm).'/thirdParty:query';
		$d->autocompleteResults = function(ThirdParty $e) use ($eFarm) {
			return self::getAutocomplete($eFarm, $e);
		};

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Créer un tiers").'</div>';

		return [
			'type' => 'link',
			'link' => \company\CompanyUi::urlAccount($eFarm).'/thirdParty:create',
			'itemHtml' => $item
		];

	}

	public static function p(string $property): \PropertyDescriber {

		$d = ThirdParty::model()->describer($property, [
			'name' => s("Nom"),
			'customer' => s("Client"),
			'clientAccountLabel' => s("Compte client"),
			'supplierAccountLabel' => s("Compte fournisseur"),
		]);

		switch($property) {

			case 'clientAccountLabel':
				$d->after = \util\FormUi::info(s("Le compte client commence toujours par {accountPrefix}", ['accountPrefix' => AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS]));
				$d->placeholder = AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS;
				$d->default = fn($e, $property) => $e[$property] ?? AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS;
				break;

			case 'supplierAccountLabel':
				$d->after = \util\FormUi::info(s("Le compte fournisseur commence toujours par {accountPrefix}", ['accountPrefix' => AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS]));
				$d->placeholder = AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS;
				$d->default = fn($e, $property) => $e[$property] ?? AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS;
				break;

			case 'name':
				$d->before = fn(\util\FormUi $form, $e) => $e->isQuick() ? \util\FormUi::info(s("Attention, ce changement sera répercuté sur toutes les opérations déjà créées")) : '';
				break;

			case 'customer':
				$d->after = \util\FormUi::info(s("Lien entre les tiers et les clients de votre ferme"));
				$d->autocompleteBody = function(\util\FormUi $form, ThirdParty $e) {
					return [
						'farm' => $e['farm']['id'] ?? POST('farm'),
						'withCollective' => 0,
					];
				};
				new \selling\CustomerUi()->query($d);
				break;

		}
		return $d;

	}

}
?>
