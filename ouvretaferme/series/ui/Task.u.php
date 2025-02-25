<?php
namespace series;

class TaskUi {

	protected ?string $period = NULL;
	protected ?\Collection $cUserFarm = NULL;

	public function __construct() {

		\Asset::css('production', 'flow.css');

		\Asset::css('series', 'task.css');
		\Asset::js('series', 'task.js');

	}

	public static function url(Task $eTask): string {
		return '/tache/'.$eTask['id'];
	}

	public static function getPanelHeader(Task $eTask): string {

		$eTask->expects([
			'action' => ['name']
		]);

		return '<div class="panel-header-subtitle">'.encode($eTask['action']['name']).'</div>';

	}

	public function getDayPlanning(\farm\Farm $eFarm, string $week, \Collection $cccTask, \Collection $cccTaskAssign, \Collection $cUserFarm, \user\User $eUser, array $seasonsWithSeries, \Collection $cCategory): string {

		$this->period = 'day';
		$this->cUserFarm = $cUserFarm;

		\Asset::css('series', 'planning.css');

		$h = '<div id="tasks-time" class="tasks-time-day container">';
			$h .= $this->getWeekUsers($eFarm, $week, $eUser, $cUserFarm, fn($eUserFarm) => 'data-ajax="'.\farm\FarmUi::urlPlanningDaily($eFarm, $week).'?user'.($eUserFarm->notEmpty() ? '='.$eUserFarm['id'] : '').'" data-ajax-method="get"', team: TRUE);
		$h .= '</div>';

		$form = new \util\FormUi();

		if($week === currentWeek()) {
			$position = (int)date('N') + 1;
		} else {
			$position = 1;
		}

		$h .= '<div id="planning-container-daily" onrender="Task.scrollPlanningDaily(this, '.$position.')" data-week="'.$week.'" data-farm="'.$eFarm['id'].'">';

			$h .= '<div id="planning-wrapper-daily">';

				$h .= '<div class="planning-daily" data-planning-scroll="0">';

					$h .= '<div class="planning-daily-header">';

						$h .= '<div>';
							$h .= '<h2>';
								$h .= s("À assigner cette semaine");
							$h .= '</h2>';
						$h .= '</div>';
						$h .= $this->getNewTask('daily', 'todo', $eFarm, $seasonsWithSeries, $cCategory, week: $week);

					$h .= '</div>';

					$h .= $this->getTodoPlanning($form, $eFarm, $week, $cccTaskAssign);

				$h .= '</div>';

			$eUserSelected = $eUser->notEmpty() ? ($cUserFarm[$eUser['id']] ?? new \user\User()) : $eUser;
			$day = 0;

			foreach(week_dates($week) as $date) {

				$day++;
				$timestamp = strtotime($date);
				$cUserAbsent = $cUserFarm->find(fn($eUserFarm) => \hr\Absence::isDateAbsent($eUserFarm['cAbsence'], $date)->notEmpty());

				$h .= '<div class="planning-daily '.($date === currentDate() ? 'planning-daily-today' : '').'" data-planning-scroll="0">';

					$h .= '<div class="planning-daily-header">';

						$h .= '<div>';
							$h .= '<h2>';
								$h .= \util\DateUi::getDayName($day);
								$h .= ' <small>'.date('j', $timestamp).' / '.date('m', $timestamp).'</small>';
							$h .= '</h2>';
							if($eUserSelected->notEmpty()) {
								if(\hr\Absence::isDateAbsent($eUserSelected['cAbsence'], $date)->notEmpty()) {
									$h .= '<div class="color-danger">'.\Asset::icon('exclamation-triangle-fill').' '.s("Absent").'</div>';
								}
							}
						$h .= '</div>';

						if($eUserSelected->notEmpty()) {

							$h .= '<div class="tasks-time-day-values">';
								if($eFarm->hasFeatureTime()) {
									$h .= $this->getDayWorkingTime($eFarm, $eUserSelected, $date, $eUserSelected['workingTime'][$date], $eUserSelected['timesheetTime'][$date]);
									if($eUserSelected['timesheetTime']) {
											$h .= $this->getDayTimesheetTime($eUserSelected['workingTime'][$date], $eUserSelected['timesheetTime'][$date]);
									}
								}
							$h .= '</div>';

						} else {
							$h .= $this->getNewTask('daily', $date >= currentDate() ? 'todo' : 'done', $eFarm, $seasonsWithSeries, $cCategory, date: $date);
						}

					$h .= '</div>';

					if(
						$eUserSelected->empty() and
						$cUserAbsent->notEmpty()
					) {

						$h .= '<div class="planning-daily-absent">';
							foreach($cUserAbsent as $eUserAbsent) {
								$h .= '<div class="color-danger">'.\user\UserUi::getVignette($eUserAbsent, '1.5rem').'  '.\Asset::icon('exclamation-triangle-fill').' '.s("Absent").'</div>';
							}
						$h .= '</div>';

					} else {
						$h .= '<div></div>';
					}

					$ccTask = $cccTask[$date];

					if($ccTask->notEmpty()) {

						$cTaskFirst = $ccTask->first();
						$cTaskLast = $ccTask->last();

						foreach($ccTask as $cTask) {
							$h .= $this->getPlanningTasks($form, $eFarm, $cTask, $cTask === $cTaskFirst, $cTask === $cTaskLast, $date, $week, TRUE);
						}

					} else {

						$h .= '<div class="tasks-planning-items tasks-planning-items-first tasks-planning-items-last util-info">';
							$h .= s("Aucune intervention ce jour.");
						$h .= '</div>';

					}

				$h .= '</div>';

			}

			$h .= $this->getBatch($eFarm, $week, $cUserFarm);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getWeekCalendar(string $week, \Closure $link, ?\Closure $filter = NULL): string {

		$weekBefore = date('o-\WW', strtotime($week.' - 1 WEEK'));
		$weekAfter = date('o-\WW', strtotime($week.' + 1 WEEK'));

		$h = '<div id="tasks-calendar-top" class="tasks-calendar tasks-calendar-week '.($filter ? 'tasks-calendar-with-filter' : '').'">';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
				$h .= '</div>';
			}

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-before">';
				$h .= '<a href="'.$link($weekBefore).'">';
					$h .= \Asset::icon('chevron-left');
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-title">';
				$h .= '<h1>';
					$h .= '<a class="dropdown-toggle" data-dropdown="bottom-center">';
						$h .= s("Semaine {week}, {year}", ['week' => week_number($week), 'year' => week_year($week)]);
					$h .= '</a>';
					$h .= '<div class="dropdown-list dropdown-list-minimalist">';
						$h .= \util\FormUi::weekSelector((string)substr($week, 0, 4), $link('{current}'), defaultWeek: $week);
					$h .= '</div>';
				$h .= '</h1>';
				$h .= '<div class="tasks-calendar-title-period">';
					$h .= \util\DateUi::weekToDays($week);
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-after">';
				$h .= '<a href="'.$link($weekAfter).'">';
					$h .= \Asset::icon('chevron-right');
				$h .= '</a>';
			$h .= '</div>';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
					$h .= $filter();
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getWeekPlanning(\farm\Farm $eFarm, string $week, \Collection $cccTask, \Collection $cUserFarm, \user\User $eUserTime, array $seasonsWithSeries, \Collection $cActionMain, \Collection $cCategory): string {

		$this->period = 'week';
		$this->cUserFarm = $cUserFarm;

		\Asset::css('series', 'planning.css');

		$h = '<div id="planning-week-tabs" class="tabs-h" data-farm="'.$eFarm['id'].'" data-week="'.$week.'" onrender="'.encode('Lime.Tab.restore(this, "todo")').'">';

			$h .= '<div class="tabs-item util-print-hide">';
				$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="todo">'.s("À faire").'</a>';
				$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="done">'.s("Fait").'</a>';
				$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="harvested">'.s("Récolté").'</a>';
				if($eFarm->hasFeatureTime()) {
					$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="time">';
						$h .= '<span class="hide-sm-up">'.\Asset::icon('clock').'&nbsp;&nbsp;'.s("Travaillé").'</span>';
						$h .= '<span class="hide-xs-down">'.\Asset::icon('clock').'&nbsp;&nbsp;'.s("Temps de travail").'</span>';
					$h .= '</a>';
				}
			$h .= '</div>';

			$form = new \util\FormUi();

			$canCreate = (new Task(['farm' => $eFarm]))->canCreate();

			$h .= '<div id="planning-wrapper-weekly">';

				$h .= '<div id="tasks-time" class="tab-panel util-print-hide" data-tab="time">';
					$h .= $this->getWeekTime($eFarm, $week, $cccTask, $eUserTime, $cUserFarm);
				$h .= '</div>';

				$h .= '<div id="tasks-todo" class="tab-panel planning-week util-print-block" data-tab="todo">';

					$h .= '<div class="planning-week-header">';

						$h .= '<h2 class="planning-week-title util-print-block">'.s("À faire").'</h2>';
						$h .= '<div class="tasks-action">';
							if($canCreate) {
								$h .= $this->getNewTask('weekly', 'todo', $eFarm, $seasonsWithSeries, $cCategory, week: $week);
							}
						$h .= '</div>';

					$h .= '</div>';

					$h .= $this->getTodoPlanning($form, $eFarm, $week, $cccTask);

				$h .= '</div>';

				$h .= '<div id="tasks-done" class="tab-panel planning-week util-print-block" data-tab="done">';

					$h .= '<div class="planning-week-header">';

						$h .= '<h2 class="planning-week-title util-print-block">'.s("Fait").'</h2>';
						$h .= '<div class="tasks-action">';
							if($canCreate) {
								$h .= $this->getNewTask('weekly', 'done', $eFarm, $seasonsWithSeries, $cCategory, week: $week);
							}
						$h .= '</div>';

					$h .= '</div>';


					if($cccTask['done']->empty() === FALSE) {

						$cTaskFirst = $cccTask['done']->first();
						$cTaskLast = $cccTask['done']->last();
						foreach($cccTask['done'] as $cTask) {
							$h .= $this->getPlanningTasks($form, $eFarm, $cTask, $cTask === $cTaskFirst, $cTask === $cTaskLast, NULL, $week);
						}

					}

				$h .= '</div>';

				$h .= '<div id="tasks-harvested" class="tab-panel planning-week util-print-block" data-tab="harvested">';

					$h .= '<div class="planning-week-header">';

						$h .= '<h2 class="planning-week-title util-print-block">'.s("Récolté").'</h2>';
						$h .= '<div class="tasks-action">';
							if($canCreate) {
								$h .= $this->getNewHarvest($eFarm, $week, $seasonsWithSeries, $cActionMain[ACTION_RECOLTE]);
							}
						$h .= '</div>';

					$h .= '</div>';


					if($cccTask['harvested']->empty() === FALSE) {
						$h .= '<div class="tasks-planning-items tasks-planning-items-first tasks-planning-items-last" data-filter-action="'.$cActionMain[ACTION_RECOLTE]['id'].'">';
							$h .= $this->getPlantPlanningTask($form, $eFarm, $cccTask['harvested'], NULL, $week);
						$h .= '</div>';
					}

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $week, $cUserFarm);

		return $h;

	}

	protected function getTodoPlanning(\util\FormUi $form, \farm\Farm $eFarm, string $week, \Collection $cccTask) {

		$h = '';

		if($cccTask['todo']->empty() === FALSE) {


				$cTaskFirst = $cccTask['todo']->first();
				$cTaskLast = $cccTask['todo']->last();
				foreach($cccTask['todo'] as $cTask) {
					$h .= $this->getPlanningTasks($form, $eFarm, $cTask, $cTask === $cTaskFirst, $cTask === $cTaskLast, NULL, $week);
				}

		}

		if($cccTask['delayed']->empty() === FALSE) {

				$h .= '<div class="planning-week-title planning-week-title-container">';
					$h .= '<div>'.s("Retardé").'</div>';
					$h .= '<div class="planning-week-title-action">';
						$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle">'.p("depuis moins de {value} mois", "depuis moins de {value} mois", $eFarm['planningDelayedMax']).'</a>';
						$h .= '<div class="dropdown-list bg-todo">';
							$h .= '<div class="dropdown-title">'.s("N'afficher que les interventions retardées").'</div>';
							$h .= '<a data-ajax="/farm/farm:doUpdatePlanningDelayedMax" post-id="'.$eFarm['id'].'" post-planning-delayed-max="1" class="dropdown-item">'.s("depuis moins de 1 mois").'</a>';
							$h .= '<a data-ajax="/farm/farm:doUpdatePlanningDelayedMax" post-id="'.$eFarm['id'].'" post-planning-delayed-max="3" class="dropdown-item">'.s("depuis moins de 3 mois").'</a>';
							$h .= '<a data-ajax="/farm/farm:doUpdatePlanningDelayedMax" post-id="'.$eFarm['id'].'" post-planning-delayed-max="6" class="dropdown-item">'.s("depuis moins de 6 mois").'</a>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

				$cTaskFirst = $cccTask['delayed']->first();
				$cTaskLast = $cccTask['delayed']->last();
				foreach($cccTask['delayed'] as $cTask) {
					$h .= $this->getPlanningTasks($form, $eFarm, $cTask, $cTask === $cTaskFirst, $cTask === $cTaskLast, NULL, $week);
				}

		}

		if($cccTask['unplanned']->empty() === FALSE) {

				$h .= '<div class="planning-week-title">';
					$h .= s("Non planifié");
				$h .= '</div>';

				$cTaskFirst = $cccTask['unplanned']->first();
				$cTaskLast = $cccTask['unplanned']->last();
				foreach($cccTask['unplanned'] as $cTask) {
					$h .= $this->getPlanningTasks($form, $eFarm, $cTask, $cTask === $cTaskFirst, $cTask === $cTaskLast, NULL, $week);
				}

		}

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, ?string $week = NULL, \Collection $cUser = new \Collection()): string {

		$menu = '';

		if($eFarm->canTask()) {
			$menu .= '<a data-ajax-submit="/series/task:updateHarvestCollection" data-ajax-method="get" class="batch-one-item batch-menu-harvest" title="'.s("Compléter la récolte").'">'.\Asset::icon('basket2').'<span>'.s("Récolte").'</span></a>';
		}
		if($eFarm->canTask()) {
			$menu .= '<a data-ajax-submit="/series/task:doUpdateTodoCollection" class="batch-one-item batch-menu-todo" data-confirm="'.s("Annuler la réalisation de cette intervention ?").'">'.\Asset::icon('arrow-up-left').'<span>'.s("À faire").'</span></a>';
		}
		if($eFarm->canTask()) {

			$menu .= '<div class="batch-menu-planned">';
				$menu .= '<a data-dropdown="top-start" class="batch-one-item">';
					$menu .= \Asset::icon('watch');
					$menu .= '<span>'.s("Planifier").'</span>';
				$menu .= '</a>';
				$menu .= $this->getBatchPlanned('batch-one-form', $week);
			$menu .= '</div>';

			if($cUser->count() > 1) {
				$menu .= '<div class="batch-menu-users">';
					$menu .= '<a data-dropdown="top-start" class="batch-one-item">';
						$menu .= \Asset::icon('people-fill');
						$menu .= '<span>'.s("Affecter").'</span>';
					$menu .= '</a>';
					$menu .= $this->getBatchUsers('batch-one-form', $cUser);
				$menu .= '</div>';
			}


		}
		if($eFarm->canWork()) {
			$menu .= '<a data-ajax-submit="/series/comment:createCollection" data-ajax-method="get" class="batch-one-item">'.\Asset::icon('chat-dots-fill').'<span>'.s("Commenter").'</span></a>';
		}
		if($eFarm->canTask()) {
			$menu .= '<a class="batch-one-item batch-menu-update">'.\Asset::icon('gear-fill').'<span>'.s("Modifier").'</span></a>';
		}
		if($eFarm->canTask()) {
			$menu .= '<a data-ajax-submit="/series/task:doDeleteCollection" class="batch-one-item" data-confirm="'.s("Confirmer la suppression de cette intervention ?").'">'.\Asset::icon('trash').'<span>'.s("Supprimer").'</span></a>';
		}

		$h = \util\BatchUi::one($menu);

		$menu = '';
		
		if(
			$eFarm->hasFeatureTime() and
			$eFarm->canWork()
		) {

			$menu .= '<a data-ajax-submit="/series/timesheet" data-ajax-method="get" class="batch-menu-item batch-menu-timesheet">';
				$menu .= \Asset::icon('clock');
				$menu .= '<span>'.s("Temps de travail").'</span>';
			$menu .= '</a>';

		}

		if($eFarm->canTask()) {

			$menu .= '<a data-ajax-submit="/series/task:updateHarvestCollection" data-ajax-method="get" class="batch-menu-item batch-menu-harvest">';
				$menu .= \Asset::icon('basket2');
				$menu .= '<span>'.s("Récolte").'</span>';
			$menu .= '</a>';

		}

		if($eFarm->canTask()) {

			$menu .= '<a data-ajax-submit="/series/task:doUpdateDoneCollection" post-done-week="'.$week.'" class="batch-menu-item batch-menu-done">';
				$menu .= \Asset::icon('check-lg');
				$menu .= '<span>'.s("Fait !").'</span>';
			$menu .= '</a>';

			$menu .= '<a data-ajax-submit="/series/task:doUpdateTodoCollection" data-confirm="'.s("Annuler la réalisation des interventions ?").'" class="batch-menu-item batch-menu-todo">';
				$menu .= \Asset::icon('arrow-up-left');
				$menu .= '<span>'.s("À faire").'</span>';
			$menu .= '</a>';

		}

		if($eFarm->canTask()) {

			$menu .= '<div class="batch-menu-planned">';
				$menu .= '<a data-dropdown="top-start" class="batch-menu-item">';
					$menu .= \Asset::icon('watch');
					$menu .= '<span>'.s("Planifier").'</span>';
				$menu .= '</a>';
				$menu .= $this->getBatchPlanned('batch-group-form', $week);
			$menu .= '</div>';

			if($cUser->count() > 1) {
				$menu .= '<div class="batch-menu-users">';
					$menu .= '<a data-dropdown="top-start" class="batch-menu-item">';
						$menu .= \Asset::icon('people-fill');
						$menu .= '<span>'.s("Affecter").'</span>';
					$menu .= '</a>';
					$menu .= $this->getBatchUsers('batch-group-form', $cUser);
				$menu .= '</div>';
			}

		}

		if($eFarm->canWork()) {

			$menu .= '<a data-ajax-submit="/series/comment:createCollection" data-ajax-method="get" class="batch-menu-item">';
				$menu .= \Asset::icon('chat-dots-fill');
				$menu .= '<span>'.s("Commenter").'</span>';
			$menu .= '</a>';

		}

		if($eFarm->canTask()) {

			$danger = '<a data-ajax-submit="/series/task:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces interventions ?").'" class="batch-menu-item batch-menu-item-danger">';
				$danger .= \Asset::icon('trash');
				$danger .= '<span>'.s("Supprimer").'</span>';
			$danger .= '</a>';

		} else {
			$danger = NULL;
		}
		
		$h .= \util\BatchUi::group($menu, $danger, title: s("Pour les interventions sélectionnées"), hide: 'Task.hidePlanningSelection()');

		return $h;

	}

	protected function getBatchUsers(string $formId, \Collection $cUser) {

		$h = '<div class="dropdown-list bg-secondary">';
			$h .= '<div class="dropdown-title">'.s("Affecter à...").'</div>';
			foreach($cUser as $eUser) {
				$h .= '<a data-ajax-submit="/series/task:doUpdateUserCollection" data-ajax-target="#'.$formId.'" post-user="'.$eUser['id'].'" post-reload="context" class="batch-planned-user dropdown-item">';
					$h .= \Asset::icon('plus-lg');
					$h .= \Asset::icon('x-lg');
					$h .= '  '.\user\UserUi::getVignette($eUser, '1.5rem').'  '.$eUser->getName();
				$h .= '</a>';
			}
		$h .= '</div>';

		return $h;

	}

