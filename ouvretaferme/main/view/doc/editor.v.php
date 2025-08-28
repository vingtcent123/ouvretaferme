<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Utiliser l'éditeur de texte");
	$t->subTitle = s("L'éditeur de texte est présent un peu partout sur {siteName} ! Il vous permet de formater vos textes, et selon les cas d'insérer des images ou des vidéos.");

	$t->menuSelected = 'editor';

	echo '<div class="util-block">';

		echo '<h2>'.s("Styliser le texte dans l'éditeur").'</h2>';

		echo '<p>'.s("Vous pouvez appliquer différents styles lorsque vous sélectionnez du texte :").'</p>';

		echo Asset::image('main', 'doc/editor-text.png');

		echo '<ul>';
			echo '<li>'.s("En <b>gras</b>").'</li>';
			echo '<li>'.s("En <b>italique</b>").'</li>';
			echo '<li>'.s("En <u>souligné</u>").'</li>';
			echo '<li>'.s("Avec un <link>souligné</link>", ['link' => '<a href="'.Lime::getUrl().'">']).'</li>';
			echo '<li>'.s("Une couleur de votre choix").'</li>';
		echo '</ul>';

		echo '<p>'.s("Vous pouvez également choisir l'alignement du texte parmi à gauche, à droite, au centre ou justifié.").'</p>';
		echo '<p>'.s("Enfin, l'icône <b>T</b> vous permet de modifier la taille du texte sélectionné.<br/>En cliquant plusieurs fois sur <b>T</b>, vous pouvez choisir parmi différentes tailles de texte.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Insérer des listes dans l'éditeur").'</h2>';

		echo '<p>'.s("Vous pouvez pouvez intégrer des listes à puces ou à numéro.").'</p>';

		echo Asset::image('main', 'doc/editor-list.png');

		echo '<ul>';
			echo '<li>'.s("Pour démarrer une liste à puces, écrivez sur une ligne vide un tiret (-) puis un espace ( ) :").'<br/><div class="doc-example">'.s("- [Votre texte]").'</pre></li>';
			echo '<li>'.s("Pour démarrer une liste à numéro, écrivez sur une ligne vide le chiffre un (1) suivi d'un point (.) :").'<br/><div class="doc-example">'.s("1. [Votre texte]").'</pre></li>';
		echo '</ul>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Intégrer du contenu dynamique dans l'éditeur").'</h2>';

		echo '<p>'.s("En vous plaçant sur une ligne vide de l'éditeur, vous verrez apparaitre un {icon} qui vous permet d'ajouter du contenu dynamique. <b>Attention, ce {icon} n'est pas proposé partout !</b> Si vous ne le voyez pas, c'est que le contenu dynamique n'est pas disponible sur la page sur laquelle vous vous trouvez.", ['icon' => Asset::icon('plus-circle')]).'</p>';

		echo Asset::image('main', 'doc/editor-dynamic.png');

		echo '<h3>'.s("Quelles sont les fonctions disponibles ?").'</h3>';

		$data = [
			[
				Asset::icon('list-ul'),
				s("Insérer une liste à puces")
			],
			[
				Asset::icon('list-ol'),
				s("Insérer une liste à numéros")
			],
			[
				Asset::icon('image'),
				s("Insérer une image")
			],
			[
				Asset::icon('camera-video-fill'),
				s("Insérer une video Youtube, Dailymotion ou Vimeo")
			],
			[
				Asset::icon('grid-fill'),
				s("Insérer une grille avec un nombre lignes et de colonnes de votre choix")
			],
			[
				Asset::icon('chat-quote-fill'),
				s("Insérer une citation")
			],
			[
				Asset::icon('dash'),
				s("Insérer une ligne horizontale")
			]
		];

		echo '<table>';
			echo '<thead>';
				echo '<tr>';
					echo '<th>'.s("Icône").'</th>';
					echo '<th>'.s("Fonction").'</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				foreach($data as [$icon, $description]) {
					echo '<tr>';
						echo '<td>'.$icon.'</td>';
						echo '<td>'.$description.'</td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';

		echo '<p>'.Asset::icon('exclamation-circle').' '.s("Toutes les fonctions ne sont pas disponibles partout. Si vous n'en voyez pas certaines, c'est qu'elles ne sont pas disponible sur la quelle sur laquelle vous vous trouvez !").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Les raccourcis au clavier").'</h2>';

		$data = [
			[
				'Ctrl + B',
				s("Mettre le texte sélectionné en gras"),
				'<b>'.s("Texte en gras").'</b>'
			],
			[
				'Ctrl + I',
				s("Mettre le texte sélectionné en italique"),
				'<i>'.s("Texte en italique").'</i>'
			],
			[
				'Ctrl + U',
				s("Mettre le texte sélectionné en souligné"),
				'<u>'.s("Texte en souligné").'</u>'
			],
			[
				'Ctrl + K',
				s("Ajouter un lien sur le texte sélectionné"),
				'<a href="'.Lime::getUrl().'">'.s("Suivre le lien").'</a>'
			],
			[
				'Maj + Entrée',
				s("Passer à la ligne sans laisser d'espace entre les paragraphes"),
				s("Ligne 1").'<br/>'.s("Ligne 2")
			],
		];

		echo '<table>';
			echo '<thead>';
				echo '<tr>';
					echo '<th>'.s("Raccourci").'</th>';
					echo '<th>'.s("Description").'</th>';
					echo '<th>'.s("Exemple").'</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				foreach($data as [$shortcut, $description, $example]) {
					echo '<tr>';
						echo '<td><pre>'.$shortcut.'</pre></td>';
						echo '<td>'.$description.'</td>';
						echo '<td><div class="doc-example">'.$example.'</div></td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
