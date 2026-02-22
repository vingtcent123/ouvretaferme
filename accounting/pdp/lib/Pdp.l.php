<?php
namespace pdp;

class PdpLib {

	/**
	 * Permettra d'activer la PA sur quelques fermes sélectionnées en production.
	 */
	public static function isActive(\farm\Farm $eFarm) {

		return (LIME_ENV === 'dev');

	}

}
