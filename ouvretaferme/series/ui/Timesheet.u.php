<?php
namespace series;

class TimesheetUi {

	public function __construct() {

		\Asset::css('series', 'timesheet.css');
		\Asset::js('series', 'timesheet.js');

	}

	public function getList(Task $eTask, \Collection $cUser, ?string $close = NULL): string {

		$eTask->expects([
			'farm' => ['featureTime']
		]);

		$cUser->expects(['time']);

		$h = '<div class="timesheet-list-wrapper">';

			$users = 0;

			foreach($cUser as $eUser) {

				if($eUser['time'] === NULL) {
					continue;
				}

				$h .= '<div>';
					$h .= \user\UserUi::getVignette($eUser, '1.5rem');
					if($eUser['firstName'] === NULL) {
						$h .= encode($eUser['lastName']);
					} else {
						$h .= encode($eUser['firstName']);
						if($cUser->count() > 5) {
							$h .= ' <span>'.encode($eUser['lastName']).'</span>';
						}
					}
				$h .= '</div>';

				if($eTask['farm']->hasFeatureTime()) {
					$h .= '<a href="/series/timesheet?ids[]='.$eTask['id'].'&user='.$eUser['id'].''.($close ? '&close='.$close : '').'" class="timesheet-list-time">';
						$h .= TaskUi::convertTime($eUser['time']);
					$h .= '</a>';
				} else {
					$h .= '<div></div>';
				}

				$users++;

			}

			if($users === 0) {
				return '/';
			}


			if($eTask['farm']->hasFeatureTime()) {

				if($eTask['time'] !== NULL and $users > 1) {
					$h .= '<div>'.s("Total").'</div>';
					$h .= '<div class="timesheet-list-time">';
						$h .= TaskUi::convertTime($eTask['time']);
					$h .= '</div>';
				}

				if($eTask['timeExpected'] !== NULL) {
					$h .= '<div>'.s("Estimé").'</div>';
					$h .= '<div class="timesheet-list-time">';
						$h .= TaskUi::convertTime($eTask['timeExpected']);
					$h .= '</div>';
				}

			}

		$h .= '</div>';

		return $h;

	}

	public function update(\farm\Farm $eFarm, \Collection $cTask, \Collection $cUser, \user\User $eUserSelected, Timesheet $eTimesheetDefault): \Panel {

		$cTask->expects(['cultivation']);

		$form = new \util\FormUi();

		$h = '';

		$formOpen = $form->openAjax('/series/timesheet:doUpdateUser', ['data-ajax-origin' => 'timesheet', 'class' => 'panel-dialog container']);

		$h .= (new TaskUi())->getTasksField($form, $cTask, 'hidden', class: 'util-overflow-xs', displayPlace: TRUE, displayTime: TRUE);

		$h .= '<div class="'.($cUser->count() > 4 ? 'util-overflow-xs' : '').'">';
			$h .= '<div class="timesheet-update-users">';

				foreach($cUser as $eUser) {

					$time = $eUser['time'] ?? 0;

					if((new \hr\WorkingTime([
						'farm' => $eFarm,
						'user' => $eUser
					]))->canRead()) {

						$h .= '<a data-ajax="'.\util\HttpUi::setArgument(LIME_REQUEST, 'user', $eUser['id']).'" data-ajax-method="get" class="timesheet-update-item-user '.($eUserSelected['id'] === $eUser['id'] ? 'timesheet-update-item-user-selected' : '').'">';
							$h .= \user\UserUi::getVignette($eUser, '3rem');
							if($eUser['firstName'] === NULL) {
								$h .= encode($eUser['lastName']);
							} else {
								$h .= encode($eUser['firstName']);
								if($cUser->count() > 5) {
									$h .= '<span>'.encode($eUser['lastName']).'</span>';
								}
							}
							$h .= '<span id="timesheet-update-time-'.$eUser['id'].'" class="timesheet-update-item-user-time">';
								$h .= TaskUi::convertTime($time);
							$h .= '</span>';
						$h .= '</a>';

					}

				}

			$h .= '</div>';
		$h .= '</div>';

		$h .= '<div class="timesheet-update-item-actions stick-xs">';
			$h .= self::updateUser($form, $cTask, $eUserSelected, $eTimesheetDefault);
			$h .= $form->group(
				content: $form->submit(s("Enregistrer"), ['class' => 'btn btn-secondary ']),
				attributes: ['class' => 'timesheet-update-item-submit']
			);
		$h .= '</div>';

		$formClose = $form->close();

		return new \Panel(
			id: 'panel-timesheet-update',
			title: s("Compléter le temps de travail"),
			dialogOpen: $formOpen,
			dialogClose: $formClose,
			body: $h,
			close: INPUT('close', ['reloadIgnoreCascade', 'passthrough'], 'passthrough')
		);

	}

