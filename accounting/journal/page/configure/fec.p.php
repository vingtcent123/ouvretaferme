<?php
// Exemples d'utilisation :
// pour générer le fec : Lancer le script php framework/lime.php -a ouvretaferme -e dev journal/configure/fec:generate
// pour vérifier le fec :
// 1. Déplacer le fec dans /tmp/fec.txt
// 2. Lancer le script php framework/lime.php -a ouvretaferme -e dev journal/configure/fec:check
new Page()
	->cli('generate', function($data) {

		$eFarm = \farm\Farm::model()
		->select(\farm\Farm::getSelection())
		->whereHasAccounting(TRUE)
		->whereId(7)
		->get();

		\company\CompanyLib::connectDatabase($eFarm);

		$eFinancialYear = \account\FinancialYear::model()->select(\account\FinancialYear::getSelection())->whereStartDate('2024-01-01')->get();

		$operations = \account\FecLib::generate($eFinancialYear);
		$fecData = \account\FecLib::formatFecData($operations);

		file_put_contents(LIME_DIRECTORY.'/'.mb_substr($eFarm['siret'], 0, 9).'FEC20250531.txt', $fecData);

	});

new Page()
	->cli('check', function($data) {

		$cheminFichier = '/tmp/shared/fec.txt';
		$erreurs = [];
		$totalDebit = 0;
		$totalCredit = 0;

		// 1. Vérifier l'encodage UTF-8 sans BOM
		$premiers_octets = file_get_contents($cheminFichier, false, null, 0, 3);
		if ($premiers_octets === "\xEF\xBB\xBF") {
			dd("Le fichier est en UTF-8 avec BOM. Il doit être en UTF-8 sans BOM.");
		}

		$fichier = fopen($cheminFichier, 'r');
		if (!$fichier) dd("Impossible d’ouvrir le fichier.");

		// 2. Vérification de l'en-tête
		$colonnes_attendues = [
			"JournalCode", "JournalLib", "EcritureNum", "EcritureDate", "CompteNum", "CompteLib",
			"CompAuxNum", "CompAuxLib", "PieceRef", "PieceDate", "EcritureLib",
			"Debit", "Credit", "EcritureLet", "DateLet",
			"ValidDate", "MontantDevise", "IDevise",
			"DateRglt", "ModeRglt", "NatOp", // Compta de tréso
		];
		$nbChamps = count($colonnes_attendues);
		$ligne = fgets($fichier);
		if (!$ligne) dd("Le fichier est vide.");

		$entetes = array_map('trim', explode('|', trim($ligne)));
		if ($entetes !== $colonnes_attendues) {
			$erreurs[] = "Les colonnes du FEC ne sont pas conformes à l’ordre ou au nom attendu.";
			$erreurs[] = "Colonnes trouvées : " . implode(', ', $entetes);
		}

		$nLigne = 1;
		while (($ligne = fgets($fichier)) !== false) {
			$nLigne++;
			$champs = explode('|', trim($ligne));

			if (count($champs) !== $nbChamps) {
				$erreurs[] = "Ligne $nLigne : doit contenir exactement 18 champs, trouvé " . count($champs);
				continue;
			}

			// Vérification des dates (AAAAMMJJ)
			$dates = [3, 9, 14, 15, 18]; // index des champs date
			foreach ($dates as $i) {
				$champ = $champs[$i];
				if ($champ !== '' and !preg_match('/^\d{8}$/', $champ)) {
					$erreurs[] = "Ligne $nLigne : date invalide dans " . $entetes[$i] . " : '$champ'";
				} elseif ($champ !== '') {
					$y = substr($champ, 0, 4);
					$m = substr($champ, 4, 2);
					$d = substr($champ, 6, 2);
					if (!checkdate((int)$m, (int)$d, (int)$y)) {
						$erreurs[] = "Ligne $nLigne : date non valide dans " . $entetes[$i] . " : '$champ'";
					}
				}
			}

			// Vérification des montants (numériques)
			$montants = [11, 12, 16];
			foreach ($montants as $i) {
				$champ = $champs[$i];
				if ($champ !== '' and !is_numeric($champ)) {
					$erreurs[] = "Ligne $nLigne : champ numérique invalide (" . $entetes[$i] . ") : '$champ'";
				}
			}

			// Totalisation débits/crédits
			$totalDebit += floatval($champs[11] ?? 0);
			$totalCredit += floatval($champs[12] ?? 0);
		}

		fclose($fichier);

		// Vérification équilibre
		if (abs($totalDebit - $totalCredit) > 0.01) {
			$erreurs[] = "Le total des débits ($totalDebit) est différent du total des crédits ($totalCredit)";
		}

		dd($erreurs);

	});
?>
