<?php
namespace company;

class CompanyUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::js('company', 'company.js');
	}

	public static function link(\farm\Farm $eFarm, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eFarm).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eFarm['name']).'</a>';
	}


	public function getHomeUrl(\farm\Farm $eFarm): string {

		return self::urlJournal($eFarm).'/';

	}

	public static function url(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/'.$eFarm['id'].'/company';
	}

	public static function urlSettings(\farm\Farm $eFarm): string {
		return self::url($eFarm).'/configuration';
	}

	public static function urlJournal(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/journal';
	}

	public static function urlOverview(int|\farm\Farm $farm, ?string $view = NULL): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/overview'.($view !== NULL ? '/'.$view : '');
	}

	public static function urlAsset(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/asset';
	}

	public static function urlBank(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/bank';
	}

	public static function urlAccount(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/account';
	}

	public function warnFinancialYear(\farm\Farm $eFarm, \Collection $cFinancialYear): string {

		if($cFinancialYear->notEmpty()) {
			return '';
		}

		$h = '<div class="util-info">';
			$h .= \Asset::icon('leaf').' '.s("Avant de démarrer, rendez-vous <link>dans les paramètres de votre exploitation</link> pour créer votre premier exercice comptable !", ['link' => '<a href="'.CompanyUi::urlAccount($eFarm).'/financialYear/">']);
		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eCompany = new Company();

		$h = '';

		if($eFarm->isLegalComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= s("Pour que votre comptabilité soit tenue au plus juste, certaines informations légales sur votre ferme sont nécessaires. Transmettez-les nous dès à présent pour pouvoir commencer votre comptabilité :");
			$h .= '</div>';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/farm/farm:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eFarm['id']);

				$h .= $form->dynamicGroups($eFarm, ['name*', 'legalEmail*', 'siret*', 'legalName*']);
				$h .= $form->addressGroup(s("Siège social de la ferme").' '.\util\FormUi::asterisk(), 'legal', $eFarm);
			$h .= $form->dynamicGroups($eFarm, ['startedAt*']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer "))
			);

			$h .= $form->close();

		} else {

			$h .= '<h4>'.s("Général").'</h4>';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/company/public:doCreate', ['id' => 'company-create', 'autocomplete' => 'off']);

			$h .= '<div>';
				$h .= s("Vous avez configuré certains paramètres pour votre ferme. Pour les modifier, rendez-vous <link>dans les paramètres généraux de votre ferme</link>.", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']);
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


			$h .= '<h4>'.s("Paramètres de comptabilité").'</h4>';
			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eCompany, ['accountingType']);


			$h .= '<h4>'.s("Premier exercice comptable").'</h4>';

			$h .= $form->dynamicGroups(new \account\FinancialYear(), ['startDate*', 'endDate*']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer les paramètres de ma ferme"))
			);

			$h .= $form->close();
		}

		return new \Panel(
			id: 'panel-company-create',
			title: s("Bienvenue dans le module de comptabilité de {siteName} !"),
			body: $h
		);

	}

	public function update(\farm\Farm $eFarm): string {

		$h = '<h3>'.s("Général").'</h3>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/'.$eFarm['id'].'/company/company:doUpdate', ['id' => 'company-update', 'autocomplete' => 'off']);

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


		$h .= '<h3>'.s("Paramètres de comptabilité").'</h3>';
		$h .= $form->asteriskInfo();

		$h .= $form->hidden('id', $eFarm['company']['id']);
		$h .= $form->dynamicGroups($eFarm['company'], ['accountingType']);

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return $h;

	}

	public function getSettings(\farm\Farm $eFarm): string {

		$h = '';

			$h .= '<div class="util-buttons">';

				if($eFarm->canManage() === TRUE) {

					$h .= '<a href="'.CompanyUi::url($eFarm).'/company:update?id='.$eFarm['id'].'" class="util-button">';
						$h .= '<h4>'.s("Les réglages de base").'</h4>';
						$h .= \Asset::icon('gear-fill');
					$h .= '</a>';

					$h .= '<a href="'.SubscriptionUi::urlManage($eFarm).'" class="util-button">';
						$h .= '<h4>'.s("L'abonnement<br/>de la ferme").'</h4>';
						$h .= \Asset::icon('cart4');
					$h .= '</a>';

				}

				$h .= '<a href="'.CompanyUi::urlBank($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les comptes bancaires").'</h4>';
					$h .= \Asset::icon('bank');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/thirdParty" class="util-button">';
					$h .= '<h4>'.s("Les tiers").'</h4>';
					$h .= \Asset::icon('person-rolodex');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les classes de compte").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/financialYear/" class="util-button">';
					$h .= '<h4>'.s("Les exercices comptables").'</h4>';
					$h .= \Asset::icon('calendar3');
				$h .= '</a>';

			$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Company::model()->describer($property, [
			'accountingType' => s("Type de comptabilité"),
		]);

		switch($property) {

			case 'accountingType' :
				$d->values = [
					CompanyElement::ACCRUAL => s("Comptabilité à l'engagement"),
					CompanyElement::CASH => s("Comptabilité de trésorerie"),
				];
				$d->after = \util\FormUi::info(s("Généralement, la comptabilité de <b>trésorerie</b> est choisie en <b>micro-BA</b> ou <b>régime simplifié</b>.<br />La comptabilité à l'<b>engagement</b> est choisie en <b>régime réel</b> (normal ou simplifié)"));
				break;

		}

		return $d;

	}

}
?>
