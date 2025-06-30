<?php
namespace payment;

/**
 * Stripe UI
 *
 */
class StripeFarmUi {

	public function __construct() {

		\Asset::css('payment', 'stripe.css');
		\Asset::js('payment', 'stripe.js');

	}

	public function getManage(\farm\Farm $eFarm, StripeFarm $eStripeFarm): string {

		if($eStripeFarm->empty()) {
			return $this->getCreate($eFarm);
		} else {
			return $this->getConfigured($eStripeFarm);
		}

	}

	private function getConfigured(StripeFarm $eStripeFarm): string {

		$h = '';

		$h .= '<h3>';
			$h .= s("Votre compte Stripe a bien été configuré !");
		$h .= '</h3>';
		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Le paiement en ligne est désormais activable sur l'ensemble de vos boutiques en ligne, et le produit de vos ventes sera versé directement sur votre compte Stripe.").'</p>';
			$h .= self::getWarning();
			$h .= '<h3>'.s("Pour la sécurité de vos paiements").'</h3>';
			$h .= '<ul>';
				$h .= '<li>'.s("Ne communiquez jamais les clés API et Webhook à des tiers !").'</li>';
				$h .= '<li>'.s("La clé Webhook a été créée automatiquement par {siteName} sur votre compte Stripe et ne doit pas être supprimée").'</li>';
			$h .= '</ul>';
		$h .= '</div>';

		$h .= '<dl class="util-presentation util-presentation-1">';
			$h .= '<dt>'.self::p('apiSecretKey')->label.'</dt>';
			$h .= '<dd>rk_live_...'.substr($eStripeFarm['apiSecretKey'], -4).'</dd>';
			$h .= '<dt>'.self::p('webhookSecretKey')->label.'</dt>';
			$h .= '<dd>whsec_...'.substr($eStripeFarm['webhookSecretKey'], -4).'</dd>';
		$h .= '</dl>';

		if($eStripeFarm['apiSecretKeyTest'] !== NULL or $eStripeFarm['webhookSecretKeyTest'] !== NULL) {

			$h .= '<br/>';

			$h .= '<h3>'.s("Mode test").'</h3>';

			$h .= '<dl class="util-presentation util-presentation-1">';
				if($eStripeFarm['apiSecretKeyTest'] !== NULL) {
					$h .= '<dt>'.self::p('apiSecretKeyTest')->label.'</dt>';
					$h .= '<dd>sk_test_...'.substr($eStripeFarm['apiSecretKeyTest'], -4).'</dd>';
				}
				if($eStripeFarm['webhookSecretKeyTest'] !== NULL) {
					$h .= '<dt>'.self::p('webhookSecretKeyTest')->label.'</dt>';
					$h .= '<dd>whsec_...'.substr($eStripeFarm['webhookSecretKeyTest'], -4).'</dd>';
				}
			$h .= '</dl>';

		}

		$h .= '<br/>';

		$h .= '<a data-ajax="/payment/stripe:doDelete" post-id="'.$eStripeFarm['id'].'" class="btn btn-danger" data-confirm="'.s("Vous ne pourrez plus accepter les paiements en ligne. Voulez-vous continuer ?").'">'.s("Supprimer la configuration").'</a>';

		return $h;

	}

	public static function getWarning(): string {

		$h = '<h4>'.s("Rappel important").'</h4>';
		$h .= '<p>'.s("{siteName} connecte vos clients directement avec Stripe et ne prend donc <b>aucune commission</b> sur le paiement en ligne. La contrepartie naturelle de cette gratuité est que {siteName} ne vous apporte aucune garantie et aucun support sur le paiement en ligne. Si ce service ne correspond pas à vos attentes, nous vous invitons à le désactiver et utiliser des solutions alternatives.").'</p>';

		return $h;

	}

