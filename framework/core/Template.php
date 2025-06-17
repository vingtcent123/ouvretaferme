<?php

abstract class Template {

	protected stdClass $data;

	public function __construct() {

		Asset::js('util', 'lime.js');
		Asset::js('util', 'ajax.js');

		\Asset::css('util', 'lime.css');

		if(LIME_ENV === 'dev') {

			Asset::js('dev', 'dev.js');
			Asset::css('dev', 'dev.css');

		}

	}

	/**
	 * Save data in template
	 */
	public function setData(stdClass $data): void {
		$this->data = $data;
	}

	/**
	 * Build template with given $callback
	 */
	public function build(Closure $callback): mixed {
		return NULL;
	}

	/**
	 * Export template data
	 */
	public function export(): mixed {
		return NULL;
	}

}



/**
 * Rendering view
 */
abstract class InstructionTemplate extends Template {

	/**
	 * Selectors
	 */
	protected array $instructions = [];

	public function getInstructions(): array {
		return $this->instructions;
	}

	public function formatInstructions(string $as = 'array'): mixed {

		$export = [];

		foreach($this->instructions as $selector) {
			$export[] = $selector->export();
		}

		switch($as) {

			case 'array' :
				return $export;

			case 'js' :

				if($export) {

					Asset::jsContent('<script>document.addEventListener("DOMContentLoaded", () => {
						new Ajax.Instruction('.json_encode(['__instructions' => $export]).', "html", this).render()
					})</script>');

				}

				return NULL;

			default:
				throw new Exception('Unexpected format');


		}

	}

	/**
	 * Show a panel
	 */
	public function copyInstructions(Template $object): self {
		$this->instructions = array_merge($this->instructions, $object->getInstructions());
		return $this;
	}

	/**
	 * Show a panel
	 */
	public function pushPanel(Panel $panel): self {
		$this->instructions[] = $panel;
		return $this;
	}

	/**
	 * Returns a package instruction
	 */
	public function package(string $package): PackageInstruction {
		$this->instructions[] = $qs = new PackageInstruction($package);
		return $qs;
	}

	/**
	 * Returns a javascript instruction
	 */
	public function js(): JavascriptInstruction {
		$this->instructions[] = $qs = new JavascriptInstruction();
		return $qs;
	}

	/**
	 * Returns a querySelector instruction to a ref
	 */
	public function ref(string $ref, string $cssSelector = '~='): QuerySelectorInstruction {

		if(in_array($cssSelector, ['~=', '=', '|=', '*=']) === FALSE) {
			throw new Exception('Invalid selector '.encode($cssSelector));
		}

		$this->instructions[] = $qs = new QuerySelectorAllInstruction('[data-ref'.$cssSelector.'"'.$ref.'"]');

		return $qs;

	}

	/**
	 * Returns a querySelector instruction
	 */
	public function qs(string $selector): QuerySelectorInstruction {
		$this->instructions[] = $qs = new QuerySelectorInstruction($selector);
		return $qs;
	}

	/**
	 * Returns a querySelectorAll instruction
	 */
	public function qsa(string $selector): QuerySelectorAllInstruction {
		$this->instructions[] = $qsa = new QuerySelectorAllInstruction($selector);
		return $qsa;
	}

}

class JsonTemplate extends InstructionTemplate {

	/**
	 * Output for Json pages
	 *
	 * @var array
	 */
	protected array $jsonOutput = [];

	/**
	 * Build Json data
	 */
	public function build(Closure $callback): mixed {

		$callback->call($this);

		return $this->exportOutput();
	}

	/**
	 * Export Json data
	 */
	public function export(): mixed {
		return $this->jsonOutput;
	}

	/**
	 * Push a new value in the json array
	 */
	public function push(string $key, int|float|string|bool|array|null $value): self {
		$this->jsonOutput[$key] = $value;
		return $this;
	}

	/**
	 * Push several values to the json array
	 */
	public function pushArray(array $values): self {
		$this->jsonOutput = $values + $this->jsonOutput;
		return $this;
	}

	/**
	 * Push an element into the json array
	 */
	public function pushElement(string $key, Element $eElement, $properties): self {

		$value = [];
		$this->fillProperty($eElement, $value, $properties);

		return $this->push($key, $value);
	}

	/**
	 * Push a collection into the json array
	 */
	public function pushCollection(string $key, Collection $cElement, $properties): self {

		$values = new stdClass();

		foreach($cElement as $i => $eElement) {

			$value = [];

			$this->fillProperty($eElement, $value, $properties);

			$values->$i = $value;

		}

		return $this->push($key, $values);
	}

	private function fillProperty(Element $source, array &$destination, $selection) {

		if(is_closure($selection)) {
			$destination = $selection($source);
		} else {

			if($source->empty()) {

				$destination = null;

			} else {

				foreach($selection as $key => $property) {

					if(is_closure($property)) {

						$destination[$key] = $property($source);

					} else if(is_array($property)) {

						if(isset($source[$key])) {

							if($source[$key] instanceof Collection) {

								$destination[$key] = new stdClass();

								foreach($source[$key] as $elementKey => $elementValue) {

									$destination[$key]->$elementKey = [];
									$this->fillProperty($elementValue, $destination[$key]->$elementKey, $property);

								}

							} else {
								$destination[$key] = [];
								$this->fillProperty($source[$key], $destination[$key], $property);
							}

						}

					} else {

						if(isset($source[$property])) {

							$destination[$property] = ($source[$property]->empty()) ? NULL : $source[$property];

						} else if(isset($source['id'])) { /* Code moustache à dégager dès que possible */
							$destination[$property] = NULL;
						}

					}
				}

				if(empty($destination)) {
					$destination = new StdClass();
				}

			}

		}

	}

}

