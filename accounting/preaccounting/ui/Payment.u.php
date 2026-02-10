<?php

namespace preaccounting;

Class PaymentUi {

	public function importPaymentCollection(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = $form->openAjax(
			\farm\FarmUi::urlConnected($eFarm).'/preaccounting/import:doImportPaymentCollection',
			[
				'id' => 'preaccounting-import-payments',
			],
		);

		$h .= '<div class="util-block-info">';
			$h .= '<p>'.p("Vous allez importer {value} paiement en comptabilité.<br/>Souhaitez-vous continuer ?", "Vous allez importer {value} paiements en comptabilité.<br/>Souhaitez-vous continuer ?", count(get('ids', 'array'))).'</p>';
			$h .= $form->submit(s("Importer"), ['data-waiter' => s("Import en cours..."), 'class' => 'btn btn-transparent']);
		$h .= '</div>';
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}


		$h .= $form->close();

		return new \Panel(
			id: 'panel-preaccounting-import-payments',
			title: p("Importer un paiement", "Importer en masse des paiements", count(get('ids', 'array'))),
			body: $h,
		);
	}

}
