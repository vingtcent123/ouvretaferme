<?php
namespace journal;

class AccruedIncomeUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
		\Asset::js('journal', 'accruedIncome.js');
	}

	public function create(\farm\Farm $eFarm, AccruedIncome $eAccruedIncome, \account\FinancialYear $eFinancialYear): \Panel {

		\Asset::js('journal', 'asset.js');
		\Asset::js('account', 'thirdParty.js');

		$dialogOpen = '';

		$form = new \util\FormUi();

		$dialogOpen .= $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/accruedIncome:doCreate',
			[
				'id' => 'journal-accrued-income-create',
				'class' => 'panel-dialog container',
			],
		);

		$h = '';

		$h .= $form->hidden('company', $eFarm['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);

		$h .= $form->dynamicGroups($eAccruedIncome, ['date', 'account', 'accountLabel', 'thirdParty', 'description', 'amount']);


		$saveButton = $form->submit(
			s("Enregistrer le produit à recevoir"),
			[
				'id' => 'submit-save-accrued-income',
			],
		);

		$dialogClose = $form->close();

		$footer = '<div class="text-end">'.$saveButton.'</div>';

		return new \Panel(
			id: 'panel-journal-accrued-income-create',
			title: '<div id="panel-journal-accrued-income-create-title"">'.s("Ajouter un produit à recevoir").'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	public function list(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cAccruedIncome): string {

		$h = '<h3 class="mt-2">'.s("Produits à recevoir (PAR)").'</h3>';

		$h .= '<div class="util-info">';
			$h .= \Asset::icon('info-circle').' '.s("Si vous avez déjà livré des biens durant cet exercice comptable mais qu'aucune facture n'a encore été établie, vous pouvez les enregistrer maintenant.");
		$h .= '</div>';

		$h .= '<div class="stick-sm util-overflow-sm mb-1">';

			if($cAccruedIncome->notEmpty()) {

				$h .= '<table class="financial-year-par-table tr-even tr-hover">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Compte").'</th>';
							$h .= '<th>'.s("Tiers").'</th>';
							$h .= '<th>'.s("Libellé").'</th>';
							$h .= '<th class="text-end">'.s("Montant HT").'</th>';
							$h .= '<th></th>';

						$h .= '</tr>';

						$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cAccruedIncome as $eAccruedIncome) {

							if($eAccruedIncome->canDelete()) {

								$action = '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/accruedIncome:doDelete" post-id="'.$eAccruedIncome['id'].'" class="btn btn-outline-danger">'.\Asset::icon('trash').'</a>';

							} else {

								$action = '';

							}

							$h .= '<tr id="'.$eAccruedIncome['id'].'">';

								$h .= '<td>'.\util\DateUi::numeric($eAccruedIncome['date'], \util\DateUi::DATE).'</td>';
								$h .= '<td>'.encode($eAccruedIncome['accountLabel']).'</td>';
								$h .= '<td>'.encode($eAccruedIncome['thirdParty']['name']).'</td>';
								$h .= '<td>'.encode($eAccruedIncome['description']).'</td>';
								$h .= '<td class="text-end">'.\util\TextUi::money($eAccruedIncome['amount']).'</td>';
								$h .= '<td class="td-min-content">'.$action.'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

			}

			$h .= '<a class="btn btn-secondary" href="'.\company\CompanyUi::urlJournal($eFarm).'/accruedIncome:create?financialYear='.$eFinancialYear['id'].'">'.\Asset::icon('plus-circle').' '.s("Ajouter un produit à recevoir").'</a>';

		$h .= '</div>';

		return $h;

	}

	public static function getTranslation(string $type): string {

		return match($type) {
			AccruedIncome::RECORDED => s("Produit à recevoir"),
			AccruedIncome::ACCRUED => s("Produit à recevoir"),
		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = AccruedIncome::model()->describer($property, [
			'account' => s("Classe de compte"),
			'accountLabel' => s("Compte"),
			'date' => s("Date de l'opération"),
			'description' => s("Libellé"),
			'amount' => s("Montant (HT)"),
			'thirdParty' => s("Tiers"),
		]);

		switch($property) {

			case 'date' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, AccruedIncome $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', '?int'), query: ['classPrefix' => \Setting::get('account\productAccountClass')]);
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, AccruedIncome $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', '?int'), query: GET('query', 'string', '7'));
				break;

			case 'amount' :
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, AccruedIncome $e) {
					return $form->addon(s("€"));
				};
				break;


			case 'thirdParty':
				$d->autocompleteBody = function(\util\FormUi $form, AccruedIncome $e) {
					return [
					];
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', '?int'));
				break;

		}

		return $d;

	}


}