	protected static function updateUser(\util\FormUi $form, \Collection $cTask, \user\User $eUser, Timesheet $eTimesheetDefault): string {

		$eTimesheetDefault->expects(['date']);
		$eUser->expects(['ccTimesheet']);

		$eAction = $cTask->first()['action'];

		$h = '';

		if($eUser['ccTimesheet']->notEmpty()) {

			$h .= '<div class="timesheet-update-item-days">';

				foreach($eUser['ccTimesheet'] as $date => $cTimesheet) {

					$year = date_year($date);
					$time = $cTimesheet->sum('time');

					$h .= '<div class="timesheet-update-item-day">';
						$h .= '<div class="timesheet-update-item-day-date">';
							$h .= \util\DateUi::getDayName(date('N', strtotime($date))).'<br/>'.\util\DateUi::textual($date, \util\DateUi::DAY_MONTH);
							if($year !== (int)date('Y')) {
								$h .= ' '.$year;
							}
						$h .= '</div>';
						$h .= '<div class="timesheet-update-item-day-value">'.TaskUi::convertTime($time).'</div>';
						$body = [];
						foreach($cTimesheet as $eTimesheet) {
							$body[] = ['ids[]', $eTimesheet['id']];
						}
						$h .= '<a data-ajax="/series/timesheet:doDeleteCollection" '.attrAjaxBody($body).' class="timesheet-update-item-day-close" data-confirm="'.s("Supprimer ce temps de travail ?").'">';
							$h .= '<div class="btn btn-danger">'.\Asset::icon('trash-fill').'</div>';
						$h .= '</a>';
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		$h .= '<h4>'.s("Compléter pour {value}", \user\UserUi::name($eUser)).'</h4>';

		$h .= $form->hidden('user', $eUser['id']);

		$dateField = '<div class="timesheet-update-item-date-field">';
			$dateField .= '<div>';
				$dateField .= '<a onclick="Timesheet.previousDate(this)">'.\Asset::icon('chevron-left').'</a>';
			$dateField .= '</div>';
			$dateField .= '<div>';
				$dateField .= $form->dynamicField($eTimesheetDefault, 'date');
			$dateField .= '</div>';
			$dateField .= '<div>';
				$dateField .= '<a onclick="Timesheet.nextDate(this)">'.\Asset::icon('chevron-right').'</a>';
			$dateField .= '</div>';
		$dateField .= '</div>';


		$h .= $form->group(
			s("Jour de travail"),
			$dateField,
			['class' => 'timesheet-update-item-date-group']
		);


		if($cTask->count() > 1) {

			$default = match($eAction['fqn']) {
				ACTION_RECOLTE => 'harvest',
				ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION => 'plant',
				default => 'area'
			};

			$h .= $form->group(
				s("Répartition du temps de travail sur les interventions"),
				TaskUi::getDistributionField($form, $cTask, $default),
				['class' => 'timesheet-update-item-distribution-group '.(($cTask->count() > 1) ? '' : 'timesheet-update-item-distribution-label')]
			);

		}

		$timeField = $form->dynamicField($eTimesheetDefault, 'timeAdd');

		$timeField .= '<div class="timesheet-update-item-shortcuts">';
			$timeField .= '<div class="timesheet-update-item-title">';
				$timeField .= '<span>'.s("Raccourcis").'</span>';
			$timeField .= '</div>';
				$timeField .= '<a onclick="Timesheet.updateShortcutSign(this)" class="timesheet-update-item-sign btn btn-color-primary" title="'.s("Inverser le signe").'">';
					$timeField .= '+';
				$timeField .= '</a>';
			$timeField .= '<div class="timesheet-update-item-values">';


				foreach([
					[5 / 60, 1],
					[10 / 60, 1],
					[0.25, 1],
					[0.5, 1],
					[0.75, 1],
					[1, 1],
					[1.5, 2],
					[2, 1],
					[2.5, 3],
					[3, 1],
					[3.5, 3],
					[4, 1],
					[5, 1],
					[6, 1],
				] as [$minutes, $priority]) {

					$label = self::convertTimeField($minutes);

					$timeField .= '<a onclick="Timesheet.changeShortcut(this)" class="btn btn-color-primary timesheet-update-item-shortcut-'.$priority.'" data-value="'.$minutes.'">';
						$timeField .= $label;
					$timeField .= '</a>';

				}

			$timeField .= '</div>';
		$timeField .= '</div>';

		$h .= $form->group(
			s("Temps de travail"),
			$timeField
		);

		return $h;

	}

	public static function convertTimeField(float $time): string {

		$sign = ($time > 0 ? '' : '-').' ';

		$time = abs($time);

		$hours = intval($time);
		$minutes = (int)(($time - $hours) * 60);

		if($hours === 0) {
			return $sign.s("{value} min", $minutes);
		} else {
			if($minutes === 0) {
				return $sign.$hours.' h';
			} else {
				return $sign.$hours.' h '.sprintf('%02d', $minutes);
			}
		}

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Timesheet::model()->describer($property, [
			'date' => s("Jour de travail"),
		]);

		switch($property) {

			case 'timeAdd' :
				$d->field = function(\util\FormUi $form, Timesheet $e) {

					$h = '<div class="timesheet-time-field">';

						$h .= $form->hidden('timeAdd');

						$h .= '<div class="timesheet-time-field-value">';
							$h .= '<a onclick="Timesheet.updateSign(this)" class="timesheet-time-field-sign btn btn-primary" data-sign="+" title="'.s("Retirer").'">'.s("Ajouter").'</a>';
							$h .= \Asset::icon('arrow-right');
							$h .= '<div class="timesheet-time-field-group">';
								$h .= $form->text('timeAddHour', attributes: ['placeholder' => '--', 'min' => 0, 'max' => 23, 'pattern' => '[0-9]{0,2}']);
								$h .= '<div class="timesheet-time-field-hour">h</div>';
								$h .= $form->text('timeAddMinute', attributes: ['placeholder' => '--', 'min' => 0, 'max' => 59, 'pattern' => '[0-9]{0,2}']);
							$h .= '</div>';
						$h .= '</div>';

					$h .= '</div>';

					return $h;

				};
				break;

			case 'date' :
				$d->default = date('Y-m-d', date('G') <= 6 ? strtotime('yesterday') : time());
				$d->attributes = [
					'max' => currentDate(),
					'oninput' => 'Task.toggleDistributionHarvestTable()'
				];
				break;

		}

		return $d;

	}

}
?>