	protected function getBatchPlanned(string $formId, ?string $week) {

		$h = '<div class="dropdown-list bg-secondary">';
			if($week !== NULL) {
				$h .= '<div class="dropdown-title">'.s("Planifier un jour précis").'</div>';
				$h .= '<div class="batch-planned-days">';
					foreach(week_dates($week) as $day => $date) {
						$h .= '<a data-ajax-submit="/series/task:doUpdatePlannedDateCollection" data-ajax-target="#'.$formId.'" post-planned-date="'.$date.'" class="dropdown-item">';
							$h .= '<div>'.\util\DateUi::getDayName($day + 1).'</div>';
							$h .= '<div class="batch-planned-days-date">'.\util\DateUi::numeric($date, \util\DateUi::DAY_MONTH).'</div>';
						$h .= '</a>';
					}
				$h .= '</div>';
				$h .= '<div class="dropdown-title">'.s("Planifier à la semaine").'</div>';
			} else {
				$h .= '<div class="dropdown-title">'.s("Planifier").'</div>';
			}
			$h .= '<div class="batch-planned-weeks">';
				$h .= '<a data-ajax-submit="/series/task:doUpdatePlannedCollection" data-ajax-target="#'.$formId.'" post-planned-week="'.currentWeek().'" class="dropdown-item batch-menu-planned-other">';
					$h .= '<div>'.s("Cette semaine").'</div>';
					$h .= '<div class="batch-planned-days-date">'.s("Semaine {week}, {year}", ['week' => week_number(currentWeek()), 'year' => week_year(currentWeek())]).'</div>';
				$h .= '</a>';
				$h .= '<a data-ajax-submit="/series/task:updatePlannedCollection" data-ajax-method="get" data-ajax-target="#'.$formId.'" class="dropdown-item batch-menu-planned-other">';
					$h .= '<div>'.s("Une autre semaine").'</div>';
				$h .= '</a>';
			$h .= '</div>';
			$h .= '<a data-ajax-submit="/series/task:doIncrementPlannedCollection" data-ajax-target="#'.$formId.'" class="dropdown-item batch-menu-postpone" post-increment="-1">'.s("Décaler une semaine plus tôt").'</a>';
			$h .= '<a data-ajax-submit="/series/task:doIncrementPlannedCollection" data-ajax-target="#'.$formId.'" class="dropdown-item batch-menu-postpone" post-increment="1">'.s("Décaler une semaine plus tard").'</a>';
			$h .= '<a data-ajax-submit="/series/task:incrementPlannedCollection" data-ajax-method="get" data-ajax-target="#'.$formId.'" class="dropdown-item batch-menu-postpone">'.s("Décaler davantage").'</a>';
		$h .= '</div>';

		return $h;

	}

	public function getWeekUsers(\farm\Farm $eFarm, string $week, \user\User $eUser, \Collection $cUserFarm, \Closure $link, bool $team = FALSE): string {

		$cUserPresent = $cUserFarm->find(fn($eUserFarm) => \hr\Presence::isWeekPresent($eUserFarm['cPresence'], $week)->notEmpty());

		if($cUserPresent->count() === 1) {
			return '<br/>';
		}

		if($eFarm->canManage()) {

			$h = '<div class="tabs-item">';

				if($team and $cUserPresent->count() >= 2) {

					$h .= '<div '.$link(new \user\User()).' class="tab-item '.($eUser->empty() ? 'selected' : '').' tasks-time-all '.($cUserPresent->count() >= 10 ? 'tasks-time-all-condensed' : '').'" title="'.s("Toute l'équipe").'">';
						foreach($cUserPresent as $eUserPresent) {
							$h .= \user\UserUi::getVignette($eUserPresent, '2rem');
						}
					$h .= '</div>';

				}

				foreach($cUserPresent as $eUserPresent) {

					$h .= '<a '.$link($eUserPresent).' class="tab-item '.(($eUser->notEmpty() and $eUserPresent['id'] === $eUser['id']) ? 'selected' : '').'" title="'.$eUserPresent->getName().'">';
						$h .= \user\UserUi::getVignette($eUserPresent, '2rem');
					$h .= '</a>';

				}

			$h .= '</div>';

			return $h;

		} else {
			return '<br/>';
		}
		
	}

