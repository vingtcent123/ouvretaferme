<?php
namespace website;

class ManageUi {

	public function __construct() {

		\Asset::css('website', 'manage.css');
		\Asset::js('website', 'manage.js');

	}

	public function create(\farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$eWebsite = new Website([
			'farm' => $eFarm,
			'name' => $eFarm['name']
		]);

		$h = '<h2>'.s("Créer le site internet de la ferme").'</h2>';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("{siteName} vous permet de créer très facilement un site internet pour votre ferme avec quelques fonctionnalité de base :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Publication des actualités de votre ferme").'</li>';
				$h .= '<li>'.s("Possibilité de créer un nombre illimité de pages").'</li>';
				$h .= '<li>'.s("Compatibilité avec les ordinateurs et les mobiles").'</li>';
				$h .= '<li>'.s("Ajout d'un module de vente en ligne").'</li>';
			$h .= '</ul>';
			$h .= '<p>'.s("Ce module de création de site internet est fait pour vous si vous désirez un site simple et facile à mettre à jour. Il n'est pas fait pour vous si vous avez des besoins spécifiques qui vont au delà des fonctionnalités de base. Dans ce cas, nous vous invitons à vous tourner vers d'autres solutions techniques.").'</p>';
		$h .= '</div>';

		$h .= $form->openAjax('/website/manage:doCreate');

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($eWebsite, ['internalDomain*', 'domain', 'name*', 'description']);

			$h .= $form->group(
				content: $form->submit(s("Démarrer le site internet"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Website $eWebsite): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/website/manage:doUpdate');

			$h .= $form->hidden('id', $eWebsite['id']);

			$h .= $form->dynamicGroups($eWebsite, ['internalDomain', 'domain', 'name', 'description']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Paramétrer le site internet"),
			body: $h,
			close: 'reload'
		);

	}

	public function display(Website $eWebsite): string {

		\Asset::css('production', 'crop.css');

		$h = '<div class="util-action">';

			$h .= '<h1>'.s("Le site internet").'</h1>';

			$h .= '<div>';
				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					$h .= '<div class="dropdown-title">'.s("Site internet").'</div>';
					$h .= '<a href="/website/manage:update?id='.$eWebsite['id'].'" class="dropdown-item">'.s("Paramétrer le site").'</a>';
					$h .= match($eWebsite['status']) {
						Website::INACTIVE => '<a data-ajax="/website/manage:doUpdateStatus" post-id="'.$eWebsite['id'].'" post-status="'.Website::ACTIVE.'" class="dropdown-item" data-confirm="'.s("Le site sera rendu accessible au public, voulez-vous continuer ?").'">'.s("Mettre en ligne le site").'</a>',
						Website::ACTIVE => '<a data-ajax="/website/manage:doUpdateStatus" post-id="'.$eWebsite['id'].'" post-status="'.Website::INACTIVE.'" class="dropdown-item" data-confirm="'.s("Le site deviendra inaccessible au public, voulez-vous continuer ?").'">'.s("Mettre hors ligne le site").'</a>'
					};
					$h .= '<div class="dropdown-divider"></div>';
					$h .= '<a data-ajax="/website/manage:doDelete" post-id="'.$eWebsite['id'].'" class="dropdown-item" data-confirm="'.s("Le site ainsi que toutes les données seront supprimées, êtes-vous sûr de vouloir continuer ?").'">'.s("Supprimer définitivement le site").'</a>';
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-action-subtitle">';
			$h .= '<a href="'.WebsiteUi::url($eWebsite).'">'.WebsiteUi::url($eWebsite, showProtocol: FALSE).'</a>';
		$h .= '</div>';

		if($eWebsite['domain'] !== NULL and in_array($eWebsite['domainStatus'], [Website::CONFIGURED_UNSECURED, Website::FAILURE_UNSECURED])) {
			$h .= '<p class="util-warning">';
				$h .= s("Pour démarrer l'activation de votre nom de domaine, configurez les DNS de votre nom de domaine vers l'adresse IP suivante : <b>{value}</b>. Une fois les DNS modifiés et propagés, la configuration de votre nom de domaine se terminera automatiquement. Cette étape peut prendre plusieurs heures.", \Setting::get('website\dnsIP'));
			$h .= '</p>';
		}

		$h .= '<div class="util-block" style="margin-bottom: 2rem">';
			$h .= '<div class="website-info">';
				$h .= '<dl class="util-presentation util-presentation-2">';
					$h .= '<dt>'.s("Statut").'</dt>';
					$h .= '<dd>';
						$h .= match($eWebsite['status']) {
							Website::ACTIVE => '<span class="color-success">'.\Asset::icon('check-lg').' '.s("Site en ligne").'</span>',
							Website::INACTIVE => '<span class="color-danger">'.\Asset::icon('pause-fill').' '.s("Site hors ligne").'</span>'
						};
					$h .= '</dd>';
					$h .= '<dt>'.s("Nom de domaine").'</dt>';
					$h .= '<dd class="website-info-domain">';
						if($eWebsite['domain'] === NULL) {
							$h .= '-';
						} else {
							$h .= encode($eWebsite['domain']).' ';
							$h .= match($eWebsite['domainStatus']) {
								Website::FAILURE_UNSECURED => \Asset::icon('chevron-right').' <b class="color-danger">'.s("Non actif - Impossible d'atteindre le nom de domaine").'</b>',
								Website::FAILURE_SECURED => \Asset::icon('chevron-right').' <b class="color-danger">'.s("Non actif - Impossible de sécuriser le nom de domaine").'</b>',
								Website::FAILURE_CERTIFICATE_CREATED => \Asset::icon('chevron-right').' <b class="color-danger">'.s("Non actif - Impossible de créer le certificat de sécurité").'</b>',
								Website::PENDING => \Asset::icon('chevron-right').' <b>'.s("Non actif - En cours de configuration").'</b>',
								Website::CONFIGURED_UNSECURED => \Asset::icon('chevron-right').' <b>'.s("Non actif - Vérification du domaine").'</b>',
								Website::CONFIGURED_SECURED => \Asset::icon('chevron-right').' <b>'.s("Non actif - Vérification du domaine sécurisé").'</b>',
								Website::CERTIFICATE_CREATED => \Asset::icon('chevron-right').' <b>'.s("Non actif - Sécurisation du domaine en cours").'</b>',
								Website::PINGED_UNSECURED => \Asset::icon('chevron-right').' <b>'.s("Non actif - Domaine vérifié et en attente de sécurisation").'</b>',
								Website::PINGED_SECURED => '',
							};
						}
					$h .= '</dd>';
					$h .= '<dt>'.s("Nom du site").'</dt>';
					$h .= '<dd>';
						$h .= encode($eWebsite['name'] ?? '');
					$h .= '</dd>';
					$h .= '<dt>'.s("Icône").'</dt>';
					$h .= '<dd>';
						$h .= '<div>';
							$h .= (new \media\WebsiteFaviconUi())->getCamera($eWebsite, size: '2rem');
						$h .= '</div>';
					$h .= '</dd>';
					$h .= '<dt>'.s("Description").'</dt>';
					$h .= '<dd>';
						if($eWebsite['description']) {
							$h .= '<span class="website-info-description">'.encode($eWebsite['description']).'</span>';
						}
					$h .= '</dd>';
					$h .= '<dt>'.s("Logo").'</dt>';
					$h .= '<dd>';
						$h .= (new \media\WebsiteLogoUi())->getCamera($eWebsite, size: '6rem');
					$h .= '</dd>';
				$h .= '</dl>';
				$h .= '<div class="website-info-link">';
					$h .= '<a href="'.WebsiteUi::url($eWebsite).'" class="btn btn-outline-primary" target="_blank">'.s("Visiter le site").'</a>';
				$h .= '</div>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function configure(Website $eWebsite, \Collection $cWebpage, \Collection $cMenu, \Collection $cNews): string {

		$h = '<div class="tabs-h" id="website-configure" onrender="'.encode('Lime.Tab.restore(this, "pages", '.(get_exists('tab') ? '"'.GET('tab', ['pages', 'news'], 'pages').'"' : 'null').')').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="pages" onclick="Lime.Tab.select(this)">'.s("Pages").'</a>';
				$h .= '<a class="tab-item" data-tab="menu" onclick="Lime.Tab.select(this)">'.s("Menu").'</a>';
				$h .= '<a class="tab-item" data-tab="news" onclick="Lime.Tab.select(this)">'.s("Actualités").'</a>';
				$h .= '<a class="tab-item" data-tab="customize" onclick="Lime.Tab.select(this)">'.s("Personnalisation").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="pages">';
				$h .= $this->getPages($eWebsite, $cWebpage);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="menu">';
				$h .= $this->getMenu($eWebsite, $cWebpage, $cMenu);
			$h .= '</div>';

		$h .= '<div class="tab-panel" data-tab="news">';
		$h .= $this->getNews($eWebsite, $cNews);
		$h .= '</div>';

		$h .= '<div class="tab-panel" data-tab="customize">';
		$h .= $this->updateCustomization($eWebsite);
		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPages(Website $eWebsite, \Collection $cWebpage): string {

		if($cWebpage->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune page à afficher.").'</div>';
		}

		$h = '<div class="text-end" style="margin-bottom: 1rem;">';
			$h .= '<a href="/website/webpage:create?website='.$eWebsite['id'].'" class="btn btn-secondary">'.\Asset::icon('plus-circle').' '.s("Nouvelle page").'</a>';
		$h.= '</div>';

		$h .= '<div class="website-page-wrapper stick-xs">';

		$h .= '<table class="tr-even website-page-table">';

			$h .= '<tr>';
				$h .= '<th class="website-page-url">'.s("Adresse").'</th>';
				$h .= '<th>'.s("Titre").'</th>';
				$h .= '<th class="website-page-content">'.s("Contenu").'</th>';
				$h .= '<th>'.s("Statut").'</th>';
				$h .= '<th></th>';
			$h .= '</tr>';

			foreach($cWebpage as $eWebpage) {

				$h .= '<tr>';

					$h .= '<td class="website-page-url">';
						$h .= '/'.$eWebpage['url'];
					$h .= '</td>';

					$h .= '<td>';
						$h .= '<a href="'.WebsiteUi::url($eWebsite, '/'.$eWebpage['url']).'" class="btn btn-outline-primary">'.encode($eWebpage['title']).'</a> ';
					$h .= '</td>';

					$h .= '<td class="website-page-content">';
						if($eWebpage['content'] === NULL) {
							$h .= '<a href="/website/webpage:updateContent?id='.$eWebpage['id'].'" class="btn btn-primary">'.\Asset::icon('pencil-fill').' '.s("Rédiger").'</a>';
						} else {
							$h .= '<a href="/website/webpage:updateContent?id='.$eWebpage['id'].'" class="btn btn-primary">'.\Asset::icon('pencil-fill').'&nbsp;&nbsp;<small>'.s("{value} Ko", round(strlen($eWebpage['content']) / 1024, 2)).'</small></a>';
						}
					$h .= '</td>';

					$h .= '<td>';
						$h .= match($eWebpage['status']) {
							Webpage::ACTIVE => '<span class="color-success">'.\Asset::icon('check-lg').' '.s("En ligne").'</span>',
							Webpage::INACTIVE => '<span class="color-muted">'.\Asset::icon('pause-fill').' '.s("Hors-ligne").'</span>',
						};
					$h .= '</td>';

					$h .= '<td class="text-end">';
						$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.s("Page").'</div>';
							$h .= '<a href="/website/webpage:update?id='.$eWebpage['id'].'" class="dropdown-item">'.s("Paramétrer la page").'</a>';
							$h .= '<a href="/website/webpage:updateContent?id='.$eWebpage['id'].'" class="dropdown-item">'.s("Rédiger le contenu").'</a>';
							if($eWebpage['template']['fqn'] !== 'homepage') {
								$h .= match($eWebpage['status']) {
									Webpage::INACTIVE => '<a data-ajax="/website/webpage:doUpdateStatus" post-id="'.$eWebpage['id'].'" post-status="'.Webpage::ACTIVE.'" class="dropdown-item">'.s("Mettre en ligne la page").'</a>',
									Webpage::ACTIVE => '<a data-ajax="/website/webpage:doUpdateStatus" post-id="'.$eWebpage['id'].'" post-status="'.Webpage::INACTIVE.'" class="dropdown-item" data-confirm="'.s("La page deviendra inaccessible au public, voulez-vous continuer ?").'">'.s("Mettre hors ligne la page").'</a>'
								};
							}
							if($eWebpage['template']['fqn'] === 'open') {
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="/website/webpage:doDelete" post-id="'.$eWebpage['id'].'" class="dropdown-item" data-confirm="'.s("La page ainsi que toutes les données qui s'y trouvent seront supprimées, êtes-vous sûr de vouloir continuer ?").'">'.s("Supprimer définitivement la page").'</a>';
							}
						$h .= '</div>';
					$h .= '</td>';

				$h .= '</tr>';

			}

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getMenu(Website $eWebsite, \Collection $cWebpage, \Collection $cMenu): string {

		if($cWebpage->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune page à afficher.").'</div>';
		}

		$h = '<div class="text-end" style="margin-bottom: 1rem;">';
			$h .= \Asset::icon('plus-circle').' '.s("Ajouter au menu").' ';
			$h .= '<a href="/website/menu:create?website='.$eWebsite['id'].'&for=webpage" class="btn btn-secondary">'.s("une page").'</a>';
			$h .= ' '.s("ou").' ';
			$h .= '<a href="/website/menu:create?website='.$eWebsite['id'].'&for=url" class="btn btn-secondary">'.s("un lien externe").'</a>';
		$h.= '</div>';

		$h .= '<div class="website-menu-wrapper stick-xs">';

		$h .= '<table class="tr-even website-menu-table">';

			$h .= '<tr>';
				$h .= '<th>'.s("Texte").'</th>';
				$h .= '<th>'.s("Destination").'</th>';
				$h .= '<th class="website-menu-status">'.s("Statut de la page").'</th>';
				$h .= '<th></th>';
			$h .= '</tr>';

			$positions = $cMenu->getIds();
			$position = 0;

			foreach($cMenu as $eMenu) {

				$h .= '<tr>';

					$h .= '<td>';
						$h .= $eMenu->quick('label', encode($eMenu['label']));
					$h .= '</td>';

					$h .= '<td>';
						if($eMenu['webpage']->notEmpty()) {
							$eWebpage = $cWebpage[$eMenu['webpage']['id']];
							$h .= '/'.$eWebpage['url'];
						} else {
							$eWebpage = $eMenu['webpage'];
							$h .= '<a href="'.encode($eMenu['url']).'" target="_blank">'.encode($eMenu['url']).'</a>';
						}
					$h .= '</td>';

					$h .= '<td class="website-menu-status">';
						if($eWebpage->notEmpty()) {
							$h .= match($eWebpage['status']) {
								Webpage::ACTIVE => '<span class="color-success">'.\Asset::icon('check-lg').' '.s("En ligne").'</span>',
								Webpage::INACTIVE => '<span class="color-muted">'.\Asset::icon('pause-fill').' '.s("Hors-ligne").'</span>',
							};
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="text-end">';
						if($position > 0) {
							$newPositions = array_merge(
								array_slice($positions, 0, $position - 1),
								[$positions[$position]],
								[$positions[$position - 1]],
								array_slice($positions, $position + 1)
							);
							$h .= '<a data-ajax="/website/menu:doUpdatePositions" post-id='.$eWebsite['id'].'" post-positions="'.implode(',', $newPositions).'" class="btn btn-outline-secondary">'.\Asset::icon('arrow-up').'</a> ';
						} else {
							$h .= '<a class="btn disabled">'.\Asset::icon('arrow-up').'</a> ';
						}
						if($position === $cMenu->count() - 1) {
							$h .= '<a class="btn disabled">'.\Asset::icon('arrow-down').'</a> ';
						} else {
							$newPositions = array_merge(
								array_slice($positions, 0, $position),
								[$positions[$position + 1]],
								[$positions[$position]],
								array_slice($positions, $position + 2)
							);
							$h .= '<a data-ajax="/website/menu:doUpdatePositions" post-id='.$eWebsite['id'].'" post-positions="'.implode(',', $newPositions).'" class="btn btn-outline-secondary">'.\Asset::icon('arrow-down').'</a> ';
						}
						$h .= '<a data-ajax="/website/menu:doDelete" post-id="'.$eMenu['id'].'" class="btn btn-outline-secondary" title="'.s("Retirer cette page du menu").'">'.\Asset::icon('trash').'</a>';
					$h .= '</td>';

				$h .= '</tr>';

				$position++;

			}

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getNews(Website $eWebsite, \Collection $cNews): string {

		$h = '<div class="text-end" style="margin-bottom: 1rem;">';
			$h .= '<a href="/website/news:create?website='.$eWebsite['id'].'" class="btn btn-secondary">'.\Asset::icon('plus-circle').' '.s("Nouvelle actualité").'</a>';
		$h.= '</div>';

		if($cNews->empty()) {
			$h .= '<div class="util-info">'.s("Il n'y a aucune actualité à afficher pour le moment.").'</div>';
		} else {

			$h .= '<div class="website-news-wrapper stick-xs">';

			$h .= '<table class="tr-even website-news-table">';

				$h .= '<tr>';
					$h .= '<th>'.s("Titre").'</th>';
					$h .= '<th>'.s("Date de publication").'</th>';
					$h .= '<th>'.s("Statut").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';

				foreach($cNews as $eNews) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= $eNews->quick('title', encode($eNews['title']));
						$h .= '</td>';

						$h .= '<td '.($eNews['isPublished'] ? '' : 'title="'.s("Pas encore publié").'"').'>';
							$h .= $eNews->quick('publishedAt', \util\DateUi::numeric($eNews['publishedAt'], \util\DateUi::DATE_HOUR_MINUTE));
							if($eNews['isPublished'] === FALSE) {
								$h .= ' '.\Asset::icon('watch');
							}
						$h .= '</td>';

						$h .= '<td>';
							$h .= match($eNews['status']) {
								News::READY => '<span class="color-success">'.\Asset::icon('check-lg').' '.s("Prête").'</span>',
								News::DRAFT => '<span class="color-muted">'.\Asset::icon('pencil-fill').' '.s("Brouillon").'</span>',
							};
						$h .= '</td>';

						$h .= '<td class="text-end">';
							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list bg-primary">';
								$h .= '<div class="dropdown-title">'.s("Actualité").'</div>';
								$h .= '<a href="/website/news:update?id='.$eNews['id'].'" class="dropdown-item">'.s("Modifier").'</a>';
								$h .= match($eNews['status']) {
									News::DRAFT => '<a data-ajax="/website/news:doUpdateStatus" post-id="'.$eNews['id'].'" post-status="'.News::READY.'" class="dropdown-item">'.s("Passer de brouillon à prête").'</a>',
									News::READY => '<a data-ajax="/website/news:doUpdateStatus" post-id="'.$eNews['id'].'" post-status="'.News::DRAFT.'" class="dropdown-item" data-confirm="'.s("L'actualité deviendra inaccessible au public, voulez-vous continuer ?").'">'.s("Repasser en brouillon").'</a>'
								};
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="/website/news:doDelete" post-id="'.$eNews['id'].'" class="dropdown-item" data-confirm="'.s("L'actualité sera supprimée, êtes-vous sûr de vouloir continuer ?").'">'.s("Supprimer définitivement").'</a>';
							$h .= '</div>';
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</table>';

			$h .= '</div>';

		}

		return $h;

	}


	public function updateCustomization(Website $eWebsite): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/website/manage:doCustomize', ['id' => 'website-customize']);

		$h .= $form->hidden('id', $eWebsite['id']);

		$h .= $form->dynamicGroups($eWebsite, ['customDesign', 'customColor', 'customFont', 'customTitleFont']);

		$h .= $form->group(
				content: $form->submit(s("Enregistrer les modifications"))
		);

		$h .= $form->close();

		$h .= '<h3 class="website-preview-title">'.s("Prévisualisation de votre site internet").'</h3>';
		$h .= '<iframe id="website-preview" src="'.WebsiteUi::url($eWebsite).'?"></iframe>';

		return $h;

	}

}
?>
