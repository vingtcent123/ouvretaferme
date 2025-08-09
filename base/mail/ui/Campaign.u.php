<?php
namespace mail;

class CampaignUi {

	public function __construct() {

	}

	public function create(Campaign $eCampaign): \Panel {

		$form = new \util\FormUi();

		$eFarm = $eCampaign['farm'];

		$h = '';

		$h .= $form->openAjax('/mail/campaign:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroup($eCampaign, 'to*');

			$h .= $form->group(
				content: $form->submit(s("Créer la campagne"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-campaign-create',
			title: s("Programmer une campagne"),
			body: $h
		);

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCampaign, int $nCampaign, int $page) {

		$h = '';

		if($cCampaign->empty()) {

			$h .= '<div class="util-empty">'.s("Il n'y a aucune campagne à afficher.").'</div>';
			return $h;

		}

		$h .= '<div class="stick-md util-overflow-xs">';

			$h .= '<table class="campaign-item-table tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.s("Date").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCampaign as $eCampaign) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= '<div class="text-center">'.\util\DateUi::numeric($eCampaign['scheduledAt'], \util\DateUi::DATE).'</div>';
						$h .= '</td>';

						$h .= '<td class="text-center hide-sm-down">';
							$h .= \util\DateUi::numeric($eCampaign['createdAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="td-min-content">';
							$h .= '<a data-ajax="/mail/campaign:doDelete" post-id="'.$eCampaign['id'].'" data-confirm="'.s("Vous allez supprimer une campagne. Continuer ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		if($nCampaign !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nCampaign / 100);
		}

		$h .= '<div class="util-info">';
			$h .= s("* Les statistiques d'e-mails reçus, lus et bloqués sont des estimations qui ne sont pas fiables à 100 %.");
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Campaign::model()->describer($property, [
			'to' => s("Destinataires"),
		]);

		switch($property) {

			case 'to' :
				$d->autocompleteDefault = fn(Campaign $e) => $e['to'] ?? [];
				$d->autocompleteBody = function(\util\FormUi $form, Campaign $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				new \mail\ContactUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'to'];
				break;


		}

		return $d;

	}

}
?>
