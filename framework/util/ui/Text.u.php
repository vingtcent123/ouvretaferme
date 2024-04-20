<?php
namespace util;

/**
 * Common methods for text
 *
 */
class TextUi {

	/**
	 * Beautify a text
	 *
	 * @param string $text
	 * @return string
	 */
	public static function beautify(string $text): string {

		$text = str_replace([' ?', ' !'], ['&nbsp;?', '&nbsp;!'], $text);

		return $text;

	}

	/**
	 * 'th'ise a number
	 *
	 * @param int $number
	 * @return string
	 */
	public static function th(int $number): string {

		if($number === 1) {
			return s("{position}<sup>er</sup>", ['position' => $number]);
		} else if($number === 2) {
			return s("{position}<sup>ème</sup>", ['position' => $number]);
		} else if($number === 3) {
			return s("{position}<sup>ème</sup>", ['position' => $number]);
		} else {
			return s("{position}<sup>ème</sup>", ['position' => $number]);
		}

	}

	/**
	 *
	 */
	public static function tiny(string $text, bool $link): string {

		$matches = NULL;

		if(preg_match_all('/https?:\/\/((www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6})\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/si', $text, $matches) > 0) {

			foreach($matches[0] as $key => $url) {

				if(
					str_ends_with($url, '.') or
					str_ends_with($url, ',')
				) {
					$url = substr($url, 0, -1);
				}

				$shortUrl = $matches[1][$key];

				$text = str_replace(
					$url,
					$link ?
						'<a '.attr('href', $url).' target="_blank">'.$shortUrl.'</a> '.\Asset::icon('box-arrow-up-right').'' :
						'<span '.attr('data-href', $url).' class="color-secondary">'.$shortUrl.'</span> '.\Asset::icon('box-arrow-up-right'),
					$text
				);

			}

		}

		return $text;

	}

	/**
	 * Reduce a number
	 *
	 * @param int $number
	 * @return string
	 */
	public static function reduce(int $number): string {

		if($number < 1000) {
			return $number;
		}

		if($number < 10000) {
			return (floor($number / 100) / 10).'K';
		}

		if($number < 1000000) {
			return floor($number / 1000).'K';
		}

		if($number < 10000000) {
			return (floor($number / 100000) / 10).'M';
		}

		return floor($number / 1000000).'M';

	}

	/**
	 * Switch item
	 */
	public static function switch(array $attributes, bool $on = FALSE, ?string $textOn = NULL, ?string $textOff = NULL): string {

		\Asset::css('util', 'form.css');

		$h = '<a '.attrs($attributes).' class="field-switch '.($on ? 'field-switch-on' : 'field-switch-off').'">';
			$h .= '<div class="field-switch-circle"></div>';
			$h .= '<div class="field-switch-text">';
				$h .= '<div>'.$textOn.'</div>';
				$h .= '<div>'.$textOff.'</div>';
			$h .= '</div>';
		$h .= '</a>';

		return $h;

	}

	/**
	 * Display a success message
	 *
	 * @param string $message
	 */
	public static function success(string $message): string {

		$text = '';

		if(str_contains($message, ':')) {

			[$package, $fqn] = explode(':', $message, 2);

			if(\Package::exists($package)) {

				$class = '\\'.$package.'\AlertUi';
				$text = $class::getSuccess($fqn) ?? '';

			}

		}

		if($text) {
			return \Asset::icon('check').' '.$text;
		} else {
			return '';
		}

	}

	/**
	 * Display an error message
	 *
	 * @param string $message
	 * @param array $options Options to help display
	 */
	public static function error(string $message, array $options = []): string {

		$text = '';

		if(str_contains($message, ':')) {

			[$package, $fqn] = explode(':', $message, 2);

			if(\Package::exists($package)) {

				$class = '\\'.$package.'\AlertUi';
				$text = $class::getError($fqn, $options) ?? '';

			}

		}

		return $text;

	}


