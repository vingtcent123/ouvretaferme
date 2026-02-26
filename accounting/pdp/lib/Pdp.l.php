<?php
namespace pdp;

class PdpLib {

	/**
	 * Permettra d'activer la PA sur quelques fermes sélectionnées en production.
	 */
	public static function isActive(\farm\Farm $eFarm): bool {

		if($eFarm->notEmpty() and $eFarm->isBE()) {
			return FALSE;
		}

		return (LIME_ENV === 'dev');

	}

}
