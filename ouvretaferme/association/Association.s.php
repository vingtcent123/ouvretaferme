<?php
namespace association;

class AssociationSetting extends \Settings {

	const URL = 'https://asso.ouvretaferme.org';

	const FARM = 1608;

	public static function getFee(\farm\Farm $eFarm): int {

		return match($eFarm['quality']) {

			\farm\Farm::NO => AssociationSetting::MEMBERSHIP_FEE_FULL,
			default => AssociationSetting::MEMBERSHIP_FEE_DISCOUNT

		};

	}

	const MEMBERSHIP_FEE_DISCOUNT = 100;
	const MEMBERSHIP_FEE_FULL = 300;

	const MEMBERSHIP_TRY = 6;

	// On peut adhérer pour l'année prochaine à partir du 1er octobre si on a déjà adhéré pour cette année
	const CAN_JOIN_FOR_NEXT_YEAR_FROM = '10-01';

}

?>
