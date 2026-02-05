<?php
namespace cash;

class CashUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');
		\Asset::js('cash', 'cash.js');

		\Asset::js('farm', 'farm.js');

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
					$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:create?register='.$eRegister['id'].'&source='.Cash::PRIVATE.'&type='.Cash::CREDIT.'" class="dropdown-item">'.self::getOperation(Cash::PRIVATE, Cash::CREDIT).'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('three-dots').'  '.s("Autre opération créditrice").'</a>';
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-minus').'</div>'.s("Débiter la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Débiter la caisse").'</div>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('bank').'  '.s("Dépôt à la banque").'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('wallet').'  '.s("Achat à un fournisseur").'</a>';
					$h .= '<a href="" class="dropdown-item">'.self::getOperation(Cash::PRIVATE, Cash::DEBIT).'</a>';
					$h .= '<a href="" class="dropdown-item">'.\Asset::icon('three-dots').'  '.s("Autre opération débitrice").'</a>';
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('plus-slash-minus').'</div>'.s("Corriger le solde").'</a>';
			$h .= '</div>';

			$h .= '<br/>';
			$h .= '<h3>'.s("Opérations automatiquement trouvées depuis le XXX").'</h3>';

		$h .= '</div>';

		return $h;

	}

	public static function getOperation(string $source, string $type): string {

		return match($source) {

			Cash::INITIAL => s("Solde initial de la caisse"),

			Cash::PRIVATE => match($type) {
				Cash::CREDIT => \Asset::icon('person-fill').'  '.s("Apport de l'exploitant à la caisse"),
				Cash::DEBIT => \Asset::icon('person-fill').'  '.s("Prélèvement par l'exploitant dans la caisse"),
			}

		};

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

						$h .= '<td>';

							$h .= CashUi::getOperation($eCash['source'], $eCash['type']);

							if($eCash['status'] === Cash::DRAFT) {
								$h .= '<span class="util-badge bg-muted ml-1">'.s("Brouillon").'</span>';
							}

							$h .= '<div class="ml-3">'.$this->getDetails($eCash).'</div>';
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

	protected function getDetails(Cash $eCash): string {

		$list = [];

		if($eCash->requireAssociateAccount()) {
			$list[] = encode($eCash['account']['name']);
		}

		if($eCash['description'] !== NULL) {
			$list[] = encode($eCash['description']);
		}

		return implode(' | ', $list);

	}

	public function start(Register $eRegister): string {

		$eCash = new Cash();

		$h = '<h3>'.s("Indiquez le solde initial de la caisse").'</h3>';

			$form = new \util\FormUi();

			$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doCreate');
				$h .= $form->hidden('source', Cash::INITIAL);
				$h .= $form->hidden('register', $eRegister['id']);
				$h .= $form->hidden('type', Cash::CREDIT);
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

		$eCash->expects(['source', 'register']);

		$eRegister = $eCash['register'];

		$form = new \util\FormUi();

		$h = '';

		$h .= ($eCash['date'] === NULL) ?
			$form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:create', ['method' => 'get']) :
			$form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('register', $eRegister);
			$h .= $form->hidden('source', $eCash['source']);
			$h .= $form->hidden('type', $eCash['type']);

			$h .= $form->group(
				s("Opération"),
				self::getOperation($eCash['source'], $eCash['type'])
			);

			if($eCash['date'] === NULL) {

				$dates = $form->inputGroup(
					$form->dynamicField($eCash, 'date').
					$form->submit(s("Valider"))
				);

				$dates .= '<fieldset class="mt-1">';
					$dates .= '<legend>'.s("Utiliser un raccourci").'</legend>';

					for($day = 0; $day < 7; $day++) {

						$time = time() - $day * 86400;
						$date = date('Y-m-d', $time);
						$dayName = \util\DateUi::getDayName(date('N', strtotime($date)));

						$dates .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'date', $date).'" class="btn btn-sm btn-outline-primary">';

							$dates .= match($day) {
								0 => s("Aujourd'hui"),
								default => $dayName.' '.\util\DateUi::numeric($date, \util\DateUi::DAY_MONTH)
							};

						$dates .= '</a> ';

					}

				$dates .= '</fieldset>';

				$h .= $form->group(
					self::p('date')->label,
					$dates
				);

			} else {

				$h .= $form->hidden('date', $eCash['date']);

				$h .= $form->group(
					self::p('date')->label,
					$form->inputGroup(
						$form->addon(\util\DateUi::numeric($eCash['date'])).
						'<a href="'.\util\HttpUi::removeArgument(LIME_REQUEST, 'date').'" class="btn btn-outline-primary">'.s("Modifier").'</a>'
					)
				);

				$h .= $this->getFields($form, $eCash);

				$h .= $form->group(
					content: $form->submit(s("Ajouter l'opération"))
				);

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-cash-create',
			title: match($eCash['type']) {
				Cash::CREDIT => s("Créditer le journal de caisse {value}", RegisterUi::getBadge($eRegister)),
				Cash::DEBIT => s("Débiter le journal de caisse {value}", RegisterUi::getBadge($eRegister))
			},
			body: $h
		);

	}

	public function getFields(\util\FormUi $form, Cash $eCash): string {

		$h = '';

		switch($eCash['source']) {

			case Cash::PRIVATE :

				$h .= $form->dynamicGroup($eCash, 'amountIncludingVat');

				if($eCash->requireAssociateAccount()) {

					$label = s("Numéro de compte associé");

					if($eCash['cAccount']->notEmpty()) {

						$label .= \util\FormUi::info(s("Vous pouvez ajouter les associés manquants depuis le <link>paramétrage des numéros de compte</link>.", ['link' => '<a href="'.\farm\FarmUi::urlConnected().'/account/account">']));

						$field = $form->radios('account', $eCash['cAccount'], attributes: [
							'mandatory' => TRUE,
							'callbackRadioContent' => fn($eAccount) => $eAccount['name']
						]);

					} else {
						$field = '<div class="util-block-info">';
							$field .= '<h3>'.s("Vous n'avez pas enregistré de compte associé").'</h3>';
							$field .= '<p>'.s("Vous devez enregistrer au moins un compte associé pour saisir une opération de caisse en lien avec un apport ou un prélèvement de l'exploitant.").'</p>';
							$field .= '<a href="'.\farm\FarmUi::urlConnected().'/account/account" class="btn btn-transparent">'.s("Paramétrer mes numéros de compte").'</a>';
						$field .= '</div>';
					}

					$h .= $form->group(
						$label,
						$field,
						['wrapper' => 'account']
					);

				}

				$h .= $form->dynamicGroup($eCash, 'description');

				break;

		}

		return $h;

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
			'date' => s("Date de l'opération"),
			'amountIncludingVat' => s("Montant"),
			'description' => s("Motif")
		]);

		switch($property) {

			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Cash $eCash) => $form->addon(s("€"));
				break;

			case 'description' :
				$d->placeholder = fn(Cash $eCash) => $eCash->requireDescription() ? s("Saisissez le motif de l'opération") : '';
				break;

		}

		return $d;

	}

}
?>
