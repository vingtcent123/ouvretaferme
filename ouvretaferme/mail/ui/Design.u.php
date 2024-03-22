<?php
namespace mail;

class DesignUi {

	public static function format(\farm\Farm $eFarm, string $title, string $content): array {

		$html = \mail\DesignUi::getBanner($eFarm).nl2br($content);
		$text = decode(strip_tags($html));

		return [
			$title,
			$text,
			$html
		];

	}

	public static function getBanner(\farm\Farm $eFarm): string {

		$eFarm->expects(['banner']);

		$html = '';

		if($eFarm['banner'] !== NULL) {

			$url = (\LIME_ENV === 'dev') ? 'https://media.ouvretaferme.org/farm-banner/500x100/659ff8c45b5dfde6eacp.png?6' : (new \media\FarmBannerUi())->getUrlByElement($eFarm, 'm');

			$html .= '<div>';
				$html .= \Asset::image($url, attributes: ['width: 100%; max-width: 500px; height: auto; aspect-ratio: 5']);
			$html .= '</div>';
			$html .= '<br/>';

		}

		return $html;

	}

	public static function getButton($link, $content): string {

		$html = '<a href="'.$link.'" style="border-radius: 5px; padding: 10px; color: white; background-color: #505075; text-decoration: none">'.$content.'</a>';

		return $html;

	}

}
?>
