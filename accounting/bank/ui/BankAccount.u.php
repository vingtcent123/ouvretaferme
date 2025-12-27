<?php
namespace bank;

class BankAccountUi {

	public function __construct() {
	}

	public function getAccountTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
					$h .= s("Les comptes bancaires");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function list(\farm\Farm $eFarm ,\Collection $cBankAccount): string {

		if($cBankAccount->empty()) {
			return '<div class="util-info">'.s("Aucun compte bancaire n'a encore été enregistré. Lorsque vous effectuerez votre premier import de relevé bancaire, le compte bancaire rattaché sera automatiquement créé.").'</div>';
		}

		$canUpdate = $eFarm->canManage();

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		$h = '';

		if($canUpdate === TRUE) {

			$h .= '<div class="util-info">'.s("Les comptes bancaires se créent automatiquement lors de l'import d'un relevé. Vous pouvez modifier le libellé du compte").'</div>';

			$h .= '<div class="util-warning-outline">'.s("Attention, en <b>modifiant le libellé d'un compte bancaire</b>, toutes les écritures comptables de l'exercice comptable en cours qui sont liées à ce numéro de compte bancaire verront leur libellé de compte être mis à jour.").'</div>';

		}

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("N° banque").'</th>';
						$h .= '<th>'.s("N° Compte bancaire").'</th>';
						$h .= '<th>'.s("N° Compte comptable").'</th>';
						$h .= '<th>'.s("Nom du compte").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cBankAccount as $eBankAccount) {

					$h .= '<tr>';

						$h .= '<td>'.encode($eBankAccount['bankId']).'</td>';
						$h .= '<td>'.encode($eBankAccount['accountId']).'</td>';
						$h .= '<td>';
							if($canUpdate === TRUE) {
								$eBankAccount->setQuickAttribute('farm', $eFarm['id']);
								$h .= $eBankAccount->quick('label', $eBankAccount['label'] ? encode($eBankAccount['label']) : '<i>'.\account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL.'&nbsp;'.s("(Par défaut)").'</i>');
							} else {
								$h .= encode($eBankAccount['label']);
							}
						$h .= '</td>';
						$h .= '<td>';
							if($canUpdate === TRUE) {
								$eBankAccount->setQuickAttribute('farm', $eFarm['id']);
								$h .= $eBankAccount->quick('description', $eBankAccount['description'] ? encode($eBankAccount['description']) : '<i>'.s("Non défini").'</i>');
							} else {
								$h .= encode($eBankAccount['label']);
							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '<tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getDefaultName(BankAccount $eBankAccount): string {
		return s("Compte bancaire {value}", trim($eBankAccount['label'], '0'));
	}
	public function getUnknownName(): string {
		return s("Compte bancaire");
	}

	public static function p(string $property): \PropertyDescriber {

		$d = BankAccount::model()->describer($property, [
			'label' => s("N° Compte comptable"),
			'description' => s("Nom du compte"),
		]);

		return $d;

	}

}
?>
