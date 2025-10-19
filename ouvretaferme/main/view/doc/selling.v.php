<?php
new AdaptativeView('pricing', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Les gestion des prix");
	$t->subTitle = s("Comment définir avec Ouvretaferme vos prix de vente en fonction de vos clients ?");

	$t->menuSelected = 'sellingPricing';

	echo '<div class="util-block">';

		echo '<h5 style="text-transform: uppercase">'.s("Introduction à la gestion des prix").'</h5>';
		echo '<h2>'.s("Distinguer les ventes aux professionnels et aux particuliers").'</h2>';
		echo '<p>'.s("Nous avons fait le choix sur Ouvretaferme de découpler les prix de vente pour vos clients professionnels et vos clients particuliers, ce qui signifie concrètement que :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Vous choisissez pour chaque produit un prix pour les professionnels et un prix pour les particuliers").'</li>';
			echo '<li>'.s("Il n'est pas possible de partager un même catalogue de vente entre professionnels et particuliers").'</li>';
			echo '<li>'.s("Un groupe de clients ne peut contenir que des professionnels ou que des particuliers").'</li>';
			echo '<li>'.s("Et ainsi de suite...").'</li>';
		echo '</ul>';
		echo '<p>'.s("Si vous êtes assujetti à la TVA, les ventes et les prix sont ainsi expimés <b>HT</b> pour vos clients professionnels et <b>TTC</b> pour vos clients particuliers. Si vous n'êtes pas assujetti à la TVA, il n'y a pas de distinction <b>HT</b> / <b>TTC</b> mais les prix de vente restent découplés.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Comprendre les pratiques des producteurs").'</h2>';

		echo '<p>'.s("Les producteurs adoptent des politiques tarifaires très différentes.").'</p>';
		echo '<p>'.s("Vous êtes nombreux à n'avoir qu'un seul prix pour chacun de produits, et dans ce cas, cet article ne vous sera pas forcément utile ! Dans d'autres fermes, il peut y avoir autant de prix que de clients, notamment dans le cas où les débouchés sont très diversifiés ou bien parce que des accords commerciaux particuliers ont été passés avec les clients en question.").'</p>';
		echo '<p>';
			echo s("Nous avons essayé sur Ouvretaferme de garder un système simple pour les producteurs qui ont des prix simples, mais également hautement personnalisable pour les autres.");
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Quelques définitions").'</h2>';

		echo '<ul>';
			echo '<li>'.s("<b>Prix de base.</b> Le prix que vous choisissez lorsque vous configurez un produit.").'</li>';
			echo '<li>'.s("<b>Prix catalogue.</b> Le prix d'un produit dans un catalogue donné. Lorsque vous modifiez un prix catalogue, le prix de base n'est pas impacté !").'</li>';
			echo '<li>'.s("<b>Prix personnalisé</b> Le prix d'un produit que vous avez personnalisé pour un client ou un groupe de clients").'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<br/>';
	echo '<h4>'.s("Cas n°1").'</h4>';
	echo '<h2>'.s("Mes prix sont les mêmes pour tout le monde (ou presque)").'</h2>';

	echo '<div class="util-block">';

		echo '<p>'.s("Vous avez une seule grille tarifaire ? Éventuellement quelques exceptions ?<br/>Nous vous conseillons de :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Choisir pour chaque produit un prix de base pour vos clients particuliers et / ou vos clients professionnels").'</li>';
			echo '<li>'.s("Éviter d'utiliser les catalogues dont les prix sont découplés des prix de base").'</li>';
			echo '<li>'.s("Utiliser les prix personnalisés pour vos quelques clients ou groupes de clients pour lesquels vous avez fait des exceptions").'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<br/>';
	echo '<h4>'.s("Cas n°2").'</h4>';
	echo '<h2>'.s("J'ai (presque) autant de prix que de clients").'</h2>';


	echo '<div class="util-block">';

		echo '<h3>'.s("Comprendre la hiérarchie des prix des produits").'</h3>';

		echo '<p>'.s("Pour que vos clients retrouvent sur les boutiques en ligne et sur leurs factures les prix que vous leur avez promis, il faut bien comprendre la hiérarchie des prix sur Ouvretaferme.").'</p>';

		echo '<ol>';
			echo '<li>'.s("<b>Le prix de base.</b> C'est le prix qui s'applique à vos ventes si vous n'utilisez pas de catalogue ou si vous n'avez pas défini de prix personnalisés pour un client ou un groupe de clients").'</li>';
			echo '<li>'.s("<b>Le prix catalogue.</b> C'est le prix qui s'applique à vos ventes qui passent par des catalogues sur les boutiques en ligne. Lorsque vous utilisez des catalogues, vous devez vous rappeler que les prix de base ne s'appliquent plus et que si vous modifiez un prix catalogue, le prix de base du produit n'est pas modifié. C'est le découplage.").'</li>';
			echo '<li>'.s("<b>Le prix personnalisé d'un groupe de clients.</b> Si vous avez personnalisé un prix pour un groupe de clients, celui-ci est toujours prioritaire par rapport au prix de base ou au prix catalogue. Si un client appartient à plusieurs groupes qui ont chacun un prix personnalisé pour un produit donné, alors on applique au client le prix du groupe le moins cher.").'</li>';
			echo '<li>'.s("<b>Le prix personnalisé pour un client.</b> Il est prioritaire à tous les autres.").'</li>';
		echo '</ol>';

		echo '<p>'.s("À vous de jouer maintenant !").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h3>'.s("Quelques situations concrètes et solutions imaginables").'</h3>';

		echo '<h4>'.Asset::icon('1-circle').'<br/>'.s("Je vends mes produits à 50 % en AMAP et le reste sur des marchés. J'applique le même prix sur tous mes marchés, mais des prix différents en AMAP.").'</h4>';
		echo '<p>'.s("Vous pouvez renseigner comme prix de base de vos produits le prix que vous appliquez aux marchés. Pour vos amapiens, vous pourriez créer un groupe de clients <b>AMAP</b> auquel ils seraient rattachés, et définir ensuite des prix personnalisés pour ce groupe.").'</p>';

		echo '<h4>'.Asset::icon('2-circle').'<br/>'.s("Je vends exclusivement aux professionnels, et j'ai des prix différents pour les restaurants et les magasins spécialisés.").'</h4>';
		echo '<p>'.s("Vous avez plusieurs options :").'</p>';
		echo '<ol>';
			echo '<li>'.s("Créer un groupe de clients <b>Restaurateur</b> et un groupe de clients <b>Magasin</b>. Pour chacun des groupes, vous définissez les prix que vous voulez personnaliser par rapport aux prix de base.").'</li>';
			echo '<li>'.s("Créer un catalogue pour les restaurants et un catalogue pour les magasins spécialisés, ce qui vous permettra d'appliquer des prix différents pour leurs commandes sur les boutiques en ligne.").'</li>';
		echo '</ol>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
