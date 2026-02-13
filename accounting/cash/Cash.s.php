<?php
namespace cash;

class CashSetting extends \Settings {

	const CLASSES = ['531', '511'];
	const DRAFT_LIMIT = 100;
	const DELETE_LIMIT = 10;

	const AMOUNT_THRESHOLD = 76;

	const SOURCE_ACCOUNTS = [Cash::BANK_MANUAL, Cash::BUY_MANUAL, Cash::SELL_MANUAL, Cash::OTHER];
	const SOURCE_PRIVATE_ACCOUNTS = [Cash::PRIVATE];

}
?>
