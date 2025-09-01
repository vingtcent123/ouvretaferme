<?php
namespace association;

class MembershipUi {

	public function __construct() {

		\Asset::css('association', 'association.css');
		\Asset::js('association', 'association.js');

	}

	public function getMembership(\farm\Farm $eFarm, bool $hasJoinedForNextYear): string {

		$h = '<div class="util-block-secondary">';

			if($eFarm['membership']) {

				if($hasJoinedForNextYear) {

					$h .= s("Vous avez adhéré à l'association pour les années {year} et {nextYear}. Merci pour votre soutien !", ['year' => '<b>'.currentYear().'</b>', 'nextYear' => '<b>'.nextYear().'</b>']);

				} else {

					$h .= s("Vous avez adhéré à l'association pour l'année {year}. Merci !", ['year' => currentYear()]);

				}

			} else {

				$h .= s("Votre ferme <b>{farmName}</b> n'a pas encore adhéré à l'association Ouvretaferme pour l'année <b>{year}</b>.", ['farmName' => encode($eFarm['name']), 'year' => date('Y')]);
			}

		$h .= '</div>';

		if($eFarm['membership'] === FALSE) {
			$h .= '<p>';
				$h .= '<a href="'.\farm\FarmUi::url($eFarm).'/donner" class="btn btn-outline-secondary">'.s("Je veux plutôt faire un don").'</a>';
			$h .= '</p>';
		}

		$h .= '<br/>';

		return $h;

	}

	public function getMemberInformation(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Adhérent").'</h3>';
			$h .= '<a href="/farm/farm:update?id='.$eFarm['id'].'" class="btn btn-outline-primary">'.s("Mettre à jour").'</a>';
		$h .= '</div>';

		$h .= '<div class="util-block stick-xs mb-2">';
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

	public function getJoinForm(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '<div id="association-join-form-container" class="mb-2">';

			$h .= '<h2>'.s("Bulletin d'adhésion").'</h2>';

			$h .= '<p>';

			if($eFarm['membership']) {

				$h .= s("Votre ferme {farmName} est déjà adhérente pour l'année {year} mais vous pouvez dès aujourd'hui adhérer pour l'année {nextYear} à venir. L'adhésion se fait pour une année civile et se terminera donc le {date}.", [
					'farmName' => '<b>'.encode($eFarm['name']).'</b>',
					'year' => '<b>'.date('Y').'</b>',
					'nextYear' => '<b>'.nextYear().'</b>',
					'date' => '<b>'.date('31/12/Y', strtotime('next year')).'</b>'
				]);

			} else {
				$h .= s("Les adhésions se font par année civile et votre adhésion se terminera donc le <b>{date}</b>.", ['farmName' => encode($eFarm['name']), 'year' => date('Y'), 'date' => date('31/12/Y')]);
			}

			$h .= '</p>';

			$h .= '<br/>';

			$h .= $this->getMemberInformation($eFarm, $eUser);

			$h .= '<h3>'.s("Montant de l'adhésion").'</h3>';

			$form = new \util\FormUi();

			$fee = \Setting::get('association\membershipFee');

			$h .= $form->openAjax('/association/membership:doCreatePayment', ['id' => 'association-join']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= '<p>';
					$h .= s("Vous pouvez choisir le montant de votre adhésion, le montant minimum pour une année civile étant de <b>{amount}</b>. Le règlement s'effectue par un paiement en ligne avec {icon} Stripe après sélection du montant et acceptation des statuts et du règlement intérieur.", ['icon' => \Asset::icon('stripe'), 'amount' => \util\TextUi::money($fee, precision: 0)]);
				$h .= '</p>';

				$h .= $this->getAmountBlocks($form, [$fee, $fee + 20, $fee + 40], $fee);

				$h .= $form->checkbox('terms', 'yes', [
					'mandatory' => TRUE,
					'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("J'accepte les <linkStatus>statuts</linkStatus> et le <linkRules>règlement intérieur</linkRules> de l'association", ['linkStatus' => '<a href="">', 'linkRules' => '<a href="">']))
				]);

				$h .= $form->inputGroup($form->submit(s("J'adhère"), ['class' => 'btn btn-primary btn-lg']), ['class' => 'mt-2']);

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getDonateForm(\farm\Farm $eFarm): \Panel {

		$h = '<div id="association-donate-form-container">';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/association/membership:doDonate', ['id' => 'association-donate']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= '<p>';
					$h .= s("Vous pouvez aussi soutenir l'association Ouvretaferme avec un don. Le don sera effectué par un paiement par carte bancaire avec {icon} Stripe.", ['icon' => \Asset::icon('stripe')]);
				$h .= '</p>';

				$h.= $this->getAmountBlocks($form, [10, 20, 30]);

				$h .= $form->inputGroup($form->submit(s("Je donne"), ['class' => 'btn btn-primary btn-lg']), ['class' => 'mt-1']);

			$h .= $form->close();

		$h .= '</div>';

		return new \Panel(
			id: 'panel-association-donation',
			title: s("Soutenir l'association Ouvretaferme avec un don"),
			body: $h
		);

	}

	public function getAmountBlocks(\util\FormUi $form, array $amounts, ?int $defaultAmount = NULL): string {

		$h = '<div class="association-amount-container">';
			foreach($amounts as $amount) {
				$h .= '<a class="association-amount-block '.($defaultAmount === $amount ? 'selected' : '').'" data-amount="'.$amount.'" onclick="Association.select(this);">'.\util\TextUi::money($amount, precision: 0).'</a>';
			}
			$h .= '<div>';
				$h .= '<div class="association-amount-custom-label">'.s("Montant personnalisé").'</div>';
				$h .= $form->text('custom-amount', NULL, [
					'class' => 'association-amount-block',
					'data-label' => s("Montant personnalisé"),
					'onfocus' => 'Association.customFocus(this);',
					'oninput' => 'Association.validateCustom(this);',
					'placeholder' => s("_ _ _ €")
				]);
			$h .= '</div>';
		$h .= '</div>';

		$h .= $form->hidden('amount', $defaultAmount);

		return $h;

	}

	public function getMyFarms(\Collection $cFarm): string {

		$h = '<div class="association-farmer-farms farmer-farms">';

		foreach($cFarm as $eFarm) {
			$h .= $this->getPanel($eFarm);
		}

		return $h;

	}

	public function getPanel(\farm\Farm $eFarm): string {

		\Asset::css('farm', 'farm.css');

		$h = '<a class="farmer-farms-item" href="'.\farm\FarmUi::url($eFarm).'/adherer"';
			if($eFarm['membership']) {
				$h .= ' title="'.s("Vous avez déjà adhéré cette année avec {farmName}, mais vous pouvez toujours faire un don !", ['farmName' => encode($eFarm['name'])]).'"';
			}
		$h .= '>';

			$h .= '<div class="farmer-farms-item-vignette">';
				$h .= \farm\FarmUi::getVignette($eFarm, '4rem');
			$h .= '</div>';

			$h .= '<div class="farmer-farms-item-content">';

				$h .= '<h4>';
					if($eFarm['membership']) {
						$h .= \Asset::icon('star-fill').'  ';
					}
					$h .= encode($eFarm['name']);
				$h .= '</h4>';

				$h .= '<div class="farmer-farms-item-infos">';
					if($eFarm['place']) {
						$h .= \Asset::icon('geo-fill').' '.encode($eFarm['place']);
					}
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</a>';

		return $h;
	}


}

?>
