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

			'Product::available.check' => s("Vous devez indiquer des limites supérieures à zéro ou laisser ce champ vide."),
			'Product::price.check' => s("Vous devez indiquer un prix valable."),
			'Product::limitEndAt.consistency' => s("La fin de la prise des commandes doit avoir lieu après le début de prise des commandes."),
			'Product::limitMax.consistency' => s("La quantité maximale autorisée par commande ne peut pas être inférieur au minimum de commande."),
			'Product::empty' => s("Vous n'avez sélectionné aucun produit."),
			'Product::createCollectionError' => s("Il y a des erreurs à vérifier sur un ou plusieurs produits que vous souhaitez ajouter."),

			'Range::missingDate' => s("Veuillez choisir si vous souhaitez retirer le catalogue de toutes les ventes en cours sur la boutique ou non."),
			'Range::duplicate' => s("Ce catalogue a déjà été associé à la boutique."),

			'Sale::update.payment' => s("Vous ne pouvez pas modifier votre panier alors qu'un paiement est en cours."),
			'Sale::terms' => s("Veuillez accepter les conditions générales de vente pour continuer."),

			'Shop::name.fqn' => s("Une boutique existe déjà avec la même adresse."),
			'Shop::name.duplicate' => s("Une boutique existe déjà avec le même nom, merci de choisir un autre nom."),
			'Shop::payment.check' => s("Veuillez sélectionner au moins un moyen de paiement, ou alternativement désactiver la page de choix du moyen de paiement."),
			'Shop::terms.check' => s("Vos conditions générales de vente ne doivent pas être vides de texte."),
			'Shop::payment.createOwner' => s("La transaction ne peut pas aboutir avec ce moyen de paiement à cause d'une erreur de configuration : {message}.", $options),
			'Shop::payment.create' => s("La transaction ne peut pas aboutir avec ce moyen de paiement à cause d'une erreur de configuration, veuillez vous rapprocher de votre producteur ou utiliser un autre moyen de paiement."),
			'Shop::customColor.light' => s("La couleur constrante est trop claire, merci de choisir une teinte plus foncée pour préserer la lisibilité de votre boutique."),
			'Shop::customBackground.light' => s("La couleur d'arrière plan est trop foncée, merci de choisir une teinte plus claire pour préserer la lisibilité de votre boutique."),
			'Shop::invalidKey' => s("Le code d'invitation que vous avez saisi n'est pas reconnu ou a expiré."),
			'Shop::invalidFarm' => s("Votre ferme ne peut pas rejoindre comme producteur une ferme qu'elle administre."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Date::copied' => s("La livraison a bien été copiée à partir d'une précédente !"),
			'Date::created' => s("La livraison a bien été créée !"),
			'Date::deleted' => s("La livraison a bien été supprimée"),
			'Date::updated' => s("Le paramétrage a bien été enregistré."),

			'Share::updated' => s("L'activité a bien été mise à jour."),
			'Share::deleted' => s("Le producteur a bien été retiré de la boutique."),
			'Share::deletedSelf' => s("Vous avez bien quitté cette boutique collective."),

			'Shop::opened' => s("Votre boutique a bien été ouverte !"),
			'Shop::closed' => s("Votre boutique a bien été fermée !"),
			'Shop::paymentOn' => s("La page de choix des moyens de paiement a bien été activée pour vos clients !"),
			'Shop::paymentOff' => s("La page de choix des moyens de paiement a bien été désactivée pour vos clients !"),
			'Shop::pointOn' => s("Le choix du mode de livraison a bien été activé pour vos clients !"),
			'Shop::pointOff' => s("Le choix du mode de livraison a bien été désactivé pour vos clients !"),
			'Shop::created' => s("Votre boutique a bien été créée. Vous pouvez maintenant paramétrer votre première vente !"),
			'Shop::updated' => s("Votre boutique a bien été mise à jour."),
			'Shop::updatedEmbed' => s("La configuration de l'intégration a bien été mise à jour !"),
			'Shop::customized' => s("Le style de votre boutique a bien été enregistré."),
			'Shop::collected' => s("La collecte des fonds a bien été lancée."),
			'Shop::deleted' => s("La boutique a bien été supprimée."),
			'Shop::joined' => s("Vous avez rejoint la boutique !"),
			'Shop::deletedFarmer' => s("Le producteur a bien été retiré de la boutique !"),

			'Point::home.created' => s("Le point de livraison à domicile a bien été créé. Vous pouvez aller l'activer sur les boutiques sur lequel vous souhaitez l'autoriser pour vos clients."),
			'Point::home.updated' => s("Le point de livraison à domicile a bien été mis à jour."),
			'Point::home.deleted' => s("Le point de livraison à domicile a bien été supprimé."),

			'Point::place.created' => s("Le point de retrait a bien été créé. Vous pouvez aller l'activer sur les boutiques sur lequel vous souhaitez l'autoriser pour vos clients."),
			'Point::place.updated' => s("Le point de retrait a bien été mis à jour."),
			'Point::place.deleted' => s("Le point de retrait a bien été supprimé."),

			'Products::created' => s("Les produits ont bien été ajoutés."),
			'Product::updated' => s("Le produit a bien été mis à jour."),
			'Product::deleted' => s("Le produit a bien été supprimé."),

			'Department::created' => s("Le rayon a bien été créé."),
			'Department::updated' => s("Le rayon a bien été mis à jour."),
			'Department::deleted' => s("Le rayon a bien été supprimé."),

			'Range::created' => s("Le catalogue a bien été associé à la boutique."),
			'Range::deleted' => s("Le catalogue a bien été dissocié de la boutique."),

			'Sale::phone' => s("Votre numéro de téléphone a bien été enregistré, vous pouvez maintenant valider votre mode de livraison !"),
			'Sale::address' => s("Votre adresse a bien été enregistré, vous pouvez maintenant valider votre commande !"),
			'Sale::canceled' => s("Votre commande a bien été annulée."),

			default => NULL

		};

	}

}
?>
