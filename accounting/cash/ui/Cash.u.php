<?php
namespace cash;

class CashUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');
		\Asset::js('cash', 'cash.js');

		\Asset::js('farm', 'farm.js');

	}

	public static function getFirstDate(): string {
		return date('Y-01-01', strtotime(date('Y-m-d').' - 5 years'));
	}

	public function getSearch(Register $eRegister, \Search $search): string {

		$h = '<div id="cash-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlCash($eRegister);

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
				if($eRegister['hasAccounts']) {
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

	public function getChoice(Register $eRegister, \Collection $cCashflow, \Collection $cInvoice, \Collection $cSale): string {

		$eCash = new Cash([
			'register' => $eRegister
		]);

		if($eCash->acceptCreate() === FALSE) {

			if($eRegister['status'] !== Register::ACTIVE) {

				$h = '<div class="util-block-info">';
					$h .= '<h3>'.s("Ce journal de caisse est désactivé").'</h3>';
					$h .= '<p>'.s("Vous ne pouvez pas ajouter de nouvelles opérations.").'</p>';
				$h .= '</div>';

				return $h;

			} else if($eRegister['pending?']('draft') >= CashSetting::DRAFT_LIMIT) {

				$h = '<div class="util-block-info">';
					$h .= '<h3>'.s("Vous ne pouvez plus saisir de nouvelle opération").'</h3>';
					$h .= '<p>'.s("Vous ne pouvez pas avoir plus de {value} opérations non validées simultanément.<br/>Veuillez valider certaines opérations afin de pouvoir saisir une nouvelle opération de caisse.", CashSetting::DRAFT_LIMIT).'</p>';
				$h .= '</div>';

				return $h;

			} else if($eRegister['pending?']('balance') > 0) {

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

		if($eRegister['operations'] === 1) {
			$h .= '<div class="util-block-info">';
				$h .= '<h4>'.s("Bienvenue sur votre nouveau journal de caisse").'</h4>';
				$h .= '<p>'.s("Ce journal vous permet de répondre à une obligation légale de traçabilité des mouvements financiers et est par conséquent soumis à des contraintes réglementaires d’inaltérabilité, de sécurisation, de conservation et d’archivage des données. Nous vous conseillons d'être rigoureux dans la saisie de vos données pour qu'elles reflètent précisément la situation de votre ferme.").'</p>';
				$h .= '<p>'.s("Notez bien qu'une fois validée, une opération de caisse devient inaltérable et ne peut donc plus être modifiée.").'</p>';
			$h .= '</div>';
		}

		$h .= '<div class="util-block stick-xs">';

			$h .= '<h3>'.s("Saisir une opération de caisse").'</h3>';

			$h .= '<div class="cash-actions">';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-plus').'</div>'.s("Créditer la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Créditer la caisse").'</div>';
					foreach([Cash::BANK_MANUAL, Cash::SELL_MANUAL, Cash::PRIVATE, Cash::OTHER] as $source) {
						if($eRegister->acceptOperation($source, Cash::CREDIT)) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:create?register='.$eRegister['id'].'&source='.$source.'&type='.Cash::CREDIT.'" class="dropdown-item">'.self::getOperation($source, Cash::CREDIT).'</a>';
						}
					}
				$h .= '</div>';
				$h .= '<a class="btn btn-secondary" data-dropdown="bottom-start"><div class="btn-icon">'.\Asset::icon('journal-minus').'</div>'.s("Débiter la caisse").'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Débiter la caisse").'</div>';
					foreach([Cash::BANK_MANUAL, Cash::BUY_MANUAL, Cash::PRIVATE, Cash::OTHER] as $source) {
						if($eRegister->acceptOperation($source, Cash::DEBIT)) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:create?register='.$eRegister['id'].'&source='.$source.'&type='.Cash::DEBIT.'" class="dropdown-item">'.self::getOperation($source, Cash::DEBIT).'</a>';
						}
					}
				$h .= '</div>';

				if($eRegister['paymentMethod']['fqn'] === \payment\MethodLib::CASH) {

					$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:updateBalance?id='.$eRegister['id'].'" class="btn btn-secondary '.($eRegister->acceptUpdateBalance() ? '' : 'disabled').'">';
						$h .= '<div class="btn-icon">'.\Asset::icon('plus-slash-minus').'</div>';
						$h .= '<span class="hide-xs-down">'.s("Constater un écart de caisse").'</span><span class="hide-sm-up">'.s("Constater un écart").'</span>';
						if($eRegister->acceptUpdateBalance() === FALSE) {
							$h .= '<div style="margin-top: 0.25rem" class="font-xs">'.\Asset::icon('exclamation-circle').' '.s("Le brouillard doit être vide").'</div>';
						}
					$h .= '</a>';

				}

			$h .= '</div>';

			$h .= $this->getSuggestions($eRegister, $cCashflow, $cInvoice, $cSale);

		$h .= '</div>';

		return $h;

	}

	protected static function getSuggestions(Register $eRegister, \Collection $cCashflow, \Collection $cInvoice, \Collection $cSale): string {

		$summarize = '';

		if($cCashflow->notEmpty()) {

			$summarize .= '<li>';
				$summarize .= '<h5>'.s("Banque").'</h5>';
				$summarize .= '<div>'.$cCashflow->count().'</div>';
			$summarize .= '</li>';

		}

		if($cInvoice->notEmpty()) {

			$summarize .= '<li>';
				$summarize .= '<h5>'.s("Factures").'</h5>';
				$summarize .= '<div>'.$cInvoice->count().'</div>';
			$summarize .= '</li>';

		}

		if($cSale->notEmpty()) {

			$summarize .= '<li>';
				$summarize .= '<h5>'.s("Ventes non facturées").'</h5>';
				$summarize .= '<div>'.$cSale->count().'</div>';
			$summarize .= '</li>';

		}

		if($summarize === '') {
			return '';
		}

		$h = '<br/>';
		$h .= '<h3>'.\Asset::icon('fire').' '.s("Opérations en {method} automatiquement trouvées depuis le {value}", ['method' => '<span style="text-transform: uppercase">'.encode($eRegister['paymentMethod']['name']).'</span>', 'value' => \util\DateUi::numeric($eRegister['openedSince'])]).'</h3>';

		$h .= '<ul class="util-summarize util-summarize-overflow">';
			$h .= $summarize;
		$h .= '</ul>';

		$cSuggestion = new \Collection()
			->mergeCollection($cCashflow)
			->mergeCollection($cInvoice)
			->mergeCollection($cSale)
			->sort([
				'date' => SORT_ASC,
				'id' => SORT_ASC
			]);

		if($cSuggestion->count() > 3) {

			$h .= '<div class="text-end mb-1">';
				$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doImportByMethod" post-id="'.$eRegister['id'].'" class="btn btn-outline-primary" data-confirm="'.s("Vous allez importer {value} opérations dans le journal de caisse. Continuer ?", $cSuggestion->count()).'" data-waiter="'.s("Import en cours...").'">'.s("Tout importer dans le journal").'</a> ';
				$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doIgnoreByMethod" post-id="'.$eRegister['id'].'" class="btn" data-confirm="'.s("Ces opérations ne vous seront plus jamais proposées à l'importation dans vos journaux de caisse. Continuer ?").'">'.s("Tout ignorer").'</a>';
			$h .= '</div>';

		}

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Débit").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Crédit").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cSuggestion as $eSuggestion) {

					$h .= '<tr>';
						$h .= '<td class="td-vertical-align-top">';
							$h .= \util\DateUi::numeric($eSuggestion['date']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::getOperation($eSuggestion['source'], $eSuggestion['type'], $eSuggestion).'</div>';

							if($eSuggestion['source'] === Cash::BANK_CASHFLOW) {
								$h .= '<div class="cash-auto-description">';
									$h .= \Asset::icon('arrow-return-right').'  '.encode($eSuggestion['description']);
								$h .= '</div>';
							}

						$h .= '</td>';

						$h .= '<td class="text-end t-highlight td-vertical-align-top">';
							if($eSuggestion['type'] === Cash::DEBIT) {
								$h .= \util\TextUi::money(abs($eSuggestion['amountIncludingVat']));
							}
						$h .= '</td>';

						$h .= '<td class="text-end t-highlight td-vertical-align-top">';
							if($eSuggestion['type'] === Cash::CREDIT) {
								$h .= \util\TextUi::money(abs($eSuggestion['amountIncludingVat']));
							}
						$h .= '</td>';
						$h .= '<td class="text-end">';

							$h .= '<div class="flex-buttons" style="justify-content: end">';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doImport" post-id="'.$eRegister['id'].'" post-source="'.$eSuggestion['source'].'" post-reference="'.$eSuggestion['reference'].'" class="btn btn-outline-primary">'.s("Importer dans le journal").'</a> ';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doIgnore" post-source="'.$eSuggestion['source'].'" post-reference="'.$eSuggestion['reference'].'" class="btn" data-confirm="'.s("Cette ligne ne vous sera plus jamais proposée à l'importation dans vos journaux de caisse. Continuer ?").'">'.s("Ignorer").'</a>';
							$h .= '</div>';

						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function getName(Cash $eCash): string {

		return s("Caisse n°{register}, opération n°{cash}", ['register' => $eCash['register']['id'], 'cash' => $eCash['position']]);

	}

	public static function getOperation(string $source, ?string $type = NULL, \Element $e = new Cash()): string {

		return match($source) {

			Cash::INITIAL => self::getText($source, $type),
			Cash::BALANCE => \Asset::icon('plus-slash-minus').'  '.self::getText($source, $type),

			Cash::PRIVATE => match($type) {
				Cash::CREDIT => \Asset::icon('person-fill').'  '.self::getText($source, $type),
				Cash::DEBIT => \Asset::icon('person-fill').'  '.self::getText($source, $type),
			},

			Cash::BANK_MANUAL, Cash::BANK_CASHFLOW => match($type) {
				Cash::CREDIT => \Asset::icon('bank').'  '.self::getText($source, $type),
				Cash::DEBIT => \Asset::icon('bank').'  '.self::getText($source, $type),
			},

			Cash::OTHER => match($type) {
				Cash::CREDIT => \Asset::icon('three-dots').'  '.self::getText($source, $type),
				Cash::DEBIT => \Asset::icon('three-dots').'  '.self::getText($source, $type),
			},

			Cash::BUY_MANUAL => \Asset::icon('wallet').'  '.self::getText($source, $type),
			Cash::SELL_MANUAL => \Asset::icon('wallet').'  '.self::getText($source, $type),
			Cash::SELL_INVOICE => \Asset::icon('wallet').'  <u class="mr-1">'.encode($e['customer']->getName()).'</u><a href="'.\selling\InvoiceUi::url($e['invoice']).'" class="btn btn-outline-primary btn-xs">'.\selling\InvoiceUi::getName($e['invoice']).'</a>',
			Cash::SELL_SALE => \Asset::icon('wallet').'  <u class="mr-1">'.encode($e['customer']->getName()).'</u><a href="'.\selling\SaleUi::url($e['sale']).'" class="btn btn-outline-primary btn-xs">'.\selling\SaleUi::getName($e['sale']).'</a>'

		};

	}

	public static function getText(string $source, ?string $type = NULL): string {

		return match($source) {

			Cash::INITIAL => s("Solde initial de la caisse"),
			Cash::BALANCE => s("Écart de caisse"),

			Cash::PRIVATE => match($type) {
				Cash::CREDIT => s("Apport de l'exploitant à la caisse"),
				Cash::DEBIT => s("Prélèvement par l'exploitant dans la caisse"),
			},

			Cash::BANK_MANUAL, Cash::BANK_CASHFLOW => match($type) {
				Cash::CREDIT => s("Retrait depuis la banque"),
				Cash::DEBIT => s("Dépôt à la banque"),
			},

			Cash::OTHER => match($type) {
				Cash::CREDIT => s("Autre opération créditrice"),
				Cash::DEBIT => s("Autre opération débitrice"),
			},

			Cash::BUY_MANUAL => s("Achat à un fournisseur"),
			Cash::SELL_MANUAL, Cash::SELL_INVOICE, Cash::SELL_SALE => s("Vente à un client")

		};

	}

	public function getList(Register $eRegister, \Collection $ccCash, \Search $search, ?int $page = NULL) {

		if($ccCash->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune opération à afficher.").'</div>';
		}

		$eFarm = \farm\Farm::getConnected();

		$h = '<div class="util-overflow-md">';
			$h .= '<table class="cash-item-table tr-even">';

			$hasVat = $ccCash->contains(fn($cCash) => $cCash->contains(fn($eCash) => $eCash['vat'] !== NULL));

			foreach($ccCash as $status => $cCash) {

				$eCashLast = $cCash->first();
				$columns = 5 + ($hasVat ? 2 : 0) + ($search->empty() ? 1 : 0);

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<td colspan="'.$columns.'" style="padding: 0">';
							$h .= '<div class="util-title mt-2">';
								$h .= '<h2>';
									$h .= match($status) {
										Cash::DRAFT => s("Brouillard de caisse").' <span class="util-counter">'.$cCash->count().'</span>',
										Cash::VALID => s("Journal de caisse"),
									};
								$h .= '</h2>';

								switch($status) {

									case Cash::DRAFT :

										if($eCashLast['balanceNegative'] === FALSE) {
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doValidate" post-id="'.$eCashLast['id'].'" data-confirm="'.s("Toutes les opérations seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération datée avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eCashLast['date'])).'" class="btn btn-secondary">'.s("Tout valider maintenant").'</a>';
										}

										break;

								}

							$h .= '</div>';

							switch($status) {

								case Cash::DRAFT :

									$h .= '<div class="util-info">'.s("Les opérations du brouillard de caisse peuvent être modifiées jusqu'à leur validation. Une fois validée, une opération devient inaltérable et vous ne pouvez plus en ajouter antérieurement.").'</div>';

									if($eCashLast['balanceNegative']) {
										$h .= '<div class="util-block-danger">'.\Asset::icon('exclamation-circle').' '.s("Le solde de votre journal de caisse doit toujours être positif. </h3>Veuillez corriger vos saisies afin de pouvoir valider vos opérations.").'</div>';
									}

									break;

								case Cash::VALID :

									if($eRegister['closedAt'] !== NULL) {

										if($eRegister['operations'] > 1) {

											$h .= '<div class="util-block bg-primary color-white">';
												$h .= \Asset::icon('lock-fill').'  '.s("Votre journal de caisse est actuellement clôturé au {closed}, la saisie de nouvelles opérations est possible à partir du {open}.", [
													'closed' => \util\DateUi::numeric($eRegister['closedAt']),
													'open' => \util\DateUi::numeric(date('Y-m-d', strtotime($eRegister['closedAt'].' + 1 DAY'))),
												]);

												if($eRegister->acceptDelete()) {
													$h .= '<br/>'.\Asset::icon('exclamation-circle').'  '.s("Si vous avez fait une erreur, vous pouvez supprimer votre journal de caisse tant qu'il contient moins de {value} opérations le temps de vous familiariser avec cette fonctionnalité.", CashSetting::DELETE_LIMIT);
												}

												if($eRegister->acceptClose()) {

													$closeDate = $eRegister->getCloseDate();

													if($closeDate !== NULL) {

														$h .= '<div class="mt-1">';
															$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doClose" post-id="'.$eRegister['id'].'" post-date="'.$closeDate.'" class="btn btn-transparent" data-confirm="'.s("ATTENTION !\nLa clôture est définitive, et vous ne pourrez ajouter, modifier ou supprimer d'opération jusqu'au {value}. Voulez-vous continuer ?", \util\DateUi::numeric($closeDate)).'">';
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
						$h .= '<th class="text-end t-highlight">'.s("Débit").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Crédit").'</th>';

						if($hasVat) {
							$h .= '<th class="text-center" colspan="2">'.s("TVA").'</th>';
						}

						if($search->empty()) {

							$h .= '<th colspan="2">';
								$h .= match($status) {
									Cash::DRAFT => s("Solde théorique"),
									Cash::VALID => s("Solde"),
								};
							$h .= '</th>';

						} else {
							$h .= '<th></th>';
						}

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
										$h .= '<td class="text-end t-highlight"></td>';
										$h .= '<td class="text-end t-highlight"></td>';
										$h .= '<td colspan="'.($columns - 3).'"></th>';
									$h .= '</tr>';
								$h .= '</tbody>';
								$h .= '<tbody>';

							$previousSubtitle = $currentSubtitle;

						}

						$h .= '<tr'.(GET('position', 'int') === $eCash['position'] ? ' class="row-highlight"' : '').'>';

							$h .= '<td class="td-min-content text-end td-vertical-align-top">';
								if($eCash['position'] !== NULL) {
									$h .= '<div class="btn btn-outline-primary btn-readonly btn-xs">'.$eCash['position'].'</div>';
								}
							$h .= '</td>';

							$h .= '<td>';

								$h .= '<div style="display: flex; align-items: center;">';

									$h .= CashUi::getOperation($eCash['source'], $eCash['type'], $eCash);

									if($eCash['accountingHash'] !== NULL) {
										$h .= '<a class="util-badge bg-accounting ml-1" title="'.s("Intégré en comptabilité").'" href="'.\farm\FarmUi::urlConnected($eFarm).'/journal/livre-journal?hash='.$eCash['accountingHash'].'&financialYearReset">'.\Asset::icon('journal-text').'</a>';
									}

									if($eCash['status'] === Cash::DRAFT) {
										$h .= '<span class="util-badge bg-muted ml-1">'.s("Non validé").'</span>';
									}

								$h .= '</div>';

								$h .= '<div class="cash-item-details">'.$this->getDetails($eCash).'</div>';

								if(
									$eCash->offsetExists('cSaleMarket') and
									$eCash['cSaleMarket']->notEmpty()
								) {

									$h .= '<div class="cash-item-children">';
										$h .= '<span>'.s("Vente supérieures à {value} € :", CashSetting::AMOUNT_THRESHOLD).'</span>';
										foreach($eCash['cSaleMarket'] as $eSale) {
											$h .= \selling\SaleUi::link($eSale, size: 'btn-xs');
										}
									$h .= '</div>';
								}

							$h .= '</td>';

							$h .= '<td class="td-min-content t-highlight text-end">';
								if($eCash['type'] === Cash::DEBIT) {
									$h .= \util\TextUi::money($eCash['amountIncludingVat']);
								}
							$h .= '</td>';

							$h .= '<td class="td-min-content t-highlight text-end">';
								if($eCash['type'] === Cash::CREDIT) {
									$h .= \util\TextUi::money($eCash['amountIncludingVat']);
								}
							$h .= '</td>';

							if($hasVat) {

								$h .= '<td class="td-min-content text-end">';
									if($eCash['vat'] !== NULL) {
										$h .= \util\TextUi::money($eCash['vat']);
									}
								$h .= '</td>';

								$h .= '<td class="td-min-content font-sm color-muted">';
									if($eCash['vatRate'] !== NULL) {
										$h .= ' '.s("{value} %", $eCash['vatRate']);
									}
								$h .= '</td>';

							}

							if($search->empty()) {

								$h .= '<td class="td-min-content cash-item-balance">';

									if($eCash['balance'] !== NULL) {

										$balance = \util\TextUi::money($eCash['balance']);

										$h .= match($eCash['status']) {
											Cash::DRAFT => '<span class="'.($eCash['balanceNegative'] ? 'util-badge bg-danger' : 'cash-item-balance-waiting').'">'.$balance.'</span>',
											Cash::VALID => $balance
										};

									}

								$h .= '</td>';

							}

							$h .= '<td class="text-end">';

								switch($status) {

									case Cash::DRAFT :
										$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
										$h .= '<div class="dropdown-list">';
											$h .= '<div class="dropdown-title">'.s("Opération de caisse").'</div>';
											if($eCash->acceptUpdate()) {
												$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:update?id='.$eCash['id'].'" class="dropdown-item">'.s("Modifier l'opération").'</a>';
											}
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doValidate" post-id="'.$eCash['id'].'" data-confirm="'.s("Cette opération ainsi que toutes les opérations antérieures seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération datée avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eCashLast['date'])).'" class="dropdown-item '.($eCash['balanceNegative'] ? 'disabled' : '').'">'.s("Valider les opérations jusqu'à celle-ci").'</a>';
											$h .= '<div class="dropdown-divider"></div>';

											switch($eCash['source']) {
												case Cash::SELL_INVOICE:
													$confirm = s("Vous allez supprimer toutes les opérations liées à la {value}. Continuer ?", \selling\InvoiceUi::getName($eCash['invoice']));
													$dropdownItem = s("Supprimer les opérations liées à la {value}", \selling\InvoiceUi::getName($eCash['invoice']));
													break;

												case Cash::SELL_SALE:
													$confirm = s("Vous allez supprimer toutes les opérations liées à {value}. Continuer ?", \selling\SaleUi::getName($eCash['sale']));
													$dropdownItem = s("Supprimer les opérations liées à {value}", \selling\SaleUi::getName($eCash['sale']));
													break;

												default:
													$confirm = s("Vous allez supprimer cette opération. Continuer ?");
													$dropdownItem = s("Supprimer l'opération");
											}

											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doDelete" data-confirm="'.$confirm.'" post-id="'.$eCash['id'].'" class="dropdown-item">'.$dropdownItem.'</a>';
										$h .= '</div>';
										break;

									case Cash::VALID :
										if($eCash->acceptUpdate()) {
											$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:update?id='.$eCash['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
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

		if($ccCash->getFound() !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $ccCash->getFound() / 200);
		}

		return $h;

	}

	protected function getDetails(Cash $eCash): string {

		$list = [];

		if($eCash['account']->notEmpty()) {
			$list[] = encode($eCash['account']['name']);
		}

		if(
			$eCash['description'] !== NULL and
			in_array($eCash['source'], [Cash::SELL_SALE, Cash::SELL_INVOICE]) === FALSE
		) {
			$list[] = encode($eCash['description']);
		}

		return implode(' | ', $list);

	}

	public function start(Register $eRegister): string {

		$eCash = new Cash([
			'source' => Cash::INITIAL
		]);

		$h = '<h3>'.s("Indiquez le solde initial de la caisse").'</h3>';

			$h .= '<div class="util-block-info">';
				$h .= '<p>'.s("Le solde initial marque le point de départ de votre caisse. Choisissez bien la date du solide initial car toutes les opérations que vous enregistrerez ultérieurement dans votre journal devront être postérieures à cette date.").'</p>';
				$h .= '<p>'.s("Votre journal de caisse peut commencer au plus tôt le {value}.", \util\DateUi::numeric(CashUi::getFirstDate())).'</p>';
			$h .= '</div>';

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
				$h .= $form->group(content: $form->submit(s("Valider le solde initial"), ['data-confirm' => s("Vous ne pourrez pas modifier votre choix. Valider ce solde initial ?")]));
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

						if($eRegister->isClosedByDate($date)) {
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

		$h .= $form->dynamicGroup($eCash, 'description');
		$h .= $this->getAccountsFields($form, $eCash);

		if($eCash->requireVat()) {

			$h .= '<div class="util-block bg-background-light">';
				$h .= $form->group(content: '<h4>'.s("Montants").'</h4>');
				$h .= $form->dynamicGroups($eCash, ['amountIncludingVat', 'vatRate']);
				$h .= $form->group(content: '<p class="util-empty mb-0">'.\Asset::icon('info-circle').' '.s("Les montants de TVA et HT sont automatiquement calculés lorsque vous tapez le montant TTC et le taux de TVA.").'</p>');
				$h .= $form->dynamicGroups($eCash, ['vat', 'amountExcludingVat']);
			$h .= '</div>';

		} else {
			$h .= $form->group(
				s("Montant"),
				$form->dynamicField($eCash, 'amountIncludingVat')
			);
		}

		return $h;

	}

	public function getAccountsFields(\util\FormUi $form, Cash $eCash): string {

		$h = '';

		if($eCash->requireAssociateAccount()) {

			$label = s("Compte associé");

			if($eCash['cAccount']->notEmpty()) {

				$label .= \util\FormUi::info(s("Vous pouvez ajouter les associés manquants depuis le <link>paramétrage des numéros de compte</link>.", ['link' => '<a href="'.\farm\FarmUi::urlConnected().'/account/account">']));

				if(($eCash['account'] ?? new \account\Account())->notEmpty()) {
					$eCashDefault = $eCash['account'];
				} else if($eCash['cAccount']->count() === 1) {
					$eCashDefault = $eCash['cAccount']->first();
				} else {
					$eCashDefault = new \account\Account();
				}

				$field = $form->radios('account', $eCash['cAccount'], $eCashDefault, attributes: [
					'required' => TRUE,
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

		if($eCash->requireAccount()) {
			$h .= $form->dynamicGroup($eCash, 'account');
		}

		return $h;

	}

	public function update(Cash $eCash): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doUpdate');

				$h .= $form->hidden('id', $eCash['id']);

				$h .= $form->group(
					s("Opération"),
					self::getOperation($eCash['source'], $eCash['type'])
				);

				$h .= $form->group(
					self::p('date')->label,
					$form->fake(\util\DateUi::numeric($eCash['date']))
				);

				switch($eCash['status']) {

					case Cash::DRAFT :
						$h .= $this->getFields($form, $eCash);
						break;

					case Cash::VALID :
						$h .= $this->getAccountsFields($form, $eCash);
						break;

				}

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

	public function updateBalance(Register $eRegister): \Panel {

		$form = new \util\FormUi();

		$eCash = new Cash([
			'source' => Cash::BALANCE
		]);

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/cash:doUpdateBalance');

			$h .= $form->hidden('id', $eRegister['id']);

			$h .= '<div class="util-info">'.s("Vous pouvez corriger le solde indiqué dans le journal de caisse lorsque vous constatez un écart avec le solde réel de la caisse.").'</div>';

			$h .= $form->group(
				s("Date de l'opération"),
				$form->date('date', ($eRegister['closedAt'] !== null) ? date('Y-m-d', strtotime($eRegister['closedAt'].' + 1 DAY')) : '')
			);

			$h .= $form->group(
				s("Solde du journal de caisse"),
				'<span class="btn btn-readonly"><b>'.\util\TextUi::money($eRegister['balance']).'</b></span>'
			);

			$h .= $form->group(
				s("Solde constaté dans la caisse"),
				$form->inputGroup(
					$form->number('balance', attributes: ['min' => 0.0, 'step' => 0.01]).
					$form->addon(s("€"))
				)
			);

			$h .= $form->dynamicGroup($eCash, 'description');

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-cash-update-balance',
			title: s("Constater un écart de caisse"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Cash::model()->describer($property, [
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
					Cash::DEBIT => s("Débit"),
					Cash::CREDIT => s("Crédit")
				];
				break;

			case 'description' :
				$d->placeholder = fn(Cash $eCash) => $eCash->requireDescription() ? s("Saisissez le motif de l'opération") : '';
				break;

			case 'amountExcludingVat' :
			case 'vat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Cash $eCash) => $form->addon(s("€"));
				break;

			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, Cash $eCash) => $form->addon(s("€"));
				$d->attributes = fn(\util\FormUi $form, Cash $eCash) => $eCash->requireVat() ? [
					'onchange' => 'Cash.recalculateAmount(this)'
				] : [];
				break;

			case 'vatRate' :
				$d->append = s("%");
				$d->attributes = [
					'onchange' => 'Cash.recalculateAmount(this)'
				];
				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Cash $e) {
					return [
						'query' => ['classPrefix' => '7']
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, \farm\Farm::getConnected(), query: function(Cash $e) {

					return ['withVat' => TRUE] + match($e['source']) {
						Cash::BANK_MANUAL => ['classPrefix' => \account\AccountSetting::BANK_ACCOUNT_CLASS],
						Cash::BUY_MANUAL => ['classPrefixes[0]' => '2', 'classPrefixes[1]' => '6'],
						Cash::SELL_MANUAL => ['classPrefixes[0]' => '7'],
						default => []
					};

				});
				break;

		}

		return $d;

	}

}
?>
