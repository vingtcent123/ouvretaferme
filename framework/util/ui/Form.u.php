<?php
namespace util;

/**
 * Handle forms
 */
class FormUi {

	protected ?string $lastFieldId = NULL;
	protected ?string $lastFieldName = NULL;
	protected ?string $nextWrapper = NULL;
	protected ?string $formId = NULL;
	protected ?bool $formDraft = NULL;

	/**
	 * Fields and button size
	 *
	 * @var string Can be 'sm', 'lg', ...
	 */
	protected ?string $size = NULL;

	protected array $options = [];

	protected ?string $id = NULL;

	/**
	 * Build a new form
	 *
	 * Options are:
	 * - style: form style from options (basic, inline, horizontal)
	 * -
	 *
	 * @param array $options Boostrap options
	 */
	public function __construct(array $options = []) {

		$this->options = $options + [
			'columns' => 2
		];

		if($this->options['columns'] > 1) {

			$this->options += [
				'firstColumnSize' => 33,
				'columnBreak' => 'xs',
			];

		}

		\Asset::css('util', 'form.css');
		\Asset::js('util', 'form.js');

	}

	/**
	 * Open a new form with POST method
	 *
	 * @param string $id Id
	 * @param string $attributes Additional attributes for <form> tag (+ groupLabelClass, groupDivClass)
	 */
	public function open(string $id = NULL, array $attributes = []): string {

		if(isset($attributes['binary'])) {
			$attributes['enctype'] = 'multipart/form-data';
			unset($attributes['binary']);
		}

		$attributes['action'] ??= 'javascript:;';
		$attributes['method'] ??= 'post';

		if($id) {
			$attributes['id'] = (string)$id;
			$this->formDraft = isset($attributes['data-draft']);
		} else {
			$this->formDraft = NULL;
		}

		if(isset($attributes['id'])) {
			$this->id = (string)$attributes['id'];
		}

		if($this->options['columns'] === 2) {
			$attributes['class'] = 'form-columns form-columns-'.$this->options['firstColumnSize'].' form-columns-'.$this->options['columnBreak'].' '.($attributes['class'] ?? '');
		}

		if(in_array('novalidate', $attributes, TRUE)) {
			$attribute = ' novalidate';
			unset($attributes[array_search('novalidate', $attributes)]);
		} else {
			$attribute = '';
		}

		return '<form '.attrs($attributes).$attribute.'>';

	}

	public function getId(): ?string {
		return $this->id;
	}

	public function openUrl(string $url, array $attributes = []): string {

		$attributes['action'] = $url;

		return $this->open(NULL, $attributes);

	}

	public function openAjax(string $url, array $attributes = []): string {

		$attributes['data-ajax-form'] = $url;

		return $this->open(NULL, $attributes);

	}

	/**
	 * Close a form
	 *
	 */
	public function close(): string {

		$h = '</form>';

		if($this->formDraft) {

			$h .= '<script type="text/javascript">';
				$h .= 'Draft.initDrafts(qs("#'.$this->formId.'"));';
			$h .= '</script>';

		}

		return $h;

	}

	/**
	 * Change fields size in the form
	 *
	 * @param string $size
	 */
	public function setSize(string $size): string {
		$this->size = $size;
	}

	/**
	 * Get size class for a field
	 *
	 * @param string $field 'btn' or 'input'
	 * @return string
	 */
	protected function getSize(string $field): string {
		if($this->size) {
			return $field.'-'.$this->size;
		} else {
			return '';
		}
	}

	/**
	 * Create a form group (according to options definition) from a label and form fields
	 *
	 * @param string $label
	 * @param string $fields
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function group(?string $label = NULL, ?string $content = NULL, array $attributes = [], bool $nested = FALSE): string {

		$wrapper = $attributes['wrapper'] ?? $this->nextWrapper;

		unset($attributes['wrapper']);
		$this->nextWrapper = NULL;

		if($wrapper !== NULL and substr($wrapper, -2) === '[]') {
			$wrapper = substr($wrapper, 0, -2);
		}

		$class = $attributes['class'] ?? '';
		unset($attributes['class']);

		$h = '<div data-wrapper="'.$wrapper.'" class="form-group '.($nested ? 'form-group-nested' : '').' '.$class.'" '.attrs($attributes).'>';

		if($attributes['for'] ?? TRUE) {
			$for = 'for="'.$this->lastFieldId.'"';
		} else {
			$for = '';
		}

		if($this->options['columns'] === 2) {

			$h .= '<label '.$for.' class="form-control-label">'.$label.'</label>';
			$h .= '<div class="form-control-field">'.$content.'</div>';

		} else {

			if($label !== NULL) {
				$h .= '<label '.$for.' class="form-control-label">'.$label.'</label>';
			}
			$h .= ' <div>'.$content.'</div>';

		}

		$h .= '</div>';

		return $h;

	}

	public function quick(\Element $e, string $property): string {

		$e->expects(['id', $property]);

		$form = new FormUi();

		$uiClass = '\\'.$e->getModule().'Ui';

		if(class_exists($uiClass)) {
			$label = $uiClass::p($property)->label;
		} else {
			$label = NULL;
		}

		$h = $form->openAjax('/@module/'.str_replace('\\', '/', $e->getModule()).'/doQuick');
			$h .= $form->hidden('id', $e['id']);
			$h .= $form->hidden('property', $property);
			$h .= '<div class="util-quick-form">';
				if($label !== NULL) {
					$h .= '<h4>'.$label.'</h4>';
				}
				$h .= '<div>';
					$h .= $form->dynamicField($e, $property);
				$h .= '</div>';
				$h .= $form->submit(s("Enregistrer"));
			$h . '</div>';
		$h .= $form->close();

		return $h;

	}

	/**
	 * Create group from an element and given properties
	 */
	public function dynamicGroups(\Element $e, array $properties, array $dsCallback = []): string {

		$h = '';

		foreach($properties as $property) {
			$h .= $this->dynamicGroup($e, $property, $dsCallback[$property] ?? NULL);
		}

		return $h;

	}

	/**
	 * Create group from an element and given property
	 */
	public function dynamicGroup(\Element $e, string $property, ?\Closure $dCallback = NULL): string {

		if($e instanceof \Element === FALSE) {
			throw new \Exception('No element given');
		}

		$asterisk = str_ends_with($property, '*');

		if($asterisk) {
			$property = substr($property, 0, -1);
		}

		$d = NULL;
		$h = $this->dynamicField($e, $property, $dCallback, $d);

		$field = $attributes['field'] ?? $d->field;

		switch($field) {

			case NULL :
				return '';

			case 'hidden' :
				return $h;

			default :

				if($d->groupLabel === FALSE) {
					$label = '';
				} else {
					$label = $d->labelBefore;
					$label .= $d->label ?? '<i>'.$property.'</i>';
					if($asterisk or $d->asterisk) {
						$label .= $this->asterisk();
					}
					$label .= $d->labelAfter;
				}

				if($d->group) {
					$groupAttributes = is_closure($d->group) ? call_user_func($d->group, $e) : $d->group;
				} else {
					$groupAttributes = [];
				}

				return $this->group($label, $h, $groupAttributes);


		}

	}

