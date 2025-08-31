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

	public function memberInformation(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '<h2>'.s("Informations sur l'adhérent").'</h2>';

		$h .= '<div class="join-identity util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Raison sociale").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalName']).'</dd>';
				$h .= '<dt>'.s("Adresse e-mail").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalEmail']).'</dd>';
				$h .= '<dt>'.s("SIRET").'</dt>';
				$h .= '<dd>'.encode($eFarm['siret']).'</dd>';
				$h .= '<dt>'.s("Contact").'</dt>';
				$h .= '<dd>'.$eUser->getName().'</dd>';
				$h .= '<dt>'.s("Adresse").'</dt>';
				$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function joinForm(\farm\Farm $eFarm): string {

		$h = '<div id="association-join-form-container">';
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
						$h .= s("Vous pouvez choisir le montant de votre adhésion, le montant minimum pour une année civile étant de <b>{amount}</b>. Le règlement s'effectue par un paiement en ligne avec {icon} Stripe après sélection du montant et acceptation des statuts et du règlement intérieur.", ['icon' => \Asset::icon('stripe'), 'amount' => \util\TextUi::money($fee, precision: 0)]);
					$h .= '</p>';

					$h .= $this->amountBlocks($form, [$fee, $fee + 20, $fee + 40]);

					$h .= $form->checkbox('terms', 'yes', [
						'mandatory' => TRUE,
						'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("J'accepte les <linkStatus>statuts</linkStatus> et le <linkRules>règlement intérieur</linkRules> de l'association", ['linkStatus' => '<a href="">', 'linkRules' => '<a href="">']))
					]);

					$h .= '<div class="mt-2">'.
						$form->submit(s("J'adhère !")).' <a class="ml-1" onclick="Association.showDonationForm();">'.s("Je souhaite uniquement faire un don").'</a>'.'</div>';

				$h .= $form->close();

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function donateForm(\farm\Farm $eFarm, bool $isVisible): string {

		$h = '<div id="association-donate-form-container" class="'.($isVisible ? '' : 'hide').'">';

			$h .= '<h2 class="mt-2">'.s("Faire un don").'</h2>';

			$h .= '<div class="join-form">';

			$form = new \util\FormUi([
			]);

			$h .= $form->openAjax('/association/membership:doDonate', ['id' => 'association-donate']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= '<p>';
					$h .= s("Vous pouvez soutenir l'association Ouvretaferme avec un don ici. Le don sera effectué par un paiement par {icon} Stripe.", ['icon' => \Asset::icon('stripe')]);
				$h .= '</p>';

				$h.= $this->amountBlocks($form, [10, 20, 30]);

				$h .= $form->inputGroup($form->submit(s("Je donne")), ['class' => 'mt-1']);

			$h .= $form->close();

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function amountBlocks(\util\FormUi $form, array $amounts): string {

		$h = '<div class="association-amount-container">';
			foreach($amounts as $amount) {
				$h .= '<a class="association-amount-block" data-amount="'.$amount.'" onclick="Association.select(this);">'.\util\TextUi::money($amount, precision: 0).'</a>';
			}
			$h .= '<div>';
				$h .= '<div class="association-amount-custom-label">'.s("Montant personnalisé").'</div>';
				$h .= $form->number('custom-amount', NULL, [
					'class' => 'association-amount-block',
					'data-label' => s("Montant personnalisé"),
					'onfocus' => 'Association.customFocus(this);',
					'onfocusout' => 'Association.validateCustom(this);',
				]);
			$h .= '</div>';
		$h .= '</div>';

		$h .= $form->hidden('amount');

		return $h;

	}


	public function gdprInfo(): string {

		$h = '<div class="color-muted font-sm mt-2">';
			$h .= s("Au regard de la loi n°78-17 du 6 janvier 1978 relative à l’informatique, aux fichiers et aux libertés, Ouvretaferme s’engage à ne pas utiliser les données à des fins commerciales. Les adhérent·e·s peuvent exercer leur droit de regard et de rectification concernant leurs données personnelles conformément au RGPD en vigueur depuis le 25 mai 2018.");
		$h .= '</div>';

		return $h;

	}


}

?>