class AjaxTemplate extends JsonTemplate {

	protected array $jsonOutput = [];

	public function build(Closure $callback): mixed {

		$callback->call($this);

		return $this
			->pushInstructions()
			->pushAssets()
			->getOutput();

	}

	public function getOutput(): mixed {
		return $this->jsonOutput;
	}

	public function pushInstructions(): self {
		$instructions = $this->formatInstructions();
		if($instructions) {
			$this->push('__instructions', $instructions);
		}
		return $this;
	}

	public function pushAssets(): self {
		$this->push('__assets', Asset::importJson());
		return $this;
	}

	/**
	 * Perform an ajax reload
	 */
	public function ajaxReload(bool $purgeLayers = TRUE): self {
		$this->jsonOutput['__redirect'][] = ['ajaxReload', NULL, FALSE, $purgeLayers];
		return $this;
	}

	/**
	 * Perform an ajax reload on the current layer
	 */
	public function ajaxReloadLayer(): self {
		$this->jsonOutput['__redirect'][] = ['ajaxReload', NULL, TRUE, FALSE];
		return $this;
	}

	/**
	 * Perform an ajax reload
	 */
	public function ajaxRedirect(string $url, bool $purgeLayers = FALSE): self {
		$this->jsonOutput['__redirect'][] = ['ajaxNavigation', $url, $purgeLayers];
		return $this;
	}

	/**
	 * Perform a navigator reload
	 */
	public function reload(string $queryStringAppend = '', string $mode = 'replace'): self {
		$this->jsonOutput['__redirect'][] = ['http', NULL, $queryStringAppend, $mode];
		return $this;
	}

	/**
	 * Perform a navigator redirection
	 */
	public function redirect(string $url, string $mode = 'assign'): self {
		$this->jsonOutput['__redirect'][] = ['http', $url, '', $mode];
		return $this;
	}

}

class HtmlTemplate extends Template {

	public function build(Closure $callback): mixed {

		ob_start();
		$callback->call($this);
		return ob_get_clean();

	}

}

/**
 * Controls AJAX navigation
 */
abstract class SmartTemplate extends InstructionTemplate {

	public function build(Closure $callback): mixed {

		ob_start();
		$callback->call($this);
		$stream = ob_get_clean();

		return match(Route::getRequestedWith()) {

			'ajax' => $this->buildAjax($stream)
				->push('__context', Route::getRequestedContext())
				->pushInstructions()
				->pushAssets()
				->getOutput(),

			default => $this->buildHtml($stream)

		};

	}

	/**
	 * Build full HTML page
	 */
	abstract protected function buildHtml(string $stream): string;

	/**
	 * Rebuild existing HTML page with the returned AjaxTemplate
	 */
	abstract protected function buildAjax(string $stream): AjaxTemplate;

}

/**
 * Controls AJAX navigation
 */
trait SmartPanelTemplate {

	public function build(Closure $callback): mixed {

		if(Route::getRequestMethod() !== 'GET') {
			throw new Exception('Panel navigation can only be used with GET request method');
		}

		ob_start();
		$panel = $callback->call($this);
		$stream = ob_get_clean();

		if($panel instanceof Panel === FALSE) {
			throw new Exception('Callback must return a Panel');
		}

		if($panel->layer === FALSE) {
			throw new Exception('Panel must be a layer');
		}

		$t = new AjaxTemplate();

		if($this instanceof InstructionTemplate) {
			$t->copyInstructions($this);
		}

		$t->pushPanel($panel);

		if($panel->getDocumentTitle()) {
			$t->qs('title')->innerHtml($panel->getDocumentTitle());
		}

		switch(Route::getRequestedWith()) {

			case 'ajax' :

				echo $stream; // Should be empty if no error

				return $t
					->push('__context', 'layer')
					->push('__panel', $panel->id)
					->pushInstructions()
					->pushAssets()
					->getOutput();

			default :

				$t->formatInstructions('js');

				if($stream !== '') {
					echo $stream; // Should be empty if no error
					exit;
				} else {
					return $this->buildHtml($stream);
				}


		}

	}

}

class BasicSmartTemplate extends SmartTemplate {

	public ?string $title = NULL;

	protected function buildHtml(string $stream): string {

		$h = '<!DOCTYPE html>';
		$h .= '<html>';

		$h .= '<head>';
			$h .= '<title>'.encode($this->title).'</title>';
			$h .= \Asset::importHtml();
		$h .= '</head>';

		$h .= '<body>';
			$h .= $stream;
		$h .= '</body>';

		$h .= '</html>';

		return $h;

	}

	protected function buildAjax(string $stream): AjaxTemplate {

		$t = new AjaxTemplate();
		$t->qs('title')->innerHtml(encode($this->title));
		$t->qs('body')->innerHtml($stream);

		return $t;

	}

}


class BasicPanelTemplate extends BasicSmartTemplate {

	use SmartPanelTemplate;

}
?>
