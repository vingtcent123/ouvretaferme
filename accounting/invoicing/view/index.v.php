<?php
new AdaptativeView('/facturation-electronique', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/facturation-electronique';

	$this->mainTitleClass = 'invoicing-presentation';

	$t->mainTitle = '<h1>'.s("À propos de la facturation électronique").'</h1>';
	$t->mainTitle .= '<span>'.s("(et pourquoi ce ne sera pas un problème)").'</span>';

	Asset::css('invoicing', 'invoicing.css');

	echo '<h3 class="mt-2">'.s("Principes généraux").'</h3>';

	echo '<div class="util-block">';
		echo '<p>'.s("La réforme de la facturation électronique concerne toutes les entreprises assujetties à la TVA.").'</p>';
			echo '<ul>';
				echo '<li>'.s("À partir du 1<sup>er</sup> septembre 2026, elles devront être en mesure de recevoir des factures électroniques de la part de leurs fournisseurs.").'</li>';
				echo '<li>';
					echo 	s("À partir du 1<sup>er</sup> septembre 2027, elles seront tenues :");
					echo '<ul>';
						echo '<li>'.s("d'émettre leurs factures au format électronique (<i>e-invoicing</i>)").'</li>';
						echo '<li>'.s("de transmettre le montant des opérations réalisées avec des clients particuliers ou certaines associations (<i>e-reporting</i>)").'</li>';
					echo '</ul>';
				echo '</li>';
			echo '</ul>';
		echo '<p>'.s("Vous pouvez trouver des informations fiables sur la <link>foire aux questions</link> éditée par les finances publiques.", ['link' => '<a href="https://www.impots.gouv.fr/sites/default/files/media/1_metier/2_professionnel/EV/2_gestion/290_facturation_electronique/faq---fe_je-decouvre-la-facturation-electronique.pdf">']).'</p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Comment émettre et recevoir des factures électroniques ?").'</h3>';

	echo '<div class="util-block">';
		echo '<p>'.s("Vous devrez contractualiser avec une plateforme agréée (PA), qui vous permettra de réaliser l'ensemble des opérations. Il est important de comprendre qu'avec cette réforme, vous n'aurez plus le droit de transmettre vos factures directement à vos clients professionnels et qu'elles devront obligatoirement transiter par votre PA.").'</p>';
		echo '<p><i>'.s("Point important : vous pourrez tout à fait utiliser plusieurs PA en parallèle et en changer comme bon vous semble.").'</i></p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Pourquoi il n'y a rien d'urgent ?").'</h3>';

	echo '<div class="util-block">';
		echo '<p>'.s("Un grand nombre d'opérateurs ayant identifié une opportunité commerciale se sont positionnés sur le marché de la facturation électronique. Il y a une situation de forte concurrence qui poussent certains de ces opérateurs à jouer sur la peur et l'urgence.").'</p>';
		echo '<p>'.s("Néanmoins, à l'heure actuelle, il faut bien comprendre que les infrastructures techniques ne sont pas encore prêtes du côté de la plupart des PA et que le travail de normalisation est encore en cours.").'</p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Comment ça va se passer sur Ouvretaferme ?").'</h3>';

	echo '<div class="util-block">';
		echo '<p>'.s("Nous allons travailler avec une plateforme agréée qui vous permettra d'envoyer automatiquement vos factures depuis Ouvretaferme. Nous avons choisi <link>SUPER PDP</link>. Cette plateforme est l'une des plus avancées et nous sommes déjà en train de l'intégrer.", ['link' => '<a href="https://www.superpdp.tech/">']).'</p>';
		echo '<p>'.s("L'utilisation de <i>SUPER PDP</i> est <link>gratuite jusqu'à 1000 factures par mois</link>, ce qui correspond à l'immense majorité des producteurs. Vous pourrez même l'utiliser indépendamment de Ouvretaferme.", ['link' => '<a href="https://www.superpdp.tech/tarifs">']).'</p>';
		echo '<p>'.s("Nous allons chercher également à intégrer pleinement <i>SUPER PDP</i> avec Ouvretaferme. Cette intégration sera facturée à l'association par <i>SUPER PDP</i> et nous la rendrons donc disponible pour les fermes ayant adhéré à l'association. <b>Notre objectif est que vous puissiez gérer l'ensemble de vos factures de ventes directement depuis Ouvretaferme.</b>").'</p>';
		echo '<p><i>'.s("<p>Notre opinion : il ne faut pas être trop pressé et il est stratégiquement intéressant de laisser d'autres acteurs essuyer les plâtres et les bugs qui accompagneront le lancement de la réforme.").'</i></p>';
	echo '</div>';

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});


new AdaptativeView('/factures/', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/factures/';

	$t->mainTitle = new \farm\FarmUi()->getAccountingInvoiceTitle($data->eFarm, $data->counts);

	if(FEATURE_PA === FALSE) {

		echo '<div class="util-block-important	">'.s("Cette fonctionnalité arrive bientôt ! On se dépêche... ").' '.Asset::icon('lightning-charge-fill').Asset::icon('lightning-charge-fill').Asset::icon('lightning-charge-fill').'</div>';

	} else {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Cette page vous permet de suivre le statut de vos factures envoyées et reçues sur la Plateforme Agréée.").'</p>';
		echo '</div>';

		echo '<div class="tabs-item">';

		foreach(['buy', 'sell'] as $tab) {

			echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/factures/?tab='.$tab.'">';
			echo match($tab) {
				'sell' => s("Ventes"),
				'buy' => s("Achats"),
			};
			echo ' <small class="tab-item-count">'.$data->counts['invoice'][$tab].'</small>';
			echo '</a>';

		}

	}

	echo '</div>';


});
