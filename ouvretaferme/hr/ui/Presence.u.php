<?php
namespace hr;

class PresenceUi {

	public function show(\farm\Farmer $eFarmer, \Collection $cPresence): string {

		$h = '<div class="util-action">';

			$h .= '<h2>'.s("Présence à la ferme").'</h2>';
			$h .= '<a href="/hr/presence:create?farm='.$eFarmer['farm']['id'].'&user='.$eFarmer['user']['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter").'</a>';

		$h .= '</div>';

		if($cPresence->empty()) {
			$h .= '<div class="util-block-help">'.s("Précisez la période de présence de cet utilisateur pour qu'il puisse participer aux interventions dans le planning au moment de son contrat !").'</div>';
		} else {

			$h .= '<table class="tr-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date d'arrivée").'</th>';
						$h .= '<th>'.s("Date de départ").'</th>';
						$h .= '<th class="text-end"></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cPresence as $ePresence) {

						$h .= '<tr>';

							$h .= '<td>';

								if($ePresence['from'] === NULL) {
									$h .= '/';
								} else {
									$h .= \util\DateUi::textual($ePresence['from']);
								}

							$h .= '</td>';

							$h .= '<td>';

								if($ePresence['to'] === NULL) {
									$h .= '/';
								} else {
									$h .= \util\DateUi::textual($ePresence['to']);
								}

							$h .= '</td>';

							$h .= '<td class="text-end">';
								$h .= '<a href="/hr/presence:update?id='.$ePresence['id'].'" class="btn btn-secondary">'.\Asset::icon('gear-fill').'</a> ';
								$h .= '<a data-ajax="/hr/presence:doDelete" post-id="'.$ePresence['id'].'" data-confirm="'.s("Supprimer cette période de présence ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm, Presence $ePresence): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/hr/presence:doCreate');

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($ePresence, ['user', 'from', 'to']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter la présence"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une présence à la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public static function getRelevant(\Collection $cPresence): Presence {

		if($cPresence->empty()) {
			return new Presence();
		}

		$ePresenceFuture = new Presence();
		$ePresencePast = new Presence();

		foreach($cPresence as $ePresence) {

			// Priorité absolue : présence en cours
			if(
				$ePresence['from'] <= currentDate() and (
					$ePresence['to'] === NULL or
					$ePresence['to'] >= currentDate()
				)
			) {
				$ePresence['when'] = 'now';
				return $ePresence;
			}

			if($ePresence['from'] > currentDate()) {

				if(
					$ePresenceFuture->empty() or
					$ePresence['from'] < $ePresenceFuture['from']
				) {
					$ePresenceFuture = $ePresence;
					$ePresenceFuture['when'] = 'future';
				}

			}

			if($ePresence['from'] < currentDate()) {

				if(
					$ePresencePast->empty() or
					$ePresence['to'] > $ePresencePast['to']
				) {
					$ePresencePast = $ePresence;
					$ePresencePast['when'] = 'past';
				}

			}

		}

		return (
			$ePresenceFuture->notEmpty() ?
				$ePresenceFuture :
				$ePresencePast
		);

	}

	public function update(Presence $ePresence): \Panel {

		$form = new \util\FormUi();
 
		$h = '';

		$h .= $form->openAjax('/hr/presence:doUpdate');

			$h .= $form->hidden('id', $ePresence['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($ePresence['farm'], TRUE)
			);

			$h .= $form->group(
				s("Utilisateur"),
				\user\UserUi::name($ePresence['user'])
			);

			$h .= $form->dynamicGroups($ePresence, ['from', 'to']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une présence à la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Presence::model()->describer($property, [
			'from' => s("Jour d'arrivée à la ferme"),
			'to' => s("Jour de départ de la ferme"),
			'user' => s("Utilisateur"),
		]);

		switch($property) {

			case 'user' :
				$d->values = fn(Presence $e) => $e['cUser'] ?? $e->expects(['cUser']);
				break;

			case 'to' :
				$d->after = \util\FormUi::info(s("Laisser vide si cet utilisateur n'a pas de date de départ connue."));
				break;


		}

		return $d;

	}

}
?>
