<?php
namespace selling;

class InvoiceUi {

	public function __construct() {

		\Asset::css('selling', 'sale.css');
		\Asset::css('selling', 'invoice.css');

		\Asset::js('selling', 'sale.js');
		\Asset::js('selling', 'invoice.js');

	}

	public static function link(Invoice $eInvoice, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eInvoice).'" data-ajax-navigation="never" class="btn btn-sm btn-outline-primary" '.($newTab ? 'target="_blank"' : '').'>'.encode($eInvoice['name']).'</a>';
	}

	public static function url(Invoice $e): string {

		return '/facture/'.$e['id'];

	}
	
	public function getSuccessActions(Invoice $eInvoice): string {
			
		$h = '<div class="mt-1">';
			$h .= '<a href="'.\selling\InvoiceUi::url($eInvoice).'" data-ajax-navigation="never" class="btn btn-transparent">'.s("Télécharger").'</a>';
			if($eInvoice->acceptSend()) {
				$h .= ' <a data-ajax="/selling/invoice:doSend" post-id="'.$eInvoice['id'].'" class="btn btn-transparent" data-confirm="'.s("Confirmer l'envoi de la facture au client par e-mail ?").'">'.\Asset::icon('send').' '.s("Envoyer au client par e-mail").'</a>';
			}
		$h .= '</div>';

		return $h;
			
	}

	public static function getPaymentStatus(Invoice $eInvoice): string {

		if($eInvoice->isCreditNote()) {
			return '';
		}

		return '<span class="util-badge sale-payment-status sale-payment-status-'.$eInvoice['paymentStatus'].'">'.self::p('paymentStatus')->values[$eInvoice['paymentStatus']].'</span>';

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="invoice-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Numéro")]);
					$h .= $form->text('customer', $search->get('customer'), ['placeholder' => s("Client")]);
					$h .= $form->select('status', InvoiceUi::p('status')->values, $search->get('status'), ['placeholder' => s("État")]);
					$h .= $form->text('date', $search->get('date'), ['placeholder' => s("Date de facturation")]);
					$h .= $form->select('paymentStatus', self::p('paymentStatus')->values, $search->get('paymentStatus'), ['placeholder' => s("Payée ?")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\Collection $cInvoice, ?int $nInvoice = NULL, array $hide = [], ?int $page = NULL) {

		if($cInvoice->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune facture à afficher.").'</div>';
		}

		$h = '<div class="util-overflow-sm stick-xs">';

			$columns = 9;

			$h .= '<table class="tr-even" data-batch="#batch-invoice">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-min-content"></th>';
						$h .= '<th class="text-center">'.s("Numéro").'</th>';
						if(in_array('customer', $hide) === FALSE) {
							$columns++;
							$h .= '<th class="invoice-item-customer">'.s("Client").'</th>';
						}
						//$h .= '<th class="text-center">'.s("État").'</th>';
						$h .= '<th class="text-center">'.s("Date de<br/>facturation").'</th>';
						$h .= '<th class="text-end invoice-item-amount">'.s("Montant").'</th>';
						$h .= '<th class="td-min-content text-center">'.s("Envoyée<br/>par e-mail").'</th>';
						$h .= '<th>'.s("Règlement").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
					$h .= '<tr>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

				$previousSubtitle = NULL;

				foreach($cInvoice as $eInvoice) {

					$currentSubtitle = $eInvoice['date'];

					if($currentSubtitle !== $previousSubtitle) {

						if($previousSubtitle !== NULL) {
							$h .= '</tbody>';
							$h .= '<tbody>';
						}

							$h .= '<tr class="tr-title">';
								$h .= '<th class="td-checkbox">';
									$h .= '<label title="'.s("Cocher ces factures / Décocher ces factures").'">';
										$h .= '<input type="checkbox" class="batch-all" onclick="Invoice.toggleDaySelection(this)"/>';
									$h .= '</label>';
								$h .= '</th>';
								$h .= '<td colspan="'.$columns.'">';
									$h .= match($currentSubtitle) {
										currentDate() => s("Aujourd'hui"),
										default => \util\DateUi::textual($currentSubtitle)
									};
								$h .= '</td>';
							$h .= '</tr>';

						$h .= '</tbody>';
						$h .= '<tbody>';

						$previousSubtitle = $currentSubtitle;

					}

					$batch = [];

					if($eInvoice->acceptSend() === FALSE) {
						$batch[] = 'not-sent';
					}

					if(in_array($eInvoice['generation'], [Invoice::PROCESSING, Invoice::WAITING])) {
						$class = 'invoice-item-waiting';
					} else {
						$class = 'invoice-item-'.$eInvoice['paymentStatus'];
					}

					$h .= '<tr id="invoice-list-'.$eInvoice['id'].'" class="'.$class.'"';
						if($eInvoice['status'] === Invoice::CANCELED) {
							$h .= ' style="opacity: 0.5"';
						}
					$h .= '>';
						$h .= '<td class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eInvoice['id'].'" oninput="Invoice.changeSelection()" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</label>';
						$h .= '</td>';
						$h .= '<td class="text-center td-min-content">';
							if($eInvoice['content']->empty()) {
								$h .= '<span class="btn disabled">'.encode($eInvoice['name']).'</span>';
							} else {
								$h .= InvoiceUi::link($eInvoice);
							}
						$h .= '</td>';

						if(in_array('customer', $hide) === FALSE) {
							$h .= '<td class="invoice-item-customer">';
								$h .= CustomerUi::link($eInvoice['customer']);
								$h .= '<div class="util-annotation">';
									$h .= CustomerUi::getCategory($eInvoice['customer']);
								$h .= '</div>';
								if($eInvoice['comment']) {
									$h .= '<div class="invoice-item-comment util-info">';
										$h .= $eInvoice->quick('comment', encode($eInvoice['comment']));
									$h .= '</div>';
								}
							$h .= '</td>';
						}
/*
						$h .= '<td class="text-center">';
							$h .= $this->getStatusForUpdate($eInvoice, 'btn-xs');
						$h .= '</td>';
*/
						$h .= '<td class="text-center">';
							$h .= \util\DateUi::numeric($eInvoice['date']);
							if($eInvoice['dueDate'] !== NULL) {
								$h .= '<div class="util-annotation invoice-item-due">';
									if($eInvoice['dueDate'] > currentDate()) {
										$h .= s("échoit le {value}", \util\DateUi::numeric($eInvoice['dueDate']));
									} else {
										$h .= s("échue le {value}", \util\DateUi::numeric($eInvoice['dueDate']));
									}
								$h .= '</div>';
							}
						$h .= '</td>';

						$h .= '<td class="text-end invoice-item-amount">';
							$h .= SaleUi::getIncludingTaxesTotal($eInvoice);
							$h .= '<div class="util-annotation">';
								$cSale = $eInvoice['cSale'];

								$h .= '<a href="'.\farm\FarmUi::urlSellingSales($eInvoice['farm']).'?ids='.implode(',', $cSale->getIds()).'">'.p("{value} vente", "{value} ventes", $cSale->count()).'</a>';
							$h .= '</div>';
						$h .= '</td>';

						switch($eInvoice['generation']) {

							case Invoice::WAITING :
							case Invoice::PROCESSING :
								$h .= '<td colspan="3">';
									$h .= '<span class="invoice-item-generation color-selling">'.\Asset::icon('arrow-clockwise').' '.s("Génération en cours").'</span>';
									$h .= '<a href="'.\farm\FarmUi::urlSellingSalesInvoice($eInvoice['farm']).'" class="btn btn-outline-secondary">'.s("Actualiser").'</a>';
								$h .= '</td>';
								break;

							case Invoice::FAIL :
								$h .= '<td colspan="3">';
									$h .= '<span class="invoice-item-generation color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Génération échouée").'</span>';
								$h .= '</td>';
								break;

							case Invoice::SUCCESS :

								$h .= '<td class="text-center">';
									$h .= $this->getIconEmail($eInvoice);
								$h .= '</td>';

								$h .= '<td>';

									if($eInvoice['paymentMethod']->empty()) {

										$h .= '<a href="/selling/invoice:update?id='.$eInvoice['id'].'" class="btn btn-outline-primary">'.s("Choisir").'</a>';

									} else {
										$h .= '<div>'.\payment\MethodUi::getName($eInvoice['paymentMethod']).'</div>';

										if($eInvoice->isCreditNote() === FALSE) {
											$h .= '<div>';
												$h .= \util\TextUi::switch([
													'id' => 'invoice-switch-'.$eInvoice['id'],
													'class' => 'field-switch-sm',
													'disabled' => $eInvoice->isPaymentOnline(),
													'data-ajax' => $eInvoice->canWrite() ? '/selling/invoice:doUpdatePaymentStatus' : NULL,
													'post-id' => $eInvoice['id'],
													'post-payment-status' => ($eInvoice['paymentStatus'] === Invoice::PAID) ? Invoice::NOT_PAID : Invoice::PAID
												], $eInvoice['paymentStatus'] === Invoice::PAID, textOn: self::p('paymentStatus')->values[Sale::PAID],textOff: self::p('paymentStatus')->values[Sale::NOT_PAID]);
											$h .= '</div>';
										}
									}
								$h .= '</td>';

								break;

						}

						$h .= '<td class="text-end td-min-content">';

							if($eInvoice->canWrite()) {

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.s("Facture {value}", encode($eInvoice['name'])).'</div>';

									if($eInvoice['content']->notEmpty()) {
										$h .= '<a href="'.self::url($eInvoice).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger la facture").'</a>';
									}

									$h .= '<div class="dropdown-divider"></div>';

									if($eInvoice->acceptRegenerate()) {
										$h .= '<a href="/selling/invoice:regenerate?id='.$eInvoice['id'].'" class="dropdown-item">'.s("Regénérer la facture").'</a>';
									}

									$h .= '<a href="/selling/invoice:updatePayment?id='.$eInvoice['id'].'" class="dropdown-item">'.s("Modifier le règlement").'</a>';

									$h .= '<a href="/selling/invoice:updateComment?id='.$eInvoice['id'].'" class="dropdown-item">';
										$h .= $eInvoice['comment'] === NULL ? s("Ajouter un commentaire") : s("Modifier le commentaire");
									$h .= '</a>';

									if($eInvoice['emailedAt']) {
										$h .= '<div class="dropdown-divider"></div>';
										$h .= ' <div class="dropdown-item">'.\Asset::icon('check-all').'&nbsp;&nbsp;'.s("Envoyé par e-mail le {value}", \util\DateUi::numeric($eInvoice['emailedAt'], \util\DateUi::DATE)).'</div>';
									} else {

										$h .= '<div class="dropdown-divider"></div>';

										if($eInvoice->acceptSend()) {
											$h .= '<a data-ajax="/selling/invoice:doSend" post-id="'.$eInvoice['id'].'" data-confirm="'.PdfUi::getTexts(Pdf::INVOICE)['sendConfirm'].'" class="dropdown-item">'.s("Envoyer au client par e-mail").'</a>';
										} else {
											$h .= '<span class="dropdown-item sale-document-forbidden">'.s("Envoyer au client par e-mail").'</span>';
										}

									}

									if($eInvoice->canDelete()) {

										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<a data-ajax="/selling/invoice:doDelete" post-id="'.$eInvoice['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression définitive de la facture ?").'">'.s("Supprimer la facture").'</a>';

										if($eInvoice['expiresAt'] !== NULL) {
											$h .= '<span class="dropdown-item sale-document-expires">'.s("Le fichier PDF de cette facture<br/>expirera automatiquement le {value}.", \util\DateUi::numeric($eInvoice['expiresAt'], \util\DateUi::DATE)).'</span>';
										}

									}

								$h .= '</div>';

							}

						$h .= '</td>';

					$h .= '</tr>';

				}


			$h .= '</table>';

		$h .= '</div>';

		if($nInvoice !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nInvoice / 100);
		}

		$h .= $this->getBatch();

		return $h;

	}

	public function getStatusForUpdate(Invoice $eInvoice, string $btn = ''): string {

		if($eInvoice->canWrite() === FALSE) {
			return '<span class="btn btn-readonly '.$btn.' invoice-status-'.$eInvoice['status'].'-button">'.self::p('status')->values[$eInvoice['status']].'</span>';
		}

		if($eInvoice['closed']) {
			return '<span class="btn btn-readonly '.$btn.' invoice-status-closed-button" title="'.s("Il n'est pas possible de modifier une facture clôturée.").'">'.s("Clôturé").'  '.\Asset::icon('lock-fill').'</span>';
		}

		$button = function(string $status, ?string $confirm = NULL) use ($eInvoice) {

			$h = '<a data-ajax="/selling/invoice:doUpdate'.ucfirst($status).'Collection" post-ids="'.$eInvoice['id'].'" class="dropdown-item" '.($confirm ? attr('data-confirm', $confirm) : '').'>';
				$h .= \Asset::icon('arrow-right').'  <span class="btn btn-sm invoice-status-'.$status.'-button">'.self::p('status')->values[$status].'</span>';
			$h .= '</a>';

			return $h;

		};

		$to = '';

		if($eInvoice->acceptStatusConfirmed()) {
			$to .= $button(Invoice::CONFIRMED);
		}

		if($eInvoice->acceptStatusCanceled()) {
			$to .= '<div class="dropdown-divider"></div>';
			$to .= $button(Invoice::CANCELED);
		}

		if($to) {

			$h = '<a data-dropdown="bottom-start" data-dropdown-id="sale-dropdown-'.$eInvoice['id'].'" data-dropdown-hover="true" class="btn '.$btn.' invoice-status-'.$eInvoice['status'].'-button dropdown-toggle">'.self::p('status')->values[$eInvoice['status']].'</a>';
			$h .= '<div data-dropdown-id="sale-dropdown-'.$eInvoice['id'].'-list" class="dropdown-list bg-primary">';
				$h .= $to;
			$h .= '</div>';

		} else {
			$h = '<span class="btn btn-readonly '.$btn.' invoice-status-'.$eInvoice['status'].'-button">'.self::p('status')->values[$eInvoice['status']].'</span>';
		}

		return $h;

	}

	public function getBatch(): string {

		$menu = '<a data-ajax-submit="/selling/invoice:doSendCollection" data-confirm="'.s("Confirmer l'envoi des factures par e-mail aux clients ?").'" class="batch-menu-send batch-menu-item">';
			$menu .= \Asset::icon('envelope');
			$menu .= '<span>'.s("Envoyer par e-mail").'</span>';
		$menu .= '</a>';

		$danger = '<a data-ajax-submit="/selling/invoice:doDeleteCollection" data-confirm="'.s("Confirmer la suppression définitive de ces factures ?").'" class="batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group('batch-invoice', $menu, $danger, title: s("Pour les factures sélectionnées"));

	}

	public function createCustomer(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$eInvoice = new Invoice([
			'farm' => $eFarm
		]);

		$h = $form->openAjax('/selling/invoice:create', ['method' => 'get']);

			$h .= $form->group(
				s("Client"),
				$form->dynamicField($eInvoice, 'customer', function($d) {
					$d->attributes = [
						'data-autocomplete-select' => 'submit'
					];
				})
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-create',
			title: s("Générer une facture pour un client"),
			body: $h
		);

	}

	public function create(Invoice $eInvoice, \Collection $cSale, \Collection $cSaleMore, \Search $search): \Panel {

		$eInvoice->expects(['lastDate']);
		
		$body = $this->getGenerateBody(
			$eInvoice,
			'/selling/invoice:doCreate',
			function(\util\FormUi $form) use($cSale, $cSaleMore, $search) {
	
				$sales = '';

				if($cSale->count() > 0) {

					$sales .= $this->getSales($form, $cSale, TRUE);

				}

				if($search->empty()) {
					$sales .= '<div class="mb-1">';
						$sales .= '<a href="'.LIME_REQUEST.'&more=1">'.s("Ajouter d'autres ventes de ce client à cette facture").'</a>';
					$sales .= '</div>';
				} else {

					if($cSale->notEmpty()) {
						$sales .= '<h3>'.s("Ajouter d'autres ventes à la facture").'</h3>';
					}

					$sales .= '<div style="display: flex; column-gap: 1rem" class="mb-1">';
						$sales .= $form->inputGroup(
							$form->addon(s("Ventes de moins de")).
							$form->number('delivered', $search->get('delivered'), ['onkeypress' => 'return event.keyCode != 13;']).
							$form->inputAddon(s("jours")).
							$form->button(\Asset::icon('search'), ['onclick' => 'Sale.submitInvoiceSearch(this)'])
						);
					$sales .= '</div>';

					if($cSaleMore->notEmpty()) {

						$sales .= $this->getSales($form, $cSaleMore, FALSE);

					} else {
						$sales .= '<div class="util-empty">'.s("Il n'y a aucune vente de moins de {value} jours à afficher pour ce client. Seules les ventes déjà livrées et pour lesquelles aucune facture n'a été éditée par ailleurs peuvent être facturées.", $search->get('delivered')).'</div>';
					}

				}

				return $sales;

			}
		);

		return new \Panel(
			id: 'panel-invoice-create',
			title: s("Générer une facture pour un client"),
			body: $body
		);

	}

	public function selectMonthForCreateCollection(\farm\Farm $eFarm, \Collection $cCustomerGroup): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/invoice:createCollection', ['method' => 'get']);

			$h .= $form->hidden('farm', $eFarm);

			$h .= $form->group(
				s("Pour les ventes de quel mois voulez-vous générer les factures ?"),
				content: $form->month('month', date('Y-m', strtotime('last month')))
			);

			$h .= $form->group(
				s("Pour quelles ventes ?"),
				content: $form->select('type', $this->getSelectType($cCustomerGroup), attributes: ['placeholder' => s("Toutes les ventes"), 'group' => TRUE])
			);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-create-collection',
			title: s("Générer les factures d'un mois de ventes"),
			body: $h
		);

	}

	public function createCollection(\farm\Farm $eFarm, string $month, ?string $type, Invoice $e, \Collection $cSale, \Collection $cCustomerGroup): \Panel {

		$e->expects(['lastDate']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/invoice:doCreateCollection');

			$h .= $form->hidden('farm', $eFarm);

			$h .= $form->group(
				s("Mois"),
				$form->text(value: mb_ucfirst(\util\DateUi::textual($month, \util\DateUi::MONTH_YEAR)), attributes: ['disabled'])
			);

			if($type !== NULL) {

				$h .= $form->group(
					s("Clients"),
					$form->select(NULL, $this->getSelectType($cCustomerGroup), $type, ['disabled', 'group' => TRUE])
				);

			}

			if($cSale->notEmpty()) {

				$h .= $form->group(
					s("Clients à facturer"),
					self::getCustomers($form, $eFarm, $cSale)
				);

				$h .= '<div id="invoice-dates">';
					$h .= $form->dynamicGroup($e, 'date*');
					$h .= $form->dynamicGroup($e, 'dueDate');
				$h .= '</div>';

				$h .= '<div id="invoice-customize" class="hide">';
					$h .= $form->dynamicGroups($e, ['paymentCondition', 'header', 'footer']);
				$h .= '</div>';


				$h .= $form->group(
					content: '<div style="display: flex; justify-content: space-between">'.$form->submit(s("Générer les factures")).'<a onclick="Invoice.customize(this)" class="btn btn-outline-primary">'.s("Personnaliser avant de générer").'</a></div>'
				);

			} else {

				$h .= $form->group(
					content: '<p class="util-info">'.s("Aucune vente n'est éligible à la facturation pour ce mois.").'</p>'
				);

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-create-collection',
			back: '/selling/invoice:createCollection?farm='.$eFarm['id'].'',
			title: s("Générer les factures d'un mois de ventes"),
			body: $h
		);

	}

	protected function getSelectType(\Collection $cCustomerGroup): array {

		$values = [
			[
				'label' => s("Une partie des ventes"),
				'values' => [
					Customer::PRO => s("Ventes aux clients professionnels"),
					Customer::PRIVATE => s("Ventes aux clients particuliers"),
					\payment\MethodLib::TRANSFER => s("Ventes non payées par virement bancaire"),
				]
			]
		];

		if($cCustomerGroup->notEmpty()) {

			$list = [];
			foreach($cCustomerGroup as $eCustomerGroup) {
				$list[] = [
					'value' => $eCustomerGroup['id'],
					'label' => $eCustomerGroup['name'],
					'attributes' => ['style' => 'color: '.$eCustomerGroup['color']]
				];
			}

			$values[] = [
				'label' => s("Les ventes d'un groupe de clients"),
				'values' => $list
			];

		}

		return $values;

	}

	protected function getCustomers(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<table class="tr-even">';
			$h .= '<tr>';
				$h .= '<th>';
					$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this.firstParent(\'form\'), this.checked, \'[name^="sales"]\')').'"/>';
				$h .= '</th>';
				$h .= '<th>'.s("Client").'</th>';
				$h .= '<th class="text-end">'.s("Ventes").'</th>';
				$h .= '<th class="text-end">';
					$h .= s("Montant");
				$h .= '</th>';
			$h .= '</tr>';

		foreach($cSale as $eSale) {

			$h .= '<tr>';
				$h .= '<td class="td-min-content">'.$form->inputCheckbox('sales[]', $eSale['list']).'</td>';
				$h .= '<td>'.CustomerUi::link($eSale['customer'], newTab: TRUE).'</td>';
				$h .= '<td class="text-end">';
					$h .= '<a href="'.\farm\FarmUi::urlSellingSales($eFarm, \farm\Farmer::ALL).'?ids='.$eSale['list'].'" class="btn btn-sm btn-outline-secondary" target="_blank">'.$eSale['number'].'</a>';
				$h .= '</td>';
				$h .= '<td class="text-end">';
				$h .= SaleUi::getTotal($eSale);
				$h .= '</td>';
			$h .= '</tr>';
		}

		$h .= '</table>';

		return $h;

	}

	public function regenerate(Invoice $eInvoice): \Panel {

		$body = $this->getGenerateBody(
			$eInvoice,
			'/selling/invoice:doRegenerate'
		);

		return new \Panel(
			id: 'panel-invoice-regenerate',
			title: s("Regénérer une facture"),
			body: $body
		);

	}

	public function getGenerateBody(Invoice $eInvoice, string $page, ?\Closure $sales = NULL): string {

		$eInvoice->expects(['customer', 'farm']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax($page);

			$h .= $form->hidden('origin', GET('origin'));
			$h .= $form->hidden('customer', $eInvoice['customer']['id']);

			if($eInvoice->exists()) {

				$h .= $form->group(
					s("Facture"),
					self::link($eInvoice, newTab: TRUE)
				);

				$h .= $form->hidden('id', $eInvoice);

			}

			$customer = '<b>'.$eInvoice['customer']->getName().'</b>';

			if($eInvoice->exists() === FALSE) {
				$customer .= '<a href="/selling/invoice:create?farm='.$eInvoice['farm']['id'].'" class="btn btn-sm btn-secondary ml-1">'.s("Changer").'</a>';
			}

			$h .= $form->group(
				s("Client"),
				 $customer
			);

			if($sales !== NULL) {

				$h .= $form->group(
					s("Ventes à inclure dans la facture").
					\util\FormUi::info(s("Notez qu'une facture d'avoir est générée lorsque le montant total à facturer est négatif.")),
					$sales($form)
				);

			}

			$h .= '<div id="invoice-dates">';

				if($eInvoice->exists()) {

					$h .= $form->group(
						self::p('date')->label,
						\util\DateUi::numeric($eInvoice['date'])
					);

					// N'est pas pris en compte à l'édition, juste présent pour les routines JS de dueDate
					$h .= $form->hidden('date', $eInvoice['date']);

				} else {
					$h .= $form->dynamicGroup($eInvoice, 'date*');
				}

				$h .= $form->dynamicGroup($eInvoice, 'dueDate');

			$h .= '</div>';

			$h .= '<div id="invoice-customize" class="hide">';
				$h .= $form->dynamicGroups($eInvoice, ['paymentCondition', 'header', 'footer']);
			$h .= '</div>';


			$h .= $form->group(
				content: '<div style="display: flex; justify-content: space-between">'.$form->submit(s("Générer la facture")).'<a onclick="Invoice.customize(this)" class="btn btn-outline-primary">'.s("Personnaliser avant de générer").'</a></div>'
			);

		$h .= $form->close();

		return $h;

	}
	
	protected function getSales(\util\FormUi $form, \Collection $cSale, bool $checked): string {
		
		$h = '<table class="tr-even">';
			$h .= '<tr>';
				$h .= '<th>';

					if($checked === FALSE) {
						$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this.firstParent(\'form\'), this.checked, \'[data-invoice-checked="0"]\')').'"/>';
					}

				$h .= '</th>';
				$h .= '<th class="text-center">#</th>';
				$h .= '<th>'.s("Date").'</th>';
				$h .= '<th>'.s("Règlement").'</th>';
				$h .= '<th class="text-end">'.s("Montant").'</th>';
			$h .= '</tr>';

		foreach($cSale as $eSale) {

			$h .= '<tr>';
				$h .= '<td class="td-min-content">'.$form->inputCheckbox('sales[]', $eSale['id'], ['checked' => $checked, 'data-invoice-checked' => (int)$checked]).'</td>';
				$h .= '<td class="td-min-content text-center">'.SaleUi::link($eSale, newTab: TRUE).'</td>';
				$h .= '<td>'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
				$h .= '<td>';

					$h .= SaleUi::getPaymentMethodName($eSale);

					if($eSale['paymentStatus'] === Sale::PAID) {
						$h .= ' '.SaleUi::getPaymentStatus($eSale);
					}

				$h .= '</td>';
				$h .= '<td class="text-end">';
				$h .= SaleUi::getTotal($eSale);
				$h .= '</td>';
			$h .= '</tr>';
		}

		$h .= '</table>';

		return $h;
		
	}

	public function updatePayment(Invoice $eInvoice): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/invoice:doUpdatePayment');

			$h .= $form->hidden('id', $eInvoice['id']);

			$h .= $form->dynamicGroup($eInvoice, 'paymentMethod');
			$h .= $form->dynamicGroup($eInvoice, 'paymentStatus', function($d) {
				$d->default = fn(Invoice $eInvoice) => $eInvoice['paymentStatus'] ?? Sale::NOT_PAID;
			});

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-update',
			title: s("Modifier le règlement"),
			body: $h
		);

	}

	public function updateComment(Invoice $eInvoice): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/invoice:doUpdateComment');

			$h .= $form->hidden('id', $eInvoice['id']);
			$h .= $form->dynamicGroup($eInvoice, 'comment');

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-update',
			title: $eInvoice['comment'] === NULL ?
				s("Ajouter un commentaire interne") :
				s("Modifier le commentaire"),
			body: $h
		);

	}

	public function getIconEmail(Invoice $eInvoice): string {

		if($eInvoice['emailedAt'] !== NULL) {
			$color = '--success';
			$text = \Asset::icon('check-lg');
		} else {
			$color = '--muted';
			$text = \Asset::icon('x-lg');
		}

		return '<span style="color: var('.$color.')">'.$text.'</span>';

	}

	public function getIconPaid(Invoice $eInvoice): string {

		switch($eInvoice['paymentStatus']) {

			case Invoice::PAID :
				$color = '--success';
				$text = \Asset::icon('check-lg');
				break;

			case Invoice::NOT_PAID :
				$color = '--muted';
				$text = \Asset::icon('x-lg');
				break;

		}

		return '<span style="color: var('.$color.')">'.$text.'</span>';

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Invoice::model()->describer($property, [
			'date' => s("Date de facturation"),
			'dueDate' => s("Date d'échéance"),
			'paymentCondition' => s("Conditions de paiement"),
			'header' => s("Texte personnalisé affiché en haut de facture"),
			'footer' => s("Texte personnalisé affiché en bas de facture"),
			'paymentMethod' => s("Moyen de paiement"),
			'paymentStatus' => s("État du paiement"),
			'comment' => s("Commentaire interne"),
		]);

		switch($property) {

			case 'customer' :
				$d->autocompleteBody = function(\util\FormUi $form, Invoice $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'withCollective' => 0
					];
				};
				new CustomerUi()->query($d);
				break;

			case 'status' :
				$d->values = [
					Invoice::DRAFT => s("Brouillon"),
					Invoice::CONFIRMED => s("Confirmée"),
					Invoice::GENERATED => s("Générée"),
					Invoice::CANCELED => s("Annulée"),
				];
				$d->shortValues = [
					Invoice::DRAFT => s("B"),
					Invoice::CONFIRMED => s("C"),
					Invoice::GENERATED => s("G"),
					Invoice::CANCELED => s("A"),
				];
				break;

			case 'date' :
				$d->labelAfter = fn(Invoice $e) => \util\FormUi::info($e['lastDate'] !== NULL ? s("Vous devez respecter la chronologie des dates de facturation dans l'édition de vos factures. Compte tenu des factures déjà générées, vous ne pouvez pas facturer antérieurement au {value}.", \util\DateUi::numeric($e['lastDate'])) : s("Vous devez respecter la chronologie des dates de facturation dans l'édition de vos factures. Dès lors que vous aurez généré une première facture, vous ne pourrez plus générer d'autres factures à une date antérieure."));
				break;

			case 'dueDate' :
				$d->labelAfter = function(Invoice $e) {

					if($e->exists()) {
						return '';
					}

					if($e['farm']->getConf('invoiceDue') === FALSE) {
						return \util\FormUi::info(s("Vous pouvez <link>définir une date d'échéance</link> par défaut.", ['link' => '<a href="/farm/configuration:update?id='.$e['farm']['id'].'" target="_blank">']));
					}

					$dueDays = $e['farm']->getConf('invoiceDueDays');
					$dueMonth = $e['farm']->getConf('invoiceDueMonth');

					$update = ' (<a href="/farm/configuration:update?id='.$e['farm']['id'].'" target="_blank">'.s("modifier").'</a>)';

					if($dueDays !== NULL and $dueMonth) {
						return \util\FormUi::info(p("La date d'échéance est calculée à la date de facturation + {value} jour fin de mois", "La date d'échéance est calculée à la date de facturation + {value} jours fin de mois", $dueDays).$update);
					} else if($dueDays !== NULL) {
						return \util\FormUi::info(p("La date d'échéance est calculée à la date de facturation + {value} jour", "La date d'échéance est calculée à la date de facturation + {value} jours", $dueDays).$update);
					} else if($dueMonth) {
						return \util\FormUi::info(s("La date d'échéance est calculée à la fin du mois de la date de facturation").$update);
					}

				};
				$d->default = function(Invoice $e) {

					if($e->exists()) {
						return $e['dueDate'];
					} else {

						$dueDays = $e['farm']->getConf('invoiceDueDays');
						$dueMonth = $e['farm']->getConf('invoiceDueMonth');

						if($dueDays === NULL and $dueMonth === NULL) {
							return NULL;
						}

						if($dueDays !== NULL) {
							$calculatedDate = date('Y-m-d', strtotime($e['date'].' + '.$dueDays.' days'));
						} else {
							$calculatedDate = $e['date'];
						}

						if($dueMonth) {
							$calculatedDate = substr($calculatedDate, 0, 8).date('t', strtotime($calculatedDate));
						}

						return $calculatedDate;

					}

				};
				$d->attributes = fn(\util\FormUi $form, Invoice $e) => [
					'data-due-days' => $e['farm']->getConf('invoiceDueDays') ?? '',
					'data-due-month' => (int)$e['farm']->getConf('invoiceDueMonth'),
				];
				break;

			case 'paymentCondition' :
				$d->placeholder = s("Exemple : Paiement à réception de facture.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement pour régler cette facture ou si cette facture est acquittée."));
				break;

			case 'paymentMethod' :
				$d->values = fn(Invoice $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->attributes['onrender'] = 'Invoice.changePaymentMethod(this)';
				$d->attributes['onchange'] = 'Invoice.changePaymentMethod(this)';
				$d->placeholder = s("Non défini");
				break;

			case 'paymentStatus' :
				$d->values = [
					Invoice::PAID => s("Payé"),
					Invoice::NOT_PAID => s("Non payé"),
				];
				$d->field = 'switch';
				$d->attributes = [
					'labelOn' => $d->values[Sale::PAID],
					'labelOff' => $d->values[Sale::NOT_PAID],
					'valueOn' => Sale::PAID,
					'valueOff' => Sale::NOT_PAID,
				];
				break;

		}

		return $d;

	}

}
?>
