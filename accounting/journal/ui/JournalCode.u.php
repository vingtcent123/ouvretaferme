<?php
namespace journal;

Class JournalCodeUi {

	public function __construct() {

		\Asset::css('journal', 'journal-code.css');
		\Asset::js('journal', 'journal-code.js');

	}

	public function getManageTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= s("Les journaux");
		$h .= '</h1>';

		$h .= '<div>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/journalCode:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un journal personnalisé").'</a>';
		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cJournalCode): string {

		$yes = '<span title="'.s("oui").'">'.\Asset::icon('check-lg').'</span>';
		$no = '<span title="'.s("non").'">'.\Asset::icon('x-lg').'</span>';

		$h = '<div class="util-overflow-sm">';

			$h .= '<table id="journal-code-list" class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>';
							$h .= s("#");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Code");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Nom");
						$h .= '</th>';
						$h .= '<th class="text-center">';
							$h .= s("Couleur");
						$h .= '</th>';
						$h .= '<th class="text-center">';
							$h .= s("Extournable ?");
						$h .= '</th>';
						$h .= '<th class="text-center">';
							$h .= s("Dans les onglets du livre-journal ?");
						$h .= '</th>';
						$h .= '<th class="text-center">';
							$h .= s("Nombre de comptes");
						$h .= '</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cJournalCode as $eJournalCode) {

					$eJournalCode->setQuickAttribute('farm', $eFarm['id']);
					$eJournalCode->setQuickAttribute('app', 'accounting');

					$h .= '<tr name="journal-code-'.$eJournalCode['id'].'">';

						$h .= '<td>'.encode($eJournalCode['id']).'</td>';
						$h .= '<td>';
							if($eJournalCode->isCustom()) {
								$h .= $eJournalCode->quick('code', encode($eJournalCode['code']));
							} else {
								$h .= encode($eJournalCode['code']);
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= $eJournalCode->quick('name', encode($eJournalCode['name']));
						$h .= '</td>';
						$h .= '<td class="text-center">';

							if($eJournalCode['color'] === NULL) {

								$color = '<i>'.s("Non définie").'</i>';

							} else {

								$color = $this->getColoredButton($eJournalCode, display: 'color');

							}

							$h .= $eJournalCode->quick('color', $color);
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$isReversable = ($eJournalCode['isReversable'] ? $yes : $no);
							$h .= $eJournalCode->quick('isReversable', $isReversable);
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$isDisplayed = ($eJournalCode['isDisplayed'] ? $yes : $no);
							$h .= $eJournalCode->quick('isDisplayed', $isDisplayed);
						$h .= '</td>';

						$h .= '<td class="text-center">';
							$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/journalCode:accounts?id='.$eJournalCode['id'].'">'.$eJournalCode['accounts'].'</a>';
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getColoredButton(JournalCode $eJournalCode, ?string $link = NULL, ?string $title = NULL, string $display = 'code'): string {

		if($eJournalCode->empty()) {
			return '';
		}

		if($eJournalCode['color'] === NULL) {
			$color = 'var(--secondary)';
		} else{
			$color = $eJournalCode['color'];
		}

		if($link) {
			return '<a class="btn btn-xs journal-code-container"  style="background-color:'.$color.'" href="'.$link.'" '.($title ? 'title="'.$title.'"' : '').'>'.encode($eJournalCode['code']).'</a>';
		}

		return '<span class="btn btn-xs journal-code-container" style="background-color:'.$color.'" title="'.($title ?: encode($eJournalCode['name'])).'">'.encode($eJournalCode[$display]).'</span>';

	}

	public function getColoredName(JournalCode $eJournalCode, ?string $link = NULL, ?string $title = NULL): string {

		if($eJournalCode->empty()) {
			return '';
		}

		if($eJournalCode['color'] === NULL) {
			$color = 'var(--secondary)';
		} else{
			$color = $eJournalCode['color'];
		}

		if($link) {
			return '<a class="journal-code-container"  style="background-color:'.$color.'" href="'.$link.'" '.($title ? 'title="'.$title.'"' : '').'>'.encode($eJournalCode['name']).'</a>';
		}

		return '<span class="journal-code-container" style="background-color:'.$color.'">'.encode($eJournalCode['name']).'</span>';

	}

	public function accounts(\farm\Farm $eFarm, JournalCode $eJournalCode, \Collection $cAccount): \Panel {

		$form = new \util\FormUi();

		$h = '<div class="util-info">'.s("En associant des journaux à des classes de compte, ces journaux seront automatiquement proposés lors de l'enregistrement d'écritures au journal.").'</div>';

		$h .= '<div class="util-block-help">'.s("Ces modifications n'impacteront pas les écritures déjà saisies en comptabilité.").'</div>';

		$h .= '<span class="hide" data-warning="journal-code-overwrite" data-singular>'.s("Une classe est déjà rattachée à un autre journal, cette modification écrasera la configuration actuelle, voulez-vous continuer ?").'</span>';
		$h .= '<span class="hide" data-warning="journal-code-overwrite" data-plural>'.s("Certaines classes sont rattachées à d'autres journaux, cette modification écrasera la configuration actuelle, voulez-vous continuer ?").'</span>';

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/journalCode:doAccounts', [
				'id' => 'journal-code-accounts',
				'class' => 'panel-dialog',
			]
		);

		$h .= $form->hidden('id', $eJournalCode['id']);

		$h .= '<table class="tr-hover tr-even">';
			foreach($cAccount as $eAccount) {
				$h .= '<tr>';
					$h .= '<td class="text-center">';
					if($eAccount['journalCode']->notEmpty()) {
						$h .= $this->getColoredName($eAccount['journalCode']);
					}
					$h .= '</td>';
					$h .= '<td>';
					$h .= $form->checkbox('account[]', $eAccount['id'], [
						'callbackLabel' => fn($input) => $input.' '.$eAccount['class'].' - '.encode($eAccount['description']),
						'checked' => ($eAccount['journalCode']['id'] ?? NULL) === $eJournalCode['id'],
						'data-journal-current' => $eAccount['journalCode']['id'] ?? NULL,
					]);
					$h .= '</td>';
				$h .= '</tr>';
			}
		$h .= '</table>';

		$footer = '<div class="text-end">'.$form->button(s("Enregistrer les classes de compte")).'</div>';

		return new \Panel(
			id         : 'panel-journal-code-account',
			title      : s("Modifier les classes de compte du journal {value}", $this->getColoredName($eJournalCode)),
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			footer     : $footer,
		);

	}

	public function create(\farm\Farm $eFarm, JournalCode $eJournalCode): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/journalCode:doCreate', ['id' => 'journal-code-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eJournalCode, ['code*', 'name*', 'color', 'isReversable', 'isDisplayed']);

		$h .= $form->group(
			content: $form->submit(s("Créer le journal"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-journal-code-create',
			title: s("Ajouter un journal personnalisé"),
			body: $h
		);

	}

	public function getInlineStyle(JournalCode $eJournalCode): string {

		if($eJournalCode->empty() or $eJournalCode['color'] === NULL) {
			return '';
		}

		return 'background-color: '.encode($eJournalCode['color']).'; color: white;';

	}
	public static function p(string $property): \PropertyDescriber {

		$d = JournalCode::model()->describer($property, [
			'name' => s("Nom"),
			'code' => s("Code"),
			'color' => s("Couleur"),
			'isReversable' => s("Extournable ?"),
			'isDisplayed' => s("Dans les onglets du livre-journal ?"),
		]);

		switch($property) {
			case 'isReversable' :
				$d->field = 'yesNo';
				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() ? \util\FormUi::info(s("Toutes les écritures d'un journal extournable pourront être extournées lors du bilan d'ouverture en année N+1.")) : '');
				break;

			case 'isDisplayed' :
				$d->field = 'yesNo';
				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() ? \util\FormUi::info(s("Est-ce que ce journal doit être affiché dans les onglets du livre-journal ?")) : '');
				break;
		}
		return $d;

	}

}
