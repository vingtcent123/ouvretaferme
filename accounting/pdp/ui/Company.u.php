<?php
namespace pdp;

Class CompanyUi {

	public function summary(Company $eCompany): string {

		$h = '<div class="util-block stick-xs bg-background-light">';

			$h .= '<dl class="util-presentation util-presentation-1">';

				$h .= '<dt>'.s("Identifiant").'</dt>';
				$h .= '<dd>'.encode($eCompany['id']).'</dd>';

				$h .= '<dt>'.s("Siren").'</dt>';
				$h .= '<dd>'.encode($eCompany['number']).'</dd>';

				$h .= '<dt>'.s("Adresse").'</dt>';
				$h .= '<dd>';
					$h .= encode($eCompany['address']);
					$h .= '<br />'.encode($eCompany['postcode']);
					$h .= ' '.encode($eCompany['city']);
				$h .= '</dd>';

			$h .= '</dl>';

		$h .= '</div>';

		return $h;

	}
}
