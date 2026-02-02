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

	public function getList(\farm\Farm $eFarm, \Collection $cCash, \Collection $cCashGroup, ?int $nCash = NULL, \Search $search = new \Search(), array $hide = [], ?int $page = NULL) {

		if($cCash->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucun client à afficher.").'</div>';
		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$h = '<table class="cash-item-table tr-even stick-xs" data-batch="#batch-cash">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2" class="td-checkbox">';
						$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
							$h .= '<input type="checkbox" class="batch-all" onclick="Cash.toggleSelection(this)"/>';
						$h .= '</label>';
					$h .= '</th>';
					$h .= '<th rowspan="2" class="text-center">';
						$h .= $search->linkSort('id', s("Numéro"));
					$h .= '</th>';
					$h .= '<th rowspan="2">';
						$label = s("Prénom");
						$h .= $search->linkSort('firstName', $label).' / ';
						$label = s("Nom");
						$h .= $search->linkSort('lastName', $label);
					$h .= '</th>';
					if(in_array('sales', $hide) === FALSE) {
						$h .= '<th colspan="2" class="text-center hide-xs-down highlight">'.s("Ventes").'</th>';
					}
					if(in_array('prices', $hide) === FALSE) {
						$h .= '<th rowspan="2" class="cash-item-grid">'.s("Prixpersonnalisés").'</th>';
					}
					$h .= '<th rowspan="2" class="cash-item-contact">'.s("Contact").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					if(in_array('actions', $hide) === FALSE) {
						$h .= '<th rowspan="2"></th>';
					}
				$h .= '</tr>';
				if(in_array('sales', $hide) === FALSE) {
					$h .= '<tr>';
						$h .= '<th class="text-end hide-xs-down highlight-stick-right">'.$year.'</th>';
						$h .= '<th class="text-end hide-xs-down cash-item-year-before highlight-stick-left">'.$yearBefore.'</th>';
					$h .= '</tr>';
				}
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cCash as $eCash) {

					$batch = [];

					if($eCash['status'] === Cash::INACTIVE) {
						$batch[] = 'not-active';
					}

					if($eCash->isCollective()) {
						$batch[] = 'not-group';
					}

					if($eCash->isPrivate()) {
						$batch[] = 'not-pro';
					}

					if($eCash->isPro()) {
						$batch[] = 'not-private';
					}

					$h .= '<tr class="'.($eCash['status'] === Cash::INACTIVE ? 'tr-disabled' : '').'">';

						$h .= '<td class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eCash['id'].'" oninput="Cash.changeSelection()" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</label>';
						$h .= '</td>';

						$h .= '<td class="td-min-content text-center">';
							$h .= '<a href="/client/'.$eCash['id'].'" class="btn btn-sm btn-outline-primary">'.\encode($eCash['number']).'</a>';
						$h .= '</td>';

						$h .= '<td>';

							$h .= '<div class="cash-item-info">';
								$h .= '<div>';

									$h .= '<a href="/client/'.$eCash['id'].'">'.\encode($eCash->getName()).'</a>';
									if($eCash['user']->notEmpty()) {
										$h .= ' <span title="'.s("Ce client a un compte client à partir duquel il peut se connecter").'">'.\Asset::icon('person-circle').'</span> ';
									}
									$h .= '<div class="util-annotation">';
										$h .= self::getCategory($eCash);
										if($eCash['color']) {
											$h .= ' | '.Register::getCircle($eCash);
										}
									$h .= '</div>';

								$h .= '</div>';

								$h .= '<div class="cash-item-label">';
									if(in_array('actions', $hide) === FALSE and $eCash['invite']->notEmpty()) {
										$h .= '<span class="util-badge bg-primary">'.\Asset::icon('person-fill').' '.s("invitation envoyée").'</span> ';
									}
									$h .= $this->getGroups($eCash);
								$h .= '</div>';

							$h .= '</div>';

						$h .= '</td>';

						if(in_array('sales', $hide) === FALSE) {

							$eSaleTotal = $eCash['eSaleTotal'];

							$h .= '<td class="text-end hide-xs-down highlight-stick-right">';
								if($eSaleTotal->notEmpty() and $eSaleTotal['year']) {
									$amount = \util\TextUi::money($eSaleTotal['year'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/cash:analyze?id='.$eCash['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							$h .= '</td>';

							$h .= '<td class="text-end hide-xs-down cash-item-year-before highlight-stick-left">';
								if($eSaleTotal->notEmpty() and $eSaleTotal['yearBefore']) {
									$amount = \util\TextUi::money($eSaleTotal['yearBefore'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/cash:analyze?id='.$eCash['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						}

						if(in_array('prices', $hide) === FALSE) {

							$h .= '<td class="cash-item-grid">';
								if($eCash['prices'] > 0) {
									$h .= \p("{value} prix", "{value} prix", $eCash['prices']);
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						}

						$h .= '<td class="cash-item-contact">';
							if($eCash['contactName']) {
								$h .= '<div>';
									$h .= \Asset::icon('person-vcard').'  '.\encode($eCash['contactName']);
								$h .= '</div>';
							}
							if($eCash['email']) {
								$h .= '<div>';
									$h .= \Asset::icon('at').'  '.\encode($eCash['email']);
								$h .= '</div>';
							}
							if($eCash['phone']) {
								$h .= '<div>';
									$h .= \Asset::icon('telephone').'  '.\encode($eCash['phone']);
								$h .= '</div>';
							}
						$h .= '</td>';

						$h .= '<td class="cash-item-status td-min-content">';
							$h .= $this->toggle($eCash);
						$h .= '</td>';

						if(in_array('actions', $hide) === FALSE) {
							$h .= '<td class="cash-item-actions">';
								$h .= $this->getUpdate($eCash, 'btn-outline-secondary');
							$h .= '</td>';
						}

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		if($nCash !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nCash / 100);
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
