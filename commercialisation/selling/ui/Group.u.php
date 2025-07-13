<?php
namespace selling;

class GroupUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');
		\Asset::js('selling', 'customer.js');

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

	public function getManageTitle(\farm\Farm $eFarm, \Collection $cGroup): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= \s("Les groupes de clients");
			$h .= '</h1>';

			if($cGroup->notEmpty()) {

				$h .= '<div>';
					$h .= '<a href="/selling/group:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau groupe").'</a>';
				$h .= '</div>';

			}

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
						$h .= '<th class="td-min-content">'.self::p('color')->label.'</th>';
						$h .= '<th class="text-center">'.s("Clients").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cGroup as $eGroup) {

					$h .= '<tr>';
						$h .= '<td>';
							$h .= encode($eGroup['name']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('type')->values[$eGroup['type']];
						$h .= '</td>';
						$h .= '<td class="td-min-content text-center">';
							$h .= self::getColorCircle($eGroup);
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<a href="'.\farm\FarmUi::urlSellingCustomer($eFarm).'?group='.$eGroup['id'].'">';
								$h .= $eGroup['customers'];
							$h .= '</a> ';
						$h .= '</td>';
						$h .= '<td class="text-end" style="white-space: nowrap">';

							$h .= '<a href="/selling/group:update?id='.$eGroup['id'].'" class="btn btn-outline-secondary">';
								$h .= \Asset::icon('gear-fill');
							$h .= '</a> ';

							$h .= '<a data-ajax="/selling/group:doDelete" data-confirm="'.s("Voulez-vous réellement supprimer ce groupe de clients. Continuer ?").'" post-id="'.$eGroup['id'].'" class="btn btn-outline-secondary">';
								$h .= \Asset::icon('trash-fill');
							$h .= '</a>';

						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		}

		return $h;

	}

	public static function getColorCircle(Group $eGroup, ?string $size = NULL): string {

		\Asset::css('selling', 'customer.css');

		$eGroup->expects(['color']);

		if($eGroup['color'] !== NULL) {
			return '<div class="customer-color-circle" style="background-color: '.$eGroup['color'].'; '.($size ? 'width: '.$size.'; height: '.$size.';' : '').'"></div>';
		} else {
			return '';
		}

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eGroup = new Group();

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/group:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eGroup, ['name*', 'type*', 'color']);
			$h .= $form->group(
				content: $form->submit(\s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-group-create',
			title: \s("Ajouter un nouveau groupe de clients"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Group $eGroup): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/group:doUpdate');

			$h .= $form->hidden('id', $eGroup['id']);
			$h .= $form->dynamicGroups($eGroup, ['name', 'color']);
			$h .= $form->group(
				content: $form->submit(\s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-group-update',
			title: \s("Modifier un groupe de clients"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Group::model()->describer($property, [
			'name' => \s("Nom"),
			'type' => \s("Clientèle"),
			'color' => \s("Couleur"),
			'fqn' => \s("Nom qualifié")
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
