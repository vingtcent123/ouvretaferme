<?php
namespace receipts;

class LineUi {

	public function __construct() {

		\Asset::css('receipts', 'receipts.css');
		\Asset::js('receipts', 'receipts.js');

		\Asset::js('farm', 'farm.js');

	}

	public function getSearch(Book $eBook, \Search $search): string {

		$h = '<div id="line-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlReceipts();

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Mouvement").'</legend>';
					$h .= $form->select('type', self::p('type')->values, $search->get('type'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Opération").'</legend>';
					$h .= $form->select('source', [
						'balance' => s("Écart de caisse"),
						'private' => s("Apport au prélèvement de l'exploitant dans la caisse"),
						'bank' => s("Retrait ou dépôt à la banque"),
						'buy' => s("Achat à un fournisseur"),
						'sell' => s("Vente à un client"),
						'other' => s("Autre opération"),
					], $search->get('source'));
				$h .= '</fieldset>';
				if($eBook['hasAccounts']) {
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Numéros de compte").'</legend>';
						$h .= $form->select('account', [
							'without' => s("Non renseignés"),
							'with' => s("Renseignés"),
						], $search->get('account'));
					$h .= '</fieldset>';
				}
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getChoice(Book $eBook, \Collection $cCashflow, \Collection $cInvoice, \Collection $cSale): string {

		$eLine = new Line([
			'book' => $eBook
		]);

		if($eLine->acceptCreate() === FALSE) {

			if($eBook['status'] !== Book::ACTIVE) {

				$h = '<div class="util-block-info">';
					$h .= '<h3>'.s("Ce livre des recettes est désactivé").'</h3>';
					$h .= '<p>'.s("Vous ne pouvez pas ajouter de nouvelles opérations.").'</p>';
				$h .= '</div>';

				return $h;

			} else if($eBook['pending?']('draft') >= ReceiptsSetting::DRAFT_LIMIT) {

				$h = '<div class="util-block-info">';
					$h .= '<h3>'.s("Vous ne pouvez plus saisir de nouvelle opération").'</h3>';
					$h .= '<p>'.s("Vous ne pouvez pas avoir plus de {value} opérations non validées simultanément.<br/>Veuillez valider certaines opérations afin de pouvoir saisir une nouvelle opération de caisse.", ReceiptsSetting::DRAFT_LIMIT).'</p>';
				$h .= '</div>';

				return $h;

			} else if($eBook['pending?']('balance') > 0) {

				$h = '<div class="util-block-info">';
					$h .= '<h3>'.s("Contrôlez et validez le nouveau solde").'</h3>';
					$h .= '<p>'.s("Valider maintenant le nouveau solde de votre caisse avant de pouvoir saisir de nouvelles opérations.").'</p>';
				$h .= '</div>';

				return $h;

			} else {
				return '';
			}

		}

		$h = '';

		if($eBook['operations'] === 1) {
			$h .= '<div class="util-block-info">';
				$h .= '<h4>'.s("Bienvenue sur votre nouveau livre des recettes").'</h4>';
				$h .= '<p>'.s("Ce journal vous permet de répondre à une obligation légale de traçabilité des espèces et est par conséquent soumis à des contraintes réglementaires d’inaltérabilité, de sécurisation, de conservation et d’archivage des données. Nous vous conseillons d'être rigoureux dans la saisie de vos données pour qu'elles reflètent précisément la situation de votre ferme.").'</p>';
				$h .= '<p>'.s("Notez bien qu'une fois validée, une opération de caisse devient inaltérable et ne peut donc plus être modifiée.").'</p>';
			$h .= '</div>';
		}

		$h .= '<div class="util-block stick-xs">';

			$h .= '<h3>'.s("Saisir une opération de caisse").'</h3>';

			$h .= '<div class="line-actions">';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-plus').'</div>'.s("Créditer la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Créditer la caisse").'</div>';
					foreach([Line::BANK_MANUAL, Line::SELL_MANUAL, Line::PRIVATE, Line::OTHER] as $source) {
						if($eBook->acceptOperation($source, Line::CREDIT)) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/receipts/line:create?book='.$eBook['id'].'&source='.$source.'&type='.Line::CREDIT.'" class="dropdown-item">'.self::getOperation($source, Line::CREDIT).'</a>';
						}
					}
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-minus').'</div>'.s("Débiter la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Débiter la caisse").'</div>';
					foreach([Line::BANK_MANUAL, Line::BUY_MANUAL, Line::PRIVATE, Line::OTHER] as $source) {
						if($eBook->acceptOperation($source, Line::DEBIT)) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/receipts/line:create?book='.$eBook['id'].'&source='.$source.'&type='.Line::DEBIT.'" class="dropdown-item">'.self::getOperation($source, Line::DEBIT).'</a>';
						}
					}
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public static function getName(Line $eLine): string {

		return s("Caisse n°{book}, opération n°{line}", ['book' => $eLine['book']['id'], 'line' => $eLine['position']]);

	}

	public static function getOperation(string $source, ?string $type = NULL, \Element $e = new Line()): string {

		return match($source) {

			Line::INITIAL => s("Solde initial de la caisse"),
			Line::BALANCE => \Asset::icon('plus-slash-minus').'  '.s("Écart de caisse"),

			Line::PRIVATE => match($type) {
				Line::CREDIT => \Asset::icon('person-fill').'  '.s("Apport de l'exploitant à la caisse"),
				Line::DEBIT => \Asset::icon('person-fill').'  '.s("Prélèvement par l'exploitant dans la caisse"),
			},

			Line::BANK_MANUAL, Line::BANK_CASHFLOW => match($type) {
				Line::CREDIT => \Asset::icon('bank').'  '.s("Retrait depuis la banque"),
				Line::DEBIT => \Asset::icon('bank').'  '.s("Dépôt à la banque"),
			},

			Line::OTHER => match($type) {
				Line::CREDIT => \Asset::icon('three-dots').'  '.s("Autre opération créditrice"),
				Line::DEBIT => \Asset::icon('three-dots').'  '.s("Autre opération débitrice"),
			},

			Line::BUY_MANUAL => \Asset::icon('wallet').'  '.s("Achat à un fournisseur"),
			Line::SELL_MANUAL => \Asset::icon('wallet').'  '.s("Vente à un client"),
			Line::SELL_INVOICE => \Asset::icon('wallet').'  <u class="mr-1">'.encode($e['customer']->getName()).'</u><a href="'.\farm\FarmUi::urlSellingInvoices(\farm\Farm::getConnected()).'?invoice='.$e['invoice']['id'].'" class="btn btn-outline-primary btn-xs">'.\selling\InvoiceUi::getName($e['invoice']).'</a>',
			Line::SELL_SALE => \Asset::icon('wallet').'  <u class="mr-1">'.encode($e['customer']->getName()).'</u><a href="'.\selling\SaleUi::url($e['sale']).'" class="btn btn-outline-primary btn-xs">'.\selling\SaleUi::getName($e['sale']).'</a>'

		};

	}

	public function getList(Book $eBook, \Collection $ccLine, \Search $search, ?int $page = NULL) {

		if($ccLine->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune opération à afficher.").'</div>';
		}

		$h = '<div class="util-overflow-md">';
			$h .= '<table class="line-item-table tr-even">';

			$hasVat = $ccLine->contains(fn($cLine) => $cLine->contains(fn($eLine) => $eLine['vat'] !== NULL));

			foreach($ccLine as $status => $cLine) {

				$eLineLast = $cLine->first();
				$columns = 5 + ($hasVat ? 2 : 0) + ($search->empty() ? 1 : 0);

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<td colspan="'.$columns.'" style="padding: 0">';
							$h .= '<div class="util-title">';
								$h .= '<h2 class="mt-2">';
									$h .= match($status) {
										Line::DRAFT => s("Brouillard de caisse").' <span class="util-counter">'.$cLine->count().'</span>',
										Line::VALID => s("Livre des recettes"),
									};
								$h .= '</h2>';

								switch($status) {

									case Line::DRAFT :

										if($eLineLast['balanceNegative'] === FALSE) {
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/line:doValidate" post-id="'.$eLineLast['id'].'" data-confirm="'.s("Toutes les opérations seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eLineLast['date'])).'" class="btn btn-secondary">'.s("Tout valider maintenant").'</a>';
										}

										break;

								}

							$h .= '</div>';

							switch($status) {

								case Line::DRAFT :

									$h .= '<div class="util-info">'.s("Les opérations du brouillard de caisse peuvent être modifiées jusqu'à leur validation. Une fois validée, une opération devient inaltérable et vous ne pouvez plus en ajouter antérieurement.").'</div>';

									if($eLineLast['balanceNegative']) {
										$h .= '<div class="util-block-danger">'.\Asset::icon('exclamation-circle').' '.s("Le solde de votre livre des recettes doit toujours être positif. </h3>Veuillez corriger vos saisies afin de pouvoir valider vos opérations.").'</div>';
									}

									break;

								case Line::VALID :

									if($eBook['closedAt'] !== NULL) {

										if($eBook['operations'] > 1) {

											$h .= '<div class="util-block bg-primary color-white">';
												$h .= \Asset::icon('lock-fill').'  '.s("Votre livre des recettes est actuellement clôturé au {closed}, la saisie de nouvelles opérations est possible à partir du {open}.", [
													'closed' => \util\DateUi::numeric($eBook['closedAt']),
													'open' => \util\DateUi::numeric(date('Y-m-d', strtotime($eBook['closedAt'].' + 1 DAY'))),
												]);

												if($eBook->acceptDelete()) {
													$h .= '<br/>'.\Asset::icon('exclamation-circle').'  '.s("Si vous avez fait une erreur, vous pouvez supprimer votre livre des recettes tant qu'il contient moins de {value} opérations le temps de vous familiariser avec cette fonctionnalité.", ReceiptsSetting::DELETE_LIMIT);
												}

												if($eBook->acceptClose()) {

													$closeDate = $eBook->getCloseDate();

													if($closeDate !== NULL) {

														$h .= '<div class="mt-1">';
															$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/book:doClose" post-id="'.$eBook['id'].'" post-date="'.$closeDate.'" class="btn btn-transparent" data-confirm="'.s("ATTENTION !\nLa clôture est définitive, et vous ne pourrez ajouter, modifier ou supprimer d'opération jusqu'au {value}. Voulez-vous continuer ?", \util\DateUi::numeric($closeDate)).'">';
																$h .= \Asset::icon('calendar-month').'  ';
																$h .= s("Clôturer le journal au {value}", \util\DateUi::textual($closeDate));
															$h .= '</a>';
														$h .= '</div>';

													}

												}

											$h .= '</div>';

										}

									}

									break;

							}

						$h .= '</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th colspan="2"></th>';
						$h .= '<th class="text-end t-highlight">'.s("Crédit").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Débit").'</th>';

						if($hasVat) {
							$h .= '<th class="text-center" colspan="2">'.s("TVA").'</th>';
						}

						if($search->empty()) {

							$h .= '<th colspan="2">';
								$h .= match($status) {
									Line::DRAFT => s("Solde théorique"),
									Line::VALID => s("Solde"),
								};
							$h .= '</th>';

						} else {
							$h .= '<th></th>';
						}

					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					$previousSubtitle = NULL;

					foreach($cLine as $eLine) {

						$currentSubtitle = $eLine['date'];

						if($currentSubtitle !== $previousSubtitle) {

							if($previousSubtitle !== NULL) {
								$h .= '</tbody>';
								$h .= '<tbody>';
							}

									$h .= '<tr class="tr-title">';
										$h .= '<td colspan="2">';
											$h .= \util\DateUi::textual($currentSubtitle);
										$h .= '</td>';
										$h .= '<td class="text-end t-highlight"></td>';
										$h .= '<td class="text-end t-highlight"></td>';
										$h .= '<td colspan="'.($columns - 3).'"></th>';
									$h .= '</tr>';
								$h .= '</tbody>';
								$h .= '<tbody>';

							$previousSubtitle = $currentSubtitle;

						}

						$h .= '<tr'.(GET('position', 'int') === $eLine['position'] ? ' class="row-highlight"' : '').'>';

							$h .= '<td class="td-min-content text-end td-vertical-align-top">';
								if($eLine['position'] !== NULL) {
									$h .= '<div class="btn btn-outline-primary btn-readonly btn-xs">'.$eLine['position'].'</div>';
								}
							$h .= '</td>';

							$h .= '<td>';

								$h .= LineUi::getOperation($eLine['source'], $eLine['type'], $eLine);

								if($eLine['status'] === Line::DRAFT) {
									$h .= '<span class="util-badge bg-muted ml-1">'.s("Non validé").'</span>';
								}

								$h .= '<div class="line-item-details">'.$this->getDetails($eLine).'</div>';

								if(
									$eLine->offsetExists('cSaleMarket') and
									$eLine['cSaleMarket']->notEmpty()
								) {

									$h .= '<div class="line-item-children">';
										$h .= '<span>'.s("Vente supérieures à {value} € :", ReceiptsSetting::AMOUNT_THRESHOLD).'</span>';
										foreach($eLine['cSaleMarket'] as $eSale) {
											$h .= \selling\SaleUi::link($eSale, size: 'btn-xs');
										}
									$h .= '</div>';
								}

							$h .= '</td>';

							$h .= '<td class="td-min-content t-highlight text-end">';
								if($eLine['type'] === Line::CREDIT) {
									$h .= \util\TextUi::money($eLine['amountIncludingVat']);
								}
							$h .= '</td>';

							$h .= '<td class="td-min-content t-highlight text-end">';
								if($eLine['type'] === Line::DEBIT) {
									$h .= \util\TextUi::money($eLine['amountIncludingVat']);
								}
							$h .= '</td>';

							if($hasVat) {

								$h .= '<td class="td-min-content text-end">';
									if($eLine['vat'] !== NULL) {
										$h .= \util\TextUi::money($eLine['vat']);
									}
								$h .= '</td>';

								$h .= '<td class="td-min-content font-sm color-muted">';
									if($eLine['vatRate'] !== NULL) {
										$h .= ' '.s("{value} %", $eLine['vatRate']);
									}
								$h .= '</td>';

							}

							if($search->empty()) {

								$h .= '<td class="td-min-content line-item-balance">';

									if($eLine['balance'] !== NULL) {

										$balance = \util\TextUi::money($eLine['balance']);

										$h .= match($eLine['status']) {
											Line::DRAFT => '<span class="'.($eLine['balanceNegative'] ? 'util-badge bg-danger' : 'line-item-balance-waiting').'">'.$balance.'</span>',
											Line::VALID => $balance
										};

									}

								$h .= '</td>';

							}

							$h .= '<td class="text-end">';

								switch($status) {

									case Line::DRAFT :
										$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
										$h .= '<div class="dropdown-list">';
											$h .= '<div class="dropdown-title">'.s("Opération de caisse").'</div>';
											if($eLine->acceptUpdate()) {
												$h .= '<a href="'.\farm\FarmUi::urlConnected().'/receipts/line:update?id='.$eLine['id'].'" class="dropdown-item">'.s("Modifier l'opération").'</a>';
											}
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/line:doValidate" post-id="'.$eLine['id'].'" data-confirm="'.s("Cette opération ainsi que toutes les opérations antérieures seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eLineLast['date'])).'" class="dropdown-item '.($eLine['balanceNegative'] ? 'disabled' : '').'">'.s("Valider les opérations jusqu'à celle-ci").'</a>';
											$h .= '<div class="dropdown-divider"></div>';
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/line:doDelete" data-confirm="'.s("Vous allez supprimer cette opération. Continuer ?").'" post-id="'.$eLine['id'].'" class="dropdown-item">'.s("Supprimer l'opération").'</a>';
										$h .= '</div>';
										break;

									case Line::VALID :
										if($eLine->acceptUpdate()) {
											$h .= '<a href="'.\farm\FarmUi::urlConnected().'/receipts/line:update?id='.$eLine['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
										}
										break;

								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			}

			$h .= '</table>';
		$h .= '</div>';

		if($cLine->getFound() !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $cLine->getFound() / 100);
		}

		return $h;

	}

	protected function getDetails(Line $eLine): string {

		$list = [];

		if($eLine['account']->notEmpty()) {
			$list[] = encode($eLine['account']['name']);
		}

		if(
			$eLine['description'] !== NULL and
			in_array($eLine['source'], [Line::SELL_SALE, Line::SELL_INVOICE]) === FALSE
		) {
			$list[] = encode($eLine['description']);
		}

		return implode(' | ', $list);

	}

	public function start(Book $eBook): string {

		$eLine = new Line([
			'source' => Line::INITIAL
		]);

		$h = '<h3>'.s("Indiquez le solde initial de la caisse").'</h3>';

			$h .= '<div class="util-block-info">';
				$h .= '<p>'.s("Le solde initial marque le point de départ de votre caisse. Choisissez bien la date du solide initial car toutes les opérations que vous enregistrerez ultérieurement dans votre journal devront être postérieures à cette date.").'</p>';
				$h .= '<p>'.s("Votre livre des recettes peut commencer au plus tôt le {value}.", \util\DateUi::numeric(date('Y-01-01'))).'</p>';
			$h .= '</div>';

			$form = new \util\FormUi();

			$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/receipts/line:doCreate');
				$h .= $form->hidden('source', Line::INITIAL);
				$h .= $form->hidden('book', $eBook['id']);
				$h .= $form->hidden('type', Line::CREDIT);
				$h .= $form->group(
					s("Date du solde initial"),
					$form->dynamicField($eLine, 'date')
				);
				$h .= $form->group(
					s("Solde initial"),
					$form->dynamicField($eLine, 'amountIncludingVat')
				);
				$h .= $form->group(content: $form->submit(s("Valider le solde initial"), ['data-confirm' => s("Vous ne pourrez pas modifier votre choix. Valider ce solde initial ?")]));
			$h .= $form->close();

		return $h;

	}

	public function create(Line $eLine): \Panel {

		$eLine->expects(['source', 'book']);

		$eBook = $eLine['book'];

		$form = new \util\FormUi();

		$h = '';

		$h .= ($eLine['date'] === NULL) ?
			$form->openAjax(\farm\FarmUi::urlConnected().'/receipts/line:create', ['method' => 'get']) :
			$form->openAjax(\farm\FarmUi::urlConnected().'/receipts/line:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('book', $eBook);
			$h .= $form->hidden('source', $eLine['source']);
			$h .= $form->hidden('type', $eLine['type']);

			$h .= $form->group(
				s("Opération"),
				self::getOperation($eLine['source'], $eLine['type'])
			);

			if($eLine['date'] === NULL) {

				$dates = $form->inputGroup(
					$form->dynamicField($eLine, 'date').
					$form->submit(s("Valider"))
				);

				$dates .= '<fieldset class="mt-1">';
					$dates .= '<legend>'.s("Utiliser un raccourci").'</legend>';

					for($day = 0; $day < 7; $day++) {

						$time = time() - $day * 86400;
						$date = date('Y-m-d', $time);

						if($eBook->isClosedByDate($date)) {
							continue;
						}

						$dayName = \util\DateUi::getDayName(date('N', strtotime($date)));

						$dates .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'date', $date).'" class="btn btn-sm btn-primary">';

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

				$h .= $form->hidden('date', $eLine['date']);

				$h .= $form->group(
					self::p('date')->label,
					$form->inputGroup(
						$form->addon(\util\DateUi::numeric($eLine['date'])).
						'<a href="'.\util\HttpUi::removeArgument(LIME_REQUEST, 'date').'" class="btn btn-outline-primary">'.s("Modifier").'</a>'
					)
				);

				$h .= $this->getFields($form, $eLine);

				$h .= $form->group(
					content: $form->submit(s("Ajouter l'opération"))
				);

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-line-create',
			title: match($eLine['type']) {
				Line::CREDIT => s("Créditer le livre des recettes {value}", BookUi::getBadge($eBook)),
				Line::DEBIT => s("Débiter le livre des recettes {value}", BookUi::getBadge($eBook))
			},
			body: $h
		);

	}

	public function getFields(\util\FormUi $form, Line $eLine): string {

		$h = '';

		$h .= $form->dynamicGroup($eLine, 'description');
		$h .= $this->getAccountsFields($form, $eLine);

		if($eLine->requireVat()) {

			$h .= '<div class="util-block bg-background-light">';
				$h .= $form->group(content: '<h4>'.s("Montants").'</h4>');
				$h .= $form->dynamicGroups($eLine, ['amountIncludingVat', 'vatRate']);
				$h .= $form->group(content: '<p class="util-empty mb-0">'.\Asset::icon('info-circle').' '.s("Les montants de TVA et HT sont automatiquement calculés lorsque vous tapez le montant TTC et le taux de TVA.").'</p>');
				$h .= $form->dynamicGroups($eLine, ['vat', 'amountExcludingVat']);
			$h .= '</div>';

		} else {
			$h .= $form->group(
				s("Montant"),
				$form->dynamicField($eLine, 'amountIncludingVat')
			);
		}

		return $h;

	}

	public function getAccountsFields(\util\FormUi $form, Line $eLine): string {

		$h = '';

		if($eLine->requireAssociateAccount()) {

			$label = s("Compte associé");

			if($eLine['cAccount']->notEmpty()) {

				$label .= \util\FormUi::info(s("Vous pouvez ajouter les associés manquants depuis le <link>paramétrage des numéros de compte</link>.", ['link' => '<a href="'.\farm\FarmUi::urlConnected().'/account/account">']));

				if(($eLine['account'] ?? new \account\Account())->notEmpty()) {
					$eLineDefault = $eLine['account'];
				} else if($eLine['cAccount']->count() === 1) {
					$eLineDefault = $eLine['cAccount']->first();
				} else {
					$eLineDefault = new \account\Account();
				}

				$field = $form->radios('account', $eLine['cAccount'], $eLineDefault, attributes: [
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

		if($eLine->requireAccount()) {
			$h .= $form->dynamicGroup($eLine, 'account');
		}

		return $h;

	}

	public function update(Line $eLine): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/receipts/line:doUpdate');

				$h .= $form->hidden('id', $eLine['id']);

				$h .= $form->group(
					s("Opération"),
					self::getOperation($eLine['source'], $eLine['type'])
				);

				$h .= $form->group(
					self::p('date')->label,
					$form->fake(\util\DateUi::numeric($eLine['date']))
				);

				switch($eLine['status']) {

					case Line::DRAFT :
						$h .= $this->getFields($form, $eLine);
						break;

					case Line::VALID :
						$h .= $this->getAccountsFields($form, $eLine);
						break;

				}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-line-update',
			title: s("Modifier une opération"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Line::model()->describer($property, [
			'date' => s("Date de l'opération"),
			'amountIncludingVat' => s("Montant TTC"),
			'amountExcludingVat' => s("Montant HT"),
			'vat' => s("Montant de TVA"),
			'vatRate' => s("Taux de TVA"),
			'description' => s("Libellé"),
			'account' => s("Numéro de compte")
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					Line::DEBIT => s("Débit"),
					Line::CREDIT => s("Crédit")
				];
				break;

			case 'description' :
				$d->placeholder = fn(Line $eLine) => $eLine->requireDescription() ? s("Saisissez le motif de l'opération") : '';
				break;

			case 'amountExcludingVat' :
			case 'vat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Line $eLine) => $form->addon(s("€"));
				break;

			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Line $eLine) => $form->addon(s("€"));
				$d->attributes = fn(\util\FormUi $form, Line $eLine) => $eLine->requireVat() ? [
					'onchange' => 'Receipts.recalculateAmount(this)'
				] : [];
				break;

			case 'vatRate' :
				$d->append = s("%");
				$d->attributes = [
					'onchange' => 'Receipts.recalculateAmount(this)'
				];
				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Line $e) {
					return [
						'query' => ['classPrefix' => '7']
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, \farm\Farm::getConnected(), query: function(Line $e) {

					return ['withVat' => TRUE] + match($e['source']) {
						Line::BANK_MANUAL => ['classPrefix' => \account\AccountSetting::BANK_ACCOUNT_CLASS],
						Line::BUY_MANUAL => ['classPrefixes[0]' => '2', 'classPrefixes[1]' => '6'],
						Line::SELL_MANUAL => ['classPrefixes[0]' => '7'],
						default => []
					};

				});
				break;

		}

		return $d;

	}

}
?>
