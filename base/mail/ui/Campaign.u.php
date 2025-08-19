<?php
namespace mail;

class CampaignUi {

	public function __construct() {

		\Asset::css('mail', 'campaign.css');
		\Asset::js('mail', 'campaign.js');

	}

	public static function unsubscribe(\farm\Farm $eFarm, ?string $email = NULL): string {

		$link = \Lime::getUrl().\farm\FarmUi::url($eFarm).'/optIn';

		if($email !== NULL) {
			$link .= '?input='.\main\CryptLib::encrypt($email, 'mail');
		}

		$h = '<p style="font-size: 90%; border-top: 1px solid #888; margin-top: 30px; padding-top: 15px">';
			$h .= s("Vous pouvez vous désinscrire de ces communications par e-mail en suivant ce lien :").'<br/>';
			$h .= '<a href="'.encode($link).'">'.encode($link).'</a>';
		$h .= '</p>';

		return $h;

	}

	public function createSelect(\farm\Farm $eFarm, \Collection $cGroup, \Collection $ccShop): \Panel {

		$h = '';

		$h .= '<div class="util-block-help">'.s("Envoyez un e-mail groupé à tous les clients qui correspondent à l'un des critères ci-dessus.").'</div>';

		if($cGroup->notEmpty()) {

			$h .= '<div class="util-block bg-background-light mb-2">';

				$h .= '<h3>'.s("À destination d'un groupe de clients").'</h3>';

				$h .= '<div class="campaign-list">';
					foreach($cGroup as $eGroup) {
						$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::GROUP.'&sourceGroup='.$eGroup['id'].'" class="btn color-white btn-lg" style="background-color: '.$eGroup['color'].'">'.encode($eGroup['name']).'</a>';
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
							$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::SHOP.'&sourceShop='.$eShop['id'].'" class="btn btn-primary btn-lg">'.encode($eShop['name']).'</a>';
						}
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		$h .= '<div class="util-block bg-background-light mb-2">';

			$h .= '<h3>'.s("À destination des clients particuliers ayant commandé il y a ...").'</h3>';

			$h .= '<div class="campaign-list">';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&sourcePeriod=1" class="btn btn-primary btn-lg">'.s("Moins de 1 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&sourcePeriod=3" class="btn btn-primary btn-lg">'.s("Moins de 3 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&sourcePeriod=6" class="btn btn-primary btn-lg">'.s("Moins de 6 mois").'</a>';
				$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::PERIOD.'&sourcePeriod=12" class="btn btn-primary btn-lg">'.s("Moins de 1 an").'</a>';
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
			id: 'panel-campaign-write',
			title: s("Programmer une campagne"),
			body: $h
		);

	}

