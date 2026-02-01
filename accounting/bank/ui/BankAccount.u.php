<?php
namespace bank;

class BankAccountUi {

	public function getAccountTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
					$h .= s("Les comptes bancaires");
			$h .= '</h1>';

			$h .= '<a href="'.\farm\FarmUi::urlFinancialYear().'/bank/account:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' <span class="">'.s("Créer un compte bancaire").'</span></a> ';
		$h .= '</div>';

		return $h;

	}

	public function list(\farm\Farm $eFarm ,\Collection $cBankAccount): string {

		if($cBankAccount->empty()) {
			return '<div class="util-empty">'.s("Aucun compte bancaire n'a encore été enregistré. Lorsque vous effectuerez votre premier import de relevé bancaire, le compte bancaire rattaché sera automatiquement créé.").'</div>';
		}

		$canUpdate = $eFarm->canManage();

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		$h = '';

		if($canUpdate === TRUE) {

			$h .= '<div class="util-info">'.s("Les comptes bancaires se créent automatiquement lors de l'import d'un relevé bancaire et sont rattachés à un numéro de compte.").'</div>';

		}

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="3"class="text-center highlight-stick-left">'.s("Compte bancaire").'</th>';
						$h .= '<th colspan="2" class="text-center highlight-stick-both">'.s("Compte comptable").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Opérations bancaires").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="highlight-stick-both">'.s("Identifiant de la banque").'</th>';
						$h .= '<th class="highlight-stick-both">'.s("Identifiant du compte").'</th>';
						$h .= '<th class="highlight-stick-left">'.s("Nom du compte").'</th>';
						$h .= '<th class="highlight-stick-both">'.s("Numéro de compte").'</th>';
						$h .= '<th class="highlight-stick-both">'.s("Libellé de compte").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cBankAccount as $eBankAccount) {

					$h .= '<tr>';

						$h .= '<td class="highlight-stick-both">'.encode($eBankAccount['bankId']).'</td>';
						$h .= '<td class="highlight-stick-both">'.encode($eBankAccount['accountId']).'</td>';
						$h .= '<td class="highlight-stick-left">';
							if($canUpdate === TRUE) {
								$eBankAccount->setQuickAttribute('farm', $eFarm['id']);
								$h .= $eBankAccount->quick('description', $eBankAccount['description'] ? encode($eBankAccount['description']) : '<i>'.s("Non défini").'</i>');
							} else {
								$h .= encode($eBankAccount['description']);
							}
						$h .= '</td>';
						$h .= '<td class="highlight-stick-both">';
							if($eBankAccount['account']->notEmpty()) {

								if($eBankAccount['account']->acceptQuickUpdate('class')) {
									$eBankAccount['account']->setQuickAttribute('farm', $eFarm['id']);
									$eBankAccount['account']->setQuickAttribute('property', 'class');
									$h .= $eBankAccount['account']->quick('class', encode($eBankAccount['account']['class']));
								} else {
									$h .= encode($eBankAccount['account']['class']);
								}

							} else {
								$h .= '-';
							}
						$h .= '</td>';
						$h .= '<td class="highlight-stick-both">';
							if($eBankAccount['account']->notEmpty()) {

								if($eBankAccount['account']->acceptQuickUpdate('description')) {
									$eBankAccount['account']->setQuickAttribute('farm', $eFarm['id']);
									$eBankAccount['account']->setQuickAttribute('property', 'description');
									$h .= $eBankAccount['account']->quick('description', encode($eBankAccount['account']['description']));
								} else {
									$h .= encode($eBankAccount['account']['description']);
								}

							} else {
								$h .= '-';
							}
						$h .= '</td>';
						$h .= '<td class="text-center">';
							if($eBankAccount['account']->notEmpty()) {
								$h .= '<a href="'.\farm\FarmUi::urlFinancialYear().'/banque/operations?bankAccount='.$eBankAccount['id'].'">'.$eBankAccount['nCashflow'].'</a>';
							} else {
								$h .= '-';
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a class="dropdown-toggle btn btn-secondary" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								if($eBankAccount['account']->notEmpty()) {
									$h .= '<a href="'.\farm\FarmUi::urlFinancialYear().'/account/account?classPrefix='.$eBankAccount['account']['class'].'&description=&vatFilter=0&customFilter=0" class="dropdown-item">';
										$h .= s("Voir le numéro de compte");
									$h .= '</a>';
								}
							if($eBankAccount->acceptDelete()) {
								$confirm = s("Confirmez-vous la suppression du compte bancaire {value} ?", $eBankAccount['description']);
								if($eBankAccount['nCashflow'] > 0) {
									$confirm .= ' '.s("Toutes les opérations bancaires et les imports liés seront également supprimés.");
								}
								$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/account:doDelete" post-id="'.$eBankAccount['id'].'" data-confirm="'.$confirm.'" class="dropdown-item">'.s("Supprimer ce compte bancaire").'</a>';
							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, BankAccount $eBankAccount): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlFinancialYear().'/bank/account:doCreate');

			$h .= $form->dynamicGroups($eBankAccount, ['description*']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter le compte bancaire"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bank-account-create',
			title: s("Ajouter un compte bancaire"),
			body: $h,
		);

	}

	public function getDefaultName(string $class): string {
		return s("Compte bancaire {value}", trim($class, '0'));
	}
	public function getUnknownName(): string {
		return s("Compte bancaire");
	}

	public static function p(string $property): \PropertyDescriber {

		$d = BankAccount::model()->describer($property, [
			'description' => s("Nom du compte"),
		]);

		switch($property) {

			case 'description' :
				$d->placeholder = s("Crédit Agricole");
				break;

		}
		return $d;

	}

}
?>
