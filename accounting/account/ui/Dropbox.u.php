<?php
namespace account;

class DropboxUi {

	public function __construct() {
	}

	public function updateConnection(\farm\Farm $eFarm, array $partnerData): string {

		$ePartner = $partnerData['partner'];

		$h = '<h3>'.s("Dropbox").'</h3>';

		$h .= '<div class="util-block-help">';
			$h .= s(
				"Vous avez la possibilité de connecter votre compte <link>Dropbox</link> pour y stocker vos factures.<br />Lorsque vous ajouterez vos factures dans le module de comptabilité au moment d'enregistrer vos opérations, elles seront alors automatiquement téléversées et rangées dans un sous-dossier dans votre Dropbox Applications > Ouvretaferme.",
				['link' => '<a href="https://www.dropbox.com/" target="_blank">']
			);
		$h .= '</div>';

		if($ePartner->isValid()) {

			[$quotaUsed, $quotaTotal] = $partnerData['quota'];

			$h .= '<ul style="list-style-type: disclosure-closed">';

				$h .= '<li>';
					$h .= s("<b>{employee}</b> a autorisé {siteName} à accéder au compte Dropbox de votre ferme le <b>{date}</b>.", [
						'date' => \util\DateUi::numeric($ePartner['updatedAt']),
						'employee' => $ePartner['updatedBy']->getName(),
					]);
				$h .= '</li>';

				$h .= '<li>';
					$h .= s("Note : vous avez utilisé {quotaUsed} Mo sur votre quota de {quotaTotal} Mo sur votre compte Dropbox", [
						'quotaUsed' => number_format($quotaUsed, decimal_separator: ',', thousands_separator: ' '),
						'quotaTotal' => number_format($quotaTotal, decimal_separator: ',', thousands_separator: ' '),
					]);
				$h .= '</li>';

			$h .= '</ul>';

			$h .= '<a data-ajax="/'.$eFarm['id'].'/account/dropbox:revoke" class="btn btn-primary">'.s("Révoquer l'accès à Dropbox").'</a>';

		} else {

			if($ePartner->notEmpty()) {

				$h .= '<ul style="list-style-type: disclosure-closed">';

					$h .= '<li>';
						$h .= s("L'accès au compte Dropbox de votre ferme a été révoqué ou a expiré. Cliquez sur le lien ci-dessous pour le remettre en place :");
					$h .= '</li>';

				$h .= '</ul>';

			}

			$h .= '<a href="'.$partnerData['authorizationUrl'].'" class="btn btn-primary">'.s("Autoriser {siteName} à accéder à Dropbox").'</a>';

		}


		return $h;
	}
}
