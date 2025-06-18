<?php

// Exemple d'utilisation :
// 1. Déplacer le fec dans /tmp/fec.txt
// 2. Lancer le script php framework/lime.php -a mapetiteferme -e dev configure/checkFec
new Page()
	->cli('index', function($data) {

		$colonnes_obligatoires = [
			'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
			'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
			'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit'
		];

		$filepath = '/tmp/fec.txt';
		$handle = fopen($filepath, 'r');
		if (!$handle) {
			dd("Erreur : impossible d'ouvrir le fichier");
		}

		$erreurs = [];
		$ligne_num = 0;
		$total_debit = 0.0;
		$total_credit = 0.0;
		$doublons = [];

		// Lecture en-tête
		$header = fgetcsv($handle, 0, ';');
		$ligne_num++;
		if ($header !== $colonnes_obligatoires) {
			$erreurs[] = "Colonnes incorrectes ou mal ordonnées.";
			$erreurs[] = "Colonnes attendues : " . implode(', ', $colonnes_obligatoires);
			$erreurs[] = "Colonnes trouvées : " . implode(', ', $header);
			fclose($handle);
			dd($erreurs);
		}

		while (($data = fgetcsv($handle, 0, ';')) !== false) {
			$ligne_num++;
			$row = array_combine($header, $data);

			// Vérification date EcritureDate
			$date = DateTime::createFromFormat('Y-m-d', $row['EcritureDate']);
			if (!$date || $date->format('Y-m-d') !== $row['EcritureDate']) {
				$erreurs[] = "Ligne $ligne_num : date EcritureDate invalide ({$row['EcritureDate']}). Format attendu : YYYY-MM-DD.";
			}

			// Vérification date PieceDate (peut être vide)
			if ($row['PieceDate'] !== '') {
				$datePiece = DateTime::createFromFormat('Y-m-d', $row['PieceDate']);
				if (!$datePiece || $datePiece->format('Y-m-d') !== $row['PieceDate']) {
					$erreurs[] = "Ligne $ligne_num : date PieceDate invalide ({$row['PieceDate']}). Format attendu : YYYY-MM-DD.";
				}
			}

			// Vérification débits et crédits
			$debit = str_replace(',', '.', $row['Debit']);
			$credit = str_replace(',', '.', $row['Credit']);
			if (!is_numeric($debit)) {
				$erreurs[] = "Ligne $ligne_num : Debit non numérique ({$row['Debit']}).";
				$debit = 0;
			}
			if (!is_numeric($credit)) {
				$erreurs[] = "Ligne $ligne_num : Credit non numérique ({$row['Credit']}).";
				$credit = 0;
			}

			if ($debit == 0 && $credit == 0) {
				$erreurs[] = "Ligne $ligne_num : Debit et Credit tous deux nuls.";
			}

			$total_debit += (float)$debit;
			$total_credit += (float)$credit;

			// Recherche doublons (JournalCode + EcritureNum + CompteNum + Debit + Credit)
			$doublon_key = $row['JournalCode'] . '|' . $row['EcritureNum'] . '|' . $row['CompteNum'] . '|' . $debit . '|' . $credit;
			if (isset($doublons[$doublon_key])) {
				$erreurs[] = "Ligne $ligne_num : doublon détecté avec la ligne {$doublons[$doublon_key]}.";
			} else {
				$doublons[$doublon_key] = $ligne_num;
			}
		}

		fclose($handle);

		// Vérification équilibre débits/crédits
		if (round($total_debit, 2) !== round($total_credit, 2)) {
			$erreurs[] = "Somme des débits ($total_debit) différente de la somme des crédits ($total_credit).";
		}

		if (empty($erreurs)) {
			dd("Le fichier FEC est conforme aux règles de base.");
		}

		dd($erreurs);

	});
?>
