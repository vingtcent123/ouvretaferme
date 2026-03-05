<?php
namespace receipts;

class ReceiptsSetting extends \Settings {

	const CLASSES = ['53', '511'];
	const DRAFT_LIMIT = 100;
	const DELETE_LIMIT = 10;

	const AMOUNT_THRESHOLD = 76;

	const SOURCE_ACCOUNTS = [Line::BANK_MANUAL, Line::BUY_MANUAL, Line::SELL_MANUAL, Line::OTHER];
	const SOURCE_PRIVATE_ACCOUNTS = [Line::PRIVATE];

}
?>
