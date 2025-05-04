<?php
new AdaptativeView('shared', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Boutiques collectives");
	$t->subTitle = s("Les boutiques collectives permettent de partager un même espace de vente entre plusieurs producteurs !");

	$t->menuSelected = 'shopShared';

	echo '<div class="util-block">';

		echo '<h2>'.s("Administrer une boutique collective").'</h2>';

		echo '<p>'.s("La création d'une boutique collective doit être l'aboutissement d'une réflexion partagée avec vos collègues. Nous vous conseillons fortement de travailler en amont le cadre de votre collaboration, et de vous assurer que les fonctionnalités de {siteName} sont compatibles avec ce cadre.").'</p>';
		echo '<p>'.s("Une boutique, qu'elle soit collective ou non, est toujours administrée par une ferme. Si vous souhaitez que plusieurs producteurs administrent votre boutique, nous vous conseillons de créer une ferme dédiée à votre groupement, et d'inviter les producteurs comme administrateurs de cette ferme.").'</p>';
		echo '<p>';
			echo '<a href="/farm/farm:create" class="btn btn-outline-secondary" target="_blank">'.s("Créer une ferme").'</a> ';
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Rejoindre une boutique collective").'</h2>';
		echo '<p>'.s("Un producteur ne peut rejoindre une boutique collective que s'il dispose d'un code d'invitation valide. Attention, pour des raisons de sécurité, les codes d'invitation ont une durée de vie limitée.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Démarrer une boutique collective").'</h2>';

		echo '<p>'.s("Voici comment vous pouvez démarrer simplement la configuration d'une boutique collective :").'</p>';

		echo '<ul>';
			echo '<li>'.s("Invitez les producteurs sur la boutique").'</li>';
			echo '<li>'.s("Demander à chaque producteur d'associer un ou plusieurs de leurs catalogues à la boutique").'</li>';
			echo '<li>'.s("Optionnellement, personnalisez les rayons de votre boutique (par exemple avec des rayons <i>Fruits et légumes</i>, <i>Plants potagers</i>...) et associez chaque catalogue à un rayon").'</li>';
			echo '<li>'.s("Créez une première vente en sélectionnant les producteurs disponibles pour participer à cette vente !").'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Qui peut faire quoi sur une boutique collective ?").'</h2>';

		echo '<h3>'.s("Un producteur peut :").'</h3>';
		echo '<ul>';
			echo '<li>'.s("Configurer les catalogues qu'il souhaite associer à la boutique").'</li>';
			echo '<li>'.s("Définir les catalogues proposés pour chaque vente").'</li>';
			echo '<li>'.s("Ajouter, modifier ou supprimer des quantités sur les commandes qui le concernent").'</li>';
		echo '</ul>';

		echo '<h3>'.s("Un administrateur de la boutique peut en plus :").'</h3>';
		echo '<ul>';
			echo '<li>'.s("Inviter ou exclure des producteurs de la boutique").'</li>';
			echo '<li>'.s("Configurer la boutique et le rayonnage").'</li>';
		echo '</ul>';

		echo '<p>'.s("<b>Nous vous rappelons que {siteName} ne propose volontairement pas de fonctionnement prédéterminé pour la préparation et la logistique des commandes réalisées dans les boutiques collectives. C'est aux producteurs de s'organiser et de trouver un fonctionnement qui leur convient.</b>").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Limites des boutiques collectives").'</h2>';
		echo '<p>'.s("Quelques limites ont été introduites dans les boutiques collectives par rapport aux boutiques personnelles :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Le choix du moyen de paiement est désactivé et c'est à vous d'informer les clients sur la façon dont ils peuvent régler leurs commandes").'</li>';
			echo '<li>'.s("Il n'est pas possible de configurer des frais de livraison").'</li>';
			echo '<li>'.s("Il n'est pas possible d'ajouter des commandes manuellement").'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
