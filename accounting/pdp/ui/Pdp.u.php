<?php
namespace pdp;

Class PdpUi {

	public function getTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
			$h .= '<a href="javascript:go(-1);"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("La plateforme agréée");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function getConnectionBlock(\farm\Farm $eFarm): string {

		$h = '<div class="util-block-info">';

			$h .= '<p>'.s("{siteName} a choisi d'utiliser la plateforme Super PDP pour l'envoi et la réception de factures électroniques.").'</p>';
			$h .= '<p>'.s("Pour utiliser cette plateforme agréée : Cliquez sur le bouton <link>Utiliser Super PDP</link> et laissez-vous guider.<br /> Si vous avez déjà un compte sur Super PDP, vous pourrez vous connecter dessus pour autoriser {siteName} à déposer et récupérer les factures.<br />Si vous n'avez pas de compte, vous pourrez en créer un et autoriser {siteName} à s'y connecter.", ['link' => '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/pdp/:connect" class="btn btn-primary">']).'</p>';

		$h .= '</div>';

		$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/pdp/:connect" class="btn btn-primary">'.s("Utiliser Super PDP").'</a>';

		return $h;

	}

}
