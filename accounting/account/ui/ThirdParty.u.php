<?php
namespace account;

class ThirdPartyUi {

	public function getThirdPartyTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les tiers");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.\attr('onclick', 'Lime.Search.toggle("#thirdParty-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/thirdParty:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un tiers").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getOperationDescription(ThirdParty $eThirdParty, string $thirdPartyType): string {

		return match($thirdPartyType) {
			'client' => s("Client {name}", ['name' => $eThirdParty['name']]),
			'supplier' => s("Fournisseur {name}", ['name' => $eThirdParty['name']]),
		};

	}

	public function create(\farm\Farm $eFarm, ThirdParty $eThirdParty): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/thirdParty:doCreate', ['id' => 'journal-thirdParty-create', 'autocomplete' => 'off', 'onrender' => 'ThirdParty.focusInput();']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroup($eThirdParty, 'name*');

		$h .= $form->group(
			content: $form->submit(s("Créer le tiers"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-journal-thirdParty-create',
			title: s("Ajouter un tiers"),
			body: $h
		);

	}

	public static function manage(\farm\Farm $eFarm, \Collection $cThirdParty, \Search $search): string {

		if($cThirdParty->empty() === TRUE) {
			return '<div class="util-info">'.
				s("Aucun tiers n'a encore été créé.").
				'</div>';
		}

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
					$h .= '<th>';
						$label = s("#");
						$h .= ($search ? $search->linkSort('id', $label) : $label);
					$h .= '</th>';
					$h .= '<th>';
						$label = s("Nom");
						$h .= ($search ? $search->linkSort('name', $label) : $label);
					$h .= '</th>';
					$h .= '<th>'.s("Nombre d'écritures comptables").'</th>';
					$h .= '<th></th>';
				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cThirdParty as $eThirdParty) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= $eThirdParty['id'];
							$h .= '</td>';
							$h .= '<td>';
								$eThirdParty->setQuickAttribute('farm', $eFarm['id']);
								$eThirdParty->setQuickAttribute('app', 'accounting');
								$h .= $eThirdParty->quick('name', encode($eThirdParty['name']));
							$h .= '</td>';
							$h .= '<td>';
								$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?thirdParty='.$eThirdParty['id'].'">'.$eThirdParty['operations'].'</a>';
							$h .= '</td>';

							$h .= '<td>';

								$attributes = [
									'data-ajax' => \company\CompanyUi::urlAccount($eFarm).'/thirdParty:doDelete',
									'post-id' => $eThirdParty['id'],
									'class' => 'btn btn-outline-secondary btn-outline-danger'.($eThirdParty['operations'] > 0 ? ' disabled' : ''),
								];
								$h .= '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>';

						$h .= '</td>';
						$h .= '</tr>';

					}
				$h .= '</tbody>';
			$h .= '</table>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="thirdParty-search" class="util-block-search stick-xs '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
						$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);
						$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
						$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}
	public static function getAutocomplete(int $farm, ThirdParty $eThirdParty): array {

		\Asset::css('media', 'media.css');

		return [
			'value' => $eThirdParty['id'],
			'clientAccountLabel' => $eThirdParty['clientAccountLabel'],
			'supplierAccountLabel' => $eThirdParty['supplierAccountLabel'],
			'company' => $farm,
			'itemHtml' => $eThirdParty['name'],
			'itemText' => $eThirdParty['name']
		];

	}

	public function query(\PropertyDescriber $d, int $company, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('person-rolodex');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tiers...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'thirdParty'];

		$d->autocompleteUrl = \company\CompanyUi::urlAccount($company).'/thirdParty:query';
		$d->autocompleteResults = function(ThirdParty $e) use ($company) {
			return self::getAutocomplete($company, $e);
		};

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Créer un tiers").'</div>';

		return [
			'type' => 'link',
			'link' => \company\CompanyUi::urlAccount($eFarm).'/thirdParty:create',
			'itemHtml' => $item
		];

	}

	public static function p(string $property): \PropertyDescriber {

		$d = ThirdParty::model()->describer($property, [
			'name' => s("Nom"),
		]);

		switch($property) {
			case 'name':
				$d->before = fn(\util\FormUi $form, $e) => $e->isQuick() ? \util\FormUi::info(s("Attention, ce changement sera répercuté sur toutes les opérations déjà créées")) : '';
				break;
		}
		return $d;

	}

}
?>
