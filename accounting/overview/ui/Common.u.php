<?php
namespace overview;

Class CommonUi {


	public function getDropdownClass(\farm\Farm $eFarm, string $class): string {

		$h = '<a class="nav-item" data-dropdown="bottom">';
			$h .= \Asset::icon('chevron-down');
		$h .= '</a>';
		$h .= '<div class="dropdown-list bg-secondary">';
			$h .= '<div class="dropdown-title">'.s("Compte {value}", encode($class)).'</div>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?accountLabel='.encode($class).'" class="dropdown-item">'.s("Voir les écritures au journal").'</a>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/book?accountLabel='.encode($class).'" class="dropdown-item">'.s("Voir les enregistrements au grand-livre").'</a>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/balance?accountLabel='.encode($class).'" class="dropdown-item">'.s("Voir la balance").'</a>';
		$h .= '</div>';

		return $h;

	}
}
