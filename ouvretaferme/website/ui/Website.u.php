<?php
namespace website;

class WebsiteUi {

	public static function link(Website $eWebsite): string {

		$url = self::url($eWebsite);

		return '<a href="'.encode($url).'" target="_blank">'.$url.'</a>';

	}

	public static function url(Website $eWebsite, string $url = '/', bool $showProtocol = TRUE): string {

		$eWebsite->expects(['id', 'domain', 'domainStatus', 'internalDomain']);

		if($eWebsite['domainStatus'] === Website::PINGED_SECURED and LIME_ENV === 'prod') {
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
				'customColor' => GET('customColor'),
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
			'customColor' => s("Couleur contrastante"),
			'customDesign' => s("Template"),
			'customFont' => s("Police pour le texte"),
			'customTitleFont' => s("Police pour le titre principal des pages"),
			'internalDomain' => s("Adresse du site sur <u>{siteName}</u>"),
			'domain' => s("Nom de domaine"),
			'logo' => s("Logo"),
			'name' => s("Nom du site"),
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
				break;

			case 'customFont':
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = \Setting::get('website\customFonts');
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
