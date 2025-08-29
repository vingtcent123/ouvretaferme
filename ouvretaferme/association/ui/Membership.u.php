<?php
namespace association;

class MembershipUi {

	public function __construct() {

		\Asset::css('association', 'association.css');
		\Asset::js('association', 'association.js');

	}
	public function membership(): string {

		$h = '<div class="util-success">';
			$h .= s("Vous avez adhéré à l'association pour l'année {year}. Merci !", ['year' => date('Y')]);
		$h .= '</div>';

		return $h;

	}

	public function joinForm(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '<h2>'.s("Informations sur la ferme").'</h2>';

		$h .= '<div class="join-identity util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Raison sociale").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalName']).'</dd>';
				$h .= '<dt>'.s("Adresse e-mail").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalEmail']).'</dd>';
				$h .= '<dt>'.s("N° SIRET").'</dt>';
				$h .= '<dd>'.encode($eFarm['siret']).'</dd>';
				$h .= '<dt>'.s("Contact").'</dt>';
				$h .= '<dd>'.$eUser->getName().'</dd>';
				$h .= '<dt>'.s("Forme juridique").'</dt>';
				$h .= '<dd>'.encode(\farm\FarmUi::p('legalForm')->values[$eFarm['legalForm']]).'</dd>';
				$h .= '<dt>'.s("Adresse").'</dt>';
				$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		$h .= '<h2>'.s("Bulletin d'adhésion").'</h2>';

		$h .= '<div class="util-info">';
			$h .= s("Votre ferme <b>{farmName}</b> n'a pas encore adhéré à l'association pour l'année <b>{year}</b>. L'adhésion se fait pour l'année civile en cours et se terminera donc le <b>{date}</b>.", ['farmName' => encode($eFarm['name']), 'year' => date('Y'), 'date' => date('31/12/Y')]);
		$h .= '</div>';

		$h .= '<div class="join-form">';

		$form = new \util\FormUi([
		]);

		$fee = \Setting::get('association\membershipFee');

		$h .= $form->openAjax('/association/membership:doCreatePayment', ['id' => 'association-join']);

		$h .= $form->hidden('farm', $eFarm['id']);

		$h .= '<p>';
			$h .= s("Le montant de l'adhésion pour une année civile est de <b>{amount}</b>. Le règlement s'effectue par un paiement en ligne avec {icon} Stripe après validation du montant et acceptation des statuts et du règlement intérieur. En outre, si vous souhaitez soutenir l'association, vous pouvez également ajouter un don à votre adhésion :", ['icon' => \Asset::icon('stripe'), 'amount' => \util\TextUi::money($fee, precision: 0)]);
		$h .= '</p>';

		$h .= '<div class="amount-container">';
			for($amount = $fee; $amount <= $fee + 40; $amount += 20) {
				$h .= '<a class="block-amount" data-amount="'.$amount.'" onclick="Association.select(this);">'.\util\TextUi::money($amount, precision: 0).'</a>';
			}
			$h .= '<div>'.$form->number('custom-amount', NULL, [
				'class' => 'block-amount',
					'min' => \Setting::get('association\membershipFee'),
					'onfocus' => 'Association.customFocus(this);',
					'onfocusout' => 'Association.validateCustom(this);'
				]).
				\util\FormUi::info(s("Montant personnalisé")).'</div>';
		$h .= '</div>';

		$h .= $form->hidden('amount');

		$h .= $form->checkbox('terms', 'yes', [
			'mandatory' => TRUE,
			'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("J'accepte les <linkStatus>statuts</linkStatus> et le <linkRules>règlement intérieur</linkRules> de l'association", ['linkStatus' => '<a href="">', 'linkRules' => '<a href="">']))
		]);

		$h .= $form->inputGroup($form->submit(s("J'adhère !")), ['class' => 'mt-2']);

		$h .= '</div>';

		$h .= '<div class="color-muted font-sm mt-2">';
			$h .= s("Au regard de la loi n°78-17 du 6 janvier 1978 relative à l’informatique, aux fichiers et aux libertés, Ouvretaferme s’engage à ne pas utiliser les données à des fins commerciales. Les adhérent·e·s peuvent exercer leur droit de regard et de rectification concernant leurs données personnelles conformément au RGPD en vigueur depuis le 25 mai 2018.");
		$h .= '</div>';

		return $h;

	}

}

?>
