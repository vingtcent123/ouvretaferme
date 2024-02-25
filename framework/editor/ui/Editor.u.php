<?php
namespace editor;

/**
 * Use editor
 *
 */
class EditorUi {

	/**
	 * Editor features initialized?
	 *
	 * @var bool
	 */
	private static bool $isInitialized = FALSE;

	/**
	 * Last ID used to build an editor field
	 *
	 * @var string
	 */
	private static ?string $lastId = NULL;

	public function __construct() {

		\Asset::js('editor', 'gallery.js');
		\Asset::css('editor', 'editor.css');

		\Asset::js('media', 'conf.js');

	}

	/**
	 * Display a value created with the editor
	 *
	 * @param string $id
	 * @param string $value
	 * @return array
	 */
	public function value(?string $value, array $options = [], array $attributes = []): string {

		\Asset::js('editor', 'reador.js');
		\Asset::js('editor', 'readitor.js');

		$options += [
			'domain' => \Lime::getDomain()
		];

		if(isset($attributes['id']) === FALSE) {
			$attributes['id'] = 'reador-'.uniqid();
		}

		$attributes['class'] = 'readitor reador '.($attributes['class'] ?? '');

		$h = '<div '.attrs($attributes).'>';
			if($value !== NULL) {
				$h .= (new \editor\ReadorFormatterUi())->getFromXml($value, $options);
			}
		$h .= '</div>';

		$h .= '<script type="text/javascript">';
			foreach(\editor\ReadorFormatterUi::getCut() as $figure => list($number, $value)) {
				$h .= 'Reador.saveCut("#'.$figure.'", '.json_encode([$this->getCutLink('#'.$figure, $number), $value]).');';
			}
			$h .= 'Reador.reorganizeInstance("#'.$attributes['id'].'");';
		$h .= '</script>';

		return $h;

	}

