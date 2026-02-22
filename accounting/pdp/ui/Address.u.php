<?php
namespace pdp;

Class AddressUi {

	public function list(\farm\Farm $eFarm, \Collection $cAddress): string {

		$h = '';

		if($cAddress->count() <= 2) {

			$h .= '<div class="util-info">';
				$h .= s("Les <b>adresses techniques</b> sont utilisées pour recevoir des messages liées à des factures envoyées depuis l'adresse initialement transmise à votre partenaire. Elle ne peuvent pas être supprimées.");
			$h .= '</div>';

		}

		$h .= '<div class="stick-sm util-overflow-md">';
			$h .= '<table class="tr-even tr-hover">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.\pdp\AddressUi::p('identifier')->label.'</th>';
						$h .= '<th>'.\pdp\AddressUi::p('status')->label.'</th>';
						$h .= '<th>'.\pdp\AddressUi::p('createdAt')->label.'</th>';
						$h .= '<th class="text-center">'.s("Adresse technique ?").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';
				foreach($cAddress as $eAddress) {
					$h .= '<tr>';
						$h .= '<td>'.encode($eAddress->getIdentifier(FALSE)).'</td>';
						$h .= '<td>'.\pdp\AddressUi::p('status')->values[$eAddress['status']].'</td>';
						$h .= '<td>'.\util\DateUi::numeric($eAddress['createdAt']).'</td>';
						$h .= '<td class="text-center">';
							if($eAddress['isReplyTo']) {
								$h .= s("oui");
							} else {
								$h .= s("non");
							};
						$h .= '</td>';
						$h .= '<td class="text-center">';
							if($eAddress->acceptDelete()) {
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/pdp/address:doDelete" post-id="'.$eAddress['id'].'" class="btn btn-outline-danger" data-waiter="'.s("Suppression en cours...").'" data-confirm="'.s("Confirmez-vous cette suppression ?").'">'.\Asset::icon('trash').'</a>';
							}
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}
	public function create(\farm\Farm $eFarm, Address $eAddress): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax(\farm\FarmUi::urlConnected($eFarm).'/pdp/address:doCreate', ['id' => 'pdp-address-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();


		$h .= $form->dynamicGroup($eAddress, 'identifier*', function(\PropertyDescriber $d) use ($eFarm) {

			$info = s("En France, le format des adresses électroniques de facturation est le suivant :");
			$info .= '<ul>';
				$info .= '<li>'.s("SIREN, exemple : <i>{value}</i>", mb_substr($eFarm['siret'], 0, 9)).'</li>';
				$info .= '<li>'.s("SIREN_SIRET, exemple : <i>{value}</i>", mb_substr($eFarm['siret'], 0, 9).'_'.$eFarm['siret']).'</li>';
				$info .= '<li>'.s("SIREN_SUFFIXE, exemple : <i>{value}_DEPARTEMENTJURIDIQUE</i>", mb_substr($eFarm['siret'], 0, 9)).'</li>';
				$info .= '<li>'.s("SIREN_SIRET_CODEROUTAGE, exemple : <i>{value}_FACTURESPUBLIQUES</i>", mb_substr($eFarm['siret'], 0, 9).'_'.$eFarm['siret']).'</li>';
			$info .= '</ul>';
			$d->after = \util\FormUi::info($info);
		});

		$h .= $form->group(
			content: $form->submit(s("Créer l'adresse"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-pdp-address-create',
			title: s("Créer une adresse de facturation électronique"),
			body: $h
		);

	}


	public static function p(string $property): \PropertyDescriber {

		$d = Address::model()->describer($property, [
			'identifier' => s("Adresse électronique"),
			'status' => s("Statut"),
			'createdAt' => s("Créée le"),
		]);

		switch($property) {

			case 'status' :
				$d->values = [
					Address::SENDING => s("En cours d'envoi sur Super PDP"),
					Address::CREATED => s("Créée"),
					Address::PENDING => s("En attente"),
					Address::ERROR => s("En erreur"),
				];
				break;

			case 'isReplyTo' :
				$d->field = 'yesNo';
				break;
		}

		return $d;

	}

}
