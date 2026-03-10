<?php
namespace association;

class MembershipUi {

	public function __construct() {

		\Asset::css('association', 'association.css');
		\Asset::js('association', 'association.js');

	}

	public function getMembershipSuccess(\Collection $cHistory): string {

		// Promo adhésion 2025-2026
		$years = $cHistory->find(fn($eHistory) => $eHistory['type'] === History::MEMBERSHIP and $eHistory['status'] === History::VALID and $eHistory['membership'] !== 2025)->count();

		$h = '<div class="util-block-success mb-2">';
			if($years >= 2) {
				$h .= '<h4>'.s("Votre réadhésion a bien été prise en compte !").'</h4>';
				$h .= '<div>'.s("Toujours fidèle au poste 🥳").'</div>';
			} else {
				$h .= '<h4>'.s("Votre adhésion a bien été prise en compte !").'</h4>';
				$h .= '<div>'.s("Toute l'équipe de Ouvretaferme vous souhaite la bienvenue et vous remercie pour votre engagement 🥳").'</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getDonationSuccess(): string {

		$h = '<div class="util-block-success mb-2">';
			$h .= '<h4>'.s("Nous avons bien reçu votre don !").'</h4>';
			$h .= '<div>'.s("Toute l'équipe de Ouvretaferme vous remercie pour votre générosité 🥳").'</div>';
		$h .= '</div>';

		return $h;

	}

	public function getBenefits(): string {

		\Asset::css('main', 'home.css');

		$h = '<div>';
			$h .= '<h3>'.s("Ce que l'adhésion apporte").'</h3>';
			$h .= '<div class="home-points">';
				$h .= '<div class="home-point">'.\Asset::icon('hand-thumbs-up').'<h4>'.s("Un immense soutien pour nous aider à développer Ouvretaferme").'</h4></div>';
				$h .= '<div class="home-point">'.\Asset::icon('bank').'<h4>'.s("L'accès au module de gestion").'</h4></div>';
				$h .= '<div class="home-point">'.\Asset::icon('envelope').'<h4>'.s("Envoyer {value} e-mails par semaine avec les campagnes e-mailing", \farm\Farm::getCampaignMemberLimit()).'</h4></div>';
			$h .= '</div>';
			$h .= '<p class="mt-1">'.s("Adhérer à l'association Ouvretaferme ne vous engage en rien à consacrer du temps pour participer à la vie de l'association ou au développement du logiciel. Il s'agit simplement de nous aider à poursuivre son développement !").'</p>';
		$h .= '</div>';

		return $h;

	}

	public function getMembership(\farm\Farm $eFarm, bool $hasJoinedForNextYear): string {

		$h = '<div>';

			if($eFarm['membership']) {

				$h .= '<p class="mb-2">';
					$h .= \Asset::icon('star-fill').' ';

					if($hasJoinedForNextYear) {
						$h .= s("Vous avez adhéré à l'association pour les années {year} et {nextYear}.", ['year' => '<b>'.currentYear().'</b>', 'nextYear' => '<b>'.nextYear().'</b>']);
					} else {
						$h .= s("Vous avez adhéré à l'association pour l'année {year} !", ['year' => currentYear()]);
					}

				$h .= '</p>';
				$h .= $this->getBenefits();

			} else {

				$h .= $this->getBenefits();
			}

		$h .= '</div>';

		if($eFarm['membership'] === FALSE) {
			$h .= '<p>';
				$h .= '<a href="'.AssociationSetting::URL.'" class="btn btn-secondary">'.s("Voir le site de l'association").'</a> ';
				$h .= '<a href="'.\farm\FarmUi::url($eFarm).'/donner" class="btn btn-outline-secondary">'.s("Je veux plutôt faire un don").'</a>';
			$h .= '</p>';
		}

		$h .= '<br/>';

		return $h;

	}

	public function getMemberInformation(\farm\Farm $eFarm, \user\User $eUser): string {

		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Adhérent").'</h3>';
			$h .= '<a href="/farm/farm:updateLegal?id='.$eFarm['id'].'" class="btn btn-outline-primary">'.s("Mettre à jour").'</a>';
		$h .= '</div>';

		if($eFarm->isLegal() === FALSE) {
			$h .= '<p class="util-info">'.s("Pour obtenir un reçu pour votre comptabilité, vérifiez et mettez à jour si besoin les informations ci-dessous.").'</p>';
		}

		$h .= '<div class="util-block stick-xs mb-2">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Raison sociale").'</dt>';
				$h .= '<dd>'.encode($eFarm['legalName'] ?? $eFarm['name']).'</dd>';
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

		$fee = AssociationSetting::getFee($eFarm);

		$h = '<div id="association-join-form-container" class="mb-2">';

			$h .= '<h2>'.s("Bulletin d'adhésion").'</h2>';

			$h .= '<p>';

			if($eFarm['membership']) {

				$h .= s("Votre ferme {farmName} est déjà adhérente pour l'année {year} mais vous pouvez dès aujourd'hui adhérer pour l'année {nextYear} à venir.", [
					'farmName' => '<b>'.encode($eFarm['name']).'</b>',
					'year' => '<b>'.currentYear().'</b>',
					'nextYear' => '<b>'.nextYear().'</b>',
					'date' => '<b>'.date('31/12/Y', strtotime('next year')).'</b>'
				]);

				$for = nextYear();

			} else {

				// Promo adhésion 2025-2026
				if(currentYear() === 2025) {
					$h .= s("Les adhésions se font habituellement par année civile, mais exceptionnellement pour le lancement de l'association, les adhésions réalisées en 2025 resteront actives jusqu'au 31/12/2026 !");
				} else {
					$h .= s("Les adhésions se font par année civile et votre adhésion se terminera donc le <b>{date}</b>.", ['year' => date('Y'), 'date' => date('31/12/Y')]);
				}

				$for = currentYear();
			}

			$h .= '</p>';

			$h .= '<p>';
				$h .= s("Vous pouvez choisir le montant de votre adhésion, le montant minimum pour une année civile étant de <b>{amount}</b> pour votre ferme. Le règlement s'effectue par un paiement en ligne avec {icon} Stripe après sélection du montant et acceptation des statuts et du règlement intérieur.", ['icon' => \Asset::icon('stripe'), 'amount' => \util\TextUi::money($fee, precision: 0)]);
			$h .= '</p>';

			$h .= '<p>';
				$h .= '<a href="/presentation/adhesion" class="btn btn-outline-secondary">'.s("Comment est calculé le montant de l'adhésion ?").'</a>';
			$h .= '</p>';

			$h .= '<br/>';


			$h .= $this->getMemberInformation($eFarm, $eUser);

			$h .= '<h3>'.s("Montant de l'adhésion").'</h3>';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/association/membership:doCreatePayment', ['id' => 'association-join']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= '<p>';
					$h .= s("Si vous le souhaitez, plutôt qu'utiliser le paiement par carte bancaire, vous pouvez également faire un virement sur le compte bancaire de l'association en indiquant dans le motif du virement <b>Adhésion ferme {farm}</b> (<link>télécharger l'IBAN</link>).", ['farm' => $eFarm['id'], 'link' => '<a href="'.\Asset::getPath('association', 'document/iban.pdf').'" data-ajax-navigation="never">']);
				$h .= '</p>';

				$h .= $this->getAmountBlocks($form, [$fee, $fee + 50, $fee + 100], $fee);

				$h .= $form->checkbox('terms', 'yes', [
					'required' => TRUE,
					'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("J'accepte les <linkStatus>statuts</linkStatus> et le <linkRules>règlement intérieur</linkRules> de l'association", ['linkStatus' => '<a data-ajax-navigation="never" target="_blank" href="'.\Asset::getPath('association', 'document/statuts.pdf').'">', 'linkRules' => '<a data-ajax-navigation="never" target="_blank" href="'.\Asset::getPath('association', 'document/reglement_interieur.pdf').'">']))
				]);

				// Promo adhésion 2025-2026
				if(currentYear() === 2025) {
					$h .= $form->inputGroup($form->submit(s("J'adhère pour 2025 et 2026"), ['class' => 'btn btn-primary btn-lg']), ['class' => 'mt-2']);
				} else {
					$h .= $form->inputGroup($form->submit(s("J'adhère pour {value}", $for), ['class' => 'btn btn-primary btn-lg']), ['class' => 'mt-2']);
				}

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

		$h .= '</div>';

		return $h;

	}

	public function getPanel(\farm\Farm $eFarm): string {

		\Asset::css('farm', 'farm.css');

		$h = '<a class="farmer-farms-item" href="'.\farm\FarmUi::url($eFarm).'/adherer">';

			$h .= '<div class="farmer-farms-item-vignette">';
				$h .= \farm\FarmUi::getVignette($eFarm, '4rem');
			$h .= '</div>';

			$h .= '<div class="farmer-farms-item-content">';

				$h .= '<h4>';
					$h .= encode($eFarm['name']);
				$h .= '</h4>';

				if($eFarm['membership']) {
					$h .= '<div class="farmer-farms-item-infos">';
						$h .= \Asset::icon('star-fill').' '.s("Cette ferme a déjà adhéré à {siteName}, mais vous pouvez toujours faire un don !");
					$h .= '</div>';
				}

			$h .= '</div>';

		$h .= '</a>';

		return $h;
	}


}

?>
