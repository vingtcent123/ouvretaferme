<?php
namespace account;

class LogUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
	}

	public function getTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("L'activité comptable");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cLog, int $page, int $nLog): string {

		if($cLog->empty()) {
			return '<div class="util-info">'.s("Il n'y a encore aucune activité à afficher sur votre compte.").'</div>';
		}

		$h = '<table class="tr-hover tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';
					$h .= '<th>';
						$h .= s("Date");
					$h .= '</th>';
					$h .= '<th>';
						$h .= s("Par");
					$h .= '</th>';
					$h .= '<th>';
						$h .= s("Sur");
					$h .= '</th>';
					$h .= '<th>';
						$h .= s("Action");
					$h .= '</th>';
				$h .= '</tr>';

			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cLog as $eLog) {

					[$element, $action] = $this->getAction($eLog['action'], $eLog['element'], $eLog['params']);

					$h .= '<tr>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eLog['createdAt']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= $eLog['doneBy']->getName();
						$h .= '</td>';
						$h .= '<td>';
							$h .= $element;
						$h .= '</td>';
						$h .= '<td>';
							$h .= $action;
						$h .= '</td>';
					$h .= '</tr>';
				}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= \util\TextUi::pagination($page, $nLog / AccountSetting::LOG_PER_PAGE);

		return $h;

	}

	public function getAction(string $action, string $element, array $params): array {

		// TODO DEFERRAL, letter
		return match(strtolower($element)) {
			'financialyear' => [s("Exercice comptable"), $this->getFinancialYearAction($action, $params)],
			'operation' => [s("Écriture"), $this->getOperationAction($action, $params)],
			'account' => [s("Classe de compte"), $this->getAccountAction($action, $params)],
			'dropbox' => [s("Dropbox"), $this->getDropboxAction($action, $params)],
			'bank' => [s("Compte bancaire"), $this->getBankAction($action, $params)],
			'cashflow' => [s("Flux bancaire"), $this->getCashflowAction($action, $params)],
			'stock' => [s("Stock"), $this->getStockAction($action, $params)],
		};

	}

	public function getFinancialYearAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'open' => s("Création de l'exercice #{value}", $params['id']),
			'close' => s("Clôture de l'exercice #{value}", $params['id']),
			'update' => s("Mise à jour de l'exercice #{value}", $params['id']),
			'reopen' => s("Réouverture de l'exercice #{value}", $params['id']),
			'generatefec' => s("Génération de FEC de l'exercice #{value}", $params['id']),
		};

	}

	public function getOperationAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'update' => s("Mise à jour de l'écriture #{value}", $params['id']),
			'delete' => s("Suppression de l'écriture #{value}", $params['id']),
			'unlinkcashflow' => ($params['action'] === 'delete' ? s("Dissociation de l'opération bancaire de l'écriture #{value} (avec suppression d'écritures)", $params['id']) : s("Dissociation de l'opération bancaire de l'écriture #{value}", $params['id'])),
		};

	}

	public function getAccountAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'delete' => s("Suppression d'une classe de compte : {value}", $params['class']),
		};

	}

	public function getBankAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'update' => s("Mise à jour du compte bancaire : {value}", $params['id']),
		};

	}

	public function getCashflowAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'attach' => s("Rattachement de l'opération bancaire 1{value}", $params['id']),
		};

	}

	public function getStockAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'create' => s("Création du stock #{value}", $params['id']),
			'set' => s("Enregistrement du stock #{value}", $params['id']),
			'delete' => s("Suppression du stock #{value}", $params['id']),
		};

	}

	public function getDropboxAction(string $action, array $params): string {

		return match(strtolower($action)) {
			'upload' => s("Upload du fichier sur Dropbox : {value}", $params['localFile']),
			'delete' => s("Suppression de la connexion Dropbox", $params['localFile']),
			'refreshtoken' => s("Regénération du token de connexion à Dropbox"),
			'getaccesstoken' => s("Récupération d'un token de connexion à Dropbox"),
			'revoke' => s("Suppression de la connexion à Dropbox"),
		};

	}

}
?>
