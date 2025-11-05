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

	public function createSelect(\farm\Farm $eFarm, \Collection $cCustomerGroup, \Collection $ccShop): \Panel {

		$h = '';

		$h .= '<div class="util-block-help">'.s("Envoyez un e-mail groupé à tous les clients qui correspondent à l'un des critères ci-dessus.").'</div>';

		if($cCustomerGroup->notEmpty()) {

			$h .= '<div class="util-block bg-background-light mb-2">';

				$h .= '<h3>'.s("À destination d'un groupe de clients").'</h3>';

				$h .= '<div class="campaign-list">';
					foreach($cCustomerGroup as $eCustomerGroup) {
						$h .= '<a href="/mail/campaign:create?farm='.$eFarm['id'].'&source='.Campaign::GROUP.'&sourceGroup='.$eCustomerGroup['id'].'" class="btn color-white btn-lg" style="background-color: '.$eCustomerGroup['color'].'">'.encode($eCustomerGroup['name']).'</a>';
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
					$h .= '<div class="util-block-secondary">';
						$h .= s("Vous allez envoyer un e-mail à tous les clients que vous avez déjà livrés sur la boutique {shop}.", ['shop' => '<a href="'.\shop\ShopUi::adminUrl($eCampaign['farm'], $eCampaign['sourceShop']).'">'.encode($eCampaign['sourceShop']['name']).'</a>']);
					$h .= '</div>';
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

		$h = $form->hidden('id', $eCampaign['id']);
		$h .= $this->getWrite($form, $eCampaign);

		return new \Panel(
			id: 'panel-campaign-update',
			title: s("Modifier une campagne"),
			dialogOpen: $form->openAjax('/mail/campaign:doUpdate', ['class' => 'panel-dialog', 'id' => 'campaign-write']),
			dialogClose: $form->close(),
			body: $h,
			footer: $form->submit(s("Enregistrer"), ['class' => 'btn btn-primary btn-lg']),
		);

	}

	protected function getWrite(\util\FormUi $form, Campaign $eCampaign): string {

		$h = $form->dynamicGroup($eCampaign, 'scheduledAt');
		$h .= $this->getEmailFields($form, $eCampaign);

		$remaining = $eCampaign['limit'] - $eCampaign['alreadyScheduled'];
		
		$label = self::p('to')->label.'  <span class="util-counter" id="campaign-contacts">'.$eCampaign['cContact']->count().'</span>';
		$label .= '<b> / <span id="campaign-limit">'.$remaining.'</span></b>';

		$label .= '<div id="campaign-limit-alert">'.$this->getAlert($eCampaign['scheduledAt'], $remaining, $eCampaign['limit']).'</div>';

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

	public function getAlert(string $date, int $remaining, int $limit): string {
		return \util\FormUi::info(s("Il vous reste {remaining} / {limit} mails à envoyer sur la semaine du {date}.", ['remaining' => $remaining, 'limit' => $limit, 'date' => \util\DateUi::numeric($date, \util\DateUi::DATE)]));
	}

	public function getEmailFields(\util\FormUi $form, Campaign $eCampaign): string {

		$h = '<div id="campaign-write-email">';

			$h .= $form->dynamicGroup($eCampaign, 'subject');

			$content = '<div class="util-block mb-0" style="max-width: '.MailSetting::MAX_WIDTH.'px">'.$form->dynamicField($eCampaign, 'content').'</div>';

			if(
				$eCampaign->exists() === FALSE and
				$eCampaign['cCampaignLast']->notEmpty()
			) {

				$action = '<a data-dropdown="bottom-end" class="dropdown-toggle">'.s("Utiliser titre et contenu d'une ancienne campagne").'</a>';
				$action .= '<div class="dropdown-list bg-secondary">';

					foreach($eCampaign['cCampaignLast'] as $eCampaignLast) {
						$action .= '<a data-ajax="/mail/campaign:getEmailFields?id='.$eCampaignLast['id'].'" data-ajax-method="get" class="dropdown-item" '.attr('data-subject', $eCampaignLast['subject']).' '.attr('data-content', $form->editor('content', $eCampaignLast['content'])).'>'.encode($eCampaignLast['subject']).'<br/><small>'.\util\DateUi::numeric($eCampaignLast['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE).'</small></a>';
					}

				$action .= '</div>';

				$content .= \util\FormUi::getFieldAction($action);

			}


			$h .= $form->group(
				self::p('content')->label.\farm\FarmUi::getEmailInfo($eCampaign['farm']),
				$content
			);

		$h .= '</div>';

		return $h;

	}

	public function get(Campaign $e, \Collection $cEmail): string {

		$status = match($e['status']) {
			Campaign::CONFIRMED => s("Programmée"),
			Campaign::SENT => s("Envoyée"),
		};
		$h = '<h2>'.encode($e['subject']).'</h2>';

		$h .= '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("État de la campagne").'</dt>';
				$h .= '<dd>'.$status.'</dd>';
				$h .= '<dt>'.s("Destinataires").'</dt>';
				$h .= '<dd>'.$e['scheduled'].'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		if($e['status'] === Campaign::SENT) {

			$h .= '<ul class="util-summarize">';
				$h .= '<li>';
					$h .= '<h5><div style="margin-bottom: .25rem">'.self::getIcon(Email::SENT).'</div>'.s("Envoyés").'</h5>';
					$h .= '<div>'.$e['sent'].'</div>';
				$h .= '</li>';
				$h .= '<li>';
					$h .= '<h5><div style="margin-bottom: .25rem">'.self::getIcon(Email::DELIVERED).'</div>'.s("Reçus").'</h5>';
					$h .= '<div>'.$e['delivered'].'</div>';
				$h .= '</li>';
				$h .= '<li>';
					$h .= '<h5><div style="margin-bottom: .25rem">'.self::getIcon(Email::OPENED).'</div>'.s("Ouverts").'</h5>';
					$h .= '<div>'.$e['opened'].'</div>';
				$h .= '</li>';
				$h .= '<li>';
					$h .= '<h5><div style="margin-bottom: .25rem">'.self::getIcon(Email::ERROR_BLOCKED).'</div>'.s("Bloqués").'</h5>';
					$h .= '<div>'.$e['failed'] + $e['spam'].'</div>';
				$h .= '</li>';
			$h .= '</ul>';

		}

		$h .= '<br/>';

		$h .= '<h2>'.s("Destinataires").'</h2>';

		if($e['consent']) {

			$h .= '<h3>'.\Asset::icon('exclamation-triangle').' '.s("Refus de consentement ou envoi d'e-mail désactivé").'</h3>';
			$h .= '<code class="mb-2">';
				$h .= encode(implode(', ', $e['consent']));
			$h .= '</code>';

		}

		if($e['limited']) {

			$h .= '<h3>'.\Asset::icon('exclamation-triangle').' '.s("Limite d'envoi hebdomadaire dépassée").'</h3>';
			$h .= '<code class="mb-2">';
				$h .= encode(implode(', ', $e['limited']));
			$h .= '</code>';

		}

		if($cEmail->notEmpty()) {

			if($cEmail->notEmpty()) {

				$h .= '<h3>'.s("E-mail envoyés").'</h3>';

				$h .= '<table class="tr-even">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("E-mail").'</th>';
							$h .= '<th>'.s("État").'</th>';
							$h .= '<th>'.s("Commentaire").'</th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cEmail as $eEmail) {

							$h .= '<tr>';
								$h .= '<td>'.encode($eEmail['to']).'</td>';
								$h .= '<td>';

									$h .= self::getIcon($eEmail['status']).' ';

									$h .= match($eEmail['status']) {
										Email::WAITING, Email::SENDING => s("En attente"),
										Email::SENT => s("Envoyé"),
										Email::DELIVERED => s("Reçu"),
										Email::OPENED => s("Ouvert"),
										Email::ERROR_PROVIDER, Email::ERROR_SPAM, Email::ERROR_BOUNCE, Email::ERROR_INVALID, Email::ERROR_BLOCKED => '<b>'.s("Bloqué").'</b>'
									};

								$h .= '</td>';
								$h .= '<td>';
									if($eEmail['status'] === Email::OPENED) {
										$h .= s("Ouvert le {date}", ['date' => \util\DateUi::numeric($eEmail['openedAt'], \util\DateUi::DATE)]);
									}
								$h .= '</td>';
							$h .= '</tr>';

						}

					$h .= '</tbody>';
				$h .= '</table>';

			}

		} else {

			if($e['status'] === Campaign::SENT) {
				$h .= '<div class="util-warning">'.s("Les informations détaillées sur les destinataires ne sont conservées que 12 mois.").'</div>';
			}

			$h .= '<code>';
				$h .= encode(implode(', ', $e['to']));
			$h .= '</code>';

		}

		$h .= '<br/>';

		$h .= '<h2>'.s("Contenu").'</h2>';

		$h .= '<div class="util-block" style="max-width: '.MailSetting::MAX_WIDTH.'px">'.new \editor\ReadorFormatterUi()->getFromXml($e['content'], ['isEmail' => TRUE]).'</div>';

		return $h;

	}

	public static function getIcon(string $status): string {

		return match($status) {
			Email::SENT => \Asset::icon('arrow-up-circle'),
			Email::DELIVERED => \Asset::icon('arrow-down-circle'),
			Email::OPENED => \Asset::icon('envelope-open'),
			Email::ERROR_BLOCKED, Email::ERROR_BOUNCE, Email::ERROR_SPAM, Email::ERROR_INVALID, Email::ERROR_PROVIDER => \Asset::icon('exclamation-circle'),
			default => ''
		};

	}

	public function getList(\farm\Farm $eFarm, \Collection $cCampaign, int $nCampaign, int $page, array $scheduledByWeek) {

		$h = '';

		if($cCampaign->empty()) {

			$h .= '<div class="util-empty">'.s("Vous n'avez pas encore créé de campagne d'envoi d'e-mails.").'</div>';
			return $h;

		}

		$h .= $this->getLimits($eFarm, $scheduledByWeek);

		$h .= '<div class="stick-md util-overflow-md">';

			$h .= '<table class="campaign-item-table tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th class="td-min-content" rowspan="2">'.s("Date d'envoi").'</th>';
						$h .= '<th rowspan="2">'.s("Titre").'</th>';
						$h .= '<th colspan="5" class="text-center">'.s("Statistiques *").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-center highlight-stick-right"><div class="font-xl" style="margin-bottom: .25rem">'.self::getIcon(Email::SENT).'</div>'.s("Envoyés").'</th>';
						$h .= '<th class="text-center highlight-stick-both"><div class="font-xl" style="margin-bottom: .25rem">'.self::getIcon(Email::DELIVERED).'</div>'.s("Reçus").'</th>';
						$h .= '<th class="text-center highlight-stick-both"><div class="font-xl" style="margin-bottom: .25rem">'.self::getIcon(Email::OPENED).'</div>'.s("Lus").'</th>';
						$h .= '<th class="text-center highlight-stick-left"><div class="font-xl" style="margin-bottom: .25rem">'.self::getIcon(Email::ERROR_BLOCKED).'</div>'.s("Bloqués").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCampaign as $eCampaign) {

					$h .= '<tr>';

						$h .= '<td class="td-min-content">';
							$h .= '<a href="/mail/campaign:get?id='.$eCampaign['id'].'" class="btn btn-outline-primary">'.\util\DateUi::numeric($eCampaign['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE).'</a>';
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

							if($eCampaign['limited']) {
								$h .= '<div class="util-annotation color-danger">'.\Asset::icon('exclamation-triangle').' '.p("Limite hebdomadaire dépassée pour {value} contact", "Limite hebdomadaire dépassée pour {value} contacts", count($eCampaign['limited'])).'</div>';
							}

						$h .= '</td>';

						$scheduled = '<span class="font-xl">'.$eCampaign['scheduled'].'</span>';

						switch($eCampaign['status']) {

							case Campaign::CONFIRMED :
								$h .= '<td class="campaign-item-stat highlight-stick-right">';
									$h .= $scheduled;
								$h .= '</td>';
								$h .= '<td colspan="3" class="text-center highlight-stick-left color-warning">';
									$h .= \Asset::icon('alarm').' '.s("Envoi programmé le {date}", ['date' => \util\DateUi::numeric($eCampaign['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
								$h .= '</td>';
								break;

							case Campaign::SENT :
								$h .= '<td class="campaign-item-stat highlight-stick-right">';
									$h .= $scheduled;
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-both">';
									if($eCampaign['scheduled'] > 0) {
										$h .= '<span>'.$eCampaign['delivered'].'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($eCampaign['delivered'] / $eCampaign['scheduled'] * 100)).'</div>';
									}
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-both">';
									if($eCampaign['delivered'] > 0) {
										$h .= '<span>'.$eCampaign['opened'].'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($eCampaign['opened'] / $eCampaign['delivered'] * 100)).'</div>';
									}
								$h .= '</td>';

								$h .= '<td class="campaign-item-stat highlight-stick-left">';
									if($eCampaign['scheduled'] > 0) {
										$blocked = $eCampaign['failed'] + $eCampaign['spam'];
										$h .= '<span '.($blocked > 0 ? 'class="color-danger"' : '').'>'.$blocked.'</span>';
										$h .= '<div class="campaign-item-stat-percent">'.s("{value} %", round($blocked / $eCampaign['scheduled'] * 100)).'</div>';
									}
								$h .= '</td>';
								break;

						}

						$h .= '<td class="td-min-content">';
							$h .= $this->getMenu($eCampaign, 'btn-outline-secondary');
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

	public function getLimits(\farm\Farm $eFarm, array $scheduledByWeek): string {

		$h = '<div class="util-block">';
			$h .= '<h4>'.s("Les campagnes sont soumises aux limites hebdomadaires suivantes :").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("{value} envois d'e-mails", $eFarm->getCampaignLimit()).'</li>';
				$h .= '<li>'.p("{value} envois d'e-mail par contact", "{value} envois d'e-mails par contact", $eFarm->getContactLimit()).'</li>';
			$h .= '</ul>';
			if($eFarm->isMembership() === FALSE) {
				$h .= '<p class="color-secondary">'.s("L'envoi d'e-mails est limité en nombre pour réduire les risques de spam et parce que l'envoi des e-mails est une source de coût pour l'association {siteName}. Il est possible d'envoyer plus d'e-mails <link><u>en adhérant à l'association</u></link>.", ['link' => '<a href="'.\association\AssociationUi::url($eFarm).'">']).'</p>';
			}
		$h .= '</div>';

		if(array_sum($scheduledByWeek) > 0) {

			$h .= '<ul class="util-summarize">';

				foreach($scheduledByWeek as $week => $scheduled) {

					$h .= '<li>';
						$h .= '<h5>';
							$h .= match($week) {
								0 => s("Cette semaine"),
								1 => s("Semaine prochaine")
							};
						$h .= '</h5>';
						$h .= '<div>'.$scheduled.' <small>'.self::getIcon(Email::SENT).' / '.$eFarm->getCampaignLimit().'</small></div>';
					$h .= '</li>';

				}

			$h .= '</ul>';

		}

		return $h;

	}

	public function getMenu(Campaign $eCampaign, string $btn): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';

			$h .= '<div class="dropdown-title">'.s("Campagne du {date}", ['date' => \util\DateUi::textual($eCampaign['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'</div>';

			if($eCampaign->acceptUpdate()) {
				$h .= '<a href="/mail/campaign:update?id='.$eCampaign['id'].'" class="dropdown-item">'.s("Modifier la campagne").'</a> ';
				$h .= '<a data-ajax="/mail/campaign:doTest" post-id="'.$eCampaign['id'].'" data-confirm="'.s("L'e-mail de cette campagne sera envoyé pour vos tests à l'adresse e-mail de la ferme {email}. Continuer ?", ['email' => $eCampaign['farm']['legalEmail']]).'" class="dropdown-item">'.s("Envoyer un e-mail de test").'</a> ';
			}

			$link = '/mail/campaign:create?farm='.$eCampaign['farm']['id'].'&copy='.$eCampaign['id'].'&source='.$eCampaign['source'];
			$link .= match($eCampaign['source']) {

				Campaign::SHOP => '&sourceShop='.$eCampaign['sourceShop']['id'],
				Campaign::GROUP => '&sourceGroup='.$eCampaign['sourceGroup']['id'],
				Campaign::PERIOD => '&sourcePeriod='.$eCampaign['sourcePeriod'],
				default => ''

			};

			$h .= '<a href="'.$link.'" class="dropdown-item">'.s("Nouvelle campagne à partir de celle-ci").'</a>';

			if($eCampaign->acceptDelete()) {
				$h .= '<div class="dropdown-divider"></div>';
				$h .= '<a data-ajax="/mail/campaign:doDelete" post-id="'.$eCampaign['id'].'" data-confirm="'.s("Vous allez supprimer une campagne et les e-mails ne seront pas envoyés. Continuer ?").'" class="dropdown-item">'.s("Supprimer la campagne").'</a>';
			}

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
				$d->attributes = function(\util\FormUi $form, Campaign $e) {
					return [
						'oninput' => 'Campaign.changeScheduledAt('.$e['farm']['id'].', '.($e->exists() ? $e['id'] : 'null').', this.value)'
					];
				};
				break;

			case 'subject' :
				$d->attributes = ['data-limit' => Campaign::model()->getPropertyRange('subject')[1]];
				break;

			case 'content' :
				$d->before = fn(\util\FormUi $form, Campaign $e) => DesignUi::getBanner($e['farm']);
				$d->after = fn(\util\FormUi $form, Campaign $e) => '<br/>'.DesignUi::getFooter($e['farm']).self::unsubscribe($e['farm']);
				$d->placeholder = s("Tapez votre texte ici...");
				$d->options = [
					'acceptFigure' => TRUE,
					'figureOnlyImage' => TRUE
				];
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