	public function getWeekTime(\farm\Farm $eFarm, string $week, \Collection $ccTask, \user\User $eUser, \Collection $cUserFarm) {

		if($eFarm->hasFeatureTime() === FALSE) {
			return '';
		}

		$eUser->expects(['weekTimesheet', 'cWorkingTimeWeek']);

		$tabItem = $this->getWeekUsers($eFarm, $week, $eUser, $cUserFarm, fn($eUserFarm) => 'data-ajax="/hr/workingTime:getByUser" post-farm="'.$eFarm['id'].'" post-week="'.$week.'" post-user="'.($eUserFarm->notEmpty() ? $eUserFarm['id'] : '').'"');

		$tasks  = [];

		$estimated =
			($ccTask->offsetExists('todo') ? $ccTask['todo']->sum(function($e) use (&$tasks) {
				$tasks[$e['id']] = $e['timeExpected'];
				return $e['timeExpected'];
			}, 2) : 0) +
			($ccTask->offsetExists('done') ? $ccTask['done']->sum(fn($e) => array_key_exists($e['id'], $tasks) ? 0 : $e['timeExpected'], 2) : 0);

		if($eFarm->hasFeatureTime() and $estimated > 0) {
			$tabEstimated = '<div class="tabs-item-label" title="'.s("Inclus le temps de travail estimé sur les tâches planifiées et réalisées cette semaine.").'">';
				$tabEstimated .= s("Travail estimé à {value} sur la semaine", self::convertTime($estimated));
			$tabEstimated .= '</div>';
		} else {
			$tabEstimated = '';
		}

		$h = '';

		if($tabEstimated) {

			$h .= '<div class="tabs-item-wrapper">';
				$h .= $tabItem;
				$h .= $tabEstimated;
			$h .= '</div>';

		} else {
			$h .= $tabItem;
		}

		$h .= '<div class="tasks-time-content">';

			if($eUser['cPresence']->empty()) {
				$h .= s("Vous ne travaillez pas à la ferme cette semaine !");
			} else {

				$h .= '<div class="tasks-time-days">';

					for($i = 1; $i <= 7; $i++) {

						$date = date('Y-m-d', strtotime($week.' + '.($i - 1).' DAY'));
						$future = (strcmp($date, currentDate()) > 0);

						$timeTimesheet = $eUser['weekTimesheet'][$date] ?? NULL;
						$timeWorking = $eUser['cWorkingTimeWeek'][$date]['time'] ?? NULL;

						$h .= '<div class="tasks-time-day '.($future ? 'tasks-time-day-future' : '').'">';
							$h .= '<h5>';
								$h .= \util\DateUi::getDayName($i);
								$h .= '<span class="tasks-time-day-numeric">'.\util\DateUi::numeric($date, \util\DateUi::DAY_MONTH).'</span>';
							$h .= '</h5>';
							$h .= '<div class="tasks-time-day-values">';
								$h .= $this->getDayWorkingTime($eFarm, $eUser, $date, $timeWorking, $timeTimesheet);
								$h .= $this->getDayTimesheetTime($timeWorking, $timeTimesheet);
							$h .= '</div>';

							if(
								\hr\Presence::isDatePresent($eUser['cPresence'], $date)->empty() or
								\hr\Absence::isDateAbsent($eUser['cAbsence'], $date)->notEmpty()
							) {
								$h .= '<div class="tasks-time-day-absent">';
									$h .= \Asset::icon('exclamation-triangle-fill').'&nbsp;&nbsp;'.s("Absent");
								$h .= '</div>';
							}

						$h .= '</div>';


					}

					$h .= \Asset::icon('chevron-right');

					$sumTimeWorking = array_sum($eUser['cWorkingTimeWeek']->getColumn('time'));
					$future = (strcmp($week, currentWeek()) > 0);

					$h .= '<div class="tasks-time-day tasks-time-day-global '.($future ? 'tasks-time-day-future' : '').'">';
						$h .= '<h5>';
							$h .= s("Semaine");
							$h .= '<span class="tasks-time-day-numeric">&nbsp;</span>';
						$h .= '</h5>';
						$h .= '<div class="tasks-time-day-values">';
							$h .= '<div class="tasks-time-day-full" title="'.s("Temps de travail réel sur la semaine").'">';
								if($eUser['cWorkingTimeWeek']->notEmpty()) {
									$h .= self::convertTime($sumTimeWorking);

								}
							$h .= '</div>';
							if($eUser['weekTimesheet']) {

								$sumTimeTimesheet = round(array_sum($eUser['weekTimesheet']), 2);

								$alert = ($sumTimeWorking > 0 and $sumTimeTimesheet > $sumTimeWorking);
								$title = $alert ? s("Le temps de travail sur les interventions excède sur le temps de travail réel renseigné pour cette semaine !") : s("Temps de travail sur les interventions de la semaine");

								$h .= '<div class="tasks-time-day-timesheet '.($alert ? 'tasks-time-day-timesheet-alert' : '').'" title="'.$title.'">';
									$h .= \Asset::icon('calendar3').'&nbsp;&nbsp;'.self::convertTime($sumTimeTimesheet);
								$h .= '</div>';

							}
						$h .= '</div>';
					$h .= '</div>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getDayTimesheetTime(?float $timeWorking, ?float $timeTimesheet): string {
		
		if($timeTimesheet === NULL) {
			return '';
		}

		$alert = ($timeWorking > 0.0 and round($timeTimesheet, 2) > round($timeWorking, 2));
		$title = $alert ? s("Le temps de travail sur les interventions excède sur le temps de travail réel renseigné pour ce jour !") : s("Temps de travail sur les interventions");

		$h = '<div class="tasks-time-day-timesheet '.($alert ? 'tasks-time-day-timesheet-alert' : '').'" title="'.$title.'">';
			$h .= \Asset::icon('calendar3').'&nbsp;&nbsp;'.$this->convertTime($timeTimesheet);
		$h .= '</div>';

		return $h;

	}

	protected function getDayWorkingTime(\farm\Farm $eFarm, \user\User $eUser, string $date, ?float $timeWorking, ?float $timeTimesheet): string {

		$h = '';

		if($eFarm->canManage() or $eUser->isOnline()) {

			$future = (strcmp($date, currentDate()) > 0);
			$inconsistency = (
				$timeWorking > 0.0 and
				round($timeWorking, 2) < round($timeTimesheet ?? 0.0, 2)
			);

			$h .= '<div class="tasks-time-day-full '.($inconsistency ? 'bg-danger' : '').'">';

				$h .= '<a data-dropdown="bottom-start" class="tasks-time-day-full-link" title="'.($timeWorking !== NULL ? s("Temps de travail réel") : s("Renseigner le temps de travail total du jour")).'">';
					$h .= ($timeWorking > 0.0 ? self::convertTime($timeWorking) : ($future ? '' : \Asset::icon('pencil-fill')));
				$h .= '</a>';

				$h .= '<div class="dropdown-list dropdown-list-minimalist" data-dropdown-keep>';

					$h .= '<div class="tasks-time-day-full-form">';

						$h .= '<h5>';
							$h .= \util\DateUi::getDayName(date('N', strtotime($date)));
						$h .= '</h5>';

						$form = new \util\FormUi();

						$h .= $form->openAjax('/hr/workingTime:doCreate');
							$h .= $form->hidden('farm', $eFarm['id']);
							$h .= $form->hidden('date', $date);
							$h .= $form->hidden('user', $eUser['id']);
							$h .= $form->dynamicField(new \hr\WorkingTime(['time' => $timeWorking]), 'time');
							$h .= '<div class="form-buttons">';
								$h .= $form->submit(s("Ok"), ['class' => 'btn btn-sm btn-primary']);
								$h .= $form->button(s("Annuler"), ['class' => 'btn btn-sm btn-outline-primary', 'data-dropdown' => 'close']);
							$h .= '</div>';
						$h .= $form->close();

					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';

		} else {
			$h .= $timeWorking;
		}

		return $h;

	}

	public function getYearCalendar(\farm\Farm $eFarm, int $year, ?\Closure $filter = NULL): string {

		$yearBefore = $year - 1;
		$yearAfter = $year + 1;

		$h = '<div id="tasks-calendar-top" class="container tasks-calendar '.($filter ? 'tasks-calendar-with-filter' : '').' tasks-calendar-year">';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search"></div>';
			}


			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-before">';
				$h .= '<a href="'.\farm\FarmUi::urlPlanningYear($eFarm, $yearBefore, 12).'">';
					$h .= \Asset::icon('chevron-left');
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-title">';
				$h .= '<h1>'.$year.'</h1>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-after">';
				$h .= '<a href="'.\farm\FarmUi::urlPlanningYear($eFarm, $yearAfter, 1).'">';
					$h .= \Asset::icon('chevron-right');
				$h .= '</a>';
			$h .= '</div>';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
					$h .= $filter();
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getYearSearch(\farm\Farm $eFarm, int $year, ?\Closure $search = NULL): string {

		$h = '';

		if($search !== NULL) {
			$h .= $search();
		}

		$h .= '<div id="tasks-calendar-months-wrapper">';

			$h .= '<div class="container" id="tasks-calendar-months" data-url="'.\farm\FarmUi::urlPlanningYear($eFarm).'">';

				for($month = 1; $month <= 12; $month++) {

					$h .= '<a '.attr('onclick', 'Task.clickPlanningMonth('.$year.', '.$month.')').' data-month="'.$month.'" class="tasks-calendar-month">';
						$h .= '<span class="hide-sm-down">'.\util\DateUi::getMonthName($month).'</span>';
						$h .= '<span class="hide-md-up">'.\util\DateUi::getMonthName($month, type: 'short').'</span>';
					$h .= '</a>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getYearMonths(int $year, int $month, \Collection $ccTask): string {

		\Asset::css('series', 'planning.css');

		$weeks = date('W', strtotime($year.'-12-31')) === '53' ? 53 : 52;

		$h = '<div id="planning-year-wrapper" '.attr('onrender', 'Task.selectPlanningMonth('.$year.', '.$month.')').'>';

			$h .= '<div id="planning-year-weeks">';

				for($weekNumber = 1; $weekNumber <= $weeks; $weekNumber++) {

					$week = $year.'-W'.sprintf('%02d', $weekNumber);
					$cTask = $ccTask[$week] ?? new \Collection();

					$months = [
						date('Y-n', strtotime($week)),
						date('Y-n', strtotime($week.' + 6 days'))
					];

					$h .= '<div class="planning-year-week';
						if($week === currentWeek()) {
							$h .= ' planning-year-week-current';
						}
					$h .= '" data-planning-scroll="0" data-months="'.implode(' ', $months).'">';

						$h .= '<h2 class="planning-year-week-title">';
							$h .= s("Semaine {value}", week_number($week));
						$h .= '</h2>';
						$h .= '<div class="planning-year-week-days">';
							$h .= \util\DateUi::weekToDays($week);
						$h .= '</div>';

						$h .= '<div class="planning-year-week-items">';

							foreach($cTask as $eTask) {
								$h .= $this->getYearTask($eTask);
							}

						$h .= '</div>';

					$h .= '</div>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getCalendarFilter(): string {

		$h = ' <a '.attr('onclick', 'Lime.Search.toggle("#planning-search")').' class="btn btn-primary">';
			$h .= \Asset::icon('search');
		$h .= '</a>';

		return $h;

	}

	public function getWeekSearch(\farm\Farm $eFarm, \Search $search, \Collection $cAction, \Collection $cZone, \Collection $cUserFarm): string {

		$form = new \util\FormUi();

		$h = '<div id="planning-search" class="util-block-search stick-xs '.($search->empty() ? 'hide' : '').'">';

			$h .= $form->openAjax(LIME_REQUEST_PATH, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					$h .= $form->hidden('search', 1);

					if($cUserFarm->count() > 1) {
						$h .= $form->select('farmer', $cUserFarm->toArray(fn($eUserFarm) => [$eUserFarm['id'], $eUserFarm['firstName'].' '.$eUserFarm['lastName']], keys: TRUE), $search->get('farmer'), ['placeholder' => s("Affecté à")]);
					}

					if($cAction->notEmpty()) {
						$h .= $form->select('action', $cAction, $search->get('action'), ['placeholder' => s("Intervention")]);
					}
					$h .= $form->dynamicField(new \plant\Plant([
						'farm' => $eFarm
					]), 'id', function($d) use ($search) {
						$d->name = 'plant';
						$d->autocompleteDefault = $search->get('plant');
					});
					if($cZone->notEmpty()) {
						$h .= (new \map\ZoneUi())->getZonePlotWidget($form, $cZone, $search->get('plot') ?? new \map\Plot(), s("Emplacement"));
					}

				$h .= '</div>';
				$h .= '<div>';

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.LIME_REQUEST_PATH.'?search" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	protected function getNewTask(string $source, string $status, \farm\Farm $eFarm, array $seasonsWithSeries, \Collection $cCategory, ?string $week = NULL, ?string $date = NULL) {

		$post = [
			'farm' => $eFarm['id'],
		];

		switch($status) {
			case 'todo' :
				$background = ($source === 'weekly') ? 'bg-todo' : '';
				$post['plannedWeek'] = $week;
				$post['plannedDate'] = $date;
				$post['status'] = Task::TODO;
				break;
			case 'done' :
				$background = ($source === 'weekly') ? 'bg-done' : '';
				$post['doneWeek'] = $week;
				$post['doneDate'] = $date;
				$post['status'] = Task::DONE;
				break;
		}

		$button = ($source === 'weekly') ? 'btn-transparent' : 'btn-secondary';
		$position = ($source === 'weekly') ? 'bottom-start' : 'bottom-end';

		$h = '<div>';
			$h .= '<a class="dropdown-toggle btn '.$button.'" data-dropdown="'.$position.'">';
				$h .= \Asset::icon('plus-circle');
				if($source === 'weekly') {
					$h .= ' '.s("Nouvelle intervention");
				}
			$h .= '</a>';
			$h .= '<div class="dropdown-list '.$background.'">';

			if($source === 'daily') {
				$h .= '<div class="dropdown-title">';
					$h .= ($status === 'todo') ? s("Planifier une intervention") : s("Nouvelle intervention");
				$h .= '</div>';
			}

			if($seasonsWithSeries) {

				$h .= '<div class="dropdown-title">';
					$h .= s("Dans une série");
				$h .= '</div>';

				foreach($seasonsWithSeries as $season) {

					$postSeason = $post;

					$h .= '<a href="/series/task:createFromSeries?season='.$season.'&'.http_build_query($postSeason).'" class="dropdown-item">';
						$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.s("Saison {value}", $season);
					$h .= '</a>';

				}

				$h .= '<div class="dropdown-title">';
					$h .= s("Intervention hors série");
				$h .= '</div>';

			}

			foreach($cCategory as $eCategory) {

				$h .= '<a href="/series/task:createFromScratch?category='.$eCategory['id'].'&'.http_build_query($post).'" class="dropdown-item">';
					$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.encode($eCategory['name']);
				$h .= '</a>';

			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	protected function getNewHarvest(\farm\Farm $eFarm, string $week, array $seasonsWithSeries, \farm\Action $eActionHarvest) {

		$label = \Asset::icon('basket2-fill').' '.s("Nouvelle récolte");

		$post = [
			'farm' => $eFarm['id'],
			'status' => Task::DONE,
			'action' => $eActionHarvest['id'],
			'doneWeek' => $week
		];

		$categoryId = $eActionHarvest['categories'][0];

		if($seasonsWithSeries === []) {

			$h = '<a href="/series/task:create?category='.$categoryId.'&'.http_build_query($post).'" class="btn btn-transparent">'.$label.'</a>';

		} else {

			$h = '<a class="dropdown-toggle btn btn-transparent" data-dropdown="bottom-start">'.$label.'</a>';
			$h .= '<div class="dropdown-list bg-harvest">';

				if(count($seasonsWithSeries) === 1) {

					$season = first($seasonsWithSeries);

					$h .= '<a href="/series/task:createFromSeries?season='.$season.'&'.http_build_query($post).'" class="dropdown-item">';
						$h .= s("Dans une série");
					$h .= '</a>';

				} else {

					$h .= '<div class="dropdown-title">';
						$h .= s("Dans une série");
					$h .= '</div>';

					foreach($seasonsWithSeries as $season) {

						$h .= '<a href="/series/task:createFromSeries?season='.$season.'&'.http_build_query($post).'" class="dropdown-item">';
							$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.s("Saison {value}", $season);
						$h .= '</a>';

					}

					$h .= '<div class="dropdown-title">';
						$h .= s("Récolte hors série");
					$h .= '</div>';

				}

				$h .= '<a href="/series/task:createFromScratch?category='.$categoryId.'&'.http_build_query($post).'" class="dropdown-item">';
					$h .= s("Choisir une plante");
				$h .= '</a>';

			$h .= '</div>';

		}

		return $h;

	}

	protected function getPlanningTasks(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cTask, bool $first, bool $last, ?string $date, string $week, bool $displayTime = FALSE): string {

		\Asset::css('production', 'flow.css');

		$eAction = $cTask->first()['action'];

		$users = array_unique($cTask->reduce(fn($eTask, $v) => array_merge($v, array_keys($eTask['times'])), []));

		$h = '<div class="tasks-planning-items '.($first ? 'tasks-planning-items-first' : '').' '.($last ? 'tasks-planning-items-last' : '').'" data-filter-action="'.$eAction['id'].'"  data-filter-user="'.implode(' ', $users).'">';

			$h .= '<div class="tasks-planning-action">';

				$h .= '<label class="tasks-planning-select">';
					$h .= $form->inputCheckbox('batchAction[]', attributes: ['onclick' => 'Task.checkPlanningAction(this)']);
				$h .= '</label>';
				$h .= '<a href="'.\farm\FarmUi::urlPlanningAction($eFarm, $week, $eAction).'" class="tasks-planning-action-name">';
					$h .= encode($eAction['name']);
				$h .= '</a>';

			$h .= '</div>';

			$h .= $this->getPlantPlanningTask($form, $eFarm, $cTask, $date, $week, $displayTime);

		$h .= '</div>';

		return $h;

	}

	protected function getPlantPlanningTask(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cTask, ?string $date, string $week, bool $displayTime = FALSE): string {

		$h = '';

		$ePlantCurrent = new \plant\Plant();

		foreach($cTask as $eTask) {

			$ePlant = $eTask['plant'];

			if(
				$ePlant->notEmpty() and (
					$ePlantCurrent->empty() or
					$ePlantCurrent['id'] !== $ePlant['id']
				)
			) {

				$ePlantCurrent = $ePlant;

				$h .= '<div class="tasks-planning-plant">';

					$h .= '<label class="tasks-planning-plant-image">';
						$h .= \plant\PlantUi::getVignette($ePlant, '1.75rem');
						$h .= $form->inputCheckbox('batchPlant[]', attributes: ['onclick' => 'Task.checkPlanningPlant(this, '.$ePlant['id'].')']);
					$h .= '</label>';
					$h .= '<div class="tasks-planning-plant-name">';
						$h .= encode($ePlant['name']);
					$h .= '</div>';

				$h .= '</div>';

			}

			$h .= $this->getPlanningTask($form, $eFarm, $eTask, $date, $week, $displayTime);

		}

		return $h;

	}

	protected function getPlanningTask(\util\FormUi $form, \farm\Farm $eFarm, Task $eTask, ?string $date, string $week, bool $displayTime = FALSE): string {

		$eTask->expects(['times']);

		$users = array_keys($eTask['times']);
		$series = $this->getTaskPlace($eTask);
		$content = $this->getTaskContent($eFarm, $eTask);

		$filters = [
			'data-filter-action' => $eTask['action']['id'],
			'data-filter-plant' => ($eTask['plant']->empty() ? '' : $eTask['plant']['id']),
			'data-filter-harvest' => $this->getBatchHarvestString($eTask),
			'data-filter-user' => implode(' ', $users)
		];

		$withContent = (
			$content !== '' or
			$series === ''
		);

		$h = '<div class="tasks-planning-item '.($series ? 'tasks-planning-item-with-series' : '').' '.($withContent ? 'tasks-planning-item-with-content' : '').' '.($eTask->isDone() ? 'tasks-planning-item-done' : 'tasks-planning-item-todo').' batch-item" id="task-item-'.$eTask['id'].'" '.attrs($filters).'>';

			$h .= '<label class="tasks-planning-select">';
				$h .= $this->getBatchCheckbox($form, $eTask);
			$h .= '</label>';

			$h .= '<div class="tasks-planning-item-base">';

				if($series) {
					$h .= '<a href="'.TaskUi::url($eTask).'" class="tasks-planning-item-top">'.$series.'</a>';
				}

				if($withContent) {
					$h .= '<a href="'.self::url($eTask).'" class="tasks-planning-item-content">'.$content.'</a>';
				}

				$h .= '<div class="tasks-planning-item-actions">';

					if($eFarm->hasFeatureTime()) {

						if(
							$displayTime and
							$this->cUserFarm !== NULL
						) {

							$hasTime = FALSE;

							if($eTask['time'] > 0) {

								foreach($eTask['times'] as $user => $time) {

									if($this->cUserFarm->offsetExists($user)) {

										$eUser = $this->cUserFarm[$user];

										$url = '/series/timesheet?ids[]='.$eTask['id'].'&user='.$user;
										if($date !== NULL) {
											$url .= '&date='.$date;
										}

										$h .= '<a href="'.$url.'" class="tasks-planning-item-user" title="'.$eUser->getName().'">';
											$h .= \user\UserUi::getVignette($eUser, '1.25rem').' '.self::convertTimeText($time);
										$h .= '</a>';

										$hasTime = TRUE;

									}

								}

							}

							if($hasTime === FALSE) {
								$h .= $this->getTime($eTask, 0);
							}

						} else {

							if($this->period === 'week' and $eTask['farm']->hasFeatureTime()) {
								$weekTime = round(array_sum($eTask['times']), 2);
								$h .= $this->getTime($eTask, $weekTime);
							}

						}

					}

				$h .= '</div>';

			$h .= '</div>';

			if($eTask->isDone() === FALSE) {
				$h .= $this->getDone($eTask, $week, ['post-week' => $week]);
			}

		$h .= '</div>';

		return $h;

	}

	protected function getBatchHarvestString(Task $eTask): string {

		$eTask->expects(['plant', 'variety', 'harvestSize', 'harvestUnit']);

		return
			($eTask['plant']->empty() ? '' : $eTask['plant']['id'])
			.'-'.($eTask['variety']->empty() ? '' : $eTask['variety']['id'])
			.'-'.($eTask['harvestSize']->empty() ? '' : $eTask['harvestSize']['id'])
			.'-'.($eTask['harvestUnit'] ?? '');

	}

	protected function getBatchCheckbox(\util\FormUi $form, Task $eTask): string {

		$batch = [];

		if($eTask['action']['fqn'] === ACTION_RECOLTE) {
			$batch[] = 'harvest';
		}
		if($eTask->acceptPostpone() === FALSE) {
			$batch[] = 'not-postpone';
		}
		if($eTask->isDone()) {
			$batch[] = 'done';
		}
		if($eTask['series']->notEmpty()) {
			$batch[] = 'series';
		}
		if($eTask->isTodo()) {
			$batch[] = 'todo';
		}
		if($eTask['plannedUsers']) {
			$batch[] = 'users';
			foreach($eTask['plannedUsers'] as $user) {
				$batch[] = 'user-'.$user;
			}
		}

		return $form->inputCheckbox('batch[]', $eTask['id'], [
			'data-week' => $eTask['doneWeek'] ?? $eTask['plannedWeek'],
			'data-batch' => implode(' ', $batch),
			'data-series' => $eTask['series']->empty() ? '' : $eTask['series']['id'],
			'oninput' => 'Task.changePlanningSelection()'
		]);

	}

	public function getTaskContent(\farm\Farm $eFarm, Task $eTask): string {

		$h = $this->getTaskDescription(
			$eTask,
			showPlant: ($eTask['category']['fqn'] !== CATEGORIE_CULTURE),
			showAction: FALSE,
			showTools: TRUE
		);

		$more = '';

		if($eFarm->hasFeatureTime() and $eTask['timeExpected'] > 0) {
			$more .= '<span>'.s("Travail estimé à {value}", self::convertTime($eTask['timeExpected'])).'</span>';
		}

		if($this->period === 'week') {

			if($eTask['delayed']) {
				$more .= '<span>'.\Asset::icon('exclamation-circle').' '.s("Retardé de semaine {value}", week_number($eTask['plannedWeek'])).'</span>';
			} else if($eTask['status'] === Task::TODO and $eTask['plannedDate']) {
				$more .= '<span>'.\Asset::icon('watch').' '.\util\DateUi::getDayName((int)date('N', strtotime($eTask['plannedDate'])))  .'</span>';
			}

		}

		if(
			$eTask['status'] === Task::TODO and
			$eTask['plannedUsers'] and
			$this->cUserFarm !== NULL
		) {

			$selectedUsers = [];
			foreach($eTask['plannedUsers'] as $user) {

				if($this->cUserFarm->offsetExists($user)) {

					$eUser = $this->cUserFarm[$user];
					$selectedUsers[] = ''.\user\UserUi::getVignette($eUser, '1rem');

				}
			}

			if($selectedUsers) {
				$more .= '<span>'.\Asset::icon('person-fill').' '.implode(' ', $selectedUsers)   .'</span>';
			}

		}

		if($more) {
			$h .= '<div class="tasks-planning-item-more">';
				$h .= $more;
			$h .= '</div>';
		}

		$h .= $this->getComments($eTask);

		return $h;

	}

	public function getTaskPlace(Task $eTask): string {

		$place = '';

		if($eTask['series']->empty() === FALSE) {
			$name = '<span class="tasks-planning-item-series-name">'.SeriesUi::name($eTask['series']).'</span>';
			$place .= s("Série {value}", $name);
			$place .= \production\CropUi::start($eTask['cultivation'], \Setting::get('farm\mainActions'));
			if($eTask['series']['cccPlace']->notEmpty()) {
				$place .= ' - <div class="tasks-planning-item-series-places">'.(new CultivationUi())->displayPlaces($eTask['series']['use'], $eTask['series']['cccPlace']).'</div>';
			}
		} else if($eTask['cccPlace']->notEmpty()) {
			$place .= '<div class="tasks-planning-item-series-places">'.(new CultivationUi())->displayPlaces(Series::BED, $eTask['cccPlace']).'</div>';
		} else {
			return '';
		}

		return '<div class="tasks-planning-item-series">'.$place.'</div>';

	}

	public function getTaskDescription(Task $eTask, bool $showPlant = TRUE, bool $showAction = TRUE, bool $showTools = FALSE): string {

		\Asset::css('farm', 'action.css');

		$description = '';

		if($showAction) {

			$description .= '<div class="tasks-planning-item-label">';
				if($eTask->isDone() and $eTask['action']['fqn'] === ACTION_RECOLTE) {
					if($showPlant) {
						$description .= '<span class="action-name">'.encode($eTask['plant']['name']).'</span> ';
					}
					$description .= $this->getMore($eTask);
				} else {
					$description .= $this->getAction($eTask);
				}
			$description .= '</div>';

		} else {

			if($eTask['plant']->notEmpty()) {
				if($showPlant) {
					$description .= '<span class="action-name">'.encode($eTask['plant']['name']).'</span> ';
				}
			}

			$description .= $this->getMore($eTask);

			if($showTools) {

				foreach($eTask['cTool?']() as $eTool) {
					$description .= ' <span class="flow-tool-name">'.\farm\ToolUi::getVignette($eTool, '2rem', '1.5rem').' '.encode($eTool['name']).'</span> ';
				}

			}

		}

		$description .= $this->getDescription($eTask);

		$h = '';

		if($description) {
			$h = '<div class="tasks-planning-item-description">';
				$h .= $description;
			$h .= '</div>';
		}

		return $h;
	}

	public function getTaskComplement(Task $eTask) {

		$h = '';

		if($eTask['action']['fqn'] === ACTION_RECOLTE) {

			$eTask->expects(['harvestSize', 'variety']);

			if($eTask['harvestSize']->notEmpty()) {
				$h .= ' <span class="task-size-name">'.encode($eTask['harvestSize']['name']).'</span> ';
			}

		}

		if($eTask['variety']->notEmpty()) {
			$h .= ' <span class="task-variety-name">'.encode($eTask['variety']['name']).'</span> ';
		}

		foreach($eTask['cMethod?']() as $eMethod) {
			$h .= ' <span class="flow-method-name">'.encode($eMethod['name']).'</span> ';
		}

		return $h;

	}

	public function getMore(Task $eTask): string {

		$h = $this->getTaskComplement($eTask);
		$h .= (new \production\FlowUi())->getMore($eTask);

		return $h;

	}

	protected function getYearTask(Task $eTask): string {

		$eTask->expects(['times']);

		$users = array_keys($eTask['times']);

		$h = '<a href="'.self::url($eTask).'" class="tasks-year-item" data-filter-action="'.$eTask['action']['id'].'" data-filter-user="'.implode(' ', $users).'" id="task-item-'.$eTask['id'].'">';

				$h .= '<div class="tasks-planning-item-series">';

					if($eTask['series']->empty() === FALSE) {
						$h .= s("Série {value}", encode($eTask['series']['name']));
					} else if($eTask['cccPlace']->notEmpty()) {
						$h .= (new CultivationUi())->displayPlaces(Series::BED, $eTask['cccPlace']);
					}

				$h .= '</div>';

				$h .= '<div class="tasks-planning-item-description">';

					$h .= '<div class="tasks-planning-item-label">';
						$h .= $this->getAction($eTask, \Setting::get('farm\mainActions'));
					$h .= '</div>';

				$h .= '</div>';

		$h .= '</a>';

		return $h;

	}

	public function getTimeline(\farm\Farm $eFarm, Series $eSeries, \Collection $cCultivation, \Collection $cTask): string {

		\Asset::css('production', 'flow.css');

		if($cTask->empty()) {
			return $this->getEmptyTimeline($eSeries);
		}

		$form = new \util\FormUi();

		$h = '<div id="series-task-wrapper" data-series="'.$eSeries['id'].'">';

			$h .= '<div class="h-line">';
				$h .= '<h3>'.s("Interventions").'</h3>';
				$h .= $this->planTask($eSeries);
			$h .= '</div>';

			$h .= '<div class="flow-timeline-wrapper stick-xs">';

				$h .= '<div class="flow-timeline flow-timeline-header">';

					$h .= '<div class="util-grid-header util-grid-icon text-center">';
						$h .= \Asset::icon('calendar-week');
					$h .= '</div>';

					$h .= '<div>';
					$h .= '</div>';
					$h .= '<div class="flow-timeline-update" title="'.s("Tout cocher / Tout décocher").'">';
						$h .= $form->inputCheckbox(attributes: ['onclick' => 'Task.checkPlanningSeries(this)', 'class' => 'batch-all']);
					$h .= '</div>';
					$h .= '<div class="util-grid-header">';
						$h .= s("Intervention");
					$h .= '</div>';

				$h .= '</div>';

				$h .= $this->getTimelineBody($cTask, [
					'item' => function(Task $eTask, bool $newWeek) {

						$h = '';

						if($newWeek) {
							$h .= '<div class="flow-timeline-circle">'.s("s{value}", week_number($eTask['display'])).'</div>';
						}

						return $h;

					},
					'content' => function(Task $eTask) use ($eFarm, $cCultivation, $form) {

						if($eTask['cultivation']->empty() === FALSE) {
							$eCultivation = $cCultivation[$eTask['cultivation']['id']];
							$eTask['plant'] = $eCultivation['plant'];
							$eTask['cultivation'] = $eCultivation;
						}

						$h = '<div class="flow-timeline-task">';

							$h .= '<div class="flow-timeline-label">';
								$h .= '<a href="/tache/'.$eTask['id'].'" class="flow-timeline-text">'.$this->getAction($eTask).'</a>';
								$h .= $this->getDescription($eTask);
								$h .= $this->getComments($eTask);
								$h .= (new \production\FlowUi())->getTools($eTask);
							$h .= '</div>';


							$h .= $this->getDone($eTask, currentWeek());

							if($eFarm->hasFeatureTime()) {
								$h .= $this->getTime($eTask);
							}

						$h .= '</div>';

						return $h;

					},
					'update' => function(Task $eTask) use ($form) {

						$filters = [
							'data-filter-action' => $eTask['action']['id'],
							'data-filter-plant' => ($eTask['plant']->empty() ? '' : $eTask['plant']['id']),
							'data-filter-harvest' => $this->getBatchHarvestString($eTask)
						];

						$h = '<label class="flow-timeline-select batch-item" '.attrs($filters).'>';
							$h .= $this->getBatchCheckbox($form, $eTask);
						$h .= '</label>';

						return $h;

					}
				], TRUE);

			$h .= '</div>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm);

		return $h;

	}

	protected function getEmptyTimeline(Series $eSeries): string {

		$h = '<div id="series-task-wrapper" data-series="'.$eSeries['id'].'">';
			$h .= '<div class="h-line">';
				$h .= '<h3>'.s("Interventions").'</h3>';
				$h .= $this->planTask($eSeries);
			$h .= '</div>';
			$h .= '<p class="util-info">';
				$h .= s("Vous n'avez pas encore saisi d'intervention pour cette série.");
			$h .= '</p>';
		$h .= '</div>';

		return $h;

	}

	public function planTask(Series $eSeries): string {

		if(
			$eSeries['status'] !== Series::OPEN or
			(new Task(['farm' => $eSeries['farm']]))->canWrite() === FALSE
		) {
			return '';
		}

		$h = '<div>';
			$h .= '<a class="btn btn-outline-primary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('calendar-plus').' '.s("Nouvelle intervention").'</a>';
			$h .= '<div class="dropdown-list">';

				$h .= '<div class="dropdown-title">'.s("Nouvelle intervention").'</div>';
				$h .= '<a href="/series/task:createFromSeries?farm='.$eSeries['farm']['id'].'&series='.$eSeries['id'].'&status='.Task::TODO.'" class="dropdown-item">'.s("Planifier une future intervention").'</a>';
				$h .= '<a href="/series/task:createFromSeries?farm='.$eSeries['farm']['id'].'&series='.$eSeries['id'].'&status='.Task::DONE.'" class="dropdown-item">'.s("Ajouter une intervention déjà réalisée").'</a>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;
	}

	protected function getDone(Task $eTask, string $week, array $onUpdate = []): string {

		\Asset::css('production', 'flow.css');

		$h = '<div class="flow-timeline-done" id="series-task-done-'.$eTask['id'].'">';

			if($eTask->isTodo()) {

				if($eTask->canWrite()) {

					$h .= '<a data-ajax="/series/task:doUpdateDoneCollection" '.attrs($onUpdate).' '.attrAjaxBody([['ids[]', $eTask['id']], ['doneWeek', $week]]).' class="flow-timeline-status-todo" title="'.s("Fait !").'">';
						$h .= \Asset::icon('check');
					$h .= '</a>';

				} else {

					$h .= '<a class="flow-timeline-status-todo disabled" title="'.s("À faire").'">';
						$h .= '&nbsp;';
					$h .= '</a>';

				}

			}

		$h .= '</div>';

		return $h;

	}

	public function getAction(Task $eTask, \Collection $cActionMain = new \Collection()): string {
		return \farm\ActionUi::text($eTask, $cActionMain).' '.$this->getMore($eTask);
	}

	public function getDescription(Task $eTask): string {

		if($eTask['description'] === NULL) {
			return '';
		}

		$description = \util\TextUi::tiny(nl2br(encode($eTask['description'])), FALSE);

		$lines = [];

		foreach(explode("\n", $description) as $position => $content) {

			if(str_starts_with($content, 'o ') or str_starts_with($content, 'O ')) {
				$line = '<div class="flow-timeline-description-list">';
					if($eTask->canWrite()) {
						$line .= '<div data-action="task-checkbox" post-id="'.$eTask['id'].'" post-position="'.$position.'" post-check="1" data-ajax-navigation="never">'.\Asset::icon('circle').'</div>';
					} else {
						$line .= \Asset::icon('circle');
					}
					$line .= '<div>'.ltrim(mb_substr($content, 1)).'</div>';
				$line .= '</div>';
			} else if(str_starts_with($content, 'x ') or str_starts_with($content, 'X ')) {
				$line = '<div class="flow-timeline-description-list">';
					if($eTask->canWrite()) {
						$line .= '<div data-action="task-checkbox" post-id="'.$eTask['id'].'" post-position="'.$position.'" post-check="0" data-ajax-navigation="never">'.\Asset::icon('check-circle').'</div>';
					} else {
						$line .= \Asset::icon('check-circle');
					}
					$line .= '<div>'.ltrim(mb_substr($content, 1)).'</div>';
				$line .= '</div>';
			} else {
				$line = $content;
			}

			$lines[] = $line;

		}

		$h = '<div class="flow-timeline-description" data-task="'.$eTask['id'].'">';
			$h .= implode("\n", $lines);
		$h .= '</div>';

		return $h;

	}

	public function getComments(Task $eTask): string {

		$h = '';

		if($eTask['cComment']->notEmpty()) {
			$h .= '<div class="flow-timeline-comments" id="task-'.$eTask['id'].'-comments">';
				$h .= (new CommentUi())->getList($eTask['cComment']);
			$h .= '</div>';
		}

		return $h;

	}

	protected function getUpdate(Task $eTask, string $btn): string {

		if($eTask->canWrite()) {

			$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.s("Intervention").'</div>';
				if($eTask['action']['fqn'] === ACTION_RECOLTE) {
					$h .= '<a href="/series/task:updateHarvestCollection?ids[]='.$eTask['id'].'" class="dropdown-item">'.s("Compléter la récolte").'</a>';
				}
				$h .= '<a href="/series/task:update?id='.$eTask['id'].'" class="dropdown-item">'.s("Modifier l'intervention").'</a>';

				if($eTask->isDone()) {
					$h .= '<a data-ajax="/series/task:doUpdateTodoCollection" post-id="'.$eTask['id'].'" '.attrAjaxBody([["ids[]", $eTask['id']]]).' class="dropdown-item" data-confirm="'.s("Annuler la réalisation de l'intervention ?").'">'.s("Marquer &laquo; À faire &raquo;").'</a>';
				}

				if($eTask['cultivation']->notEmpty()) {
					$h .= '<a href="/series/task:updateCultivation?id='.$eTask['id'].'" class="dropdown-item">'.s("Changer de série").'</a>';
				}

				$h .= '<div class="dropdown-divider"></div>';

				if($eTask['repeat']->empty()) {

					$h .= '<a data-ajax="/series/task:doDelete" class="dropdown-item" post-id="'.$eTask['id'].'" data-confirm="'.s("Confirmer la suppression de cette intervention ?").'">'.s("Supprimer l'intervention").'</a>';

				} else {

					$h .= '<div class="dropdown-subtitle">'.s("Supprimer").'</div>';
					$h .= '<a data-ajax="/series/task:doDelete" class="dropdown-item" post-id="'.$eTask['id'].'" data-confirm="'.s("Confirmer la suppression de cette intervention ?").'"> '.\Asset::icon('arrow-right').'  '.s("Uniquement cette intervention").'</a>';
					$h .= '<a data-ajax="/series/task:doDeleteRepeat" class="dropdown-item" post-id="'.$eTask['id'].'" data-confirm="'.s("Confirmer la suppression de cette intervention et de toutes les suivantes ?").'"> '.\Asset::icon('arrow-right').'  '.s("Cette intervention et toutes les suivantes").'</a>';

				}



			$h .= '</div>';

		} else {
			$h = '';
		}

		return $h;

	}

	public function getUsersReadOnly(Task $eTask, \Collection $cUser): string {

		$h = '<div class="tasks-item-users-list">';

			foreach($cUser as $eUser) {

				if($eUser['time'] !== NULL) {
					$h .= $this->getUser($eUser, $eUser['time']);
				}

			}

		$h .= '</div>';

		return $h;

	}

	protected function getUser(\user\User $eUser, float $time, string $size = '2rem'): string {

		$h = '<div title="'.$eUser->getName().' : '.self::convertTimeText($time).'">';
			$h .= \user\UserUi::getVignette($eUser, $size);
		$h .= '</div>';

		return $h;

	}

	public function getTime(Task $eTask, ?float $time = NULL): string {

		$time ??= $eTask['time'];

		$h = '<a href="/series/timesheet?ids[]='.$eTask['id'].'" class="tasks-planning-item-time" title="'.s("Gérer le temps de travail sur l'intervention").'">';
			$h .= '<div class="tasks-planning-item-time-icon">';
				$h .= \Asset::icon('clock');
			$h .= '</div>';
				$h .= $this->getTimeValue($time);
		$h .= '</a>';

		return $h;

	}

	public function getTimeValue(?float $time): string {

		$h = '<div class="tasks-planning-item-time-value '.($time > 0 ? 'text-end' : 'text-center').'">';

		if($time > 0) {
			$h .= $this->convertTime($time);
		} else {
			$h .= '&middot; &middot; &middot;';
		}

		$h .= '</div>';

		return $h;

	}

	public static function convertTime(float $time, bool $showMinutes = TRUE): string {

		\Asset::css('series', 'task.css');

		$hours = intval($time);

		$minutes = round(($time - $hours) * 60);

		if($minutes === 60.0) {
			$minutes = 0;
			$hours++;
		}

		if($showMinutes) {

			return '<span><span class="task-time-hours">'.$hours.'</span><small class="task-time-hour">h</small><span class="task-time-minutes">'.sprintf('%02d', $minutes).'</span></span>';

		} else {
			return '<span><span class="task-time-hours">'.$hours.'</span><small class="task-time-hour">h</small></span>';
		}

	}

	public static function convertTimeText(float $time): string {

		$hours = intval($time);
		$minutes = round(($time - $hours) * 60);

		return $hours.' h '.sprintf('%02d', $minutes);

	}

	protected function getTimelineBody(\Collection $cTask, array $callbacks, bool $canWrite): string {

		array_expects($callbacks, ['item', 'content']);

		$extractYear = function(?string $week) {
			return ($week === NULL) ? \Asset::icon('question-circle-fill') : week_year($week);
		};

		$h = '<div class="flow-timeline-body">';

		$lastWeek = NULL;
		$lastYear = NULL;

		foreach($cTask as $eTask) {

			$eTask->expects(['display']);

			$newWeek = ($lastWeek !== $eTask['display']);
			$newYear = ($lastYear !== $extractYear($eTask['display']));

			if($newYear) {
				$h .= '<div class="flow-timeline flow-timeline-year">';
					$h .= '<div class="flow-timeline-year-value">';
						$h .= $extractYear($eTask['display']);
					$h .= '</div>';
					$h .= '<div></div>';
					if($canWrite) {
						$h .= '<div class="flow-timeline-update"></div>';
					}
					$h .= '<div></div>';
				$h .= '</div>';
			}

			$h .= '<div class="flow-timeline flow-timeline-only '.($newWeek ? 'flow-timeline-new-week' : '').'" data-checked="0" data-week="'.$eTask['display'].'">';

				$h .= '<a class="flow-timeline-item" onclick="Task.checkPlanningWeekSeries(this)">';
					$h .= ($callbacks['item'])($eTask, $newWeek);
				$h .= '</a>';

				$h .= '<a class="flow-timeline-week" onclick="Task.checkPlanningWeekSeries(this)">';
					if($newWeek) {
						$h .= \util\DateUi::weekToDays($eTask['display'], TRUE, FALSE);
					}
				$h .= '</a>';

				if($canWrite) {
					$h .= '<div class="flow-timeline-update">';
						$h .= ($callbacks['update'])($eTask);
					$h .= '</div>';
				}

				$h .= '<div class="flow-timeline-action">';

					$color = $eTask->isDone() ? 'background-color: '.$eTask['action']['color'] : 'background: repeating-linear-gradient(to bottom, '.$eTask['action']['color'].' 0, '.$eTask['action']['color'].' 3px, transparent 2px, transparent 6px)';

					$h .= '<div class="flow-timeline-lasting-only" style="'.$color.'"></div>';

					$h .= ($callbacks['content'])($eTask);

				$h .= '</div>';

			$h .= '</div>';

			$lastWeek = $eTask['display'];
			$lastYear = $extractYear($eTask['display']);

		}

		$h .= '</div>';

		return $h;

	}

	public function getOne(Task $eTask, \Collection $cPlace, \Collection $cPhoto, \Collection $cUser, \Collection $cComment): \Panel {

		$eTask->expects([
			'cultivation'
		]);

		$cTool = $eTask['cTool?']();

		$h = '<div class="task-item-header">';
			if($eTask['description'] !== NULL) {
				$h .= $this->getDescription($eTask);
			}
		$h .= '</div>';

		$columns = 1;

		if($eTask['farm']->hasFeatureTime()) {
			$columns++;
		}

		if($eTask['action']['fqn'] === ACTION_RECOLTE) {
			$columns++;
		}

		if($cTool->notEmpty()) {
			$columns++;
		}

		$h .= '<div class="task-item-presentation task-item-presentation-'.$columns.'">';

			$h .= '<div>';

				$h .= '<div class="util-title">';
					$h .= '<h4>'.s("Intervention").'</h4>';
					$h .= '<div>';

						if(
							$eTask->isTodo() and
							$eTask['plannedWeek'] !== NULL
						) {

							$h .= '<a data-dropdown="bottom-end" class="btn btn-secondary">'.($eTask['plannedUsers'] ? \Asset::icon('people-fill') : \Asset::icon('person-fill-add')).'</a>';
							$h .= '<div class="dropdown-list bg-secondary">';

								$h .= '<div class="dropdown-title">'.s("Affecter à...").'</div>';

								foreach($cUser as $eUser) {

									if(\hr\Presence::isWeekPresent($eUser['cPresence'], $eTask['plannedWeek'])->empty()) {
										continue;
									}

									$has = in_array($eUser['id'], $eTask['plannedUsers']);

									$h .= '<a data-ajax="/series/task:doUpdateUserCollection" post-ids="'.$eTask['id'].'" post-user="'.$eUser['id'].'" post-action="'.($has ? 'delete' : 'add').'" post-reload="layer" class="dropdown-item">';
										$h .= $has ? \Asset::icon('x-lg') : \Asset::icon('plus-lg');
										$h .= '  '.\user\UserUi::getVignette($eUser, '1.5rem').'  '.$eUser->getName();
									$h .= '</a>';

								}

							$h .= '</div>';

						}

						$h .= ' '.$this->getUpdate($eTask, 'btn-secondary');

					$h .= '</div>';
				$h .= '</div>';

				$h .= '<dl class="util-presentation util-presentation-1">';

					if($eTask['series']->notEmpty()) {
						$h .= '<dt>'.s("Série").'</dt>';
						$h .= '<dd>';
							$h .= SeriesUi::link($eTask['series']);
						$h .= '</dd>';
					}

					if(
						$eTask['plannedWeek'] !== NULL or
						$eTask['doneWeek'] !== NULL
					) {

						if($eTask['plannedDate'] !== NULL) {
							$h .= '<dt>'.s("Planifiée").'</dt>';
							$h .= '<dd>';
								$h .= \util\DateUi::numeric($eTask['plannedDate']);
							$h .= '</dd>';
						} else if($eTask['plannedWeek'] !== NULL) {
							$h .= '<dt>'.s("Planifiée").'</dt>';
							$h .= '<dd>';
								$h .= s("Semaine {value}", week_number($eTask['plannedWeek']));
								$h .= '<span class="task-item-presentation-week-days">'.\util\DateUi::weekToDays($eTask['plannedWeek']).'</span>';
							$h .= '</dd>';
						}

						if($eTask['doneDate'] !== NULL) {
							$h .= '<dt>'.s("Réalisée").'</dt>';
							$h .= '<dd>';
								$h .= \util\DateUi::numeric($eTask['doneDate']);
							$h .= '</dd>';
						} else if($eTask['doneWeek'] !== NULL) {
							$h .= '<dt>'.s("Réalisée").'</dt>';
							$h .= '<dd>';
								$h .= s("Semaine {value}", week_number($eTask['doneWeek']));
								$h .= '<span class="task-item-presentation-week-days">'.\util\DateUi::weekToDays($eTask['doneWeek']).'</span>';
							$h .= '</dd>';
						}

					}

					if(
						$cUser->count() > 1 and
						$eTask['plannedUsers']
					) {

						$h .= '<dt>'.s("Affectée").'</dt>';
						$h .= '<dd>';

							foreach($eTask['plannedUsers'] as $user) {

								if($cUser->offsetExists($user)) {

									$eUser = $cUser[$user];
									$h .= ' '.\user\UserUi::getVignette($eUser, '1rem');

								}

							}

						$h .= '</dd>';

					}

					if($eTask['repeat']->notEmpty()) {

						$h .= '<dt>'.s("Répétée").'</dt>';
						$h .= '<dd>';
							$h .= RepeatUi::getSequence($eTask['repeat']);
						$h .= '</dd>';

					}

					$h .= '<dt>'.s("Créée").'</dt>';
					$h .= '<dd title="'.s("Plus précisément à {value}", \util\DateUi::numeric($eTask['createdAt'], \util\DateUi::TIME)).'">';
						$h .= s("{date} par {user}", ['date' => \util\DateUi::numeric($eTask['createdAt'], \util\DateUi::DATE), 'user' => $eTask['createdBy']->getName()]);
					$h .= '</dd>';

				$h .= '</dl>';

			$h .= '</div>';

			if($eTask['farm']->hasFeatureTime()) {

				$h .= '<div>';

					$h .= '<div class="util-title">';
						$h .= '<h4>'.s("Temps de travail").'</h4>';
						$h .= '<a href="/series/timesheet?ids[]='.$eTask['id'].'&close=reloadIgnoreCascade" class="btn btn-secondary">'.\Asset::icon('clock').'</a>';
					$h .= '</div>';
					$h .= '<div>';
						$h .= (new TimesheetUi())->getList($eTask, $cUser, 'reloadIgnoreCascade');
					$h .= '</div>';
				$h .= '</div>';

			}

			if($cTool->notEmpty()) {

				$h .= '<div>';
					$h .= '<h4>'.s("Matériel").'</h4>';
					$h .= (new \farm\ToolUi())->getList($cTool);
				$h .= '</div>';

			}

			if($eTask['action']['fqn'] === ACTION_RECOLTE) {

				$h .= '<div>';

					$h .= '<div class="util-title">';
						$h .= '<h4>'.s("Récolte").'</h4>';
						$h .= '<a href="/series/task:updateHarvestCollection?ids[]='.$eTask['id'].'" class="btn btn-secondary btn">'.\Asset::icon('plus-circle').'</a>';
					$h .= '</div>';

					if($eTask['harvestDates']) {

						krsort($eTask['harvestDates']);

						$h .= '<dl class="util-presentation util-presentation-1">';

						foreach($eTask['harvestDates'] as $date => $value) {

							$workingTime = $eTask['harvestWorkingTime'][$date] ?? NULL;

							$h .= '<dt>'.\util\DateUi::numeric($date).'</dt>';

							$h .= '<dd';
								if($workingTime > 0) {
									$h .= ' title="'.s("{value} / h", \selling\UnitUi::getValue(round($value / $workingTime, 1), $eTask['harvestUnit'])).'"';
								}
							$h .= '>';
								$h .= \selling\UnitUi::getValue(round($value, 1), $eTask['harvestUnit']);
							$h .= '</dd>';

						}

						$h .= '</dl>';

					} else {
						$h .= '/';
					}

				$h .= '</div>';

			}

		$h .= '</div>';

		if($eTask['cultivation']->notEmpty()) {

			$h .= match($eTask['action']['fqn']) {

				ACTION_SEMIS_DIRECT => $this->displaySowing($eTask),
				ACTION_SEMIS_PEPINIERE => $this->displayYoungPlant($eTask),
				ACTION_PLANTATION => $this->displayPlanting($eTask),
				default => ''

			};

		}

		if($eTask['series']->notEmpty()) {

			$h .= match($eTask['action']['fqn']) {

				ACTION_FERTILISATION => $this->displayFertilizer($eTask, $cPlace),
				default => ''

			};

		}

		$h .= '<div class="util-title">';
			$h .= '<h3>'.s("Commentaires").'</h3>';
			$h .= '<div>';
				if((new \series\Comment(['farm' => $eTask['farm']]))->canCreate()) {
					$h .= '<a href="/series/comment:createCollection?ids[]='.$eTask['id'].'" class="btn btn-outline-primary">';
						$h .= \Asset::icon('plus-circle').' '.s("Nouveau commentaire");
					$h .= '</a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		if($cComment->notEmpty()) {

			$h .= '<div>';
				$h .= (new CommentUi())->getList($cComment, TRUE, TRUE);
			$h .= '</div>';
			$h .= '<br/>';

		}

		if($eTask['series']->notEmpty()) {

			if($cPlace->empty() === FALSE) {

				$h .= '<h3>'.s("Assolement").'</h3>';

				$h .= '<div class="util-overflow-md">';
					$h .= (new SeriesUi())->getPlace('series', $eTask['series'], $cPlace);
				$h .= '</div>';

				$h .= '<br/>';

			}

		} else if($eTask->acceptSoil()) {

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Assolement").'</h3>';
				if($eTask->canWrite()) {
					$h .= '<a href="/series/place:update?task='.$eTask['id'].'&close=reloadIgnoreCascade" class="btn btn-outline-primary">';
						if($cPlace->empty()) {
							$h .= \Asset::icon('plus-circle').' '.s("Assoler");
						} else {
							$h .= \Asset::icon('gear-fill');
						}
					$h .= '</a>';
				}
			$h .= '</div>';

			if($cPlace->empty() === FALSE) {

				$h .= '<div class="util-overflow-md">';
					$h .= (new SeriesUi())->getPlace('task', $eTask, $cPlace);
				$h .= '</div>';
				$h .= '<br/>';

			}

		}


		if(
			$cPhoto->notEmpty() or
			$eTask->canWrite()
		) {

			$h .= '<div class="util-title">';
				$h .= '<h3 id="scroll-photos">'.s("Photos").'</h3>';

				if($eTask->canWrite()) {

					$h .= '<div data-media="gallery" post-task="'.$eTask['id'].'">';
						$h .= (new \media\GalleryUi())->getDropdownLinks(
							\Asset::icon('plus-circle').' <span>'.s("Nouvelle photo").'</span>',
							'btn-outline-primary',
							uploadInputAttributes: ['multiple' => 'multiple']
						);
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		if($cPhoto->notEmpty()) {
			$h .= (new \gallery\PhotoUi())->getList($cPhoto, NULL, 4);
		}


		if($eTask['plant']->notEmpty()) {
			$title = s("{action} de {plant}", ['action' => encode($eTask['action']['name']), 'plant' => encode($eTask['plant']['name'])]);
		} else {
			$title = encode($eTask['action']['name']);
		}

		$title .= $this->getTaskComplement($eTask);

		return new \Panel(
			id: 'panel-task',
			title: $title,
			body: $h,
			close: 'reloadOnCascade'
		);

	}

	public function displayByActionTitle(\farm\Farm $eFarm, string $week, \farm\Action $eAction): string {
		return $this->getWeekCalendar($week, fn($week) => \farm\FarmUi::urlPlanningAction($eFarm, $week, $eAction));
	}

	public function displayByAction(\farm\Farm $eFarm, string $week, \farm\Action $eAction, \Collection $cTask): string {

		$hasPlant = in_array($eAction['fqn'], [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION]);
		$hasTools = $cTask->contains(fn($eTask) => $eTask['tools']);

		$h = '<h2>'.encode($eAction['name']).'</h2>';

		if($cTask->empty()) {
			$h .= '<div class="util-empty">'.s("Aucune intervention de cette nature à afficher cette semaine.").'</div>';
		} else if($hasPlant or $hasTools) {

				$h .= $this->getListByAction($eFarm, $week, $cTask, $hasTools);

				if($hasPlant) {
					$h .= '<br/><h3>'.s("Répartition par variété").'</h3>';
					$h .= $this->getListByPlant($eAction, $cTask);
				}

				if($hasTools) {
					$h .= '<br/><h3>'.s("Répartition par matériel").'</h3>';
					$h .= $this->getListByTool($eAction, $cTask);
				}

			$h .= '</div>';

		} else {
			$h .= $this->getListByAction($eFarm, $week, $cTask, FALSE);
		}

		return $h;

	}

	protected function getListByAction(\farm\Farm $eFarm, string $week, \Collection $cTask, bool $hasTools): string {

		$h = '<table class="tr-even table-block tasks-week-list stick-xs">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th class="tasks-week-list-name">';
						$h .= s("Tâche");
					$h .= '</th>';
					if($hasTools) {
						$h .= '<th class="tasks-week-list-tools">';
							$h .= s("Matériel");
						$h .= '</th>';
					}
					$h .= '<th></th>';
					if($eFarm->hasFeatureTime()) {
						$h .= '<th>';
							$h .= '<span class="hide-xs-down">'.s("Temps de travail").'</span>';
						$h .= '</th>';
					}
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cTask as $eTask) {

					$h .= '<tr>';
						$h .= '<td class="tasks-week-list-name">';
							$h .= '<a href="'.TaskUi::url($eTask).'">';
								$h .= $this->getTaskPlace($eTask);
								$h .= $this->getTaskDescription($eTask, showAction: FALSE);
								$h .= $this->getComments($eTask);
							$h .= '</a>';
						$h .= '</td>';

						if($hasTools) {

							$h .= '<td class="tasks-week-list-tools">';

								foreach($eTask['cTool?']() as $eTool) {

									$h .= '<div>';
										$h .= \farm\ToolUi::link($eTool);
									$h .= '</div>';

								}

							$h .= '</td>';

						}
						$h .= '<td class="text-center">';
							$h .= $this->getDone($eTask, $week);
						$h .= '</td>';
						if($eFarm->hasFeatureTime()) {
							$h .= '<td>';
								$h .= $this->getTime($eTask);
							$h .= '</td>';
						}
						$h .= '<td class="text-end">';
							$h .= $this->getUpdate($eTask, 'btn-outline-secondary');
						$h .= '</td>';
					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

	protected function getListByPlant(\farm\Action $eAction, \Collection $cTask): string {

		$validTasks = $cTask->find(fn($eTask) => $eTask['series']->notEmpty())->count();

		$h = '';

		if($validTasks !== $cTask->count()) {

			$h .= '<div class="util-info">';
				$h .= s("Le détail par espèce n'est affiché que pour les interventions créées au sein de séries.");
			$h .= '</div>';

		}

		if($validTasks === 0) {
			return $h;
		}

		$targeted = FALSE;

		$ccVariety = new \Collection();
		$ccVariety->setDepth(2);

		foreach($cTask as $eTask) {

			if($eTask['cultivation']->empty()) {
				continue;
			}

			$ePlant = $eTask['plant'];
			$eSeries = $eTask['series'];

			$ccVariety[$ePlant['id']] ??= new \Collection();

			if($eSeries->notEmpty()) {

				foreach($eTask['cultivation']['cSlice'] as $eSlice) {

					$eVariety = $eSlice['variety'];

					if($eVariety->empty()) {
						$eVariety['id'] = NULL;
						$id = '';
					} else {
						$id = $eVariety['id'];
					}

					$ccVariety[$ePlant['id']][$id] ??= $eVariety->merge([
						'plant' => $ePlant,
						'youngPlants' => 0,
						'seeds' => 0,
						'area' => 0,
						'targeted' => FALSE,
						'tools' => []
					]);

					if($eSlice['youngPlants'] !== NULL) {

						$ccVariety[$ePlant['id']][$id]['youngPlants'] += $eSlice['youngPlants'];

						foreach($eTask['cTool?']()->find(fn($eTool) => $eTool['routineName'] === 'tray') as $eTool) {

							$ccVariety[$ePlant['id']][$id]['tools'][$eTool['id']] ??= [
								'youngPlants' => 0,
								'tool' => $eTool
							];

							$ccVariety[$ePlant['id']][$id]['tools'][$eTool['id']]['youngPlants'] += $eSlice['youngPlants'];

						}

					}

					if($eSlice['seeds'] !== NULL) {
						$ccVariety[$ePlant['id']][$id]['seeds'] += $eSlice['seeds'];
					}

					if($eSlice['area'] !== NULL) {
						$ccVariety[$ePlant['id']][$id]['area'] += $eSlice['area'];
					}

					if($eSeries->isTargeted()) {
						$ccVariety[$ePlant['id']][$id]['targeted'] = TRUE;
						$targeted = TRUE;
					}

				}

			}

		}

		$ccVariety->sort(function(\Collection $c1, \Collection $c2) {
			return \L::getCollator()->compare(
				$c1->empty() ? '' : $c1->first()['plant']['name'],
				$c2->empty() ? '' : $c2->first()['plant']['name']
			);
		});

		$h = '<div class="stick-xs">';

			$columns = 2;

			$h .= '<table class="tbody-even table-block tasks-week-plant-list">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th class="text-end hide-xs-down">';
							$h .= s("Surface");
						$h .= '</th>';
						if($eAction['fqn'] !== ACTION_SEMIS_DIRECT) {
							$columns++;
							$h .= '<th class="text-end">';
								$h .= s("Plants");
							$h .= '</th>';
						}
						if($eAction['fqn'] !== ACTION_PLANTATION) {
							$columns++;
							$h .= '<th class="text-end">';
								$h .= s("Semences");
							$h .= '</th>';
						}
						if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {
							$columns++;
							$h .= '<th>';
								$h .= s("Plateaux de semis");
							$h .= '</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

			foreach($ccVariety as $cVariety) {

				$eVariety = $cVariety->first();

				$h .= '<tbody>';

					$h .= '<tr>';
						$h .= '<th colspan="'.$columns.'">';
							$h .= \plant\PlantUi::getVignette($eVariety['plant'], '2rem').'<span style="font-weight: bold; margin-left: 0.5rem">'.encode($eVariety['plant']['name']).'</span>';
						$h .= '</th>';
					$h .= '</tr>';

					foreach($cVariety as $eVariety) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= ($eVariety['id'] === NULL) ? '<i>'.s("Variété non renseignée").'</i>' : encode($eVariety['name']);
							$h .= '</td>';
							$h .= '<td class="text-end hide-xs-down '.($eVariety['targeted'] ? 'color-warning' : '').'">';
								if($eVariety['area'] > 0) {
									$h .= s("{value} m²", $eVariety['area']).($eVariety['targeted'] ? '&nbsp;*' : '');
								} else {
									$h .= '?';
								}
							$h .= '</td>';
							if($eAction['fqn'] !== ACTION_SEMIS_DIRECT) {
								$h .= '<td class="text-end '.($eVariety['targeted'] ? 'color-warning' : '').'">';
									if($eVariety['youngPlants'] > 0) {
										$h .= $eVariety['youngPlants'].($eVariety['targeted'] ? '&nbsp;*' : '');
									} else {
										$h .= '?';
									}

									$h .= '<div>';
										if($eVariety->exists() and $eVariety['seeds'] > 0 and $eVariety['numberPlantKilogram'] !== NULL) {
											$h .= '<small class="color-muted">'.\plant\VarietyUi::getPlantsWeight($eVariety, $eVariety['seeds']).'</small>';
										}
									$h .= '</div>';
								$h .= '</td>';
							}
							if($eAction['fqn'] !== ACTION_PLANTATION) {

								$h .= '<td class="text-end '.($eVariety['targeted'] ? 'color-warning' : '').'">';

									if($eVariety['seeds'] > 0) {
										$h .= $eVariety['seeds'].($eVariety['targeted'] ? '&nbsp;*' : '');
									} else {
										$h .= '?';
									}

									$h .= '<div>';
										if($eVariety->exists() and $eVariety['seeds'] > 0 and $eVariety['weightSeed1000'] !== NULL) {
											$h .= '<small class="color-muted">'.\plant\VarietyUi::getSeedsWeight1000($eVariety, $eVariety['seeds']).'</small>';
										}
									$h .= '</div>';

								$h .= '</td>';
							}
							if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {

								$h .= '<td>';

									foreach($eVariety['tools'] as ['youngPlants' => $youngPlants, 'tool' => $eTool]) {

										$h .= '<div>';
											$h .= '<b>'.(ceil($youngPlants / $eTool['routineValue']['value'] * 10) / 10).'</b> ';
											$h .= '<small class="color-muted tasks-week-plant-tray">'.encode($eTool['name']).'</span>';
										$h .= '</div>';

									}

								$h .= '</td>';

							}
						$h .= '</tr>';

					}

				$h .= '</tbody>';

			}

			$h .= '</table>';

		$h .= '</div>';

		if($targeted) {
			$h .= (new CultivationUi())->getWarningTargeted();
		}

		return $h;

	}

	protected function getListByTool(\farm\Action $eAction, \Collection $cTask): string {

		$validTasks = $cTask->find(fn($eTask) => $eTask['series']->notEmpty())->count();

		$h = '';

		if($validTasks !== $cTask->count()) {

			$h .= '<div class="util-info">';
				$h .= s("Le détail par matériel n'est affiché que pour les interventions créées au sein de séries.");
			$h .= '</div>';

		}

		if($validTasks === 0) {
			return $h;
		}

		$targeted = FALSE;

		$cTask = (clone $cTask)->filter(fn($eTask) => $eTask['series']->notEmpty());

		$cTool = new \Collection();

		foreach($cTask as $eTask) {

			$eSeries = $eTask['series'];

			foreach($eTask['cTool?']() as $eTool) {

				$cTool[$eTool['id']] ??= $eTool->merge([
					'youngPlants' => 0,
					'seeds' => 0,
					'area' => 0,
					'length' => 0,
					'targeted' => FALSE,
				]);

				if($eSeries->notEmpty()) {

					if(
						in_array($eAction['fqn'], [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION]) and
						$eTask['cultivation']->notEmpty()
					) {

						foreach($eTask['cultivation']['cSlice'] as $eSlice) {

							if($eSlice['youngPlants'] !== NULL) {
								$cTool[$eTool['id']]['youngPlants'] += $eSlice['youngPlants'];
							}

							if($eSlice['seeds'] !== NULL) {
								$cTool[$eTool['id']]['seeds'] += $eSlice['seeds'];
							}

						}

					}

					$cTool[$eTool['id']]['area'] += ($eSeries['area'] ?? $eSeries['areaTarget']);
					$cTool[$eTool['id']]['length'] += ($eSeries['length'] ?? $eSeries['lengthTarget']);

					if($eSeries->isTargeted()) {
						$cTool[$eTool['id']]['targeted'] = TRUE;
						$targeted = TRUE;
					}

				}

			}

		}

		$cTool->sort('name', natural: TRUE);

		$h = '<div class="stick-xs">';

			$h .= '<table class="tr-even table-block">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">';
							$h .= s("Matériel");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Surface");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= '<span class="hide-xs-down">'.s("Longueur de planche").'</span>';
							$h .= '<span class="hide-sm-up">'.s("Longueur").'</span>';
						$h .= '</th>';
						if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE or $eAction['fqn'] === ACTION_PLANTATION) {
							$h .= '<th class="text-end hide-xxs-down">';
								$h .= s("Plants");
							$h .= '</th>';
						}
						if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE or $eAction['fqn'] === ACTION_SEMIS_DIRECT) {
							$h .= '<th class="text-end hide-xxs-down">';
								$h .= s("Semences");
							$h .= '</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cTool as $eTool) {

					$h .= '<tr>';
						$h .= '<td>';
							$h .= \farm\ToolUi::getVignette($eTool, '4rem', '3rem').' '.\farm\ToolUi::link($eTool);
						$h .= '</td>';
						$h .= '<td class="text-end">';

							switch($eTool['routineName']) {

								case 'tray' :
									if($eTool['youngPlants'] > 0) {
										$h .= '<b>'.(ceil($eTool['youngPlants'] / $eTool['routineValue']['value'] * 10) / 10).'</b>';
									}
									break;

							}

						$h .= '</td>';
						$h .= '<td class="text-end '.($eTool['targeted'] ? 'color-warning' : '').'">';
							if($eTool['area'] > 0) {
								$h .= s("{value} m²", $eTool['area']).($eTool['targeted'] ? '&nbsp;*' : '');
							} else {
								$h .= '?';
							}
						$h .= '</td>';
						$h .= '<td class="text-end '.($eTool['targeted'] ? 'color-warning' : '').'">';
							if($eTool['length'] > 0) {
								$h .= s("{value} mL", $eTool['length']).($eTool['targeted'] ? '&nbsp;*' : '');
							} else {
								$h .= '?';
							}
						$h .= '</td>';
						if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE or $eAction['fqn'] === ACTION_PLANTATION) {
							$h .= '<td class="text-end hide-xxs-down '.($eTool['targeted'] ? 'color-warning' : '').'">';
								if($eTool['youngPlants'] > 0) {
									$h .= $eTool['youngPlants'].($eTool['targeted'] ? '&nbsp;*' : '');
								} else {
									$h .= '?';
								}
							$h .= '</td>';
						}
						if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE or $eAction['fqn'] === ACTION_SEMIS_DIRECT) {
							$h .= '<td class="text-end hide-xxs-down '.($eTool['targeted'] ? 'color-warning' : '').'">';
								if($eTool['seeds'] > 0) {
									$h .= $eTool['seeds'].($eTool['targeted'] ? '&nbsp;*' : '');
								} else {
									$h .= '?';
								}
							$h .= '</td>';
						}
					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		if($targeted) {
			$h .= (new CultivationUi())->getWarningTargeted();
		}

		return $h;

	}

	protected function displayAction(Task $eTask, string $content): string {

		$h = '<h3>'.encode($eTask['action']['name']).'</h3>';

		if($eTask['cultivation']->notEmpty()) {

			$ePlant = $eTask['plant'];

			$info = [
				'plant' => \plant\PlantUi::getVignette($ePlant, '1.5rem').' <a href="/plant/plant:update?id='.$ePlant['id'].'">'.encode($ePlant['name']).'</a>'
			];

			switch($eTask['cultivation']['sliceError']) {

				case 'density' :
					$h .= '<div class="util-danger">'.\Asset::icon('exclamation-circle').' '.s("Il manque la densité d'implantation de la production pour afficher ces informations.").'</div>';
					break;

				case 'area' :
					$h .= '<div class="util-danger">'.\Asset::icon('exclamation-circle').' '.s("Il manque la surface de cette série pour afficher ces informations.").'</div>';
					break;

				case 'seedling' :
					$h .= '<div class="util-danger">'.\Asset::icon('exclamation-circle').' '.s("Il manque le choix d'implantation de la production pour afficher ces informations.").'</div>';
					break;

				default :

					switch($eTask['cultivation']['seedling']) {

						case Cultivation::SOWING :
							if($ePlant['seedsSafetyMargin'] !== NULL) {
								$h .= '<div class="util-block-help">';
									$h .= s("Les quantités indiquées incluent la marge de sécurité de {value} % sur vos semis directs de {plant}.", ['value' => $ePlant['seedsSafetyMargin']] + $info);
								$h .= '</div>';
							}
							break;

						case Cultivation::YOUNG_PLANT :
							if($ePlant['plantsSafetyMargin'] !== NULL) {
								$h .= '<div class="util-block-help">';
									$h .= s("Les quantités indiquées incluent la marge de sécurité de {value} % sur vos plants autoproduits de {plant}.", ['value' => $ePlant['plantsSafetyMargin']] + $info);
								$h .= '</div>';
							}
							break;

					}

			}

		}

		$h .= '<div class="task-item-action">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

	protected function displayInvalidAction(Task $eTask, string $content): string {

		$h = '<h3>'.encode($eTask['action']['name']).'</h3>';
		$h .= '<div class="task-item-action">';
			$h .= '<div class="util-danger">'.\Asset::icon('exclamation-circle').' '.$content.'</div>';
		$h .= '</div>';

		return $h;

	}

	protected function displaySowing(Task $eTask): string {

		if($eTask['cultivation']['seedling'] === Cultivation::YOUNG_PLANT or $eTask['cultivation']['seedling'] === Cultivation::YOUNG_PLANT_BOUGHT) {
			return $this->displayInvalidAction($eTask, s("Ce semis direct est incohérent avec l'implantation que vous avez choisie pour cette production."));
		}

		$h = '<dl class="util-presentation util-presentation-2">';
			$h .= $this->displaySowingPresentation($eTask);
		$h .= '</dl>';

		return $this->displayAction($eTask, $h);

	}

	protected function displaySowingPresentation(Task $eTask): string {

		$eTask->expects([
			'cultivation' => ['series']
		]);

		$eCultivation = $eTask['cultivation'];
		$eSeries = $eCultivation['series'];

		$uiCrop = new \production\CropUi();

		$h = $uiCrop->getPresentationDistance($eSeries, $eCultivation);
		$h .= $this->getPresentationSize($eSeries);
		$h .= $this->getPresentationSeeds($eTask);
		$h .= $this->getPresentationSeedling($eTask);

		return $h;

	}

	protected function displayYoungPlant(Task $eTask): string {

		if($eTask['cultivation']['seedling'] === Cultivation::SOWING or $eTask['cultivation']['seedling'] === Cultivation::YOUNG_PLANT_BOUGHT) {
			return $this->displayInvalidAction($eTask, s("Ce semis en pépinière est incohérent avec l'implantation que vous avez choisie pour cette production."));
		}

		$h = '<dl class="util-presentation util-presentation-2">';
			$h .= $this->displayYoungPlantPresentation($eTask);
			$h .= $this->displayYoungPlantTools($eTask);
		$h .= '</dl>';

		if(
			$eTask['cultivation']['cSlice']->contains(fn($eSlice) => $eSlice['youngPlants'] !== NULL) and
			$eTask['series']->isTargeted()
		) {
			$h .= '<br/>';
			$h .= (new CultivationUi())->getWarningTargeted();
		}

		return $this->displayAction($eTask, $h);

	}

	protected function displayYoungPlantPresentation(Task $eTask): string {

		$eTask->expects([
			'cultivation' => ['series']
		]);

		$h = $this->getPresentationYoungPlants($eTask);
		$h .= $this->getPresentationSeeds($eTask);
		$h .= $this->getPresentationSeedling($eTask);

		return $h;

	}

	protected function displayYoungPlantTools(Task $eTask): string {

		$cSlice = $eTask['cultivation']['cSlice'];

		$youngPlants = $cSlice->sum('youngPlants');

		if($youngPlants === 0) {
			return '';
		}

		$h = '';

		foreach($eTask['cTool?']() as $eTool) {

			if(
				$eTool['routineName'] === 'tray' and
				$eTool['routineValue']['value']
			) {

				$h .= '<dt>'.s("Matériel").'</dt>';
				$h .= '<dd>';
					$h .= '<b>';
						$h .= encode($eTool['name']);
					$h .= '</b>';

					$h .= '<div class="task-presentation-seedling-tools">';

						$sum = 0;

						foreach($cSlice as $eSlice) {

							$eVariety = $eSlice['variety'];
							$trays = $eSlice['youngPlants'] ? (ceil($eSlice['youngPlants'] / $eTool['routineValue']['value'] * 10) / 10) : NULL;

							$h .= '<div>';
								$h .= $eVariety->empty() ? '<i>'.s("Variété non renseignée").'</i>' : encode($eVariety['name']);
							$h .= '</div>';
							$h .= '<div class="text-end '.($eTask['series']->isTargeted() ? 'color-warning' : '').'">';
								if($trays !== NULL) {
									$sum += $trays;
									$h .= $trays.' '.($eTask['series']->isTargeted() ? '*' : '');
								} else {
									$h .= '?';
								}
							$h .= '</div>';
						}

						if($cSlice->count() >= 2 and $sum > 0) {
							$h .= '<div><b>'.s("Total").'</b></div>';
							$h .= '<div class="text-end '.($eTask['series']->isTargeted() ? 'color-warning' : '').'">';
								$h .= $sum.' '.($eTask['series']->isTargeted() ? '*' : '');
							$h .= '</div>';
						}

					$h .= '</div>';
				$h .= '</dd>';

			}
		}

		return $h;

	}

	protected function displayPlanting(Task $eTask): string {

		if($eTask['cultivation']['seedling'] === Cultivation::SOWING) {
			return $this->displayInvalidAction($eTask, s("Cette plantation est incohérente avec l'implantation en semis direct que vous avez choisie pour cette production."));
		}

		$h = '<dl class="util-presentation util-presentation-2">';
			$h .= $this->displayPlantingPresentation($eTask);
		$h .= '</dl>';

		return $this->displayAction($eTask, $h);


	}

	protected function displayFertilizer(Task $eTask, \Collection $cPlace): string {

		if(
			$eTask['fertilizer'] === NULL or
			array_filter($eTask['fertilizer']) === []
		) {
			return '';
		}

		$eSeries = $eTask['series'];
		$cTool = $eTask['cToolFertilizer'];

		if($cTool->empty()) {
			return '';
		}

		$form = new \util\FormUi();

		// Liste des N, P, K de la tâche
		$elementsValues = [];

		foreach(\farm\RoutineUi::getListFertilizer() as $key => $label) {
			if($eTask['fertilizer'][$key] !== NULL) {
				$elementsValues[$key] = $eTask['fertilizer'][$key].' '.$label;
			}
		}

		$elementSelected = GET('element', array_keys($elementsValues), default: array_key_first($elementsValues));

		if(count($elementsValues) > 1) {

			$fieldElement = $form->select(NULL, $elementsValues, $elementSelected, attributes: [
				'data-action' => 'task-fertilizer-element',
				'class' => 'task-fertilizer-field',
				'onchange' => '',
				'mandatory' => TRUE
			]);

		} else {
			$fieldElement = first($elementsValues);
			$fieldElement .= $form->hidden(NULL, $elementSelected, ['data-action' => 'task-fertilizer-element']);
		}

		// Liste des fertilisants
		$toolsValues = [];

		foreach($cTool as $eTool) {

			$name = encode($eTool['name']);

			[$major, $minor] = (new \farm\RoutineUi())->getSeparateFertilizer($eTool['routineValue'], ' ');

			if($minor or $major) {

				$name .= '  →  ';
				$name .= implode(' / ', $major);
				if($major and $minor) {
					$name .= ' + ';
				}
				$name .= implode(' / ', $minor);

			}

			$toolsValues[$eTool['id']] = $name;

		}

		$default = array_key_first($toolsValues);

		foreach($eTask['cTool?']() as $eTool) {
			if($eTool['routineName'] === 'fertilizer') {
				$default = $eTool['id'];
				break;
			}
		}

		$toolSelected = (int)GET('tool', array_keys($toolsValues), default: $default);
		$eToolSelected = $cTool->find(fn($eTool) => $eTool['id'] === $toolSelected, limit: 1);

		if(count($toolsValues) > 1) {

			$fieldTool = $form->select(NULL, $toolsValues, $toolSelected, attributes: [
				'data-action' => 'task-fertilizer-tool',
				'class' => 'task-fertilizer-field',
				'placeholder' => 'Choisissez un intrant'
			]);

		} else {
			$fieldTool = $form->select(NULL, $toolsValues, $toolSelected, attributes: [
				'data-action' => 'task-fertilizer-tool',
				'class' => 'task-fertilizer-field',
				'disabled'
			]);
		}

		$elementExpected = $eTask['fertilizer'][$elementSelected];
		$elementPart = $eToolSelected['routineValue'][$elementSelected];

		$h = '<h3>';
			$h .= s("Fertilisation pour {value}", $fieldElement);
		$h .= '</h3>';

		$h .= '<div class="mb-1">';
			$h .= $form->inputGroup(
				$form->addon(s("Intrant")).
				$fieldTool
			);
		$h .= '</div>';

		$h .= '<div class="util-overflow-xs">';
			$h .= '<div class="task-fertilizer-grid">';

				$h .= '<div class="util-grid-header">'.s("Parcelle").'</div>';
				$h .= '<div class="util-grid-header">'.s("Bloc").'</div>';
				$h .= '<div class="util-grid-header">';
					if($eSeries['use'] === Series::BED) {
						$h .= s("Planche");
					}
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end">'.s("Surface").'</div>';
				$h .= '<div class="util-grid-header">'.s("Intrant").'</div>';

				foreach($cPlace as $ePlace) {

					$h .= '<div class="task-fertilizer-grid-zone">'.encode($ePlace['zone']['name']).'</div>';
					$h .= '<div class="task-fertilizer-grid-plot">'.encode($ePlace['plot']['name']).'</div>';
					$h .= '<div class="task-fertilizer-grid-bed">';
						if($eSeries['use'] === Series::BED) {

							if($ePlace['bed']['name'] !== NULL) {
								$h .= encode($ePlace['bed']['name']);
							} else {
								$h .= s("Temporaire");
							}

						}
					$h .= '</div>';
					$h .= '<div class="task-fertilizer-grid-size util-unit">';
						$h .= s("{value} m²", round($ePlace['area']));
					$h .= '</div>';
					$h .= '<div class="task-fertilizer-grid-weight">';

						if($elementPart === NULL) {
							$h .= s("Pas de {value} dans l'intrant", \farm\RoutineUi::getListFertilizer()[$elementSelected]);
						} else {
							$h .= s("{value} kg", round($ePlace['area'] * $elementExpected / 10000 / ($elementPart / 100), 1));
						}

					$h .= '</div>';

				}

				if($cPlace->count() > 1) {
					$h .= '<div style="grid-column: span 3"></div>';

					$h .= '<div class="task-fertilizer-grid-size util-unit task-fertilizer-grid-total">';
						$h .= s("{value} m²", round($eSeries['area']));
					$h .= '</div>';
					$h .= '<div class="task-fertilizer-grid-weight task-fertilizer-grid-total">';

						if($elementPart === NULL) {
							$h .= s("Pas de {value} dans l'intrant", \farm\RoutineUi::getListFertilizer()[$elementSelected]);
						} else {
							$h .= s("{value} kg", round($eSeries['area'] * $elementExpected / 10000 / ($elementPart / 100), 1));
						}

					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		$h .= '<br/>';

		return $h;


	}

	protected function displayPlantingPresentation(Task $eTask): string {

		$eTask->expects([
			'cultivation' => ['series']
		]);

		$eCultivation = $eTask['cultivation'];
		$eSeries = $eCultivation['series'];

		$uiCrop = new \production\CropUi();

		$h = $uiCrop->getPresentationDistance($eSeries, $eCultivation);
		$h .= $this->getPresentationSize($eSeries);
		$h .= $this->getPresentationYoungPlants($eTask);

		return $h;

	}



	protected function getPresentationSize(Series $eSeries): string {

		if($eSeries['length'] !== NULL) {
			$size = s("{value} mL", $eSeries['length']);
		} else if($eSeries['area'] > 0) {
			$size = s("{value} m²", $eSeries['area']);
		} else {
			$size = NULL;
		}


		$h = '<dt>'.s("Surface").'</dt>';
		$h .= '<dd>'.$size.'</dd>';

		return $h;

	}

	protected function getPresentationYoungPlants(Task $eTask): string {

		$h = '<dt>'.s("Plants").'</dt>';
		$h .= '<dd>'.$this->getPresentationSeedlingList($eTask, 'youngPlants').'</dd>';

		return $h;

	}

	protected function getPresentationSeeds(Task $eTask): string {

		$h = '<dt>'.s("Semences").'</dt>';
		$h .= '<dd>'.$this->getPresentationSeedlingList($eTask, 'seeds').'</dd>';

		return $h;

	}

	protected function getPresentationSeedling(Task $eTask): string {

		$h = '';

		if($eTask['cultivation']['seedling'] === Cultivation::YOUNG_PLANT) {
			$h .= '<dt>'.s("Semis").'</dt>';
			$h .= '<dd>'.p("{value} graine / plant", "{value} graines / plant", $eTask['cultivation']['seedlingSeeds']).'</dd>';
		} else if($eTask['cultivation']['seedling'] === Cultivation::SOWING and $eTask['cultivation']['seedlingSeeds'] > 1) {
			$h .= '<dt>'.s("Semis").'</dt>';
			$h .= '<dd>'.p("{value} graine / trou", "{value} graines / trou", $eTask['cultivation']['seedlingSeeds']).'</dd>';
		}

		return $h;

	}

	protected function getPresentationSeedlingList(Task $eTask, string $property): string {

		$cSlice = $eTask['cultivation']['cSlice'];
		$sum = 0;

		$list = '<div class="task-presentation-seedling">';

		foreach($cSlice as $eSlice) {

			$eVariety = $eSlice['variety'];

			$list .= '<div>';
				$list .= $eVariety->empty() ? '<i>'.s("Variété non renseignée").'</i>' : encode($eVariety['name']);
			$list .= '</div>';
			$list .= '<div class="text-end">';
				if($eSlice[$property] !== NULL) {
					$sum += $eSlice[$property];
					$list .= $eTask['series']->isTargeted() ? '<span class="color-warning">'.$eSlice[$property].' *</span>' : $eSlice[$property];
				} else {
					$list .= '?';
				}
			$list .= '</div>';
			$list .= '<div>';
				if(
					$eSlice[$property] !== NULL and
					$eVariety->notEmpty()
				) {
					$list .= match($property) {
						'seeds' => ($eVariety['weightSeed1000'] !== NULL) ? '<small class="color-muted">('.\plant\VarietyUi::getSeedsWeight1000($eVariety, $eSlice[$property]).')</small>' : '',
						'youngPlants' => ($eVariety['numberPlantKilogram'] !== NULL) ? '<small class="color-muted">('.\plant\VarietyUi::getPlantsWeight($eVariety, $eSlice[$property]).')</small>' : ''
					};
				}
			$list .= '</div>';
		}

		if($cSlice->count() >= 2 and $sum > 0) {
			$list .= '<div><b>'.s("Total").'</b></div>';
			$list .= '<div class="text-end '.($eTask['series']->isTargeted() ? 'color-warning' : '').'">';
				$list .= $sum.' '.($eTask['series']->isTargeted() ? '*' : '');
			$list .= '</div>';
		}

		$list .= '</div>';

		return $list;

	}

	public function getPlace(Task $eTask): string {

		if($eTask['series']->empty() === FALSE) {
			return s("Série {value}", SeriesUi::link($eTask['series'], newTab: TRUE));
		} else if($eTask['cccPlace']->notEmpty()) {
			return (new CultivationUi())->displayPlaces(Series::BED, $eTask['cccPlace']);
		} else {
			return '';
		}

	}

	public function createFromOneSeries(Task $eTask, Series $eSeries): \Panel {

		$panel = $this->createFromSeries($eTask, FALSE, function(\util\FormUi $form) use ($eTask, $eSeries) {
			return $form->hidden('series[]', $eSeries['id']);
		});

		$panel->subTitle = SeriesUi::getPanelHeader($eSeries);

		return $panel;

	}

	public function createFromAllSeries(Task $eTask, \Collection $cSeries): \Panel {

		return $this->createFromSeries($eTask, TRUE, function(\util\FormUi $form) use ($eTask, $cSeries) {

			$series = '<div id="task-create-plant">';
				$series .= $form->dynamicField($eTask, 'plantsFilter');
			$series .= '</div>';

			// Dans le cas des récoltes, on affiche les séries uniquement si on a filtré sur une espèce
			if($this->canDisplayMultipleSeries($eTask)) {
				$series .= $this->getSeriesListField($form, $eTask, $cSeries);
			}

			return $this->getCreateSeasonGroup($form, $eTask).$form->group(
				p("Série", "Séries", $cSeries->count()),
				$series,
				['wrapper' => 'series']
			);

		});

	}

	protected function canDisplayMultipleSeries(Task $eTask): bool {

		// Dans le cas des récoltes, on affiche les séries uniquement si on a filtré sur une espèce
		return (
			$eTask['action']->empty() or
			$eTask['action']['fqn'] !== ACTION_RECOLTE or
			$eTask['plant']->notEmpty()
		);

	}

	/**
	 * Recherche parmi les séries
	 *
	 * Field checkbox ou hidden
	 */
	public function getSeriesListField(\util\FormUi $form, Task $eTask, \Collection $cSeries): string {

		if($cSeries->empty()) {
			return '<div class="util-warning">'.s("Il n'y a aucune série à afficher pour renseigner une intervention.").'</div>';
		}

		$h = '';

		$h .= '<table id="series-field-table" class="table-block stick-xs">';

			$h .= '<thead>';
				$h .= '<tr>';
					if($cSeries->count() > 1) {
						$h .= '<td id="series-field-filter" class="td-checkbox bg-secondary" title="'.s("Tout cocher / Tout décocher").'">';

							if(
								$eTask['plant']->notEmpty() and
								$cSeries->match(fn($eSeries) => $eSeries['cCultivation']->count() > 1, 1)
							) {

								$h .= '<a class="btn btn-secondary dropdown-toggle" data-dropdown="bottom-start">'.\Asset::icon('check2-square').'</a>';
								$h .= '<div class="dropdown-list bg-secondary">';
									$h .= '<div class="dropdown-title">'.s("Tout cocher ou tout décocher").'</div>';

									$h .= '<label class="dropdown-item">';
										$h .= '<input type="checkbox" '.attr('onclick', 'Task.selectAll(this, null)').'"/>';
										$h .= ' '.s("Interventions partagées");
									$h .= '</label>';

									$h .= '<label class="dropdown-item">';
										$h .= '<input type="checkbox" '.attr('onclick', 'Task.selectAll(this, '.$eTask['plant']['id'].')').'"/>';
										$h .= ' '.s("Interventions sur {value}", \plant\PlantUi::getVignette($eTask['plant'], '1.5rem').' '.encode($eTask['plant']['name']));
									$h .= '</label>';

								$h .= '</div>';

							} else {

								$h .= '<label>';
									$h .= '<input type="checkbox" '.attr('onclick', 'Task.selectAll(this, null)').'"/>';
								$h .= '</label>';

							}

						$h .= '</td>';
					} else {
						$h .= '<th></th>';
					}
					$h .= '<th>'.s("Série").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			foreach($cSeries as $eSeries) {

				$cCultivation = $eSeries['cCultivation'];

				$association = $cCultivation->count() > 1;
				$checked = ($cSeries->count() === 1 and $association === FALSE);

				$places = (new CultivationUi())->displayPlaces($eSeries['use'], $eSeries['cccPlace']);

				$h .= '<tbody>';

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';

							$h .= '<label>';
								$h .= $form->inputCheckbox('series[]', $eSeries['id'], [
									'data-filter-cultivations' => $cCultivation->count(),
									'oninput' => 'Task.selectSeriesCheckbox(this)',
									'onrender' => $checked ? 'Task.selectSeriesCheckbox(this)' : NULL,
									 $checked ? 'checked' : NULL,
									 ($cCultivation->count() > 1) ? 'disabled' : NULL
								]);
							$h .= '</label>';

							if($association === FALSE) {
								$h .= $form->hidden('cultivation['.$eSeries['id'].']', $eSeries['cCultivation']->first()['id'], [
									'data-plant' => $cCultivation->first()['plant']['id']
								]);
							}

						$h .= '</td>';
						$h .= '<td>';

							if($association === FALSE) {

								$h .= '<div class="task-field-vignette">';

									$h .= '<div>';
										$h .= \plant\PlantUi::getVignette($cCultivation->first()['plant'], '2rem');
									$h .= '</div>';
									$h .= '<div>';

							}

							$h .= '<div class="task-field-link">';
								$h .= SeriesUi::link($eSeries, newTab: TRUE);
								if($association === FALSE) {
									$h .= ' '.\production\CropUi::start($cCultivation->first(), \Setting::get('farm\mainActions'));
								}
							$h .= '</div>';
							$h .= '<div class="task-field-place">';
								if($places) {
									$h .= $places;
								} else {
									$h .= s("Assolement non renseigné");
								}
							$h .= '</div>';

							if($association === FALSE) {

									$h .= '</div>';
								$h .= '</div>';

							}

						$h .= '</td>';
						$h .= '<td class="task-field-area">';
							if($eSeries['area']) {
								$h .= s("{value} m²", $eSeries['area']);
							} else {
								$h .= '<a href="/series/place:update?series='.$eSeries['id'].($eSeries['mode'] === Series::GREENHOUSE ? '&mode='.Series::GREENHOUSE : '').'" class="btn btn-outline-secondary">'.s("Assoler").'</a>';
							}
						$h .= '</td>';
					$h .= '</tr>';

					if($association) {

						$h .= '<tr>';
							$h .= '<td colspan="4" class="series-field-cultivations">';

								$h .= '<label class="series-field-cultivation">';
									$h .= $form->inputRadio('cultivation['.$eSeries['id'].']', '', s("Partagé"), attributes: [
										'checked' => FALSE,
										'oninput' => 'Task.selectCultivationRadio(this)'
									]);
								$h .= '</label>';

								foreach($eSeries['cCultivation'] as $eCultivation) {

									$label = \plant\PlantUi::getVignette($eCultivation['plant'], '2rem').' '.encode($eCultivation['plant']['name']);
									$label .= ' '.\production\CropUi::start($eCultivation, \Setting::get('farm\mainActions'));

									$h .= '<label class="series-field-cultivation">';
										$h .= $form->inputRadio('cultivation['.$eSeries['id'].']', $eCultivation['id'], $label, attributes: [
											'data-plant' => $eCultivation['plant']['id'],
											'oninput' => 'Task.selectCultivationRadio(this)',
										]);
									$h .= '</label>';

								}

							$h .= '</td>';
						$h .= '</tr>';

					}

					$h .= '</body>';

			}

		$h .= '</table>';

		return $h;

	}

	public function createFromSeries(Task $eTask, bool $multipleSeries, \Closure $series): \Panel {

		$eTask->expects(['farm', 'action', 'plannedWeek', 'doneWeek', 'status']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/task:doCreateFromSeriesCollection', ['autocomplete' => 'off', 'data-farm' => $eTask['farm']['id']]);

			$h .= $form->hidden('farm', $eTask['farm']['id']);
			$h .= $form->hidden('status', $eTask['status']);

			$h .= $series->call($this, $form);

			if(
				$multipleSeries === FALSE or
				$this->canDisplayMultipleSeries($eTask)
			) {

				$h .= $this->getCreateActionGroup($form, $eTask);

				$h .= '<div id="task-create-variety">';
					$h .= $this->getVarietyGroup($form, $eTask, $eTask['cVariety'], $eTask['varietiesIntersect']);
				$h .= '</div>';

				$h .= '<div id="task-create-size">';
					if($eTask['cSize']->notEmpty()) {
						$h .= $this->getHarvestSizeField($form, $eTask);
					}
				$h .= '</div>';

				$h .= '<div id="task-create-fertilizer">';
					$h .= $this->getFertilizerField($form, $eTask);
				$h .= '</div>';

				$h .= $this->getMethodsGroup($form, $eTask);
				$h .= $this->getToolsGroup($form, $eTask);
				$h .= $this->getTimeGroup($form, $eTask);

				if($eTask['farm']->hasFeatureTime()) {
					$h .= $form->dynamicGroup($eTask, 'timeExpected');
				}

				$h .= $form->dynamicGroup($eTask, 'description');

				$h .= $form->group(
					content: $form->submit(s("Ajouter l'intervention"))
				);

			}

		$h .= $form->close();

		$title = $this->getCreateTitle($eTask);

		return new \Panel(
			id: 'panel-task-create',
			title: $title,
			body: $h
		);

	}

	protected function getCreateSeasonGroup(\util\FormUi $form, Task $e): string {

		$seasons = '';

		for($i = max(date('Y') - 1, $e['farm']['seasonFirst']); $i <= min(date('Y') + 1, $e['farm']['seasonLast']); $i++) {

			$action = $e['action']->notEmpty() ? $e['action']['id'] : '';

			$seasons .= '<a href="/series/task:createFromSeries?farm='.$e['farm']['id'].'&season='.$i.'&action='.$action.'&'.http_build_query($e->extracts(['doneWeek', 'plannedWeek', 'status'])).'" class="btn btn-form '.($i === $e['season'] ? 'btn-selected' : '').'">'.$i.'</a> ';

		}

		return $form->group(
			s("Saison"),
			'<div>'.$seasons.'</div>'
		);

	}

	protected function getCreateActionGroup(\util\FormUi $form, Task $e): string {

		$e->expects('action');

		$h = '';

		$eSeries = $e['series'];

		if($eSeries->notEmpty()) {

			$cCultivation = $eSeries['cCultivation'];

			if($cCultivation->count() > 1) {

				$values = [];

				if($e['action']->empty() or $e['action']['fqn'] !== ACTION_RECOLTE) {
					$values[NULL] = s("Partagé");
				}

				foreach($cCultivation as $eCultivation) {
					$values[$eCultivation['id']] = \plant\PlantUi::getVignette($eCultivation['plant'], '2rem').' '.encode($eCultivation['plant']['name']);
				}

				$h .= $form->radios('cultivation['.$eSeries['id'].']', $values, $e['cultivation'], attributes: [
					'mandatory' => TRUE,
					'callbackRadioAttributes' => function() {
						return [
							'onclick' => 'Task.createSelectCultivation(this)'
						];
					}
				]);


				$h .= '<br/>';

			} else {
				$h .= $form->hidden('cultivation['.$eSeries['id'].']', $cCultivation->first()['id']);
			}

		}

		if($e['action']->empty()) {

			$h .= $form->dynamicField($e, 'action');

		} else {

			$h .= $form->hidden('action', $e['action']['id'], [
				'data-fqn' => $e['action']['fqn']
			]);
			$h .= '<h3>'.encode($e['action']['name']).'</h3>';
		}

		return $form->group(
			s("Intervention"),
			$h,
			['wrapper' => 'action']
		);

	}

	public function createFromScratch(Task $eTask, \Collection $cAction, \Collection $cCategory): \Panel {

		$eTask->expects(['farm', 'series', 'action', 'cultivation', 'category', 'status']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/task:doCreate', ['id' => 'task-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('status', $eTask['status']);
			$h .= $form->hidden('farm', $eTask['farm']['id']);

			if($eTask['action']->empty()) {

				$h .= $this->getCreateScratchActionGroup($eTask, $cAction, $cCategory);

			} else {

				$h .= $form->hidden('action', $eTask['action']['id']);
				$h .= $form->hidden('category', $eTask['category']['id']);

				if($eTask['category']['fqn'] === CATEGORIE_CULTURE) {

					$h .= $form->group(
						s("Espèce"),
						$form->dynamicField($eTask, 'plant', function(\PropertyDescriber $d) {
							$d->autocompleteDispatch = '#task-create-plant';
						}),
						['wrapper' => 'action plant cultivation', 'id' => 'task-create-plant', 'class' => 'form-group-highlight']
					);

				}

				$h .= $form->group(
					s("Intervention"),
					'<h3>'.encode($eTask['action']['name']).'</h3>'
				);

			}

			if($eTask['cSize']->notEmpty()) {
				$h .= $this->getHarvestSizeField($form, $eTask);
			}

			if($eTask['category']['fqn'] === CATEGORIE_CULTURE) {
				$h .= '<div id="task-create-fertilizer">';
					$h .= $this->getFertilizerField($form, $eTask);
				$h .= '</div>';
			}

			$h .= $this->getMethodsGroup($form, $eTask);
			$h .= $this->getToolsGroup($form, $eTask);
			$h .= $this->getTimeGroup($form, $eTask);

			if($eTask['farm']->hasFeatureTime()) {
				$h .= $form->dynamicGroup($eTask, 'timeExpected');
			}

			$h .= $form->dynamicGroup($eTask, 'description');

			$h .= $form->group(
				content: $form->submit(s("Ajouter l'opération"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-task-create',
			title: $this->getCreateTitle($eTask),
			body: $h
		);

	}

	public function getCreateScratchActionGroup(Task $eTask, \Collection $cAction, \Collection $cCategory): string {

		$eTask->expects(['status', 'farm', 'category']);

		$form = new \util\FormUi([
			'firstColumnSize' => 25
		]);

		$h = '<div id="task-write-action">';

			$h .= $form->hidden('category', $eTask['category']['id']);

			$tabs = '<div class="tabs-categories">';

				foreach($cCategory as $eCategory) {

					$get = $_REQUEST;
					$get['category'] = $eCategory['id'];

					$tabs .= '<a data-ajax="/series/task:createFromScratch?'.http_build_query($get).'" data-ajax-method="get" class="tabs-category '.($eTask['category']['id'] === $eCategory['id'] ? 'active' : '').'">'.encode($eCategory['name']).'</a>';

				}

			$tabs .= '</div>';

			$h .= $form->group('', $tabs);

			if($eTask['category']['fqn'] === CATEGORIE_CULTURE) {
				$h .= $form->group(
					s("Espèce"),
					$form->dynamicField($eTask, 'plant', function(\PropertyDescriber $d) {
						$d->autocompleteDispatch = '#task-create-plant';
					}),
					['id' => 'task-create-plant']
				);
			}

			if($cAction->empty()) {
				$actions = '<p class="util-warning">'.s("Aucune intervention n'a été configurée dans cette catégorie.").'</p>';
			} else {
				$actions = $form->dynamicField($eTask, 'action', function(\PropertyDescriber $d) use ($cAction) {
					$d->values = $cAction;
				});
			}

			$h .= $form->group(
				s("Intervention"),
				$actions,
				['wrapper' => 'action']
			);

		$h .= '</div>';

		return $h;

	}

	protected function getCreateTitle(Task $eTask): string {
		
		$eTask->expects(['action']);
		
		if(
			$eTask['action']->empty() or
			$eTask['action']['fqn'] !== ACTION_RECOLTE
		) {

			return match($eTask['status']) {
				Task::TODO => s("Planifier une future intervention"),
				Task::DONE => s("Consigner une intervention")
			};
			
		} else {

			return match($eTask['status']) {
				Task::TODO => s("Planifier une future récolte"),
				Task::DONE => s("Consigner une récolte")
			};

		}

	}

	public function update(Task $eTask, \Collection $cAction): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/task:doUpdate', ['id' => 'task-update', 'data-ajax-origin' => \Route::getRequestedOrigin(), 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eTask['id']);

			if(
				$eTask['series']->notEmpty() and
				$eTask['action']['fqn'] !== ACTION_RECOLTE
			) {

				$eSeries = $eTask['series'];

				if(
					$eSeries->notEmpty() and
					$eSeries['cCultivation']->count() > 1
				) {

					$values = [
						NULL => s("Partagé")
					];

					foreach($eSeries['cCultivation'] as $eCultivation) {
						$values[$eCultivation['id']] = \plant\PlantUi::getVignette($eCultivation['plant'], '2rem').' '.encode($eCultivation['plant']['name']);
					}

					$action = $form->radios('cultivation', $values, $eTask['cultivation'], attributes: [
						'mandatory' => TRUE,
						'callbackRadioAttributes' => function() {
							return [
								'onclick' => 'Task.updateSelectCultivation(this)'
							];
						}
					]);

					$action .= '<br/>';

				} else {
					$action = $form->hidden('cultivation', $eTask['cultivation']);
				}

			} else {

				$action = '<div>';

					switch($eTask['category']['fqn']) {

						case CATEGORIE_CULTURE :
							if($eTask['plant']->empty()) {
								$action .= '<b>'.encode($eTask['category']['name']).'</b>';
							} else {
								$action .= \plant\PlantUi::getVignette($eTask['plant'], '3rem').' <b>'.encode($eTask['plant']['name']).'</b>';
							}
							break;

						default :
							$action .= '<b>'.encode($eTask['category']['name']).'</b>';
							break;

					}

				$action .= '</div>';
				$action .= '<br/>';

			}

			if($eTask['action']['fqn'] !== ACTION_RECOLTE) {

				$action .= $form->dynamicField($eTask, 'action', function(\PropertyDescriber $d) use ($cAction) {
					$d->values = $cAction;
				});

			} else {
				$action .= '<h3>'.encode($eTask['action']['name']).'</h3>';
			}

			$h .= $form->group($this->p('action'), $action, attributes: ['for' => FALSE]);

			// Changer de variété
			$h .= '<div data-ref="varieties">';
				if($eTask['cultivation']->notEmpty()) {
					$cVariety = $eTask['cultivation']['cSlice']->getColumnCollection('variety');
					$h .= $this->getVarietyGroup($form, $eTask, $cVariety);
				}
			$h .= '</div>';

			if($eTask['action']['fqn'] === ACTION_RECOLTE and $eTask['cSize']->notEmpty()) {
				$h .= $this->getHarvestSizeField($form, $eTask);
			}

			if($eTask['category']['fqn'] === CATEGORIE_CULTURE) {
				$h .= $this->getFertilizerField($form, $eTask);
			}

			$h .= $this->getMethodsGroup($form, $eTask);
			$h .= $this->getToolsGroup($form, $eTask);
			$h .= $this->getTimeGroup($form, $eTask);

			if(
				$eTask['farm']->hasFeatureTime() and
				$eTask['status'] === Task::TODO
			) {
				$h .= $form->dynamicGroup($eTask, 'timeExpected');
			}

			$h .= $form->dynamicGroup($eTask, 'description');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-task-update',
			title: s("Modifier une intervention"),
			subTitle: $eTask['series']->empty() ? NULL : SeriesUi::getPanelHeader($eTask['series']),
			body: $h,
			close: 'reloadOnHistory'
		);

	}

	public function updateIncrementPlannedCollection(\Collection $cTask): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/task:doIncrementPlannedCollection', ['autocomplete' => 'off']);

			$h .= $form->group(
				p("Intervention", "Interventions", $cTask->count()),
				$this->getTasksField($form, $cTask, displayPlanned: TRUE),
				['wrapper' => 'tasks']
			);

			$h .= $form->group(
				s("Décaler de ..."),
				$form->inputGroup(
					$form->number('increment', attributes: [
						'onrender' => 'this.focus();',
						'min' => -26,
						'max' => 26
					]).
					$form->addon(s("semaine(s)"))
				).
				\util\FormUi::info(s("Utilisez un nombre négatif pour décaler plus tôt et positif pour décaler plus tard"))
			);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Décaler"),
			body: $h
		);

	}

	public function updateHarvestCollection(\Collection $cTask, \Collection $cProductStock, \selling\Product $eProductBookmark): \Panel {

		$form = new \util\FormUi();

		$eTask = $cTask->first();

		$h = '';

		$h .= $form->openAjax('/series/task:doUpdateHarvestCollection', ['autocomplete' => 'off']);

			if($eTask['harvestUnit'] === NULL) {

				$h .= $form->group(
					s("Quantité récoltée"),
					'<div class="input-group">'.$form->dynamicField($eTask, 'harvestMore').$form->dynamicField($eTask, 'harvestUnit').'</div>',
					['wrapper' => 'harvest harvestUnit']
				);

			} else {

				$h .= $form->group(
					s("Quantité récoltée"),
					'<div class="input-group">'.$form->dynamicField($eTask, 'harvestMore').'<div class="input-group-addon">'.\selling\UnitUi::getSingular($eTask['harvestUnit']).'</div></div>',
					['wrapper' => 'harvest']
				);

			}

			$h .= $form->dynamicGroup($eTask, 'harvestDate');

			$h .= $this->getPlantsByTasksField($form, $cTask);
			$h .= $this->getStockField($form, $eTask, $cProductStock, $eProductBookmark);

			if($cTask->count() > 1) {
				$h .= $form->group(
					s("Répartition de la récolte sur les productions"),
					self::getDistributionField($form, $cTask, 'plant', withHarvest: FALSE)
				);
			}

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Compléter la récolte"),
			body: $h
		);

	}

	public function updatePlannedCollection(\Collection $cTask): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/task:doUpdatePlannedCollection', ['autocomplete' => 'off']);

			$h .= $this->getTasksField($form, $cTask, displayPlanned: TRUE);

			$h .= $form->dynamicGroup($cTask->first(), 'plannedWeek');

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Planifier"),
			body: $h
		);

	}
	
	public function getTasksField(\util\FormUi $form, \Collection $cTask, string $field = 'checkbox', string $class = '', bool $displayPlace = FALSE, bool $displayTime = FALSE, bool $displayArea = FALSE, bool $displayDensity = FALSE, bool $displayPlanned = FALSE): string {

		if($displayArea) {
			$cTask->expects(['distributionArea']);
		}

		$display = ($field === 'checkbox' and $cTask->count() > 1) ? 'checkbox' : 'hidden';

		$h = '<div class="'.$class.' stick-xs mb-1">';
			$h .= '<table class="table-block">';

				$h .= '<thead>';
					$h .= '<tr>';
						if($display === 'checkbox') {
							$h .= '<th></th>';
						}
						$h .= '<th>'.s("Intervention").'</th>';
						if($displayPlanned) {
							$h .= '<th>'.s("Planifiée").'</th>';
						}
						if($displayTime) {
							$h .= '<th>'.s("Temps<br/>passé").'</th>';
						}
						if($displayPlace) {
							$h .= '<th>'.s("Lieu").'</th>';
						}
						if($displayArea) {
							$h .= '<th class="text-end">'.s("Surface").'</th>';
						}
						if($displayDensity) {
							$h .= '<th class="text-end hide-sm-down">';
								$h .= s("Densité");
							$h .= '</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';


					foreach($cTask as $eTask) {
						$h .= '<tr>';
							if($display === 'checkbox') {
								$h .= '<td class="td-checkbox">';
									$h .= '<label>'.$form->inputCheckbox('ids[]', $eTask['id'], ['checked']).'</label>';
								$h .= '</td>';
							}
							$h .= '<td>';
								if($eTask['plant']->notEmpty()) {
									$h .= \plant\PlantUi::getVignette($eTask['plant'], '1.5rem').'&nbsp;&nbsp;';
								}
								$h .= $this->getAction($eTask);
							$h .= '</td>';
							if($displayPlanned) {
								$h .= '<td>';
									if($eTask['plannedWeek']) {
										$h .= '<b>'.s("Semaine {value}", week_number($eTask['plannedWeek'])).'</b>';
										$h .= '  <span class="util-annotation" style="white-space: nowrap">'.\util\DateUi::weekToDays($eTask['plannedWeek']).'</span>';
									} else {
										$h .= s("Non planifiée");
									}
								$h .= '</td>';
							}
							if($displayTime) {
								$h .= '<td>'.($eTask['time'] ? self::convertTime($eTask['time']) : '-').'</td>';
							}
							if($displayPlace) {
								$h .= '<td>'.$this->getPlace($eTask).'</td>';
							}
							if($displayArea) {
								$h .= '<td class="text-end">'.($eTask['distributionArea'] ? s("{value} m²", round($eTask['distributionArea'])) : '-').'</td>';
							}
							if($displayDensity) {

								$h .= '<td class="text-end hide-sm-down">';
									if($eTask['cultivation']->notEmpty() and $eTask['cultivation']['density'] !== NULL) {
										$density = round($eTask['cultivation']['density'], 1);
										$h .= s("{value} / m²", $density);
									} else {
										$h .= '-';
									}
								$h .= '</td>';

							}
						$h .= '</tr>';
					}

				$h .= '</body>';

			$h .= '</table>';
		$h .= '</div>';

		if($display === 'hidden') {
			foreach($cTask as $eTask) {
				$h .= $form->hidden('ids[]', $eTask['id']);
			}
		}
		
		return $h;
		
	}

	public function getPlantsByTasksField(\util\FormUi $form, \Collection $cTask): string {

		$h = '<table class="table-block stick-xs mb-0">';

			$h .= '<tbody>';

				foreach($cTask as $eTask) {
					$h .= '<tr>';
						if($cTask->count() > 1) {
							$h .= '<td class="td-checkbox">';
								$h .= $form->checkbox('ids[]', $eTask['id'], ['checked' => TRUE]);
							$h .= '</td>';
						}
						$h .= '<td>';
							if($cTask->count() === 1) {
								$h .= $form->hidden('ids[]', $eTask['id']);
							}
							$h .= '<div class="task-field-vignette">';
								$h .= '<div>';
									$h .= \plant\PlantUi::getVignette($eTask['plant'], '2rem');
								$h .= '</div>';
								$h .= '<div>';
									$h .= '<div class="task-field-link">';
										$h .= encode($eTask['plant']['name']);
										$h .= $this->getTaskComplement($eTask);
									$h .= '</div>';
									$h .= '<div class="task-field-place">';
										if($eTask['series']->notEmpty()) {
											$h .= s("Série {value}", SeriesUi::link($eTask['series'], newTab: TRUE));
										} else {
											$h .= s("Hors série");
										}
									$h .= '</div>';
								$h .= '</div>';
							$h .= '</div>';
						$h .= '</td>';
					$h .= '</tr>';
				}

			$h .= '</body>';

		$h .= '</table>';

		return $form->group(
			p("Production", "Productions", $cTask->count()),
			$h,
			attributes: ['for' => FALSE]
		);

	}

	public function getStockField(\util\FormUi $form, Task $eTask, \Collection $cProductStock, \selling\Product $eProductBookmark): string {

		$h = '<div id="task-harvest-stock">';

			if($cProductStock->notEmpty()) {

				$values = [];

				foreach($cProductStock as $eProductStock) {

					$name = \selling\ProductUi::getVignette($eProductStock, '2rem').'  ';
					$name .= encode($this->getStockText($eProductStock));

					$values[$eProductStock['id']] = $name;

				}

				$confirmText = s("Ajouter automatiquement les quantités récoltées de {value} à ce stock dans le futur ?", $this->getStockText(new \selling\Product([
					'name' => $eTask['plant']['name'],
					'variety' => $eTask['variety']->notEmpty() ? $eTask['variety']['name'] : NULL,
					'size' => $eTask['harvestSize']->notEmpty() ? $eTask['harvestSize']['name'] : NULL,
					'unit' => $eTask['harvestUnit'] ?? 'kg'
				])));

				$h .= $form->group(
					s("Ajouter à un stock"),
					$form->inputGroup(
						$form->selectDropdown('stock', $values, $eProductBookmark, attributes: ['placeholder' => s("Pas de mise en stock"), 'class' => 'form-control-lg']).
						'<label id="task-field-bookmark" class="input-group-addon" title="'.s("Mémoriser ce choix pour les futures récoltes").'">'.$form->inputCheckbox('stockRemember', attributes: [
							'checked' => $eProductBookmark->notEmpty(),
							'data-confirm' => $eProductBookmark->notEmpty() ? NULL : $confirmText,
							'data-confirm-text' => $confirmText,
							'onclick' => 'Task.changeBookmark(this)'
						]).\Asset::icon('star', ['class' => 'task-field-bookmark-no']).\Asset::icon('star-fill', ['class' => 'task-field-bookmark-yes']).'</label>'
					),
					attributes: [
						'wrapper' => 'stock',
						'for' => FALSE
					]
				);

			}

			$h .= '</div>';

			return $h;

	}

	protected function getStockText(\selling\Product $eProduct): string {

		$text = encode($eProduct['name']).' ('.\selling\UnitUi::getSingular($eProduct['unit']).')';

		if($eProduct['variety'] !== NULL) {
			$text .= ' / '.$eProduct['variety'];
		}

		if($eProduct['size'] !== NULL) {
			$text .= ' '.s("calibre {value}", $eProduct['size']);
		}
		
		return $text;

	}

	public function updateCultivation(Task $eTask, \Collection $cCultivation): \Panel {

		$form = new \util\FormUi();

		if($cCultivation->count() < 2) {

			$h = '<div class="util-info">';
				$h .= s("Il n'y a aucune autre série compatible avec cette espèce.");
			$h .= '</div>';

		} else {

			$h = '';

			$h .= $form->openAjax('/series/task:doUpdateCultivation', ['id' => 'task-update', 'data-ajax-origin' => \Route::getRequestedOrigin(), 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eTask['id']);

				$h .= $form->group(
					s("Nouvelle série"),
					$form->dynamicField($eTask, 'cultivation', function($d) use ($cCultivation) {

						$d->values = $cCultivation->makeArray(function($eCultivation, &$key) {
							$key = $eCultivation['id'];
							return $eCultivation['series']['name'].' / '.\production\CropUi::startText($eCultivation);
						});
						asort($d->values);

						$d->attributes['mandatory'] = TRUE;


					}),
					['wrapper' => 'harvest']
				);

				$h .= $form->group(
					content: $form->submit(s("Valider"))
				);

			$h .= $form->close();

		}

		return new \Panel(
			id: 'panel-task-update-cultivation',
			title: s("Changer de série"),
			subTitle: $eTask['series']->empty() ? NULL : SeriesUi::getPanelHeader($eTask['series']),
			body: $h
		);

	}

	public function getTimeGroup(\util\FormUi $form, Task $eTask): string {

		$eTask->expects(['status']);

		$h = '<div class="util-block bg-background-light">';

		switch($eTask['status']) {

			case Task::TODO :
				$h .= $form->dynamicGroup($eTask, 'planned');
				break;

			case Task::DONE :
				$h .= $form->dynamicGroup($eTask, 'done');
				break;

		}

		$h .= '</div>';

		return $h;

	}

	public function getVarietyGroup(\util\FormUi $form, Task $eTask, \Collection $cVariety, ?array $varietiesIntersect = NULL): string {

		$h = '';

		if($cVariety->count() > 1) {

			$cVariety->sort('name');

			$attributes = [
				'placeholder' => s("Toutes")
			];

			if($cVariety->count() <= 3) {

				if($varietiesIntersect !== NULL) {
					$attributes['callbackRadioAttributes'] = function($eVariety) use ($varietiesIntersect) {
						if($eVariety !== NULL) {
							return in_array($eVariety['id'], $varietiesIntersect) ? [] : ['disabled'];
						} else {
							return [];
						}
					};
				}

				$h .= $form->group(
					s("Variété"),
					$form->radios('variety', $cVariety, $eTask['variety'] ?? new \plant\Variety(), $attributes + [
						'columns' => 2,
					]),
				);

			} else {

				$values = [];

				foreach($cVariety as $eVariety) {

					$values[] = [
						'value' => $eVariety['id'],
						'label' => $eVariety['name'],
						'attributes' => ($varietiesIntersect === NULL or in_array($eVariety['id'], $varietiesIntersect)) ? [] : ['disabled']
					];

				}

				$h .= $form->group(
					s("Variété"),
					$form->select('variety', $values, $eTask['variety'] ?? new \plant\Variety(), $attributes),
				);

			}

		} else {
			$h .= $form->hidden('variety', '');
		}

		return $h;

	}

	public function getToolsGroup(\util\FormUi $form, Task $eTask): string {

		$h = '<div data-ref="tools" data-farm="'.$eTask['farm']['id'].'">';
			if($eTask['hasTools']->notEmpty()) {
				$h .= $form->dynamicGroup($eTask, 'tools');
			}
		$h .= '</div>';

		return $h;

	}

	public function getMethodsGroup(\util\FormUi $form, Task $eTask): string {

		$h = '<div data-ref="methods" data-farm="'.$eTask['farm']['id'].'">';
			if($eTask['hasMethods']->notEmpty()) {
				$h .= $form->dynamicGroup($eTask, 'methods');
			}
		$h .= '</div>';

		return $h;

	}

	public function getHarvestSizeField(\util\FormUi $form, Task $eTask): string {

		$eTask->expects(['action', 'cSize']);

		return $form->group(
			self::p('harvestSize')->label,
			$form->dynamicField($eTask, 'harvestSize'),
			['class' => ($eTask['action']->notEmpty() and $eTask['action']['fqn'] === ACTION_RECOLTE) ? '' : 'hide']
		);

	}

	public function getFertilizerField(\util\FormUi $form, Task $eTask): string {

		$eTask->expects(['action']);

		return $form->group(
			self::p('fertilizer')->label,
			(new \farm\RoutineUi())->getFieldFertilizer($form, 'fertilizer', $eTask['fertilizer'] ?? NULL),
			['class' => ($eTask['action']->notEmpty() and $eTask['action']['fqn'] === ACTION_FERTILISATION) ? '' : 'hide', 'wrapper' => 'fertilizer']
		);

	}

	public static function getDistributionField(\util\FormUi $form, \Collection $cTask, string $default, bool $withHarvest = TRUE): ?string {

		$cTask->expects([
			'category'
		]);

		// La distribution est réalisée sur le TEMPS DE TRAVAIL et la RÉCOLTE
		$values = [
			'harvest' => s("En fonction de la quantité récoltée le jour de travail"),
			'area' => s("En fonction de la surface"),
			'plant' => s("En fonction du nombre de plants"),
			'fair' => s("Égalitaire")
		];

		if($withHarvest === FALSE) {
			unset($values['harvest']);
		}

		// Gestion du cas où au moins une intervention ni concerne ni le sol, ni la plante
		// Répartition uniquement égalitaire
		foreach($cTask as $eTask) {

			if($eTask['category']['fqn'] !== CATEGORIE_CULTURE) {

				$h = $form->hidden('distribution', 'fair');
				$h .= '<b>'.$values['fair'].'</b>';
				$h .= '<div class="util-info">';
					$h .= \Asset::icon('info-circle').' '.s("Les autres options de répartition ne sont pas disponibles car au moins une intervention ne concerne pas les cultures.");
				$h .= '</div>';

				return $h;

			}

		}

		$byPlant = NULL;
		$byArea = NULL;
		$byHarvest = NULL;

		$harvested = '';

		foreach($cTask as $eTask) {

			if($withHarvest) {

				if($eTask['action']['fqn'] !== ACTION_RECOLTE) {
					unset($values['harvest']);
				} else if($eTask['harvest'] === NULL) {
					$byHarvest = 'noHarvest';
				} else {
					foreach($eTask['harvestDates'] as $day => $value) {
						$harvested .= '<tr data-day="'.$day.'">';
							$harvested .= '<td>';
								$harvested .= '<span class="action-name">'.encode($eTask['plant']['name']).'</span>';
							$harvested .= '</td>';
							$harvested .= '<td>';
								$harvested .= (new TaskUi())->getPlace($eTask);
							$harvested .= '</td>';
							$harvested .= '<td>';
								$harvested .= '<span class="annotation" style="color: '.$eTask['action']['color'].'">'.\selling\UnitUi::getValue($value, $eTask['harvestUnit']).'</span>';
							$harvested .= '</td>';
						$harvested .= '</tr>';
					}
				}

			}

			// Pas série, répartition uniquement égalitaire
			if($eTask['series']->empty()) {


				if($default === 'area' or $default === 'plant') {
					$default = 'fair';
				}

				$byArea = 'noSeries';
				$byPlant = 'noSeries';

			} else {

				$eTask['series']->expects(['area']);

				// La série n'a pas de surface renseignée, impossible de répartir par surface
				if($eTask['series']['area'] === NULL) {
					$byArea = 'noArea';
					$default = 'fair';
				}

				// La culture de la série n'a pas de densité renseignée, impossible de répartir par densité
				if($eTask['cultivation']->notEmpty()) {

					$eTask['cultivation']->expects(['density']);

					if($eTask['cultivation']['density'] === NULL) {

						$byPlant = 'noDensity';

						if($default === 'plant') {
							$default = ($byArea === NULL) ? 'area' : 'fair';
						}

					}

				}

			}

		}

		if($byArea === 'noSeries') {
			$values['area'] = '<span class="color-muted">'.$values['area'].' - '.\Asset::icon('info-circle').' '.s("indisponible car au moins une intervention n'est pas liée à une série").'</span>';
		} else if($byArea === 'noArea') {
			$values['area'] = '<span class="color-muted">'.$values['area'].' - '.\Asset::icon('info-circle').' '.s("indisponible car vous n'avez pas saisi la surface de toutes les séries").'</span>';
		}

		if($withHarvest) {

			if($byHarvest === 'noHarvest') {
				$values['harvest'] = '<span class="color-muted">'.$values['harvest'].' - '.\Asset::icon('info-circle').' '.s("indisponible car il n'y a aucune saisie de récolte pour au moins une des interventions").'</span>';
			}

			if($harvested !== '') {
				$values['harvest'] .= '<table id="task-distribution-dates" class="table-block" data-day="'.implode(' ', array_keys($eTask['harvestDates'])).'" onrender="Task.toggleDistributionHarvestTable()">';
					$values['harvest'] .= $harvested;
				$values['harvest'] .= '</table>';
			}

		}

		if($byPlant === 'noSeries') {
			$values['plant'] = '<span class="color-muted">'.$values['plant'].' - '.\Asset::icon('info-circle').' '.s("indisponible car au moins une intervention n'est pas liée à une série").'</span>';
		} else if($byPlant === 'noDensity') {
			$values['plant'] = '<span class="color-muted">'.$values['plant'].' - '.\Asset::icon('info-circle').' '.s("indisponible car vous n'avez pas saisi la densité de toutes les séries").'</span>';
		}

		return $form->radios('distribution', $values, $default, [
			'mandatory' => TRUE,
			'callbackRadioAttributes' => function($option, $key) use ($byPlant, $byArea, $byHarvest) {
				if(
					($key === 'area' and $byArea !== NULL) or
					($key === 'harvest' and $byHarvest !== NULL) or
					($key === 'plant' and $byPlant !== NULL)
				) {
					return ['disabled'];
				} else {
					return [];
				}
			}
		]);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Task::model()->describer($property, [
			'action' => s("Intervention"),
			'cultivation' => s("Série"),
			'planned' => s("Planifié"),
			'plannedWeek' => s("Planifié"),
			'done' => s("Réalisé"),
			'doneWeek' => s("Réalisé"),
			'time' => s("Temps de travail effectué"),
			'timeExpected' => s("Temps de travail estimé"),
			'method' => s("Méthode de travail"),
			'description' => s("Observations"),
			'fertilizer' => s("Apports"),
			'harvestSize' => s("Calibre récolté"),
			'harvestMore' => s("Quantité récoltée"),
			'harvestDate' => s("Jour de récolte"),
			'methods' => s("Méthodes de travail"),
			'tools' => s("Matériel nécessaire")
		]);

		switch($property) {

			case 'action' :
				$d->field = 'radio';
				$d->values = fn(Task $e) => $e['cAction'] ?? $e->expects(['cAction']);
				$d->attributes = [
					'columns' => 3,
					'mandatory' => TRUE,
					'callbackRadioAttributes' => function(\farm\Action $eAction) {
						return [
							'disabled' => ($eAction['disabled'] ?? FALSE) ? 'disabled' : NULL,
							'data-action' => 'task-write-action-change',
							'data-fqn' => $eAction['fqn'],
							'data-farm' => $eAction['farm']['id']
						];
					}
				];
				break;

			case 'timeExpected' :
				$d->append = s("h");
				break;

			case 'planned' :
			case 'done' :
				$d->field = function(\util\FormUi $form, Task $e) use ($property) {

					$e->expects([$property.'Week', $property.'Date']);

					if(isset($e[$property.'Selection'])) {

						$isWeek = ($e[$property.'Selection'] === 'week');
						$isDate = ($e[$property.'Selection'] === 'date');

					} else {

						$isWeek = ($e[$property.'Week'] !== NULL and $e[$property.'Date'] === NULL);
						$isDate = ($e[$property.'Date'] !== NULL);

					}

					$h = '<div class="task-write-planned" data-period="'.($isWeek ? 'week' : ($isDate ? 'date' : 'unplanned')).'">';

						$h .= '<div class="task-write-planned-field task-write-planned-week">';
							$h .= $form->week($property.'[week]', $e[$property.'Week'], $isWeek ? [] : ['disabled']);
						$h .= '</div>';

						$h .= '<div class="task-write-planned-field task-write-planned-date">';
							$h .= $form->date($property.'[date]', $e[$property.'Date'], $isDate ? [] : ['disabled']);
						$h .= '</div>';

						$h .= '<div class="task-write-planned-field task-write-planned-unplanned">';
							$h .= $form->fake(s("Non planifié"));
						$h .= '</div>';

						$labelWeek = match($property) {
							'planned' => s("Planifier à la semaine"),
							'done' => s("Consigner à la semaine")
						};

						$labelDate = match($property) {
							'planned' => s("Planifier à la journée"),
							'done' => s("Consigner à la journée")
						};

						$h .= '<div class="field-followup">';
							$h .= '<span class="task-write-planned-link-week">';
								$h .= '<a '.attr('onclick', 'Task.changePlanned(this, "week")').'>'.$labelWeek.'</a>';
							$h .= '</span>';
							$h .= '<span class="task-write-planned-separator-1"> | </span>';
							$h .= '<span class="task-write-planned-link-date">';
								$h .= '<a '.attr('onclick', 'Task.changePlanned(this, "date")').'>'.$labelDate.'</a>';
							$h .= '</span>';
							if($property === 'planned') {
								$h .= '<span class="task-write-planned-separator-2"> | </span>';
								$h .= '<span class="task-write-planned-link-unplanned">';
									$h .= '<a '.attr('onclick', 'Task.changePlanned(this, "unplanned")').'>'.s("Non planifié").'</a>';
								$h .= '</span>';
							}
							if($e->exists() === FALSE) {
								$h .= '<span class="task-write-repeat">';
									$h .= ' | '.\Asset::icon('caret-down-fill').' <a '.attr('onclick', 'Task.changeRepeat(this, true)').'>'.s("Répéter").'</a>';
								$h .= '</span>';
								$h .= '<span class="task-write-norepeat hide">';
									$h .= ' | '.\Asset::icon('caret-up-fill').' <a '.attr('onclick', 'Task.changeRepeat(this, false)').'>'.s("Une seule fois").'</a>';
								$h .= '</span>';
							}
						$h .= '</div>';

						if($e->exists() === FALSE) {

							$eRepeat = new Repeat();

							$h .= '<div class="task-write-repeat-field hide">';
								$h .= '<h5>'.s("Répéter l'intervention").'</h5>';
								$h .= $form->dynamicField($eRepeat, 'frequency', function($d) {
									$d->attributes['callbackRadioAttributes'] = fn() => ['disabled'];
								});
								$h .= '<div class="mt-1">';
									$h .= '<h5>'.s("Jusqu'à :").'</h5>';
									$h .= $form->dynamicField($eRepeat, 'stop', function($d) {
										$d->attributes = ['disabled'];
									});
								$h .= '</div>';
							$h .= '</div>';

						}

					$h .= '</div>';

					return $h;

				};
				$d->group = ['wrapper' => $property.'Week '.$property.'Date '.$property.' frequency stop'];
				break;

			case 'plant' :
				$d->autocompleteBody = function(\util\FormUi $form, Task $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new \plant\PlantUi())->query($d);
				break;

			case 'plantsFilter' :
				$d->autocompleteBody = function(\util\FormUi $form, Task $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'season' => $e['season']
					];
				};
				$d->autocompleteDefault = fn(Task $e) => $e['plant'] ?? $e->expects(['plant']);
				$d->autocompleteDispatch = '#task-create-plant';
				$d->placeholder = s("Filtrer sur une espèce...");
				$d->prepend ??= \Asset::icon('search');

				(new \plant\PlantUi())->query($d);
				$d->group = function(Task $e) {
					return [
						'id' => 'task-create-plant',
						'data-season' => $e['season']
					];
				};
				break;

			case 'harvestUnit' :
				$d->field = 'select';
				$d->attributes = [
					'mandatory' => TRUE,
					'onchange' => fn(\util\FormUi $form, Task $e) => 'Task.changeHarvestUnit('.$e['id'].', this)'
				];
				$d->values = \selling\UnitUi::getBasicList();
				$d->default = function(Task $e) {
					if($e['cultivation']->notEmpty()) {
						return $e['cultivation']['mainUnit'];
					} else {
						return NULL;
					}
				};
				break;

			case 'harvestMore' :
				$d->type = 'float';
				$d->attributes = [
					'onrender' => 'this.focus();',
					'step' => 0.001
				];
				$d->group = ['wrapper' => 'harvest'];
				break;

			case 'harvestDate' :
				$d->field = 'date';
				$d->default = date('Y-m-d', date('G') <= 6 ? strtotime('yesterday') : time());
				$d->group = ['wrapper' => 'harvestDates'];
				break;

			case 'harvestSize' :
				$d->attributes = ['placeholder' => s("Aucun")];
				$d->values = fn(Task $e) => $e['cSize'] ?? $e->expects(['cSize']);
				break;

			case 'status' :
				$d->values = [
					Task::TODO => s("À faire"),
					Task::DONE => s("Fait"),
				];
				break;

			case 'methods' :
				$d->autocompleteDefault = fn(Task $e) => ($e['cMethod?'] ?? $e->expects(['cMethod?']))();
				$d->autocompleteBody = function(\util\FormUi $form, Task $e) {
					$e->expects(['action', 'farm']);
					return [
						'action' => $e['action']['id'],
						'farm' => $e['farm']['id']
					];
				};
				(new \farm\MethodUi())->query($d, TRUE);
				$d->group = ['wrapper' => 'methods'];
				break;

			case 'tools' :
				$d->autocompleteDefault = fn(Task $e) => ($e['cTool?'] ?? $e->expects(['cTool?']))();
				$d->autocompleteBody = function(\util\FormUi $form, Task $e) {
					$e->expects(['action', 'farm']);
					return [
						'action' => $e['action']['id'],
						'farm' => $e['farm']['id']
					];
				};
				(new \farm\ToolUi())->query($d, TRUE);
				$d->group = ['wrapper' => 'tools'];
				break;

		}

		return $d;

	}

}
?>
