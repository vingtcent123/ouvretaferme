<?php
namespace company;

class SubscriptionUi {

	public function __construct() {
		\Asset::css('company', 'subscription.css');
	}

	public static function urlManage(Company $eCompany): string {
		return CompanyUi::url($eCompany).'/subscription:manage';
	}

	public function getManageTitle(Company $eCompany): string {

		$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlSettings($eCompany).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("L'abonnement");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public static function getCompanySubscriptionTypeBySubscriptionType(string $subscriptionType): int {

		return match($subscriptionType) {
			SubscriptionElement::ACCOUNTING => CompanyElement::ACCOUNTING,
			SubscriptionElement::PRODUCTION => CompanyElement::PRODUCTION,
			SubscriptionElement::SALES => CompanyElement::SALES,
			default => new \NotExpectedAction('Unknown company subscription type'),
		};

	}

	public static function getSubscriptionTypeByCompanySubscriptionType(int $companySubscriptionType): string {

		return match($companySubscriptionType) {
			CompanyElement::ACCOUNTING => SubscriptionElement::ACCOUNTING,
			CompanyElement::PRODUCTION => SubscriptionElement::PRODUCTION,
			CompanyElement::SALES => SubscriptionElement::SALES,
			default => new \NotExpectedAction('Unknown subscription type'),
		};

	}

	public static function getCurrent(Company $eCompany): string {

		$h = '<h2>'.s("Votre abonnement").'</h2>';

		if($eCompany['cSubscription']->empty()) {

			$h .= '<div class="util-info">';
				$h .= s("Vous n'avez pas d'abonnement en cours !");
			$h .= '</div>';

		} else {

			$h .= '<div class="stick-sm util-overflow-sm">';

				$h .= '<table class="subscriptions-table tr-even tr-hover">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("Souscrit le").'</th>';
							$h .= '<th>'.s("Module").'</th>';
							$h .= '<th>'.s("Date de fin").'</th>';
							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';
						$h .= '<div class="util-overflow-sm">';

						foreach($eCompany['cSubscription'] as $eSubscription) {

							$price = \Setting::get('company\subscriptionPrices')[$eSubscription['type']];

							$h .= '<tr>';

								$h .= '<td>';
									$h .= \util\DateUi::numeric($eSubscription['updatedAt']);
								$h .= '</td>';

								$h .= '<td>';
									$h .= self::p('type')->values[$eSubscription['type']];
								$h .= '</td>';

								$h .= '<td>';
									$h .= \util\DateUi::numeric($eSubscription['endsAt']);
								$h .= '</td>';

								$h .= '<td>';

									if($eCompany['isBio'] === FALSE) {

											$h .= '<a class="btn btn-primary" title="'.s("Prolonger pour un an à {price}", ['price' => \util\TextUi::money($price)]).'" data-ajax="'.CompanyUi::url($eCompany).'/subscription:subscribe" post-type="'.self::getCompanySubscriptionTypeBySubscriptionType($eSubscription['type']).'">'.\Asset::icon('arrow-repeat').'</a>';

									}

								$h .= '</td>';

							$h .= '</tr>';
						}

					$h .= '</tbody>';
				$h .= '</table>';
			$h .= '</div>';

		}

		return $h;

	}

	public static function getHistory(\Collection $cSubscriptionHistory): string {

		if($cSubscriptionHistory->empty()) {
			return '';
		}

		$h = '<h2>'.s("Historique de vos abonnements").'</h2>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="subscriptions-table tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date de souscription").'</th>';
						$h .= '<th>'.s("Module").'</th>';
						$h .= '<th>'.s("Date de fin").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= '<div class="util-overflow-sm">';

					foreach($cSubscriptionHistory as $eSubscriptionHistory) {

						$h .= '<tr>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eSubscriptionHistory['createdAt']);
							$h .= '</td>';

							$h .= '<td>';
								$h .= self::p('type')->values[$eSubscriptionHistory['type']];
								if($eSubscriptionHistory['isPack']) {
									$h .= ' '.s("(pack)");
								}
								if($eSubscriptionHistory['isBio']) {
									$h .= ' <span class="is-bio" title="'.s("Grâce à votre certification biologique").'">'.\Asset::icon('leaf').'</span>';
								}
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eSubscriptionHistory['endsAt']);
							$h .= '</td>';

						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	private static function getModuleData(): array {

		return [
			SubscriptionElement::PRODUCTION => [
				'title' => s("Ma production"),
				'icon' => 'calendar3',
				'subtitle' => s("Planifier et gérer ma production"),
				'items' => [
					s("Planifiez vos saisons"),
					s("Créez et réutilisez vos itinéraires techniques"),
					s("Gérez votre matériel"),
					s("Maîtrisez votre temps de travail"),
					s("Collaborez avec votre équipe"),
				],
			],
			SubscriptionElement::SALES => [
				'title' => s("Ma commercialisation"),
				'icon' => 'shop',
				'subtitle' => s("Commercialiser mes produits"),
				'items' => [
					s("Gérez vos ventes (professionnels et particuliers)"),
					s("Utilisez un logiciel de caisse intuitif et intégré<sup>*</sup>"),
					s("Créez des boutiques en ligne"),
					s("Pilotez vos stocks"),
				],
			],
			SubscriptionElement::ACCOUNTING => [
				'title' => s("Ma compta"),
				'icon' => 'calculator',
				'subtitle' => s("Gérer ma comptabilité<sup>*</sup>"),
				'items' => [
					s("Importez vos relevés bancaires"),
					s("Saisissez vos écritures à partir des opérations bancaires"),
					s("Suivez la comptabilité, les amortissements et la TVA"),
					s("Éditez des documents d'analyse"),
					s("Ouvrez un accès à votre comptable"),
				],
			],
		];

	}

	public function getPlans(Company $eCompany): string {

		$h = '<h2>'.s("Toutes les offres sans engagement").'</h2>';

		$h .= '<div class="subscriptions">';

			foreach([SubscriptionElement::PRODUCTION, SubscriptionElement::SALES, SubscriptionElement::ACCOUNTING] as $subscriptionType) {

				$moduleData = self::getModuleData()[$subscriptionType];
				$companySubscriptionType = self::getCompanySubscriptionTypeBySubscriptionType($subscriptionType);
				$price = \Setting::get('company\subscriptionPrices')[$subscriptionType];

				$h .= '<div class="subscriptions-item">';
					$h .= '<div class="subscriptions-item-title">';
						$h .= '<span>'.$moduleData['title'].'</span>';
						$h .= \Asset::icon($moduleData['icon']);
					$h .= '</div>';
					$h .= '<div class="subscriptions-item-subtitle">'.$moduleData['subtitle'].'</div>';
					$h .= '<ul class="subscription-item-list">';
						foreach($moduleData['items'] as $item) {
							$h .= '<li>'.\Asset::icon('chevron-right').$item.'</li>';
						}
					$h .= '</ul>';

					if($eCompany->notEmpty()) {
						if($eCompany['subscriptionType'] !== NULL and $eCompany['subscriptionType']->value($companySubscriptionType) === TRUE) {
							$eSubscription = $eCompany['cSubscription']->find(fn($e) => $e['type'] === $subscriptionType)->first();
							$h .= '<a class="btn btn-outline-secondary">';
								$h .= s("Votre abonnement est actif jusqu'au {date}", ['date' => \util\DateUi::numeric($eSubscription['endsAt'])]);
							$h .= '</a>';
						} else {
							$h .= '<a class="btn btn-primary" data-ajax="'.CompanyUi::url($eCompany).'/subscription:subscribe" post-type="'.$companySubscriptionType.'">'.s("Souscrire pour 1 an à {price}", ['price' => \util\TextUi::money($price)]).'</a>';
						}
					}
				$h .= '</div>';

			}

		$h .= '</div>';

		$h .= '<p class="more-info"><sup>*</sup>'.s("Le logiciel de caisse n'est pas encore certifié NF525, et le module de comptabilité n'est pas encore certifié NF203.").'</p>';

		if($eCompany->empty() or $eCompany['isBio'] === FALSE) {

			$h .= '<div class="subscriptions-item">';

				$h .= '<div class="subscriptions-item-title">';
					$h .= '<span>'.s("Pack spécial").'</span>';
				$h .= '</div>';

				$h .= '<div class="subscriptions-item-subtitle">'.s("Offre spéciale : module production, commercialisation et comptabilité").'</div>';

				$h .= '<ul class="subscription-item-list">';
					$h .= '<li>';
						$h .= \Asset::icon('chevron-right');
						$h .= s("Les 3 modules pour {pricePack} au lieu de {totalPrices} !", [
							'pricePack' => \util\TextUi::money(\Setting::get('company\subscriptionPackPrice')),
							'totalPrices' => \util\TextUi::money(array_sum(\Setting::get('company\subscriptionPrices'))),
							]);
						$h .= '</li>';
				$h .= '</ul>';

				if($eCompany->notEmpty()) {
					if($eCompany['cSubscription']->count() === 3) {

						$h .= '<a class="btn btn-outline-secondary" data-ajax="'.CompanyUi::url($eCompany).'/subscription:subscribePack">'.s("Renouveler mes 3 modules pour {pricePack}", ['pricePack' => \util\TextUi::money(\Setting::get('company\subscriptionPackPrice'))]).'</a>';

					} else {

						$h .= '<a class="btn btn-primary" data-ajax="'.CompanyUi::url($eCompany).'/subscription:subscribePack">'.s("Souscrire aux 3 modules pour 1 an à {pricePack}", ['pricePack' => \util\TextUi::money(\Setting::get('company\subscriptionPackPrice'))]).'</a>';

					}
				}
			$h .= '</div>';

		}
		return $h;

	}
	public static function p(string $property): \PropertyDescriber {

		$d = Subscription::model()->describer($property, [
			'type' => s("Module"),
			'endsAt' => s("Jusqu'à"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					SubscriptionElement::ACCOUNTING => s("Comptabilité"),
					SubscriptionElement::PRODUCTION => s("Production"),
					SubscriptionElement::SALES => s("Commercialisation"),
				];
				break;

		}

		return $d;

	}

}
