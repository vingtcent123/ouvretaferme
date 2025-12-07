<?php
namespace farm;


class AdminUi {

	public function __construct() {

		\Asset::css('farm', 'admin.css');

	}

	public function getNavigation(string $selection): string {

		// Un seul onglet pour l'instant
		return '';

		$pages = [
			'farm' => s("Parcourir"),
		];

		$h = '<div class="nav">';

			foreach($pages as $page => $name) {
				$h .= '<a href="/farm/admin/'.($page === 'farm' ? '' : $page).'" class="nav-link '.($selection === $page ? 'active' : '').'">'.$name.'</a>';
			}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form with default conditions
	 *
	 */
	public function getFarmsForm(\Search $search, int $count) {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/admin/', ['method' => 'get', 'id' => 'form-search']);
			$h .= '<div>';

				$h .= $form->number('id', $search->get('id'), ['placeholder' => 'ID']);
				$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);
				$h .= $form->text('user', $search->get('user'), ['placeholder' => s("Utilisateur")]);
			$h .= $form->checkbox('membership', 1, ['checked' => $search->get('membership'), 'callbackLabel' => fn($input) => $input.' '.s("Fermes adhérentes")]);

				$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
				if($search->notEmpty()) {
					$h .= '<a href="/farm/admin/" class="btn btn-sm btn-secondary">'.\Asset::icon('x-lg').'</a>';
				}

				$h .= '<div class="form-search-end">';
					$h .= p("{value} ferme", "{value} fermes", $count);
				$h .= '</div>';
			$h .= '</div>';

		$h .= $form->close();

		return $h;

	}

	/**
	 * Organize the table with the farms
	 *
	 */
	public function displayFarms(\Collection $cFarm, int $nFarm, int $page, \Search $search): string {

		if($nFarm === 0) {
			return '<div class="util-info">'.s("Il n'y a aucune ferme à afficher...").'</div>';
		}

		$h = '<div class="util-overflow-xs stick-sm">';

			$h .= '<table class="tr-even farm-admin-table">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.$search->linkSort('id', '#', SORT_DESC).'</th>';
						$h .= '<th></th>';
						$h .= '<th>'.$search->linkSort('name', s("Nom")).'</th>';
						$h .= '<th>'.s("Ville").'</th>';
						$h .= '<th>'.s("Utilisateurs").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cFarm as $eFarm) {

					$h .= '<tr id="farm-admin-'.$eFarm['id'].'" class="farm-admin-'.$eFarm['status'].'">';
						$h .= '<td class="text-center">'.$eFarm['id'].'</td>';
						$h .= '<td class="farm-admin-vignette">';
							if($eFarm['vignette'] !== NULL) {
								$h .= \Asset::image(new \media\FarmVignetteUi()->getUrlByElement($eFarm, 's'));
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a href="'.FarmUi::urlPlanningWeekly($eFarm).'">';
								if($eFarm['membership']) {
									$h .= \Asset::icon('star-fill').' ';
								}
								$h .= encode($eFarm['name']);
							$h .= '</a>';
						$h .= '</td>';
						$h .= '<td>';
							if($eFarm['legalCity'] !== NULL) {
								$h .= encode($eFarm['legalCity']);
								if($eFarm['legalPostcode'] !== NULL) {
									$h .= ' <small class="color-muted">'.$eFarm['legalPostcode'].'</small>';
								}
							} else {
								$h .= encode($eFarm['cultivationPlace'] ?? '');
							}
						$h .= '</td>';
						$h .= '<td>';
							if($eFarm['cFarmer']->empty()) {
								$h .= '-';
							} else {
								$h .= '<small>'.implode('<br/>', $eFarm['cFarmer']->toArray(function($eFarmer) {

									if($eFarmer['farmGhost']) {
										$h = $eFarmer['user']->getName();
									} else {
										$h = '<a href="/user/admin/?id='.$eFarmer['user']['id'].'">'.$eFarmer['user']->getName().'</a>';
									}

									$h .= ' <span class="color-muted">';
										if($eFarmer['farmGhost']) {
											$h .= \Asset::icon('snapchat');
										} else {
											$h .= FarmerUi::p('role')->values[$eFarmer['role']];
										}
									$h .= '</span>';

									return $h;

								})).'</small>';
							}
						$h .= '</td>';

						$h .= '<td class="text-center">';
						$h .= '<div>';
							$h .= '<a class="dropdown-toggle btn btn-secondary" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<a href="/association/admin/:create?id='.$eFarm['id'].'" class="dropdown-item">';
								$h .= s("Gérer les adhésions");
							$h .= '</a>';
							$h .= '</div>';
						$h .= '</div>';
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .= \util\TextUi::pagination($page, $nFarm / 100);

		return $h;
	}

}
?>
