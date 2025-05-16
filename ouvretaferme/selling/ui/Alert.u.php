<?php
namespace selling;

class AlertUi {

	public static function getError(string $fqn, array $options): mixed {

		return match($fqn) {

			'Configuration::error' => s("Il y a des erreurs à corriger sur le formulaire."),
			'Configuration::legalName.empty' => s("Merci d'indiquer la raison sociale de la ferme"),
			'Configuration::legalEmail.empty' => s("Merci d'indiquer l'adresse e-mail de la ferme"),
			'Configuration::creditPrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::invoicePrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::documentInvoices.consistency' => fn($e) => s("Par souci de cohérence avec les factures existantes, le numéro ne peut pas être inférieur à {value}", $e['invoicePrefixMin']),
			'Configuration::orderFormPrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::deliveryNotePrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::notLegal' => '<p>'.s("Vous devez configurer la raison sociale et l'adresse e-mail de votre ferme pour continuer !").'</p><a href="/selling/configuration:update?id='.$options['farm']['id'].'" class="btn '.($options['btn'] ?? 'btn-transparent').'">'.s("Compléter mes informations").'</a>',
			'Configuration::notSelling' => '<p>'.s("Nous avons besoin de quelques informations administratives de base à propos de votre ferme (adresse e-mail, objet social...) pour accéder à cette fonctionnalité !").'</p><a href="/selling/configuration:update?id='.$options['farm']['id'].'" class="btn '.($options['btn'] ?? 'btn-transparent').'">'.s("Compléter mes informations").'</a>',

			'Customer::name.empty' => s("Un nom de client valide est obligatoire."),
			'Customer::phone.check' => s("Le numéro de téléphone est invalide."),
			'Customer::category.user' => s("Un client ne peut pas être transformé en point de vente."),
			'Customer::deletedUsed' => s("Ce client ne peut pas être supprimé car il a déjà été utilisé pour une vente."),
			'Customer::firstName.empty' => s("Veuillez indiquer le prénom de votre client."),
			'Customer::lastName.empty' => s("Veuillez indiquer le nom de votre client."),

			'Grid::packaging.check' => s("Merci d'indiquer un conditionnement valide."),
			'Grid::price.check' => s("Merci d'indiquer un prix valide."),

			'Invoice::sales.prepare' => s("Veuillez sélectionner au moins une vente."),
			'Invoice::sales.check' => s("Une ou plusieurs ventes ne sont pas éligibles à la facturation."),
			'Invoice::sales.taxes' => s("Vous ne pouvez pas mixer des ventes hors taxes et toutes taxes comprises au sein d'une même facture."),
			'Invoice::sales.hasVat' => s("Vous ne pouvez pas mixer des ventes assujetties et non assujetties à la TVA au sein d'une même facture."),
			'Invoice::sales.paid' => s("Il n'est pas possible de créer une facture contenant plusieurs ventes dont au moins l'une a déjà été payée. Vous devez créer des factures individuelles pour les ventes déjà payées."),
			'Invoice::sales.methods' => s("Il n'est pas possible de créer une facture contenant des ventes avec des moyens de paiement différents."),
			'Invoice::emptySales' => s("Vous devez ajouter au moins une vente à votre facture !"),
			'Invoice::invoices.check' => s("Merci de sélectionner au moins une facture."),
			'Invoice::inconsistencySales' => s("Vous ne pouvez pas regénérer cette facture car une ou plusieurs ventes ne sont plus éligibles à la facturation !"),
			'Invoice::fileAlreadySent' => s("Cette facture a déjà été envoyée au client"),
			'Invoice::fileEmpty' => s("Le fichier PDF de cette facture n'existe pas."),

			'Product::proOrPrivate.check' => s("Veuillez déterminer si votre produit composé est vendu aux clients particuliers ou aux clients professionnels. Il ne peut pas à la fois être vendu aux professionnels et aux particuliers"),
			'Product::privatePrice.empty' => s("Veuillez indiquer le prix de ce produit vendu aux particuliers"),
			'Product::proPrice.empty' => s("Veuillez indiquer le prix de ce produit vendu aux professionnels"),

			'Sale::deletedNotDraft' => s("Il n'est possible de supprimer que les ventes à l'état de brouillon ou de panier."),
			'Sale::deletedMarket' => s("Cette vente de marché ne peut pas être supprimée."),

			'Sale::shopPoint.check' => s("Vous devez choisir un mode de livraison pour continuer !"),
			'Sale::phone.check' => s("Nous avons besoin de votre numéro de téléphone pour le suivi de votre commande."),
			'Sale::orderMin.check' => s("Vous n'avez pas atteint le minimum de commande demandé pour ce point de livraison !"),
			'Sale::address.check' => s("Nous avons besoin de votre adresse pour vous livrer à domicile !"),
			'Sale::products.check' => s("Vous ne pouvez pas continuer car votre panier est vide !"),
			'Sale::preparationStatus.checkOutOfDraft' => s("Il est nécessaire d'indiquer une date de vente pour sortir la vente de l'état de brouillon."),
			'Sale::preparationStatus.market' => s("Vous ne pouvez pas changer l'état de cette vente car il y a des commandes en cours."),
			'Sale::customer.market' => s("Le logiciel de caisse n'est disponible que pour les points de vente aux particuliers."),
			'Sale::deliveredAt.check' => s("La date de vente est obligatoire."),
			'Sale::deliveredAt.composition' => s("Vous avez déjà ajouté une composition pour cette même date."),
			'Sale::deliveredAt.compositionTooLate' => s("Vous ne pouvez modifier la composition de votre produit que sur les 30 derniers jours."),
			'Sale::paymentStatus.method' => s("Vous ne pouvez modifier l'état du paiement car le paiement est géré par un moyen de paiement externe"),
			'Sale::shippingIncludedVat.check' => s("Les frais de livraison doivent être supérieurs à zéro ou laissés vide."),
			'Sale::shippingExcludedVat.check' => s("Les frais de livraison doivent être supérieurs à zéro ou laissés vide."),
			'Sale::downloadEmpty' => s("Sélectionnez au moins une vente pour générer des étiquettes"),
			'Sale::canNotSell' => s("La caisse virtuelle n'est plus accessible pour cette vente !"),
			'Sale::orderFormValidUntil.check' => s("La date d'échéance doit être au plus tôt la date d'aujourd'hui."),
			'Sale::sales.check' => s("Merci de sélectionner au moins une vente"),
			'Sale::from.check' => s("Vous n'avez pas indiqué l'origine de la vente"),
			'Sale::market.status' => s("Vous ne pouvez pas mettre à jour une vente terminée ou annulée."),
			'Sale::productsBasket.check' => s("Un produit n'est plus disponible dans la quantité que vous avez demandée, veuillez vérifier votre panier et revalider votre commande."),


			'Sale::generateDeliveryNote' => s("Vous ne pouvez générer de bon de livraison que pour les ventes livrées !"),
			'Sale::generateOrderForm' => s("Vous ne pouvez générer de bon de commande que pour les ventes à l'état de brouillon ou confirmées !"),

			'Stock::newValue.check' => s("Merci d'indiquer une valeur supérieure ou égale à zéro"),
			'Stock::newValue.negative' => s("Le stock ne peut pas être négatif"),

			'Pdf::noCustomerEmail' => s("Vous n'avez pas renseigné d'adresse e-mail pour ce client"),
			'Pdf::noFarmEmail' => s("Vous n'avez pas renseigné d'adresse e-mail pour votre ferme"),
			'Pdf::emptySale' => s("Vous pourrez générer ce document dès que vous aurez ajouté au moins un article à la vente !"),
			'Pdf::fileAlreadySent' => s("Ce fichier a déjà été envoyé au client"),
			'Pdf::fileEmpty' => s("Ce fichier PDF n'existe pas."),
			'Pdf::fileTooOld' => s("Par sécurité, vous ne pouvez envoyer automatiquement un document par e-mail que le jour même ou le lendemain de sa génération."),
			'Pdf::fileLocked' => s("Ce fichier est déjà en cours de génération, réactualisez la page d'ici quelques instants !"),

			'Item::vatRate.check' => s("Vous devez renseigner la TVA de tous les produits que vous vendez !"),
			'Item::number.empty' => s("La quantité vendue ne peut pas être vide !"),
			'Item::price.locked' => s("Veuillez indiquer le montant total !"),
			'Item::unitPrice.check' => s("Veuillez indiquer le prix unitaire !"),
			'Item::number.division' => s("Lorsque le prix unitaire est verrouillée, la quantité vendue ne peut pas être égale à zéro !"),
			'Item::unitPrice.division' => s("Lorsque la quantité vendue est verrouillée, le prix unitaire ne peut pas être égal à zéro !"),
			'Item::createEmpty' => s("Ajoutez au moins un article à la vente !"),
			'Item::createDuplicateNameMarket' => fn($name) => s("Vous avez déjà ajouté un article sans référence de produit portant le nom {value} à votre vente !", $name),
			'Item::createDuplicateProductMarket' => fn($name) => s("Vous avez déjà ajouté le produit {value} à votre vente !", $name),
			'Item::canNotDelete' => s("Impossible de supprimer cet article"),

			'Item::createCollectionError' => s("Il y a des erreurs à vérifier sur un ou plusieurs produits que vous souhaitez ajouter à la vente."),

			'Unit::singular.duplicate' => s("Il y a déjà une unité avec le même nom"),
			'Unit::deleteUsed' => s("Cette unité ne peut pas être supprimée car elle est utilisée dans une vente ou pour un produit"),

			'Market::emptyPayment' => s("Veuillez indiquer un moyen de paiement pour terminer cette vente"),
			'Market::inconsistencyTotal' => s("La somme des moyens de paiement doit être égale au montant de la vente"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Customer::created' => s("Le client a bien été ajouté."),
			'Customer::updated' => s("Le client a bien été mis à jour."),
			'Customer::deleted' => s("Le client a bien été supprimé."),
			'Customer::optInUpdated' => s("Vos préférences de communication par e-mail ont bien été mises à jour."),

			'Market::pricesUpdated' => s("Les nouveaux prix des produits proposés à la vente ont bien été enregistrés."),
			'Market::closed' => s("La vente a bien été clôturée !"),

			'Category::created' => s("La catégorie a bien été créée."),
			'Category::updated' => s("La catégorie a bien été mise à jour."),
			'Category::deleted' => s("La catégorie a bien été supprimée."),

			'Product::created' => s("Le produit a bien été ajouté."),
			'Product::createdComposition' => s("La composition a bien été ajoutée."),
			'Product::updated' => s("Le produit a bien été mis à jour."),
			'Product::updatedComposition' => s("La composition a bien été mise à jour."),
			'Product::deleted' => s("Le produit a bien été supprimé."),
			'Product::deletedComposition' => s("La composition a bien été supprimée."),
			'Product::categoryUpdated' => s("La catégorie a bien été modifiée."),
			'Product::stockEnabled' => s("Le suivi du stock a bien été activé pour ce produit."),
			'Product::stockDisabled' => s("Le suivi du stock a bien été désactivé pour ce produit."),

			'Sale::created' => s("La vente a bien été créée."),
			'Sale::updated' => s("La vente a bien été mise à jour."),
			'Sale::deleted' => s("La vente a bien été supprimée."),
			'Sale::pdfCreated' => [
				Pdf::ORDER_FORM => s("Le devis a été généré au format PDF !"),
				Pdf::DELIVERY_NOTE => s("Le bon de livraison a été généré au format PDF !"),
			][$options['type']].$options['actions'],
			'Sale::customerUpdated' => s("Le client a bien été mis à jour."),

			'Stock::updated' => s("Le stock a bien été mis à jour pour ce produit."),

			'Pdf::orderFormSent' => s("Le devis a bien été envoyé par e-mail au client."),
			'Pdf::deliveryNoteSent' => s("Le bon de livraison a bien été envoyé par e-mail au client."),
			'Pdf::deleted' => s("Le document a bien été supprimé."),

			'Invoice::created' => s("La facture a bien été générée !").($options['actions'] ?? ''),
			'Invoice::createdCollection' => s("Les factures ont bien été créées et sont en cours de génération !").($options['actions'] ?? ''),
			'Invoice::sent' => s("La facture a bien été envoyée par e-mail au client."),
			'Invoice::sentCollection' => s("Les factures ont bien été envoyées par e-mail aux clients."),
			'Invoice::regenerated' => s("La facture a bien été regénérée !").($options['actions'] ?? ''),
			'Invoice::updated' => s("La facture a bien été mise à jour."),
			'Invoice::deleted' => s("La facture a bien été supprimée."),
			'Invoice::deletedCollection' => s("Les factures ont bien été supprimées."),

			'Item::created' => s("Le(s) article(s) ont bien été ajoutés à la vente."),
			'Item::updated' => s("L'article a bien été mis à jour."),
			'Item::deleted' => s("L'article a bien été supprimé de la vente."),

			'Unit::created' => s("L'unité a bien été créée."),
			'Unit::updated' => s("L'unité a bien été mise à jour."),
			'Unit::deleted' => s("L'unité a bien été supprimée."),

			'Configuration::updated' => s("La configuration a bien été mis à jour."),

			'Quality::updated' => s("Le signe de qualité a bien été modifié."),
			'Quality::deleted' => s("Le signe de qualité a bien été supprimé et retiré des produits."),

			default => NULL

		};

	}

}
?>
