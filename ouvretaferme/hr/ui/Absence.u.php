<?php
namespace hr;

class AbsenceUi {

	public function show(\farm\Farmer $eFarmer, \Collection $cAbsence): string {

		$h = '<div class="util-action">';

			$h .= '<h2>'.s("Absence à la ferme").'</h2>';
			$h .= '<a href="/hr/absence:create?farm='.$eFarmer['farm']['id'].'&user='.$eFarmer['user']['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter").'</a>';

		$h .= '</div>';

		if($cAbsence->empty()) {
			$h .= '<div class="util-info">'.s("Aucune absence n'a été saisie pour cet utilisateur.").'</div>';
		} else {

			$h .= '<div class="util-overflow-sm">';

				$h .= '<table class="tr-bordered">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("Début").'</th>';
							$h .= '<th>'.s("Fin").'</th>';
							$h .= '<th>'.s("Durée").'</th>';
							$h .= '<th>'.s("Raison").'</th>';
							$h .= '<th class="text-end"></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cAbsence as $eAbsence) {

							$h .= '<tr>';

								$h .= '<td>';

									if($eAbsence['from'] === NULL) {
										$h .= '/';
									} else {
										$h .= \util\DateUi::textual($eAbsence['from'], \util\DateUi::DATE);
										$h .= '<div class="color-muted">'.\util\DateUi::textual($eAbsence['from'], \util\DateUi::TIME_HOUR_MINUTE).'</div>';
									}

								$h .= '</td>';

								$h .= '<td>';

									if($eAbsence['to'] === NULL) {
										$h .= '/';
									} else {
										$h .= \util\DateUi::textual($eAbsence['to'], \util\DateUi::DATE);
										$h .= '<div class="color-muted">'.\util\DateUi::textual($eAbsence['to'], \util\DateUi::TIME_HOUR_MINUTE).'</div>';
									}

								$h .= '</td>';

								$h .= '<td class="color-muted">';
									$h .= p("{value} jour", "{value} jours", $eAbsence['duration']);
								$h .= '</td>';

								$h .= '<td>';
									$h .= AbsenceUi::p('type')->values[$eAbsence['type']];
								$h .= '</td>';

								$h .= '<td class="text-end">';
									$h .= '<a href="/hr/absence:update?id='.$eAbsence['id'].'" class="btn btn-secondary">'.\Asset::icon('gear-fill').'</a> ';
									$h .= '<a data-ajax="/hr/absence:doDelete" post-id="'.$eAbsence['id'].'" data-confirm="'.s("Supprimer cette absence ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
								$h .= '</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm, Absence $eAbsence): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/hr/absence:doCreate');

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($eAbsence, ['user', 'type', 'from', 'to', 'duration']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter l'absence"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une absence"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Absence $eAbsence): \Panel {

		$form = new \util\FormUi();
 
		$h = '';

		$h .= $form->openAjax('/hr/absence:doUpdate');

			$h .= $form->hidden('id', $eAbsence['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eAbsence['farm'], TRUE)
			);

			$h .= $form->group(
				s("Utilisateur"),
				\user\UserUi::name($eAbsence['user'])
			);

			$h .= $form->dynamicGroups($eAbsence, ['type', 'from', 'to', 'duration']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une absence"),
			body: $h,
			footer: '<div class="text-end"><a data-ajax="/hr/absence:doDelete" post-id="'.$eAbsence['id'].'" data-confirm="'.s("Supprimer cette absence ?").'" class="btn btn-danger">'.s("Supprimer").'</a></div>',
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Absence::model()->describer($property, [
			'from' => s("Début de l'absence"),
			'to' => s("Fin de l'absence"),
			'duration' => s("Durée de l'absence"),
			'user' => s("Utilisateur"),
			'type' => s("Type"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					Absence::VACATION => s("Congés"),
					Absence::RTT => s("RTT"),
					Absence::RECOVERY => s("Compensation"),
					Absence::OTHER => s("Autre motif")
				];
				break;

			case 'duration' :
				$d->append = s("jours");
				break;

			case 'user' :
				$d->values = fn(Absence $e) => $e['cUser'] ?? $e->expects(['cUser']);
				break;


		}

		return $d;

	}

}
?>
