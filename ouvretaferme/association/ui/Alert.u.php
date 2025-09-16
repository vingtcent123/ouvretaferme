<?php
namespace association;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'History::terms.check' => s("Veuillez accepter les statuts et le règlement intérieur."),
			'History::amount.check' => s("Le montant doit être au moins égal à la cotisation de base ({amount}).", ['amount' => \util\TextUi::money(AssociationSetting::MEMBERSHIP_FEE, precision: 0)]),
			'History::amount.checkDonation' => s("Quel montant souhaitez-vous donner ?"),
			'History::membership.check' => s("On ne peut pas adhérer à l'association plusieurs fois pour la même année."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'History::adminCreated' => s("Le don a bien été enregistré"),
			'adminMembershipCreated' => s("L'adhésion a bien été enregistrée"),

			default => NULL

		};

	}

}
?>