	/**
	 * Create a dynamic field
	 */
	public function dynamicField(\Element $e, string $property, ?\Closure $dCallback = NULL, \PropertyDescriber &$d = NULL): string {

		$classUi = $e->getModule().'Ui';
		$ui = new $classUi();

		$fullProperty = $property;

		if(preg_match('/^([a-z0-9]+)\[([^\]]*)\]/si', $property, $matches) > 0) {
			[, $property] = $matches;
		}

		$d = $ui->p($property);
		$d->name ??= $fullProperty;

		if($dCallback !== NULL) {
			$dCallback($d);
		}

		$field = $d->field;

		if(is_closure($d->attributes)) {
			$d->attributes = call_user_func($d->attributes, $this, $e, $property, $field);
		}

		if($field === NULL) {
			return '';
		}

		$d->last = '';

		$dynamicField = $this->createDynamicField($d, $e, $property);

		if($d->load) {
			$load = $d->load;
			$load();
		}

		$h = '';

		if($d->before) {
			$h .= is_closure($d->before) ? call_user_func($d->before, $this, $e, $property, $field) : $d->before;
		}

		if($d->prepend !== NULL or $d->append !== NULL) {

			$input = '';

			if($d->prepend) {
				foreach((array)$d->prepend as $prepend) {
					$input .= is_closure($prepend) ? call_user_func($prepend, $this, $e, $property, $field, $d->attributes) : $this->addon($prepend);
				}
			}

			$input .= $dynamicField;

			if($d->append) {
				foreach((array)$d->append as $append) {
					$input .= is_closure($append) ? call_user_func($append, $this, $e, $property, $field, $d->attributes) : $this->addon($append);
				}
			}

			if($d->inputGroup) {
				$inputGroupAttributes = is_closure($d->inputGroup) ? call_user_func($d->inputGroup, $e) : $d->inputGroup;
			} else {
				$inputGroupAttributes = [];
			}

			$h .= $this->inputGroup($input, $inputGroupAttributes);

		} else {
			$h .= $dynamicField;
		}

		if($d->after) {
			$h .= is_closure($d->after) ? call_user_func($d->after, $this, $e, $property, $field, $d->attributes) : $d->after;
		}

		$h .= $d->last;

		return $h;

	}

	protected function createDynamicField(\PropertyDescriber $d, \Element $e, string $property): string {

		$field = $d->field;
		$attributes = $d->attributes;

		$m = ($e->getModule())::model();

		if(is_closure($field)) {
			return $field->call($d, $this, $e, $property, $d);
		}

		$type = $d->type;

		if(is_closure($d->default)) {
			$default = ($d->default)($e, $property);
		} else {
			if(
				$e->model()->hasProperty($property) and
				$m->isPropertyNull($property)
			) {
				$default = $e->offsetExists($property) ? $e[$property] : ($d->default ?? NULL);
			} else {
				$default = $e[$property] ?? $d->default ?? NULL;
			}
		}

		if(is_closure($d->values)) {
			$values = ($d->values)($e);
		} else {
			$values = $d->values;
		}

		if(is_closure($d->placeholder)) {
			$placeholder = ($d->placeholder)($e);
		} else {
			$placeholder = $d->placeholder;
		}

		if($placeholder) {
			$attributes += [
				'placeholder' => $placeholder
			];
		}

		foreach($attributes as $key => $value) {
			if(str_starts_with($key, 'callback') === FALSE and is_closure($value)) {
				$attributes[$key] = call_user_func($value, $this, $e, $property, $attributes);
			}
		}

		$name = $d->name ?? $property;

		switch($field) {

			case 'select' :
				return $this->select($name, $values, $default, $attributes);

			case 'range' :
				return $this->range($name, $d->from, $d->to, $d->step ?? 1, $default, $attributes);

			case 'rangeSelect' :
				return $this->rangeSelect($name, $d->from, $d->to, $d->step ?? 1, $default, $attributes);

			case 'radio' :
				return $this->radios($name, $values ?? $attributes['values'], $default, $attributes);

			case 'textarea' :
			case 'yesNo' :
			case 'switch' :
			case 'weekNumber' :
			case 'week' :
			case 'month' :
			case 'time' :
			case 'date' :
			case 'email' :
			case 'hidden' :
				return $this->{$field}($name, $default, $attributes);

			case 'autocomplete' :

				$url = $d->autocompleteUrl ?? throw new \Exception('Missing $d->autocompleteUrl for autocomplete field');

				if(is_closure($d->autocompleteBody)) {
					$autocompleteBody = $d->autocompleteBody->call($this, $this, $e);
				} else {
					$autocompleteBody = $d->autocompleteBody ?? [];
				}

				if(is_closure($d->autocompleteDefault)) {
					$autocompleteDefault = $d->autocompleteDefault->call($this, $e);
				} else {
					$autocompleteDefault = $d->autocompleteDefault;
				}

				$default = [];

				if($d->multiple ?? FALSE) {

					$name .= '[]';

					if($autocompleteDefault instanceof \Collection) {
						$default = $autocompleteDefault->makeArray(fn($e) => ($d->autocompleteResults)($e));
					}

				} else {

					if($autocompleteDefault !== NULL) {

						if(
							$autocompleteDefault instanceof \Element === FALSE or
							$autocompleteDefault->notEmpty()
						) {
							$default[] = ($d->autocompleteResults)($autocompleteDefault);
						}

					} else {

						if(
							$e->offsetExists($property) and (
								($e[$property] instanceof \Element and $e[$property]->notEmpty()) or
								($e[$property] instanceof \Element === FALSE and $e[$property] !== NULL)
							)
						) {
							$default[] = ($d->autocompleteResults)($e[$property]);
						}

					}

				}

				if(is_closure($d->autocompleteDispatch)) {
					$dispatch = $d->autocompleteDispatch->call($this, $this, $e);
				} else {
					$dispatch = $d->autocompleteDispatch ?? NULL;
				}

				[
					'query' => $query,
					'results' => $results
				] = $this->autocomplete($name, $url, $autocompleteBody, $dispatch, $default, $attributes);

				$d->last = $results;

				return $query;


		}

		if($type === NULL) {
			throw new \Exception('No type found for property \''.$property.'\'');
		}

		if($type === 'bool') {
			return $this->checkbox($name, 1, ['checked' => $default] + $attributes);
		} else if(strpos($type, 'element') !== FALSE and $d->module !== NULL) {

			if($values instanceof \Collection or is_array($values)) {
				return $this->select($name, $values, ($e[$property] ?? new \Element()), $attributes);
			} else {
				throw new \Exception('Missing collection or array for property \''.$property.'\'');
			}

		} else if($type === 'text8' or $type === 'textFixed') {

			return $this->text($name, $default, $attributes);

		} else if($type === 'fqn') {

			return $this->fqn($name, $default, $attributes);

		} else if(strpos($type, 'text') === 0) {

			return $this->textarea($name, $default, $attributes);

		} else if(strpos($type, 'editor') === 0) {

			return $this->editor($name, ($default), $d->options ?? [], $attributes);

		} else if($type === 'email') {

			return $this->email($name, $default, $attributes);

		} else if($type === 'url') {

			return $this->url($name, $default, $attributes);

		} else if(strpos($type, 'int') === 0) {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			return $this->number($name, $default, $attributes);

		} else if(strpos($type, 'float') === 0 or $type === 'decimal') {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			$attributes += [
				'step' => '0.01'
			];

			return $this->number($name, $default, $attributes);

		} else if(
			$type === 'date' or
			$type === 'datetime'
		) {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			return match($type) {
				'date' => $this->date($name, $default, $attributes),
				'datetime' => $this->datetime($name, $default, $attributes)
			};

		} else if($type === 'time') {
			return $this->time($name, $default, $attributes);
		} else if($type === 'week') {
			return $this->week($name, $default, $attributes);
		} else if($type === 'month') {
			return $this->month($name, $default, $attributes);
		} else if($type === 'set') {

			$h = '';

			foreach($d->set as $set) {

				$checked = (isset($e[$property]) and $e[$property]->value($set));
				$label = $values[$set] ?? '<i>'.$set.'</i>';

				$h .= $this->checkbox($name.'[]', $set, [
					'checked' => $checked,
					'callbackLabel' => fn($input) => $input.' '.$label
				]);

			}

			return $h;

		} else if(strpos($type, 'enum') === 0) {

			$list = [];
			foreach($d->enum as $enum) {
				$list[$enum] = $values[$enum] ?? '<i>'.$enum.'</i>';
			}
			if($m->hasProperty($property) and $m->isPropertyNull($property) === FALSE) {
				$attributes += ['mandatory' => TRUE];
			}

			return $this->radios($name, $list, $default, $attributes);

		} else if($type === 'color') {
			return $this->color($name, $default, $attributes);
		} else {
			throw new \Exception('Type \''.$type.'\' of property \''.$property.'\' not handled');
		}

	}

