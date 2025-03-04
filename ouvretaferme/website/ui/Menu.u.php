<?php
namespace website;

class MenuUi {

	public function __construct() {

	}

	public function create(Website $eWebsite, \Collection $cWebpage, string $for): \Panel {

		$form = new \util\FormUi();

		$eMenu = new Menu([
			'website' => $eWebsite
		]);

		$h = '';

		if(
			$for === 'webpage' and
			$cWebpage->empty()
		) {
			$h .= '<div class="util-info">';
				$h .= s("Vous avez déjà ajouté toutes les pages de votre site au menu.");
			$h .= '</div>';
		} else {

			$h .= $form->openAjax('/website/menu:doCreate');

				$h .= $form->hidden('website', $eWebsite['id']);

				$h .= match($for) {
					'webpage' => $form->dynamicGroup($eMenu, 'webpage*', function($d) use($cWebpage) {
						$d->values = $cWebpage;
					}),
					'url' => $form->dynamicGroup($eMenu, 'url*')
				};

				$h .= $form->dynamicGroup($eMenu, 'label*');

				$h .= $form->group(
					content: $form->submit(s("Ajouter au menu"))
				);

			$h .= $form->close();

		}

		return new \Panel(
			id: 'panel-menu-create',
			title: s("Ajouter une page au menu"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Menu::model()->describer($property, [
			'url' => s("Adresse de destination"),
			'webpage' => s("Page de destination"),
			'label' => s("Texte"),
		]);

		switch($property) {

			case 'webpage' :
				$d->field = 'select';
				break;

		}

		return $d;

	}

}
?>
