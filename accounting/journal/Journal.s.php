<?php
namespace journal;

class JournalSetting extends \Settings {

	const JOURNAL_CODE_BANK = 'BAN'; // Journal de banque
	const JOURNAL_CODE_OD = 'OD'; // Journal d'opérations diverses
	const JOURNAL_CODE_INV = 'INV'; // Journal d'inventaire

	const HASH_LETTER_RETAINED = 'n'; // hash des écritures d'à nouveau
	const HASH_LETTER_ASSETS = 'i'; // hash des écritures d'inventaire (immos)
	const HASH_LETTER_DEFERRAL = 'd'; // hash des écritures d'inventaire (PCA et CCA)

	const HASH_LETTER_IMPORT_INVOICE = 'f'; // Hash des écritures importées depuis le module de vente (factures)
	const HASH_LETTER_WRITE = 'w'; // Hash de la création d'écriture (base)
	const HASH_LETTER_CASHFLOW = 'c'; // Hash de la création d'écriture (depuis un cashflow)
	const HASH_LETTER_FEC_IMPORT = 'e'; // Hash d'un import FEC
}

?>
