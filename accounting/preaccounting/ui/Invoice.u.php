<?php

namespace preaccounting;

Class InvoiceUi {

	public function importInvoiceCollection(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = $form->openAjax(
			\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoiceCollection',
			[
				'id' => 'preaccounting-import-invoices',
			],
		);

		$h .= '<div class="util-block-info">';
			$h .= '<p>'.p("Vous allez importer {value} facture en comptabilité.<br/>Souhaitez-vous continuer ?", "Vous allez importer {value} factures en comptabilité.<br/>Souhaitez-vous continuer ?", count(get('ids', 'array'))).'</p>';
			$h .= $form->submit(s("Importer"), ['data-waiter' => s("Import en cours..."), 'class' => 'btn btn-transparent']);
		$h .= '</div>';
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}


		$h .= $form->close();

		return new \Panel(
			id: 'panel-preaccounting-import-invoices',
			title: p("Importer une facture", "Importer en masse des factures", count(get('ids', 'array'))),
			body: $h,
		);
	}

}
