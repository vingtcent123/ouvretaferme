<?php

interface Instruction {

	public function export(): array;

}

class Panel implements Instruction {

	public function __construct(
		public ?string $id = NULL,
		public ?string $back = NULL,
		public ?string $title = NULL,
		public ?string $documentTitle = NULL,
		public ?string $subTitle = NULL,
		public ?string $dialogOpen = NULL,
		public ?string $dialogClose = NULL,
		public ?string $body = NULL,
		public ?string $header = NULL,
		public ?string $footer = NULL,
		public string $close = 'passthrough',
		public bool $layer = TRUE,
		public array $attributes = []
	) {
	}

	public function export(): array {
		return ['js', [
			['pushPanel', [$this->asArray()]]
		]];
	}

	public function getDocumentTitle(): ?string {
		return $this->documentTitle ?? ($this->title ? strip_tags($this->title) : NULL);
	}

	protected function asArray(): array {

		$output = [
			'id' => $this->id ?? uniqid('panel-')
		];

		if($this->back !== NULL) {
			$output['back'] = $this->back;
		}

		if($this->title !== NULL) {
			$output['title'] = $this->title;
		}

		if($this->subTitle !== NULL) {
			$output['subTitle'] = $this->subTitle;
		}

		if($this->header !== NULL) {
			$output['header'] = $this->header;
		}

		if($this->body !== NULL) {
			$output['body'] = $this->body;
		}

		if($this->footer !== NULL) {
			$output['footer'] = $this->footer;
		}

		if($this->dialogOpen !== NULL) {
			$output['dialogOpen'] = $this->dialogOpen;
		}

		if($this->dialogClose !== NULL) {
			$output['dialogClose'] = $this->dialogClose;
		}

		$output['layer'] = $this->layer;

		$output['attributes'] = $this->attributes + [
			'data-close' => $this->close
		];

		return $output;
	}

}

class PackageInstruction implements Instruction {

	protected array $instructions = [];

	public function __construct(
		protected string $package
	) {

		if(Package::exists($package) === FALSE) {
			throw new Exception('Package \''.$package.'\' does not exist');
		}

	}

	public function __call($name, $arguments): self {
		$this->instructions[] = [$name, $arguments];
		return $this;
	}

	public function export(): array {
		Asset::js($this->package, 'instruction.js');
		return ['package', $this->package, $this->instructions];
	}

}

class JavascriptInstruction implements Instruction {

	protected array $instructions = [];

	/**
	 * Eval custom JS
	 */
	public function eval(string $code): self {
		$this->instructions[] = ['eval', [$code]];
		return $this;
	}

	/**
	 * Perform an ajax call
	 */
	public function ajax(string $method, string $url, array $body = []): self {
		$this->instructions[] = ['ajax', [strtoupper($method), $url, $body]];
		return $this;
	}

	/**
	 * Push a success value in the json array
	 *
	 * @param string $package
	 * @param string $fqn
	 */
	public function success(string $package, string $fqn, array $options = []): self {

		if(\Package::exists($package)) {

			$class = '\\'.$package.'\AlertUi';

			$message = $class::getSuccess($fqn, $options);

		} else {
			throw new Exception('Package "'.$package.'" does not exist');
		}

		$this->instructions[] = ['success', [$message]];

		return $this;

	}

	/**
	 * Push errors in the json array
	 */
	public function errors(FailWatch $fail): self {
		$this->instructions[] = ['errors', [$fail->format()]];
		return $this;
	}

	/**
	 * Push a new state in the history
	 */
	public function pushHistory(string $url): self {
		if(Route::getRequestedContext() !== 'reuse') {
			$this->instructions[] = ['pushHistory', [$url]];
		}
		return $this;
	}

	/**
	 * Replace state in the history
	 */
	public function replaceHistory(string $url): self {
		if(Route::getRequestedContext() !== 'reuse') {
			$this->instructions[] = ['replaceHistory', [$url]];
		}
		return $this;
	}

	/**
	 * Update key/value in the history
	 */
	public function updateHistory(string $key, string $value, string $state = 'replace'): self {
		if(Route::getRequestedContext() !== 'reuse') {
			$this->instructions[] = ['updateHistory', [$key, $value, $state]];
		}
		return $this;
	}

