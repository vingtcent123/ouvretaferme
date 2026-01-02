<?php

namespace preaccounting;

Class InvoiceUi {

	public function importInvoiceCollection(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = $form->openAjax(
			\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoiceCollection',
			[
				'id' => 'preaccounting-import-invoices',
				'class' => 'panel-dialog',
			],
		);

		$h .= '<div class="util-info">';
			$h .= p("Vous allez importer {value} facture en comptabilité. Souhaitez-vous continuer ?", "Vous allez importer {value} factures en comptabilité. Souhaitez-vous continuer ?", count(get('ids', 'array')));
		$h .= '</div>';
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}

		$h .= $form->group($form->submit(s("Enregistrer"), ['data-waiter' => s("Import en cours...")]));

		$h .= $form->close();

		return new \Panel(
			id: 'panel-preaccounting-import-invoices',
			title: p("Importer une facture", "Importer en masse les factures", count(get('ids', 'array'))),
			body: $h,
		);
	}

}
