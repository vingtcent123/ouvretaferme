<?php
namespace main;

Class MainUi {

	public static function getDropdownMenuTitle(array $categories, string $selectedView): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';

				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView]['longLabel'].' '.'<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
				$h .= '</a>';

				$h .= '<div class="dropdown-list bg-primary">';

					foreach($categories as $category => $categoryData) {
						$h .= '<a href="'.$categoryData['url'].'" class="dropdown-item '.($category === $selectedView ? 'selected' : '').'">'.$categoryData['longLabel'].'</a> ';
					}

				$h .= '</div>';

			$h .= '</h1>';

		$h .= '</div>';

		return $h;
	}

}
?>
