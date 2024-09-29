<?php
namespace util;

class BatchUi {

	public static function one(string $menu) {

		\Asset::css('util', 'batch.css');
		\Asset::js('util', 'batch.js');

		$form = new \util\FormUi();

		$h = '<div id="batch-one" class="batch-one hide">';

			$h .= $form->open('batch-one-form');

				$h .= '<div class="batch-ids hide"></div>';
				$h .= '<div class="batch-one-menu">';
					$h .= $menu;
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public static function group(string $menu, string $danger = NULL, string $title = NULL, string $hide = 'Batch.hideSelection()') {

		\Asset::css('util', 'batch.css');
		\Asset::js('util', 'batch.js');

		$form = new \util\FormUi();

		$title ??= s("Pour la sélection");

		$h = '<div id="batch-group" class="hide">';

			$h .= $form->open('batch-group-form');

			$h .= '<div class="batch-ids hide"></div>';

			$h .= '<div class="batch-title">';
				$h .= '<h4>'.$title.' (<span id="batch-group-count"></span>)</h4>';
				$h .= '<a onclick="'.$hide.'" class="btn btn-transparent">'.s("Annuler").'</a>';
			$h .= '</div>';

			$h .= '<div class="batch-menu">';
				$h .= '<div class="batch-menu-main">';
					$h .= $menu;
				$h .= '</div>';
				if($danger !== NULL) {
					$h .= '<div class="batch-menu-danger">';
						$h .= $danger;
					$h .= '</div>';
				}
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

}
?>
