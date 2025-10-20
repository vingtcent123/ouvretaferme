<?php
namespace selling;

class GroupUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'customer.js');

	}

	public static function link(Group $eGroup): string {
		return '<a href="/selling/group:get?id='.$eGroup['id'].'" class="util-badge" style="background-color: '.$eGroup['color'].'">'.encode($eGroup['name']).'</a>';
	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('people-fill');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Choisissez un groupe");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'group'];

		$d->autocompleteUrl = '/selling/group:query';
		$d->autocompleteResults = function(Group|\Collection $e) {
			return self::getAutocomplete($e);
		};

		$d->attributes = [
			'data-autocomplete-id' => 'group'
		];

	}

	public static function getAutocomplete(Group $eGroup): array {

		\Asset::css('media', 'media.css');

		$item = '<div>'.encode($eGroup['name']).'</div>';

		return [
			'value' => $eGroup['id'],
			'itemHtml' => $item,
			'itemText' => $eGroup['name']
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Ajouter un groupe").'</div>';

		return [
			'type' => 'link',
			'link' => '/selling/group:create?farm='.$eFarm['id'],
			'itemHtml' => $item
		];

	}

	public function getOne(Group $eGroup, \Collection $cCustomer, \Collection $cGrid, \Collection $cGroup): string {

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
					$h .= new CustomerUi()->getList($eGroup['farm'], $cCustomer, $cGroup, hide: ['more', 'sales', 'prices', 'actions']);
				$h .= '</div>';

				$h .= '<div data-tab="grid" class="tab-panel">';
					$h .= new \selling\GridUi()->getGridByGroup($eGroup, $cGrid);
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getMenu(Group $eGroup, string $btn): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';

			$h .= '<div class="dropdown-title">'.encode($eGroup['name']).'</div>';

			$h .= '<a href="/selling/group:update?id='.$eGroup['id'].'" class="dropdown-item">'.s("Modifier le groupe").'</a> ';
			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a data-ajax="/selling/group:doDelete" post-id="'.$eGroup['id'].'" data-confirm="'.s("Voulez-vous réellement supprimer ce groupe de clients. Continuer ?").'" class="dropdown-item">'.s("Supprimer le groupe").'</a>';

		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cGroup): string {

		$h = '';

		if($cGroup->empty()) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pouvez créer des groupes de clients pour regrouper vos clients en fonction de votre canaux de commercialisation. Cela vous permettra notamment de créer des ventes pour tout un groupe en clic ou faire des recherches plus facilement dans votre base de clients !").'</p>';
				$h .= '<p>'.s("Par exemple, si vous avez plusieurs AMAP, vous pouvez créer un groupe pour chaque AMAP.").'</p>';
				$h .= '<a href="/selling/group:create?farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Créer un premier groupe de clients").'</a>';
			$h .= '</div>';

		} else {

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.self::p('name')->label.'</th>';
						$h .= '<th>'.self::p('type')->label.'</th>';
						$h .= '<th class="text-center">'.s("Clients").'</th>';
						$h .= '<th class="text-center hide-xs-down">'.s("Prix personnalisés").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cGroup as $eGroup) {

					$h .= '<tr>';
						$h .= '<td class="td-min-content">';
							$h .= self::link($eGroup);
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('type')->values[$eGroup['type']];
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= $eGroup['customers'];
						$h .= '</td>';
						$h .= '<td class="text-center hide-xs-down">';
							if($eGroup['prices'] > 0) {
								$h .= p("{value} prix", "{value} prix", $eGroup['prices']);
							} else {
								$h .= '-';
							}
						$h .= '</td>';
						$h .= '<td class="text-end" style="white-space: nowrap">';
							$h .= $this->getMenu($eGroup, 'btn-primary');
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eGroup = new Group();

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/group:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eGroup, ['name*', 'type*', 'color']);
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

	public function update(Group $eGroup): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/group:doUpdate');

			$h .= $form->hidden('id', $eGroup['id']);
			$h .= $form->dynamicGroups($eGroup, ['name', 'color']);
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

		$d = Group::model()->describer($property, [
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
					Group::PRO => s("Professionnels"),
					Group::PRIVATE => s("Particuliers")
				];
				break;

		}

		return $d;

	}


}
?>
