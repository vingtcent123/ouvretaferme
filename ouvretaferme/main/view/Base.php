<?php
/**
 * Structure commune pour OTF
 */
class BaseTemplate extends SmartTemplate {

	/**
	 * Lang
	 */
	public string $lang = 'fr';

	/**
	 * Template
	 */
	public string $template = 'default';

	/**
	 * Page title
	 */
	public ?string $title = NULL;

	/**
	 * Text for the description meta tag
	 */
	public ?string $metaDescription = NULL;

	/**
	 * Enable noindex meta tag
	 */
	public bool $metaNoindex = FALSE;

	/**
	 * Canonical URL
	 */
	public ?string $canonical = NULL;

	/**
	 * OpenGraph data
	 */
	public array $og = [];

	/**
	 * Base URL
	 */
	public ?string $base = NULL;

	/**
	 * Favicon
	 */
	public string $favicon;

	public function __construct() {

		parent::__construct();

		$this->base = \Lime::getProtocol().'://'.SERVER('HTTP_HOST');
		$this->favicon = \Asset::path('main', LIME_ENV === 'dev' ? 'favicon-dev.png' : 'favicon.png');

		\Asset::css('util', 'font-open-sans.css');

		$script = '';

		$success = GET('success');

		if($success) {
			$script .= 'Lime.Alert.showStaticSuccess("'.addcslashes(util\TextUi::success($success), '"').'");';
		}

		$error = GET('error', '?string');

		if($error) {

			$options = $this->data->errorOptions ?? [];
			$script .= 'Lime.Alert.showStaticError("'.addcslashes(util\TextUi::error($error, $options), '"').'");';

		}

		if($script) {
			\Asset::jsContent('<script>
				document.addEventListener("DOMContentLoaded", () => {
					'.$script.'
				})
			</script>');
		}

	}

	protected function buildHtml(string $stream): string {

		$nav = $this->getNav();
		$header = $this->getHeader();
		$main = $this->getMain($stream);
		$footer = $this->getFooter();

		$h = '<!DOCTYPE html>';
		$h .= '<html lang="'.$this->lang.'">';

		$h .= '<head>';
			$h .= $this->getHead();
			$h .= Asset::importHtml();
		$h .= '</head>';

		$h .= '<body data-template="'.$this->template.'" '.(OTF_DEMO ? 'data-demo' : '').' data-touch="no">';

			$h .= '<nav id="main-nav">'.$nav.'</nav>';
			$h .= '<header>'.$header.'</header>';
			$h .= '<main>'.$main.'</main>';
			$h .= '<footer>'.$footer.'</footer>';

		$h .= '</body>';

		$h .= '</html>';

		return $h;

	}

	protected function buildAjax(string $stream): AjaxTemplate {

		[$ogSiteName, $ogDescription, $ogTitle, $ogType, $ogUrl, $ogImg] = $this->getOg();

		$t = new AjaxTemplate();
		$t->copyInstructions($this);
		
		$t->qs('body')->setAttribute('data-template', $this->template);
		$t->qs('title')->innerHtml(encode($this->title));
		$t->qs('meta[name="description"]')->setAttribute('content', $this->metaDescription);
		$t->qs('meta[property="og:title"]')->setAttribute('content', $ogTitle);
		$t->qs('meta[property="og:description"]')->setAttribute('content', $ogDescription);
		$t->qs('meta[property="og:url"]')->setAttribute('content', $ogUrl);
		$t->qs('meta[property="og:img"]')->setAttribute('content', $ogImg);
		$t->qs('meta[property="og:site_name"]')->setAttribute('content', $ogSiteName);
		$t->qs('meta[property="og:type"]')->setAttribute('content', $ogType);
		$t->qs('nav')->innerHtml($this->getNav());
		$t->qs('main')->innerHtml($this->getMain($stream));
		$t->qs('footer')->innerHtml($this->getFooter());

		$this->buildAjaxHeader($t);
		$this->buildAjaxScroll($t);

		return $t;

	}

	protected function buildAjaxScroll(AjaxTemplate $t): void {

		if(server_exists('HTTP_X_REQUESTED_HISTORY') === FALSE) {
			$t->package('main')->resetScroll();
		}

	}

	protected function buildAjaxHeader(AjaxTemplate $t): void {
		$t->qs('header')->innerHtml($this->getHeader());
	}

	/**
	 * Build metadata for HTML document
	 * Must not be called within AJAX navigation query
	 *
	 * @param stdClass $data
	 * @return string
	 */
	protected function getHead(): string {

		[$ogSiteName, $ogDescription, $ogTitle, $ogType, $ogUrl, $ogImg] = $this->getOg();

		$this->canonical ??= LIME_REQUEST_PATH;

		$h = '';
		if($this->base !== NULL) {
			$h .= '<base href="'.$this->base.'">';
		}
		$h .= '<link rel="canonical" href="'.$this->canonical.'"/>';
		$h .= '<title>'.encode($this->title ?? '').'</title>';
		$h .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		$h .= '<meta name="description" content="'.$this->metaDescription.'" />';
		$h .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';

		if($this->favicon !== NULL) {
			$h .= '<link rel="icon" href="'.$this->favicon.'"/>';
			$h .= '<link rel="apple-touch-icon-precomposed" type="image/png" href="'.$this->favicon.'"/>';
		}


		if($this->metaNoindex) {
			$h .= '<meta name="robots" content="noindex"/>';
		}

		// Open graph data
		$h .= '<meta property="og:title" content="'.$ogTitle.'" />';
		$h .= '<meta property="og:site_name" content="'.$ogSiteName.'"/>';
		$h .= '<meta property="og:url" content="'.$ogUrl.'" />';
		$h .= '<meta property="og:image" content="'.$ogImg.'" />';
		$h .= '<meta property="og:type" content="'.$ogType.'" />';
		$h .= '<meta property="og:description" content="'.$ogDescription.'" />';
		$h .= '<meta property="og:locale" content="'.L::getLang().'" />';

		return $h;

	}

	protected function getOg(): array {

		$ogUrl = Lime::getUrl().LIME_REQUEST_PATH;
		$ogSiteName = \Lime::getName();
		$ogImg = $this->og['image'] ?? Lime::getUrl().'/'.\Asset::path('main', 'open-graph-default.jpg');

		$ogType = $this->og['type'] ?? 'website';
		$ogDescription = $this->og['description'] ?? $this->metaDescription;
		$ogTitle = $this->og['title'] ?? $this->title;

		return [$ogSiteName, $ogDescription, $ogTitle, $ogType, $ogUrl, $ogImg];

	}

	protected function getNav(): string {
		return '';
	}

	protected function getHeader(): string {
		return '';
	}

	protected function getMain(string $stream): string {
		return '';
	}

	protected function getFooter() {
		return '';
	}

	protected function getWarningObsoleteBrowser(): string {

		$alert = '<p>';
		$alert .= s("Attention, vous utilisez un vieux navigateur qui n'est pas compatible avec ce site. Merci d'utiliser un navigateur plus récent : <linkIE>Edge 16+</linkIE>, <linkFirefox>Firefox 54+</linkFirefox> ou <linkChrome>Google Chrome 58+</linkChrome>",
			['linkIE' => '<a href="http://window.microsoft.com/ie">', 'linkFirefox' => '<a href="http://www.getfirefox.com/">', 'linkChrome' => '<a href="http://www.google.com/chrome/">']);
		$alert .= '</p>';

		return $alert;

	}

}

?>
