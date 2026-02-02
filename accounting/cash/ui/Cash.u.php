<?php
namespace cash;

class CashUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');
		\Asset::js('cash', 'cash.js');

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

			$h .= '<div class="cash-actions">';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-plus').'</div>'.s("Créditer la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Créditer la caisse").'</div>';
					if($eRegister['paymentMethod']['fqn'] === 'cash') {
						$h .= '<a href="" class="dropdown-item">'.\Asset::icon('bank').'  '.s("Retrait depuis la banque").'</a>';
					}
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('wallet').'  '.s("Vente à un client").'</a>';
					$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:create?register='.$eRegister['id'].'&origin='.Cash::PRIVATE.'&type='.Cash::CREDIT.'" class="dropdown-item">'.\Asset::icon('person-fill').'  '.s("Apport de l'exploitant à la caisse").'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('three-dots').'  '.s("Autre opération créditrice").'</a>';
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-minus').'</div>'.s("Débiter la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Débiter la caisse").'</div>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('bank').'  '.s("Dépôt à la banque").'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('wallet').'  '.s("Achat à un fournisseur").'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('person-fill').'  '.s("Prélèvement par l'exploitant dans la caisse").'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('three-dots').'  '.s("Autre opération débitrice").'</a>';
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('plus-slash-minus').'</div>'.s("Corriger le solde").'</a>';
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

								case Cash::INITIAL :
									$h .= '';
									break;

								default :
									throw new \Exception();

							}

						$h .= '</td>';

						$h .= '<td>';
							$h .= '<div>'.encode($eCash['description']).'</div>';
							$h .= '<div>';
								if($eCash['status'] === Cash::DRAFT) {
									$h .= '<span class="util-badge bg-muted">'.s("Brouillon").'</span>';
								}
							$h .= '</div>';
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

						$h .= '<td class="text-end">';

							if($eCash['status'] === Cash::DRAFT) {

								$h .= '<a class="btn btn-secondary" class="dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.s("Opération").'</div>';
									$h .= '<a href="" class="dropdown-item">'.s("Modifier l'opération").'</a>';
									$h .= '<a href="" class="dropdown-item">'.s("Valider l'opération").'</a>';
									$h .= '<div class="dropdown-divider"></div>';
									$h .= '<a href="" class="dropdown-item">'.s("Supprimer l'opération").'</a>';
								$h .= '</div>';

							}

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
				$h .= $form->hidden('origin', Cash::INITIAL);
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

			case 'date' :
				$d->attributes = [
					'oninput' => 'Cash.changeDate(this)'
				];
				break;

			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Cash $eCash) => $form->addon(s("€"));
				break;

		}

		return $d;

	}

}
?>
