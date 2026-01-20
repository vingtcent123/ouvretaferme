<?php
new AdaptativeView('series', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Importer un plan de culture");
	$t->subTitle = s("Vous pouvez importer un plan de culture au format CSV sur votre ferme. C'est une fonctionnalité bien pratique si vous préférez concevoir votre plan de culture avec un tableur et le visualiser ensuite sur {siteName} !");

	$t->menuSelected = 'importSeries';

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
	echo \main\CsvUi::getSyntaxInfo();
	echo '<p>';
	echo '<a href="'.Asset::getPath('series', 'series.csv').'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger un exemple CSV").'</a>';
	echo '</p>';
	echo '<br/>';
	echo '<h3>'.s("Liste des données à fournir").'</h3>';

	$list = [
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
			'mode',
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
			s("Le nom de l'espèce doit correspondre à <link>une espèce existante de votre ferme</link>, seules les espèces annuelles sont acceptées.", ['link' => $data->eFarm->empty() ? NULL : '<a href="'.\plant\PlantUi::urlManage($data->eFarm).'">']),
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
			s("Nombre de graines par trou"),
			'seeds_per_hole',
			s("Pris en compte dans le cas d'implantation par semis direct ou plant autoproduit"),
			3
		],
		[
			s("Plateau de semis"),
			'young_plants_tray',
			s("Pris en compte uniquement dans le cas d'implantation par plant autoproduit et le plateau de semis doit avoir été préalablement créé dans la <link>liste du matériel de votre ferme<link>", ['link' => $data->eFarm->empty() ? NULL : '<a href="/farm/tool:manage?farm='.$data->eFarm['id'].'">']),
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
			s("<example>yes</example> si la série est clôturée, <example>no</example> sinon", ['example' => '<div class="doc-example">']),
			'false'
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
		[
			s("Nom de la variété"),
			'variety_name',
			s("Une variété utilisée pour cette culture. Il est possible d'enchainer les colonnes <i>variety_name</i> et <i>variety_part</i> autant de fois que nécessaire dans le fichier CSV à importer."),
			'Andine cornue'
		],
		[
			s("Fréquence de la variété en %"),
			'variety_part',
			s("La part de la variété dans la culture. La somme des variétés doit être égale à 100."),
			'25'
		],
	];

	echo \main\CsvUi::getDataList($list);

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

new AdaptativeView('products', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Importer des produits");
	$t->subTitle = s("Vous pouvez importer des produits au format CSV sur votre ferme. Cette fonctionnalité peut vous être utile si vous utilisiez d'autres logiciels avant {siteName} ou si vous voulez concevoir plus facilement votre gamme de produits sur tableur !");

	$t->menuSelected = 'importProducts';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer des produits au format CSV").'</h2>';
		echo '<p>';
			echo s("Les produits que vous importez au format CSV sont ajoutés à la liste de vos produits existants, à l'exception de ceux que vous tentez d'importer sous une référence déjà existante. Les produits concernés ne seront pas ajoutés une deuxième fois mais seront modifiés avec les nouvelles valeurs, à l'exception de l'unité de vente qui ne peut pas être modifiée par un import.");
		echo '</p>';
		echo '<p>';
			echo '<b>'.s("Si vous réimportez un produit avec une référence identique, toutes les valeurs seront mises à jour y compris celles qui ne sont pas présentes dans votre fichier CSV. Soyez donc vigilant car si vous importez un fichier sans la colonne <i>quality</i> par exemple, tous les produits réimportés avec une référence identique perdront leur signe de qualité.").'</b>';
		echo '</p>';
		echo '<p>';
			echo s("Le fichier CSV que vous importez doit comporter une ligne par produit, et les colonnes de ce fichier doivent correspondre à la liste des données à fournir décrite plus bas.");
		echo '</p>';
		echo \main\CsvUi::getSyntaxInfo();
		echo '<p>';
			echo '<a href="'.Asset::getPath('selling', 'products.csv').'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger un exemple CSV").'</a>';
		echo '</p>';
		echo '<br/>';
		echo '<h3>'.s("Liste des données à fournir").'</h3>';


		$profiles = '';
		foreach(\selling\Product::getProfiles('import') as $profile) {
			$profiles .= '<li><div class="doc-example">'.$profile.'</div> → '.\selling\ProductUi::p('profile')->values[$profile].'</li>';
		}

		$list = [
			[
				s("Type de produit").' '.\util\FormUi::asterisk(),
				'type',
				s("Les valeurs possibles :").
				'<ul>'.
					$profiles.
				'</ul>',
				\selling\Product::UNPROCESSED_PLANT
			],
			[
				s("Nom du produit").' '.\util\FormUi::asterisk(),
				'name',
				s("Le nom du produit"),
				s("Tomate")
			],
			[
				s("Référence du produit"),
				'reference',
				s("Une référence interne pour le produit (uniquement des chiffres, des lettres et des tirets). Une même référence ne peut pas être utilisée pour plusieurs produits."),
				s("TOMCDB")
			],
			[
				s("Unité de vente"),
				'unit',
				s("L'unité de vente doit correspondre à <link>une des unités de vente de votre ferme</link>. Vous devez utiliser le nom de l'unité au singulier.", ['link' => $data->eFarm->empty() ? NULL : '<a href="/selling/unit:manage?farm='.$data->eFarm['id'].'">']),
				'kg'
			],
			[
				s("Prix pour les particuliers"),
				'price_private',
				s("Le prix en € TTC du produit pour les particuliers.<br/>Vous devez renseigner au moins un prix, qu'il soit professionnel ou particulier, pour votre produit."),
				'5'
			],
			[
				s("Prix pour les professionnels"),
				'price_pro',
				s("Le prix en € HT du produit pour les professionnels.<br/>Vous devez renseigner au moins un prix, qu'il soit professionnel ou particulier, pour votre produit."),
				'3'
			],
			[
				s("Taux de TVA").' '.\util\FormUi::asterisk(),
				'vat',
				s("Le taux de TVA du produit (peut être laissé à 0 si vous avez indiqué que votre ferme n'est pas redevable de la TVA)"),
				'5,5'
			],
			[
				s("Complément d'information sur le produit"),
				'additional',
				s("Un court texte pour compléter le nom du produit"),
				s("Environ 400 grammes")
			],
			[
				s("Origine du produit"),
				'origin',
				s("L'origine géographique du produit"),
				s("La ferme du voisin (63)")
			],
			[
				s("Signe de qualité"),
				'quality',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} → Agriculture biologique", '<div class="doc-example">'.\selling\Product::ORGANIC.'</div>').'</li>'.
					'<li>'.s("{value} → Nature & Progrès", '<div class="doc-example">'.\selling\Product::NATURE_PROGRES.'</div>').'</li>'.
					'<li>'.s("{value} → En conversion vers l'agriculture biologique", '<div class="doc-example">'.\selling\Product::CONVERSION.'</div>').'</li>'.
				'</ul>',
				\selling\Product::ORGANIC
			]
		];

		echo \main\CsvUi::getDataList($list);

		echo '<br/>';
		echo '<h3>'.s("Autres données que vous pouvez fournir").'</h3>';

		echo '<p class="util-info">'.s("Certaines données qui ne s'appliquent qu'à certains profils de produits peuvent être ajoutées à votre fichier CSV.<br/>Par exemple, la colonne pour définir la variété d'un produit ne sera prise en compte que pour les produits d'origine végétale.").'</p>';


		$apply = function($property) {

			$values = '';
			foreach(\selling\Product::getProfiles($property) as $profile) {
				$values .= ' <div class="doc-example">'.\selling\ProductUi::p('profile')->values[$profile].'</div>';
			}

			return '<div class="doc-condition">'.s("Utilisable pour : {values}",['values' => $values]).'</div>';

		};

		$list = [
			[
				s("Espèce"),
				'species',
				s("Le nom de l'espèce dont fait partie ce produit et qui doit correspondre à <link>une espèce existante de votre ferme</link>.", ['link' => $data->eFarm->empty() ? NULL : '<a href="'.\plant\PlantUi::urlManage($data->eFarm).'">']).$apply('unprocessedPlant'),
				s("Tomate")
			],
			[
				s("Variété"),
				'variety',
				s("Un nom de variété").$apply('unprocessedVariety'),
				s("Coeur de boeuf")
			],
			[
				s("Surgelé"),
				'frozen',
				s("<example>yes</example> si le produit est vendu surgelé, <example>no</example> ou vide sinon", ['example' => '<div class="doc-example">']).$apply('mixedFrozen'),
				'false'
			],
			[
				s("Conditionnement"),
				'packaging',
				s("Le conditionnement du produit").$apply('processedPackaging'),
				s("Pack de 6 bouteilles")
			],
			[
				s("Composition"),
				'composition',
				s("La composition du produit").$apply('processedComposition'),
				s("Purée de noisette (90 %), Sucre de canne (10 %)")
			],
			[
				s("Allergènes"),
				'allergen',
				s("Les allergènes du produits").$apply('processedAllergen'),
				s("Traces de fruits à coques")
			],
		];

		echo \main\CsvUi::getDataList($list);

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('customers', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Importer des clients");
	$t->subTitle = s("Vous pouvez importer des clients au format CSV sur votre ferme. Cette fonctionnalité peut vous être utile si vous utilisiez d'autres logiciels ou un tableur pour gérer votre commercialisation avant {siteName} !");

	$t->menuSelected = 'importProducts';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer des clients au format CSV").'</h2>';
		echo '<p>';
			echo s("Les clients que vous importez au format CSV sont ajoutés à la liste de vos clients existants, à l'exception de ceux que vous tentez d'importer sous une adresse e-mail déjà présente sur votre base. Les clients concernés ne seront pas ajoutés une deuxième fois.");
		echo '</p>';
		echo '<p>';
			echo s("Le fichier CSV que vous importez doit comporter une ligne par client, et les colonnes de ce fichier doivent correspondre à la liste des données à fournir décrite plus bas.");
		echo '</p>';
		echo \main\CsvUi::getSyntaxInfo();
		echo '<p>';
			echo '<a href="'.Asset::getPath('selling', 'customers.csv').'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger un exemple CSV").'</a>';
		echo '</p>';
		echo '<br/>';
		echo '<h3>'.s("Liste des données à fournir").'</h3>';

		$private = '<div class="doc-example">'.\selling\Customer::PRIVATE.'</div>';
		$pro = '<div class="doc-example">'.\selling\Customer::PRO.'</div>';
		$onlyPrivate = '<div class="doc-condition">'.s("Uniquement pour les clients particuliers").' '.$private.'</div>';
		$onlyPro = '<div class="doc-condition">'.s("Uniquement pour les clients professionnels").' '.$pro.'</div>';

		$list = [
			[
				s("Type de client").' '.\util\FormUi::asterisk(),
				'type',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.$private.' → '.\selling\CustomerUi::getCategories()[\selling\Customer::PRIVATE].'</li>'.
					'<li>'.$pro.' → '.\selling\CustomerUi::getCategories()[\selling\Customer::PRO].'</li>'.
				'</ul>',
				\selling\Customer::PRIVATE
			],
			[
				s("Prénom"),
				'private_first_name',
				s("Le prénom du client").$onlyPrivate,
				s("Tomate")
			],
			[
				s("Nom").' '.\util\FormUi::asterisk(),
				'private_last_name',
				s("Le nom de famille du client").$onlyPrivate,
				s("Tomate")
			],
			[
				s("Nom commercial").' '.\util\FormUi::asterisk(),
				'pro_commercial_name',
				s("Le nom commercial du client").$onlyPro,
				s("Biocoop d'ici")
			],
			[
				s("Raison sociale"),
				'pro_legal_name',
				s("La raison sociale du client. Peut-être laissée vide si elle est identique au nom commercial.").$onlyPro,
				s("SAS Biocoop ICI")
			],
			[
				s("E-mail"),
				'email',
				s("L'e-mail principal du client qu'il pourra utiliser pour se connecter à son compte client si vous l'y avez invité"),
				s("toto@exemple.fr")
			],
			[
				s("Inviter ce client à créer un compte client"),
				'invite',
				s("<example>yes</example> pour envoyer un e-mail à l'adresse indiquée dans la colonne <example>email</example> pour inciter votre client à se créer un mot de passe, <example>no</example> ou vide sinon", ['example' => '<div class="doc-example">']),
				'yes'
			],
			[
				s("Numéro de téléphone"),
				'phone',
				s("Le numéro de téléphone du client"),
				'5'
			],
			[
				s("Groupes"),
				'groups',
				s("Les groupes auxquels appartient le client. Indiquez le nom des groupes séparés par des virgules."),
				'AMAP / Petit panier'
			],
			[
				s("Nom du contact"),
				'pro_contact_name',
				s("Le nom de votre contact chez ce client professionnel").$onlyPro,
				'Jacques Durand'
			],
			[
				s("Numéro de SIRET"),
				'pro_siret',
				s("Le numéro de SIRET du professionnel").$onlyPro,
				s("12345678901234")
			],
			[
				s("Numéro de TVA"),
				'pro_vat_number',
				s("Le numéro de TVA intracommunautaire du professionnel").$onlyPro,
				s("FR76123456789")
			],
			s("Adresse de livraison").'<div class="doc-condition">'.s("L'adresse de livraison sera aussi utilisée pour la facturation si vous ne renseignez pas d'adresse de facturation.").'</div>',
			[
				s("Ligne 1"),
				'delivery_street_1',
				s("La première ligne de l'adresse de livraison"),
				s("13 rue des Oiseaux")
			],
			[
				s("Ligne 2"),
				'delivery_street_2',
				s("La deuxième ligne de l'adresse de livraison"),
				s("Lieu dit les Moineauxs")
			],
			[
				s("Code postal"),
				'delivery_postcode',
				s("Le code postal de l'adresse de livraison"),
				'42420'
			],
			[
				s("Ville"),
				'delivery_city',
				s("La ville de l'adresse de livraison"),
				s("Maville")
			],
			[
				s("Pays").' '.\util\FormUi::asterisk(),
				'delivery_country',
				s("Le pays de l'adresse de livraison. Pour connaitre la liste des pays acceptés, reportez-vous au menu déroulant sur la page de modification d'un client. <b>Vous devez au moins fournir un pays pour votre client, que ce soit le pays de livraison ou de facturation.</b>"),
				s("France")
			],
			s("Adresse de facturation").'<div class="doc-condition">'.s("Peut être laissée vide si elle correspond à l'adresse de facturation").'</div>',
			[
				s("Ligne 1"),
				'invoice_street_1',
				s("La première ligne de l'adresse de facturation"),
				s("13 rue des Oiseaux")
			],
			[
				s("Ligne 2"),
				'invoice_street_2',
				s("La deuxième ligne de l'adresse de facturation"),
				s("Lieu dit les Moineauxs")
			],
			[
				s("Code postal"),
				'invoice_postcode',
				s("Le code postal de l'adresse de facturation"),
				'42420'
			],
			[
				s("Ville"),
				'invoice_city',
				s("La ville de l'adresse de facturation"),
				s("Maville")
			],
			[
				s("Pays").' '.\util\FormUi::asterisk(),
				'invoice_country',
				s("Le pays de l'adresse de facturation. Pour connaitre la liste des pays acceptés, reportez-vous au menu déroulant sur la page de modification d'un client. <b>Vous devez au moins fournir un pays pour votre client, que ce soit le pays de livraison ou de facturation.</b>"),
				s("France")
			]
		];

		echo \main\CsvUi::getDataList($list);

	echo '</div>';

	echo '<br/>';
	echo '<br/>';

});
?>
