<?php
namespace accounting;

class AccountUi {

	public function __construct() {
	}

	public function getManageTitle(\company\Company $eCompany): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eCompany).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les classes de comptes");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#account-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				$h .= '<a href="'.\company\CompanyUi::urlAccounting($eCompany).'/account:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un compte personnalisé").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="account-search" class="util-block-search stick-xs '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->text('class', $search->get('class'), ['placeholder' => s("Classe de compte")]);
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Libellé")]);
					$h .= $form->checkbox('vatFilter', 1, ['checked' => $search->get('vatFilter'), 'callbackLabel' => fn($input) => $input.' '.s("Avec compte de TVA uniquement")]);
					$h .= $form->checkbox('customFilter', 1, ['checked' => $search->get('customFilter'), 'callbackLabel' => fn($input) => $input.' '.s("Personnalisés")]);
				$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getManage(\company\Company $eCompany, \Collection $cAccount): string {

		if($cAccount->empty() === TRUE) {
			return '<div class="util-info">'.s("Aucun compte n'a encore été enregistré").'</div>';
		}
		\Asset::js('main', 'settings.js');

		$h = '<div class="util-info">';
			$h .= s("Il est possible de créer des classes de compte (dites “personnalisées“), par exemple pour créer un compte-courant par associé.");
		$h .= '</div>';

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table id="account-list" class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>';
							$h .= s("Classe");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Libellé");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Personnalisé ?");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Compte de TVA");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Taux de TVA");
						$h .= '</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cAccount as $eAccount) {

					$classNumber = strlen($eAccount['class']) - 2;

					$h .= '<tr name="account-'.$eAccount['id'].'">';

						$h .= '<td>';
							$h .= '<span class="ml-'.$classNumber.'">';
								$h .= $classNumber === 0 ? '<b>' : '';
									$h .= encode($eAccount['class']);
								$h .= $classNumber === 0 ? '</b>' : '';
							$h .= '</span>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<span class="ml-'.$classNumber.'">';
								$h .= $classNumber === 0 ? '<b>' : '';
									if($eAccount['custom'] === TRUE) {
										$eAccount->setQuickAttribute('company', $eCompany['id']);
										$h .= $eAccount->quick('description', encode($eAccount['description']));
									} else {
										$h .= encode($eAccount['description']).'</span>';
									}
								$h .= $classNumber === 0 ? '</b>' : '';
						$h .= '</td>';

						$h .= '<td class="text-center">';
							if($eAccount['custom'] === TRUE) {
								$h .= 'oui';
							}
						$h .= '</td>';

						$h .= '<td>';
							$h .= ($eAccount['vatAccount']->exists() === TRUE ? '<a '.attr('onclick', 'Settings.scrollTo('.$eAccount['vatAccount']['id'].');').'>'.encode($eAccount['vatAccount']['class']).'</a>' : '');
						$h .= '</td>';

						$h .= '<td>';
							if($eAccount['vatAccount']->exists() === TRUE and $eAccount['vatAccount']['vatRate'] !== NULL) {
								$h .= encode($eAccount['vatAccount']['vatRate']).'%';
							} else {
								$h .= $eAccount['vatRate'] !== NULL ? $eAccount['vatRate'].'%' : '';
							}
						$h .= '</td>';
						$h .= '<td>';
							if($eAccount['custom'] === TRUE and $eAccount['nOperation'] === 0) {
								$message = s("Confirmez-vous la suppression de cette classe de compte ?");
								$h .= '<a data-ajax="'.\company\CompanyUi::urlAccounting($eCompany).'/account:doDelete" post-id="'.$eAccount['id'].'" data-confirm="'.$message.'" class="btn btn-outline-secondary btn-outline-danger">'.\Asset::icon('trash').'</a>';
							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function getAutocomplete(int $company, Account $eAccount, \Search $search = new \Search()): array {

		\Asset::css('media', 'media.css');

		$vatRate = 0.0;
		if($eAccount['vatRate'] !== NULL) {
			$vatRate = $eAccount['vatRate'];
		} elseif($eAccount['vatAccount']->exists() === TRUE) {
			$vatRate = $eAccount['vatAccount']['vatRate'];
		}

		$itemHtml = encode($eAccount['class'].' '.$eAccount['description']);
		if(
			$search->get('classPrefix')
			and $search->get('classPrefix') === (string)\Setting::get('accounting\vatClass')
			and $eAccount['vatRate'] !== NULL
		) {
			$itemHtml .= ' ('.$eAccount['vatRate'].'%)';
		}

		return [
			'value' => $eAccount['id'],
			'class' => encode($eAccount['class']),
			'vatRate' => $vatRate,
			'company' => $company,
			'itemHtml' => $itemHtml,
			'itemText' => $eAccount['class'].' '.$eAccount['description']
		];

	}

	public static function getAutocompleteCreate(\company\Company $eCompany): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Créer une classe de compte").'</div>';

		return [
			'type' => 'link',
			'link' => \company\CompanyUi::urlAccounting($eCompany).'/account:create',
			'itemHtml' => $item
		];

	}

	public function query(\PropertyDescriber $d, int $company, bool $multiple = FALSE, array $query = []): void {

		$d->prepend = \Asset::icon('journal-text');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir la classe...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlAccounting($company).'/account:query?'.http_build_query($query);
		$d->autocompleteResults = function(Account $e) use ($company) {
			return self::getAutocomplete($company, $e);
		};

	}

	public static function getAutocompleteLabel(string $query, int $company, string $label): array {

		\Asset::css('media', 'media.css');

		return [
			'value' => $label,
			'company' => $company,
			'itemHtml' => str_replace($query, '<b>'.$query.'</b>', $label),
			'itemText' => encode($label),
		];

	}

	public function queryLabel(\PropertyDescriber $d, int $company, ?string $query, bool $multiple = FALSE): void {

		$d->prepend = \Asset::icon('123');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir le compte...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlAccounting($company).'/account:queryLabel';
		$d->autocompleteResults = function(string $label) use ($company, $query) {
			return self::getAutocompleteLabel($query, $company, $label);
		};

	}

	public function create(\company\Company $eCompany, Account $eAccount): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccounting($eCompany).'/account:doCreate', ['id' => 'accounting-account-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eAccount, ['class*', 'description*']);

		$h .= $form->dynamicGroup($eAccount, 'vatAccount', function($d) use($form) {
		});
		$h .= $form->dynamicGroup($eAccount,  'vatRate', function($d) use ($form) {
				$d->default = NULL;
			}
		);

		$h .= $form->group(
			content: $form->submit(s("Créer la classe"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-accounting-account-create',
			title: s("Ajouter une classe de compte personnalisée"),
			body: $h
		);

	}

	public static function getSummaryBalanceCategories(): array {
		return [
			['min' => 10, 'max' => 12, 'name' => s("Capital, report, résultat")],
			['min' => 13, 'max' => 13, 'name' => s("Subventions d'investissement")],
			['min' => 14, 'max' => 15, 'name' => s("Provisions")],
			['min' => 16, 'max' => 16, 'name' => s("Emprunts")],
			['min' => 17, 'max' => 18, 'name' => s("Dettes rattachées, comptes de liaisons")],
			['min' => 20, 'max' => 20, 'name' => s("Immobilisations incorporelles")],
			['min' => 21, 'max' => 24, 'name' => s("Immobilisations corporelles et en cours")],
			['min' => 25, 'max' => 27, 'name' => s("Participations et autres immo. financières")],
			['min' => 28, 'max' => 28, 'name' => s("Amortissments")],
			['min' => 29, 'max' => 29, 'name' => s("Provisions pour dépréciations")],
			['min' => 30, 'max' => 30, 'name' => s("Approvisionnements et marchandises")],
			['min' => 31, 'max' => 32, 'name' => s("Animaux")],
			['min' => 33, 'max' => 34, 'name' => s("Végétaux en terre")],
			['min' => 35, 'max' => 36, 'name' => s("En cours de production")],
			['min' => 37, 'max' => 37, 'name' => s("Produits")],
			['min' => 38, 'max' => 38, 'name' => s("Inventaire permanent")],
			['min' => 40, 'max' => 40, 'name' => s("Fournisseurs")],
			['min' => 41, 'max' => 41, 'name' => s("Clients")],
			['min' => 42, 'max' => 42, 'name' => s("Personnels")],
			['min' => 43, 'max' => 43, 'name' => s("MSA et autres organismes sociaux")],
			['min' => 44, 'max' => 44, 'name' => s("État et autres collectivités publiques")],
			['min' => 45, 'max' => 45, 'name' => s("Groupe, communautés d'exploitation")],
			['min' => 46, 'max' => 46, 'name' => s("Débiteurs et créditeurs divers")],
			['min' => 47, 'max' => 47, 'name' => s("Comptes transitoires")],
			['min' => 48, 'max' => 48, 'name' => s("Comptes de régularisation")],
			['min' => 49, 'max' => 49, 'name' => s("Provisions pour dépréciation")],
			['min' => 50, 'max' => 50, 'name' => s("Valeurs mobilières de placement")],
			['min' => 51, 'max' => 51, 'name' => s("Banques")],
			['min' => 52, 'max' => 52, 'name' => s("Instruments de trésorerie")],
			['min' => 53, 'max' => 53, 'name' => s("Caisse")],
			['min' => 54, 'max' => 54, 'name' => s("Règles d'avance")],
			['min' => 58, 'max' => 58, 'name' => s("Virements internes")],
			['min' => 59, 'max' => 59, 'name' => s("Provisions pour dépréciation")],
			['min' => 603, 'max' => 603, 'name' => s("Variation des stocks")],
			['min' => 60, 'max' => 60, 'name' => s("Achats")],
			['min' => 61, 'max' => 62, 'name' => s("Charges externes")],
			['min' => 63, 'max' => 63, 'name' => s("Impôts et taxes")],
			['min' => 64, 'max' => 64, 'name' => s("Charges de personnels")],
			['min' => 65, 'max' => 65, 'name' => s("Autres charges de gestion")],
			['min' => 66, 'max' => 66, 'name' => s("Charges financières")],
			['min' => 67, 'max' => 67, 'name' => s("Charges exceptionnelles")],
			['min' => 68, 'max' => 68, 'name' => s("Dotations aux amortissements")],
			['min' => 69, 'max' => 69, 'name' => s("IS et participation des salariés")],
			['min' => 70, 'max' => 70, 'name' => s("Ventes")],
			['min' => 71, 'max' => 72, 'name' => s("Variation inventaire")],
			['min' => 73, 'max' => 73, 'name' => s("Production immobilisée")],
			['min' => 74, 'max' => 74, 'name' => s("Produits nets partiels")],
			['min' => 75, 'max' => 75, 'name' => s("Indemnités et subventions")],
			['min' => 76, 'max' => 76, 'name' => s("Produits financiers")],
			['min' => 77, 'max' => 77, 'name' => s("Produits exceptionnels")],
			['min' => 78, 'max' => 78, 'name' => s("Reprises sur amortissements")],
			['min' => 79, 'max' => 79, 'name' => s("Transferts de charges")],
		];
	}

	public static function getLiabilityBalanceCategories(): array {

		return [
			'capitaux-propres' => [
				'name' => s("Capitaux propres"),
				'categories' => [
					[
						'name' => s("Capital social"),
						'accounts' => [1015],
					],
					[
						'name' => s("Primes d'émission, de fusion, d'apport"),
						'accounts' => [108],
					],
					[
						'name' => s("Écarts de réévaluation"),
						'accounts' => [],
					],
					[
						'name' => s("Réserves"),
						'accounts' => [106],
					],
					[
						'name' => s("Résultat de l'exercice (bénéfice ou perte)"),
						'accounts' => [120, 129],
					],
					[
						'name' => s("Subventions d'investissement"),
						'accounts' => [131, 138, 139],
					],
					[
						'name' => s("Amortissements dérogatoires"),
						'accounts' => [145],
					],
					[
						'name' => s("Autres provisions réglementées"),
						'accounts' => [],
					],
				]
			],
			'provisions' => [
				'name' => s("Provisions"),
				'categories' => [
					[
						'name' => s("Provisions pour risques et charges"),
						'accounts' => [15],
					],
				]
			],
			'dettes' => [
				'name' => s("Dettes"),
				'categories' => [
					[
						'name' => s("Dettes financières"),
						'accounts' => [164],
					],
					[
						'name' => s("Avances et acomptes reçus sur commandes"),
						'accounts' => [419],
					],
					[
						'name' => s("Autres dettes"),
						'accounts' => [401, 408, 421, 455],
					],
					[
						'name' => s("Instruments de trésorerie"),
						'accounts' => [],
					],
					[
						'name' => s("Produits constatés d'avance"),
						'accounts' => [487],
					],
					[
						'name' => s("Écarts de conversion - Passif"),
						'accounts' => [],
					],
				],
			],
		];

	}
	public static function getAssetBalanceCategories(): array {

		return [
			'actif-immobilise' => [
				'name' => s("Actif immobilisé"),
				'categories' => [
					[
						'name' => s("Capital souscrit non appelé"),
						'accounts' => [109],
					],
					[
						'name' => s("Immobilisations incorporelles"),
						'accounts' => [201, /*203, */205, 206, 207, 208, /*232, */237],
					],
					[
						'name' => s("Immobilisations corporelles hors biens vivants"),
						'accounts' => [211, 212, 213, 214, 215, 218, 231, 238],
					],
					[
						'name' => s("Immobilisations corporelles biens vivants"),
						'accounts' => [241, 243, 246],
					],
					[
						'name' => s("Immobilisations financières"),
						'accounts' => [/*261, 266, 267, 268, 271, 272, 273, 274, 275, 276, 277*/],
					],
				]
			],
			'actif-circulant' => [
				'name' => s("Actif circulant"),
				'categories' => [
					[
						'name' => s("Biens vivants et en-cours (cycle long)"),
						'accounts' => [31, 341, 332, 338],
					],
					[
						'name' => s("Biens vivants et en-cours (cycle court)"),
						'accounts' => [32, 348],
					],
					[
						'name' => s("Stocks"),
						'accounts' => [30, 371, 374, 375, 376],
					],
					[
						'name' => s("Avances & acomptes versés / commandes créances"),
						'accounts' => [],
					],
					[
						'name' => s("Créances"),
						'accounts' => [411, 46, 445, /*455*/],
					],
					[
						'name' => s("Valeurs mobilières de placement"),
						'accounts' => [50],
					],
					[
						'name' => s("Instruments de trésorerie"),
						'accounts' => [],
					],
					[
						'name' => s("Disponibilités"),
						'accounts' => [512, 531],
					],
					[
						'name' => s("Charges constatées d'avance"),
						'accounts' => [486],
					],
					[
						'name' => s("Charges à répartir sur plusieurs exercices"),
						'accounts' => [],
					],
					[
						'name' => s("Écarts de conversion - Actif"),
						'accounts' => [],
					],
				],
			],
		];

	}

	public static function getLabelByAccount(int $account): string {

		switch($account) {
			case 109:
				return s("Apporteurs, capital souscrit non appelé");
			case 201:
				return s("Frais d'établissement");
			case 205:
				return s("Cessions et droits");
			case 206:
				return s("Droit au bail");
			case 207:
				return s("Fonds commercial");
			case 208:
				return s("Autres immo incorporelles");
			case 237:
				return s("Avances et acomptes versés");
			case 211:
				return s("Terrains");
			case 212:
				return s("Aménagements fonciers");
			case 213:
				return s("Améliorations du fonds");
			case 214:
				return s("Constructions");
			case 215:
				return s("Installations techniques, matériel et outillage");
			case 218:
				return s("Autres");
			case 231:
				return s("Immobilisations corporelles en cours");
			case 238: // PAS SURE
				return s("Avances et acomptes");
			case 241:
				return s("Animaux reproducteurs (adultes)");
			case 242:
				return s("Animaux reproducteurs (jeunes de renouvel.)");
			case 243:
				return s("Animaux de service");
			case 246:
				return s("Plantations pérennes");
			case 31:
				return s("Animaux cycle long");
			case 341:
				return s("Avances aux cultures");
			case 332:
				return s("Pépinières (cycle long)");
			case 338:
				return s("Autres végétaux en terre (cycle long)");
			case 32:
				return s("Animaux cycle court");
			case 348:
				return s("Pépinières (cycle court)");
			case 30:
				return s("Stocks");
			case 371:
				return s("Stocks produits intermédiaires végétaux");
			case 374:
				return s("Stocks produits finis végétaux");
			case 375:
				return s("Stocks produits animaux");
			case 376:
				return s("Stocks produits finis transformés");
			case 411:
				return s("Créances (clients)");
			case 46:
				return s("Débiteurs créditeurs divers");
			case 445:
				return s("État (TVA et taxes assimilées)");
			case 455:
				return s("Associés - compte courant");
			case 50:
				return s("Placement");
			case 512:
				return s("Banque");
			case 531:
				return s("Caisse");
			case 486:
				return s("Charges constatées d'avance");
			case 1015:
				return s("Capital social");
			case 108:
				return s("Compte de l'exploitant");
			case 106:
				return s("Réserves");
			case 120:
				return s("Résultat d'exercice (bénéfice)");
			case 129:
				return s("Résultat d'exercice (perte)");
			case 131:
				return s("Subvention d'investissement");
			case 138:
				return s("Autres types de subventions");
			case 139:
				return s("Amortissement de subventions d'investissements et autres");
			case 145:
				return s("Amortissements dérogatoires");
			case 15:
				return s("Provisions");
			case 164:
				return s("Emprunts");
			case 419:
				return s("Acomptes perçus");
			case 401:
				return s("Dettes (fournisseurs)");
			case 408:
				return s("Fournisseurs");
			case 421:
				return s("Personnel (rémunérations dues)");
			case 487:
				return s("Produit constaté d'avance");

		}

		return '';
	}


	public static function p(string $property): \PropertyDescriber {

		$d = Account::model()->describer($property, [
			'class' => s("Classe"),
			'description' => s("Libellé"),
			'custom' => s("Personnalisé"),
			'vatAccount' => s("Compte de TVA"),
			'vatRate' => s("Taux de TVA"),
		]);

		switch($property) {

			case 'vatAccount':
				$d->autocompleteBody = function (\util\FormUi $form, Account $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'vatAccount'];
				new \accounting\AccountUi()->query($d, GET('company', '?int'), query: ['classPrefix' => \Setting::get('accounting\vatClass')]);
				break;

			case 'class':
				$d->attributes['minlength'] = 4;
				$d->attributes['maxlength'] = 8 ;
		}
		return $d;

	}

}

?>
