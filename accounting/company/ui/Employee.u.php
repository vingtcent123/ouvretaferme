<?php
namespace company;

class EmployeeUi {

  public function __construct() {
    \Asset::css('company', 'company.css');
  }

  public static function urlManage(Company $eCompany): string {
    return CompanyUi::url($eCompany).'/employee:manage';
  }

  public function getMyCompanies(\Collection $cCompany): string {

    $h = '<div class="employee-companies">';

    foreach($cCompany as $eCompany) {
      $h .= (new CompanyUi())->getPanel($eCompany);
    }

    $h .= '</div>';

    return $h;

  }

  public function getNoCompany(): string {

    $h = '<h2>'.s("Ma ferme").'</h2>';
    $h .= '<div class="util-block-help">';
      $h .= '<h4>'.s("Bienvenue sur {siteName} !").'</h4>';
      $h .= '<p>'.s("Vous dirigez une exploitation et vous venez de vous inscrire sur {siteName}. Pour commencer à utiliser tous les services à votre disposition sur la plateforme, configurez maintenant votre exploitation en renseignant quelques informations de base !").'</p>';
    $h .= '</div>';
    $h .= '<div class="util-buttons">';
      $h .= '<a href="/company/public:create" class="bg-secondary util-button">';
        $h .= '<div>';
          $h .= '<h4>'.s("Démarrer la création de ma ferme").'</h4>';
        $h .= '</div>';
        $h .= \Asset::icon('house-door-fill');
      $h .= '</a>';
    $h .= '</div>';

    return $h;

  }

  public function createUser(\company\Company $eCompany): \Panel {

    $form = new \util\FormUi();

    $h = $form->openAjax('/company/employee:doCreateUser');

    $h .= $form->asteriskInfo();

    $h .= $form->hidden('company', $eCompany['id']);

    $eUser = new \user\User();

    $h .= $form->dynamicGroups($eUser, ['firstName', 'lastName*'], [
      'firstName' => function($d) use ($form) {
        $d->after =  \util\FormUi::info(s("Facultatif"));
      }
    ]);

    $h .= $form->group(
      content: $form->submit(s("Créer l'utilisateur"))
    );

    $h .= $form->close();

    return new \Panel(
			id: 'company-create-ghost',
      title: s("Créer un utilisateur fantôme pour la ferme"),
      body: $h,
      close: 'reload'
    );

  }

  public function updateUser(\company\Company $eCompany, \user\User $eUser): \Panel {

    $form = new \util\FormUi();

    $h = $form->openAjax('/company/employee:doUpdateUser');

    $h .= $form->hidden('company', $eCompany['id']);
    $h .= $form->hidden('user', $eUser['id']);

    $h .= $form->dynamicGroups($eUser, ['firstName', 'lastName'], [
      'firstName' => function($d) use ($form) {
        $d->after =  \util\FormUi::info(s("Facultatif"));
      }
    ]);

    $h .= $form->group(
      content: $form->submit(s("Modifier l'utilisateur"))
    );

    $h .= $form->close();

    return new \Panel(
			id: 'company-employee-update',
      title: s("Modifier une personne de l'équipe"),
      body: $h,
      close: 'reload'
    );

  }

  public function create(Employee $eEmployee, Employee $eEmployeeLink): \Panel {

    return new \Panel(
			id: 'company-employee-invite',
      title: s("Inviter une personne dans l'équipe"),
      body: $this->createForm($eEmployee, $eEmployeeLink, 'panel'),
      close: 'reload'
    );

  }

  public function getManageTitle(Company $eCompany): string {

    $h = '<div class="util-action">';

    $h .= '<h1>';
      $h .= '<a href="'.\company\CompanyUi::urlSettings($eCompany).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
      $h .= s("L'équipe");
    $h .= '</h1>';

    $h .= '<div>';
      $h .= '<a href="'.CompanyUi::url($eCompany).'/employee:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Inviter une personne dans l'équipe").'</a>';
    $h .= '</div>';

    $h .= '</div>';

    return $h;

  }

