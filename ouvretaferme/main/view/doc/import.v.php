<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->title = s("{siteName} - Formations");
	$t->metaDescription = s("Formez-vous à l'utilisation de {siteName} !");
	$t->template = 'doc';

	Asset::css('main', 'font-itim.css');
	Asset::css('main', 'doc.css');

	$t->title = s("Importer un plan de culture");

	echo '<div class="util-block">';

		echo '<h2>'.s("XX").'</h2>';
		echo '<p>';
			echo s("XXX.");
		echo '</p>';
		echo '<ul>';
			echo '<li>'.s("XXX").'</li>';
			echo '<li>'.s("XXX").'</li>';
		echo '</ul>';
		echo '<b>'.s("XXX").'</b>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Format du fichier CSV").'</h2>';
		echo '<p>';
			echo s("Le fichier CSV que vous importez doit comporter une ligne par culture, et les colonnes de ce fichier doivent correspondre à la liste des données à fournir décrite plus bas. Si vous ne respectez pas ce format, vous obtiendrez un résultat qui ne sera pas satisfaisant.");
		echo '</p>';
		echo '<ul>';
			echo '<li>'.s("La première ligne du fichier CSV correspond aux en-têtes qui doivent être recopiées sans modification").'</li>';
			echo '<li>'.s("Le séparateur des colonnes dans le fichier est la virgule (,)").'</li>';
			echo '<li>'.s("Le séparateur des nombres décimaux est le point (.) et non la virgule (,)").'</li>';
		echo '</ul>';
		echo '<h3>'.s("Liste des données à fournir").'</h3>';

		$data = [
			[
				s("Saison").' '.\util\FormUi::asterisk(),
				'season',
				s("La saison à laquelle intégrer cette culture"),
				2024
			],
			[
				s("Numéro de série"),
				'series_id',
				s("Les cultures avec un numéro de série identique seront rassemblées dans la même série et traitées comme des associations de culture"),
				123
			],
			[
				s("Nom de la série").' '.\util\FormUi::asterisk(),
				'series_name',
				s("Le nom de la série pour cette culture"),
				s("Carotte primeur")
			],
			[
				s("Mode de culture").' '.\util\FormUi::asterisk(),
				'place',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} → plein-champ", '<div class="doc-example">'.\series\Series::OPEN_FIELD.'</div>').'</li>'.
					'<li>'.s("{value} → sous abri", '<div class="doc-example">'.\series\Series::GREENHOUSE.'</div>').'</li>'.
					'<li>'.s("{value} → mixte", '<div class="doc-example">'.\series\Series::MIX.'</div>').'</li>'.
				'</ul>',
				'open-field'
			],
			[
				s("Utilisation du sol").' '.\util\FormUi::asterisk(),
				'use',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} → Culture sur planches", '<div class="doc-example">'.\series\Series::BED.'</div>').'</li>'.
					'<li>'.s("{value} → Culture sur surface libre", '<div class="doc-example">'.\series\Series::BLOCK.'</div>').'</li>'.
				'</ul>',
				'bed'
			],
			[
				s("Espèce").' '.\util\FormUi::asterisk(),
				'species',
				s("Le nom de l'espèce doit correspondre à une espèce existante de votre ferme"),
				s("Carotte")
			],
			[
				s("Implantation").' '.\util\FormUi::asterisk(),
				'planting_type',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} → semis direct", '<div class="doc-example">'.\series\Cultivation::SOWING.'</div>').'</li>'.
					'<li>'.s("{value} → plant autoproduit", '<div class="doc-example">'.\series\Cultivation::YOUNG_PLANT.'</div>').'</li>'.
					'<li>'.s("{value} → plant acheté", '<div class="doc-example">'.\series\Cultivation::YOUNG_PLANT_BOUGHT.'</div>').'</li>'.
				'</ul>',
				'young-plant'
			],
			[
				s("Nombre de graines par plant"),
				'young_plants_seeds',
				s("Pris en compte uniquement dans le cas de plants autoproduits"),
				3
			],
			[
				s("Plateau de semis"),
				'young_plants_tray',
				s("Pris en compte uniquement dans le cas de plants autoproduits et le plateau de semis doit avoir été préalablement créé dans la liste de votre matériel"),
				s("Plaque de 150")
			]
		];
/*		} else if(count(array_intersect($header, ['series_name', 'season', 'place', 'species', 'use', 'planting_type', 'harvest_unit'])) === 7) {
*/
		echo '<table class="tr-bordered">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>'.s("Type de donnée").'</th>';
					echo '<th>'.s("Nom de l'entête").'</th>';
					echo '<th>'.s("Description").'</th>';
					echo '<th>'.s("Exemple").'</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				foreach($data as [$title, $column, $description, $example]) {
					echo '<tr>';
						echo '<td>'.$title.'</td>';
						echo '<td><pre>'.$column.'</pre></td>';
						echo '<td style="max-width: 25rem">'.$description.'</td>';
						echo '<td><div class="doc-example">'.$example.'</div></td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';

		echo \util\FormUi::asteriskInfo(NULL);

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