	/**
	 * Creates a numeric pagination for "?page=X"
	 *
	 * @param string $url can be a simple URL (string) or a string formatted as data-action="@action" for an ajax pagination
	 *
	 */
	public static function pagination(int $currentPage, float $nPage): string {

		$nPage = (int)ceil($nPage);

		if($nPage <= 1) {
			return '';
		}

		$url = HttpUi::setArgument(LIME_REQUEST, 'page', '{page}', FALSE);

		$range = 10;
		$rangeStart = max(0, $currentPage - $range / 2);
		$rangeEnd = min($nPage, $rangeStart + $range);

		$form = new FormUi();

		$h = '<div class="pagination-wrapper">';

			$h .= '<ul class="pagination">';

				if($currentPage > 0) {

					$h .= '<li>
						<a class="btn btn-outline-primary" '.self::getPaginationUrl($url, $url, 0).' data-page="0" aria-label="'.s("Début").'">
						<span>&laquo;</span>
						</a>
					</li>';

				}

				for($i = $rangeStart; $i < $rangeEnd; $i++) {
					$h .= '<li><a class="btn btn'.($i !== $currentPage ? '-outline' : '').'-primary" '.self::getPaginationUrl($url, $url, $i).' data-page="'.$i.'">'.($i + 1).'</a></li>';
				}

				if($currentPage !== $nPage - 1) {

					$h .= '<li>
						<a class="btn btn-outline-primary" '.self::getPaginationUrl($url, $url, $nPage - 1).' data-page="'.($nPage - 1).'" aria-label="'.s("Fin").'">
						<span>&raquo;</span>
						</a>
					</li>';

				}

			$h .= '</ul>';

			$h .= $form->open(NULL, [
				'onsubmit' => 'Lime.Pagination.form(\''.$url.'\', this);'
			]);
				$h .= '<div class="input-group">';
					$h .= $form->number("page", "", ["placeholder" => s("Aller à..."), 'min' => 1, 'max' => $nPage, 'class' => 'pagination-goto', 'style' => 'width: 75px', 'title' => s("Aller à la page...")]);
					$h .= $form->submit(s("Ok"));
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;
	}


	/**
	 * Creates a previous/next pagination for "?page=X"
	 *
	 */
	public static function paginationAround(int $currentPage, bool $hasMore): string {

		$url = HttpUi::setArgument(LIME_REQUEST, 'page', '{page}', FALSE);
		$urlZero = HttpUi::removeArgument(LIME_REQUEST, 'page');

		$hasBefore = ($currentPage > 0);

		if($hasBefore === FALSE and $hasMore === FALSE) {
			return '';
		}

		$h = '<div class="input-group">';

			if($hasBefore) {

				$h .= '<a class="btn btn-secondary" '.self::getPaginationUrl($urlZero, $url, $currentPage - 1).' data-page="'.($currentPage - 1).'">
					&laquo; '.s("Précédente").'
				</a>';

			}

			$h .= '<div class="input-group" style="background-color: #eee; display: flex; align-items: center; padding: 0 1rem; font-weight: bold">'.s("Page {value}", $currentPage + 1).'</div>';

			if($hasMore) {

				$h .=	'<a class="btn btn-secondary" '.self::getPaginationUrl($urlZero, $url, $currentPage + 1).' data-page="'.($currentPage + 1).'">
					'.s("Suivante").' &raquo;
				</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected static function getPaginationUrl(string $urlZero, string $url, $page) {

		if($page === 0) {
			$selectedUrl = $urlZero;
		} else {
			$selectedUrl = $url;
		}

		if(str_starts_with($selectedUrl, 'data-action')) {
			return $selectedUrl;
		} else {
			return 'href="'.str_replace('{page}', $page, $selectedUrl).'"';
		}

	}

	public static function pc(int|float $number, int $precision = 0): string {

		$value = ($precision !== NULL) ? round($number, $precision) : (float)$number;

		if($value === 0.0) {
			$value = '< '.(1 / pow(10, $precision));
		}

		return '<span style="white-space: nowrap">'.s("{value} %", $value).'</span>';

	}

	public static function money(float $number, string $currency = 'EUR', int $precision = 2): string {

		$numberFormatter = new \NumberFormatter(\L::getLang(), \NumberFormatter::CURRENCY);
		$numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $precision);

		return $numberFormatter->formatCurrency(round($number, $precision), $currency);

	}

	public static function number(int|float $number, ?int $precision = NULL): string {

		$precision ??= is_int($number) ? 0 : 2;

		$numberFormatter = new \NumberFormatter(\L::getLang(), \NumberFormatter::DECIMAL);
		$numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $precision);

		return $numberFormatter->format(round($number, $precision));

	}

	public static function csvNumber(int|float $number, ?int $precision = NULL): string {

		$precision ??= is_int($number) ? 0 : 2;

		return str_replace('.', ',', round($number, $precision));

	}

}
?>
