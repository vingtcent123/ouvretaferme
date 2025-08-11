<?php
namespace mail;

class CampaignUi {

	public function __construct() {

		\Asset::css('mail', 'campaign.css');
		\Asset::js('mail', 'campaign.js');

	}

	public function createSelect(\farm\Farm $eFarm, \Collection $cGroup, \Collection $ccShop): \Panel {

		$h = '';

		$h .= '<div class="util-block-help">'.s("Envoyez un e-mail groupé à tous les clients qui correspondent à l'un des critères ci-dessus.").'</div>';

		if($cGroup->notEmpty()) {

			$h .= '<div class="util-block bg-background-light mb-2">';

				$h .= '<h3>'.s("À destination d'un groupe de clients").'</h3>';

				$h .= '<div class="campaign-list">';
					foreach($cGroup as $eGroup) {
						$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::GROUP.'&group='.$eGroup['id'].'" class="btn color-white btn-lg" style="background-color: '.$eGroup['color'].'">'.encode($eGroup['name']).'</a>';
					}
				$h .= '</div>';

			$h .= '</div>';

		}

		if($ccShop['selling']->notEmpty()) {

			$h .= '<div class="util-block bg-background-light mb-2">';

				$h .= '<h3>'.s("À destination des clients ayant déjà commandé sur une boutique en ligne").'</h3>';

				if($ccShop['selling']->notEmpty()) {

					$h .= '<div class="campaign-list">';
						foreach($ccShop['selling'] as $eShop) {
							$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::SHOP.'&shop='.$eShop['id'].'" class="btn btn-primary btn-lg">'.encode($eShop['name']).'</a>';
						}
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		$h .= '<div class="util-block bg-background-light mb-2">';

			$h .= '<h3>'.s("À destination des clients particuliers ayant commandé il y a ...").'</h3>';

			$h .= '<div class="campaign-list">';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&period=1" class="btn btn-primary btn-lg">'.s("Moins de 1 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&period=3" class="btn btn-primary btn-lg">'.s("Moins de 3 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&period=6" class="btn btn-primary btn-lg">'.s("Moins de 6 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&period=12" class="btn btn-primary btn-lg">'.s("Moins de 1 an").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-block bg-background-light mb-2">';

			$h .= '<h3>'.s("Autre").'</h3>';

			$h .= '<div class="campaign-list">';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'" class="btn btn-primary btn-lg">';
					$h .= s("Choisir les destinataires parmi les contacts");
				$h .= '</a>';

				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::NEWSLETTER.'" class="btn btn-primary btn-lg">';
					$h .= s("Envoyer aux contacts inscrits à la newsletter");
				$h .= '</a>';

			$h .= '</div>';
		$h .= '</div>';

		return new \Panel(
			id: 'panel-campaign-create',
			title: s("Programmer une campagne"),
			body: $h
		);

	}

	public function create(Campaign $eCampaign): string {

		$form = new \util\FormUi([
			'firstColumnSize' => 25
		]);

		$eFarm = $eCampaign['farm'];

		$h = $form->openAjax('/mail/campaign:doCreate', ['id' => 'campaign-create']);

			switch($eCampaign['source']) {

				case Campaign::GROUP :
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous les clients du groupe <u>{name}</link>.", ['name' => encode($eCampaign['sourceGroup']['name'])]).'</div>';
					break;

				case Campaign::SHOP :
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous les clients que vous avez déjà livrés sur la boutique <link>{name}</link>.", ['link' => '<a href="'.\shop\ShopUi::url($eCampaign['sourceShop']).'">', 'name' => encode($eCampaign['sourceShop']['name'])]).'</div>';
					break;

				case Campaign::PERIOD :
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous les clients ayant été livrés il y a moins de <b>{value} mois</link>.", $eCampaign['sourcePeriod']).'</div>';
					break;

				case Campaign::NEWSLETTER :
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous vos contacts inscrits à votre newsletter.").'</div>';
					break;

			}

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups($eCampaign, ['scheduledAt', 'subject']);

			$h .= $form->group(
				self::p('html')->label.\util\FormUi::info(s("L'e-mail envoyé contiendra toujours le bandeau et la signature que vous avez défini sur la <link>page de configuration des e-mails</link>.", ['link' => '<a href="/farm/farm:updateEmail?id='.$eCampaign['farm']['id'].'" target="_blank">'])),
				'<div class="util-block">'.$form->dynamicField($eCampaign, 'html').'</div>'
			);

			$h .= $form->group(
				self::p('to')->label.'  <span class="util-counter" id="campaign-contacts">'.$eCampaign['cContact']->count().'</span>',
				$form->dynamicField($eCampaign, 'to')
			);

			$h .= $form->group(
				content: $form->submit(s("Créer un brouillon"), ['class' => 'btn btn-primary btn-lg'])
			);

		$h .= $form->close();

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCampaign, int $nCampaign, int $page) {

		$h = '';

		if($cCampaign->empty()) {

			$h .= '<div class="util-empty">'.s("Vous n'avez pas encore créé de campagne d'envoi d'e-mails.").'</div>';
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
			'scheduledAt' => s("Programmer l'envoi de l'e-mail"),
			'subject' => s("Titre de l'e-mail"),
			'html' => s("Contenu de l'e-mail"),
			'to' => s("Destinataires"),
		]);

		switch($property) {

			case 'scheduledAt' :
				$d->prepend = s("Envoyer le");
				$d->after = fn(\util\FormUi $form, Campaign $e) => ($e->getMinScheduledAt() > currentDatetime()) ? \util\FormUi::info(s("Au plus tôt le {date} pour laisser le temps d'amender l'e-mail si nécessaire", ['date' => \util\DateUi::numeric($e->getMinScheduledAt(), \util\DateUi::DATE_HOUR_MINUTE)])) : '';
				$d->default = fn(Campaign $e) => $e->getMinScheduledAt();
				break;

			case 'html' :
				$d->before = fn(\util\FormUi $form, Campaign $e) => DesignUi::getBanner($e['farm']);
				$d->after = fn(\util\FormUi $form, Campaign $e) => '<br/>'.DesignUi::getFooter($e['farm']);
				$d->placeholder = s("Tapez votre texte ici...");
				break;

			case 'to' :
				$d->autocompleteDefault = fn(Campaign $e) => $e['cContact'] ?? new \Collection();
				$d->autocompleteDispatch = '#campaign-create';
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
