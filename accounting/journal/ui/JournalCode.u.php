<?php
namespace journal;

Class JournalCodeUi {


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
						$h .= '<th>';
							$h .= s("Couleur");
						$h .= '</th>';
						$h .= '<th>';
							$h .= s("Extournable ?");
						$h .= '</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cJournalCode as $eJournalCode) {

					$eJournalCode->setQuickAttribute('farm', $eFarm['id']);
					$eJournalCode->setQuickAttribute('app', 'accounting');

					$h .= '<tr name="journal-code-'.$eJournalCode['id'].'">';

						$h .= '<td>'.encode($eJournalCode['id']).'</td>';
						$h .= '<td>'.encode($eJournalCode['code']).'</td>';
						$h .= '<td>';
							if($eJournalCode->canQuickUpdate('name')) {
								$h .= $eJournalCode->quick('name', encode($eJournalCode['name']));
							} else {
								$h .= encode($eJournalCode['name']);
							}
						$h .= '</td>';
						$h .= '<td>';

							if($eJournalCode['color'] === NULL) {

								$color = '<i>'.s("Non définie").'</i>';

							} else {

								$color = '<span style="padding: 0.5rem 0.75rem; border-radius: var(--radius); color: white; background-color: '.encode($eJournalCode['color']).'">'.encode($eJournalCode['color']).'</span>';
							}

							if($eJournalCode->canQuickUpdate('color')) {
								$h .= $eJournalCode->quick('color', $color);
							} else {
								$h .= $color;
							}
						$h .= '</td>';
						$h .= '<td>';
							$isExtournable = ($eJournalCode['isExtournable'] ? s("oui") : s("non"));
							if($eJournalCode->canQuickUpdate('isExtournable')) {
								$h .= $eJournalCode->quick('isExtournable', $isExtournable);
							} else {
								$h .= $isExtournable;
							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, JournalCode $eJournalCode): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/journalCode:doCreate', ['id' => 'journal-code-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eJournalCode, ['code*', 'name*', 'color', 'isExtournable']);

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
			'isExtournable' => s("Extournable ?"),
		]);

		switch($property) {
			case 'isExtournable' :
				$d->field = 'yesNo';
				break;
		}
		return $d;

	}

}