	public function create(Campaign $eCampaign): string {

		$eCampaign->expects(['farm', 'source']);

		$form = new \util\FormUi([
			'firstColumnSize' => 25
		]);

		$eFarm = $eCampaign['farm'];

		$h = $form->openAjax('/mail/campaign:doCreate', ['id' => 'campaign-write']);

			$h .= $form->hidden('source', $eCampaign['source']);

			switch($eCampaign['source']) {

				case Campaign::GROUP :
					$h .= $form->hidden('sourceGroup', $eCampaign['sourceGroup']);
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous les clients du groupe <u>{name}</u>.", ['name' => encode($eCampaign['sourceGroup']['name'])]).'</div>';
					break;

				case Campaign::SHOP :
					$h .= $form->hidden('sourceShop', $eCampaign['sourceShop']);
					$h .= '<div class="util-block-secondary">'.s("Vous allez envoyer un e-mail à tous les clients que vous avez déjà livrés sur la boutique <link>{name}</link>.", ['link' => '<a href="'.\shop\ShopUi::url($eCampaign['sourceShop']).'">', 'name' => encode($eCampaign['sourceShop']['name'])]).'</div>';
					break;

				case Campaign::PERIOD :
					$h .= $form->hidden('sourcePeriod', $eCampaign['sourcePeriod']);
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous les clients ayant été livrés il y a moins de <b>{value} mois</b>.", $eCampaign['sourcePeriod']).'</div>';
					break;

				case Campaign::NEWSLETTER :
					$h .= '<div class="util-block-help">'.s("Vous allez envoyer un e-mail à tous vos contacts inscrits à votre newsletter.").'</div>';
					break;

			}

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $this->getWrite($form, $eCampaign);

			$content = '<div class="util-block">';
				$content .= '<p class="color-muted">'.s("Vous pourrez modifier votre campagne jusqu'à la date d'envoi que vous avez programmée.").'</p>';
				$content .= $form->submit(s("Programmer la campagne"), ['class' => 'btn btn-primary btn-lg']);
			$content .= '</div>';

			$h .= $form->group(
				content: $content
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Campaign $eCampaign): \Panel {

		$form = new \util\FormUi([
			'firstColumnSize' => 25
		]);

		$h = $form->openAjax('/mail/campaign:doCreate', ['id' => 'campaign-write']);

			$h .= $form->hidden('id', $eCampaign['id']);

			$h .= $this->getWrite($form, $eCampaign);

		$h .= $form->close();


		return new \Panel(
			id: 'panel-campaign-update',
			title: s("Modifier une campagne"),
			dialogOpen: $form->openAjax('/mail/campaign:doUpdate', ['class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			footer: $form->submit(s("Enregistrer"), ['class' => 'btn btn-primary btn-lg']),
		);

	}

	protected function getWrite(\util\FormUi $form, Campaign $eCampaign): string {

		$h = $form->dynamicGroups($eCampaign, ['scheduledAt', 'subject']);


		$content = '<div class="util-block mb-0">'.$form->dynamicField($eCampaign, 'content').'</div>';

		if($eCampaign['cCampaignLast']->notEmpty()) {

			$action = '<a data-dropdown="bottom-end" class="dropdown-toggle">'.s("Utiliser titre et contenu d'une ancienne campagne").'</a>';
			$action .= '<div class="dropdown-list bg-secondary">';

				foreach($eCampaign['cCampaignLast'] as $eCampaignLast) {
					$action .= '<a href="" class="dropdown-item" '.attr('data-subject', $eCampaignLast['subject']).' '.attr('data-content', $form->editor('content', $eCampaignLast['content'])).'>'.encode($eCampaignLast['subject']).'<br/><small>'.\util\DateUi::numeric($eCampaignLast['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE).'</small></a>';
				}

			$action .= '</div>';

			$content .= \util\FormUi::getFieldAction($action);

		}


		$h .= $form->group(
			self::p('content')->label.\util\FormUi::info(s("L'e-mail envoyé contiendra toujours le bandeau et la signature que vous avez défini sur la <link>page de configuration des e-mails</link>.", ['link' => '<a href="/farm/farm:updateEmail?id='.$eCampaign['farm']['id'].'" target="_blank">'])),
			$content
		);

		$label = self::p('to')->label.'  <span class="util-counter" id="campaign-contacts">'.$eCampaign['cContact']->count().'</span>';

		if(
			$eCampaign->exists() and
			count($eCampaign['to']) !== $eCampaign['cContact']->count()
		) {
			$label .= \util\FormUi::info(s("Les contacts pour qui vous avez désactivé l'envoi des e-mails ainsi que ceux qui ont refusé vos communications ont été retirés de la liste."));
		}

		$h .= $form->group(
			$label,
			$form->dynamicField($eCampaign, 'to'),
			['wrapper' => 'to']
		);

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCampaign, int $nCampaign, int $page) {

		$h = '';

		if($cCampaign->empty()) {

			$h .= '<div class="util-empty">'.s("Vous n'avez pas encore créé de campagne d'envoi d'e-mails.").'</div>';
			return $h;

		}

		$h .= '<div class="stick-md util-overflow-md">';

			$h .= '<table class="campaign-item-table tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th class="td-min-content" rowspan="2">'.s("Date d'envoi").'</th>';
						$h .= '<th rowspan="2">'.s("Titre").'</th>';
						$h .= '<th colspan="5" class="text-center">'.s("Statistiques *").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-center highlight-stick-right">'.s("Destinataires").'</th>';
						$h .= '<th class="text-center highlight-stick-both">'.s("Envoyés").'</th>';
						$h .= '<th class="text-center highlight-stick-both">'.s("Reçus").'</th>';
						$h .= '<th class="text-center highlight-stick-both">'.s("Lus").'</th>';
						$h .= '<th class="text-center highlight-stick-left">'.s("Bloqués").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCampaign as $eCampaign) {

					$h .= '<tr>';

						$h .= '<td class="td-min-content">';
							$h .= \util\DateUi::numeric($eCampaign['scheduledAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td>';
							$h .= encode($eCampaign['subject']);
							$h .= '<div class="util-annotation">';

								switch($eCampaign['source']) {

									case Campaign::SHOP :
										$h .= s("Pour les clients de {value}", \shop\ShopUi::link($eCampaign['sourceShop']));
										break;

									case Campaign::GROUP :
										$h .= s("Pour le groupe de clients {value}", '<span class="util-badge" style="background-color: '.$eCampaign['sourceGroup']['color'].'">'.encode($eCampaign['sourceGroup']['name']).'</span>');
										break;

									case Campaign::PERIOD :
										$h .= s("Pour les clients livrés il y a moins de {value} mois", $eCampaign['sourcePeriod']);
										break;

									case Campaign::NEWSLETTER :
										$h .= s("Newsletter");
										break;

								}

							$h .= '</div>';
						$h .= '</td>';

						$scheduled = '<span style="font-size: 1.25rem">'.$eCampaign['scheduled'].'</span>';

						switch($eCampaign['status']) {

							case Campaign::CONFIRMED :
								$h .= '<td class="campaign-item-stat highlight-stick-right">';
									$h .= $scheduled;
								$h .= '</td>';
								$h .= '<td colspan="4" class="text-center highlight-stick-left color-warning">';
									$h .= \Asset::icon('alarm').' '.s("Envoi programmé le {date}", ['date' => \util\DateUi::numeric($eCampaign['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
								$h .= '</td>';
								break;

							case Campaign::SENT :
								$h .= '<td class="campaign-item-stat highlight-stick-right">';
									$h .= $scheduled;
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-both">';
									$h .= '<span style="font-size: 1.25rem">'.$eCampaign['sent'].'</span>';
									$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($eCampaign['delivered'] / $eCampaign['scheduled'] * 100)).'</div>';
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-both">';
									if($eCampaign['sent'] > 0) {
										$h .= '<span>'.$eCampaign['delivered'].'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($eCampaign['delivered'] / $eCampaign['sent'] * 100)).'</div>';
									}
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-both">';
									if($eCampaign['delivered'] > 0) {
										$h .= '<span>'.$eCampaign['opened'].'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($eCampaign['opened'] / $eCampaign['delivered'] * 100)).'</div>';
									}
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-left">';
									if($eCampaign['sent'] > 0) {
										$blocked = $eCampaign['failed'] + $eCampaign['spam'];
										$h .= '<span '.($blocked > 0 ? 'class="color-danger"' : '').'>'.$blocked.'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($blocked / $eCampaign['sent'] * 100)).'</div>';
									}
								$h .= '</td>';
								break;

						}

						$h .= '<td class="td-min-content">';
							if($eCampaign->acceptUpdate()) {
								$h .= '<a href="/mail/campaign:update?id='.$eCampaign['id'].'" class="btn btn-secondary">'.\Asset::icon('gear-fill').'</a> ';
							}
							if($eCampaign->acceptDelete()) {
								$h .= '<a data-ajax="/mail/campaign:doDelete" post-id="'.$eCampaign['id'].'" data-confirm="'.s("Vous allez supprimer une campagne et les e-mails ne seront pas envoyés. Continuer ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
							}
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
			'content' => s("Contenu de l'e-mail"),
			'to' => s("Destinataires"),
		]);

		switch($property) {

			case 'scheduledAt' :
				$d->prepend = s("Envoyer le");
				$d->after = fn(\util\FormUi $form, Campaign $e) => ($e->getMinScheduledAt() > currentDatetime()) ? \util\FormUi::info(s("Au plus tôt le {date} pour laisser le temps d'amender l'e-mail si nécessaire", ['date' => \util\DateUi::numeric($e->getMinScheduledAt(), \util\DateUi::DATE_HOUR_MINUTE)])) : '';
				break;

			case 'subject' :
				$d->attributes = ['data-limit' => Campaign::model()->getPropertyRange('subject')[1]];
				break;

			case 'content' :
				$d->before = fn(\util\FormUi $form, Campaign $e) => DesignUi::getBanner($e['farm']);
				$d->after = fn(\util\FormUi $form, Campaign $e) => '<br/>'.DesignUi::getFooter($e['farm']).self::unsubscribe($e['farm']);
				$d->placeholder = s("Tapez votre texte ici...");
				break;

			case 'to' :
				$d->autocompleteDefault = fn(Campaign $e) => $e['cContact'] ?? new \Collection();
				$d->autocompleteDispatch = '#campaign-write';
				$d->autocompleteBody = function(\util\FormUi $form, Campaign $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'new' => TRUE
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
