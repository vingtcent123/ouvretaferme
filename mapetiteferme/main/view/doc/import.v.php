<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->title = s("{siteName} - Formations");
	$t->metaDescription = s("Formez-vous à l'utilisation de {siteName} !");
	$t->template = 'doc';

	Asset::css('main', 'font-itim.css');
	Asset::css('main', 'doc.css');

	$t->title = s("Importer un plan de culture");
	$t->subTitle = s("Vous pouvez importer un plan de culture au format CSV sur votre ferme. C'est une fonctionnalité bien pratique si vous préférez concevoir votre plan de culture avec un tableur et le visualiser ensuite sur {siteName} !");

	echo '<div class="util-block">';

		echo '<h2>'.s("Comment importer un plan de culture ?").'</h2>';
		echo '<p>'.s("Importer un plan de culture revient à importer sur {siteName} un fichier CSV qui contient des listes de séries que vous souhaitez ajouter à votre plan de culture. Deux formats sont utilisables pour importer des séries :").'</p>';
		echo '<ul>';
			echo '<li>'.s("le format <b>{siteName}</b>").'</li>';
			echo '<li>'.s("le format <b>Brinjel</b>, qui permet d'importer vos séries depuis ce logiciel ou depuis Qrop").'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer un fichier CSV au format {siteName}").'</h2>';
		echo '<p>';
			echo s("Le fichier CSV que vous importez doit comporter une ligne par culture, et les colonnes de ce fichier doivent correspondre à la liste des données à fournir décrite plus bas. Si vous ne respectez pas ce format, vous obtiendrez un résultat qui ne sera pas satisfaisant.");
		echo '</p>';
		echo '<ul>';
			echo '<li>'.s("La première ligne du fichier CSV correspond aux en-têtes qui doivent être recopiées sans modification").'</li>';
			echo '<li>'.s("Le séparateur des colonnes dans le fichier est la virgule (,)").'</li>';
			echo '<li>'.s("Le séparateur des nombres décimaux est le point (.) et non la virgule (,)").'</li>';
		echo '</ul>';
		echo '<p>';
			echo '<a href="'.Asset::getPath('series', 'plan.csv').'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger un exemple CSV").'</a>';
		echo '</p>';
		echo '<br/>';
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
				s("Le nom de l'espèce doit correspondre à <link>une espèce existante de votre ferme</link>, seules les espèces annuelles sont acceptées.", ['link' => $data->eCompany->empty() ? NULL : '<a href="'.\plant\PlantUi::urlManage($data->eCompany).'">']),
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
				s("Pris en compte uniquement dans le cas d'implantation par plant autoproduit"),
				3
			],
			[
				s("Plateau de semis"),
				'young_plants_tray',
				s("Pris en compte uniquement dans le cas d'implantation par plant autoproduit et le plateau de semis doit avoir été préalablement créé dans la <link>liste du matériel de votre ferme<link>", ['link' => $data->eCompany->empty() ? NULL : '<a href="/company/tool:manage?company='.$data->eCompany['id'].'">']),
				s("Plaque de 150")
			],
			[
				s("Date de semis"),
				'sowing_date',
				s("Pris en compte uniquement dans le cas d'implantation par semis direct et des plant autoproduit (format AAAA-MM-JJ)"),
				'2024-01-25'
			],
			[
				s("Date de plantation"),
				'planting_date',
				s("Pris en compte uniquement dans le cas d'implantation par plant autoproduit ou acheté (format AAAA-MM-JJ)"),
				'2024-03-05'
			],
			[
				s("Début de récolte"),
				'first_harvest_date',
				s("Format AAAA-MM-JJ"),
				'2024-05-15'
			],
			[
				s("Fin de récolte"),
				'last_harvest_date',
				s("Format AAAA-MM-JJ"),
				'2024-06-15'
			],
			[
				s("Objectif de surface en m²"),
				'block_area',
				s("Pris en compte uniquement dans le cas de culture sur surface libre"),
				'100'
			],
			[
				s("Densité d'implantation par m²"),
				'block_density',
				s("Pris en compte uniquement dans le cas de culture sur surface libre, ne pas être utilisé simultanément avec <pre>block_spacing_rows</pre> et <pre>block_spacing_plants</pre>"),
				'500'
			],
			[
				s("Espacement entre les rangs en cm"),
				'block_spacing_rows',
				s("Pris en compte uniquement dans le cas de culture sur surface libre, ne pas être utilisé simultanément avec <pre>block_density</pre>"),
				'40'
			],
			[
				s("Espacement sur le rang en cm"),
				'block_spacing_plants',
				s("Pris en compte uniquement dans le cas de culture sur surface libre, ne pas être utilisé simultanément avec <pre>block_density</pre>"),
				'15'
			],
			[
				s("Objectif de longueur de planches en mL"),
				'bed_area',
				s("Pris en compte uniquement dans le cas de culture sur planches"),
				'100'
			],
			[
				s("Densité d'implantation par m²"),
				'bed_density',
				s("Pris en compte uniquement dans le cas de culture sur planches, ne pas être utilisé simultanément avec <pre>bed_rows</pre> et <pre>bed_spacing_plants</pre>"),
				'500'
			],
			[
				s("Nombre de rangs par planche"),
				'bed_rows',
				s("Pris en compte uniquement dans le cas de culture sur planches, ne pas être utilisé simultanément avec <pre>bed_density</pre>"),
				'3'
			],
			[
				s("Espacement sur le rang en cm"),
				'bed_spacing_plants',
				s("Pris en compte uniquement dans le cas de culture sur planches, ne pas être utilisé simultanément avec <pre>bed_density</pre>"),
				'15'
			],
			[
				s("Série clôturée"),
				'finished',
				s("<example>TRUE</example> si la série est clôturée, <example>FALSE</example> sinon", ['example' => '<div class="doc-example">']),
				'FALSE'
			],
			[
				s("Unité de récolte"),
				'harvest_unit',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} → au kg", '<div class="doc-example">'.\series\Cultivation::KG.'</div>').'</li>'.
					'<li>'.s("{value} → à la pièce", '<div class="doc-example">'.\series\Cultivation::UNIT.'</div>').'</li>'.
					'<li>'.s("{value} → à la botte", '<div class="doc-example">'.\series\Cultivation::BUNCH.'</div>').'</li>'.
				'</ul>',
				'bunch'
			],
			[
				s("Objectif de rendement par m²"),
				'yield_expected_area',
				s("Le rendement attendu pour cette culture en fonction de l'unité de récolte choisie"),
				'3.5'
			],
		];

		echo '<table class="table-block">';
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

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer un fichier CSV depuis Qrop / Brinjel").'</h2>';

		echo '<p>'.s("Pour récupérer le fichier CSV de Brinjel à importer sur {siteName} :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Allez dans <b>Paramètres</b>").'</li>';
			echo '<li>'.s("Dans la section <b>Données de la ferme</b>, téléchargez le <b>Plan de culture seul</b>").'</li>';
		echo '</ul>';
		echo '<p>'.s("Pour importer vos données depuis Qrop, vous devez d'abord importer vos données de Qrop vers Brinjel, puis ensuite utiliser le mode opératoire ci-dessus pour importer vos données de Brinjel vers {siteName}.").'</p>';
		echo '<p>'.s("Notez que vous aurez probablement des corrections à faire dans le fichier CSV issu de Brinjel, notamment au niveau des unités de récolte ou des espèces. {siteName} vous fera un rapport des modifications à effectuer après chargement de votre fichier.").'</p>';
		echo '<p>';
			echo '<a href="https://app.brinjel.com/" class="btn btn-outline-secondary" target="_blank">'.s("Aller sur Brinjel").'</a> ';
		echo '</p>';

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
