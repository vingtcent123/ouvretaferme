<?php
namespace association;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'History::terms.check' => s("Veuillez accepter les statuts et le règlement intérieur."),
			'History::amount.check' => s("Le montant doit être au moins égal à la cotisation de base ({amount}).", ['amount' => \util\TextUi::money(\Setting::get('association\membershipFee'), precision: 0)]),
			'History::amount.checkDonation' => s("Quel montant souhaitez-vous donner ?"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			default => NULL

		};

	}

}
?>
