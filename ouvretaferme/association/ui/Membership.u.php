<?php
namespace association;

class MembershipUi {

	public function membership(): string {

		$h = '<div class="util-success">';
			$h .= s("Vous avez adhéré à l'association pour l'année {year}. Merci !", ['year' => date('Y')]);
		$h .= '</div>';

		return $h;

	}

	public function joinForm(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '';

		$h .= '<div class="join-identity util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Raison sociale").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalName']).'</dd>';
				$h .= '<dt>'.s("N° SIRET").'</dt>';
				$h .= '<dd>'.encode($eFarm['siret']).'</dd>';
				$h .= '<dt>'.s("Forme juridique").'</dt>';
				$h .= '<dd></dd>';
				$h .= '<dt>'.s("Contact").'</dt>';
				$h .= '<dd>'.$eUser->getName().'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		$h .= '<div class="util-info">';
			$h .= s("Votre ferme <b>{farmName}</b> n'a pas encore adhéré à l'association pour l'année <b>{year}</b>. L'adhésion se fait pour l'année civile en cours et se terminera donc le <b>{date}</b>.", ['farmName' => encode($eFarm['name']), 'year' => date('Y'), 'date' => date('31/12/Y')]);
		$h .= '</div>';

		$h .= '<div class="join-form">';

		$form = new \util\FormUi([
		]);

		$fee = \Setting::get('association\membershipFee');

		$h .= $form->openAjax('/association/membership:doCreatePayment', ['id' => 'association-join']);

		$h .= $form->hidden('farm', $eFarm['id']);

		$h .= $form->group('', content: s("Le montant de l'adhésion pour l'année en cours est de <b>{amount}</b>.", ['amount' => \util\TextUi::money($fee, precision: 0)]));

		$h .= $form->group(
			s("Cotisation"),
			$form->radio('amountType', 'origin', s("Je verse la cotisation de {amount}", ['amount' => \util\TextUi::money($fee, precision: 0)])).
			$form->radio('amountType', 'custom', '<span class="flex-align-center">'.s("Je souhaite, en plus, faire un don : {formInput}", ['formInput' => $form->inputGroup($form->number('amount', 50, ['min' => $fee, 'step' => 1]).$form->addon('€'))]).'</span>', attributes: ['class' => 'flex-align-center'])
		);

		$h .= $form->group('', content: $form->checkbox('terms', 'yes', [
			'mandatory' => TRUE,
			'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("J'accepte les <linkStatus>statuts</linkStatus> et le <linkRules>règlement intérieur</linkRules> de l'association", ['linkStatus' => '<a href="">', 'linkRules' => '<a href="">']))
		]));

		$h .= $form->group('', content: $form->submit(s("J'adhère !")));

		$h .= '</div>';

		$h .= '<div class="color-muted font-sm mt-2">';
			$h .= s("Au regard de la loi n°78-17 du 6 janvier 1978 relative à l’informatique, aux fichiers et aux libertés, Ouvretaferme s’engage à ne pas utiliser les données à des fins commerciales. Les adhérent·e·s peuvent exercer leur droit de regard et de rectification concernant leurs données personnelles conformément au RGPD en vigueur depuis le 25 mai 2018.");
		$h .= '</div>';

		return $h;

	}

}

?>