	public function addressGroup(string $label, ?string $prefix, \Element $e): string {

		$field = fn($name) => ($prefix === NULL) ? $name : $prefix.ucfirst($name);

		return $this->group(
			$label,
			'<div class="form-control-block">'.$this->address($prefix, $e).'</div>',
			['wrapper' => $field('address').' '.$field('street1').' '.$field('street2').' '.$field('postcode').' '.$field('city')]
		);

	}

	/**
	 * Create a input group (according to options definition)
	 * It is usefull for inline checkbox/radio and for input-group-addon style
	 *
	 * @param string $fields
	 * @param array $attributes
	 * @return string
	 */
	public function inputGroup(string $fields, array $attributes = []): string {

		$h = '<div class="input-group';

		if(isset($attributes['class'])) {
			$h .= ' '.$attributes['class'];
			unset($attributes['class']);
		}

		$h .= '" '.attrs($attributes).'>'.$fields.'</div>';

		return $h;

	}

	/**
	 * Create an asterisk
	 *
	 */
	public function asterisk(): string {
		return '<div class="form-asterisk" title="'.s("Champ obligatoire").'">'.\Asset::icon('asterisk').'</div>';
	}

	/**
	 * Create an asterisk
	 *
	 */
	public function asteriskInfo(?int $maxSeniority = 10): string {

		if($maxSeniority !== NULL) {

			try {

				$eUser = \Setting::get('main\onlineUser');

				if(
					$eUser->notEmpty() and
					$eUser['seniority'] > $maxSeniority
				) {
					return '';
				}

			} catch(\Exception) {

			}

		}

		$h = '<div class="form-asterisk-info">';
			$h .= s("Les champs marqués {value} sont obligatoires", $this->asterisk());
		$h .= '</div>';

		return $h;

	}

	/**
	 * Create a input text
	 *
	 */
	public function addon(string $content, array $attributes = []): string {
		$h = '<span class="input-group-addon" '.attrs($attributes).'>'.$content.'</span>';
		return $h;
	}

	/**
	 * Display help for a field
	 *
	 * @param string $content
	 * @return string
	 */
	public function help(string $content): string {
		return '<span class="color-muted">'.$content.'</span>';
	}

	/**
	 * Create a label for the last field
	 *
	 * @param string $text Label text
	 */
	public function label(string $text): string {

		if($this->lastFieldId === NULL) {
			return $text;
		} else {
			return '<label for="'.$this->lastFieldId.'">'.$text.'</label>';
		}

	}