	/**
	 * Move in the browser history
	 */
	public function moveHistory(int $moves): self {
		if(Route::getRequestedContext() !== 'reuse') {
			$this->instructions[] = ['moveHistory', [$moves]];
		}
		return $this;
	}

	/**
	 * Show a panel
	 */
	public function showPanel(array $value): self {
		$this->instructions[] = ['showPanel', [$value]];
		return $this;
	}

	/**
	 * Hide a panel
	 */
	public function closePanel(string $selector): self {
		$this->instructions[] = ['closePanel', [$selector]];
		return $this;
	}

	/**
	 * Hide all panels
	 */
	public function purgeLayers(): self {
		$this->instructions[] = ['purgeLayers'];
		return $this;
	}

	/**
	 * Hide a dropdown
	 */
	public function closeDropdown(string $selector): self {
		$this->instructions[] = ['closeDropdown', [$selector]];
		return $this;
	}

	/**
	 * Hide all dropdowns
	 */
	public function closeDropdowns(): self {
		$this->instructions[] = ['closeDropdowns'];
		return $this;
	}

	public function export(): array {
		return ['js', $this->instructions];
	}

}

class QuerySelectorInstruction implements Instruction {

	protected array $instructions = [];

	protected string $mode = 'qs';

	public function __construct(
		private string $selector
	) {

	}

	/**
	 * Focus to something
	 */
	public function focus(): self {
		$this->instructions[] = ['focus'];
		return $this;
	}

	/**
	 * Toggle something
	 */
	public function toggle(): self {
		$this->instructions[] = ['toggle'];
		return $this;
	}

	/**
	 * Hide something
	 */
	public function hide(): self {
		$this->instructions[] = ['hide'];
		return $this;
	}

	/**
	 * Show something
	 */
	public function removeHide(): self {
		$this->instructions[] = ['removeHide'];
		return $this;
	}

	/**
	 * Toggle switch element
	 */
	public function toggleSwitch(): self {
		$this->instructions[] = ['toggleSwitch'];
		return $this;
	}

	/**
	 * Scroll to to something
	 */
	public function scrollTo(string $block = 'start', string $behavior = 'auto'): self {
		$this->instructions[] = ['scrollTo', [$block, $behavior]];
		return $this;
	}

	/**
	 * Change field value
	 */
	public function value(string|int|bool|float|null $value): self {
		$this->instructions[] = ['value', [$value]];
		return $this;
	}

	/**
	 * Replace outer HTML
	 */
	public function outerHtml(string $value): self {
		$this->instructions[] = ['outerHtml', [$value]];
		return $this;
	}

	/**
	 * Show a panel
	 */
	public function replacePanel(Panel $panel): self {
		$this->instructions[] = ['replacePanel', $panel];
		return $this;
	}

	/**
	 * Replace inner HTML
	 */
	public function innerHtml(string $value): self {
		$this->instructions[] = ['innerHtml', [$value]];
		return $this;
	}

	/**
	 * Replace inner HTML
	 */
	public function insertAdjacentHtml(string $mode, string $value): self {
		$this->instructions[] = ['insertAdjacentHtml', [$mode, $value]];
		return $this;
	}

	/**
	 * Remove HTML node
	 */
	public function remove(array $options = []): self {
		$this->instructions[] = ['remove', [$options]];
		return $this;
	}

	/**
	 * Set attributes
	 */
	public function setAttribute(string $name, string|int|bool|float|null $value): self {
		$this->instructions[] = ['setAttribute', [$name, $value]];
		return $this;
	}

	/**
	 * Remove attributes
	 */
	public function removeAttribute(string $name): self {
		$this->instructions[] = ['removeAttribute', [$name]];
		return $this;
	}

	/**
	 * Adds css
	 */
	public function style(array $styles): self {
		$this->instructions[] = ['style', [$styles]];
		return $this;
	}

	/**
	 * Adds a class
	 */
	public function addClass(string $value, array $options = []): self {
		$this->instructions[] = ['addClass', [$value, $options]];
		return $this;
	}

	/**
	 * Removes a class
	 */
	public function removeClass(string $value, array $options = []): self {
		$this->instructions[] = ['removeClass', [$value, $options]];
		return $this;
	}

	public function export(): array {
		return [$this->mode, $this->selector, $this->instructions];
	}

}

class QuerySelectorAllInstruction extends QuerySelectorInstruction {

	protected string $mode = 'qsa';

}
?>
