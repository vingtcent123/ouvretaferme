<?php
namespace series;

class PlanningUi {

	protected ?string $period = NULL;
	protected ?\Collection $cUserFarm = NULL;

	public function __construct() {

		\Asset::css('series', 'planning.css');
		\Asset::css('series', 'task.css');

	}

	public function getCalendar(\farm\Farm $eFarm, string $week, \Closure $link, ?\Closure $filter = NULL): string {

		$eFarm->expects(['seasonFirst', 'seasonLast']);

		$weekAfter = date('o-\WW', strtotime($week.' + 1 WEEK'));
		$weekBefore = date('o-\WW', strtotime($week.' - 1 WEEK'));

		if($eFarm->isSeasonValid(week_year($weekBefore)) === FALSE) {
			$weekBefore = NULL;
		}

		if($eFarm->isSeasonValid(week_year($weekAfter)) === FALSE) {
			$weekAfter = NULL;
		}

		$h = '<div id="tasks-calendar-top" class="tasks-calendar tasks-calendar-week '.($filter ? 'tasks-calendar-with-filter' : '').'">';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
				$h .= '</div>';
			}

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-before">';
				if($weekBefore !== NULL) {
					$h .= '<a href="'.$link($weekBefore).'">';
						$h .= \Asset::icon('chevron-left');
					$h .= '</a>';
				}
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-title">';
				$h .= '<h1>';
					$h .= '<a class="dropdown-toggle" data-dropdown="bottom-center">';
						$h .= s("Semaine {week}, {year}", ['week' => week_number($week), 'year' => week_year($week)]);
					$h .= '</a>';
					$h .= '<div class="dropdown-list dropdown-list-minimalist">';
						$h .= \util\FormUi::weekSelector((string)substr($week, 0, 4), $link('{current}'), defaultWeek: $week, minYear: $eFarm->getFirstValidSeason(), maxYear: $eFarm->getLastValidSeason());
					$h .= '</div>';
				$h .= '</h1>';
				$h .= '<div class="tasks-calendar-title-period">';
					$h .= \util\DateUi::weekToDays($week);
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-after">';
				if($weekAfter !== NULL) {
					$h .= '<a href="'.$link($weekAfter).'">';
						$h .= \Asset::icon('chevron-right');
					$h .= '</a>';
				}
			$h .= '</div>';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
					$h .= $filter();
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getByDay(\farm\Farm $eFarm, string $week, \Collection $cccTask, \Collection $cccTaskAssign, \farm\Action $eActionSelected, \plant\Plant $ePlantSelected, \Collection $cUserFarm, \user\User $eUser, array $seasonsWithSeries, \Collection $cCategory): string {

		$this->period = 'day';
		$this->cUserFarm = $cUserFarm;

		\Asset::css('series', 'planning.css');

		$h = '<div id="planning-daily-time">';
			$h .= $this->getUsers($eFarm, $week, $eUser, $cUserFarm, fn($eUserFarm) => 'data-ajax="'.\farm\FarmUi::urlPlanningDaily($eFarm, $week).'?user'.($eUserFarm->notEmpty() ? '='.$eUserFarm['id'] : '').'" data-ajax-method="get"', team: TRUE);
		$h .= '</div>';

		$form = new \util\FormUi();

		if($week === currentWeek()) {
			$position = (int)date('N') + 1;
		} else {
			$position = 1;
		}

		$h .= '<div id="planning-daily-container" onrender="Task.scrollPlanningDaily(this, '.$position.')" data-week="'.$week.'" data-farm="'.$eFarm['id'].'" data-batch="#batch-task">';

			$h .= '<div id="planning-daily-list">';

				$h .= '<div class="planning-daily" data-planning-scroll="0">';

					$h .= '<div class="planning-daily-header">';

						$h .= '<div>';
							$h .= '<h2>';
								$h .= s("À assigner");
							$h .= '</h2>';
						$h .= '</div>';
						$h .= $this->getNewTask('daily', 'todo', $eFarm, $seasonsWithSeries, $cCategory, $eActionSelected, week: $week);

					$h .= '</div>';

					$h .= $this->getTodoPlanning($form, $eFarm, $week, $cccTaskAssign, $eActionSelected, $ePlantSelected);

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
							$h .= $this->getNewTask('daily', $date >= currentDate() ? 'todo' : 'done', $eFarm, $seasonsWithSeries, $cCategory, $eActionSelected, date: $date);
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

						foreach($ccTask as $cTask) {
							$h .= $this->getTasks($form, $eFarm, $cTask, $date, $week, $eActionSelected, $ePlantSelected, TRUE);
						}

					} else {

						$h .= '<div class="tasks-planning-items util-empty">';
							$h .= s("Aucune intervention ce jour.");
						$h .= '</div>';

					}

