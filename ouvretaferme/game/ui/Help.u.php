<?php
namespace game;

class HelpUi {

	public static function getSummary(): string {
	
		$h = '<div class="game-intro">';
			$h .= '<h3>'.s("La fin d'année approche et vous pensiez pouvoir partir tranquillement en vacances ?").'</h3>';
			$h .= '<h2>'.s("Détrompez-vous, il vous reste une ultime mission à accomplir en décembre avant de profiter d'un moment de repos bien mérité.").'</h2>';
			$h .= '<div class="util-block">';
				$h .= '<p>'.s("Le père Noël 🎅 et les lutins sont bien en train de confectionner les 247 millions de cadeaux 🎁 commandés par les petits enfants français, bien entendus fabriqués en bois et emballés avec un papier recyclable. Par contre, petit problème, le père Noël a encore oublié de s'occuper de la logistique pour nourrir ses rennes, qui ne mangent que des légumes biologiques 🙄.").'</p>';
				$h .= '<p>'.s("C'est donc à vous que revient cette lourde mission. En lien avec les autres fermes, il vous reste seulement quelques semaines pour cultiver 🥕 et autres 🫛 pour qu'ils puissent se ravitailler chez vous dans la nuit du 24 décembre.").'</p>';
				$h .= '<p class="text-center">'.s("<b>Pas de légumes pour les rennes, pas de cadeaux 😞<br/>À vous de jouer !</b>").'</p>';
			$h .= '</div>';
			$h .= '<div class="game-intro-disclaimer">';
				$h .= '<h4>'.s("Pourquoi ce jeu ?").'</h4>';
				$h .= '<p>'.s("C'est d'abord l'opportunité de vous amuser seul ou avec vos collègues et vos clients avant de démarrer une nouvelle année.").'</p>';
				$h .= '<p>'.s("C'est aussi un moyen pour Ouvretaferme de vous demander officiellement d'adhérer à notre association pour contribuer à sécuriser financièrement notre projet sur le long terme. Parce que oui, en <link>ayant adhéré à l'association</link>, vous bénéficierez de quelques bonus dans le jeu pour sauver Noël !", ['link' => '<a href="/adherer">']).'</p>';
			$h .= '</div>';
		$h .= '</div>';
		
		return $h;
		
	}

	public static function getRules(bool $new): string {

		$h = '<div class="game-intro">';
			$h .= '<h3>'.s("Les règles du jeu").'</h3>';
			$h .= '<div class="util-block mb-2">';
				$h .= '<p>'.s("Vous disposez chaque jour de {value} heures de temps de travail que vous pouvez répartir sur les différentes actions :", GameSetting::TIME_DAY).'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("<b>IMPLANTER</b> une nouvelle culture sur une de vos parcelles").'</li>';
					$h .= '<li>'.s("<b>RÉCOLTER</b> les légumes à la fin d'une de vos cultures").'</li>';
					$h .= '<li>'.s("<b>ARROSER</b> une de vos cultures ou celles d'un autre joueur").'</li>';
					$h .= '<li>'.s("<b>TROQUER</b> des légumes avec les autres joueurs").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Votre compteur de temps de travail est remis à zéro chaque nuit à minuit.").'</p>';
			$h .= '</div>';
			$h .= '<h3>'.s("Comment gagner ?").'</h3>';
			$h .= '<div class="util-block mb-2">';
				$h .= '<p>'.s("À partir du 24 décembre entre 20:00 et 23:59, les rennes du Père Noël viendront manger les légumes que vous aurez récoltés pour eux. Votre objectif est de voir passer un maximum de rennes sur votre partie, et pour cela vous devez :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("<b>PRODUIRE</b> le plus de légumes possibles <i>(1 légume attirera 1 renne)</i>").'</li>';
					$h .= '<li>'.s("<b>CUISINER</b> des soupes de légumes <i>(1 soupe attirera 10 rennes)</i>").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Une soupe de légume se cuisine en utilisant 1 légume de chacune des 5 espèces proposées dans le jeu.").'</p>';
			$h .= '</div>';
			$h .= '<h3>'.s("Les bonus").'</h3>';
			$h .= '<div class="util-block">';
				$h .= '<p>'.s("Chaque jour, vous pourrez ouvrir un petit cadeau pour débloquer une récompense qui vous aidera dans votre quête !").'</p>';
				$h .= '<p>'.s("Si vous êtes membre de l'équipe d'une ferme qui a adhéré à l'association Ouvretaferme, vous débloquez les deux bonus suivants :").'</p>';
				$h .= '<ul class="mb-1">';
					$h .= '<li>'.s("<b>{premium} heures de travail par jour au lieu de {value} heures</b>", ['value' => GameSetting::TIME_DAY, 'premium' => GameSetting::TIME_DAY_PREMIUM]).'</li>';
					$h .= '<li>'.s("<b>Proposer un troc aux autres joueurs</b>").'</li>';
				$h .= '</ul>';
				$h .= '<p class="text-center">';
					$h .= '<a href="/adherer" class="btn btn-game">'.s("Adhérer à l'association").'</a>';
				$h .= '</p>';
			$h .= '</div>';
			if($new) {
				$h .= '<div class="game-intro-disclaimer">';
					$h .= '<h2>'.s("Pour commencer").'</h2>';
					$h .= '<p>'.s("Choisissez une parcelle sur votre terrain et implantez une première culture !").'</p>';
				$h .= '</div>';
			}
		$h .= '</div>';
		
		return $h;

	}

	public static function getCrops(): string {

		$h = '<div class="game-intro">';
			$h .= '<h3>'.s("Tableau des cultures").'</h3>';
			$h .= '<table class="tr-bordered">';
				$h .= '<tbody>';
				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

}
?>