	/**
	 * Display several checkboxes
	 *
	 */
	public function checkboxes(?string $name, array|\ArrayIterator $values, array|\ArrayIterator $selectedValues = NULL, array $attributes = []): string {

		// Default attributes
		$attributes += [
			'columns' => 1,
			'all' => TRUE,
			'callbackCheckboxContent' => NULL,
			'callbackCheckboxAttributes' => function() {
				return [];
			}
		];

		$columns = $attributes['columns'];
		unset($attributes['columns']);

		$callbackCheckboxContent = $attributes['callbackCheckboxContent'];
		unset($attributes['callbackCheckboxContent']);

		$callbackCheckboxAttributes = $attributes['callbackCheckboxAttributes'];
		unset($attributes['callbackCheckboxAttributes']);

		$this->setDefaultAttributes($attributes, $name);

		$formatSelectedValues = $this->getSelectedValue($selectedValues, TRUE);

		$h = '<div class="form-control field-radio-group field-radio-group-'.$columns.'" '.attrs($attributes).'>';

			if($attributes['all']) {
				$h .= '<div class="field-radio-group-action">';
					$h .= '<a '.attr('onclick', 'CheckboxField.check(this, "'.encode($name).'", true)').'>'.s("Tout cocher").'</a>';
					$h .= ' / ';
					$h .= '<a '.attr('onclick', 'CheckboxField.check(this, "'.encode($name).'", false)').'>'.s("Tout décocher").'</a>';
				$h .= '</div>';
			}

			foreach($values as $key => $option) {

				[$optionValue, $optionContent] = $this->getOptionValue($key, $option);

				if($callbackCheckboxContent !== NULL) {
					$label = call_user_func($callbackCheckboxContent, $option);
				} else {
					$label = $optionContent;
				}

				$checked = in_array($optionValue, $formatSelectedValues, TRUE);

				$h .= '<label>'.$this->inputCheckbox($name, $optionValue, ['checked' => $checked] + call_user_func($callbackCheckboxAttributes, $option, $key)).' '.$label.'</label>';

			}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a checkbox
	 *
	 */
	public function checkbox(string $name, mixed $value = '1', array $attributes = []): string {

		if(isset($attributes['callbackLabel'])) {
			$addon = $attributes['callbackLabel'];
			unset($attributes['callbackLabel']);
		} else {
			$addon = fn($input) => $input;
		}

		$input = $this->inputCheckbox($name, $value, $attributes);

		$h = '<label class="field-checkbox">';
			$h .= $addon($input);
		$h .= '</label>';

		return $h;

	}

	public function inputCheckbox(?string $name = NULL, mixed $value = '1', array $attributes = []): string {

		if(isset($attributes['checked'])) {

			if($attributes['checked']) {
				$attributes['checked'] = 'checked';
			} else {
				unset($attributes['checked']);
			}
		}

		return $this->input('checkbox', $name, $value, $attributes);

	}

	/**
	 * Display a radio button
	 *
	 */
	public function radio(string $name, $value, string $label, mixed $selectedValue = NULL, array $attributes = []): string {
		return '<div class="radio"><label>'.$this->inputRadio($name, $value, $label, $selectedValue, $attributes).'</label></div>';
	}

	/**
	 * Display several radio fields
	 *
	 */
	public function radios(?string $name, array|\ArrayIterator $values, mixed $selectedValue = NULL, array $attributes = []): string {

		// Default attributes
		$attributes += [
			'columns' => 1,
			'callbackRadioContent' => NULL,
			'callbackRadioAttributes' => function() {
				return [];
			}
		];

		$columns = $attributes['columns'];
		unset($attributes['columns']);

		$callbackRadioContent = $attributes['callbackRadioContent'];
		unset($attributes['callbackRadioContent']);

		$callbackRadioAttributes = $attributes['callbackRadioAttributes'];
		unset($attributes['callbackRadioAttributes']);

		$this->setDefaultAttributes($attributes);

		$h = '<div class="form-control field-radio-group field-radio-group-'.$columns.'" '.attrs($attributes).' '.attr('data-field', $name).'>';

			if(empty($attributes['mandatory'])) {

				$h .= '<label>'.$this->inputRadio($name, '', $attributes['placeholder'] ?? s("Aucun"), $selectedValue, call_user_func($callbackRadioAttributes, NULL, NULL)).'</label>';
				unset($attributes['placeholder']);

			}
			unset($attributes['mandatory']);


			foreach($values as $key => $option) {

				[$optionValue, $optionContent, $optionAttributes] = $this->getOptionValue($key, $option);

				if($callbackRadioContent !== NULL) {
					$label = call_user_func($callbackRadioContent, $option);
				} else {
					$label = $optionContent;
				}

				$h .= '<label>'.$this->inputRadio($name, $optionValue, $label, $selectedValue, call_user_func($callbackRadioAttributes, $option, $key)).'</label>';

			}

		$h .= '</div>';

		return $h;

	}

	public function yesNo(?string $name, mixed $selectedValue = NULL, array $attributes = []): string {

		$attributes += [
			'mandatory' => TRUE,
			'columns' => 2,
			'yes' => s("oui"),
			'no' => s("non")
		];

		$values = [
			[
				'value' => 1,
				'label' => $attributes['yes']
			],
			[
				'value' => 0,
				'label' => $attributes['no']
			],
		];

		unset($attributes['yes'], $attributes['no']);

		return $this->radios($name, $values, $selectedValue, $attributes);

	}

	public function switch(?string $name, mixed $selectedValue = NULL, array $attributes = []): string {

		$h = '';

		if(isset($attributes['onchange'])) {
			$onclick = 'SwitchField.change(this, (input) => '.$attributes['onchange'].')';
		} else {
			$onclick = 'SwitchField.change(this)';
		}

		$h .= '<div class="field-switch '.($selectedValue ? 'field-switch-on' : 'field-switch-off').'" '.attr('onclick', $onclick).'>';
			$h .= '<div class="field-switch-circle"></div>';
			if(isset($attributes['labelOn']) or isset($attributes['labelOff'])) {
				$h .= '<div class="field-switch-text">';
					$h .= '<div>'.($attributes['labelOn'] ?? '').'</div>';
					$h .= '<div>'.($attributes['labelOff'] ?? '').'</div>';
				$h .= '</div>';
			}
			$h .= $this->inputCheckbox($name, TRUE, [
				'checked' => (bool)$selectedValue,
			]);
		$h .= '</div>';

		return $h;

	}

	public function inputRadio(?string $name, int|string|float|null $value, string $label = NULL, mixed $selectedValue = NULL, array $attributes = []): string {

		if(array_key_exists('id', $attributes) === FALSE) {
			$attributes['id'] = $name;
			if(is_string($value)) {
				$attributes['id'] .= ctype_alnum($value) ? ucfirst($value) : crc32($value);
			} else {
				$attributes['id'] .= $value;
			}
		}

		$selectedValue = $this->getInputValue($selectedValue);

		if(is_bool($selectedValue)) {
			$selectedValue = (int)$selectedValue;
		}

		$selectedValue = (string)$selectedValue;

		if(isset($attributes['checked'])) {

			if($attributes['checked']) {
				$attributes['checked'] = 'checked';
			} else {
				unset($attributes['checked']);
			}
		} else if((string)$value === $selectedValue) {
			$attributes['checked'] = 'checked';
		}

		$h = $this->input('radio', $name, $value, $attributes);

		if($label !== NULL) {
			$h .= ' <span>'.$label.'</span>';
		}

		return $h;

	}

	/**
	 * Display a date field
	 *
	 * @param string $name
	 * @param string $selection Default date
	 * @param array $attributes => 'callback' method called for each changement
	 * @return string
	 */
	public function date(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('date', $name, $value, $attributes);

	}

	/**
	 * Display a datetime field
	 *
	 * @param string $name
	 * @param string $selection Default datetime
	 * @param array $attributes => 'callback' method called for each changement
	 * @return string
	 */
	public function datetime(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		if($value !== NULL) {
			$value = date('Y-m-d\TH:i', strtotime($value));
		}

		if(isset($attributes['min'])) {
			$attributes['min'] = date('Y-m-d\TH:i', strtotime($attributes['min']));
		}

		if(isset($attributes['max'])) {
			$attributes['max'] = date('Y-m-d\TH:i', strtotime($attributes['max']));
		}

		return $this->input('datetime-local', $name, $value, $attributes);

	}

	/**
	 * Display a date field
	 *
	 * @param string $name
	 * @param string $selection Default date
	 * @param array $attributes => 'callback' method called for each changement
	 * @return string
	 */
	public function month(string $name, ?string $value = NULL, array $attributes = []): string {

		if(isset($attributes['display'])) {
			$display = $attributes['display'];
			unset($attributes['display']);
		} else {
			$display = 'auto';
		}

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$wrapperId = uniqid('field-month-wrapper-');

		$h = '<div class="field-month-wrapper" id="'.$wrapperId.'">';

			if($display === 'auto' or $display === 'fallback') {

				$h .= '<div class="field-month-fallback input-group hide">';
					$h .= $this->select($name.'Month', DateUi::months(), $value ? date_month($value) : NULL, [
						'placeholder' => s("Mois"),
						'data-fallback' => 'monthNumber',
					]).' ';
					$h .= $this->number($name.'Year', $value ? date_year($value) : NULL, [
						'data-fallback' => 'year',
						'placeholder' => s("Année")
					]);
				$h .= '</div>';

			}

			$h .= '<div class="field-month-native hide">';
				$h .= $this->input('month', $name, $value, $attributes);
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<script>';
			$h .= 'DateField.startFieldMonth(\'#'.$wrapperId.'\', \''.$display.'\')';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Display a week field
	 */
	public function week(string $name, string|int|null $value = NULL, array $attributes = []): string {

		$wrapperId = uniqid('field-week-wrapper-');

		if(is_int($value)) {
			$weekNumber = NULL;
			$weekYear = $value;
		} else {
			$weekNumber = $value ? week_number($value) : NULL;
			$weekYear = $value ? week_year($value) : NULL;
		}

		if(isset($attributes['withYear'])) {
			$withYear = $attributes['withYear'];
			unset($attributes['withYear']);
		} else {
			$withYear = TRUE;
		}

		$h = '<div class="field-week-wrapper" id="'.$wrapperId.'" data-with-year="'.($withYear ? 'true' : 'false').'">';

			$h .= '<div class="input-group">';
				$h .= '<span class="input-group-addon">'.s("Semaine").'</span>';

				$id = uniqid('field-week-');

				$h .= $this->getWeekNumberField($name.'Week', $weekNumber, [
					'data-dropdown-id' => $wrapperId.'-week',
					'onfocus' => 'DateField.blurFieldWeek(this)',
					'onclick' => 'DateField.openWeekSelector(this)',
					'oninput' => 'DateField.onInputFieldWeek("#'.$wrapperId.'")',
					'data-fallback' => 'weekNumber',
					'data-week-number' => '#'.$id,
					'data-year-selector' => '#'.$wrapperId.' [name="'.$name.'Year"]',
					'min' => 1,
					'max' => 53,
				] + $attributes);
				$h .= '<span class="input-group-addon field-week-preview" id="'.$id.'">'.($weekNumber ? DateUi::weekToDays(date('Y').'-W'.sprintf('%02d', $weekNumber), withYear: FALSE) : '').'</span>';

				if($withYear === TRUE) {
					$h .= '<span class="input-group-addon">'.s("Année").'</span>';
					$h .= $this->number($name.'Year', $weekYear, [
							'data-fallback' => 'year',
							'onclick' => 'DateField.openWeekSelector(this)',
							'oninput' => 'DateField.onInputFieldWeek("#'.$wrapperId.'")',
							'data-week-selector' => '#'.$wrapperId.' [name="'.$name.'Week"]',
						] + $attributes);
				}
			$h .= '</div>';

			$h .= '<div data-dropdown-id="'.$wrapperId.'-week-list" class="dropdown-list dropdown-list-minimalist">';
				$h .= \util\FormUi::weekSelector(
					$weekYear ?? date('Y'),
					onclickWeeks: 'DateField.changeFieldWeek(\'#'.$wrapperId.'\', this)',
					defaultWeek: $value,
					showYear: $withYear
				);
			$h .= '</div>';

			if($withYear === FALSE) {
				$h .= $this->hidden($name.'Year', $weekYear, [
					'data-fallback' => 'year',
					'data-week-selector' => '#'.$wrapperId.' [name="'.$name.'Week"]',
				]);
			}

			$h .= $this->hidden($name, $value, ['class' => 'field-week-value'] + $attributes);

		$h .= '</div>';

		$h .= '<script>';
			$h .= 'DateField.startFieldWeek(\'#'.$wrapperId.'\')';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Display text field for weeks
	 */
	public function weekNumber(string $name, int $value = NULL, array $attributes = []): string {

		$id = uniqid('field-week-');

		$weekNumber = $value ? week_number($value) : NULL;

		$attributes += [
			'data-week-number' => '#'.$id,
		];

		$h = '<div class="input-group">';
			$h .= '<span class="input-group-addon">'.s("Semaine").'</span>';
			$h .= $this->getWeekNumberField($name, $value, $attributes);
			$h .= '<span class="input-group-addon field-week-preview" id="'.$id.'">'.DateUi::weekToDays(date('Y').'-W'.sprintf('%02d', $weekNumber), withYear: FALSE).'</span>';
		$h .= '</div>';

		return $h;
	}

	protected function getWeekNumberField(string $name, int $value = NULL, array $attributes = []) {

		$attributes += [
			'placeholder' => s("n°"),
			'min' => 1,
			'max' => 53,
			'onrender' => 'DateField.updateWeeks(this)'
		];

		return $this->number($name, $value, $attributes);

	}

	public static function weekSelector(int $year, ?string $linkWeeks = NULL, ?string $linkMonths = NULL, ?string $onclickWeeks = NULL, ?string $onclickMonths = NULL, ?string $defaultWeek = NULL, bool $showYear = TRUE): string {

		\Asset::css('util', 'form.css');

		$currentWeek = currentWeek();
		$defaultWeek ??= $currentWeek;

		$weeks = date('W', strtotime($year.'-12-31')) === '53' ? 53 : 52;
		$list = [];

		for($weekNumber = 1; $weekNumber <= $weeks; $weekNumber++) {

			$week = $year.'-W'.sprintf('%02d', $weekNumber);
			$monday = strtotime($week);
			$sunday = strtotime($week.' + 6 DAY');

			$sundayMonth = (int)date('n', $sunday);
			$sundayDay = (int)date('j', $sunday);

			if($sundayDay >= 4) {

				$list[$sundayMonth][] = [
					'week' => $week,
					'weekNumber' => $weekNumber,
					'sunday' => $sundayDay,
				];

			} else {

				$mondayMonth = (int)date('n', $monday);
				$mondayDay = (int)date('j', $monday);

				$list[$mondayMonth][] = [
					'week' => $week,
					'weekNumber' => $weekNumber,
					'sunday' => $mondayDay + 6,
				];

			}

		}

		$id = uniqid('field-week-selector-');

		$h = '<div id="'.$id.'" class="field-week-selector">';
			$h .= '<h4 class="field-week-selector-title">'.s("Calendrier").'</h4>';
			
			if($showYear) {

				$params = [
					'id' => $id,
					'linkWeeks' => $linkWeeks,
					'linkMonths' => $linkMonths,
					'onclickWeeks' => $onclickWeeks,
					'onclickMonths' => $onclickMonths,
					'default' => $defaultWeek
				];
				
				$h .= '<div class="field-week-selector-year">';
					$h .= '<a data-ajax="util/form:weekChange" '.attrs($params, 'post-').' post-year="'.($year - 1).'" data-dropdown-keep class="field-week-selector-navigation field-week-selector-navigation-before">'.\Asset::icon('chevron-left').'</a>';
					$h .= '<h4>'.$year.'</h4>';
					$h .= '<a data-ajax="util/form:weekChange" '.attrs($params, 'post-').' post-year="'.($year + 1).'" data-dropdown-keep class="field-week-selector-navigation field-week-selector-navigation-after">'.\Asset::icon('chevron-right').'</a>';
				$h .= '</div>';
				
			} else {
				$h .= '<br/>';
			}
			
			$h .= '<div class="field-week-selector-weeks">';

				$h .= '<div></div>';

				$h .= '<div class="field-week-selector-ticks">';

					foreach([1, 5, 10, 15, 20, 25, 31] as $day) {
						$h .= '<div class="field-week-selector-label" style="grid-column-start: '.($day + 3).'; grid-column-end: '.($day + 5).'">'.$day.'</div>';
						$h .= '<div class="field-week-selector-tick" style="grid-column-start: '.($day + 4).'"></div>';
					}

				$h .= '</div>';

				foreach($list as $month => $weeks) {

					if($linkMonths) {
						$h .= '<a '.attr('href', str_replace('{current}', $month, $linkMonths)).' data-month="'.$month.'" class="field-week-selector-month">'.DateUi::getMonthName($month).'</a>';
					} else if($onclickMonths) {
						$h .= '<a '.attr('onclick', str_replace('{current}', $month, $onclickMonths)).' data-month="'.$month.'" class="field-week-selector-month">'.DateUi::getMonthName($month).'</a>';
					} else {
						$h .= '<div class="field-week-selector-month">'.DateUi::getMonthName($month).'</div>';
					}

					$h .= '<div class="field-week-selector-bubbles">';

					foreach($weeks as ['week' => $week, 'weekNumber' => $weekNumber, 'sunday' => $sunday]) {

						$color = ($defaultWeek === $week) ? 'btn-secondary' : ($currentWeek === $week ? 'btn-outline-primary field-week-selector-bubble-current' : 'btn-primary');

						$attrs = [
							'href' => $linkWeeks ? str_replace('{current}', $week, $linkWeeks) : NULL,
							'onclick' => $onclickWeeks ? str_replace('{current}', $week, $onclickWeeks) : NULL,
							'data-week' => $week,
							'class' => 'btn btn-sm '.$color.' field-week-selector-bubble',
							'style' => 'grid-column-start: '.($sunday - 6 + 4).'; grid-column-end: '.($sunday + 1 + 4).'',
							'title' => $showYear ? \util\DateUi::weekToDays($week) : s("Autour du {value}", \util\DateUi::weekToDays($week, withYear: FALSE))
						];

						$h .= '<a '.attrs($attrs).'>'.$weekNumber.'</a>';

					}

					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a time field
	 *
	 * @param string $name
	 * @param string $value Default time
	 * @param array $attributes => 'callback' method called for each changement
	 * @return strings
	 */
	public function time(string $name, mixed $value = NULL, array $attributes = []): string {

		$attributes['placeholder'] = '--:--';
		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('time', $name, $value, $attributes);

	}

	/**
	 * Display a select field with range
	 *
	 * @param string $name
	 * @param int $min
	 * @param int $max
	 * @param int $step
	 * @param mixed $selection Default selection (int or array for multiple selected values)
	 * @param array $attributes Additional attributes (with callback for content, default encode)
	 */
	public function range(string $name, int $min, int $max, int $step, int $value, array $attributes = []): string {

		$attributes['min'] = $min;
		$attributes['max'] = $max;
		$attributes['step'] = $step;

		if(isset($attributes['data-label'])) {

			$h = '<div class="form-range">';
				$h .=  $this->input('range', $name, $value, $attributes);
				$h .= '<div class="form-range-label" title="'.s("Modifier la valeur").'">';
					$h .= $this->inputGroup(
						$this->number(value: $value, attributes: [
							'oninput' => 'RangeField.setValue(this)'
						]).
						$this->addon($attributes['data-label'])
					);
				$h .= '</div>';
			$h .= '</div>';

		} else {
			$h =  $this->input('range', $name, $value, $attributes);
		}

		return $h;

	}

	/**
	 * Display a select field with range
	 *
	 * @param string $name
	 * @param int $from
	 * @param int $to
	 * @param int $step
	 * @param mixed $selection Default selection (int or array for multiple selected values)
	 * @param array $attributes Additional attributes (with callback for content, default encode)
	 */
	public function rangeSelect(string $name, int $from, int $to, int $step, mixed $selection = NULL, array $attributes = []): string {

		$array = [];

		if(
			($from < $to and $step > 0) or
			($from > $to and $step < 0)
		) {

			for($i = $from; ($from > $to) ? ($i >= $to) : ($i <= $to); $i += $step) {
				$array[$i] = $i;
			}

		} else {
			$array[$from] = $from;
		}

		return $this->select($name, $array, $selection, $attributes);

	}

	/**
	 * Display a select field for elements
	 *
	 * @param string $name
	 * @param array $values Possible values
	 * @param mixed $selection Default selection
	 * @param array $attributes Additional attributes ('multiple' for multiple select, with callback for content, default encode)
	 */
	public function select(?string $name, $values, mixed $selection = NULL, array $attributes = []): string {

		// Selection can be an element
		$selection = $this->getSelectedValue($selection, !empty($attributes['multiple']));

		if(isset($attributes['callback'])) {
			$callback = $attributes['callback'];
			unset($attributes['callback']);
		} else {
			$callback = 'encode';
		}

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$size = $this->getSize('form-control');

		if($size) {
			$attributes['class'] .= ' '.$size;
		}

		$this->setDefaultAttributes($attributes, $name);

		$select = "";

		if(empty($attributes['mandatory']) and empty($attributes['multiple'])) {

			$select .= "<option value=''";

			if(in_array(NULL, $selection, TRUE)) {
				$select .= " selected='selected'";
			}

			$select .= ' class="field-radio-select">';
			$select .= $attributes['placeholder'] ?? s("< Choisir >");
			$select .= "</option>";

			unset($attributes['placeholder']);

		}
		unset($attributes['mandatory']);

		$select = "<select ".attrs($attributes).">".$select;

		foreach($values as $key => $value) {

			[$optionValue, $optionContent, $optionAttributes] = $this->getOptionValue($key, $value);

			$select .= "<option value=\"".encode($optionValue)."\"";

			if(isset($optionAttributes['disabled']) === FALSE) {

				foreach($selection as $valueCheck) {
					if((string)$valueCheck === (string)$optionValue) {
						$select .= ' selected="selected"';
						break;
					}
				}

			}

			if($optionAttributes) {
				$select .= ' '.attrs($optionAttributes);
			}

			$select .= ">".call_user_func($callback, $optionContent)."</option>";

		}

		$select .= "</select>";

		return $select;

	}

	/**
	 * Display a list of selects field for multiple choice
	 * This is a user-friendly alternative to <select multiple="multiple">
	 *
	 * @param string $name
	 * @param array $values Possible values
	 * @param mixed $selection Default selection
	 * @param array $attributes Additional attributes ('multiple' for multiple select, with callback for content, default encode)
	 */
	public function selects(string $name, $values, mixed $selection = NULL, array $attributes = []): string {

		$selection = $this->getSelectedValue($selection, TRUE);

		$h = '<div class="form-selects">';

			if($selection) {

				foreach($selection as $value) {
					$h .= '<div class="form-selects-item input-group">';
						$h .= $this->select($name, $values, $value, $attributes);
						$h .= '<a data-action="form-selects-delete" class="input-group-addon">';
							$h .= \Asset::icon('trash-fill');
						$h .= '</a>';
					$h .= '</div>';
				}

			} else {

				$h .= '<div class="form-selects-item input-group">';
					$h .= $this->select($name, $values, NULL, $attributes);
					$h .= '<a data-action="form-selects-delete" class="input-group-addon">';
						$h .= \Asset::icon('trash-fill');
					$h .= '</a>';
				$h .= '</div>';

			}

			$h .= '<div class="form-selects-add">';
				$h .= \Asset::icon('plus-circle').' ';
				$h .= '<a data-action="form-selects-add">'.($attributes['labelAdd'] ?? s("Ajouter")).'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function getSelectedValue($selection, bool $multiple): array {

		if($selection instanceof \Collection) {
			if($multiple) {
				return $selection->getIds();
			} else {
				throw new \Exception('Unexpected collection');
			}
		} else if($selection instanceof \Element) {
			if($selection->empty()) {
				return [];
			} else {
				return [$selection['id']];
			}
		} else if(is_array($selection) === FALSE) {
			return [$selection];
		} else {
			return $selection;
		}

	}

	private function getInputValue($selection) {

		if($selection instanceof \Element) {
			if($selection->empty()) {
				return NULL;
			} else {
				return $selection['id'];
			}
		} else if(is_bool($selection)) {
			return $selection ? '1' : '0';
		} else {
			return $selection;
		}

	}

	private function getOptionValue(string|int|float $key, $option): array {

		if($option instanceof \Element) {

			$value = NULL;
			$label = NULL;
			$counter = 0;

			foreach($option as $field => $valueElement) {

				switch(++$counter) {
					case 1 :
						$value = $valueElement;
						break;
					case 2 :
						$label = $valueElement;
						break;
					default :
						break(2);
				}

			}

			return [$value, $label, []];

		} else if(is_array($option)) {

			return [
				$option['value'] ?? NULL,
				$option['label'],
				$option['attributes'] ?? []
			];

		} else {
			return [$key, $option, []];
		}

	}

	public static function info(string $text, string $icon = 'arrow-return-right', string $class = ''): string {
		return '<div class="form-info '.$class.'">'.\Asset::icon($icon).$text.'</div>';
	}

	/**
	 * Display text field
	 */
	public function text(?string $name = NULL, $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('text', $name, $value, $attributes);
	}

	/**
	 * Display text field for numbers
	 */
	public function number(?string $name = NULL, $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control form-number '.($attributes['class'] ?? '');

		return $this->input('number', $name, $value, $attributes);
	}

	/**
	 * Display text field with color picker
	 */
	public function color(string $name, $value = NULL, array $attributes = []): string {

		if(array_key_exists('emptyColor', $attributes)) {
			$emptyColor = $attributes['emptyColor'];
			unset($attributes['emptyColor']);
		} else {
			$emptyColor = '#000000';
		}

		$attributes['class'] = 'form-control form-color '.($attributes['class'] ?? '');

		$h = '<div class="field-color">';
			$h .= $this->input('color', $name.'Color', $value, ['oninput' => 'ColorField.update(this)'] + $attributes);
			$h .= '<label>'.$this->inputCheckbox($name.'Empty', TRUE, ['checked' => ($value === NULL), 'onclick' => 'ColorField.setEmpty(this, "'.$emptyColor.'")']).' '.s("Aucune").'</label>';
			$h .= $this->hidden($name, $value);
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display text field for emails
	 */
	public function email(string $name, string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control email '.($attributes['class'] ?? '');

		return $this->input('email', $name, $value, $attributes);
	}

	public function address(?string $prefix, \Element $e) {

		$field = fn($name) => ($prefix === NULL) ? $name : $prefix.ucfirst($name);

		$h = $this->group(
			s("Adresse"),
			$this->dynamicField($e, $field('street1')),
			nested: TRUE
		);
		$h .= $this->group(
			s("Complément d'adresse"),
			$this->dynamicField($e, $field('street2')),
			nested: TRUE
		);

		$h .= '<div class="form-postcode-city">';

			$h .= $this->group(
				s("Code postal"),
				$this->dynamicField($e, $field('postcode')),
				nested: TRUE
			);
			$h .= $this->group(
				s("Ville"),
				$this->dynamicField($e, $field('city')),
				nested: TRUE
			);

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display text field for fully qualified names
	 */
	public function fqn(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$attributes += [
			'data-type' => 'fqn',
			'append' => '<small>'.\Asset::icon('info-circle').' '.s("Uniquement a-z et -").'</small>'
		];

		return $this->input('text', $name, $value, $attributes);
	}

	/**
	 * Display text field for urls
	 */
	public function url(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control url '.($attributes['class'] ?? '');
		$attributes += [
			'placeholder' => 'https://'
		];

		return $this->input('url', $name, $value, $attributes);
	}

	/**
	 * Display text area field
	 */
	public function textarea(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$this->setDefaultAttributes($attributes, $name);

		$textarea = "<textarea ".attrs($attributes).">".encode($value)."</textarea>";

		$count = $this->getCharacterCount($attributes);
		if($count) {
			return '<div class="form-character-count-wrapper for-textarea">
						'.$textarea.'
						'.$count.'
					</div>';

		}

		return $textarea;
	}

	/**
	 * Display editor field
	 */
	public function editor(string $name, $value = '', array $values = [], array $attributes = []): string {

		if($value) {
			$convertedValue = (new \editor\EditorFormatterUi())->getFromXml($value, $values);
		} else {
			$convertedValue = '';
		}

		$this->setDefaultAttributes($attributes, $name);

		return (new \editor\EditorUi())->field($name, $values, $convertedValue, $attributes);

	}

	/**
	 * Display password field
	 */
	public function password(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');
		$attributes['autocapitalize'] = 'off';
		$attributes['autocorrect'] = 'off';

		return $this->input('password', $name, $value, $attributes);
	}

	/**
	 * Display hidden field
	 */
	public function hidden(?string $name, $value = NULL, array $attributes = []): string {

		return $this->input('hidden', $name, $value, $attributes);

	}

	/**
	 * Display several hidden field
	 */
	public function hiddens(array $values, array $attributes = []): string {

		$h = '';

		foreach($values as $name => $value) {
			$h .= $this->hidden($name, $value, $attributes);
		}

		return $h;

	}

	/**
	 * Relay some information
	 */
	public function relay(string $source, string|array $names, array $attributes = []): string {

		str_is($source, ['GET', 'POST', 'REQUEST']);

		$h = '';

		foreach((array)$names as $name) {
			$h .= $this->hidden($name, $source($name), $attributes);
		}

		return $h;

	}

	/**
	 * Display a file field
	 */
	public function file(string $name, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('file', $name, NULL, $attributes);
	}

	/**
	 * Display a button
	 */
	public function button($value = NULL, array $attributes = []): string {

		$attributes['class'] = ($attributes['class'] ?? 'btn '.$this->getSize('btn').' btn-primary');
		$attributes['type'] = ($attributes['type'] ?? 'button');

		$this->setDefaultAttributes($attributes);

		return "<button ".attrs($attributes).">".$value."</button>";

	}

	/**
	 * Display submit field
	 */
	public function submit($value = NULL, array $attributes = []): string {

		$attributes['type'] = 'submit';

		return $this->button($value, $attributes);

	}

	/**
	 * Create an autocomplete field
	 */
	public function autocomplete(string $name, string $url, array $body, ?string $dispatch, array $values, array $attributes): array {

		$id = $attributes['id'] ?? uniqid('autocomplete-');

		$multiple = (strpos($name, '[]') !== FALSE);

		$attributes += [
			'placeholder' => '', // Mandatory
			'data-autocomplete-url' => $url,
			'data-autocomplete-body' => json_encode($body),
			'data-autocomplete-items' => $id,
			'data-autocomplete-field' => $name,
			'data-autocomplete-empty' => s("Aucun résultat !"),
			'data-autocomplete-select' => 'event',
			'onrender' => 'AutocompleteField.start(this);'
		];

		if($dispatch) {
			$attributes['data-autocomplete-dispatch'] = $dispatch;
		}

		if($values === []) {
			$defaultQuery = NULL;
			$defaultResults = '';
		} else if($multiple) {
			$defaultQuery = NULL;
			$defaultResults = '';
			foreach($values as $value) {
				$defaultResults .= '<div class="autocomplete-item" data-value="'.$value['value'].'">';
					$defaultResults .= $value['itemHtml'].'&nbsp;<a onclick="AutocompleteField.removeItem(this)" class="btn btn-muted">'.\Asset::icon('trash-fill').'</a>';
					$defaultResults .= $this->hidden($name, $value['value']);
				$defaultResults .= '</div>';
			}
		} else {
			$value = first($values);
			$defaultQuery = $value['itemText'];
			$defaultResults = $this->hidden($name, $value['value']);
		}

		$query = $this->text($id.'-label', $defaultQuery, $attributes);
		$query .= '<a class="autocomplete-empty" onclick="AutocompleteField.empty(this)">'.\Asset::icon('x').'</a>';

		return [
			'query' => $query,
			'results' => '<div class="autocomplete-items '.($multiple ? 'autocomplete-items-multiple' : '').'" id="'.$id.'">'.$defaultResults.'</div>'
		];

	}

	protected function input(string $type, ?string $name, $value, array $attributes): string {

		$this->setDefaultAttributes($attributes, $name);

		$attributes['type'] = $type;
		$attributes['value'] = $this->getInputValue($value);

		$size = $this->getSize('form-control');

		if($size) {
			$attributes['class'] = ($attributes['class'] ?? NULL).' '.$size;
		}

		$inputAttributes = $attributes;

		if(isset($attributes['prepend'])) {

			unset($inputAttributes['prepend']);
			$prepend = $this->addon($attributes['prepend']);

		} else {
			$prepend = '';
		}

		if(isset($attributes['append'])) {

			unset($inputAttributes['append']);
			$append = $this->addon($attributes['append']);

		} else {
			$append = '';
		}

		$input = '<input '.attrs($inputAttributes).'/>';

		if($prepend or $append) {
			$input = $this->inputGroup($prepend.$input.$append);
		}

		$count = $this->getCharacterCount($attributes);

		if($count) {
			return '<div class="form-character-count-wrapper for-input input-alert">
				'.$input.$count.'
			</div>';
		}

		return $input;
	}

	private function getCharacterCount(array $attributes): string {
		$limit = $attributes['data-limit'] ?? NULL;
		if($limit) {
			return '<span class="form-character-count" data-limit-for="'.$attributes['id'].'"></span>';
		}
		return '';
	}

	protected function setDefaultAttributes(array &$attributes, ?string $name = NULL) {

		$attributes['id'] ??= uniqid('field-');
		$attributes['name'] ??= $name;

		$this->lastFieldId = $attributes['id'] ?? NULL;
		$this->lastFieldName = $attributes['name'];
		$this->nextWrapper = $attributes['name'];


	}

	public function getLastFieldId(): ?string {
		return $this->lastFieldId;
	}

}
?>
