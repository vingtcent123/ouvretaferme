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