	protected function getCutLink($figureId, $number) {

		$h = '<div class="reador-cut-link">';
			$h .= '<a data-action="figure-extend" data-figure="'.$figureId.'" title="'.s("Voir les autres photos").'">';
				$h .= p("{value} photo", "{value} photos", $number, ['number' => '<span class="reador-cut-link-number">+'.$number.'</span>']);
			$h .= '</a>';
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a text area to use editor
	 *
	 * @param string $name Field name
	 * @param array $options
	 * 	acceptFigures = Enable/disable figures?
	 * 	placeholder	= Placeholder if empty
	 * @param string $defaultValue Default value
	 *
	 */
	public function field(string $name, array $options, string $defaultValue = '', array $attributes = []): string {

		\Asset::lib('util', 'Sortable-1.11.0.js');

		$options += [
			'acceptFigure' => FALSE,
			'figurePlaceholders' => FALSE,
			'speed' => 'fast',
		];

		\Asset::js('editor', 'editor.js');
		\Asset::js('editor', 'readitor.js');
		\Asset::js('media', 'upload.js');

		$h = $this->init($options['acceptFigure']);

		if(isset($attributes['placeholder'])) {
			$placeholderEmpty = $attributes['placeholder'];
		} else {

			if($options['acceptFigure'] and $options['figurePlaceholders']) {
				$placeholderEmpty = s("Tapez du texte ou utilisez le {plus} pour ajouter photos ou vidéos ici !", ['plus' => \Asset::icon('plus-circle')]);
			} else {
				$placeholderEmpty = s("Commencez à écrire...");
			}

		}

		$id = 'editor-'.$name.'-'.str_replace('.', '', (string)microtime(TRUE)).'';

		if(isset($attributes['class'])) {
			$class = $attributes['class'];
			unset($attributes['class']);
		} else {
			$class = '';
		}

		$data = 'data-admin="'.((int)\Privilege::can('user\admin')).'"';
		$data .= ' data-speed="'.$options['speed'].'"';

		if($options['acceptFigure']) {

			$data .= ' data-figure="1"';

			if($options['figurePlaceholders']) {
				$data .= ' data-placeholder-focus="'.encode($placeholderEmpty).'"';
			} else {
				$data .= ' data-placeholder-focus=""';
			}

			if($defaultValue === '') {
				$defaultValue = '<p><br/></p>';
			}

		} else {

			$data .= ' data-figure="0"';
			$class .= ' editor-bordered';

		}

		$h .= '<div contenteditable="true" id="'.$id.'" '.$data.' data-editor="true" data-started="0" data-name="'.$name.'" data-progress="'.s("Veuillez patienter le temps que toutes les images soient téléchargées...").'" data-placeholder-empty="'.encode($placeholderEmpty).'" class="readitor editor form-control '.$class.'" '.attrs($attributes).'>'.$defaultValue.'</div>';

		$js = '<script type="text/javascript">';
			$js .= 'Editor.startInstance("#'.$id.'");';

			if($defaultValue) {
				$js .= 'EditorFigure.reorganizeInstance("#'.$id.'");';
			}
		$js .= '</script>';

		self::$lastId = $id;

		return $h.$js;

	}

	/**
	 * Returns last ID
	 *
	 * @return string
	 */
	public static function getLastId() {
		return self::$lastId;
	}

	/**
	 * Initializes JS variables with translation
	 * Should be called only once
	 *
	 * Initializes the panels
	 *
	 */
	public function init(bool $acceptFigure): string {

		if(self::$isInitialized) {
			return '';
		}

		self::$isInitialized = TRUE;

		$h = '<script type="text/javascript">';
			$h .= 'Editor.labels = '.json_encode($this->labels()).';';
			$h .= 'Editor.conf = {';
				$h .= 'captionLimit: '.\Setting::get('captionLimit');
			$h .= '};';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Returns useful labels
	 *
	 */
	public function labels(): array {

		return [
			'placeholderLink' => s("Votre lien"),
			'header' => s("Transformer en titre"),
			'link' => s("Créer ou supprimer un lien"),
			'button' => s("Transformer en bouton"),
			'confirmHeader' => s("Les styles en gras, italique, souligné ainsi que les liens seront perdus. Voulez-vous toujours transformer ce paragraphe en titre ?"),
			'confirmRemoveLinks' => s("Si vous continuez, les liens que vous avez sélectionnés seront supprimés. Voulez-vous continuer ?"),
			'confirmRemoveLink' => s("Souhaitez-vous supprimer ce lien ?"),
			'media' => s("Ajouter une photo, une vidéo, une astuce ou un séparateur"),
			'imageLabel' => s("PHOTO"),
			'image' => s("Ajouter une photo"),
			'imageFigure' => s("Ajouter une photo à la mosaïque"),
			'videoLabel' => s("VIDEO"),
			'video' => s("Ajouter une vidéo"),
			'videoFigure' => s("Ajouter une vidéo à la mosaïque"),
			'separator' => s("Insérer un séparateur"),
			'linkFilledSubmit' => s("Modifier"),
			'linkFilledClose' => s("Supprimer"),
			'linkEmptySubmit' => s("Insérer"),
			'linkEmptyClose' => s("Annuler"),
			'grid' => s("Insérer une grille"),
			'quote' => s("Insérer une astuce ou un conseil"),
			'quoteIcon' => s("Changer l'icône"),
			'quoteIcons' => FormatterUi::getQuoteIcons(),
			'confirmRemoveFigure' => s("Voulez-vous supprimer cette mosaïque et tout ce qu'elle contient ?"),
			'confirmRemoveMedia' => s("Voulez-vous supprimer cet élément ?"),
			'removeFigure' => s("Supprimer la mosaïque"),
			'moveFigure' => s("Déplacer la mosaïque"),
			'captionFigure' => s("Tapez une légende (facultatif)"),
			'captionLimit' => s("Au delà de {value} caractères, la légende est tronquée à l'affichage.", \Setting::get('editor\captionLimit')),
			'move' => s("Déplacer"),
			'configure' => s("Paramétrer"),
			'delete' => s("Supprimer"),
			'crop' => s("Recadrer"),
			'cropResolution' => s("Cette photo ne peut pas être recadrée car elle fait moins de 2000 pixels sur son plus grand côté"),
			'resizeLabel' => s("DISPOSITION"),
			'resize' => s("Modifier la disposition de la mosaïque"),
			'resizeLeft' => s("À gauche"),
			'resizeRight' => s("À droite"),
			'resizeCompress' => s("Ajusté au texte"),
			'figureNewLine' => s("Ajouter une ligne vide ici"),
		];

	}

	public function getVideoConfigure(): \Panel {

		return new \Panel(
			id: 'panel-editor-video',
			title: s("Insérer une vidéo"),
			body: '<p>'.s(
				"Vous pouvez insérer une vidéo provenant de <linkY>Youtube</linkY>, <linkD>Dailymotion</linkD> ou <linkV>Vimeo</linkV>.",
				[
					'linkY' => '<a href="http://www.youtube.com">',
					'linkD' => '<a href="http://www.dailymotion.com">',
					'linkV' => '<a href="https://vimeo.com/">',
				]
			).'</p>'.
			'<form id="editor-video-form">'.
				'<div class="input-group">'.
					'<input type="text" class="form-control" id="editor-video-value" placeholder="http://"/>'.
					'<span class="input-group-btn">'.
						' <button type="submit" class="btn btn-primary">'.s("Insérer").'</button>'.
					'</span>'.
				'</div>'.
			'</form>',
			layer: FALSE
		);

	}

	public function getGridConfigure(): \Panel {

		$form = new \util\FormUi();

		return new \Panel(
			id: 'panel-editor-grid',
			title: s("Insérer une grille"),
			body: '<p>'.s("Choisissez le nombre de colonnes et de lignes pour votre grille. Veuillez noter que sur les petits écrans comme les téléphones, le nombre de colonnes se réduira automatiquement pour conserver une bonne qualité d'affichage.").'</p>'.
			$form->open('editor-grid-form').
				$form->group(
					s("Nombre de colonnes"),
					$form->number('columns', 2, ['min' => 2, 'max' => 4])
				).
				$form->group(
					s("Nombre de lignes"),
					$form->number('rows', 1, ['min' => 1, 'max' => 5])
				).
				$form->group(
					NULL,
					$form->submit(s("Insérer"))
				).
			$form->close(),
			layer: FALSE
		);

	}

	public function getMediaConfigure(string $instanceId, string $url, string $xyz = NULL, string $title, string $link, int $figureSize): \Panel {

		$form = new \util\FormUi();

		$h = $form->open('editor-media-configure');

			$h .= $form->hidden('url', $url);
			$h .= $form->group(
				s("Titre :"),
				$form->text('title', $title, ['placeholder' => s("Donnez un titre à cette photo"), 'maxlength' => \Setting::get('mediaTitleLimit'), 'data-limit' => \Setting::get('mediaTitleLimit')])
			);
			$h .= $form->group(
				s("Lien :"),
				$form->url('link', $link, ['placeholder' => s("http://www.votrelien.org/...")])
			);
			$h .= $form->group(
				content: $form->submit(s("Enregistrer")).
				'<a onclick="Lime.Panel.closeLast()" class="btn">'.s("Annuler").'</a>'
			);

		$h .= $form->close();

		$actions = [];

		if($xyz) {

			$extension = substr($xyz, -1);

			if($extension !== 'g') { // Can not rotate GIF

				$version = time();

				$rotate = '';
				$rotate .= '<a data-action="media-rotate-show">'.s("Faire pivoter la photo").'</a>';
				$rotate .= '<div id="media-rotate-list">';
					$rotate .= '<p class="color-muted">'.s("Choisissez la nouvelle orientation").'</p>';
					foreach([90, 180, 270] as $angle) {
						$rotate .= '<a data-action="media-rotate" data-xyz="'.encode($xyz).'" data-angle="'.$angle.'" data-confirm="'.s("Souhaitez-vous faire pivoter votre photo de {value} degrés ?", $angle).'">';
							$rotate .= '<img src="'.encode($url).'?'.$version.'" class="angle-'.$angle.'"/>';
						$rotate .= '</a>';
					}
				$rotate .= '</div>';

				$actions[] = $rotate;

			}

			$actions[] = '<a data-action="media-download" data-url="'.encode($url).'">'.s("Télécharger la photo originale").'</a>';

		}

		if($figureSize > 1) {
			$actions[] = '<a data-action="media-separate" data-instance="'.encode($instanceId).'" data-confirm="'.s("Voulez-vous sortir cet élément de sa mosaïque actuelle pour qu'il soit sur une ligne entière ?").'">'.s("Créer une nouvelle mosaïque à partir de cette photo").'</a>';
		}

		if($actions) {
			$h .= '<br/>';
			$h .= '<div>';
				$h .= '<h4>'.s("Autres actions").'</h4>';
				$h .= '<hr/>';
				$h .= '<ul>';
					foreach($actions as $action) {
						$h .= '<li>'.$action.'</li>';
					}
				$h .= '</ul>';
			$h .= '</div>';
		}

		return new \Panel(
			id: 'panel-editor-media-configure',
			title: s("Paramétrer une photo"),
			body: $h,
			layer: FALSE
		);


	}

}
?>
