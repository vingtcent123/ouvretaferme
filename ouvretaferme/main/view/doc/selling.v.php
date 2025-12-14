<?php
new AdaptativeView('market', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Le logiciel de caisse");
	$t->subTitle = s("Comment utiliser le logiciel de caisse proposé par Ouvretaferme ?");

	$t->menuSelected = 'sellingMarket';

	echo '<div class="util-block">';

		echo '<h2>'.s("À quoi sert le logiciel de caisse ?").'</h2>';
		echo '<p>'.s("Le logiciel de caisse proposé par {siteName} vous permet d'enregistrer les ventes que vous réalisez pendant vos marchés avec une tablette ou un téléphone. C'est une solution simple et efficace qui permet de gérer un grand nombre de clients par heure.").'</p>';

		echo '<p>'.s("Il fonctionne de la manière suivante :").'</p>';

		echo '<ol>';
			echo '<li>'.s("Vous créez une nouvelle vente en activant le logiciel de caisse").'</li>';
			echo '<li>'.s("Vous choisissez la gamme de produits disponibles à la vente").'</li>';
			echo '<li>'.s("Vous amenez une tablette ou votre téléphone au marché").'</li>';
			echo '<li>'.s("Lorsque les ventes démarrent, vous saisissez les commandes de vos clients directement sur votre tablette").'</li>';
			echo '<li>'.s("Une fois le marché terminé, vous clôturez la caisse").'</li>';
			echo '<li>'.s("En fin de saison, vous retrouvez dans l'analyse des ventes une synthèse complète de ce que vous avez vendu dans l'année").'</li>';
		echo '</ol>';

		echo '<p>'.s("Le logiciel de caisse est pleinement utilisable pour toutes les productions.<br/>Il est utilisé quotidiennement par des paysans boulangers, des apiculteurs, des éleveurs ou des maraîchers...").'</p>';

		echo '<h5 class="mt-2" style="text-transform: uppercase">'.s("L'interface du logiciel de caisse").'</h5>';

		echo Asset::image('main', 'doc/market-example.png');

		echo '<p>';
			echo s("Le logiciel de caisse est conforme à la réglementation sur les logiciels de caisse (inaltérabilité, sécurisation, conservation et archivage des données). Un audit de conformité a été engagé auprès de LNE conformément à la loi. Si cette fonctionnalité vous plait, nous vous invitons à <link>adhérer à notre association</link> pour nous aider à financer cet audit dont le coût s'élève 11 000 €.", ['link' => '<a href="/adherer">']);
		echo '</p>';
		echo '<p>';
			echo s("Conformément à la loi, vous pouvez fournir en cas de contrôle <link>l'attestation ci-jointe</link>, valable jusqu'au 31 août 2026.", ['link' => '<a href="'.Asset::getPath('association', 'document/attestation-logiciel-caisse.pdf').'" data-ajax-navigation="never">']);
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Utiliser le logiciel de caisse pas à pas").'</h2>';

		echo '<h3>'.s("Activer le logiciel de caisse").'</h3>';

		echo '<h4>'.Asset::icon('1-circle').' '.s("Créez un client").'</h4>';

		echo '<p>'.s("Vous devez créer un client de type <i>Point de vente pour les particuliers</i> en indiquant le nom de votre marché comme nom de client.").'</p>';
		echo Asset::image('main', 'doc/market-customer.png');

		echo '<h4>'.Asset::icon('2-circle').' '.s("Créez une vente pour ce client").'</h4>';

		echo '<p>'.s("Une fois le client créé, vous pouvez maintenant lui créer une vente en veillant à activer le logiciel de caisse pour cette vente. Vous pouvez en profiter pour sélectionner les produits que vous voulez proposer à la vente. Il n'est pas indispensable de sélectionner les produits à cette étape, vous pourrez également le faire à l'étape suivante.").'</p>';
		echo Asset::image('main', 'doc/market-sale.png');

		echo '<h4>'.Asset::icon('3-circle').' '.s("Configurez votre vente").'</h4>';

		echo '<p>'.s("Une fois la vente créée, vous pouvez encore compléter votre gamme. Pour chaque produit, vous pouvez indiquer la quantité que vous souhaitez emporter au marché. Cela peut vous être notamment utile pour préparer vos récoltes ou vos conditionnements.").'</p>';
		echo Asset::image('main', 'doc/market-configure.png');

		echo '<h4>'.Asset::icon('4-circle').' '.s("Démarrer la vente").'</h4>';

		echo '<p>'.s("Vous avez complété votre gamme ? Votre étal est prêt ?").'</p>';
		echo '<p>'.s("Il ne vous reste plus qu'à cliquer sur le bouton <link>Ouvrir le logiciel de caisse</link> pour commencer à vendre !", ['link' => '<a class="btn btn-selling">'.Asset::icon('cart4').' ']).'</p>';
		echo Asset::image('main', 'doc/market-welcome.png');


		echo '<h3>'.s("Face au client").'</h3>';

		echo '<p>'.s("Utilisez tout simplement le bouton <i>Créer une vente</i> pour commencer à saisir les produits achetés par un client.").'</p>';
		echo '<p>';
			echo s("Pour chaque produit, un clavier numérique vous permet d'effectuer une saisie rapide, et vous avez également la possibilité d'ajouter des remises pour vos clients préférés.");
			echo Asset::image('main', 'doc/market-item.png');
		echo '</p>';

		echo '<p>'.s("Pour terminer une vente avec un client avec le bouton <i>Vente payée</i>, vous devez préalablement indiquer au moins un moyen de paiement, ce qui est une obligation légale.").'</p>';

		echo '<h3>'.s("Clôturer la caisse").'</h3>';

		echo '<p>'.s("Lorsque votre marché est terminé, vous avez l'obligation de clôturer la caisse. Une fois la clôture réalisée, les données de vente sont figées et peuvent être intégrées à votre comptabilité.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Astuces").'</h2>';

		echo '<h3>'.s("Configurer les marchés suivants en un clic").'</h3>';
		echo '<div class="util-info">';
			echo '<p>'.s("Créer votre premier marché avec le logiciel de caisse va prendre un peu de temps, car vous devrez ajouter manuellement les produits que vous souhaitez vendre dans le logiciel. Pour les marchés suivants, nous vous recommandons d'utiliser la fonctionnalité <u>Dupliquer une vente</u>.").'</p>';
			echo '<p>'.s("En dupliquant votre marché précédent, vous n'avez plus qu'à choisir la nouvelle date de vente et la liste des produits que vous avez vendus la dernière fois est automatiquement reportée. Il vous restera éventuellement à supprimer les quelques produits que vous avez sortis de la vente et ajouter ceux qui entrent dans la gamme !").'</p>';
		echo '</div>';
		echo Asset::image('main', 'doc/market-duplicate.png');

		echo '<h3>'.s("Vendre à plusieurs").'</h3>';
		echo '<p class="util-info">'.s("Vous pouvez être plusieurs à utiliser le logiciel de caisse simultanément sur un même marché. Dans ce cas, nous vous suggérons de vous connecter avec des comptes différents pour que chacun puisse retrouver facilement les ventes qu'il gère avec son avatar.").'</p>';

		echo '<h3>'.s("Ajouter un produit au dernier moment dans le logiciel de caisse").'</h3>';
		echo '<p class="util-info">'.s("Si vous avez oublié d'ajouter un produit à votre caisse, utilisez l'onglet <i>Articles</i> pour ajouter le produit manquant. Cet onglet vous permet également de modifier les prix de vente.").'</p>';

		echo '<h3>'.s("Vous permettez à certains clients de payer plus tard ?").'</h3>';
		echo '<p class="util-info">';
			echo s("Si vous permettez à certains clients de payer plus tard (par exemple sur facture en fin de mois), vous avez la possibilité de sélectionner l'option <i>Vente en paiement différé</i> à la place de <i>Vente payée</i>. En choisissant cette option, la vente sera sortie du logiciel de caisse et sera intégrée à la page des ventes de votre ferme.");
		echo '</p>';
		echo Asset::image('main', 'doc/market-later.png');

		echo '<h3>'.s("Mettre le curseur sur la quantité par défaut").'</h3>';
		echo '<p class="util-info">';
			echo s("Par défaut, c'est le champ de <i>Prix</i> qui est sélectionné pour les produits vendus au poids. Si cela nous vous convient pas, vous pouvez sélectionner le champ <i>Quantité</i> par défaut dans les réglages de base du module <i>Vendre</i>.");
		echo '</p>';
		echo Asset::image('main', 'doc/market-settings.png');

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('product', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Photos libres de droit");
	$t->subTitle = s("Nous mettons à votre disposition des photos libres de droit pour les principaux légumes cultivés dans les systèmes maraîchers. Vous pouvez notamment les utiliser pour vos produits sur Ouvretaferme !");

	$t->subTitle .= '<div class="mt-3"><a href="'.\main\MainSetting::URL_PHOTOS.'" target="_blank" class="btn btn-transparent btn-xl">'.s("Accéder aux photos").'</a></div>';

	$t->menuSelected = 'sellingProduct';

	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('pricing', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("La gestion des prix");
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
		echo '<p>'.s("Si vous êtes redevable de la TVA, les ventes et les prix sont ainsi expimés <b>HT</b> pour vos clients professionnels et <b>TTC</b> pour vos clients particuliers. Si vous n'êtes pas redevable de la TVA, il n'y a pas de distinction <b>HT</b> / <b>TTC</b> mais les prix de vente restent découplés.").'</p>';

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
