<?php
namespace website;

class WebsiteUi {

	public static function link(Website $eWebsite): string {

		$url = self::url($eWebsite);

		return '<a href="'.encode($url).'" target="_blank">'.$url.'</a>';

	}

	public static function url(Website $eWebsite, string $url = '/', bool $showProtocol = TRUE, bool $internalDomain = FALSE): string {

		$eWebsite->expects(['id', 'domain', 'domainStatus', 'internalDomain']);

		if(
			$internalDomain === FALSE and
			$eWebsite['domainStatus'] === Website::PINGED_SECURED and
			LIME_ENV === 'prod'
		) {
			$domain = $eWebsite['domain'];
		} else {
			$domain = \Setting::get('domain').'/'.encode($eWebsite['internalDomain']);
		}

		return $showProtocol ? match(LIME_ENV) {
			'dev' => 'http://'.$domain.$url,
			'prod' => SERVER('REQUEST_SCHEME', default: 'https').'://'.$domain.$url
		} : $domain;

	}

	public static function path(Website $eWebsite, string $url = '/'): string {

		$eWebsite->expects(['id', 'domain', 'domainStatus', 'internalDomain']);

		if($eWebsite['domainStatus'] === Website::PINGED_SECURED and LIME_ENV === 'prod') {
			$prefix = '';
		} else {
			$prefix = '/'.encode($eWebsite['internalDomain']);
		}

		if(get_exists('customDesign')) {
			$url .= '?'.http_build_query([
				'customDesign' => GET('customDesign'),
				'customWidth' => GET('customWidth'),
				'customText' => GET('customText'),
				'customBackground' => GET('customBackground'),
				'customColor' => GET('customColor'),
				'customLinkColor' => GET('customLinkColor'),
				'customFont' => GET('customFont'),
				'customTitleFont' => GET('customTitleFont'),
			]);
		}
		return match(LIME_ENV) {
			'dev' => $prefix.$url,
			'prod' => $prefix.$url
		};

	}

	public static function getLogo(Website $eWebsite, string $size): string {

		$eWebsite->expects(['id', 'logo']);

		if($eWebsite['logo'] === NULL) {
			return '';
		}

		$ui = new \media\WebsiteLogoUi();

		$format = $ui->convertToFormat($size);

		$style = 'background-image: url('.$ui->getUrlByElement($eWebsite, $format).');';

		return '<div class="media-rectangle-view" style="'.$ui->getSquareCss($size).'; '.$style.'"></div>';

	}

	public static function getBanner(Website $eWebsite, string $width): string {

		$eWebsite->expects(['id', 'banner']);

		$ui = new \media\WebsiteBannerUi();

		$class = 'media-banner-view'.' ';
		$style = '';

		if($eWebsite['banner'] !== NULL) {
			$style .= 'background-image: url('.$ui->getUrlByElement($eWebsite, 's').');';
		}

		return '<div class="'.$class.'" style="width: '.$width.'; max-width: 100%; height: auto; aspect-ratio: 3; '.$style.'"></div>';

	}

	public static function getLogoImage(Website $eWebsite): string {

		$eWebsite->expects(['id', 'logo']);

		if($eWebsite['logo'] === NULL) {
			return '';
		}

		$ui = new \media\WebsiteLogoUi();

		return \Asset::image($ui->getUrlByElement($eWebsite, 'm'));

	}

	public static function getFavicon(Website $eWebsite, string $size): string {

		$eWebsite->expects(['id', 'favicon']);

		if($eWebsite['favicon'] === NULL) {
			return '';
		}

		$ui = new \media\WebsitefaviconUi();

		$format = $ui->convertToFormat($size);
		$style = 'background-image: url('.$ui->getUrlByElement($eWebsite, $format).');';

		return '<div class="media-rectangle-view" style="'.$ui->getSquareCss($size).'; '.$style.'"></div>';

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Website::model()->describer($property, [
			'customBackground' => s("Couleur d'arrière plan"),
			'customText' => s("Couleur du texte"),
			'customColor' => s("Couleur contrastante"),
			'customLinkColor' => s("Couleur des liens"),
			'customDesign' => s("Template"),
			'customWidth' => s("Largeur maximale du contenu"),
			'customFont' => s("Police pour le texte"),
			'customTitleFont' => s("Police pour le titre principal des pages"),
			'internalDomain' => s("Adresse du site sur <u>{siteName}</u>"),
			'domain' => s("Nom de domaine"),
			'logo' => s("Logo"),
			'name' => s("Titre du site"),
			'description' => s("Description du site"),
		]);

		switch($property) {

			case 'internalDomain' :
				$d->prepend = \Lime::getProtocol().'://'.\Setting::get('domain').'/';
				$d->after = \util\FormUi::info(s("Uniquement des chiffres, des lettres ou des tirets"));
				break;

			case 'domain' :
				$d->prepend = \Lime::getProtocol().'://';
				$d->after = \util\FormUi::info(s("Si vous renseignez un nom de domaine, celui-ci est prioritaire par rapport à l'adresse du site sur <u>{siteName}</u>. Vous devez acheter et configurer séparément votre nom de domaine chez un vendeur agréé pour utiliser cette fonctionnalité."));
				break;

			case 'name' :
				$d->attributes = ['data-limit' => Website::model()->getPropertyRange('name')[1]];
				break;

			case 'description' :
				$d->field = 'textarea';
				$d->attributes = ['data-limit' => Website::model()->getPropertyRange('description')[1]];
				$d->label .= \util\FormUi::info(s("Utilisée pour les moteurs de recherche"));
				break;

			case 'customDesign':
				$d->values = fn(Website $e) => $e['cDesign'] ?? $e->expects(['cDesign']);
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'customWidth':
				$d->append = 'pixels';
				$d->labelAfter = \util\FormUi::info(s("La valeur par défaut de {value} pixels est généralement un bon compromis.", Website::model()->getDefaultValue('customWidth')));
				break;

			case 'customText':
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = [
					Website::BLACK => s("Noir"),
					Website::WHITE => s("Blanc"),
				];
				break;

			case 'customFont':
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = \Setting::get('website\customFonts');
				break;

			case 'customColor':
				$d->labelAfter = \util\FormUi::info(s("Utilisée sur certains templates ainsi que sur les petits écrans"));
				break;

			case 'customLinkColor':
				$d->labelAfter = \util\FormUi::info(s("À l'exception des liens du menu principal de votre site"));
				$d->placeholder = s("Par défaut");
				break;

			case 'customTitleFont':
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = \Setting::get('website\customTitleFonts');
				break;
		}

		return $d;

	}

}
?>