				$h .= '</div>';

			}

			$h .= new TaskUi()->getBatch($eFarm, $week, $cUserFarm);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getByWeek(\farm\Farm $eFarm, string $week, \Collection $cccTask, \Collection $cUserFarm, \user\User $eUserTime, array $seasonsWithSeries, \Collection $cAction, \user\User $eUserSelected, \farm\Action $eActionSelected, \plant\Plant $ePlantSelected, \Collection $cCategory): string {

		$this->period = 'week';
		$this->cUserFarm = $cUserFarm;

		\Asset::css('series', 'planning.css');

		$h = '<div id="planning-weekly-container" class="tabs-h" data-farm="'.$eFarm['id'].'" data-week="'.$week.'" onrender="'.encode('Lime.Tab.restore(this, "todo")').'" data-batch="#batch-task">';

			$form = new \util\FormUi();

			$canCreate = (new Task(['farm' => $eFarm]))->canCreate();

			$h .= '<div id="planning-weekly-wrapper">';

				$h .= '<div id="planning-weekly-tabs" class="tabs-item util-print-hide">';
					$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="todo">'.s("À faire").'</a>';
					$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="done">'.s("Fait").'</a>';
					if($eFarm->hasFeatureTime()) {
						$h .= '<a class="tab-item" onclick="Lime.Tab.select(this)" data-tab="time">';
							$h .= '<span class="hide-sm-up">'.\Asset::icon('clock').'&nbsp;&nbsp;'.s("Travaillé").'</span>';
							$h .= '<span class="hide-xs-down">'.\Asset::icon('clock').'&nbsp;&nbsp;'.s("Temps de travail").'</span>';
						$h .= '</a>';
					}
				$h .= '</div>';

				$h .= '<div id="planning-weekly-time" class="tab-panel util-print-hide" data-tab="time">';
					$h .= $this->getWeekTime($eFarm, $week, $cccTask, $eUserTime, $cUserFarm);
				$h .= '</div>';

				$h .= $this->getFilters($eFarm, $cccTask, $cUserFarm, $eUserSelected, $cAction, $eActionSelected, NULL, $ePlantSelected);

				$h .= '<div id="planning-weekly-todo" class="tab-panel planning-week util-print-block" data-tab="todo">';

					$h .= '<div class="planning-week-header">';

						$h .= '<h2 class="planning-week-title util-print-block">'.s("À faire").'</h2>';
						if($canCreate) {
							$h .= $this->getNewTask('weekly', 'todo', $eFarm, $seasonsWithSeries, $cCategory, $eActionSelected, week: $week);
						}

					$h .= '</div>';

					$h .= $this->getTodoPlanning($form, $eFarm, $week, $cccTask, $eActionSelected, $ePlantSelected);

				$h .= '</div>';

				$h .= '<div id="planning-weekly-done" class="tab-panel planning-week util-print-block" data-tab="done">';

					$h .= '<div class="planning-week-header">';

						$h .= '<h2 class="planning-week-title util-print-block">'.s("Fait").'</h2>';
						if($canCreate) {
							$h .= $this->getNewTask('weekly', 'done', $eFarm, $seasonsWithSeries, $cCategory, $eActionSelected, week: $week);
						}

					$h .= '</div>';


					if($cccTask['done']->empty() === FALSE) {

						foreach($cccTask['done'] as $cTask) {
							$h .= $this->getTasks($form, $eFarm, $cTask, NULL, $week, $eActionSelected, $ePlantSelected);
						}

					}

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= new TaskUi()->getBatch($eFarm, $week, $cUserFarm);

		return $h;

	}

	public function getYearCalendar(\farm\Farm $eFarm, int $year, ?\Closure $filter = NULL): string {

		$yearBefore = $year - 1;
		$yearAfter = $year + 1;

		if($eFarm->isSeasonValid($yearBefore) === FALSE) {
			$yearBefore = NULL;
		}

		if($eFarm->isSeasonValid($yearAfter) === FALSE) {
			$yearAfter = NULL;
		}

		$h = '<div id="tasks-calendar-top" class="tasks-calendar '.($filter ? 'tasks-calendar-with-filter' : '').' tasks-calendar-year">';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search"></div>';
			}


			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-before">';
				if($yearBefore !== NULL) {
					$h .= '<a href="'.\farm\FarmUi::urlPlanningYear($eFarm, $yearBefore, 12).'">';
						$h .= \Asset::icon('chevron-left');
					$h .= '</a>';
				}
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-title">';
				$h .= '<h1>'.$year.'</h1>';
			$h .= '</div>';

			$h .= '<div class="tasks-calendar-navigation tasks-calendar-navigation-after">';
				if($yearAfter !== NULL) {
					$h .= '<a href="'.\farm\FarmUi::urlPlanningYear($eFarm, $yearAfter, 1).'">';
						$h .= \Asset::icon('chevron-right');
					$h .= '</a>';
				}
			$h .= '</div>';

			if($filter !== NULL) {
				$h .= '<div class="tasks-calendar-search">';
					$h .= $filter();
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getYearSearch(\farm\Farm $eFarm, int $year): string {

		$h = '<div id="tasks-calendar-months-wrapper">';

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

	public function getExport(\farm\Farm $eFarm, string $week): string {

		$h = '<a href="/farm/planning:downloadWeeklyPdf?id='.$eFarm['id'].'&week='.$week.'" data-ajax-navigation="never" data-waiter="'.s("Création en cours").'" data-waiter-timeout="8" class="btn btn-primary">';
			$h .= \Asset::icon('file-pdf').' '.s("PDF");
		$h .= '</a> ';

		return $h;

	}

	public function getCalendarFilter(): string {

		$h = ' <a '.attr('onclick', 'Lime.Search.toggle("#planning-search")').' class="btn btn-primary">';
			$h .= \Asset::icon('search');
		$h .= '</a>';

		return $h;

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search, \Collection $cZone): string {

		$form = new \util\FormUi();

		$h = '<div id="planning-search" class="util-block-search '.($search->empty(['plant', 'action', 'user']) ? 'hide' : '').'">';

			$h .= $form->openAjax(LIME_REQUEST_PATH, ['method' => 'get', 'class' => 'util-search']);

					$h .= $form->hidden('search', 1);

					if($cZone->notEmpty()) {
						$h .= '<fieldset>';
							$h .= '<legend>'.s("Emplacement").'</legend>';
							$h .= new \map\ZoneUi()->getZonePlotWidget($form, $cZone, $search->get('plot') ?? new \map\Plot());
						$h .= '</fieldset>';
					}

				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.LIME_REQUEST_PATH.'?search" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getFilters(\farm\Farm $eFarm, \Collection $cccTask, \Collection $cUser, \user\User $eUserSelected, \Collection $cAction, \farm\Action $eActionSelected, ?\Collection $cPlant, \plant\Plant $ePlantSelected, bool $filterSelected = TRUE): string {

		$cActionFound = $cccTask->getColumnCollection('action');
		$cPlantFound = $cccTask->getColumnCollection('plant');

		$actions = array_count_values($cActionFound->getIds());

		$cActionFavorite = $cAction->find(fn($eAction) => $eAction['favorite']);

		if(
			$filterSelected and
			$cActionFound->empty() and
			$cUser->count() < 2
		) {
			return '';
		}

		if($cPlant === NULL) {

			$cPlant = new \Collection();
			$plants = [];

			foreach($cPlantFound as $ePlantFound) {

				if($ePlantFound->empty()) {
					continue;
				}

				$plants[$ePlantFound['id']] ??= 0;
				$plants[$ePlantFound['id']]++;

				$cPlant[$ePlantFound['id']] ??= $ePlantFound;

			}

		} else {

			$plants = array_count_values(
				$cPlantFound
					->filter(fn($ePlant) => $ePlant->notEmpty())
					->getIds()
			);

		}

		$cPlant->sort(['name' => SORT_ASC]);

		$h = '<div class="planning-filters">';

			$h .= '<div class="planning-filters-buttons">';
				$h .= $this->getUserFilter($cUser);
				$h .= $this->getPlantFilter($cPlant, $plants, $filterSelected);
				$h .= $this->getActionFilter($eFarm, $cAction, $actions, $filterSelected);
			$h .= '</div>';

			if(
				$eUserSelected->notEmpty() or
				$cActionFavorite->notEmpty() or
				$eActionSelected->notEmpty() or
				$ePlantSelected->notEmpty()
			) {

				if($eUserSelected->notEmpty()) {
					$h .= $this->getUserFiltered($eUserSelected);
				}

				if($ePlantSelected->notEmpty()) {
					$h .= $this->getPlantFiltered($ePlantSelected);
				}

				if(
					$eActionSelected->notEmpty() and
					$cActionFavorite->offsetExists($eActionSelected['id']) === FALSE
				) {
					$h .= $this->getActionFiltered($cAction[$eActionSelected['id']], $eActionSelected, $filterSelected);
				}

				foreach($cActionFavorite as $eActionFavorite) {

					if(
						$filterSelected and
						array_key_exists($eActionFavorite['id'], $actions) === FALSE and
						($eActionSelected->empty() or $eActionFavorite->is($eActionSelected) === FALSE)
					) {
						continue;
					}

					$h .= $this->getActionFiltered($eActionFavorite, $eActionSelected, $filterSelected);

				}


			}

		$h .= '</div>';

		return $h;

	}

	protected function getUserFilter(\Collection $cUser): string {

		if($cUser->count() < 2) {
			return '';
		}

		$h = '<a data-dropdown="bottom-start" class="btn btn-outline-primary">';
			$h .= \Asset::icon('person');
		$h .= '</a>';

		$h .= '<div class="dropdown-list dropdown-list-3 planning-filters-dropdown">';

			$h .= '<div class="dropdown-title">'.s("Équipe").'</div>';

			foreach($cUser as $eUser) {

				$h .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'userSelected', $eUser['id']).'" class="dropdown-item">';
					$h .= \user\UserUi::getVignette($eUser, '1.5rem').' ';
					$h .= encode($eUser->getName());
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getPlantFilter(\Collection $cPlant, array $plants, bool $filterSelected): string {

		if($cPlant->empty()) {
			return '';
		}

		$h = '<a data-dropdown="bottom-start" class="btn btn-outline-primary">';
			$h .= \Asset::icon('flower3');
		$h .= '</a>';

		$h .= '<div class="dropdown-list dropdown-list-4 planning-filters-dropdown">';

			$h .= '<div class="dropdown-title">'.s("Espèces").'</div>';

			foreach($cPlant as $ePlant) {

				$h .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'plantSelected', $ePlant['id']).'" class="dropdown-item">';
					$h .= \plant\PlantUi::getVignette($ePlant, '1.5rem').' ';
					$h .= encode($ePlant['name']);
					if($filterSelected) {
						$h .= '<span class="tab-item-count hide-xs-down">'.$plants[$ePlant['id']].'</span>';
					}
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getActionFilter(\farm\Farm $eFarm, \Collection $cAction, array $actions, bool $filterSelected): string {

		$list = '';

		foreach($cAction as $eAction) {

			if($filterSelected and array_key_exists($eAction['id'], $actions) === FALSE) {
				continue;
			}

			$visibility = ($eAction['color'] === '#AAAAAA') ? 'visibility: hidden' : '';

			$list .=  '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'actionSelected', $eAction['id']).'" class="dropdown-item">';
				$list .=  '<span style="'.$visibility.'">'.\farm\ActionUi::getCircle($eAction).'</span>';
				$list .=  encode($eAction['name']);
				if($eAction['favorite']) {
					$list .=  '<span class="hide-xs-down"> '.\Asset::icon('star-fill').'</span>';
				}
				if($filterSelected) {
					$list .=  '<span class="tab-item-count hide-xs-down">'.$actions[$eAction['id']].'</span>';
				}
			$list .=  '</a>';

		}

		if($list === '') {
			return '';
		}

		$h = '<a data-dropdown="bottom-start" class="btn btn-outline-primary">';
			$h .= \Asset::icon('list-task');
		$h .= '</a>';

		$h .= '<div class="dropdown-list dropdown-list-3 planning-filters-dropdown">';

			$h .= '<div class="dropdown-title">'.s("Interventions").'</div>';
			$h .= $list;

			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a href="/farm/action:manage?farm='.$eFarm['id'].'" class="dropdown-item dropdown-item-full">'.s("Modifier mes interventions favorites").'</a>';

		$h .= '</div>';

		return $h;

	}

	protected function getActionFiltered(\farm\Action $eActionFavorite, \farm\Action $eActionSelected, bool $filterSelected): string {

		$isSelected = $eActionFavorite->is($eActionSelected);

		$style = $isSelected ? 'color: white; background-color: '.$eActionFavorite['color'] : 'color: '.$eActionFavorite['color'];

		$url = $isSelected ?
			\util\HttpUi::setArgument(LIME_REQUEST, 'actionSelected', '') :
			\util\HttpUi::setArgument(LIME_REQUEST, 'actionSelected', $eActionFavorite['id']);

		$h = '<a href="'.$url.'" class="planning-filters-favorite" style="'.$style.'">';
			$h .= encode($eActionFavorite['name']);
			if($isSelected) {
				$h .= '  '.\Asset::icon('x-circle-fill');
			}
		$h .= '</a>';

		return $h;

	}

	protected function getUserFiltered(\user\User $eUserSelected): string {

		$url = \util\HttpUi::setArgument(LIME_REQUEST, 'userSelected', '');

		$h = '<a href="'.$url.'" class="planning-filters-favorite" style="color: white; background-color: var(--primary)">';
			$h .= \user\UserUi::getVignette($eUserSelected, '1rem').'  ';
			$h .= encode($eUserSelected->getName());
			$h .= '  '.\Asset::icon('x-circle-fill');
		$h .= '</a>';

		return $h;

	}

	protected function getPlantFiltered(\plant\Plant $ePlantSelected): string {

		$url = \util\HttpUi::setArgument(LIME_REQUEST, 'plantSelected', '');

		$h = '<a href="'.$url.'" class="planning-filters-favorite" style="color: white; background-color: '.$ePlantSelected['color'].'">';
			$h .= encode($ePlantSelected['name']);
			$h .= '  '.\Asset::icon('x-circle-fill');
		$h .= '</a>';

		return $h;

	}

	public function getUsers(\farm\Farm $eFarm, string $week, \user\User $eUser, \Collection $cUserFarm, \Closure $link, bool $team = FALSE): string {

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

	public function getExportPlanning(\farm\Farm $eFarm, string $week, \Collection $ccTask) {

		TaskUi::setExport(TRUE);

		$h = '';

		foreach($ccTask as $cTask) {
			$h .= $this->getTasks(new \util\FormUi(), $eFarm, $cTask, NULL, $week);
		}

		return $h;

	}

	protected function getTodoPlanning(\util\FormUi $form, \farm\Farm $eFarm, string $week, \Collection $cccTask, \farm\Action $eActionSelected, \plant\Plant $ePlantSelected) {

		if(
			$cccTask['todo']->empty() and
			$cccTask['delayed']->empty() and
			$cccTask['unplanned']->empty() and
			$cccTask['done']->empty()
		) {

			$h = '<div class="util-empty">';
				$h .= s("Le planning est encore vide.");
			$h .= '</div>';

			return $h;

		}

		$h = '';

		if($cccTask['todo']->empty() === FALSE) {

			foreach($cccTask['todo'] as $cTask) {
				$h .= $this->getTasks($form, $eFarm, $cTask, NULL, $week, $eActionSelected, $ePlantSelected);
			}

		}

		if($cccTask['delayed']->empty() === FALSE) {

				$delayed = '';

				foreach($cccTask['delayed'] as $cTask) {
					$delayed .= $this->getTasks($form, $eFarm, $cTask, NULL, $week, $eActionSelected, $ePlantSelected);
				}

				if($delayed) {

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
					$h .= $delayed;


				}

		}

		if($cccTask['unplanned']->empty() === FALSE) {

				$unplanned = '';

				foreach($cccTask['unplanned'] as $cTask) {
					$unplanned .= $this->getTasks($form, $eFarm, $cTask, NULL, $week, $eActionSelected, $ePlantSelected);
				}

				if($unplanned) {

					$h .= '<div class="planning-week-title">';
						$h .= s("Non planifié");
					$h .= '</div>';
					$h .= $unplanned;

				}

		}

		return $h;

	}

	protected function getTasks(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cTask, ?string $date, string $week, \farm\Action $eActionSelected = new \farm\Action(), \plant\Plant $ePlantSelected = new \plant\Plant(), bool $displayTime = FALSE): string {

		\Asset::css('sequence', 'flow.css');

		$eAction = $cTask->first()['action'];

		if(
			$eActionSelected->notEmpty() and
			$eActionSelected->is($eAction) === FALSE
		) {
			return '';
		}

		$users = array_unique($cTask->reduce(fn($eTask, $v) => array_merge($v, array_keys($eTask['times'])), []));
		$tasks = $this->getPlantTask($form, $eFarm, $cTask, $date, $week, $ePlantSelected, $displayTime);

		if($tasks === '') {
			return '';
		}

		$h = '<div class="tasks-planning-items" data-filter-action="'.$eAction['id'].'"  data-filter-user="'.implode(' ', $users).'">';

			$h .= '<div class="tasks-planning-action">';

				$h .= '<label class="tasks-planning-select">';
					$h .= $form->inputCheckbox('batchAction[]', attributes: ['onclick' => 'Task.checkPlanningAction(this)']);
				$h .= '</label>';
				$h .= '<a href="'.\farm\FarmUi::urlPlanningAction($eFarm, $week, $eAction).'" class="tasks-planning-action-name">';
					$h .= encode($eAction['name']);
				$h .= '</a>';

			$h .= '</div>';

			$h .= $tasks;

		$h .= '</div>';

		return $h;

	}

	protected function getPlantTask(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cTask, ?string $date, string $week, \plant\Plant $ePlantSelected, bool $displayTime = FALSE): string {

		$h = '';

		$ePlantCurrent = new \plant\Plant();

		foreach($cTask as $eTask) {

			$ePlant = $eTask['plant'];

			if(
				$ePlantSelected->notEmpty() and
				$ePlantSelected->is($ePlant) === FALSE
			) {
				continue;
			}

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

			$h .= $this->getTask($form, $eFarm, $eTask, $date, $week, $displayTime);

		}

		return $h;

	}

	protected function getTask(\util\FormUi $form, \farm\Farm $eFarm, Task $eTask, ?string $date, string $week, bool $displayTime = FALSE): string {

		$eTask->expects(['times']);

		$users = array_keys($eTask['times']);
		$series = new TaskUi()->getTaskPlace($eTask);

		$filters = [
			'data-filter-action' => $eTask['action']['id'],
			'data-filter-plant' => ($eTask['plant']->empty() ? '' : $eTask['plant']['id']),
			'data-filter-harvest' => new TaskUi()->getBatchHarvestString($eTask),
			'data-filter-user' => implode(' ', $users)
		];

		$h = '<div class="tasks-planning-item '.($series ? 'tasks-planning-item-with-series' : '').' '.($eTask->isDone() ? 'tasks-planning-item-done' : 'tasks-planning-item-todo').' batch-checkbox" id="task-item-'.$eTask['id'].'" '.attrs($filters).'>';

			$h .= '<label class="tasks-planning-select">';
				$h .= new TaskUi()->getBatchCheckbox($form, $eTask);
			$h .= '</label>';

			$h .= '<div class="tasks-planning-item-base">';

				$h .= '<a href="'.TaskUi::url($eTask).'" class="tasks-planning-item-main">';
					$h .= $series;
					$h .= new TaskUi()->getTaskContent(
						$eTask,
						showPlant: ($eTask['category']['fqn'] !== CATEGORIE_CULTURE),
						showAction: FALSE,
						showTools: TRUE
					);
					$h .= new TaskUi()->getTaskDescription($eTask);
					$h .= $this->getTaskDetails($eFarm, $eTask);
				$h .= '</a>';

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
											$h .= \user\UserUi::getVignette($eUser, '1.25rem').' '.TaskUi::convertTimeText($time);
										$h .= '</a>';

										$hasTime = TRUE;

									}

								}

							}

							if($hasTime === FALSE) {
								$h .= TaskUi::getTime($eTask, 0);
							}

						} else {

							if($this->period === 'week' and $eTask['farm']->hasFeatureTime()) {
								$weekTime = round(array_sum($eTask['times']), 2);
								$h .= TaskUi::getTime($eTask, $weekTime);
							}

						}

					}

				$h .= '</div>';

			$h .= '</div>';

			if($eTask->isDone() === FALSE) {
				$h .= '<div class="tasks-planning-item-validate util-print-hide">';
					$h .= new TaskUi()->getDone($eTask, $week, ['post-week' => $week]);
				$h .= '</div>';
			}

			$h .= $this->getTaskSeedling($eTask);

		$h .= '</div>';


		return $h;

	}

	protected function getTaskSeedling(Task $eTask): string {

		$eSeries = $eTask['series'];
		$eAction = $eTask['action'];

		if(in_array($eAction['fqn'], [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION]) === FALSE) {
			return '';
		}

		$body = '';

		foreach($eTask['cCultivation'] as $eCultivation) {

			if(
				($eAction['fqn'] === ACTION_SEMIS_DIRECT and $eCultivation['seedling'] !== Cultivation::SOWING) or
				($eAction['fqn'] === ACTION_SEMIS_PEPINIERE and $eCultivation['seedling'] !== Cultivation::YOUNG_PLANT) or
				($eAction['fqn'] === ACTION_PLANTATION and in_array($eCultivation['seedling'],  [Cultivation::YOUNG_PLANT, Cultivation::YOUNG_PLANT_BOUGHT]) === FALSE)
			) {
				continue;
			}

			foreach($eCultivation['cSlice'] as $eSlice) {

				$eVariety = $eSlice['variety'];

				$body .= '<tr>';
					$body .= '<td>';
						$body .= ($eVariety['id'] === NULL) ? '<i>'.s("Variété non renseignée").'</i>' : encode($eVariety['name']);
						if($eSlice['area'] > 0) {
							$body .= ' <span class="util-annotation planning-plant-area hide-xs-down">'.s("{value} m²", $eSlice['area']).'</span>';
						}
					$body .= '</td>';

					if($eAction['fqn'] !== ACTION_SEMIS_DIRECT) {

						$body .= '<td class="planning-plant-value '.($eSeries->isTargeted() ? 'color-warning' : '').'">';

							if($eSlice['youngPlants'] > 0) {

								if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {
									$body .= $eSlice['youngPlants'];
								} else {
									$body .= p("{value} plant", "{value} plants", $eSlice['youngPlants']);
								}

								if($eVariety->exists() and $eSlice['seeds'] > 0 and $eVariety['numberPlantKilogram'] !== NULL) {
									$body .= '<small class="color-muted"> / '.\plant\VarietyUi::getPlantsWeight($eVariety, $eSlice['seeds']).'</small>';
								}

							} else {
								$body .= '?';
							}

						$body .= '</td>';
					}

					if($eAction['fqn'] !== ACTION_PLANTATION) {

						$body .= '<td class="planning-plant-value '.($eSeries->isTargeted() ? 'color-warning' : '').'">';

							if($eSlice['seeds'] > 0) {

								if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {
									$body .= $eSlice['seeds'];
								} else {
									$body .= p("{value} graine", "{value} graines", $eSlice['seeds']);
								}

								if($eVariety->exists() and $eSlice['seeds'] > 0 and $eVariety['weightSeed1000'] !== NULL) {
									$body .= '<small class="color-muted"> / '.\plant\VarietyUi::getSeedsWeight1000($eVariety, $eSlice['seeds']).'</small>';
								}

							} else {
								$body .= '?';
							}

						$body .= '</td>';
					}
					if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {

						$body .= '<td class="planning-plant-value">';

							foreach($eTask['cTool?']() as $eTool) {

								$eTool = $eTask['cTool?']()[$eTool['id']];

								$body .= '<div>';
									$body .= (ceil($eSlice['youngPlants'] / $eTool['routineValue']['value'] * 10) / 10);
								$body .= '</div>';

							}

						$body .= '</td>';

					}
				$body .= '</tr>';

			}

		}

		if($body) {

			$h = '<table class="planning-plant-list planning-plant-list-'.$eAction['fqn'].'" style="grid-column: span 3">';

				if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th></th>';
							if($eAction['fqn'] !== ACTION_SEMIS_DIRECT) {
								$h .= '<th class="planning-plant-value">';
									$h .= s("Plants");
								$h .= '</th>';
							}
							if($eAction['fqn'] !== ACTION_PLANTATION) {
								$h .= '<th class="planning-plant-value">';
									$h .= s("Semences");
								$h .= '</th>';
							}
							if($eAction['fqn'] === ACTION_SEMIS_PEPINIERE) {
								$h .= '<th class="planning-plant-value">';
									$h .= s("Plateaux");
								$h .= '</th>';
							}
						$h .= '</tr>';
					$h .= '</thead>';

				}

				$h .= $body;

			$h .= '</table>';

			return $h;

		} else {
			return '';
		}

	}

	public function getTaskDetails(\farm\Farm $eFarm, Task $eTask): string {

		$more = '';

		if($eFarm->hasFeatureTime() and $eTask['timeExpected'] > 0) {
			$more .= '<span>'.s("Travail estimé à {value}", TaskUi::convertTime($eTask['timeExpected'])).'</span>';
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

		$details = '';

		if($more) {
			$details .= '<div class="tasks-planning-item-more">';
				$details .= $more;
			$details .= '</div>';
		}

		$details .= new TaskUi()->getComments($eTask);

		if($details === '') {
			return '';
		}

		$h = '<div class="tasks-planning-item-details">';
			$h .= $details;
		$h .= '</div>';

		return $h;

	}

	protected function getNewTask(string $source, string $status, \farm\Farm $eFarm, array $seasonsWithSeries, \Collection $cCategory, \farm\Action $eActionSelected, ?string $week = NULL, ?string $date = NULL) {

		$eCategoryCulture = $cCategory->find(fn($eCategory) => $eCategory['fqn'] === CATEGORIE_CULTURE, limit: 1);

		$hasSeries = (
			$eActionSelected->empty() or
			in_array($eCategoryCulture['id'], $eActionSelected['categories'])
		);

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

		$button = ($source === 'weekly') ? 'btn-'.$status : 'btn-secondary';

		$h = '<div>';

			if($hasSeries) {

				$h .= '<a class="dropdown-toggle btn '.$button.'" data-dropdown="bottom-end">';
					$h .= \Asset::icon('plus-circle').' ';
					if($eActionSelected->empty()) {
						$h .= s("Nouvelle intervention");
					} else {
						$h .= encode($eActionSelected['name']);
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

						$url = '/series/task:createFromSeries?season='.$season.'&'.http_build_query($postSeason);
						if($eActionSelected->notEmpty()) {
							$url .= '&action='.$eActionSelected['id'];
						}

						$h .= '<a href="'.$url.'" class="dropdown-item">';
							$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.s("Saison {value}", $season);
						$h .= '</a>';

					}

				}

				if($seasonsWithSeries) {
					$h .= '<div class="dropdown-title">';
						$h .= s("Intervention hors série");
					$h .= '</div>';
				}

				if($eActionSelected->empty()) {

					foreach($cCategory as $eCategory) {

						$h .= '<a href="/series/task:createFromScratch?category='.$eCategory['id'].'&'.http_build_query($post).'" class="dropdown-item">';
							$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.encode($eCategory['name']);
						$h .= '</a>';

					}

				} else {

					$h .= $this->getNewCategoryTask($cCategory, $eActionSelected, $post);

				}

				$h .= '</div>';

			} else {

				if(count($eActionSelected['categories']) === 1) {

					$h .= '<a href="/series/task:createFromScratch?category='.$eActionSelected['categories'][0].'&action='.$eActionSelected['id'].'&'.http_build_query($post).'" class="btn '.$button.'">';
						$h .= \Asset::icon('plus-circle').' '.encode($eActionSelected['name']);
					$h .= '</a>';

				} else {

					$h .= '<a class="dropdown-toggle btn '.$button.'" data-dropdown="bottom-end">';
						$h .= \Asset::icon('plus-circle').' '.encode($eActionSelected['name']);
					$h .= '</a>';
					$h .= '<div class="dropdown-list '.$background.'">';
						$h .= $this->getNewCategoryTask($cCategory, $eActionSelected, $post);
					$h .= '</div>';
				}

			}

		$h .= '</div>';

		return $h;
	}

	protected function getNewCategoryTask(\Collection $cCategory, \farm\Action $eActionSelected, array $post): string {

		$h = '';

		foreach($eActionSelected['categories'] as $category) {

			$eCategory = $cCategory[$category];

			$h .= '<a href="/series/task:createFromScratch?category='.$eCategory['id'].'&action='.$eActionSelected['id'].'&'.http_build_query($post).'" class="dropdown-item">';
				$h .= '&nbsp;&nbsp;'.\Asset::icon('chevron-right').'&nbsp;&nbsp;'.encode($eCategory['name']);
			$h .= '</a>';

		}

		return $h;

	}

	public function getWeekTime(\farm\Farm $eFarm, string $week, \Collection $ccTask, \user\User $eUser, \Collection $cUserFarm) {

		if($eFarm->hasFeatureTime() === FALSE) {
			return '';
		}

		$eUser->expects(['weekTimesheet', 'cWorkingTimeWeek']);

		$tabItem = $this->getUsers($eFarm, $week, $eUser, $cUserFarm, fn($eUserFarm) => 'data-ajax="/hr/workingTime:getByUser" post-farm="'.$eFarm['id'].'" post-week="'.$week.'" post-user="'.($eUserFarm->notEmpty() ? $eUserFarm['id'] : '').'"');

		$tasks  = [];

		$estimated =
			($ccTask->offsetExists('todo') ? $ccTask['todo']->sum(function($e) use(&$tasks) {
				$tasks[$e['id']] = $e['timeExpected'];
				return $e['timeExpected'];
			}, 2) : 0) +
			($ccTask->offsetExists('done') ? $ccTask['done']->sum(fn($e) => array_key_exists($e['id'], $tasks) ? 0 : $e['timeExpected'], 2) : 0);

		if($eFarm->hasFeatureTime() and $estimated > 0) {
			$tabEstimated = '<div class="tabs-item-label" title="'.s("Inclus le temps de travail estimé sur les tâches planifiées et réalisées cette semaine.").'">';
				$tabEstimated .= s("Travail estimé à {value} sur la semaine", TaskUi::convertTime($estimated));
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
									$h .= TaskUi::convertTime($sumTimeWorking);

								}
							$h .= '</div>';
							if($eUser['weekTimesheet']) {

								$sumTimeTimesheet = round(array_sum($eUser['weekTimesheet']), 2);

								$alert = ($sumTimeWorking > 0 and $sumTimeTimesheet > $sumTimeWorking);
								$title = $alert ? s("Le temps de travail sur les interventions excède sur le temps de travail réel renseigné pour cette semaine !") : s("Temps de travail sur les interventions de la semaine");

								$h .= '<div class="tasks-time-day-timesheet '.($alert ? 'tasks-time-day-timesheet-alert' : '').'" title="'.$title.'">';
									$h .= \Asset::icon('calendar3').'&nbsp;&nbsp;'.TaskUi::convertTime($sumTimeTimesheet);
								$h .= '</div>';

							}
						$h .= '</div>';
					$h .= '</div>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getDayTimesheetTime(?float $timeWorking, ?float $timeTimesheet): string {

		if($timeTimesheet === NULL) {
			return '';
		}

		$alert = ($timeWorking > 0.0 and round($timeTimesheet, 2) > round($timeWorking, 2));
		$title = $alert ? s("Le temps de travail sur les interventions excède sur le temps de travail réel renseigné pour ce jour !") : s("Temps de travail sur les interventions");

		$h = '<div class="tasks-time-day-timesheet '.($alert ? 'tasks-time-day-timesheet-alert' : '').'" title="'.$title.'">';
			$h .= \Asset::icon('calendar3').'&nbsp;&nbsp;'.TaskUi::convertTime($timeTimesheet);
		$h .= '</div>';

		return $h;

	}

	public function getDayWorkingTime(\farm\Farm $eFarm, \user\User $eUser, string $date, ?float $timeWorking, ?float $timeTimesheet): string {

		$h = '';

		if($eFarm->canManage() or $eUser->isOnline()) {

			$future = (strcmp($date, currentDate()) > 0);
			$inconsistency = (
				$timeWorking > 0.0 and
				round($timeWorking, 2) < round($timeTimesheet ?? 0.0, 2)
			);

			$h .= '<div class="tasks-time-day-full '.($inconsistency ? 'bg-danger' : '').'">';

				$h .= '<a data-dropdown="bottom-start" class="tasks-time-day-full-link" title="'.($timeWorking !== NULL ? s("Temps de travail réel") : s("Renseigner le temps de travail total du jour")).'">';
					$h .= ($timeWorking > 0.0 ? TaskUi::convertTime($timeWorking) : ($future ? '' : \Asset::icon('pencil-fill')));
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

	protected function getYearTask(Task $eTask): string {

		$eTask->expects(['times']);

		$users = array_keys($eTask['times']);

		$h = '<a href="'.TaskUi::url($eTask).'" class="tasks-year-item" data-filter-action="'.$eTask['action']['id'].'" data-filter-user="'.implode(' ', $users).'" id="task-item-'.$eTask['id'].'">';

				$h .= '<div class="tasks-planning-item-series">';

					if($eTask['series']->empty() === FALSE) {
						$h .= s("Série {value}", encode($eTask['series']['name']));
					} else if($eTask['cccPlace']->notEmpty()) {
						$h .= new CultivationUi()->displayPlaces(Series::BED, $eTask['cccPlace']);
					}

				$h .= '</div>';

				$h .= '<div class="tasks-planning-item-content">';

					$h .= '<div class="tasks-planning-item-label">';
						$h .= new TaskUi()->getAction($eTask, \farm\FarmSetting::$mainActions);
					$h .= '</div>';

				$h .= '</div>';

		$h .= '</a>';

		return $h;

	}

}
?>
