<?php
new AdaptativeView('/doc/', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Utiliser Ouvretaferme");
	$t->subTitle = s("Quelques règles simples pour vous retrouver dans le logiciel.");

	$t->menuSelected = 'mainUse';

	echo '<div>';

		echo '<h2>'.s("Introduction").'</h2>';
		echo '<p>'.s("Ouvretaferme est un logiciel conçu pour contribuer à l’autonomie des fermes et soutenir agriculteurs et agricultrices pour réaliser les finalités économiques, sociales et environnementales de leur projet.").'</p>';
		echo '<p>'.s("Même si nous avons soigné Ouvretaferme pour que son utilisation soit la plus simple et ergonomique possible, il est normal que la prise en main du logiciel soit progressive. C'est un outil, et comme tout outil, il faut prendre le temps de le maîtriser. Rappelez-vous la première fois que vous avez conduit un tracteur !").'</p>';
		echo '<p>'.s("C'est en ayant défini précisément vos besoins et vos attentes que vous pourrez tirer sur le long terme le meilleur parti de Ouvretaferme pour votre ferme.").'</p>';

		echo '<h3>'.s("Pour démarrer").'</h3>';

		echo '<ul>';
			echo '<li><a href="/doc/main:help">'.s("Découvrez comment obtenir de l'aide").'</a></li>';
			echo '<li><a href="/doc/main:design">'.s("Lisez les grands principes ergonomiques du logiciel").'</a></li>';
		echo '</ul>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('design', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Principes ergonomiques");
	$t->subTitle = s("Les grands principes ergonomiques mis en oeuvre sur Ouvretaferme.");

	$t->menuSelected = 'mainDesign';

	echo '<h2>'.s("Ouvretaferme propose trois modules").'</h2>';
	echo '<ul>';
		echo '<li>'.s("<b>Vendre</b> pour gérer vos ventes, vos factures et vos boutiques en ligne", ['b' => '<b class="color-commercialisation">']).'</li>';
		echo '<li>'.s("<b>Produire</b> destiné aux cultures spécialisées pour créer un plan de culture et gérer son planning", ['b' => '<b class="color-production">']).'</li>';
		echo '<li>'.s("<b>Gestion</b> pour réaliser la comptabilité et la gestion de votre ferme", ['b' => '<b class="color-accounting">']).'</li>';
	echo '</ul>';

	echo '<p>'.s("Chaque module est accessible depuis le menu principal avec sa propre couleur :").'</p>';

	echo Asset::image('main', 'doc/design-module.png');

	echo '<h2>'.s("Tous les modules fonctionnent de la même manière").'</h2>';

	echo '<ul>';
		echo '<li>';
			echo s("Un module est composé de plusieurs sections qui vous permettent d'accéder aux fonctionnalités métier");
			echo Asset::image('main', 'doc/design-section.png');
		echo '</li>';
		echo '<li>';
			echo s("Des pages d'analyse dans chaque module vous permettent d'analyser vos données pour améliorer vos pratiques");
			echo Asset::image('main', 'doc/design-analyze.png');
		echo '</li>';
		echo '<li>';
			echo s("Une page de paramétrage propose une multitude d'options de personnalisation");
			echo Asset::image('main', 'doc/design-settings.png');
		echo '</li>';
	echo '</ul>';

	echo '<h3>'.s("En dehors des modules, un menu vous permet de paramétrer votre ferme :").'</h3>';
	echo Asset::image('main', 'doc/design-farm.png');

	echo '<h2>'.s("Toutes les pages fonctionnent de la même manière").'</h2>';

	echo '<h3>'.s("Dès que vous voyez une {icon}, c'est qu'il y a quelque chose que vous pouvez paramétrer", ['icon' => '<span class="doc-icon">'.Asset::icon('gear-fill').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("N'hésitez pas à fouiller un peu partout, vous découvrirez une multitude de fonctionnalités très pratiques.").'</p>';
	echo Asset::image('main', 'doc/page-gear.png');

	echo '<h3>'.s("Dès que vous voyez une {icon}, c'est qu'il y a des fonctionnalités à découvrir", ['icon' => '<span class="doc-icon">'.Asset::icon('list').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("Par exemple depuis la page des ventes, vous pouvez n'afficher que les ventes destinées aux particuliers ou aux professionnels, et imprimer vos étiquettes de colisage.").'</p>';
	echo Asset::image('main', 'doc/page-list.png');

	echo '<h3>'.s("Dès que vous voyez une {icon}, c'est que des options sont disponibles", ['icon' => '<span class="doc-icon">'.Asset::icon('caret-down-fill').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("Cette icône permet généralement de personnaliser les pages ou de trier les données.").'</p>';
	echo Asset::image('main', 'doc/page-option.png');

	echo '<h3>'.s("Dès que vous voyez une {icon}, c'est que vous êtes face à une astuce", ['icon' => '<span class="doc-icon">'.Asset::icon('lightbulb').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("Vous pouvez soit la lire car les astuces contiennent parfois des informations qui pourraient vous intéresser, soit la cacher définitivement en cliquant sur le bouton approprié.").'</p>';
	echo '<a href="/doc/selling:product">'.Asset::image('main', 'doc/page-tip.png').'</a>';

	echo '<h3>'.s("Dès que vous voyez des cases à cocher à côté à gauche d'un tableau, c'est que vous pouvez réaliser des actions groupées ...", ['icon' => '<span class="doc-icon">'.Asset::icon('lightbulb').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("Dans l'exemple ci-dessous, assignez à plusieurs produits simultanément une nouvelle catégorie.").'</p>';
	echo Asset::image('main', 'doc/page-checkbox.png');

	echo '<h3>'.s("... Et lorsque vous voyez une case à cocher à côté d'un titre, c'est qu'elle permet de cocher automatiquement tout ce qui est en dessous !", ['icon' => '<span class="doc-icon">'.Asset::icon('lightbulb').'</span>']).'</h3>';
	echo '<p class="util-info">'.s("Dans l'exemple ci-dessous, vous pouvez sélectionner en un clic toutes les interventions <i>Matériel</i>.").'</p>';
	echo Asset::image('main', 'doc/page-all.png');

});

new AdaptativeView('help', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Obtenir de l'aide");
	$t->subTitle = s("Comment faire si vous avez besoin d'être accompagné pour prendre en main le logiciel ?");

	$t->menuSelected = 'mainHelp';

	echo '<h2>'.s("Comment fonctionne Ouvretaferme ?").'</h2>';
	echo '<p>'.s("Ouvretaferme est développé sur du temps bénévole par une association.").'</p>';
	echo '<p>'.s("Nous ne sommes donc pas en mesure de faire un support individuel, mais nous avons mis en place plusieurs solutions pour que vous puissiez obtenir l'aide dont vous avez besoin, et en retour aider vos collègues quand vous avez des réponses !").'</p>';
	echo '<a href="'.\association\AssociationSetting::URL.'" class="btn btn-secondary" target="_blank">'.s("En savoir plus sur l'association").'</a>';

	echo '<br/>';
	echo '<br/>';

	echo '<h2>'.s("Les solutions pour obtenir de l'aide").'</h2>';

	echo '<h3>'.Asset::icon('1-circle').' '.s("Rejoindre le serveur Discord").'</h3>';

	echo '<p>'.s("Discord est un espace d'entraide sur lequel vous pouvez poser les questions que vous avez sur Ouvretaferme.").'</p>';
	echo '<p>'.s("Plusieurs centaines de producteurs ont déjà rejoint cet espace pour :").'</p>';
	echo '<ul>';
		echo '<li>'.s("Reporter les bugs que vous pourriez avoir sur votre compte").'</li>';
		echo '<li>'.s("Demander de l'aide sur les fonctionnalités que vous ne comprenez pas").'</li>';
		echo '<li>'.s("Suivre les actualités du site").'</li>';
		echo '<li>'.s("Et plus généralement échanger autour de l'utilisation de {siteName}").'</li>';
	echo '</ul>';
	echo '<p>'.s("<b>Et une fois que vous serez à l'aise avec l'outil, vous pourrez même apporter votre aide à vos collègues !</b>").'</p>';
	echo '<a href="https://discord.gg/bdSNc3PpwQ" class="btn btn-secondary mt-1" target="_blank">'.s("Recevoir une invitation sur Discord").'</a>';

	echo '<h3>'.Asset::icon('2-circle').' '.s("Utiliser la ferme démo").'</h3>';

	echo '<p>'.s("Nous avons mis en place une ferme démo partagée entre tous les producteurs. Cette ferme démo reprend les données d'une ferme réelle qui utilise {siteName}.").'</p>';
	echo '<h5>'.s("Le principe de la démo est simple : vous pouvez faire n'importe quoi dessus !<br/>C'est l'endroit idéal pour tester {siteName} sans risquer de corrompre les données de votre ferme.").'</h5>';
	echo '<ul>';
		echo '<li>'.s("Testez la création de ventes et de produits").'</li>';
		echo '<li>'.s("Testez la création de factures").'</li>';
		echo '<li>'.s("Testez les itinéraires techniques").'</li>';
		echo '<li>'.s("...").'</li>';
	echo '</ul>';
	echo '<p>';
		echo s("Vous avez supprimé par erreur (ou volontairement si vous avez du temps) toutes les données de la ferme démo ?<br/>Aucun problème, elle se réinitialise automatiquement toutes les nuits.");
	echo '</p>';
	echo '<a href="'.OTF_DEMO_URL.'" class="btn btn-secondary mt-1" target="_blank">'.s("Parcourir la démo").'</a>';

	echo '<h3>'.Asset::icon('3-circle').' '.s("Échanger avec les collègues de votre territoire").'</h3>';

	echo '<p>'.s("Des milliers de producteurs et productrices utilisent Ouvretaferme.").'</p>';

	echo '<h5>'.s("Une chose est sûre, vous avez forcément des collègues qui utilisent comme vous le logiciel.<br/>Formez-vous ensemble et entraidez-vous !").'</h5>';

	echo '<p>'.s("Vous avez un territoire en commun, et probablement en commun également des méthodes de travail et une commercialisation. Vous entraider à un niveau local est probablement le moyen le plus puissant de prendre en main Ouvretaferme.").'</p>';

	echo '<h3>'.Asset::icon('4-circle').' '.s("Parcourir la documentation").'</h3>';

	echo '<p>'.s("La documentation est encore parcellaire, mais elle apporte de l'aide sur quelques fonctionnalités dont la prise en main n'est pas toujours évidente.<br/>Nous la complétons régulièrement en fonction des besoins.").'</p>';

	echo '<p>'.s("Vous pouvez également <link>parcourir le blog de Ouvretaferme</link>, qui explique le fonctionnement des nouveautés et propose quelques ressources.", ['link' => '<a href="https://blog.ouvretaferme.org/" target="_blank">']).'</p>';

	echo '<h3>'.Asset::icon('5-circle').' '.s("Faire des formations").'</h3>';

	echo '<p>'.s("Des formations à l'utilisation de Ouvretaferme sont régulièrement organisées.<br/>Même si notre association n'en assure pas directement, nous faisons la promotion de celles dont nous en avons connaissance.").'</p>';

	echo '<h5>'.s("Demandez à vos GAB, chambres... des formations sur Ouvretaferme !").'</h5>';

	echo '<p>'.s("Vous trouverez facilement une dizaine de collègues prêts à se former à l'outil avec vous. Si votre organisme de formation a besoin de formateurs, il est possible de contacter l'association pour que nous puissions vous donner des contacts.").'</p>';

	echo '<p>'.s("Vous organisez une formation sur Ouvretaferme finançable avec Vivea ?<br/>Contactez-nous également pour que nous en fassions la promotion.").'</p>';

	echo '<br/>';

	echo '<h2>'.s("Les 3 choses à faire pour ne pas avoir de réponse").'</h2>';

	echo '<div class="util-block">';

		echo '<ul>';
			echo '<li>⛔ '.s("<b>Nous demander de l'aide sur le site de l'association.</b> Nous n'avons pas les moyens d'assurer un support individuel.").'</li>';
			echo '<li>⛔ '.s("<b>Nous proposer une idée de fonctionnalité.</b> La <link>feuille de route</link> est déjà bien chargée, et ce ne sont pas les idées qui manquent, mais plutôt le temps bénévole pour les développer.", ['link' => '<a href="https://blog.ouvretaferme.org/feuille-de-route">']).'</li>';
			echo '<li>';
				echo '⛔ '.s("<b>Se comporter en client 👑.</b> Vous voulez une relation de client à fournisseur pour les outils numériques de votre ferme ? N'utilisez surtout pas {siteName} et dirigez-vous vers des entreprises commerciales qui seront à même de répondre à vos exigences :");
				echo '<ul>';
					echo '<li>'.s("Pour la production : Elzeard, Heirloom, Permatechnics... généralement pour 300 € à 1000 € par an").'</li>';
					echo '<li>'.s("Pour la commercialisation : Socleo, Kuupanda, Coopcircuits, Cagette... généralement pour 300 € à 1000 € par an").'</li>';
					echo '<li>'.s("Pour la comptabilité : Istea, Isacompta, Macompta... généralement pour 200 € à 500 € par an").'</li>';
				echo '</ul>';
			echo '</li>';
		echo '</ul>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
