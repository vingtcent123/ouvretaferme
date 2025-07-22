<?php
namespace journal;

class StockUi {

	public function __construct() {

		\Asset::js('journal', 'stock.js');

	}

	public function getTranslation(Stock $eStock) : string {

			return s("Variation de stock - {description}", ['description' => $eStock['type']]);

	}

	public function create(\farm\Farm $eFarm, Stock $eStock, \account\FinancialYear $eFinancialYear): \Panel {

		$dialogOpen = '';

		$form = new \util\FormUi();

		$dialogOpen .= $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/stock:doCreate',
			[
				'id' => 'journal-stock-create',
				'class' => 'panel-dialog container',
			],
		);

		$h = '';

		$h .= $form->hidden('company', $eFarm['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);

		$h .= $form->dynamicGroups($eStock, ['account', 'accountLabel', 'type', 'variationAccount', 'variationAccountLabel', 'initialStock', 'finalStock', 'variation'], [
			'variation' => function($d) use($form) {
				$d->attributes['disabled'] = 'disabled';
			},
			'initialStock' => function($d) use($form) {
				$d->attributes['oninput'] = 'Stock.computeStock();';
			},
			'finalStock' => function($d) use($form) {
				$d->attributes['oninput'] = 'Stock.computeStock();';
			},
		]);


		$saveButton = $form->submit(
			s("Enregistrer le stock"),
			[
				'id' => 'submit-save-stock',
			],
		);

		$dialogClose = $form->close();

		$footer = '<div class="text-end">'.$saveButton.'</div>';

		return new \Panel(
			id: 'panel-journal-stock-create',
			title: '<div id="panel-journal-stock-create-title"">'.s("Ajouter du stock").'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	public function set(\farm\Farm $eFarm, Stock $eStock, \account\FinancialYear $eFinancialYear): \Panel {

		$dialogOpen = '';
		$initialStock = $eStock['finalStock'];

		$form = new \util\FormUi();

		$dialogOpen .= $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/stock:doSet',
			[
				'id' => 'journal-stock-set',
				'class' => 'panel-dialog container',
			],
		);

		$h = '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Exercice d'origine").'</dt>';
				$h .= '<dd>'.\account\FinancialYearUi::getYear($eStock['financialYear']).' ('.s("du {startDate} au {endDate}", [
						'startDate' => \util\DateUi::numeric($eStock['financialYear']['startDate'], \util\DateUi::DATE),
						'endDate' => \util\DateUi::numeric($eStock['financialYear']['endDate'], \util\DateUi::DATE),
					]).')</dd>';

				$h .= '<dt>'.self::p('account')->label.'</dt>';
				$h .= '<dd>'.encode($eStock['accountLabel']).' - '.encode($eStock['account']['description']).'</dd>';

				$h .= '<dt>'.self::p('type')->label.'</dt>';
				$h .= '<dd>'.encode($eStock['type']).'</dd>';

				$h .= '<dt>'.self::p('variationAccount')->label.'</dt>';
				$h .= '<dd>'.encode($eStock['variationAccountLabel']).' - '.encode($eStock['variationAccount']['description']).'</dd>';

				$h .= '<dt>'.self::p('finalStock')->label.'</dt>';
				$h .= '<dd>'.s("{amount} (au {date})", ['amount' => \util\TextUi::money($initialStock), 'date' => \util\DateUi::numeric($eStock['financialYear']['endDate'], \util\DateUi::DATE)]).'</dd>';

			$h .= '</dl>';
		$h .= '</div>';


		$h .= $form->hidden('company', $eFarm['id']);
		$h .= $form->hidden('id', $eStock['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);

		$eStock['finalStock'] = NULL;
		$eStock['variation'] = NULL;

		$h .= $form->dynamicGroups($eStock, ['finalStock', 'variation'], [
			'variation' => function($d) use($form) {
				$d->attributes['disabled'] = 'disabled';
			},
			'finalStock' => function($d) use($form, $initialStock) {
				$d->attributes['oninput'] = 'Stock.updateStock('.$initialStock.');';
				$d->label = s("Nouveau stock");
			},
		]);


		$saveButton = $form->submit(
			s("Enregistrer"),
			[
				'id' => 'submit-set-stock',
			],
		);

		$dialogClose = $form->close();

		$footer = '<div class="text-end">'.$saveButton.'</div>';

		return new \Panel(
			id: 'panel-journal-stock-set',
			title: '<div id="panel-journal-stock-create-title"">'.s("Mettre à jour le stock").'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	public function listForClosing(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cStock): string {

		$h = '<h3 class="mt-2">'.s("Stocks").'</h3>';

		$h .= '<div class="util-block-help">';
		$h .= s("Si vous avez généré du stock pendant cette période comptable, ou si vous aviez du stock à la fin de la période comptable précédente, indiquez ici le stock comptabilisé en date du {day}.", ['day'=> \util\DateUi::numeric($eFinancialYear['endDate'])]);
		$h .= '</div>';

		$h .= '<div class="stick-sm util-overflow-sm mb-1">';

			if($cStock->notEmpty()) {

				$h .= '<table class="financial-year-stock-table tr-even tr-hover">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Date d'ajout").'</th>';
							$h .= '<th>'.s("Type").'</th>';
							$h .= '<th>'.s("Compte de stock").'</th>';
							$h .= '<th>'.s("Compte de variation de stock").'</th>';
							$h .= '<th class="text-end">'.s("Stock initial").'</th>';
							$h .= '<th class="text-end">'.s("Stock final").'</th>';
							$h .= '<th class="text-end">'.s("Variation").'</th>';
							$h .= '<th></th>';

						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cStock as $eStock) {

							if($eStock->canDelete()) {

								$action = '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/stock:doDelete" post-id="'.$eStock['id'].'" class="btn btn-outline-danger">'.\Asset::icon('trash').'</a>';

							} else {

								$action = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';

								$action .= '<div class="dropdown-list">';

									$action .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/stock:set?id='.$eStock['id'].'&financialYear='.$eFinancialYear['id'].'" class="dropdown-item">'.s("Saisir le stock final").'</a>';
									$action .= '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/stock:reset" post-id="'.$eStock['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Passer le stock à 0").'</a>';
									$action .= '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/stock:renew" post-id="'.$eStock['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Reconduire le stock").'</a>';

								$action .= '</div>';

							}

							$h .= '<tr id="'.$eStock['id'].'">';

								$h .= '<td>';
									if($eStock['financialYear']->is($eFinancialYear) === FALSE) {
										$h .= '<div class="text-center color-danger" title="'.s("Vous devez indiquer l'encours de ce stock à la fin de cet exercice comptable.").'">';
											$h .= \Asset::icon('exclamation-diamond');
										$h .= '</div>';
									}
									$h .= \util\DateUi::numeric($eStock['createdAt'], \util\DateUi::DATE);
								$h .= '</td>';
								$h .= '<td>'.encode($eStock['type']).'</td>';
								$h .= '<td>'.encode($eStock['accountLabel']).' - '.encode($eStock['account']['description']).'</td>';
								$h .= '<td>'.encode($eStock['variationAccountLabel']).' - '.encode($eStock['variationAccount']['description']).'</td>';

								// Du stock ajouté cette année
								if($eStock['financialYear']->is($eFinancialYear)) {

									$h .= '<td class="text-end">'.\util\TextUi::money($eStock['initialStock']).'</td>';
									$h .= '<td class="text-end">'.\util\TextUi::money($eStock['finalStock']).'</td>';
									$h .= '<td class="text-end">'.\util\TextUi::money($eStock['variation']).'</td>';

								} else { // Du stock reporté de la déclaration de l'exercice comptable passé (mais pas encore enregistré)

									$h .= '<td class="text-end">'.\util\TextUi::money($eStock['finalStock']).'</td>';
									$h .= '<td class="text-end"></td>';
									$h .= '<td class="text-end"></td>';

								}

								$h .= '<td class="td-min-content">'.$action.'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

			}

			$h .= '<a class="btn btn-secondary" href="'.\company\CompanyUi::urlJournal($eFarm).'/stock:create?financialYear='.$eFinancialYear['id'].'">'.\Asset::icon('plus-circle').' '.s("Ajouter du stock").'</a>';

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Stock::model()->describer($property, [
			'type' => s("Type de stock"),
			'account' => s("Classe de compte de stock"),
			'accountLabel' => s("Compte de stock"),
			'variationAccount' => s("Classe de compte de variation"),
			'variationAccountLabel' => s("Compte de variation"),
			'initialStock' => s("Stock initial"),
			'finalStock' => s("Stock final"),
			'variation' => s("Variation de stock"),
			'operation' => s("Écriture"),
		]);

		switch($property) {


			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Stock $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', '?int'), query: ['class' => array_keys(\Setting::get('account\stockVariationClasses'))]);
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Stock $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', '?int'), query: GET('query'));
				break;

			case 'variationAccount':
				$d->autocompleteBody = function(\util\FormUi $form, Stock $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', '?int'), query: ['class' => \Setting::get('account\stockVariationClasses')]);
				break;

			case 'variationAccountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Stock $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'variationAccountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', '?int'), query: GET('query'));
				break;

			case 'variation' :
			case 'finalStock' :
			case 'initialStock' :
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, Stock $e) {
					return $form->addon(s("€"));
				};
				break;

			case 'thirdParty':
				$d->autocompleteBody = function(\util\FormUi $form, Stock $e) {
					return [
					];
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', '?int'));
				break;

		}

		return $d;

	}


}
