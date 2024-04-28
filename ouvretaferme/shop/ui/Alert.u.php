<?php
namespace shop;

class AlertUi {

	public static function getError(string $fqn, array $options = []): mixed {

		return match($fqn) {

			'Date::points.check' => s("Merci de conserver au moins un mode de livraison."),
			'Date::orderEndAt.consistency' => s("La fin de la prise des commandes doit avoir lieu après le début de prise des commandes."),
			'Date::deliveryDate.consistency' => s("La fin des retraits doit avoir lieu après le début des retraits."),
			'Date::canNotOrder' => s("Votre commande a été annulée car vous êtes hors de la période de commande. Vous pourrez passer commander la prochaine fois !"),

			'Point::name.duplicate' => s("Une point de retrait existe déjà avec le même nom, merci de choisir un autre nom."),
			'Point::payment.check' => s("Veuillez sélectionner au moins un moyen de paiement."),

			'Point::deletedSaleUsed' => s("Ce mode de livraison ne peut pas être supprimé car il a déjà été utilisé pour une commande."),
			'Point::deletedDateUsed' => s("Ce mode de livraison ne peut pas être supprimé car il a déjà été utilisé dans une vente."),

			'Product::stock.check' => s("Vous devez indiquer un stock disponible positif ou laisser ce champ vide."),
			'Product::price.check' => s("Vous devez indiquer un prix valable."),

			'Sale::update.payment' => s("Vous ne pouvez pas modifier votre panier alors qu'un paiement est en cours."),
			'Sale::terms' => s("Veuillez accepter les conditions générales de vente pour continuer."),

			'Shop::name.fqn' => s("Une boutique existe déjà avec la même adresse."),
			'Shop::name.duplicate' => s("Une boutique existe déjà avec le même nom, merci de choisir un autre nom."),
			'Shop::payment.check' => s("Veuillez sélectionner au moins un moyen de paiement."),
			'Shop::terms.check' => s("Vos conditions générales de ventes ne doivent pas être vides de texte."),
			'Shop::payment.createOwner' => s("La transaction ne peut pas aboutir avec ce moyen de paiement à cause d'une erreur de configuration : {message}.", $options),
			'Shop::payment.create' => s("La transaction ne peut pas aboutir avec ce moyen de paiement à cause d'une erreur de configuration, veuillez vous rapprocher de votre producteur ou utiliser un autre moyen de paiement."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Date::copied' => s("La nouvelle vente a bien été copiée à partir d'une date précédente !"),
			'Date::created' => s("La nouvelle vente a bien été créée !"),
			'Date::deleted' => s("La vente a bien été supprimée"),
			'Date::updated' => s("Le paramétrage a bien été enregistré."),

			'Shop::opened' => s("Votre boutique a bien été ouverte !"),
			'Shop::closed' => s("Votre boutique a bien été fermée !"),
			'Shop::created' => s("Votre boutique a bien été créée. Vous pouvez maintenant paramétrer votre première vente !"),
			'Shop::updated' => s("Votre boutique a bien été mise à jour."),
			'Shop::collected' => s("La collecte des fonds a bien été lancée."),
			'Shop::deleted' => s("La boutique a bien été supprimée."),

			'Point::home.created' => s("Le point de livraison à domicile a bien été créé !"),
			'Point::home.updated' => s("Le point de livraison à domicile a bien été mis à jour."),
			'Point::home.deleted' => s("Le point de livraison à domicile a bien été supprimé."),

			'Point::place.created' => s("Le point de retrait a bien été créé !"),
			'Point::place.updated' => s("Le point de retrait a bien été mis à jour."),
			'Point::place.deleted' => s("Le point de retrait a bien été supprimé."),

			'Products::created' => s("Les produits ont bien été ajoutés à la vente."),
			'Product::deleted' => s("Le produit a bien été supprimé de la vente."),

			'Sale::phone' => s("Votre numéro de téléphone a bien été enregistré, vous pouvez maintenant valider votre mode de livraison !"),
			'Sale::address' => s("Votre adresse a bien été enregistré, vous pouvez maintenant valider votre commande !"),
			'Sale::canceled' => s("Votre commande a bien été annulée."),

			default => NULL

		};

	}

}
?>
