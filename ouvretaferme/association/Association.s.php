<?php
namespace association;

class AssociationSetting extends \Settings {

	const URL = 'https://asso.ouvretaferme.org';

	const FARM = 1608;

	const MEMBERSHIP_FEE = 100;

	// On peut adhérer pour l'année prochaine à partir du 1er octobre si on a déjà adhéré pour cette année
	const CAN_JOIN_FOR_NEXT_YEAR_FROM = '10-01';

}

?>
