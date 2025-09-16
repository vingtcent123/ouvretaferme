<?php
namespace association;

class AdminUi {

	public function __construct() {

		\Asset::css('association', 'admin.css');

	}

	public function create(\farm\Farm $eFarm, \Collection $cHistory, \Collection $cMethod): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/association/admin/:doCreate');

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('fromAdmin', 1);

		$eHistory = new History();
		$h .= $form->dynamicGroups($eHistory, ['type', 'amount', 'membership', 'paidAt'], ['amount' => function($d) {
			$d->default = 0;
			$d->append = s("€");
			$d->attributes = ['min' => 0];
		}, 'membership' => function($d) {
			$d->default = date('Y');
			$d->append = s("");
			$d->attributes = ['min' => date('Y')];
		}, 'paidAt' => function($d) {
			//$d->type = 'date';
			$d->default = date('Y-m-d H:i');
		}]);

		$h .= $form->group(s("Moyen de paiement"), $form->select('method', $cMethod));

		$h .= $form->group(
			content: $form->submit(s("Créer"))
		);

		$h .= $form->close();

		if($cHistory->count() > 0) {
			$h .= '<h3>'.s("Historique des adhésions et dons").'</h3>';
			$h .= '<table class="tr-even tr-hover">';
				$h .= '<tr>';
					$h .= '<th>'.s("Type").'</th>';
					$h .= '<th>'.s("Année").'</th>';
					$h .= '<th>'.s("Montant").'</th>';
					$h .= '<th>'.s("Statut").'</th>';
					$h .= '<th>'.s("Payé le").'</th>';
					$h .= '<th>'.s("Moyen de paiement").'</th>';
				$h .= '</tr>';

				foreach($cHistory as $eHistory) {
					$h .= '<tr>';
						$h .= '<td>'.HistoryUi::p('type')->values[$eHistory['type']].'</td>';
						$h .= '<td>'.($eHistory['membership'] ?? '').'</td>';
						$h .= '<td>'.($eHistory['amount'] ? \util\TextUi::money($eHistory['amount'], precision: 0) : s("Offert")).'</td>';
						$h .= '<td>'.HistoryUi::p('status')->values[$eHistory['status']].'</td>';
						$h .= '<td>'.($eHistory['paidAt'] ? \util\DateUi::textual($eHistory['paidAt']) : '-').'</td>';
						$h .= '<td>';
						if(($eHistory['sale']['cPayment'] ?? new \Collection())->notEmpty()) {
							$h .= join(', ', \selling\PaymentUi::getList($eHistory['sale']['cPayment']));
						}
						$h .= '</td>';
					$h .= '</tr>';
				}

			$h .= '</table>';

		}

		return new \Panel(
			id: 'panel-farm-create',
			title: encode($eFarm['name']),
			body: $h,
			close: 'reload'
		);

	}


}

?>
