<?php
new \company\InvitePage(function($data) {

	\user\ConnectionLib::checkLogged();
	$data->eCompany = \company\CompanyLib::getById(INPUT('company'))->validate('canManage');

})
  ->getCreateElement(function($data) {

    return new \company\Invite([
    'company' => $data->eCompany,
    ]);

  })
  ->create(function($data) {

    throw new ViewAction($data);

  })
  ->doCreate(fn($data) => throw new RedirectAction(\company\CompanyUi::url($data->e['company']).'/employee:manage?success=company:Invite::created'))
  ->write('doDeleteInvite', function($data) {

    \company\InviteLib::deleteFromEmployee($data->e);

    throw new RedirectAction(\company\CompanyUi::url($data->e['company']).'/employee:manage?success=company:Invite::deleted');

  })
  ->update()
  ->doUpdate(fn($data) => throw new ViewAction($data))
  ->doDelete(fn($data) => throw new RedirectAction(\company\CompanyUi::url($data->e['company']).'/employee:manage?success=company:Invite::deleted'));


(new \company\InvitePage())
  ->write('doExtend', function($data) {

    $data->e['company'] = \company\CompanyLib::getById($data->e['company']);

    \company\InviteLib::deleteByEmail($data->e['email']);
    \company\InviteLib::extends($data->e);

    throw new ReloadAction('company', 'Invite::extended');

  });

(new Page())
  ->get('check', function($data) {

    $data->eInvite = \company\InviteLib::getByKey(GET('key'));

    if($data->eInvite->empty()) {
      throw new ViewAction($data);
    }

    $data->eInvite['company'] = \company\CompanyLib::getById($data->eInvite['company']);

    if(\company\InviteLib::accept($data->eInvite, \user\ConnectionLib::getOnline())) {
      throw new ViewAction($data, ':accept');
    } else {
      throw new ViewAction($data);
    }

  })
  ->post('doAcceptUser', function($data) {

    $data->eInvite = \company\InviteLib::getByKey(POST('key'));

    if(
      $data->eInvite->empty() or
      $data->eInvite['company']['user']->empty()
    ) {
      throw new NotExpectedAction();
    }

    $data->eInvite['company'] = \company\CompanyLib::getById($data->eInvite['company']);

    $eUser = $data->eInvite['employee']['user'];

    $fw = new FailWatch();

    $eUser->build(['email', 'password'], $_POST);

    user\SignUpLib::matchBasicPassword('create', $eUser, $_POST);

    $fw->validate();

    if(\company\InviteLib::acceptUser($data->eInvite, $eUser)) {

      \user\ConnectionLib::logInUser($eUser);

      throw new RedirectAction('/');

    }

  });

?>
