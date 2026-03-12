<?php
/**
 * Script pour rajouter un compte manquant.
 * php framework/lime.php -a ouvretaferme -e prod company/configure/accounts
 */
new Page()
	->cli('index', function($data) {

		$accounts = [
			'4551' => ['description' => s("Compte courant d'associé")],
			'60' => ['description' => s("Achats"), 'vatRate' => 20],
			'70' => ['description' => s("Ventes"), 'vatRate' => 5.5],
			'10' => ['description' => s("Capital et réserves")],
			'11' => ['description' => s("Report à nouveau")],
			'12' => ['description' => s("Résultat de l'exercice")],
			'13' => ['description' => s("Subventions d'investissement")],
			'14' => ['description' => s("Provisions réglementées")],
			'15' => ['description' => s("Provisions pour risques et charges")],
			'16' => ['description' => s("Emprunts et dettes assimilées")],
			'17' => ['description' => s("Dettes rattachées à des participations")],
			'18' => ['description' => s("Comptes de liaison des établissements et sociétés en participation")],
			'20' => ['description' => s("Immobilisations incorporelles")],
			'21' => ['description' => s("Immobilisations corporelles")],
			'22' => ['description' => s("Immobilisations mises en cession")],
			'23' => ['description' => s("Immobilisations en cours, avantes et acomptes")],
			'24' => ['description' => s("Immobilisations corporelles (biens vivants)")],
			'27' => ['description' => s("Autres immobilisations financières")],
			'28' => ['description' => s("Amortissements des immobilisations")],
			'29' => ['description' => s("Dépréciation des immobilisations")],
			'40' => ['description' => s("Fournisseurs et comptes rattachés")],
			'41' => ['description' => s("Clients et comptes rattachés")],
			'42' => ['description' => s("Personnel et comptes rattachés")],
			'43' => ['description' => s("Mutualité sociale agricole et autres organismes sociaux")],
			'44' => ['description' => s("État et autres collectivités publiques")],
			'45' => ['description' => s("Groupe, communautés d'exploitation et associés")],
			'46' => ['description' => s("Débiteurs divers et créditeurs divers")],
			'48' => ['description' => s("Comptes de régularisation")],
			'49' => ['description' => s("Dépréciation des comptes de tiers")],
			'50' => ['description' => s("Valeurs mobilières de placement")],
			'51' => ['description' => s("Banques, établissements financiers et assimilés")],
			'53' => ['description' => s("Caisse")],
			'58' => ['description' => s("Virements internes")],
			'59' => ['description' => s("Dépréciation des comptes financiers")],
			'61' => ['description' => s("Charges externes")],
			'62' => ['description' => s("Autres services extérieurs")],
			'63' => ['description' => s("Impôts, taxes et versements assimilés")],
			'64' => ['description' => s("Charges de personnel")],
			'65' => ['description' => s("Autres charges de gestion courante")],
			'66' => ['description' => s("Charges financières")],
			'67' => ['description' => s("Charges exceptionnelles")],
			'68' => ['description' => s("Dotations aux amortissements et aux provisions")],
			'69' => ['description' => s("Participations des salariés - impôts sur les bénéfices")],
			'72' => ['description' => s("Production immobilisée")],
			'73' => ['description' => s("Production autoconsommée")],
			'74' => ['description' => s("Subventions d'exploitation")],
			'75' => ['description' => s("Autres produits de gestion courante")],
			'76' => ['description' => s("Produits financiers")],
			'77' => ['description' => s("Produits exceptionnels")],
			'78' => ['description' => s("Reprises sur amortissements et provisions")],
		];

		foreach($accounts as $class => $account) {
			\company\GenericAccount::model()
				->whereClass($class)
				->update($account);
		}

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			foreach($accounts as $class => $account) {
				\account\Account::model()
					->whereClass($class)
					->update($account);
			}
		}

	});
?>
