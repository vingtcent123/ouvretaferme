<?php
namespace account;

class PartnerSetting extends \Settings {

	const DROPBOX = 'dropbox';
	const SUPER_PDP = 'super-pdp';

	public static $PARTNERS = [self::DROPBOX, self::SUPER_PDP];

}
?>
