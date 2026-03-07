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

			$h .= $form->group(content: '<h3>'.s("Achat / revente").'</h3>');
			$h .= $form->dynamicGroup($eSurvey, 'achatRevente');
			$h .= '<br/>';
			$h .= $form->group(content: '<h3>'.s("Dépôt-vente").'</h3>');
			$h .= $form->dynamicGroup($eSurvey, 'depotVente');
			$h .= '<br/>';
			$h .= $form->group(content: '<h3>'.s("Autofacturation").'</h3>');
			$h .= $form->dynamicGroup($eSurvey, 'autofacturation');
			$h .= '<br/>';
			$h .= $form->group(content: '<h3>'.s("Encours clients").'</h3>');
			$h .= $form->dynamicGroup($eSurvey, 'cagnotte');
			$h .= '<br/>';


			$h .= $form->group(
				content: $form->submit(s("Envoyer"))
			);

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Survey::model()->describer($property, [
			'achatRevente' => s("Si vous vendez une partie de votre production en achat / revente, indiquez votre mode de fonctionnement présent et ce qui vous manque actuellement sur Ouvretaferme."),
			'depotVente' => s("Si vous vendez une partie de votre production en dépôt-vente, indiquez votre mode de fonctionnement présent et ce qui vous manque actuellement sur Ouvretaferme."),
			'autofacturation' => s("Si vous vendez une partie de votre production en autofacturation, indiquez votre mode de fonctionnement présent et ce qui vous manque actuellement sur Ouvretaferme."),
			'cagnotte' => s("Si vous demandez à vos clients de payer en avance et permettez de déduire leurs achats d'une cagnotte, indiquez votre mode de fonctionnement présent et ce qui vous manque actuellement sur Ouvretaferme."),
		]);

		switch($property) {

			case 'achatRevente' :
			case 'depotVente' :
			case 'autofacturation' :
			case 'caisse' :
				$d->options = [
					'acceptFigure' => TRUE,
					'figureOnlyImage' => TRUE
				];
				break;

		}

		return $d;

	}

}
?>
