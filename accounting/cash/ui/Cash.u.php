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

		$h = '<div class="util-block">';

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
						$h .= s("Constater un écart de caisse");
						if($eRegister->acceptUpdateBalance() === FALSE) {
							$h .= '<div style="margin-top: 0.25rem" class="font-xs">'.\Asset::icon('exclamation-circle').' '.s("Opérations non validées").'</div>';
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
		$h .= '<div class="util-title">';
			$h .= '<h3>'.\Asset::icon('fire').' '.s("Opérations en {method} automatiquement trouvées depuis le {value}", ['method' => '<span style="text-transform: uppercase">'.encode($eRegister['paymentMethod']['name']).'</span>', 'value' => \util\DateUi::numeric($eRegister['openedSince'])]).'</h3>';
			$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doIgnoreByMethod" post-id="'.$eRegister['id'].'" class="btn btn-outline-secondary" data-confirm="'.s("Ces opérations ne vous seront plus jamais proposées à l'importation dans vos journaux de caisse. Continuer ?").'">'.s("Tout ignorer").'</a>';
		$h .= '</div>';

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

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit").'</th>';
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
							$h .= self::getOperation($eSuggestion['source'], $eSuggestion['type'], $eSuggestion['customer']).'</div>';
							$h .= '<div class="cash-auto-description">';
								$h .= \Asset::icon('arrow-return-right').'  ';

								switch($eSuggestion['source']) {

									case Cash::SELL_INVOICE :
										$h .= '<a href="'.\farm\FarmUi::urlSellingInvoices(\farm\Farm::getConnected()).'?invoice='.$eSuggestion['invoice']['id'].'">'.encode($eSuggestion['description']).'</a>';
										break;

									case Cash::SELL_SALE :
										$h .= '<a href="'.\selling\SaleUi::url($eSuggestion['sale']).'">'.encode($eSuggestion['description']).'</a>';
										break;

									default :
										$h .= encode($eSuggestion['description']);
										break;

								}

							$h .= '</div>';
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-right td-vertical-align-top">';
							if($eSuggestion['type'] === Cash::DEBIT) {
								$h .= \util\TextUi::money(abs($eSuggestion['amountIncludingVat']));
							}
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-left td-vertical-align-top">';
							if($eSuggestion['type'] === Cash::CREDIT) {
								$h .= \util\TextUi::money(abs($eSuggestion['amountIncludingVat']));
							}
						$h .= '</td>';
						$h .= '<td class="text-end">';

							$h .= '<div class="flex-buttons" style="justify-content: end">';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doImport" post-id="'.$eRegister['id'].'" post-source="'.$eSuggestion['source'].'" post-reference="'.$eSuggestion['reference'].'" class="btn btn-secondary">'.s("Importer dans le journal").'</a> ';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/suggestion:doIgnore" post-source="'.$eSuggestion['source'].'" post-reference="'.$eSuggestion['reference'].'" class="btn btn-outline-secondary" data-confirm="'.s("Cette ligne ne vous sera plus jamais proposée à l'importation dans vos journaux de caisse. Continuer ?").'">'.s("Ignorer").'</a>';
							$h .= '</div>';

						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function getOperation(string $source, ?string $type = NULL, \selling\Customer $eCustomer = new \selling\Customer()): string {

		return match($source) {

			Cash::INITIAL => s("Solde initial de la caisse"),
			Cash::BALANCE => \Asset::icon('plus-slash-minus').'  '.s("Écart de caisse"),

			Cash::PRIVATE => match($type) {
				Cash::CREDIT => \Asset::icon('person-fill').'  '.s("Apport de l'exploitant à la caisse"),
				Cash::DEBIT => \Asset::icon('person-fill').'  '.s("Prélèvement par l'exploitant dans la caisse"),
			},

			Cash::BANK_MANUAL, Cash::BANK_CASHFLOW => match($type) {
				Cash::CREDIT => \Asset::icon('bank').'  '.s("Retrait depuis la banque"),
				Cash::DEBIT => \Asset::icon('bank').'  '.s("Dépôt à la banque"),
			},

			Cash::OTHER => match($type) {
				Cash::CREDIT => \Asset::icon('three-dots').'  '.s("Autre opération créditrice"),
				Cash::DEBIT => \Asset::icon('three-dots').'  '.s("Autre opération débitrice"),
			},

			Cash::BUY_MANUAL => \Asset::icon('wallet').'  '.s("Achat à un fournisseur"),
			Cash::SELL_MANUAL => \Asset::icon('wallet').'  '.s("Vente à un client"),
			Cash::SELL_INVOICE => \Asset::icon('wallet').'  '.s("Facture {value}", '<u>'.encode($eCustomer->getName()).'</u>'),
			Cash::SELL_SALE => \Asset::icon('wallet').'  '.s("Vente {value}", '<u>'.encode($eCustomer->getName()).'</u>')

		};

	}

	public function getList(Register $eRegister, \Collection $ccCash, \Search $search, ?int $page = NULL) {

		if($ccCash->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune opération à afficher.").'</div>';
		}

		$h = '<div class="util-overflow-md">';
			$h .= '<table class="cash-item-table tr-even">';

			$hasVat = $ccCash->contains(fn($cCash) => $cCash->contains(fn($eCash) => $eCash['vat'] !== NULL));

			foreach($ccCash as $status => $cCash) {

				$eCashLast = $cCash->first();
				$columns = 5 + ($hasVat ? 2 : 0) + ($search->empty() ? 1 : 0);

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<td colspan="'.$columns.'" style="padding: 0">';
							$h .= '<div class="util-title">';
								$h .= '<h2 class="mt-2">';
									$h .= match($status) {
										Cash::DRAFT => s("Opérations non validées").' <span class="util-counter">'.$cCash->count().'</span>',
										Cash::VALID => s("Journal de caisse"),
									};
								$h .= '</h2>';

								switch($status) {

									case Cash::DRAFT :

										if($eCashLast['balanceNegative'] === FALSE) {
											$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doValidate" post-id="'.$eCashLast['id'].'" data-confirm="'.s("Toutes les opérations seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eCashLast['date'])).'" class="btn btn-secondary">'.s("Tout valider maintenant").'</a>';
										}

										break;

								}

							$h .= '</div>';

							switch($status) {

								case Cash::DRAFT :

									if($eCashLast['balanceNegative']) {
										$h .= '<div class="util-block-danger">'.\Asset::icon('exclamation-circle').' '.s("Le solde de votre journal de caisse doit toujours être positif. </h3>Veuillez corriger vos saisies afin de pouvoir valider vos opérations.").'</div>';
									}

									break;

								case Cash::VALID :

									if($eRegister['closedAt'] !== NULL) {

										$h .= '<div class="util-block-gradient">';
											$h .= \Asset::icon('lock-fill').'  '.s("Votre journal de caisse est actuellement clôturé au {closed}, la saisie de nouvelles opérations est possible à partir du {open}.", [
												'closed' => \util\DateUi::numeric($eRegister['closedAt']),
												'open' => \util\DateUi::numeric(date('Y-m-d', strtotime($eRegister['closedAt'].' + 1 DAY'))),
											]);

											if($eRegister->acceptClose()) {

												$closeDate = $eRegister->getCloseDate();

												if($closeDate !== NULL) {

													$h .= '<div class="mt-1">';
														$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doClose" post-id="'.$eRegister['id'].'" post-date="'.$closeDate.'" class="btn btn-secondary" data-confirm="'.s("La clôture est définitive, et vous ne pourrez ajouter, modifier ou supprimer d'opération jusqu'au {value}. Voulez-vous continuer ?", \util\DateUi::numeric($closeDate)).'">';
															$h .= '<div class="btn-icon">'.\Asset::icon('calendar-month').'</div>';
															$h .= s("Clôturer le journal au {value}", \util\DateUi::numeric($closeDate));
														$h .= '</a>';
													$h .= '</div>';

													if(substr($closeDate, 0, 7) < date('Y-m', strtotime('last month'))) {
														$h .= '<div class="util-info mt-1">'.s("Validez les opérations en attente pour clôturer le journal plus tard.").'</div>';
													}

												}

											}

										$h .= '</div>';

									}

									break;

							}

						$h .= '</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th colspan="2"></th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Crédit").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Débit").'</th>';

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
										$h .= '<td class="text-end highlight-stick-right"></td>';
										$h .= '<td class="text-end highlight-stick-left"></td>';
										$h .= '<th colspan="'.($columns - 3).'"></th>';
									$h .= '</tr>';
								$h .= '</tbody>';
								$h .= '<tbody>';

							$previousSubtitle = $currentSubtitle;

						}

						$h .= '<tr>';

							$h .= '<td class="td-min-content text-end td-vertical-align-top">';
								if($eCash['position'] !== NULL) {
									$h .= '<div class="btn btn-outline-primary btn-readonly btn-xs">'.$eCash['position'].'</div>';
								}
							$h .= '</td>';

							$h .= '<td>';

								$h .= CashUi::getOperation($eCash['source'], $eCash['type'], $eCash['customer']);

								if($eCash['status'] === Cash::DRAFT) {
									$h .= '<span class="util-badge bg-muted ml-1">'.s("Non validé").'</span>';
								}

								$h .= '<div class="cash-item-details">'.$this->getDetails($eCash).'</div>';
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

								if($eCash['status'] === Cash::DRAFT) {

									$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-title">'.s("Opération de caisse").'</div>';
										$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/cash:update?id='.$eCash['id'].'" class="dropdown-item">'.s("Modifier l'opération").'</a>';
										$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doValidate" post-id="'.$eCash['id'].'" data-confirm="'.s("Cette opération ainsi que toutes les opérations antérieures seront définitivement validées, et vous ne pourrez ajouter, modifier ou supprimer d'opération avant le {value}. Voulez-vous continuer ?", \util\DateUi::numeric($eCashLast['date'])).'" class="dropdown-item '.($eCash['balanceNegative'] ? 'disabled' : '').'">'.s("Valider les opérations jusqu'à celle-ci").'</a>';
										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/cash:doDelete" data-confirm="'.s("Vous allez supprimer cette opération. Continuer ?").'" post-id="'.$eCash['id'].'" class="dropdown-item">'.s("Supprimer l'opération").'</a>';
									$h .= '</div>';

								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			}

			$h .= '</table>';
		$h .= '</div>';

		if($cCash->getFound() !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $cCash->getFound() / 100);
		}

		return $h;

	}

	protected function getDetails(Cash $eCash): string {

		$list = [];

		if($eCash['account']->notEmpty()) {
			$list[] = encode($eCash['account']['name']);
		}

		if($eCash['description'] !== NULL) {

			$description = encode($eCash['description']);

			if($eCash['sale']->notEmpty()) {
				$list[] = '<a href="'.\selling\SaleUi::url($eCash['sale']).'">'.$description.'</a>';
			} else if($eCash['invoice']->notEmpty()) {
				$list[] = '<a href="'.\selling\InvoiceUi::url($eCash['invoice']).'">'.$description.'</a>';
			} else {
				$list[] = $description;
			}

		}

		return implode(' | ', $list);

	}

	public function start(Register $eRegister): string {

		$eCash = new Cash([
			'source' => Cash::INITIAL
		]);

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

		if($eCash->requireAssociateAccount()) {

			$label = s("Numéro de compte associé");

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

		if($eCash->requireAccount()) {
			$h .= $form->dynamicGroup($eCash, 'account');
		}

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

		$h .= $form->dynamicGroup($eCash, 'description');

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

			$h .= $this->getFields($form, $eCash);

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

					return match($e['source']) {
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
