<?php
namespace mail;

class DesignUi {

	public static function format(\farm\Farm $eFarm, string $title, string $content, ?string $footer = NULL, bool $encapsulate = TRUE): array {

		$html = fn($value) => nl2br($value);
		$text = fn($value) => decode(strip_tags(str_replace(['</p>', '<br/>'], ["\n", "\n"], $value)));

		$contentHtml = $html($content);
		$contentText = $text($content);

		return [
			$title,
			($encapsulate ? \mail\DesignUi::encapsulateText($eFarm, $contentText) : $contentText).($footer ? "\n\n".$text($footer) : ''),
			($encapsulate ? \mail\DesignUi::encapsulateHtml($eFarm, $contentHtml) : $contentHtml).($footer ? $html($footer) : '')
		];

	}

	public static function encapsulateText(\farm\Farm $eFarm, string $content): string {

		$eFarm->expects(['emailBanner', 'emailFooter']);

		return $content;

	}

	public static function encapsulateHtml(\farm\Farm $eFarm, string $content): string {

		$eFarm->expects(['emailBanner', 'emailFooter']);

		$html = self::getBanner($eFarm);
		$html .= $content;
		$html .= self::getFooter($eFarm);

		return $html;

	}

	public static function getFooter(\farm\Farm $eFarm): string {

		$eFarm->expects(['emailFooter']);

		if($eFarm['emailFooter'] !== NULL) {
			return new \editor\ReadorFormatterUi()->getFromXml($eFarm['emailFooter']);
		} else {
			return '';
		}

	}

	public static function getBanner(\farm\Farm $eFarm): string {

		$eFarm->expects(['emailBanner']);

		$html = '';

		if($eFarm['emailBanner'] !== NULL) {

			$url = (\LIME_ENV === 'dev') ? 'https://media.ouvretaferme.org/farm-banner/500x100/659ff8c45b5dfde6eacp.png?6' : new \media\FarmBannerUi()->getUrlByElement($eFarm, 'm');

			$html .= '<div>';
				$html .= \Asset::image($url, attributes: ['style' => 'width: 100%; max-width: 500px; height: auto; aspect-ratio: 5']);
			$html .= '</div>';
			$html .= '<br/>';

		}

		return $html;

	}

	public static function getButton($link, $content): string {

		$html = '<a href="'.$link.'" style="border-radius: 5px; padding: 10px; color: white; background-color: #4a4a70; text-decoration: none; display: inline-block">'.$content.'</a>';

		return $html;

	}

}
?>
