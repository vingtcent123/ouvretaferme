<?php
namespace journal;

class JournalSetting extends \Settings {

	const JOURNAL_CODE_BANK = 'BAN';
	const JOURNAL_CODE_OD = 'OD';

	const HASH_LETTER_RETAINED = 'n'; // hash des écritures d'à nouveau
	const HASH_LETTER_ASSETS = 'i'; // hash des écritures d'inventaire (immos)

}

?>
