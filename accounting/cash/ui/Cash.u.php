<?php
namespace cash;

class CashUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');

	}

	public function getSearch(Register $eRegister, \Search $search): string {

		$h = '<div id="cash-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlCash($eRegister);

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Mouvement").'</legend>';
					$h .= $form->select('type', [Cash::DEBIT => s("Débit"), Cash::CREDIT => s("Crédit")], $search->get('type'));
				$h .= '</fieldset>';
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getChoice(Register $eRegister): string {

		$h = '<div class="util-block">';

			$h .= '<h3>'.s("Saisir une opération de caisse").'</h3>';
			$h .= '<p>'.s("Les opérations de caisses sont obligatoirement saisies de manière chronologique.").'</p>';

			$h .= '<div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start">'.\Asset::icon('database-add').' '.s("Créditer la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('bank').'  '.s("Banque").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Retrait à la banque pour créditer la caisse").'</a>';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('wallet').'  '.s("Ventes").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Règlement d'une vente").'</a>';
					$h .= '<a href="" class="dropdown-item">'.s("Règlement d'une facture").'</a>';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('person-fill').'  '.s("Exploitant").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Apport de l'exploitant à la caisse").'</a>';
				$h .= '</div>';
				$h .= '  ';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start">'.\Asset::icon('database-dash').' '.s("Débiter la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('bank').'  '.s("Banque").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Dépôt à la banque depuis la caisse").'</a>';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('wallet').'  '.s("Achats").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Achat réglé avec la caisse").'</a>';
					$h .= '<div class="dropdown-subtitle">'.\Asset::icon('person-fill').'  '.s("Exploitant").'</div>';
					$h .= '<a href="" class="dropdown-item">'.s("Prélèvement par l'exploitant dans la caisse").'</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<br/>';
			$h .= '<h3>'.s("Opérations automatiquement trouvées depuis le XXX").'</h3>';

		$h .= '</div>';

		return $h;

	}

	public function getList(\Collection $cCash, \Search $search = new \Search(), ?int $page = NULL) {

		if($cCash->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune opération à afficher.").'</div>';
		}

		$h = '<table class="cash-item-table tr-even stick-xs" data-batch="#batch-cash">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>';
						$h .= s("Opération");
					$h .= '</th>';
					$h .= '<th>';
						$h .= s("Libellé");
					$h .= '</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("Crédit").'</th>';
					$h .= '<th class="text-end highlight-stick-left">'.s("Débit").'</th>';
					$h .= '<th class="text-center" colspan="2">'.s("TVA").'</th>';
					$h .= '<th class="text-end">'.s("Solde").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				$columns = 5;
				$previousSubtitle = NULL;

				foreach($cCash as $eCash) {

					$currentSubtitle = $eCash['date'];

					if($currentSubtitle !== $previousSubtitle) {

						if($previousSubtitle !== NULL) {
							$h .= '</tbody>';
							$h .= '<tbody>';
						}

								$h .= '<tr class="tr-title">';
									$h .= '<td colspan="2">';
										$h .= \util\DateUi::textual($currentSubtitle);
									$h .= '</td>';
									$h .= '<td class="text-end highlight-stick-right"></td>';
									$h .= '<td class="text-end highlight-stick-left"></td>';
									$h .= '<th colspan="4"></th>';
								$h .= '</tr>';
							$h .= '</tbody>';
							$h .= '<tbody>';

						$previousSubtitle = $currentSubtitle;

					}

					$h .= '<tr>';

						$h .= '<td class="td-min-content">';

							switch($eCash['origin']) {

								case Cash::BALANCE_INITIAL :
									$h .= '';
									break;

								default :
									throw new \Exception();

							}

						$h .= '</td>';

						$h .= '<td>';
							$h .= encode($eCash['description']);
						$h .= '</td>';

						$h .= '<td class="td-min-content highlight-stick-right text-end">';
							if($eCash['type'] === Cash::CREDIT) {
								$h .= \util\TextUi::money($eCash['amountIncludingVat']);
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content highlight-stick-left text-end">';
							if($eCash['type'] === Cash::DEBIT) {
								$h .= \util\TextUi::money($eCash['amountIncludingVat']);
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content text-end">';
							if($eCash['vat'] !== NULL) {
								$h .= \util\TextUi::money($eCash['vat']);
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content cash-item-vat-rate">';
							if($eCash['vatRate'] !== NULL) {
								$h .= s("({value} %)", $eCash['vatRate']);
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content text-end">';
							if($eCash['balance'] !== NULL) {
								$h .= \util\TextUi::money($eCash['balance']);
							}
						$h .= '</td>';

						$h .= '<td>';
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		if($cCash->getFound() !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $cCash->getFound() / 100);
		}

		return $h;

	}

	public static function getInitial(): string {
		return s("Solde initial de la caisse");
	}

	public function start(Register $eRegister): string {

		$eCash = new Cash();

		$h = '<h3>'.s("Indiquez le solde initial de la caisse").'</h3>';

			$form = new \util\FormUi();

			$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doCreate');
				$h .= $form->hidden('origin', Cash::BALANCE_INITIAL);
				$h .= $form->hidden('register', $eRegister['id']);
				$h .= $form->group(
					s("Date du solde initial"),
					$form->dynamicField($eCash, 'date')
				);
				$h .= $form->group(
					s("Solde initial"),
					$form->dynamicField($eCash, 'amountIncludingVat')
				);
				$h .= $form->group(content: $form->submit(s("Valider le solde initial", ['onclick' => s("Vous ne pourrez pas modifier votre choix. Valider ce solde initial ?")])));
			$h .= $form->close();

		return $h;

	}

	public function create(Cash $eCash): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('register', $eCash['register']);

			$h .= $form->group(
				s("Journal de caisse"),
				RegisterUi::getName($eCash['register'])
			);

			$h .= $form->group(
				content: $form->submit(s("Ajouter l'opération"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-cash-create',
			title: s("Ajouter une opération"),
			body: $h
		);

	}

	public function update(Cash $eCash): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doUpdate');

			$h .= $form->hidden('id', $eCash['id']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-cash-update',
			title: s("Modifier une opération"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Cash::model()->describer($property, [
			'amountIncludingVat' => s("Montant")
		]);

		switch($property) {


			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Cash $eCash) => $form->addon(s("€"));
				break;

		}

		return $d;

	}

}
?>
