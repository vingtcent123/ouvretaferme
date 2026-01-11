<?php
namespace farm;

class SurveyUi {

	public function create(Survey $eSurvey): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doSurvey');

			$h .= \util\FormUi::asteriskInfo(100000);

			$h .= $form->hidden('farm', $eSurvey['farm']['id']);

			$h .= $form->group(
				s("Votre ferme"),
				'<b>'.encode($eSurvey['farm']['name']).'</b>'
			);

			$h .= '<br/><br/>';

			$h .= $form->group(content: '<div class="util-block bg-primary color-white"><h3>'.s("Votre système").'</h3></div>');
			$h .= $form->dynamicGroups($eSurvey, ['number*', 'why', 'feedback']);

			$h .= '<br/><br/>';

			$h .= $form->group(content: '<div class="util-block bg-production color-white"><h3>'.\Asset::icon('leaf').'  '.s("Production").'</h3><p>'.\Asset::icon('arrow-right').' '.s("Si vous n'utilisez pas le module de production, vous pouvez passer directement à la section suivante.").'</p></div>');
			$h .= $form->dynamicGroups($eSurvey, ['productionFeature', 'productionResearch']);

			$h .= '<br/><br/>';

			$h .= $form->group(content: '<div class="util-block bg-commercialisation color-white"><h3>'.\Asset::icon('basket3').'  '.s("Vente").'</h3><p>'.\Asset::icon('arrow-right').' '.s("Si vous n'utilisez pas le module de vente, vous pouvez passer directement à la section suivante.").'</p></div>');
			$h .= $form->dynamicGroups($eSurvey, ['sellingFeature']);

			$h .= '<br/><br/>';

			$h .= $form->group(content: '<div class="util-block bg-accounting color-white"><h3>'.\Asset::icon('bank').'  '.s("Comptabilité").'</h3></div>');
			$h .= $form->dynamicGroups($eSurvey, ['accounting*', 'accountingType*', 'accountingAutonomy', 'accountingOtf', 'accountingInfo']);

			$h .= '<br/><br/>';

			$h .= $form->group(content: '<div class="util-block bg-private color-white"><h3>'.\Asset::icon('people-fill').'  '.s("Coopération").'</h3></div>');
			$h .= $form->group(content: '<p>'.s("Nous envisageons de développer des fonctionnalités pour faciliter la coopération entre les fermes.").'</p><h3>'.s("Êtes-vous intéressé par ?").'</h3>');
			$h .= $form->dynamicGroups($eSurvey, ['coopItk', 'coopCommandes', 'coopTroc', 'coopMercuriale', 'coopOther']);

			$h .= $form->group(content: '<div class="util-block bg-primary color-white"><h3>'.s("Pour terminer").'</h3></div>');
			$h .= $form->dynamicGroups($eSurvey, ['formation', 'other']);

			$h .= '<br/><br/>';

			$h .= $form->group(
				content: $form->submit(s("Envoyer"))
			);

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Survey::model()->describer($property, [
			'number' => s("À combien travaillez-vous sur votre ferme ?"),
			'why' => s("Pouvez-vous dire ce qui vous a conduit à utiliser Ouvretaferme ?"),
			'feedback' => s("Avez-vous un retour d'expérience à nous faire sur votre utilisation du logiciel ? A t-il répondu à vos attentes initiales ?"),
			'formation' => s("Aimeriez-vous suivre des formations sur certaines fonctionnalités du logiciel. Si oui, lesquelles ?"),
			'productionFeature' => s("Y a t-il des fonctionnalités qui vous manquent sur le module de production ?"),
			'productionResearch' => s("Êtes-vous intéressé·e pour être recontacté·e et partager les données de votre ferme dans le cadre de projets de recherche ?"),
			'sellingFeature' => s("Y a t-il des fonctionnalités qui vous manquent sur le module de vente ?"),
			'accounting' => s("Avec quel organisme faites-vous la comptabilité de votre ferme ?"),
			'accountingType' => s("Vous êtes ?"),
			'accountingAutonomy' => s("Êtes-vous en mesure de faire la comptabilité de votre ferme de manière autonome ?"),
			'accountingOtf' => s("Voulez-vous être recontacté pour tester le nouveau logiciel de comptabilité de Ouvretaferme ?"),
			'accountingInfo' => s("Avez-vous des choses à dire à propos de la comptabilité ?"),
			'coopTroc' => s("... une fonctionnalité pour faire du troc ou du dépôt-vente avec vos collègues ?"),
			'coopMercuriale' => s("... une fonctionnalité pour établir des mercuriales de prix en vente directe ou en demi-gros ?"),
			'coopItk' => s("... une fonctionnalité pour partager vos itinéraires techniques de production et vos choix variétaux ?"),
			'coopCommandes' => s("... une fonctionnalité pour faciliter l'organisation de commandes groupées de matériel avec vos collègues ?"),
			'coopOther' => s("... avez-vous d'autres idées de fonctionnalités collaboratives ?"),
			'other' => s("Laissez éventuellement un commentaire si vous avez quelque chose de plus à dire"),
		]);

		switch($property) {

			case 'accounting' :
				$d->values = [
					Survey::CERFRANCE => s("Cerfrance"),
					Survey::AFOCG => s("AFOCG"),
					Survey::AUTONOME => s("En autonomie"),
					Survey::OTHER => s("Autre cabinet comptable"),
					Survey::NONE => s("Je ne tiens pas de comptabilité"),
				];
				break;

			case 'accountingType' :
				$d->values = [
					Survey::REEL => s("Au réel"),
					Survey::MICROBA => s("Au micro-BA"),
					Survey::OTHER => s("Autre"),
				];
				break;

			case 'coopItk' :
			case 'coopCommandes' :
			case 'coopTroc' :
			case 'coopMercuriale' :
			case 'productionResearch' :
			case 'accountingOtf' :
			case 'accountingAutonomy' :
				$d->field = 'yesNo';
				break;

		}

		return $d;

	}

}
?>
