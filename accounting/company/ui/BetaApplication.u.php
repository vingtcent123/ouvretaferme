<?php
namespace company;

Class BetaApplicationUi {

	public function __construct() {
		\Asset::js('company', 'company.js');
	}

	public function create(\farm\Farm $eFarm): string {

		$form = new \util\FormUi();
		$eBetaApplication = new BetaApplication();

		$h = '';

		$h .= $form->openAjax('/company/beta:doCreate', ['onrender' => 'CompanyConfiguration.changeHasVat(); CompanyConfiguration.changeHelpComment(); CompanyConfiguration.changeHasSoftware();', 'id' => 'beta-form']);

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eFarm['id']);

		$h .= $form->dynamicGroups($eBetaApplication, [
			'accountingLevel*', 'accountingHelped*', 'helpComment',
			'hasSoftware*', 'software',
			'accountingType*', 'taxSystem*', 'hasVat*', 'vatFrequency',
			'hasStocks*',
			'discord', 'comment'], [
			'accountingHelped*' => function($d) use($form) {
				$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'CompanyConfiguration.changeHelpComment()'];
			},
			'hasVat*' => function($d) use($form) {
				$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'CompanyConfiguration.changeHasVat()'];
			},
			'hasSoftware*' => function($d) use($form) {
				$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'CompanyConfiguration.changeHasSoftware()'];
			},
		]);

		$h .= $form->group(
			content: $form->submit(s("Me proposer pour tester !"))
		);

		$h .= $form->close();

		return $h;
	}

	public static function p(string $property): \PropertyDescriber {

		$d = BetaApplication::model()->describer($property, [
			'accountingLevel' => s("Votre niveau de connaissances en comptabilité"),
			'accountingHelped' => s("Êtes-vous accompagné·e par un cabinet comptable ou un organisme pour la comptabilité ?"),
			'helpComment' => s("Si oui, qui vous accompagne ?"),
			'hasSoftware' => s("Utilisez-vous un logiciel de comptabilité actuellement ?"),
			'software' => s("Si oui, lequel ?"),
			'accountingType' => s("Type de comptabilité"),
			'taxSystem' => s("Régime fiscal"),
			'hasStocks' => s("Gérez-vous des stocks ?"),
			'comment' => s("Commentaire"),
			'hasVat' => s("Votre ferme est-elle redevable de la TVA ?"),
			'notPayingVat' => s("Régime de franchise de TVA ?"),
			'vatFrequency' => s("Fréquence de déclaration de TVA"),
			'discord' => s("Êtes-vous d'accord pour échanger sur <link>Discord</link> ?", ['link' => '<a href="https://discord.com/channels/1344219338684497961">']),
		]);

		switch($property) {

			case 'accountingLevel' :
				$d->values = [
					BetaApplication::BEGINNER => s("Débutant·e"),
					BetaApplication::INITIATED => s("Initié·e"),
					BetaApplication::COMFORTABLE => s("À l'aise"),
					BetaApplication::EXPERT => s("Expert"),
				];
				break;

			case 'taxSystem' :
				$d->values = [
					BetaApplication::MICRO_BA => s("Micro-BA"),
					BetaApplication::BA_REEL_SIMPLIFIE => s("Bénéfice Agricole réel simplifié"),
					BetaApplication::BA_REEL_NORMAL => s("Bénéfice Agricole réel"),
					BetaApplication::OTHER_BIC => s("BIC"),
					BetaApplication::OTHER_BNC => s("BNC"),
					BetaApplication::OTHER => s("Autre"),
				];
				break;

			case 'accountingType' :
				$d->values = [
					BetaApplication::ACCRUAL => s("Comptabilité à l'engagement"),
					BetaApplication::CASH => s("Comptabilité de trésorerie"),
					BetaApplication::CASH_ACCRUAL => s("Comptabilité de trésorerie et à l'engagement pour les ventes"),
				];
				break;

			case 'vatFrequency' :
				$d->values = [
					BetaApplication::MONTHLY => s("Mensuelle"),
					BetaApplication::QUARTERLY => s("Trimestrielle"),
					BetaApplication::ANNUALLY => s("Annuelle"),
				];
				break;

			case 'accountingHelped' :
			case 'hasVat' :
			case 'discord' :
			case 'hasStocks' :
			case 'hasSoftware' :
				$d->field = 'yesNo';
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'comment':
				$d->after = \util\FormUi::info(s("N'hésitez pas à ajouter des informations sur la manière dont la comptabilité de votre ferme est tenue (si vous êtes plusieurs à vous répartir les tâches, si vous faites tout en une fois dans l'année ou plutôt au fur et à mesure chaque semaine etc.)"));
		}
		return $d;

	}

}

?>
