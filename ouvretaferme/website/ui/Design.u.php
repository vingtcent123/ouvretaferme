<?php
namespace website;

class DesignUi {

	public function __construct() {

	}

	public static function getCSSFile(Website $eWebsite): string {

		if(get_exists('customDesign')) {
			return 'design-'.GET('customDesign', 'int').'.css';
		}

		return 'design-'.$eWebsite['customDesign']['id'].'.css';

	}

	public static function getStyles(Website $eWebsite): string {

		$fontLabel = ($eWebsite->canWrite() and get_exists('customFont')) ? GET('customFont') : $eWebsite['customFont'];
		$titleFontLabel = ($eWebsite->canWrite() and get_exists('customTitleFont')) ? GET('customTitleFont') : $eWebsite['customTitleFont'];

		$font = \website\DesignUi::getFont($fontLabel);
		$titleFont = \website\DesignUi::getTitleFont($titleFontLabel);

		$families = [];

		if($font !== NULL) {
			$families[] = 'family='.$font['label'];
		}

		if($titleFont !== NULL) {
			$families[] = 'family='.$titleFont['label'];
		}

		$text = Website::GET('customText', 'customText', $eWebsite['customText']);

		if($families) {
			$h = '<link rel="preconnect" href="https://fonts.googleapis.com">';
			$h .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
			$h .= '<link href="https://fonts.googleapis.com/css2?'.implode('&', $families).'" rel="stylesheet">';
		}

		$color = ($eWebsite->canWrite() and get_exists('customColor')) ? GET('customColor') : $eWebsite['customColor'];
		$background = ($eWebsite->canWrite() and get_exists('customBackground')) ? GET('customBackground') : $eWebsite['customBackground'];

		$h .= '<style>';
		$h .= ':root {
			--background: '.$background.';
			--primary: '.$color.';
			--container-max-width: '.$eWebsite['customDesign']['maxWidth'].';
			--custom-font: '.($font ? $font['value'] : "'Open Sans', sans-serif").';
			--custom-title-font: '.($titleFont ? $titleFont['value'] : "'Open Sans', sans-serif").';
			--border: '.($text === Website::BLACK ? '#8883' : '#8883').';
			--textColor: '.($text === Website::BLACK ? 'var(--text)' : 'white').';
			--linkColor: '.($text === Website::BLACK ? 'black' : 'white').';
			--muted: '.($text === Website::BLACK ? '#888' : '#CCC').';
			--transparent: '.($text === Website::BLACK ? '#FFF8' : '#0002').';
		}';
		$h .= '</style>';

		return $h;
	}

	public static function getFont(string $label): ?array {

		$font = array_filter(
			\Setting::get('website\customFonts'), fn($font) => $font['value'] === $label
		);

		return $font ? first($font) : NULL;

	}

	public static function getTitleFont(string $label): ?array {

		$font = array_filter(
			\Setting::get('website\customTitleFonts'), fn($font) => $font['value'] === $label
		);

		return $font ? first($font) : NULL;

	}

}
?>
