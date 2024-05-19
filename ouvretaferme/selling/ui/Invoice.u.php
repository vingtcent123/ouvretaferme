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
		return '<a href="'.self::url($eInvoice).'" data-ajax-navigation="never" class="btn btn-sm btn-outline-primary" '.($newTab ? 'target="_blank"' : '').'>'.$eInvoice->getInvoice().'</a>';
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

		$h = '<div id="sale-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Numéro")]);
					$h .= $form->text('customer', $search->get('customer'), ['placeholder' => s("Client")]);
					$h .= $form->text('date', $search->get('date'), ['placeholder' => s("Date de facturation")]);
					$h .= $form->select('paymentStatus', self::p('paymentStatus')->values, $search->get('paymentStatus'), ['placeholder' => s("Réglée ?")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\Collection $cInvoice, ?int $nInvoice = NULL, array $hide = [], ?int $page = NULL) {

		if($cInvoice->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune facture à afficher.").'</div>';
		}

		$h = '<div class="util-overflow-sm stick-xs">';

			$columns = 7;

			$h .= '<table class="tr-bordered tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';/*
						$h .= '<th class="td-min-content">';
						$h .= '</th>';*/
						$h .= '<th class="text-center">#</th>';
						if(in_array('customer', $hide) === FALSE) {
							$columns++;
							$h .= '<th class="invoice-item-customer">'.s("Client").'</th>';
						}
						$h .= '<th class="text-center">'.s("Date de facturation").'</th>';
						$h .= '<th class="text-end invoice-item-amount">'.s("Montant").'</th>';
						$h .= '<th>'.s("Envoyée<br/>par e-mail").'</th>';
						$h .= '<th>'.s("Réglée").'</th>';
						$h .= '<th class="invoice-item-list hide-sm-down">'.s("Ventes").'</th>';
						$h .= '<th></th>';
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

						$h .= '<tr>';/*
							$h .= '<th class="td-min-content invoice-item-select">';
								$h .= '<label title="'.s("Cocher ces factures / Décocher ces factures").'">';
									$h .= '<input type="checkbox" class="batch-all" onclick="Invoice.toggleDaySelection(this)"/>';
								$h .= '</label>';
							$h .= '</th>';*/
							$h .= '<td colspan="'.$columns.'" class="invoice-item-date">';
								$h .= match($currentSubtitle) {
									currentDate() => s("Aujourd'hui"),
									default => \util\DateUi::textual($currentSubtitle)
								};
							$h .= '</td>';
						$h .= '</tr>';

						$previousSubtitle = $currentSubtitle;

					}

					$batch = [];

					if(in_array($eInvoice['generation'], [Invoice::PROCESSING, Invoice::WAITING])) {
						$class = 'invoice-item-waiting';
					} else {
						$class = 'invoice-item-'.$eInvoice['paymentStatus'];
					}

					$h .= '<tr class="'.$class.'">';
/*
						$h .= '<td class="td-min-content sale-item-select">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eInvoice['id'].'" oninput="Sale.changeSelection()" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</label>';
						$h .= '</td>';
*/
						$h .= '<td class="text-center td-min-content">';
							if($eInvoice['content']->empty()) {
								$h .= '<span class="btn disabled">'.$eInvoice->getInvoice().'</span>';
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
							$h .= '</td>';
						}

						$h .= '<td class="text-center">';
							$h .= \util\DateUi::numeric($eInvoice['date']);
						$h .= '</td>';

						$h .= '<td class="text-end invoice-item-amount">';
							$h .= SaleUi::getTotal($eInvoice);
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

								$h .= '<td>';
									$h .= $this->getIconEmail($eInvoice);
								$h .= '</td>';

								$h .= '<td>';
									if($eInvoice->isCreditNote() === FALSE) {
										if($eInvoice->canWrite()) {
											$h .= $eInvoice->quick('paymentStatus', $this->getIconPaid($eInvoice));
										} else {
											$h .= $this->getIconPaid($eInvoice);
										}
									}
								$h .= '</td>';

								$h .= '<td class="invoice-item-list hide-sm-down">';
									foreach($eInvoice['cSale'] as $eSale) {
										$h .= SaleUi::link($eSale).' ';
									}

									if($eInvoice['description']) {
										$h .= '<div class="invoice-item-description util-info">';
											$h .= $eInvoice->quick('description', encode($eInvoice['description']));
										$h .= '</div>';
									}
								$h .= '</td>';

								break;

						}

						$h .= '<td class="text-end">';

							if($eInvoice['content']->notEmpty()) {
								$h .= '<a href="'.self::url($eInvoice).'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.\Asset::icon('download').'</a> ';
							}

							if($eInvoice->canWrite()) {

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.s("Facture {value}", $eInvoice->getInvoice()).'</div>';

									$h .= '<a href="/selling/invoice:update?id='.$eInvoice['id'].'" class="dropdown-item">'.s("Modifier la facture").'</a>';

									if($eInvoice->acceptRegenerate()) {
										$h .= '<a href="/selling/invoice:regenerate?id='.$eInvoice['id'].'" class="dropdown-item">'.s("Regénérer la facture").'</a>';
									}

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

		return $h;

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
		
		$body = $this->getGenerateBody(
			$eInvoice,
			$cSale,
			'/selling/invoice:doCreate',
			function(\util\FormUi $form) use ($cSale, $cSaleMore, $search) {

				$more = '';

				if($search->empty()) {
					$more .= '<div class="mb-1">';
						$more .= '<a href="'.LIME_REQUEST.'&more=1">'.s("Ajouter d'autres ventes de ce client à cette facture").'</a>';
					$more .= '</div>';
				} else {

					if($cSale->notEmpty()) {
						$more .= '<h3>'.s("Ajouter d'autres ventes à la facture").'</h3>';
					}

					$more .= '<div style="display: flex; column-gap: 1rem" class="mb-1">';
						$more .= $form->inputGroup(
							$form->addon(s("Ventes de moins de")).
							$form->number('delivered', $search->get('delivered'), ['onkeypress' => 'return event.keyCode != 13;']).
							$form->addon(s("jours"))
						);
						$more .= $form->button(s("Filtrer"), ['onclick' => 'Sale.submitInvoiceSearch(this)']);
					$more .= '</div>';

					if($cSaleMore->notEmpty()) {

						$more .= $this->getSales($form, $cSaleMore, FALSE);

					} else {
						$more .= '<div class="util-info">'.s("Il n'y a aucune vente de moins de {value} jours à afficher pour ce client. Seules les ventes déjà livrées et pour lesquelles aucune facture n'a été éditée par ailleurs peuvent être facturées.", $search->get('delivered')).'</div>';
					}

				}

				return $more;

			}
		);

		return new \Panel(
			id: 'panel-invoice-create',
			title: s("Générer une facture pour un client"),
			body: $body
		);

	}

	public function selectMonthForCreateCollection(\farm\Farm $eFarm): \Panel {

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
				content: $form->select('type', $this->getSelectType(), attributes: ['placeholder' => s("Toutes les ventes")])
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

	public function createCollection(\farm\Farm $eFarm, string $month, ?string $type, Invoice $e, \Collection $cSale): \Panel {

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
					$form->select(NULL, $this->getSelectType(), $type, ['disabled'])
				);

			}

			if($cSale->notEmpty()) {

				$h .= $form->group(
					s("Ventes à facturer"),
					self::getCustomers($form, $eFarm, $cSale)
				);

				$h .= $form->dynamicGroups($e, ['date', 'paymentCondition']);

				$h .= $form->group(
					content: $form->submit(s("Générer les factures"))
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

	protected function getSelectType(): array {

		return [
			Customer::PRO => s("Ventes aux clients professionnels"),
			Customer::PRIVATE => s("Ventes aux clients particuliers"),
			Sale::TRANSFER => s("Ventes payées par virement bancaire dans vos boutiques"),
		];

	}

	protected function getCustomers(\util\FormUi $form, \farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<table class="tr-even tr-bordered">';
			$h .= '<tr>';
				$h .= '<th>';
					$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this, \'[name^="sales"]\')').'"/>';
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

	public function regenerate(Invoice $eInvoice, \Collection $cSale): \Panel {

		$body = $this->getGenerateBody(
			$eInvoice,
			$cSale,
			'/selling/invoice:doRegenerate'
		);

		return new \Panel(
			id: 'panel-invoice-regenerate',
			title: s("Regénérer une facture"),
			body: $body
		);

	}

	public function getGenerateBody(Invoice $eInvoice, \Collection $cSale, string $page, ?\Closure $moreSales = NULL): string {

		$eInvoice->expects(['customer', 'farm']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax($page);

			$h .= $form->hidden('origin', GET('origin'));
			$h .= $form->hidden('customer', $eInvoice['customer']['id']);

			if($eInvoice->offsetExists('id')) {

				$h .= $form->group(
					s("Facture"),
					self::link($eInvoice, newTab: TRUE)
				);

				$h .= $form->hidden('id', $eInvoice);

			}

			$h .= $form->group(
				s("Client"),
				'<div class="input-group">'.$form->text(value: $eInvoice['customer']['name'], attributes: ['disabled']).'<a href="/selling/invoice:create?farm='.$eInvoice['farm']['id'].'" class="btn btn-secondary">'.s("Changer").'</a></div>'
			);

			$sales = '';

			if($cSale->count() > 0) {

				$sales .= $this->getSales($form, $cSale, TRUE);

			}

			$sales .= $moreSales ? $moreSales($form) : '';

			$h .= $form->group(
				s("Ventes à inclure dans la facture").
				\util\FormUi::info(s("Notez qu'une facture d'avoir est générée lorsque le montant total à facturer est négatif.")),
				$sales
			);

			$h .= $form->dynamicGroups($eInvoice, ['date', 'paymentCondition']);


			$h .= $form->group(
				content: $form->submit(s("Générer la facture"))
			);

		$h .= $form->close();

		return $h;

	}
	
	protected function getSales(\util\FormUi $form, \Collection $cSale, bool $checked): string {
		
		$h = '<table class="tr-even tr-bordered">';
			$h .= '<tr>';
				$h .= '<th>';

					if($checked === FALSE) {
						$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this, \'[data-invoice-checked="0"]\')').'"/>';
					}

				$h .= '</th>';
				$h .= '<th class="text-center">#</th>';
				$h .= '<th>'.s("Date").'</th>';
				$h .= '<th class="text-end">'.s("Montant").'</th>';
			$h .= '</tr>';

		foreach($cSale as $eSale) {

			$h .= '<tr>';
				$h .= '<td class="td-min-content">'.$form->inputCheckbox('sales[]', $eSale['id'], ['checked' => $checked, 'data-invoice-checked' => (int)$checked]).'</td>';
				$h .= '<td class="td-min-content text-center">'.SaleUi::link($eSale, newTab: TRUE).'</td>';
				$h .= '<td>'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
				$h .= '<td class="text-end">';
				$h .= SaleUi::getTotal($eSale);
				$h .= '</td>';
			$h .= '</tr>';
		}

		$h .= '</table>';

		return $h;
		
	}

	public function update(Invoice $eInvoice): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/invoice:doUpdate');

			$h .= $form->hidden('id', $eInvoice['id']);

			$h .= $form->dynamicGroups($eInvoice, ['paymentStatus', 'description']);


			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-invoice-update',
			title: s("Modifier une facture"),
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
			'paymentCondition' => s("Conditions de paiement"),
			'paymentStatus' => s("Facturée réglée ?"),
			'description' => s("Observations internes"),
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
				(new CustomerUi())->query($d);
				break;

			case 'paymentCondition' :
				$d->placeholder = s("Exemple : Paiement à réception de facture.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement pour régler cette facture ou si cette facture est acquittée."));
				break;

			case 'paymentStatus' :
				$d->values = [
					Invoice::PAID => s("Réglée"),
					Invoice::NOT_PAID => s("Non réglée"),
				];
				$d->attributes = [
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

		}

		return $d;

	}

}
?>