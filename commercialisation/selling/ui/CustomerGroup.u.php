<?php
namespace selling;

class CustomerGroupUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'customer.js');

	}

	public static function link(CustomerGroup $eCustomerGroup): string {
		return '<a href="/selling/customerGroup:get?id='.$eCustomerGroup['id'].'" class="util-badge" style="background-color: '.$eCustomerGroup['color'].'">'.encode($eCustomerGroup['name']).'</a>';
	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('people-fill');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Choisissez un groupe");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'group'];

		$d->autocompleteUrl = '/selling/customerGroup:query';
		$d->autocompleteResults = function(CustomerGroup|\Collection $e) {
			return self::getAutocomplete($e);
		};

		$d->attributes = [
			'data-autocomplete-id' => 'group'
		];

	}

	public static function getAutocomplete(CustomerGroup $eCustomerGroup): array {

		\Asset::css('media', 'media.css');

		$item = '<div>'.encode($eCustomerGroup['name']).'</div>';

		return [
			'value' => $eCustomerGroup['id'],
			'itemHtml' => $item,
			'itemText' => $eCustomerGroup['name']
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Ajouter un groupe").'</div>';

		return [
			'type' => 'link',
			'link' => '/selling/customerGroup:create?farm='.$eFarm['id'],
			'itemHtml' => $item
		];

	}

	public function getOne(CustomerGroup $eCustomerGroup, \Collection $cCustomer, \Collection $cGrid, \Collection $cCustomerGroup): string {

		$h = '<div class="tabs-h" id="group-tabs-wrapper" onrender="'.encode('Lime.Tab.restore(this, "group")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="sales" onclick="Lime.Tab.select(this)">';
					$h .= s("Clients").' <span class="tab-item-count">'.$cCustomer->count().'</span>';
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="grid" onclick="Lime.Tab.select(this)">';
					$h .= s("Prix personnalisés").' <span class="tab-item-count">'.$cGrid->count().'</span>';
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div>';
				$h .= '<div data-tab="sales" class="tab-panel selected">';
					$h .= new CustomerUi()->getList($eCustomerGroup['farm'], $cCustomer, $cCustomerGroup, hide: ['more', 'sales', 'prices', 'actions']);
				$h .= '</div>';

				$h .= '<div data-tab="grid" class="tab-panel">';
					$h .= new \selling\GridUi()->getGridByGroup($eCustomerGroup, $cGrid);
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getMenu(CustomerGroup $eCustomerGroup, string $btn): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';

			$h .= '<div class="dropdown-title">'.encode($eCustomerGroup['name']).'</div>';

			$h .= '<a href="/selling/customerGroup:update?id='.$eCustomerGroup['id'].'" class="dropdown-item">'.s("Modifier le groupe").'</a> ';
			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a data-ajax="/selling/customerGroup:doDelete" post-id="'.$eCustomerGroup['id'].'" data-confirm="'.s("Voulez-vous réellement supprimer ce groupe de clients. Continuer ?").'" class="dropdown-item">'.s("Supprimer le groupe").'</a>';

		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cCustomerGroup): string {

		$h = '';

		if($cCustomerGroup->empty()) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pouvez créer des groupes de clients pour regrouper vos clients en fonction de votre canaux de commercialisation. Cela vous permettra notamment de créer des ventes pour tout un groupe en clic ou faire des recherches plus facilement dans votre base de clients !").'</p>';
				$h .= '<p>'.s("Par exemple, si vous avez plusieurs AMAP, vous pouvez créer un groupe pour chaque AMAP.").'</p>';
				$h .= '<a href="/selling/customerGroup:create?farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Créer un premier groupe de clients").'</a>';
			$h .= '</div>';

		} else {

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.self::p('name')->label.'</th>';
						$h .= '<th>'.self::p('type')->label.'</th>';
						$h .= '<th class="text-center">'.s("Clients").'</th>';
						$h .= '<th class="text-center hide-xs-down">'.s("Prix<br/>personnalisés").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCustomerGroup as $eCustomerGroup) {

					$h .= '<tr>';
						$h .= '<td class="td-min-content">';
							$h .= self::link($eCustomerGroup);
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('type')->values[$eCustomerGroup['type']];
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= $eCustomerGroup['customers'];
						$h .= '</td>';
						$h .= '<td class="text-center hide-xs-down">';
							if($eCustomerGroup['prices'] > 0) {
								$h .= p("{value} prix", "{value} prix", $eCustomerGroup['prices']);
							} else {
								$h .= '-';
							}
						$h .= '</td>';
						$h .= '<td class="text-end" style="white-space: nowrap">';
							$h .= $this->getMenu($eCustomerGroup, 'btn-primary');
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eCustomerGroup = new CustomerGroup();

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/customerGroup:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eCustomerGroup, ['name*', 'type*', 'color']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-group-create',
			title: s("Ajouter un nouveau groupe de clients"),
			body: $h
		);

	}

	public function update(CustomerGroup $eCustomerGroup): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/customerGroup:doUpdate');

			$h .= $form->hidden('id', $eCustomerGroup['id']);
			$h .= $form->dynamicGroups($eCustomerGroup, ['name', 'color']);
			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-group-update',
			title: s("Modifier un groupe de clients"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = CustomerGroup::model()->describer($property, [
			'name' => s("Nom"),
			'type' => s("Clientèle"),
			'color' => s("Couleur"),
			'fqn' => s("Nom qualifié")
		]);

		switch($property) {

			case 'color' :
				$d->labelAfter = \util\FormUi::info(s("Choisissez une couleur plutôt sombre pour que le nom du groupe reste lisible."));
				break;

			case 'type' :
				$d->values = [
					CustomerGroup::PRO => s("Professionnels"),
					CustomerGroup::PRIVATE => s("Particuliers")
				];
				break;

		}

		return $d;

	}


}
?>