	private function getCreate(\farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$eStripeFarm = new StripeFarm([
			'farm' => $eFarm
		]);

		$h = '<h1>';
			$h .= s("Configurer le paiement en ligne");
		$h .= '</h1>';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Vous pouvez activer le paiement par carte bancaire sur vos boutiques de vente en ligne grâce au service {icon} Stripe. C'est à vous de créer un compte sur Stripe, et de récupérer les informations demandées sur cette page, qui vous permettront de connecter {siteName} avec le serveur de paiement.", ['icon' => \Asset::icon('stripe')]).'</p>';
			$h .= '<p>'.s("Veuillez noter qu'à aucun moment {siteName} n'intervient dans les procédures de paiement en ligne. C'est cette absence d'intermédiaire qui vous permet de ne payer que les commissions demandées par Stripe, inférieures à 2 % et bien plus faibles que celles demandées par des sites comme <link>cagette.net</link>.", ['icon' => \Asset::icon('stripe'), 'link' => '<a href="https://www.cagette.net/">']).'</p>';
			$h .= '<p>'.s("La contrepartie de cette absence d'intermédiaire est que vous devez vous-même créer un compte sur le prestataire de paiement {icon} Stripe. Si vous n'êtes pas à l'aise avec ça, nous vous recommandons de renoncer au paiement en ligne ou de vous faire aider, étant entendu que les clés secrètes qui vous sont demandées ci-dessous <u>ne doivent être communiquées à personne</u>.", ['icon' => \Asset::icon('stripe')]).'</p>';
			$h .= '<a href="https://stripe.com/" target="_blank" class="btn btn-secondary">'.s("Accéder à {icon} Stripe", ['icon' => \Asset::icon('stripe')]).'</a>';
		$h .= '</div>';

		$h .= $form->openAjax('/payment/stripe:doCreate');

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($eStripeFarm, ['apiSecretKey']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer les clés"))
			);

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = StripeFarm::model()->describer($property, [
			'apiSecretKey' => s("Clé API limitée Stripe"),
			'apiSecretKeyTest' => s("Clé API Stripe pour le mode test"),
			'webhookSecretKey' => s("Clé Webhook secrète de signature Stripe"),
			'webhookSecretKeyTest' => s("Clé Webhook secrète de signature Stripe pour le mode test"),
		]);

		switch($property) {

			case 'apiSecretKey' :
				$d->placeholder = 'rk_live...';
				$d->before = function(\util\FormUi $form, StripeFarm $eStripeFarm) {

					$h = '<div class="util-block-help">';
						$h .= '<h4>'.s("Obtenir la clé API").'</h4>';
						$h .= '<p>'.s("La clé API limitée peut être récupérée dans la section <i>Développeurs</i> de {icon} Stripe. Le mode opératoire pour créer une clé API limitée :", ['icon' => \Asset::icon('stripe')]).'</p>';
						$h .= '<ul>';
							$h .= '<li>'.s("Allez dans la section développeurs de {icon} Stripe, puis sur l'onglet <i>Clés API</i>").'  '.\Asset::icon('arrow-right').'  <a href="https://dashboard.stripe.com/apikeys" class="btn btn-secondary btn-sm" target="_blank">'.\Asset::icon('link').' '.s("Lien direct").'</a></li>';
							$h .= '<li>'.s("Cliquez sur <i>Créer une clé limitée</i>").'</li>';
							$h .= '<li>'.s("Choisissez l'option <i>Fournir cette clé à un autre site Web</i>").'</li>';
							$h .= '<li>';
								$h .= s("Renseignez les informations demandées :");
							$h .= '<ul>';
								$h .= '<li>'.s("Nom : <i>Ouvretaferme</i>").'</li>';
								$h .= '<li>'.s("URL : <i>{value}</i>", \Lime::getUrl()).'</li>';
							$h .= '</ul>';
							$h .= '</li>';
							$h .= '<li>'.s("Créez la clé API !").'</li>';
						$h .= '</ul>';
						$h .= '<p>'.s("Une fois que vous avez créé votre clé API, copiez-là ci-dessous.").'</p>';
					$h .= '<p>'.s("Il est possible que {icon} Stripe fasse évoluer son interface et que le mode opératoire ci-dessus ne soit plus tout à fait exact. Dans ce cas, vous pouvez nous en informer en cliquant sur <i>Signaler un problème</i> en bas de cette page.", ['icon' => \Asset::icon('stripe')]).'</p>';
					$h .= '</div>';

					return $h;

				};
				break;

			case 'apiSecretKeyTest' :
				$d->placeholder = 'sk_test_...';
				break;

			case 'webhookSecretKey' :
			case 'webhookSecretKeyTest' :
				$d->placeholder = 'whsec_...';
				$d->before = function(\util\FormUi $form, StripeFarm $eStripeFarm) {

					$h = '<div class="util-block-help">';
						$h .= '<h4>'.s("Configurer le Webhook").'</h4>';
						$h .= '<p>'.s("Le webhook se configure dans la section <i>Développeurs</i> de {icon} Stripe. Le mode opératoire pour configurer le Webhook :", ['icon' => \Asset::icon('stripe')]).'</p>';
						$h .= '<ul>';
							$h .= '<li>'.s("Allez dans la Section développeurs de {icon} Stripe puis sur l'onglet <i>Webhooks</i>", ['icon' => \Asset::icon('stripe')]).'  '.\Asset::icon('arrow-right').'  <a href="https://dashboard.stripe.com/webhooks" class="btn btn-secondary btn-sm" target="_blank">'.\Asset::icon('link').' '.s("Lien direct").'</a></li>';
							$h .= '<li>'.s("Cliquez sur <i>Ajouter un endpoint</i>").'</li>';
							$h .= '<li>';
								$h .= s("Renseignez les informations suivantes :");
								$h .= '<ul>';
									$h .= '<li>'.s("URL d'endpoint :").' <u>'.\Lime::getUrl().'/payment/stripe:webhook?farm='.$eStripeFarm['farm']['id'].'</u></li>';
									$h .= '<li>'.s("Événements à sélectionner :").' '.s("<b>tous</b> les événements <i>Checkout</i> ET <b>tous</b> les événements <i>Payment Intent</i>").'</li>';
								$h .= '</ul>';
							$h .= '</li>';
						$h .= '</ul>';
						$h .= '<p>'.s("Une fois que vous avez créé votre <i>endpoint</i>, récupérez sa clé secrète de signature et renseignez-là ci-dessous.").'</p>';
					$h .= '<p>'.s("Il est possible que {icon} Stripe fasse évoluer son interface et que le mode opératoire ci-dessus ne soit plus tout à fait exact. Dans ce cas, vous pouvez nous en informer en cliquant sur <i>Signaler un problème</i> en bas de cette page.", ['icon' => \Asset::icon('stripe')]).'</p>';
					$h .= '</div>';

					return $h;

				};
				break;

		}

		return $d;

	}

}