  public function getManage(Company $eCompany, \Collection $cEmployee, \Collection $cInvite): string {

    $h = '';

    if($cEmployee->notEmpty()) {


      $h .= '<div class="util-buttons">';

        foreach($cEmployee as $eEmployee) {

          $properties = [];

          $h .= '<a href="'.CompanyUi::url($eCompany).'/employee:show?id='.$eEmployee['id'].'" class="util-button bg-secondary">';
            $h .= '<div>';
              $h .= '<h4>';
                $h .= \user\UserUi::name($eEmployee['user']);
              $h .= '</h4>';
              $h .= '<div class="util-button-text">';
                $h .= implode(' | ', $properties);
              $h .= '</div>';
            $h .= '</div>';
            $h .= \user\UserUi::getVignette($eEmployee['user'], '4rem');
          $h .= '</a>';

        }

      $h .= '</div>';

      if($cInvite->notEmpty()) {

        $h .= '<h3>'.s("Les invitations en cours").'</h3>';

        $h .= '<div class="util-buttons">';

          foreach($cInvite as $eInvite) {

            $h .= '<div class="util-button bg-primary">';
              $h .= '<div>';
                $h .= '<div>';
                  if($eInvite->empty()) {
                    $h .= \Asset::icon('exclamation-triangle-fill').' '.s("Invitation expirée");
                  } else if($eInvite->isValid() === FALSE) {
                    $h .= s("Invitation expirée pour {value}", '<b>'.encode($eInvite['email']).'</b>');
                  } else {
                    $h .= s("Invitation envoyée à {value}", '<b>'.encode($eInvite['email']).'</b>');
                  }
                $h .= '</div>';
                $h .= '<div class="mt-1">';
                  $h .= '<a data-ajax="/company/invite:doDelete" post-company="'.$eCompany['id'].'" post-id="'.$eInvite['id'].'" class="btn btn-transparent">';
                    $h .= s("Supprimer");
                  $h .= '</a> ';
                  $h .= '<a data-ajax="/company/invite:doExtend" post-company="'.$eCompany['id'].'" post-id="'.$eInvite['id'].'" data-confirm="'.s("Voulez-vous vraiment renvoyer un mail d'invitation à cette personne ?").'" class="btn btn-transparent">';
                    $h .= s("Renvoyer l'invitation");
                  $h .= '</a>';
                $h .= '</div>';
              $h .= '</div>';
            $h .= '</div>';

          }

        $h .= '</div>';

      }

    }


    return $h;

  }

  public function getUserTitle(\company\Employee $eEmployee): string {

    $h = '<h1>';
      $h .= '<a href="'.EmployeeUi::urlManage($eEmployee['company']).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
      $h .= \user\UserUi::getVignette($eEmployee['user'], '3rem').' '.\user\UserUi::name($eEmployee['user']);
    $h .= '</h1>';

    return $h;

  }

  public function getUser(\company\Employee $eEmployee): string {

    $h = '<dl class="util-presentation util-presentation-1">';

      $h .= '<dt>'.s("Adresse e-mail").'</dt>';
      $h .= '<dd>'.($eEmployee['user']['visibility'] === \user\User::PUBLIC ? encode($eEmployee['user']['email']) : '<i>'.s("Utilisateur fantôme").'</i>').'</dd>';

		  $h .= '<dt>'.s("Rôle").'</dt>';
		  $h .= '<dd>';
				if($eEmployee['role'] === NULL) {
					$h .= '<em>'.s("Aucun").'</em>';
				} else {
					$h .= self::p('role')->values[$eEmployee['role']];
				}
			$h .= '</dd>';

    $h .= '</dl>';

    $h .= '<br/>';

    if($eEmployee['status'] === Employee::OUT) {

      $h .= '<div class="util-info">'.s("Cet utilisateur a été sorti de l'équipe de la ferme et ne peut donc plus accéder ni être affecté aux interventions sur la ferme.").'</div>';

    } else {

      $h .= '<div>';

      $h .= '<a href="'.CompanyUi::url($eEmployee['company']).'/employee:update?id='.$eEmployee['id'].'" class="btn btn-primary">';
        $h .= s("Configurer");
      $h .= '</a> ';

      $h .= '<a data-ajax="'.CompanyUi::url($eEmployee['company']).'/employee:doDelete" post-id="'.$eEmployee['id'].'" class="btn btn-primary" data-confirm="'.s("Souhaitez-vous réellement retirer cet utilisateur de la ferme ?").'">';
        $h .= s("Sortir de l'équipe");
      $h .= '</a>';

      $h .= '</div>';

      $h .= '<br/>';

    }

    return $h;

  }

