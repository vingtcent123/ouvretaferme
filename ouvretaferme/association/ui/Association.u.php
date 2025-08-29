<?php
namespace association;

class AssociationUi {

	public function getTitle(): string {

		return '<h1>'.s("Association Ouvretaferme").'</h1>';

	}

	public function getProductName(bool $withDonation): string {

		$year = date('Y');

		if($withDonation) {
			return s("Adhésion {year} Ouvretaferme et don (merci !)", ['year' => $year]);
		}

		return s("Adhésion {year} Ouvretaferme", ['year' => $year]);

	}

	public static function confirmationUrl(\farm\Farm $eFarm): string {
		return self::url($eFarm).'?success=association:Membership::created';
	}

	public static function url(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/ferme/'.$eFarm['id'].'/adherer';
	}
}
?>
