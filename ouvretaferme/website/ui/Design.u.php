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

		$text = Website::GET('customText', 'customText', $eWebsite['customText']);

		$fontGet = Website::GET('customFont', 'customFont', $eWebsite['customFont']);
		$font = first(array_filter(
			\Setting::get('website\customFonts'), fn($font) => $font['value'] === $fontGet
		));
		if(empty($font)) {
			$font = first(array_filter(
				\Setting::get('website\customFonts'), fn($font) => $font['label'] === 'PT Serif'
			));
		}

		$titleFontGet = Website::GET('customTitleFont', 'customTitleFont', $eWebsite['customTitleFont']);
		$titleFont = first(array_filter(
			\Setting::get('website\customTitleFonts'), fn($font) => $font['value'] === $titleFontGet
		));
		if(empty($titleFont)) {
			$titleFont = first(array_filter(
				\Setting::get('website\customTitleFonts'), fn($font) => $font['label'] === 'PT Serif'
			));
		}
		$h = '<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family='.$font['label'].'&family='.$titleFont['label'].'" rel="stylesheet">
';
		$h .= '<style>';
		$h .= ':root {
			--background: '.Website::GET('customBackground', 'customBackground', $eWebsite['customBackground']).';
			--primary: '.Website::GET('customColor', 'customColor', $eWebsite['customColor']).';
			--container-max-width: '.$eWebsite['customDesign']['maxWidth'].';
			--custom-font: '.$font['value'].';
			--custom-title-font: '.$titleFont['value'].';
			--border: '.($text === Website::BLACK ? '#8883' : '#8883').';
			--textColor: '.($text === Website::BLACK ? 'var(--text)' : 'white').';
			--linkColor: '.($text === Website::BLACK ? 'black' : 'white').';
			--muted: '.($text === Website::BLACK ? '#888' : '#CCC').';
			--transparent: '.($text === Website::BLACK ? '#FFF8' : '#0002').';
		}';
		$h .= '</style>';

		return $h;
	}

}
?>
