<?php
namespace company;

class CompanyUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
	}

	public static function urlSettings(\farm\Farm $eFarm, ?\account\FinancialYear $eFinancialYear = NULL): string {
		return \farm\FarmUi::urlFinancialYear($eFinancialYear, $eFarm).'/configuration/accounting';
	}

	public static function urlJournal(\farm\Farm $eFarm, ?\account\FinancialYear $eFinancialYear = NULL): string {
		return \farm\FarmUi::urlFinancialYear($eFinancialYear, $eFarm).'/journal';
	}

	public static function urlAsset(\farm\Farm $eFarm, ?\account\FinancialYear $eFinancialYear = NULL): string {
		return \farm\FarmUi::urlFinancialYear($eFinancialYear, $eFarm).'/asset';
	}

	public static function urlBank(\farm\Farm $eFarm, ?\account\FinancialYear $eFinancialYear = NULL): string {
		return \farm\FarmUi::urlFinancialYear($eFinancialYear, $eFarm).'/bank';
	}

	public static function urlAccount(\farm\Farm $eFarm, ?\account\FinancialYear $eFinancialYear = NULL): string {
		return \farm\FarmUi::urlFinancialYear($eFinancialYear, $eFarm).'/account';
	}

	public function create(\farm\Farm $eFarm): string {

		\Asset::js('account', 'financialYear.js');

		$h = '';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/company/public:doCreate', ['id' => 'company-create', 'autocomplete' => 'off']);

		$h .= '<div class="util-action mb-1">';
			$h .= '<h2>'.s("Informations légales").'</h2>';
			$h .= '<a href="/farm/farm:update?id='.$eFarm['id'].'" class="btn btn-outline-accounting">'.s("Modifier").'</a>';
		$h .= '</div>';

		$h .= '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				foreach(['siret', 'legalName', 'legalEmail'] as $field) {

					$h .= '<dt>'.\farm\FarmUi::p($field)->label.'</dt>';
					$h .= '<dd>'.encode($eFarm[$field]).'</dd>';

				}

				$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
				$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		$h .= '<br/><br/>';

		$h .= '<h2>'.s("Paramétrage de votre exercice comptable").'</h2>';

		$h .= $form->hidden('farm', $eFarm['id']);

		$h .= $form->dynamicGroups(new \account\FinancialYear(), ['startDate*', 'endDate*', 'taxSystem*', 'accountingMode', 'accountingType', 'hasVat*', 'vatFrequency', 'vatChargeability', 'legalCategory*', 'associates*']);

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return $h;

	}

	public function update(\farm\Farm $eFarm): string {

		$h = '<h3>'.s("Général").'</h3>';

		$h .= '<div>';
			$h .= s("Vous avez configuré certains paramètres pour votre ferme. Pour les modifier, rendez-vous <link>dans les paramètres généraux de votre ferme</link>.", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']);
		$h .= '</div>';

		$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

			foreach(['siret', 'legalName', 'legalEmail'] as $field) {

				$h .= '<dt>'.\farm\FarmUi::p($field)->label.'</dt>';
				$h .= '<dd>'.encode($eFarm[$field]).'</dd>';

			}

			$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
			$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function getSettings(\farm\Farm $eFarm): string {

		$h = '';

			$h .= '<div class="text-end mb-1">';
				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/log">'.s("Activité du compte").'</a>';
			$h .= '</div>';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/financialYear/" class="util-button">';
					$h .= '<h4>'.s("Les exercices comptables").'</h4>';
					$h .= \Asset::icon('journals');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlBank($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les comptes bancaires").'</h4>';
					$h .= \Asset::icon('bank');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/thirdParty" class="util-button">';
					$h .= '<h4>'.s("Les tiers").'</h4>';
					$h .= \Asset::icon('person-rolodex');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les numéros de compte").'</h4>';
					$h .= \Asset::icon('list-columns-reverse');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlJournal($eFarm).'/journalCode" class="util-button">';
					$h .= '<h4>'.s("Les journaux").'</h4>';
					$h .= \Asset::icon('journal-bookmark');
				$h .= '</a>';

			$h .= '</div>';


		return $h;

	}

}
?>
