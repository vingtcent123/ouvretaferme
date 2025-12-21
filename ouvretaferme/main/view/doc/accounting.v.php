<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting';

	$t->title = s("Prendre en main le module de comptabilité");
	$t->subTitle = s("Utiliser Ouvretaferme pour tenir sa comptabilité simplement");

	echo '<div class="util-block">';

	echo '<h5 style="text-transform: uppercase">'.s("Introduction à la comptabilité sur Ouvretaferme").'</h5>';
	echo '<h2>'.s("Utiliser Ouvretaferme pour préparer (puis tenir) sa comptabilité").'</h2>';
	echo '<p>'.s("Sur Ouvretaferme, vous pouvez préparer vos données de vente à votre comptabilité sans être obligé·e·s d'utiliser le module de comptabilité. Cela signifie que : ").'</p>';
	echo '<ul>';
		echo '<li>'.s("Vous paramétrez le minimum nécessaire de votre comptabilité (journaux, comptes de clients, comptes de produits de vos ventes)").'</li>';
		echo '<li>'.s("Vous indiquez pour chaque produit à quel compte le rattacher").'</li>';
		echo '<li>'.s("Vous indiquez le moyen de paiement et clôturez vos ventes").'</li>';
	echo '</ul>';
	echo '<p>'.s("Au final, ").'</p>';
	echo Asset::icon('arrow-up-right', ['style' => 'margin-bottom: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous téléchargez un fichier au format FEC puis l'importez dans votre logiciel de comptabilité habituel");
	echo '<br />';
	echo Asset::icon('arrow-down-right', ['style' => 'margin-top: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous importez vos ventes dans le module de comptabilité d'Ouvretaferme");
	echo '<p class="mt-1">'.s("... C'est <b>vous qui choisissez</b> le niveau d'utilisation de la comptabilité proposé par Ouvretaferme !").'</p>';

	echo '</div>';


	echo '<br/>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Préparer mes données").'</h2>';
		echo '<h3>'.s("Les comptes").'</h3>';

		echo '<p>';
			echo s("Vous pouvez personnaliser des comptes dans les <link>paramètres du module de Comptabilité</link>.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">'
			]);
		echo '</p>';
		echo '<p>';
			echo s("Vous pouvez également paramétrer des numéros de compte par catégorie de produit dans la page \"Vendre > Paramétrage > Les réglages de base > Comptabilité\". Ainsi, toutes les articles vendus seront immédiatement configurés correctement.");
		echo '</p>';

		if(FEATURE_ACCOUNTING_ACCRUAL) {

			echo '<h3>'.s("Les comptes des clients (411)").'</h3>';

			echo '<p>';
				echo s("Vous pouvez personnaliser des comptes pour vos différents clients dans les <link>paramètres du module de comptabilité</link>. En reliant un client avec un tiers et un numéro de compte spécifique, toutes vos ventes sont rattachées au bon numéro de compte de tiers.", [
					'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">'
				]);
			echo '</p>';

		}

		echo '<h3>'.s("Les journaux").'</h3>';

		echo '<p>';
			echo s("Les journaux peuvent être automatiquement attribués à chaque ligne de vente. Vous pouvez personnaliser vos journaux dans les <link>paramètres du module de comptabilité</link>.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'/journalCode">'
			]);
		echo '</p>';
		echo '<p>';
			echo s("Ensuite, associez un journal à chaque compte de vente (classe {productAccount}) dans les <link>paramètres des comptes du module de comptabilité</link>.", ['productAccount' => \account\AccountSetting::PRODUCT_ACCOUNT_CLASS, 'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">']);
		echo '</p>';

		echo '<h3>'.s("Les données sont-elles obligatoires ?").'</h3>';

		echo '<p>';
			echo s("Si vous n'utilisez pas le module de comptabilité d'Ouvretaferme, il faut avoir réalisé les actions suivantes pour que l'export puisse être réalisé : ");
			echo '<ul>';
				echo '<li>'.s("Marchés, ventes et factures sont clôturés.").'</li>';
			echo '</ul>';
		echo '</p>';
		echo '<p>';
			echo s("Si des données venaient à manquer, la valeur sera vide.");
		echo '</p>';

		echo '<p>';
			echo s("Si vous préparez vos données pour l'import dans le module de comptabilité d'Ouvretaferme, il faut avoir réalisé les actions suivantes pour que l'import puisse être réalisé : ");
			echo '<ul>';
				echo '<li>'.s("Tous les articles vendus ont bien un compte associé (via leur produit, via la catégorie du produit ou directement sur l'article)").'</li>';
				echo '<li>'.s("Un moyen de paiement est renseigné").'</li>';
				echo '<li>'.s("Et enfin : marchés, ventes et factures sont clôturés.").'</li>';
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
			echo Asset::icon('exclamation-triangle').' '.s("La complétude de l'export est soumise à la complétude de la phase préparatoire. Plus vos données seront remplies, plus votre import sera complet et facile à utiliser par la suite.");
		echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Télécharger l'export précomptable").'</h2>';
		echo '<p>';
			echo s("Le fichier CSV téléchargé répond aux normes codifiées à <link>l'article 1.47 A-1 du livre des procédures fiscales {icon}</link> (FEC).", ['icon' => Asset::icon('box-arrow-up-right'), 'link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775">']);
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
				s("Numéro de compte correspondant à la ligne sur 8 caractères."),
				'70120000'
			],
			[
				'CompteLib',
				s("Libellé du compte"),
				s("Le nom au sens du Plan Comptable du compte"),
				s("Vente de produits végétaux")
			],
		];
		if(FEATURE_ACCOUNTING_ACCRUAL) {
			$data = array_merge($data, [[
				'CompAuxNum',
				s("Numéro de compte auxiliaire"),
				s("Numéro du compte du tiers concerné par cette vente."),
				'41100001'
			],
				[
					'CompAuxLib',
					s("Libellé de compte auxiliaire"),
					s("Le nom du compte auxiliaire"),
					'Client XYZ'
				],
			]);
		} else {
			$data = array_merge($data, [[
				'CompAuxNum',
				s("Numéro de compte auxiliaire"),
				s("Ne sera jamais renseigné."),
				''
			],
				[
					'CompAuxLib',
					s("Libellé de compte auxiliaire"),
					s("Ne sera jamais renseigné."),
					''
				],
			]);
		}
		$data = array_merge($data, [[
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
		]);

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

	$t->title = s("Importer les ventes dans la comptabilité d'Ouvretaferme");
	$t->subTitle = s("Créer les écritures comptables en un clic");

	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir préparé les données de vente</link>", ['link' => '<a href="/doc/accounting">']).'</h4>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer les ventes").'</h2>';
		echo '<p>'.s("Tous les marchés, les factures et les ventes dont les données sont préparées sont affichés dans la page d'import (dans le menu Précomptabilité). Vous pouvez :").'</p>';
		echo '<ul class="doc-list-icons">';
		echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("Les intégrer en comptabilité").'</li>';
		echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("Les ignorer <span>(attention, ils ne vous seront alors plus proposés à l'import)</span>", ['span' => '<span class="doc-annotation">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("En les intégrant dans votre comptabilité, les écritures suivantes sont automatiquement créées :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Classe {productAccount} pour tous vos comptes de produits (autant d'écritures que de comptes différents)", ['productAccount' => '<b>'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("Classe {vatAccount} pour la TVA (autant d'écritures que de numéros de comptes et taux de TVA différents). <br /><span>Note : il n'y a pas de ligne d'écriture de TVA si vous avez indiqué ne pas être redevable de la TVA dans les paramètres de votre exercice comptable. Les écritures citées juste au-dessus seront intégrées TTC.</span>", ['vatAccount' => '<b>'.\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT.'</b>', 'span' => '<span class="doc-annotation">']).'</li>';

			if(FEATURE_ACCOUNTING_ACCRUAL) {
				echo '<li>'.s("Classe {clientAccount} pour la contrepartie liée au client (tiers). <br /><span>Note : uniquement si vous êtes en comptabilité d'engagement (globalement ou uniquement pour les ventes)</span>", ['clientAccount' => '<b>'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'</b>', 'span' => '<span class="doc-annotation">']).'</li>';
			}
			echo '<li>'.Asset::icon('magic').' '.s("Bonus ! Classe {bankAccount} pour le montant total TTC si vous avez <link>préalablement rapproché l'opération bancaire avec la vente ou la facture</link>.", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>', 'link' => '<a href="/doc/accounting:bank#reconciliate-sales">']).'</li>';
		echo '</ul>';

	echo '</div>';

	echo '<br /><br />';

});

new AdaptativeView('bank', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:bank';

	$t->title = s("Manipuler les opérations bancaires");
	$t->subTitle = s("Gérer les flux bancaires avec Ouvretaferme");

	echo '<div id="bank-import" class="util-block">';

		echo '<h2>'.s("Importer les opérations bancaires").'</h2>';
		echo '<p>'.s("Vous pouvez importer les opérations de votre compte en banque en exportant un fichier <i>.ofx</i> (la plupart des banques proposent ce format).<br />Rendez-vous sur la page des opérations bancaires et cliquez sur le bouton \"Importer un relevé .ofx\" puis sélectionnez votre fichier sur votre ordinateur.").'</p>';
		echo '<p>'.s("Et c'est tout !").'</p>';
		echo '<p>'.s("Sur la page des imports, vous pouvez voir le résumé de l'import, et vous verrez tout ce qui a été importé sur la page des opérations bancaires.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2 id="cashflow-manage">'.s("Traiter les opérations bancaires").'</h2>';
		echo '<p>'.s("Si certaines opérations bancaires ne concernent pas votre exploitation et que vous ne souhaitez pas les voir dans la liste, vous pouvez simplement les <i>Supprimer</i>.<br />Pas de panqiue, si vous voulez les réafficher, utilisez le formulaire de recherche et choisissez d'afficher les opérations supprimées, ce qui vous permettra de les réintégrer si besoin.").'</p>';
		echo '<p>'.s("Pour chaque opération bancaire, il est possible de :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Créer des écritures comptables correspondantes").'</li>';
			echo '<li>'.s("Rattacher des écritures comptables déjà saisies").'</li>';
		echo '</ul>';

		echo '<h3>'.s("Créer ou Rattacher des écritures comptables").'</h3>';
		echo '<p>'.s("Une écriture de contrepartie en compte {bankAccount} sera automatiquement créée et reliée aux écritures comptables concernées.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';
		echo '<p>'.s("Attention, il faut veiller à ce que l'équilibre de l'ensemble de vos écritures comptables soit bien respecté. Ouvretaferme vous indiquera un message si ce n'est pas le cas.").'</p>';
		echo '<p>'.s("Ouvretaferme ne créera jamais d'écriture de contrepartie avec le tiers concerné (compte client {clientAccount} ou compte fournisseur {supplierAccount}), même si le tiers est indiqué.", ['clientAccount' => \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS, 'supplierAccount' => \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS]).'</p>';
	echo '</div>';

		echo '<h2>'.s("Exemples d'usage").'</h2>';

	echo '<div class="util-block">';
		echo '<h4>'.s("Cas n°1 : Un achat apparaît sur mon relevé bancaire et rien n'est indiqué dans mon journal à ce sujet.").'</h4>';
		echo '<p>'.s("En sélectionnant <b>\"Créer des écritures\"</b>, vous pourrez saisir votre achat (en classe {chargeAccount}), éventuellement son montant de TVA, et valider.", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</p>';
		echo '<p>'.s("Ouvretaferme créera les écritures suivantes :").'</p>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('1-circle').' '.s("Compte {bankAccount} (banque) pour l'opération bancaire", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</li>';
			echo '<li>'.Asset::icon('2-circle').' '.s("Compte {chargeAccount} (charge) pour votre achat (HT)", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</li>';
			echo '<li>'.Asset::icon('3-circle').' '.s("Compte {vatAccount} (TVA sur Biens et Services) pour la TVA de votre achat", ['vatAccount' => \account\AccountSetting::VAT_BUY_CLASS_ACCOUNT]).'</li>';
		echo '</ul>';
		echo '<p>'.Asset::icon('arrow-right-short').' '.s("Le montant de l'écriture de banque {icon1} devra être égal au total des écritures {icon2} + {icon3}", ['icon1' => Asset::icon('1-circle'), 'icon2' => Asset::icon('2-circle'), 'icon3' => Asset::icon('3-circle')]).'</p>';
	echo '</div>';

	echo '<div class="util-block">';
		echo '<h4>'.s("Cas n°2 : Mon associé·e a enregistré l'achat d'un petit matériel et l'opération bancaire vient d'apparaître sur le relevé.").'</h4>';
		echo '<p>'.s("Pré-requis : l'achat doit avoir été saisi dans le journal (écritures en classe {chargeAccount} et éventuellement compte {vatAccount}).", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS, 'vatAccount' => \account\AccountSetting::VAT_BUY_CLASS_ACCOUNT]).'</p>';
		echo '<p>'.s("En sélectionnant <b>\"Rattacher des écritures\"</b>, vous pourrez choisir directement l'écriture comptable.", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</p>';
		echo '<p>'.s("Ouvretaferme créera uniquement l'écriture comptable du compte banque {bankAccount}. Les 3 écritures seront liées et équilibrées.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';
		echo '<p><i>'.s("Ce cas peut se présenter si une personne s'occupe de la comptabilité depuis le compte bancaire tandis que toutes les autres saisissent leurs dépenses au fur et à mesure, par exemple.").'</i></p>';

	echo '</div>';


	echo '<br /><br />';
	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir importé les opérations bancaires</link>", ['link' => '<a href="/doc/accounting:bank#bank-import">']).'</h4>';

	echo '<div class="util-block">';
	Asset::css('selling', 'sale.css');

		echo '<h2 id="reconciliate-sales">'.s("Rapprocher les <u>ventes et factures</u> avec les opérations bancaires").'</h2>';

		echo '<p>'.s("Une fois que vous avez importé vos opérations bancaires, {siteName} vous propose une association entre les opérations bancaires et vos ventes (ou factures) qui semblent correspondre. Vous verrez une petite alerte dès qu'un rapprochement sera possible.").'</p>';
		echo '<p>'.s("Les critères de décision sont : ").'</p>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('file-person').' '.s("La corrélation entre le tiers détecté dans l'opération bancaire, et le client lié à la vente ou la facture").'</li>';
			echo '<li>'.Asset::icon('currency-euro').' '.s("Le montant de l'opération bancaire et de la vente ou la facture").'</li>';
			echo '<li>'.Asset::icon('123').' '.s("La présence de la référence de facture dans la description de l'opération bancaire").'</li>';
			echo '<li>'.Asset::icon('calendar-range').' '.s("L'adéquation entre la date d'opération bancaire et la date de la vente ou de la facture").'</li>';
		echo '</ul>';
		echo '<p>'.s("Pour chaque suggestion de rapprochement, vous pouvez modifier le moyen de paiement détecté via le libellé de l'opération bancaire.").'</li>';
		echo '<p>'.s("Puis ensuite vous avez le choix : ").'</li>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("d'accepter le rapprochement suggéré").'</li>';
			echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("de le refuser.<br /><span>Note : dans ce dernier cas, cette association ne vous sera plus proposée et si une autre association est trouvée, elle vous sera présentée à son tour.</span>", ['span' => '<span class="doc-annotation">']).'</li>';
		echo '</ul>';

		echo '<p>'.s("Après avoir accepté une suggestion de rapprochement, la facture ou la vente sera marquée <span>payée</span> avec le moyen de paiement renseigné, dans le module de commercialisation.", ['span' => '<span class="util-badge sale-payment-status sale-payment-status-success">']).'</p>';

	echo '</div>';
	echo '<br /><br />';

});

new AdaptativeView('reconciliate', function($data, DocTemplate $t) {

	Asset::css('selling', 'sale.css');

	$t->template = 'doc';
	$t->menuSelected = 'accounting:reconciliate';

	$t->title = s("Rapprochement bancaire");
	$t->subTitle = s("Gérer les flux bancaires, les ventes et les opérations avec Ouvretaferme");


	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir importé les opérations bancaires</link> et <linkImportSales>importé les ventes, factures, et/ou marchés</linkImportSales>", ['link' => '<a href="/doc/accounting:bank">', 'linkImportSales' => '<a href="/doc/accounting:import">']).'</h4>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Rapprocher les <u>écritures comptables</u> avec les opérations bancaires").'</h2>';

		echo '<p>'.s("Ouvretaferme proposera automatiquement les opérations bancaires détectées comme les plus pertinentes par rapport à vos écritures comptables (menu \"Précomptabilité > Rapprocher les écritures\").<br />Les critères de décision sont les mêmes que dans le rapprochement entre l'opération bancaire et les ventes (cf. ci-dessus).").'</p>';

		echo '<p>'.s("Pour chaque suggestion de rapprochement, vous avez ensuite le choix :").'</p>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("de l'accepter").'</li>';
			echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("de la refuser<br /><span>Note : dans ce dernier cas, cette association ne vous sera plus proposée et si une autre opération est éligible, elle vous sera présentée à son tour.</span>", ['span' => '<span class="doc-annotation">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("Après avoir accepté une suggestion de rapprochement, les actions suivantes sont réalisées :").'</p>';
		echo '<ul>';
			echo '<li>'.s("saisie de l'écriture du compte de banque {bankAccount} du montant total de la vente,", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("saisie de l'écriture du compte de tiers {clientAccount} du montant total de la vente", ['clientAccount' => '<b>'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'</b>']).'<sup>*</sup></li>';
			echo '<li>'.s("lettrage du tiers").'<sup>*</sup></li>';
			echo '<li>'.s("la vente ou la facture est marquée <span>payée</span> dans le module de commercialisation", ['span' => '<span class="util-badge sale-payment-status sale-payment-status-success">']).'</li>';
		echo '</ul>';
		echo '<p class="doc-annotation"><sup>*</sup>'.s("Note : Les opérations liées aux tiers (saisie de l'écriture et lettrage) ne sont réalisées que dans le cas d'une comptabilité à l'engagement (globalement ou uniquement pour les ventes)").'</p>';
	echo '</div>';

	echo '<br /><br />';

});

?>
