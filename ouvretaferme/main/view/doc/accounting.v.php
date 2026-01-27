<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting';

	$t->title = s("Prendre en main le logiciel de comptabilité");
	$t->subTitle = s("Utiliser Ouvretaferme pour tenir sa comptabilité simplement");

	echo '<div class="util-block">';

	echo '<h5 style="text-transform: uppercase">'.s("Introduction à la comptabilité sur Ouvretaferme").'</h5>';
	echo '<h2>'.s("Utiliser Ouvretaferme pour préparer (puis tenir) sa comptabilité").'</h2>';
	echo '<p>'.s("Sur Ouvretaferme, vous pouvez préparer les données de vos factures sans être obligé·e·s d'utiliser le logiciel de comptabilité. Cela signifie que : ").'</p>';
	echo '<ul>';
		echo '<li>'.s("Vous paramétrez le minimum nécessaire de votre comptabilité (journaux, comptes de clients, numéros de comptes des produits de vos ventes)").'</li>';
		echo '<li>'.s("Vous indiquez pour chaque produit à quel compte le rattacher").'</li>';
		echo '<li>'.s("Vous indiquez le moyen de paiement").'</li>';
	echo '</ul>';
	echo '<p>'.s("Au final, ").'</p>';
	echo Asset::icon('arrow-up-right', ['style' => 'margin-bottom: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous téléchargez un fichier au format {fec} puis l'importez dans votre logiciel de comptabilité habituel", ['fec' => '<span class="util-badge bg-primary">FEC</span>']);
	echo '<br />';
	echo Asset::icon('arrow-down-right', ['style' => 'margin-top: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Soit vous importez vos factures dans le logiciel comptable de Ouvretaferme");
	echo '<p class="mt-1">'.s("... C'est <b>vous qui choisissez</b> le niveau d'utilisation de la comptabilité proposé par Ouvretaferme !").'</p>';

	echo '</div>';


	echo '<br/>';

	echo '<div class="util-block">';
	echo '<h2>'.s("Tutoriels en vidéo").'</h2>';
	echo '<p>'.s("Des tutoriels vidéos sont également disponibles sur Youtube. Cliquez sur le petit menu en haut à droite de la vidéo pour voir le sommaire des vidéos disponibles.").'</p>';
	echo '<iframe width="100%" src="https://www.youtube.com/embed/videoseries?si=M_vGvQ9gfoS6PwdI&amp;list=PL9PdPD-HgdQO9OLw_Ky5hTdtmGagCLfcE" title="'.s("Tutoriels du module de comptabilité de {siteName}").'" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
	echo '</div>';


	echo '<br/>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Préparer mes données").'</h2>';
		echo '<h3>'.s("Les numéros de compte").'</h3>';

		echo '<p>';
			echo s("Vous pouvez personnaliser des comptes dans les paramètres de comptabilité.");
		echo '</p>';
		echo '<p>';
			echo s("Vous pouvez également paramétrer des numéros de compte par catégorie de produit dans les paramétrages de commercialisation. Ainsi, toutes les articles vendus seront immédiatement configurés correctement.");
		echo '</p>';

		echo '<h3>'.s("Les journaux").'</h3>';

		echo '<p>';
			echo s("Les journaux peuvent être automatiquement attribués à chaque ligne de vente. Vous pouvez personnaliser vos journaux dans les paramètres de comptabilité. Certains journaux sont préconfigurés pour démarrer plus facilement.");
		echo '</p>';
		echo '<p>';
			echo s("Ensuite, associez un journal à chaque compte de vente de la classe {productAccount} dans les paramètres de comptabilité.", ['productAccount' => \account\AccountSetting::PRODUCT_ACCOUNT_CLASS]);
		echo '</p>';

		echo '<h3>'.s("Les données sont-elles obligatoires ?").'</h3>';

		echo '<p>';
			echo s("Si vous n'utilisez pas le logiciel comptable de Ouvretaferme, il faut avoir avoir uniquement configuré tous vos numéros de compte pour pouvoir télécharger un export des ventes. ");
		echo '</p>';
		echo '<p>';
			echo s("Si des données venaient à manquer, la valeur sera vide.");
		echo '</p>';

		echo '<h3>'.s("Et pour un import dans la comptabilité ?").'</h3>';
		echo '<p>';
			echo s("{siteName} propose d'importer uniquement des <b>factures générées</b> dont les articles vendus sont rattachés à des <b>numéros de compte</b> et dont le <b>moyen de paiement</b> est défini.");
		echo '</p>';
		echo '<h3>'.s("Et si des factures n'ont pas pour objectif d'entrer en comptabilité par un import ?").'</h3>';
		echo '<p>';
			echo s("Dans ce cas, il est possible, dans la page d'import, d'ignorer simplement ces factures. Elles ne seront plus proposées, ni pour la préparation, ni pour l'export, ni pour l'import dans la comptablité.");
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Export précomptable").'</h2>';
		echo '<h3>'.s("Où le trouver ?").'</h3>';
		echo '<p>';
			echo s("Vous accéderez à la page de synthèse de l'export précomptable depuis un lien dans la page du menu <b>Précomptabilité</b>, dans la 3<sup>ème</sup> étape de précomptabilité.");
		echo '</p>';

		echo '<h3>'.s("Quelles informations contient-il ?").'</h3>';
		echo '<p>';
			echo s("Vous retrouverez dans cet export toutes vos ventes avec leur numéro, organisé avec 1 ligne par compte et moyen de paiement pour chaque vente. C'est un export qui respecte le format FEC pour pouvoir être intégré dans votre logiciel comptable.");
		echo '</p>';
		echo '<p>';
			echo s("La page d'export des ventes vous permet également de visualiser une synthèse selon vos critères de recherche.");
		echo '</p>';
		echo '<p>';
			echo s("Si vous avez filtré les résultat des ventes à afficher, l'export sera basé dessus.");
		echo '</p>';
		echo '<div class="util-info">';
			echo Asset::icon('exclamation-triangle').' '.s("La complétude de l'export est soumise à la complétude de la phase préparatoire. Plus vos données seront remplies, plus votre import sera complet et facile à utiliser par la suite.");
		echo '</div>';
	echo '</div>';

	echo '<div class="util-block">';
		echo '<h2>'.s("Télécharger l'export précomptable").'</h2>';
		echo '<p>';
			echo s("Le fichier CSV téléchargé répond aux normes codifiées à <link>l'article 1.47 A-1 du livre des procédures fiscales {icon}</link> (FEC).", ['icon' => Asset::icon('box-arrow-up-right'), 'link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775">']);
		echo '</p>';
		echo \main\CsvUi::getSyntaxInfo();
		echo '<br/>';
		echo '<h3>'.s("Format des données du fichier").'</h3>';

		$list = [
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
			$list = array_merge($list, [[
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
			$list = array_merge($list, [[
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
		$list = array_merge($list, [[
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

		echo \main\CsvUi::getDataList($list);

	echo '</div>';


	echo '<br/>';
	echo '<br/>';

});


new AdaptativeView('import', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:import';

	$t->title = s("Importer les factures dans le logiciel de comptabilité de {siteName}");
	$t->subTitle = s("... et créer les écritures comptables en un clic");

	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir préparé les données de vos factures</link>", ['link' => '<a href="/doc/accounting">']).'</h4>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Importer les factures").'</h2>';
		echo '<p>'.s("Pour accéder au récapitulatif de vos factures à importer dans la comptabilité, rendez vous dans la page <b>Précomptabilité</b>, sur le 3<sup>ème</sup> onglet, et cliquez sur le bouton <b>Importer les factures</b>.").'</p>';
		echo '<p>'.s("Seules les factures dont les données sont préparées et <link>qui sont rapprochées avec une opération bancaire</link> sont éligibles à l'import dans la comptabilité.", ['link' => '<a href="/doc/accounting:bank#reconciliate">']).'</p>';
		echo '<p>'.s("Vous pouvez :").'</p>';
		echo '<ul class="doc-list-icons">';
		echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("Les importer").'</li>';
		echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("Les ignorer <span>Note : ces factures ne vous seront alors plus proposées à l'import</span>", ['span' => '<span class="doc-annotation">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("En les important dans la comptabilité, les écritures suivantes sont automatiquement créées :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Classe {productAccount} pour tous vos numéros de comptes de produits ou pour la livraison, avec autant d'écritures que de comptes différents", ['productAccount' => '<b>'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'</b>']).'</li>';
			echo '<li>'.s("Numéro de compte de TVA {vatAccount}, avec autant d'écritures que de numéros de comptes et taux de TVA différents. <br /><span>Note : il n'y a pas de ligne d'écriture de TVA si vous avez indiqué ne pas être redevable de la TVA dans les paramètres de votre exercice comptable. Les écritures citées juste au-dessus seront intégrées TTC.</span>", ['vatAccount' => '<b>'.\account\AccountSetting::VAT_SELL_CLASS_ACCOUNT.'</b>', 'span' => '<span class="doc-annotation">']).'</li>';

			if(FEATURE_ACCOUNTING_ACCRUAL) {
				echo '<li>'.s("Numéro de compte {clientAccount} pour la contrepartie liée au client (tiers). <br /><span>Note : uniquement si vous êtes en comptabilité d'engagement (globalement ou uniquement pour les ventes)</span>", ['clientAccount' => '<b>'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'</b>', 'span' => '<span class="doc-annotation">']).'</li>';
			}
			echo '<li>'.Asset::icon('magic').' '.s("Bonus ! Classe {bankAccount} pour le montant total TTC", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>', 'link' => '<a href="/doc/accounting:bank#reconciliate">']).'</li>';
		echo '</ul>';
		echo '<p>'.s("Le document comptable enregistré sera le numéro de facture.").'</p>';

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
		echo '<p>'.s("Vous pouvez importer les opérations de votre compte en banque en exportant un fichier <i>OFX</i>. Si vous ne savez pas ce c'est, contactez votre banque pour qu'elle vous aide à télécharger ce document.<br />Rendez-vous sur la page des opérations bancaires et cliquez sur le bouton {button} puis sélectionnez votre fichier sur votre ordinateur.", ['button' => '<a class="btn btn-primary btn-readonly btn-xs">'.\Asset::icon('file-earmark-plus').' '.s("Importer un relevé bancaire").'</a>']).'</p>';
		echo '<p>'.s("Et c'est tout !").'</p>';
		echo '<p>'.s("Sur la page des imports, vous pouvez voir le résumé de l'import, et vous verrez tout ce qui a été importé sur la page des opérations bancaires.").'</p>';

		$form = new \util\FormUi();
		echo '<label>';
			echo $form->inputCheckbox('export-ofx-ca', 1, [
				'checked' => FALSE,
				'class' => 'hide doc-hidden-checkbox',
			]);
			echo '<a>'.s("Voir comment exporter un fichier OFX depuis le Crédit Agricole").'</a>';
			echo '<div doc-hidden-checkbox>';
				echo Asset::image('main', 'doc/accounting-ofx-ca-1.png', ['style' => 'max-height: 9rem; margin-bottom: 0 !important']);
				echo '<span class="util-annotation">'.s("Cliquez sur <b>Documents</b>").'</span>';
				echo Asset::image('main', 'doc/accounting-ofx-ca-2.png', ['style' => 'max-height: 15rem; margin-bottom: 0 !important']);
				echo '<span class="util-annotation">'.s("Puis cliquez sur <b>Télécharger l'historique des opérations</b>").'</span>';
				echo Asset::image('main', 'doc/accounting-ofx-ca-3.png', ['style' => 'max-height: 40rem; margin-bottom: 0 !important']);
				echo '<span class="util-annotation">'.s("Choisissez le compte bancaire, le format <b>OFX</b>, la période de transactions, puis validez").'</span>';
			echo '</div>';
		echo '</label>';

	echo '</div>';

	echo '<br />';
	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir importé les opérations bancaires</link>", ['link' => '<a href="/doc/accounting:bank#bank-import">']).'</h4>';

	echo '<div class="util-block">';
	Asset::css('selling', 'sale.css');

		echo '<h2 id="reconciliate">'.s("Rapprocher les factures avec les opérations bancaires").'</h2>';

		echo '<p>'.s("Il est uniquement possible d'<b>importer en comptabilité des factures</b>. Les ventes non rattachées à des factures et les points de vente ne sont pas rapprochables.").'</p>';
		echo '<p>'.s("Une fois que vous avez importé vos opérations bancaires, {siteName} vous propose une association entre les opérations bancaires et vos factures qui semblent correspondre. Vous verrez une petite alerte {icon} dès qu'un rapprochement sera possible.", ['icon' => Asset::icon('fire')]).'</p>';
		echo '<p>'.s("Les critères de décision sont : ").'</p>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('person').' '.s("La corrélation entre le tiers détecté dans l'opération bancaire, et le client lié à la facture").'</li>';
			echo '<li>'.Asset::icon('currency-euro').' '.s("Le montant de l'opération bancaire et de la facture").'</li>';
			echo '<li># '.s("La présence de la référence de facture dans la description de l'opération bancaire").'</li>';
			echo '<li>'.Asset::icon('calendar-range').' '.s("L'adéquation entre la date d'opération bancaire et la date de la facture").'</li>';
			echo '<li>'.Asset::icon('wallet2').' '.s("La correspondance entre le moyen de paiement détecté dans l'opération bancaire et le moyen de paiement indiqué dans la facture").'</li>';
		echo '</ul>';
		echo '<p>'.s("Un score avec un code couleur vous indique le niveau de confiance de {siteName} dans le rapprochement, et les points de vigilance s'il y en a.").'</p>';
		echo '<p>'.s("Pour chaque suggestion de rapprochement, vous pouvez modifier le moyen de paiement détecté via le libellé de l'opération bancaire avec un bouton de validation.").'</li>';
		echo '<p>'.s("Puis ensuite vous avez le choix : ").'</li>';
		echo '<ul class="doc-list-icons">';
			echo '<li>'.Asset::icon('hand-thumbs-up').' '.s("d'accepter le rapprochement suggéré").'</li>';
			echo '<li>'.Asset::icon('hand-thumbs-down').' '.s("de le refuser.<br /><span>Note : dans ce dernier cas, cette association ne vous sera plus proposée et si une autre association est trouvée, elle vous sera présentée à son tour.</span>", ['span' => '<span class="doc-annotation">']).'</li>';
		echo '</ul>';

		echo '<p>'.s("Après avoir accepté une suggestion de rapprochement, la facture ou la vente sera marquée <span>payée</span> avec le moyen de paiement et la date de paiement renseignés et <span2>{icon} rapprochée</span2>, dans le module de commercialisation.", ['span' => '<span class="util-badge sale-payment-status sale-payment-status-success">', 'span2' => '<span class="util-badge bg-accounting">', 'icon' => Asset::icon('piggy-bank')]).'</p>';
		echo '<p>'.s("Vous pouvez ensuite passer à l'étape suivante : <link>Importer vos factures dans la comptabilité de {siteName}</link> en quelques clics !", ['link' => '<a href="/doc/accounting:import">']).'</p>';

	echo '</div>';
	echo '<br />';

	echo '<div class="util-block">';

		echo '<h2 id="cashflow-manage">'.s("Masquer les opérations bancaires").'</h2>';
		echo '<p>'.s("Si certaines opérations bancaires ne concernent pas votre exploitation et que vous ne souhaitez pas les voir dans la liste, vous pouvez simplement les <i>Supprimer</i>.<br />Pas de panique, si vous voulez les réafficher, utilisez le formulaire de recherche, champ de recherche <b>Écritures</b> et choisissez d'afficher les opérations supprimées, ce qui vous permettra de les réintégrer si besoin.").'</p>';

	echo '</div>';
	echo '<br />';

	echo '<h4>'.Asset::icon('arrow-right-short').' '.s("Pré-requis : <link>Avoir créé son premier exercice comptable</link>", ['link' => '<a href="/doc/accounting:start#financial-year">']).'</h4>';

	echo '<div class="util-block">';
		echo '<h2 id="cashflow-manage">'.s("Créer des écritures comptables à partir d'une opération bancaire").'</h2>';
		echo '<p>'.s("Il est possible de faire un lien entre une opération bancaire et une ou plusieurs écritures comptables depuis la page des opérations bancaires. Lors de la création des écritures comptables, l'équilibre sera vérifié pour l'ensemble des écritures comptables qui deviendront un groupe non dissociable.").'</p>';
		echo '<p>'.s("Une écriture de contrepartie en compte {bankAccount} sera automatiquement créée et reliée aux écritures comptables concernées.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';
		echo '<p>'.Asset::icon('exclamation-triangle').' '.s("Attention, il faut veiller à ce que l'équilibre de l'ensemble de vos écritures comptables soit bien respecté. Ouvretaferme vous indiquera un message si ce n'est pas le cas.").'</p>';
		echo '<p>'.s("Ouvretaferme ne créera jamais d'écriture de contrepartie avec le tiers concerné, que ça soit le compte client {clientAccount} ou le compte fournisseur {supplierAccount}, même si le tiers est indiqué.", ['clientAccount' => \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS, 'supplierAccount' => \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS]).'</p>';
		echo '<div class="util-block-optional">';
			echo '<h5>'.s("Exemple d'usage : Un achat apparaît sur mon relevé bancaire et rien n'est indiqué dans mon journal à ce sujet.").'</h5>';
			echo '<p>'.s("En sélectionnant <b>Créer des écritures</b>, vous pourrez saisir votre achat en classe {chargeAccount}, éventuellement son montant de TVA, et valider.", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</p>';
			echo '<p>'.s("Ouvretaferme créera les écritures suivantes :").'</p>';
			echo '<ul class="doc-list-icons">';
				echo '<li>'.Asset::icon('1-circle').' '.s("Compte {bankAccount} - Banques pour l'opération bancaire", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</li>';
				echo '<li>'.Asset::icon('2-circle').' '.s("Compte {chargeAccount} - Charge pour votre achat (HT)", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</li>';
				echo '<li>'.Asset::icon('3-circle').' '.s("Compte {vatAccount} - TVA sur Biens et Services pour la TVA de votre achat", ['vatAccount' => \account\AccountSetting::VAT_BUY_CLASS_ACCOUNT]).'</li>';
			echo '</ul>';
			echo '<p>'.Asset::icon('arrow-right-short').' '.s("Le montant de l'écriture de banque {icon1} devra être égal au total des écritures {icon2} + {icon3}", ['icon1' => Asset::icon('1-circle'), 'icon2' => Asset::icon('2-circle'), 'icon3' => Asset::icon('3-circle')]).'</p>';
		echo '</div>';
	echo '</div>';
	echo '<br />';

	echo '<div class="util-block">';
		echo '<h2 id="cashflow-manage">'.s("Rattacher des écritures comptables à une opération bancaire").'</h2>';

		echo '<p>'.s("Si vous avez déjà créé vos écritures comptables, en sélectionnant <b>Rattacher des écritures</b>, il sera possible de sélectionner les écritures de contrepartie à l'écriture qui sera créée en compte de banque {bankAccount}.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';
		echo '<p>'.s("Note : {siteName} vous préviendra si les montants ne sont pas équilibrés et le groupe d'écritures nouvellement créé formera un groupe non dissociable.").'</p>';

		echo '<div class="util-block-optional">';
			echo '<h5>'.s("Exemple d'usage : Mon associé·e a enregistré l'achat d'un petit matériel et l'opération bancaire vient d'apparaître sur le relevé.").'</h5>';
			echo '<p>'.s("Pré-requis : l'achat doit avoir été saisi dans le journal via une écriture en classe {chargeAccount} et éventuellement la TVA en compte {vatAccount}.", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS, 'vatAccount' => \account\AccountSetting::VAT_BUY_CLASS_ACCOUNT]).'</p>';
			echo '<p>'.s("En sélectionnant <b>Rattacher des écritures</b>, vous pourrez choisir directement l'écriture comptable.", ['chargeAccount' => \account\AccountSetting::CHARGE_ACCOUNT_CLASS]).'</p>';
			echo '<p>'.s("Ouvretaferme créera uniquement l'écriture comptable du compte banque {bankAccount}. Les 3 écritures seront liées et équilibrées.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';
			echo '<p><i>'.s("Ce cas peut se présenter si une personne s'occupe de la comptabilité depuis le compte bancaire tandis que toutes les autres saisissent leurs dépenses au fur et à mesure, par exemple.").'</i></p>';
		echo '</div>';
	echo '</div>';
	echo '<br />';

	echo '<div class="util-block">';
		echo '<h2 id="cashflow-manage">'.s("Supprimer les écritures comptables d'une opération bancaire").'</h2>';

		echo '<p>'.s("La suppression des écritures comptables entraînera la suppression de <b>toutes</b> les écritures comptables, y compris l'écriture de banque.").'</p>';
		echo '<p>'.s("Vous pourrez ensuite à nouveau créer des écritures comptables ou rattacher l'opération bancaire.").'</p>';

	echo '</div>';
	echo '<br />';

	echo '<div class="util-block">';
		echo '<h2 id="cashflow-manage">'.s("Dissocier sans supprimer les écritures comptables d'une opération bancaire").'</h2>';

		echo '<p>'.s("En dissociant les écritures comptables de leur opération bancaire, l'opération bancaire deviendra à nouveau disponible pour de nouvelles écritures comptables, et le groupe d'écritures comptables deviendra un groupe séparé.").'</p>';
		echo '<p>'.Asset::icon('exclamation-triangle').' '.s("Attention ! L'écriture en compte de banque {bankAccount} sera supprimée car elle n'aura plus de raison d'exister.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]).'</p>';

	echo '</div>';

	echo '<br /><br />';

});

new AdaptativeView('start', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:start';

	$t->title = s("Démarrer la comptabilité");
	$t->subTitle = s("Paramétrer sa comptabilité et démarrer à son rythme !");

	echo '<div class="util-block" id="financial-year">';

		echo '<h2>'.s("Créer le premier exercice comptable").'</h2>';

		echo '<p>'.s("L'exercice comptable est la base de la tenue d'une comptabilité.").'</p>';

		echo '<p>'.s("Il s'agit de paraméter au mieux les choix effectués pour la ferme afin que le logiciel s'adapte à vos besoins.").'</p>';
		echo '<p>'.s("Pour le moment, seules les fermes remplissant les critères suivants peuvent utiliser le logiciel comptable de {siteName} :").'</p>';
		echo '<ul>';
			echo '<li>'.s("fermes au micro-BA,").'</li>';
			echo '<li>'.s("à la comptabilité de trésorerie,").'</li>';
			echo '<li>'.s("redevables ou non de la TVA.").'</li>';
		echo '</ul>';
	echo '</div>';

	echo '<br />';
	echo '<div class="util-block" id="financial-year">';

		echo '<h2>'.s("Importer un fichier FEC").'</h2>';

		echo '<p>'.s("Si vous souhaitez importer vos anciens exercices comptables, c'est tout à fait possible.").'</p>';

		echo '<p>'.s("Pour ce faire, créez votre exercice puis, dans la liste des exercices comptables et en regard de l'exercice à importer, cliquez sur <b>Importer un fichier FEC</b>.").'</p>';
		echo '<p>'.s("Vos données seront importées dans votre comptabilité !").'</p>';

	echo '</div>';

	echo '<br />';
	echo '<div class="util-block" id="financial-year">';

		echo '<h2>'.s("Et ensuite ?").'</h2>';

		echo '<p>'.s("Vous pouvez <link>Importer vos factures rapprochées</link>, <linkCreate>créer vos écritures comptables à partir de vos opérations bancaires</linkCreate>, ou encore créer directement vos écritures comptables !", ['link' => '<a href="/doc/accounting:import">', 'linkCreate' => '<a href="/doc/accounting:bank#cashflow-manage">']).'</p>';

	echo '</div>';

	echo '<br />';
	echo '<br /><br />';

});

new AdaptativeView('asset', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:asset';

	$t->title = s("Importer les immobilisations");
	$t->subTitle = '';

	echo '<div class="util-block" id="import">';

		echo '<h2>'.s("Comment importer mes immobilisations ?").'</h2>';

		echo '<p>'.s("Si vous avez déjà une comptabilité antérieure, il est très probable que vous ayez fait des acquisitions et que vous souhaitiez les reprendre en même temps que votre comptabilité sur {siteName}.").'</p>';

		echo '<p>'.s("Vous pouvez importer un fichier au format CSV avec toutes vos immobilisations.").'</p>';

	echo '</div>';

	echo '<br />';
	echo '<div class="util-block" id="financial-year">';


		echo '<h2>'.s("Importer un fichier CSV").'</h2>';

		echo '<p>'.s("Le fichier CSV que vous importez doit comporter une ligne par immobilisation, et les colonnes de ce fichier doivent correspondre à la liste des données à fournir décrite plus bas. Si vous ne respectez pas ce format, {siteName} refusera d'importer vos données.").'</p>';
		echo '<ul>';
      echo '<li>'.s("La première ligne du fichier CSV correspond aux en-têtes qui doivent être recopiées sans modification").'</li>';
      echo '<li>'.s("Le séparateur des colonnes dans le fichier est la virgule (,)").'</li>';
		echo '</ul>';

		echo '<p>';
			echo '<a href="'.Asset::getPath('asset', 'immobilisations.csv').'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger un exemple CSV").'</a>';
		echo '</p>';
		echo '<br/>';
		echo '<h3>'.s("Liste des données à fournir").'</h3>';

		$data = [
			[
				s("Numéro de compte").' '.\util\FormUi::asterisk(),
				'account',
				s("Le numéro de compte auquel correspond votre immobilisation"),
				2154
			],
			[
				s("Libellé").' '.\util\FormUi::asterisk(),
				'description',
				s("Le nom de votre immobilisation"),
				s("Irrigation")
			],
			[
				s("Valeur d'acquisition").' '.\util\FormUi::asterisk(),
				'value',
				s("Le prix de l'acquisition de votre immobilisation, sur lequel sont basés les calculs d'amortissement"),
				1244.35
			],
			[
				s("Mode d'amortissement économique").' '.\util\FormUi::asterisk(),
				'economic_mode',
				s("Les valeurs possibles :").
				'<ul>'.
					'<li>'.s("{value} ou {value2} → Sans amortissement", ['value' => '<div class="doc-example">'.s("S").'</div>', 'value2' => '<div class="doc-example">'.s("Sans").'</div>']).'</li>'.
					'<li>'.s("{value} ou {value2} → Linéaire", ['value' => '<div class="doc-example">'.s("L").'</div>', 'value2' => '<div class="doc-example">'.s("LIN").'</div>']).'</li>'.
					'<li>'.s("{value} ou {value2} → Dégressif", ['value' => '<div class="doc-example">'.s("D").'</div>', 'value2' => '<div class="doc-example">'.s("DEG").'</div>']).'</li>'.
				'</ul>',
				'L'
			],
			[
				s("Durée d'amortissement économique").' '.\util\FormUi::asterisk(),
				'economic_duration',
				s("La durée de l'amortissement économique, exprimée en mois"),
				120
			],
			[
				s("Montant déjà amorti").' '.\util\FormUi::asterisk(),
				'economic_amortization',
				s("Ce qui a déjà été amorti jusqu'à la réintégration dans {siteName}"),
				1000
			],
			[
				s("Mode d'amortissement fiscal"),
				'fiscal_mode',
				s("Les valeurs possibles sont les mêmes que pour le mode d'amortissement économique. Si cette donnée n'est pas indiquée, c'est le mode d'amortissement économique qui sera utilisé"),
				'L'
			],
			[
				s("Durée d'amortissement fiscal"),
				'fiscal_duration',
				s("La durée de l'amortissement fiscal, exprimée en mois. Si cette donnée n'est pas indiquée, c'est la durée de l'amortissement économique qui sera utilisée."),
				120
			],
			[
				s("Date d'acquisition").' '.\util\FormUi::asterisk(),
				'acquisition_date',
				s("Date d'acquisition de l'immobilisation"),
				'2025-04-03'
			],
			[
				s("Date de mise en service"),
				'start_date',
				s("Date de mise en service. Si cette donnée n'est pas indiquée, c'est la date d'acquisition qui sera utilisée.<br />{value} Attention aux acquisitions au 31/12 avec mise en service le 01/01 de l'année suivante : la date de mise en service est importante pour ne démarrer l'amortissement que l'année de la mise en service.", Asset::icon('exclamation-triangle')),
				'2025-04-03'
			],
			[
				s("Valeur résiduelle"),
				'residual_value',
				s("La valeur résiduelle du bien, à 0 si l'information n'est pas indiquée."),
				90
			],
		];

		echo \main\CsvUi::getDataList($data);

	echo '</div>';

	echo '<br />';
	echo '<br /><br />';

});

new AdaptativeView('vat', function($data, DocTemplate $t) {

	$t->template = 'doc';
	$t->menuSelected = 'accounting:vat';

	$t->title = s("La TVA sur {siteName}");
	$t->subTitle = '';

	echo '<div class="util-block">';

		echo '<h2>'.s("Manipuler les codes de TVA").'</h2>';

		echo '<p>'.s("Sur {siteName} il y a des codes de TVA à indiquer lors de la saisie d'une écriture et qui seront utilisés lors de la déclaration de TVA :").'</p>';

		echo '<table class="td-vertical-align-top">';
			echo '<tr>';
				echo '<th colspan="2">'.s("Code").'</th>';
				echo '<th>'.s("Explication").'</th>';
				echo '<th>'.s("Exemples").'</th>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>'.s("STD").'</td>';
				echo '<td>'.s("<b>Standard</b>").'</td>';
				echo '<td>'.s("Opération imposable à la TVA au taux indiqué").'</td>';
				echo '<td>'.s("Achat de graines, frais de livraison, vente directe").'</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>'.s("EXO").'</td>';
				echo '<td>'.s("<b>Exonéré</b>").'</td>';
				echo '<td>'.s("Opération qui est dans le champ d'application de la TVA, mais qui n'est pas soumise à la Taxe sur la Valeur Ajoutée en raison d'une disposition de la loi. En revanche, l'opération entre dans le Chiffre d'Affaires").'</td>';
				echo '<td>'.s("Exportations, livraisons intracommunautaires").'</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>'.s("NCA").'</td>';
				echo '<td>'.s("<b>Hors Chiffre d'Affaires</b>").'</td>';
				echo '<td>'.s("Opération qui n'est pas du Chiffre d'Affaires à déclarer.").'</td>';
				echo '<td>'.s("Écritures de régularisation, autoconsommation au régime du micro-BA").'</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>'.s("NS").'</td>';
				echo '<td>'.s("<b>Non soumis</b>").'</td>';
				echo '<td>'.s("Opération qui est dans le champ d'application de la TVA, mais qui n'est pas taxable, et qui entre dans le Chiffre d'Affaires.").'</td>';
				echo '<td>'.s("Vente alors qu'on est en franchise de TVA").'</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>'.s("HC").'</td>';
				echo '<td>'.s("<b>Hors-champ</b>").'</td>';
				echo '<td>'.s("Opération qui n'entre pas dans le champ d'application de la TVA et ne sera pas considérée comme du Chiffre d'Affaires.").'</td>';
				echo '<td>'.s("Indemnité d'assurance").'</td>';
			echo '</tr>';
		echo '</table>';

	echo '<br />';
	echo '<br /><br />';

});

?>
