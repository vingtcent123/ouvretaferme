<?php
namespace company;

class AdminUi {

	public function __construct() {

		\Asset::css('company', 'admin.css');

	}

	public function getNavigation(string $selection): string {

		// Un seul onglet pour l'instant
		return '';

		$pages = [
			'company' => s("Parcourir"),
		];

		$h = '<div class="nav">';

			foreach($pages as $page => $name) {
				$h .= '<a href="/company/admin/'.($page === 'company' ? '' : $page).'" class="nav-link '.($selection === $page ? 'active' : '').'">'.$name.'</a>';
			}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form with default conditions
	 *
	 */
	public function getCompaniesForm(\Search $search, int $count) {

		$form = new \util\FormUi();

		$h = $form->openAjax('/company/admin/', ['method' => 'get', 'id' => 'form-search']);
			$h .= '<div>';

				$h .= $form->number('id', $search->get('id'), ['placeholder' => 'ID']);
				$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);
				$h .= $form->text('user', $search->get('user'), ['placeholder' => s("Utilisateur")]);

				$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
				if($search->notEmpty()) {
					$h .= '<a href="/company/admin/" class="btn btn-sm btn-secondary">'.\Asset::icon('x-lg').'</a>';
				}

				$h .= '<div class="form-search-end">';
					$h .= p("{value} ferme", "{value} fermes", $count);
				$h .= '</div>';
			$h .= '</div>';

		$h .= $form->close();

		return $h;

	}

	/**
	 * Organize the table with the companies
	 *
	 */
	public function displayCompanies(\Collection $cCompany, int $nCompany, int $page, \Search $search): string {

		if($nCompany === 0) {
			return '<div class="util-info">'.s("Il n'y a aucune ferme à afficher...").'</div>';
		}

		$h = '<div class="util-overflow-xs stick-sm">';

			$h .= '<table class="company-admin-table">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.$search->linkSort('id', '#', SORT_DESC).'</th>';
						$h .= '<th></th>';
						$h .= '<th>'.$search->linkSort('name', s("Nom")).'</th>';
						$h .= '<th>'.s("Ville").'</th>';
						$h .= '<th>'.s("Utilisateurs").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCompany as $eCompany) {

					$h .= '<tr id="company-admin-'.$eCompany['id'].'" class="company-admin-'.$eCompany['status'].'">';
						$h .= '<td class="text-center">'.$eCompany['id'].'</td>';
						$h .= '<td class="company-admin-vignette">';
							if($eCompany['vignette'] !== NULL) {
								$h .= \Asset::image((new \media\CompanyVignetteUi())->getUrlByElement($eCompany, 's'));
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a href="'.CompanyUi::url($eCompany).'">'.encode($eCompany['name']).'</a>';
						$h .= '</td>';
						$h .= '<td>';
						$h .= '</td>';
						$h .= '<td>';
							if($eCompany['cEmployee']->empty()) {
								$h .= '-';
							} else {
								$h .= '<small>'.implode('<br/>', $eCompany['cEmployee']->toArray(function($eEmployee) {

									return '<a href="user/admin/?id='.$eEmployee['user']['id'].'">'.\user\UserUi::name($eEmployee['user']).'</a>';

								})).'</small>';
							}
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .= \util\TextUi::pagination($page, $nCompany / 100);

		return $h;
	}

}
?>
