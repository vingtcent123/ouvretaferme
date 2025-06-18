<?php
new AdaptativeView('manage', function($data, CompanyTemplate $t) {

  $t->title = s("L'équipe de {value}", $data->eCompany['name']);
  $t->tab = 'settings';
  $t->subNav = (new \company\CompanyUi())->getSettingsSubNav($data->eCompany);

  $t->mainTitle = (new \company\EmployeeUi())->getManageTitle($data->eCompany);

  echo (new \company\EmployeeUi())->getManage($data->eCompany, $data->cEmployee, $data->cInvite);

});

new AdaptativeView('show', function($data, CompanyTemplate $t) {

  $t->title = \user\UserUi::name($data->eEmployee['user']);
  $t->tab = 'settings';
  $t->subNav = (new \company\CompanyUi())->getSettingsSubNav($data->eEmployee['company']);

  $t->mainTitle = (new \company\EmployeeUi())->getUserTitle($data->eEmployee);

  echo (new \company\EmployeeUi())->getUser($data->eEmployee);

});

new AdaptativeView('createUser', function($data, PanelTemplate $t) {
  return (new \company\EmployeeUi())->createUser($data->eCompany);
});

new AdaptativeView('updateUser', function($data, PanelTemplate $t) {
  return (new \company\EmployeeUi())->updateUser($data->eCompany, $data->eUserOnline);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
  return (new \company\EmployeeUi())->create($data->e, $data->eEmployeeLink);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
  return (new \company\EmployeeUi())->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
  $t->js()->moveHistory(-1);
});
?>
