<?php
namespace journal;

class JournalSetting extends \Settings {

	const JOURNAL_CODE_BANK = 'BAN'; // Journal de banque
	const JOURNAL_CODE_OD = 'OD'; // Journal d'opérations diverses
	const JOURNAL_CODE_INV = 'INV'; // Journal d'inventaire

	const HASH_LETTER_RETAINED = 'n'; // hash des écritures d'à nouveau
	const HASH_LETTER_ASSETS = 'i'; // hash des écritures d'inventaire (immos)
	const HASH_LETTER_DEFERRAL = 'd'; // hash des écritures d'inventaire (PCA et CCA)

	const HASH_LETTER_PAYMENT = 'p'; // hash des écritures de paiement
	const HASH_LETTER_IMPORT_INVOICE = 'f'; // Hash des écritures importées depuis le module de vente (factures)
	const HASH_LETTER_IMPORT_SALE = 'v'; // Hash des écritures importées depuis le module de vente (ventes)
	const HASH_LETTER_IMPORT_MARKET = 'm'; // Hash des écritures importées depuis le module de vente (marché)
	const HASH_LETTER_RECONCILIATE = 'r'; // Hash des réconciliations
}

?>
