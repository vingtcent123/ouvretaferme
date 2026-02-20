<?php
namespace account;

Class PdpUi {

	public function getTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("La plateforme agréée");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}
}
