<?php
new AdaptativeView('index', function($data, DocTemplate $t) {

	$t->template = 'doc';

	$t->title = s("Préparer les données de vente pour ma comptabilité");
	$t->subTitle = s("Vous pouvez préparer vos données de vente pour un import plus facile en comptabilité !");

	$t->menuSelected = 'accounting';

	echo '<div class="util-block">';

		echo '<h2>'.s("Comment configurer toutes les données ?").'</h2>';
		echo '<p>'.s("Pour faciliter un import en comptabilité, vous avez besoin de connaître les classes de compte, les journaux, le montant de TVA de toutes vos ventes.").'</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Configurer les classes de compte").'</h2>';
		echo '<p>';
			echo s("Vous pouvez personnaliser des classes de compte directement dans les <link>paramètres du module de Comptabilité</link>. En associant vos catégories de produits ou vos produits à ces classes de compte, vous exporterez pour chaque vente des lignes affectées aux bonnes classes de compte.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">'
			]);
		echo '</p>';

	echo '</div>';

	echo '<div class="util-block">';

		echo '<h2>'.s("Configurer les journaux").'</h2>';
		echo '<p>';
			echo s("Les journaux peuvent être automatiquement attribués à chaque ligne de vente.");
		echo '</p>';
		echo '<p>';
			echo s("Vous pouvez personnaliser vos journaux directement dans les <link>paramètres du module de Comptabilité</link>.", [
				'link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'/journalCode">'
			]);
		echo '</p>';
		echo '<p>';
			echo s("Ensuite, associez vos journaux à vos classes de compte directement dans les <link>paramètres des classe de compte du module de Comptabilité</link>, cette information..", ['link' => $data->eFarm->empty() ? '<span>' : '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/account">']);
		echo '</p>';

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
		echo '<div class="util-warning-outline">'.s("Attention, pour que votre fichier soit valide, vos ventes et factures doivent être <b>clôturées</b> et chaque article doit avoir la <b>classe de compte</b> qui lui correspond.").'</div>';
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
?>
