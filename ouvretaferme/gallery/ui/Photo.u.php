<?php
namespace gallery;

class PhotoUi {

	public function __construct() {

		\Asset::css('gallery', 'photo.css');

	}

	public function getList(\Collection $cPhoto, ?int $lines = NULL, int $maxDensity = 5): string {

		\Asset::js('editor', 'gallery.js');

		$id = uniqid('galleryPhotos');

		$h = '<div id="'.$id.'" class="gallery-photos" data-density="strong">';

		foreach($cPhoto as $ePhoto) {

			if($ePhoto['title'] === NULL) {
				$caption = '';
			} else if(mb_strlen($ePhoto['title']) < 100) {
				$caption = encode($ePhoto['title']);
			} else {
				$caption = encode(mb_substr($ePhoto['title'], 0, 80).'...');
			}

			$h .= '<figure data-w="'.$ePhoto['width'].'" data-h="'.$ePhoto['height'].'" data-id="'.$ePhoto['id'].'" class="gallery-photo">';
				$h .= '<a href="/gallery/photo?id='.$ePhoto['id'].'">';
					$h .= \Asset::image((new \media\GalleryUi())->getUrlByHash($ePhoto['hash'], 'm'));
				$h .= '</a>';
				if($caption) {
					$h .= '<figcaption>'.$caption.'</figcaption>';
				}
			$h .= '</figure>';

		}

		$h .= '</div>';

		$h .= '<script>';
		$h .= 'function '.$id.'() {
				if(window.matchMedia(\'(max-width: 575px)\').matches) {
					return 2;
				} else if(window.matchMedia(\'(max-width: 767px)\').matches) {
					return '.min($maxDensity, 3).';
				} else if(window.matchMedia(\'(max-width: 991px)\').matches) {
					return '.min($maxDensity, 4).';
				} else {
					return '.min($maxDensity, 5).';
				}
			};';
		$h .= 'new GalleryEditor(qs(\'#'.$id.'\'), {
				container: \'figure\',
				lineSize: function(n) {
					return Math.min(n, '.$id.'());
				},
				maxHeight: function() {
					return '.(250 + (5 - $maxDensity) * 50).';
				}';

		if($lines !== NULL) {

			$h .= ',
				maxItems: function() {
					if(window.matchMedia(\'(max-width: 767px)\').matches) {
						return Math.min('.$id.'() * '.($lines + 1).', Math.max(1, Math.floor('.$cPhoto->count().' / '.$id.'())) * '.$id.'());
					} else {
						return Math.min('.$id.'() * '.$lines.', Math.max(1, Math.floor('.$cPhoto->count().' / '.$id.'())) * '.$id.'());
					}
				}';

		}

		$h .= '}).listenResize().listenMutation();';
		$h .= '</script>';

		return $h;

	}

	public function displayOne(Photo $ePhoto): string {

		$url = (new \media\GalleryUi())->getUrlByHash($ePhoto['hash']);

		$h = '<div class="gallery-photo-one">';
			$h .= \Asset::image($url);
		$h .= '</div>';

		return $h;

	}

	public function displayPanel(Photo $ePhoto): \Panel {

		$h = $this->getDescription($ePhoto, TRUE);
		$h .= $this->displayOne($ePhoto);

		if($ePhoto['takenAt'] !== NULL) {
			$header = s("Photo prise en {value}", ucfirst(\util\DateUi::textual($ePhoto['takenAt'], \util\DateUi::MONTH_YEAR)));
		} else {
			$header = NULL;
		}

		return new \Panel(
			id: 'panel-photo-fullscreen',
			title: $ePhoto['title'] ?? s("Photo"),
			subTitle: $header,
			body: $h
		);

	}

	public function getDescription(Photo $ePhoto, $withActions): string {

		$h = '<div class="gallery-photo-description">';
			$h .= '<div class="gallery-photo-infos">';
				$h .= '<div>';
					$h .= \farm\FarmUi::link($ePhoto['farm']);
				$h .= '</div>';
				$h .= '<div>';
					$h .= \Asset::icon('camera-fill').' ';
					$h .= \user\UserUi::name($ePhoto['author']);
				$h .= '</div>';
				$h .= '<div>';
					$h .= s("Téléversé le {value}", \util\DateUi::numeric($ePhoto['createdAt'], \util\DateUi::DATE));
				$h .= '</div>';
			$h .= '</div>';
			if($withActions and $ePhoto->canWrite()) {
				$h .= '<div class="gallery-photo-actions">';
					$h .= '<a href="/gallery/photo:update?id='.$ePhoto['id'].'" class="btn btn-primary">'.s("Modifier").'</a> ';
					$h .= '<a data-ajax="/gallery/photo:doDelete" post-id="'.$ePhoto['id'].'" class="btn btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer cette photo ?").'">'.s("Supprimer").'</a>';
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function update(Photo $ePhoto): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/gallery/photo:doUpdate');

			$h .= $form->hidden('id', $ePhoto['id']);

			$h .= $form->dynamicGroup($ePhoto, 'title');
			$h .= $form->dynamicGroup($ePhoto, 'takenAt');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une photo"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Photo::model()->describer($property, [
			'sequence' => s("Itinéraire technique"),
			'farm' => s("Ferme de la prise de vue"),
			'series' => s("Série"),
			'task' => s("Intervention"),
			'title' => s("Légende de la photo"),
			'takenAt' => s("Date de la prise de vue"),
			'status' => s("Statut"),
		]);

		switch($property) {

			case 'title' :
				$d->placeholder = s("Ajoutez une légende pour décrire précisément cette photo.");
				break;

			case 'takenAt' :
				$d->default = function(Photo $e) {
					return $e->empty() ? currentDate() : $e['takenAt'];
				};
				break;

			case 'farm' :
				(new \farm\FarmUi())->query($d);
				break;

		}

		return $d;

	}

}
?>
