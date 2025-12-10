<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting';

	$t->title = s("Prendre en main le module de comptabilité");
	$t->subTitle = s("Utiliser OTF pour tenir sa comptabilité simplement");

	echo '<div class="util-block">';

	echo '<h5 style="text-transform: uppercase">'.s("Introduction à la comptabilité sur Ouvretaferme").'</h5>';
	echo '<h2>'.s("Utiliser Ouvretaferme pour préparer (puis tenir) sa comptabilité").'</h2>';
	echo '<p>'.s("Sur Ouvretaferme, nous avons fait le choix de vous permettre de préparer vos données de vente à votre comptabilité sans être obligé·e·s d'utiliser le module de comptabilité. Cela signifie que : ").'</p>';
	echo '<ul>';
		echo '<li>'.s("Vous paramétrez le minimum nécessaire de votre comptabilité (journaux, comptes de clients, comptes de produits de vos ventes)").'</li>';
		echo '<li>'.s("Vous indiquez pour chaque produit à quel compte le rattacher").'</li>';
		echo '<li>'.s("Vous clôturez vos ventes (moyen de paiement, date de livraison)").'</li>';
	echo '</ul>';
	echo Asset::icon('arrow-up-right', ['style' => 'margin-bottom: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous téléchargez un fichier au format FEC et vous l'importez dans votre logiciel de comptabilité habituel");
	echo '<br />';
	echo Asset::icon('arrow-down-right', ['style' => 'margin-top: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous importez vos ventes dans le module de comptabilité d'Ouvretaferme");
	echo '<p class="mt-1">'.s("...C'est <b>vous qui choisissez</b> le niveau d'utilisation de la comptabilité proposé par Ouvretaferme !").'</p>';

	echo '</div>';


	echo '<br/>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Préparer mes données").'</h2>';
		echo '<h3>'.s("Les comptes").'</h3>';

		echo '<p>';
			echo s("Vous pouvez personnaliser des comptes dans les <link>paramètres du module de Comptabilité</link>. En associant vos catégories de produits ou vos produits à ces comptes, vous exporterez pour chaque vente des lignes affectées aux bons comptes.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">'
			]);
		echo '</p>';

		echo '<h3>'.s("Les comptes des clients (411)").'</h3>';

		echo '<p>';
			echo s("Vous pouvez personnaliser des comptes pour vos différents clients dans les <link>paramètres du module de Comptabilité</link>. En reliant un client avec un tiers et un numéro de compte spécifique, toutes vos ventes sont rattachées au bon numéro de compte de tiers.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">'
			]);
		echo '</p>';

		echo '<p>';
			echo '<i>'.s("Note : cette fonctionnalité n'est pas accessible si votre exercice comptable en cours est une comptabilité à la trésorerie.").'</i>';
		echo '</p>';

		echo '<h3>'.s("Les journaux").'</h3>';

		echo '<p>';
			echo s("Les journaux peuvent être automatiquement attribués à chaque ligne de vente. Vous pouvez personnaliser vos journaux dans les <link>paramètres du module de Comptabilité</link>.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'/journalCode">'
			]);
		echo '</p>';
		echo '<p>';
			echo s("Ensuite, associez vos journaux à vos comptes dans les <link>paramètres des comptes du module de Comptabilité</link>.", ['link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">']);
		echo '</p>';

		echo '<h3>'.s("Les données sont-elles obligatoires ?").'</h3>';

		echo '<p>';
			echo s("Si vous n'utilisez pas le module de comptabilité d'Ouvretaferme, il faut avoir réalisé les actions suivantes pour que l'export puisse être réalisé : ");
			echo '<ul>';
				echo '<li>'.s("Marchés, ventes et factures sont clôturés.").'</li>';
			echo '</ul>';
		echo '</p>';

		echo '<p>';
			echo s("Si vous préparez vos données pour l'import dans le module de comptabilité d'Ouvretaferme, il faut avoir réalisé les actions suivantes pour que l'import puisse être réalisé : ");
			echo '<ul>';
				echo '<li>'.s("Tous les articles vendus ont bien un compte associé (via leur produit, via la catégorie du produit ou directement sur l'article)").'</li>';
				echo '<li>'.s("La date de livraison est indiquée").'</li>';
				echo '<li>'.s("Un moyen de paiement a été renseigné").'</li>';
				echo '<li>'.s("Et enfin, marchés, ventes et factures sont clôturés.").'</li>';
			echo '</ul>';
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Que contient l'export précomptable ?").'</h2>';
		echo '<p>';
			echo s("Vous retrouverez dans cet export :");
			echo '<ul>';
				echo '<li>'.s("Tous vos marchés clôturés (1 ligne par compte et moyen de paiement pour chaque marché)").'</li>';
				echo '<li>'.s("Toutes vos factures clôturées (1 ligne par compte pour chaque facture)").'</li>';
				echo '<li>'.s("Toutes vos ventes clôturées non issues d'un marché et non incluses dans des factures (1 ligne par compte pour chaque vente)").'</li>';
			echo '</ul>';
		echo '</p>';
		echo '<div class="util-info-outline">';
			echo Asset::icon('exclamation-triangle').' '.s("La complétude de l'export est soumise à la complétude de la phase préparatoire. Plus vos données seront remplies, plus votre import sera complet et facile à importer par la suite.");
		echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Télécharger l'export précomptable").'</h2>';
		echo '<p>';
			echo s("Le fichier CSV que vous téléchargerez répond aux normes codifiées à <link>l'article 1.47 A-1 du livre des procédures fiscales {icon}</link> (FEC).", ['icon' => Asset::icon('box-arrow-up-right'), 'link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775">']);
		echo '</p>';
		echo '<ul>';
			echo '<li>'.s("La première ligne du fichier CSV correspond aux en-têtes").'</li>';
			echo '<li>'.s("Le séparateur des colonnes dans le fichier est la virgule (,)").'</li>';
			echo '<li>'.s("Le séparateur des nombres décimaux est le point (.) et non la virgule (,)").'</li>';
		echo '</ul>';
		echo '<br/>';
		echo '<h3>'.s("Format des données du fichier").'</h3>';

		$data = [
			[
				'JournalCode',
				s("Code journal"),
				s("Code sur 2 à 4 caractères du journal"),
				'VEN'
			],
			[
				'JournalLib',
				s("Libellé du journal"),
				s("Nom du journal"),
				s("Ventes")
			],
			[
				'EcritureNum',
				s("Numéro de l'écriture"),
				s("Numéro de l'écriture comptable. Il doit être séquentiel et renseigné par le logiciel comptable, et ne pas comporter de \"trou\" au niveau de la numérotation."),
				123
			],
			[
				'EcritureDate',
				s("Date de l'écriture"),
				s("Date de l'écriture comptable. Par défaut c'est la date de livraison enregistrée de la vente, au format <pre>AAAAMMJJ</pre>."),
				date('Ymd')
			],
			[
				'CompteNum',
				s("Numéro de compte"),
				s("Numéro de la classe de compte correspondant à la ligne sur 8 caractères."),
				'70120000'
			],
			[
				'CompteLib',
				s("Libellé de la classe de compte"),
				s("Le nom au sens du Plan Comptable de la classe de compte"),
				s("Vente de produits végétaux")
			],
			[
				'CompAuxNum',
				s("Numéro de compte auxiliaire"),
				s("Numéro de la classe de compte du tiers concerné par cette vente."),
				'41100001'
			],
			[
				'CompAuxLib',
				s("Libellé de compte auxiliaire"),
				s("Le nom du compte auxiliaire"),
				'Client XYZ'
			],
			[
				'PieceRef',
				s("Référence de pièce justificative"),
				s("Généralement le numéro de facture lié à la ligne de vente"),
				s("FA-123")
			],
			[
				'PieceDate',
				s("Date de la pièce justificative"),
				s("Soit la date de facture si elle existe, sinon la date de livraison de la vente."),
				date('Ymd')
			],
			[
				'EcritureLib',
				s("Libellé de l'écriture"),
				s("Libellé de l'écriture comptable"),
				''
			],
			[
				'Debit',
				s("Montant au débit"),
				s("Le montant renseigné pour une vente"),
				'10.50'
			],
			[
				'Credit',
				s("Montant au crédit (utilisé dans les avoirs)"),
				s("Le montant renseigné pour un avoir"),
				'10.50'
			],
			[
				'EcritureLet',
				s("Numéro de lettrage"),
				s("Ne sera jamais renseigné"),
				''
			],
			[
				'DateLet',
				s("Date du lettrage"),
				s("Ne sera jamais renseignée"),
				''
			],
			[
				'ValidDate',
				s("Date du validation de l'écriture"),
				s("Ne sera jamais renseignée"),
				''
			],
			[
				'MontantDevise',
				s("Montant de la vente en devise (€)"),
				s("Montant de la vente ou de l'avoir, peut être positif ou négatif."),
				'-5.90'
			],
			[
				'IDevise',
				s("Devise (€)"),
				s("Sera toujours égal à EUR."),
				'EUR'
			],
			[
				'DateRglt',
				s("Date de règlement"),
				s("La date de livraison sera la date utilisée."),
				date('Ymd')
			],
			[
				'ModeRglt',
				s("Mode de règlement"),
				s("Le mode de règlement enregistré pour la vente"),
				s('Espèces')
			],
			[
				'NatOp',
				s("Nature de l'opération"),
				s("Le mode de règlement enregistré pour la vente"),
				s('Espèces')
			],
		];

		echo '<table>';
			echo '<thead>';
				echo '<tr>';
					echo '<th>'.s("Nom de l'entête").'</th>';
					echo '<th>'.s("Type de donnée").'</th>';
					echo '<th>'.s("Description").'</th>';
					echo '<th>'.s("Exemple").'</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				foreach($data as [$column, $title, $description, $example]) {
					echo '<tr>';
						echo '<td><pre>'.$column.'</pre></td>';
						echo '<td>'.$title.'</td>';
						echo '<td style="max-width: 25rem">'.$description.'</td>';
						echo '<td>'.($example ? '<div class="doc-example">'.$example.'</div>' : '').'</td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';

		echo \util\FormUi::asteriskInfo(NULL);

	echo '</div>';


	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('import', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:import';

	$t->title = s("Importer et rapprocher les ventes dans la comptabilité d'Ouvretaferme");
	$t->subTitle = s("Créer les écritures comptables et rapprocher les paiements");

	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir préparé les données de vente</link>", ['link' => '<a href="/doc/accounting">']).' '.Asset::icon('arrow-left-short').'</h4>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer les ventes").'</h2>';
		echo '<p>'.s("Tous les marchés, les factures et les ventes dont les données sont préparées sont affichés dans la page d'import. Vous pouvez :").'</p>';
		echo '<ul style="list-style-type: none; padding-left: 0.75rem;">';
		echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("Les intégrer en comptabilité").'</li>';
		echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("Les ignorer <span>(attention, ils ne vous seront alors plus proposés à l'import)</span>", ['span' => '<span class="color-muted" style="font-style: italic">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("En les intégrant dans votre comptabilité, les écritures suivantes sont automatiquement créées :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Classe {productAccount} pour tous vos comptes de produits (autant d'écritures que de comptes différents)", ['productAccount' => '<b>'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("Classe {vatAccount} pour la TVA (autant d'écritures que de comptes et taux de TVA différents). <br /><span>Note : il n'y a pas de ligne d'écriture de TVA si vous avez indiqué ne pas être redevable de la TVA dans les paramètres de votre exercice comptable. Les écritures citées juste au-dessus seront intégrées TTC.</span>", ['vatAccount' => '<b>'.\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT.'</b>', 'span' => '<span class="color-muted" style="font-style: italic">']).'</li>';
			echo '<li>'.s("Classe {clientAccount} pour la contrepartie liée au client (tiers). <br /><span>Note : uniquement si vous êtes en comptabilité d'engagement (globalement ou uniquement pour les ventes)</span>", ['clientAccount' => '<b>'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'</b>', 'span' => '<span class="color-muted" style="font-style: italic">']).'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Rapprocher écritures & opérations bancaires").'</h2>';
		echo '<p>'.s("Pré-requis : Avoir réalisé un import du fichier <i>.ofx</i> de votre compte bancaire, et avoir importé vos marchés, factures et ventes en comptabilité.").'</p>';
		echo '<p>'.s("Ouvretaferme vous proposera automatiquement les opérations bancaires qui seront les plus proches des imports du module de vente.<br />Les critères de décision sont : ").'</p>';
		echo '<ul>';
			echo '<li>'.s("La corrélation entre le tiers détecté dans l'opération bancaire, et le client").'</li>';
			echo '<li>'.s("Le montant").'</li>';
			echo '<li>'.s("La présence de la référence de facture dans la description du paiement").'</li>';
			echo '<li>'.s("L'adéquation entre la date d'opération bancaire et la date de la vente").'</li>';
		echo '</ul>';
		echo '<p>'.s("Pour chaque suggestion, vous avez ensuite le choix :").'</p>';
		echo '<ul style="list-style-type: none; padding-left: 0.75rem;">';
			echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("de l'accepter").'</li>';
			echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("de la refuser<br /><span>Note : dans ce dernier cas, cette association ne vous sera plus proposée et si une autre opération est éligible, elle vous sera présentée à son tour.</span>", ['span' => '<span class="color-muted" style="font-style: italic">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("Pour chaque suggestion acceptée, les actions suivantes sont réalisées :").'</p>';
		echo '<ul>';
			echo '<li>'.s("saisie de l'écriture du compte de banque {bankAccount} du montant total de la vente", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("l'opération bancaire est notée \"traitée\"").'</li>';
			echo '<li>'.s("saisie de l'écriture du compte de tiers {clientAccount} du montant total de la vente", ['clientAccount' => '<b>'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("lettrage du tiers").'</li>';
			echo '<li>'.s("la vente ou la facture est marquée comme payée dans le module de Commercialisation").'</li>';
		echo '</ul>';
		echo '<p class="color-muted" style="font-style: italic">'.s("Note : Les opérations liées aux tiers ne sont réalisées que dans le cas d'une comptabilité à l'engagement (globalement ou uniquement pour les ventes)").'</p>';
	echo '</div>';

	echo '<br /><br />';

});

?>
