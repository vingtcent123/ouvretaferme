<?php
namespace hr;

class HrUi {

	public function __construct() {

		\Asset::css('hr', 'hr.css');

	}

	public function getPlanning(\farm\Farm $eFarm, \Collection $cUserFarm): string {

		$h = '<h1>';
			$h .= s("Équipe");
		$h .= '</h1>';

		$h .= '<div>';

			$h .= '<div class="hr-user-grid">';
				$h .= '<div class="hr-user-name util-grid-header"></div>';
				$h .= '<div class="util-grid-header">'.s("Présence").'</div>';
				$h .= '<div class="util-grid-header">'.s("Absence").'</div>';

			foreach($cUserFarm as $eUserFarm) {

				$cPresence = $eUserFarm['cPresence'];
				$cAbsence = $eUserFarm['cAbsence'];

				$h .= '<div class="hr-user-name">';
					$h .= '<div>'.\user\UserUi::getVignette($eUserFarm, '2rem').' '.\user\UserUi::name($eUserFarm).'</div>';
					$h .= '<div>';

						if($eFarm->canManage()) {
							$h .= '<a data-dropdown="bottom-end" class="btn btn-primary dropdown-toggle">'.\Asset::icon('plus-circle').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<a href="/hr/presence:create?farm='.$eFarm['id'].'&user='.$eUserFarm['id'].'" class="dropdown-item">'.s("Ajouter une présence").'</a>';
								$h .= '<a href="/hr/absence:create?farm='.$eFarm['id'].'&user='.$eUserFarm['id'].'" class="dropdown-item">'.s("Ajouter une absence").'</a>';
							$h .= '</div>';
						}

					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div>';
					$h .= '<h5 class="hide-sm-up">'.s("Présence").'</h5>';
					if($cPresence->empty()) {
						$h .= s("/");
					} else {
						foreach($cPresence as $ePresence) {
							$h .= '<div>';

								$h .= '<a href="/hr/presence:update?id='.$ePresence['id'].'">';
									if($ePresence['from'] === NULL) {
										$h .= s("jusqu'au {to}", ['to' => \util\DateUi::numeric($ePresence['to'])]);
									} else if($ePresence['to'] === NULL) {
										$h .= s("depuis le {from}", ['from' => \util\DateUi::numeric($ePresence['from'])]);
									} else {
										$h .= s("{from} → {to}", ['from' => \util\DateUi::numeric($ePresence['from']), 'to' => \util\DateUi::numeric($ePresence['to'])]);
									}
								$h .= '</a>';

							$h .= '</div>';
						}
					}
				$h .= '</div>';

				$h .= '<div>';
					$h .= '<h5 class="hide-sm-up">'.s("Absence").'</h5>';
					if($cAbsence->empty()) {
						$h .= s("/");
					} else {
						foreach($cAbsence as $eAbsence) {

							$fromDate = substr($eAbsence['from'], 0, 10);
							$toDate = substr($eAbsence['to'], 0, 10);

							$h .= '<div>';

								$h .= '<a href="/hr/absence:update?id='.$eAbsence['id'].'">';

								if($fromDate === $toDate) {
									$h .= s("{from} → {to}", ['from' => \util\DateUi::numeric($eAbsence['from'], \util\DateUi::DATE_HOUR_MINUTE), 'to' => \util\DateUi::numeric($eAbsence['to'], \util\DateUi::TIME_HOUR_MINUTE)]);
								} else {
									$h .= s("{from} → {to}", ['from' => \util\DateUi::numeric($eAbsence['from'], \util\DateUi::DATE_HOUR_MINUTE), 'to' => \util\DateUi::numeric($eAbsence['to'], \util\DateUi::DATE_HOUR_MINUTE)]);
								}

								$h .= '</a>';

								$h .= ' / <span class="color-muted">'.p("{value} jour", "{value} jours", $eAbsence['duration']).'</span>';

							$h .= '</div>';

						}
					}
				$h .= '</div>';

			}

			$h .= '<div>';

		$h .= '<div>';

		return $h;

	}

}
?>