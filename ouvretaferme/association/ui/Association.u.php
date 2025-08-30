<?php
namespace association;

class AssociationUi {

	public function getProductDonationName(): string {

		return s("Don à l'association Ouvretaferme (merci !)");

	}
	public function getProductName(): string {

		$year = date('Y');

		return s("Adhésion {year} à l'association Ouvretaferme", ['year' => $year]);

	}

	public static function confirmationUrl(\farm\Farm $eFarm): string {
		return self::url($eFarm).'?success=association:Membership::created';
	}

	public static function url(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/ferme/'.$eFarm['id'].'/adherer';
	}
}
?>
