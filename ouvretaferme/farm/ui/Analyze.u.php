<?php
namespace farm;

class AnalyzeUi {

	public function __construct() {

		\Asset::css('analyze', 'chart.css');
		\Asset::js('analyze', 'chart.js');

	}

	public function getTime(\farm\Action $eAction, Category $eCategory, int $year, \Collection $cTimesheetTarget, \Collection $cTimesheetMonth, \Collection $cTimesheetMonthBefore, \Collection $cTimesheetUser): \Panel {

		$h = '';

		if($cTimesheetTarget->notEmpty()) {

			$h .= $this->getTimesheet($eAction, $eCategory, $cTimesheetTarget, $year);

			if($cTimesheetMonth->notEmpty()) {

				$h .= '<br/>';

				$h .= '<h3>'.s("Temps de travail mensuel").'</h3>';
				$h .= '<div class="analyze-chart-table">';
					$h .= new \series\AnalyzeUi()->getPeriodMonthTable($cTimesheetMonth, $eCategory['farm']->canPersonalData() ? $cTimesheetUser : new \Collection());
					$h .= new \series\AnalyzeUi()->getPeriodMonthChart($cTimesheetMonth, $year, $cTimesheetMonthBefore, $year - 1);
				$h .= '</div>';

			} else {
				$h .= '<p class="util-empty">';
					$h .= s("Il n'y a aucune intervention en {value}.", $year);
				$h .= '</p>';
			}

		} else {

			$h .= '<p class="util-empty">';
				if($eAction->empty()) {
					$h .= s("Vous n'avez jamais utilisé cette catégorie.");
				} else {
					$h .= s("Vous n'avez jamais utilisé cette intervention.");
				}
			$h .= '</p>';

		}

		if($eAction->empty()) {
			$title = s("{value} en {year}", ['value' => encode($eCategory['name']), 'year' => $year]);
			$header = '<h2 class="panel-title">'.$title.'</h2>';
		} else {
			$title = s("{value} en {year}", ['value' => encode($eAction['name']), 'year' => $year]);
			$header = '<h2 class="panel-title">'.$title.'</h2>';
			$header .= '<h4 class="panel-subtitle">'.encode($eCategory['name']).'</h4>';
		}

		return new \Panel(
			id: 'panel-action-analyze',
			documentTitle: $title,
			body: $h,
			header: $header,
		);

	}

	public function getTimesheet(\farm\Action $eAction, Category $eCategory, \Collection $cTimesheetTarget, ?int $year): string {

		$h = '<ul class="util-summarize">';

			foreach($cTimesheetTarget as $eTimesheet) {

				$h .= '<li '.($eTimesheet['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a data-ajax="/farm/action:analyzeTime?'.($eAction->notEmpty() ? 'action='.$eAction['id'] : '').'&category='.$eCategory['id'].'&year='.$eTimesheet['year'].'" data-ajax-method="get">';
						$h .= '<h5>'.$eTimesheet['year'].'</h5>';
						$h .= '<div>'.\series\TaskUi::convertTime($eTimesheet['time']).'</div>';
					$h .= '</a>';
				$h .= '</li>';

			}

		$h .= '</ul>';

		return $h;

	}

	public function getYears(
		array $years,
		int $selectedYear, ?int $selectedMonth, ?string $selectedWeek,
		\Closure $url
	): string {

		if(count($years) === 1) {
			return '<div class="nav-year">'.$selectedYear.'</div>';
		}

		$h = '<a data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-offset-x="2" class="nav-year">'.s("Année {value}", $selectedYear).'  '.\Asset::icon('chevron-down').'</a>';

		$h .= '<div class="dropdown-list bg-primary">';

			$h .= '<div class="dropdown-title">'.s("Changer l'année").'</div>';

			foreach($years as $year) {

				$h .= '<a href="'.$url($year).''.($selectedMonth ? '?month='.$selectedMonth : '').'" class="dropdown-item dropdown-item-full '.(($selectedYear === $year and $selectedMonth === NULL) ? 'selected' : '').'">'.s("Année {value}", $year).'</a>';

			}

		$h .= '</div>';

		if($selectedMonth !== NULL) {
			$h .= ' '.\Asset::icon('chevron-right').' ';
			$h .= '<div class="btn-group">';
				$h .= '<a data-dropdown="bottom-start" data-dropdown-hover="true" class="btn btn-sm btn-outline-primary">'.mb_ucfirst(\util\DateUi::getMonthName($selectedMonth)).' '.\Asset::icon('chevron-down').'</a>';
				$h .= '<div class="dropdown-list">';
					foreach(\util\DateUi::months() as $position => $month) {
						$h .= '<a href="'.$url($selectedYear).'?month='.$position.'" class="dropdown-item">'.mb_ucfirst($month).'</a>';
					}
				$h .= '</div>';
				$h .= '<a href="'.$url($selectedYear,).'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('x-circle').'</a>';
			$h .= '</div>';
		}

		if($selectedWeek !== NULL) {
			$h .= ' '.\Asset::icon('chevron-right').' ';
			$h .= s("Semaine {value}", week_number($selectedWeek));
			$h .= ' <a href="'.$url($selectedYear).'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('x-circle').'</a>';
		}

		return $h;

	}

}
?>