  protected function createForm(Employee $eEmployee, Employee $eEmployeeLink, string $origin): string {

    $form = new \util\FormUi();

    $h = $form->openAjax('/company/employee:doCreate', ['data-ajax-origin' => $origin]);

    $h .= $form->asteriskInfo();

    if($eEmployeeLink->notEmpty()) {
      $h .= $form->hidden('id', $eEmployeeLink['id']);
    }

    $h .= $form->hidden('company', $eEmployee['company']['id']);

    $description = '<div class="util-block-help">';
      $description .= '<p>'.s("En invitant une personne à rejoindre l'équipe de votre ferme, vous lui permettrez d'accéder à un grand nombre de données sur votre entreprise. Choisissez le rôle que vous donnez avec soin :").'</p>';
	    $description .= $this->getRoles();
      $description .= '<p>'.s("Pour inviter quelqu'un, saisissez son adresse e-mail. Un e-mail avec les instructions à suivre lui sera envoyé. Ces instructions devront être réalisées dans un délai de trois jours.").'</p>';
    $description .= '</div>';

    $h .= $form->group(content: $description);

	  $h .= $form->dynamicGroups($eEmployee, ['email*', 'role*']);

    $h .= $form->group(
      content: $form->submit(s("Ajouter"))
    );

    $h .= $form->close();

    return $h;

  }

	protected function getRoles(): string {

		$h = '<ul>';
		$h .= '<li>'.s("<b>Exploitant·e</b> : accède aux mêmes fonctionnalités que vous").'</li>';
		$h .= '<li>'.s("<b>Salarié·e</b> : consulter les données uniquement, sans pouvoir les modifier").'</li>';
		$h .= '<li>'.s("<b>Expert·e comptable</b> : accède à toutes les fonctionnalités sauf les réglages de base, l'équipe, et suppression de la ferme.").'</li>';
		$h .= '</ul>';

		return $h;

	}

  public function update(\company\Employee $eEmployee): \Panel {

    $form = new \util\FormUi();

    $h = $form->openAjax('/company/employee:doUpdate');

    $h .= $form->hidden('id', $eEmployee['id']);

	  if($eEmployee->canUpdateRole()) {

		  $h .= '<div class="util-block-help">';
			  $h .= '<p>';
					$h .= s(
						"Pour rappel, le rôle que vous donnez à {name} conditionne les pages qui lui seront accessibles :",
						['name' => '<b>'.encode($eEmployee['user']['firstName']).' '.encode($eEmployee['user']['lastName']).'</b>'],
						);
				$h .= '</p>';
		    $h .= $this->getRoles();
		  $h .= '</div>';

		  $h .= $form->dynamicGroup($eEmployee, 'role');

	  }
    $h .= $form->group(
      content: $form->submit(s("Modifier"))
    );

    $h .= $form->close();

    return new \Panel(
			id: 'company-employee-update',
      title: s("Modifier une personne de l'équipe"),
      body: $h,
      close: 'reload'
    );

  }

  public static function p(string $property): \PropertyDescriber {

    $d = Employee::model()->describer($property, [
      'email' => s("Adresse e-mail"),
      'user' => s("Utilisateur"),
	    'role' => s("Rôle"),
    ]);

    switch($property) {

      case 'email' :
        $d->field = 'email';
        break;

	    case 'role' :
		    $d->values = [
			    EmployeeElement::OWNER => s("Exploitant·e"),
			    EmployeeElement::EMPLOYEE => s("Salarié·e"),
			    EmployeeElement::ACCOUNTANT => s("Expert·e comptable"),
		    ];
		    $d->attributes['mandatory'] = TRUE;
		    break;

    }

    return $d;

  }

}
