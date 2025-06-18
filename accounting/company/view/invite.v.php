<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {
  return (new \company\InviteUi())->create($data->e);
});

new AdaptativeView('check', function($data, MainTemplate $t) {

  $t->title = s("Accepter une invitation");
  $t->metaNoindex = TRUE;

  echo (new \company\InviteUi())->check($data->eInvite);

});

new AdaptativeView('accept', function($data, MainTemplate $t) {

  $t->title = s("Invitation acceptée !");
  $t->metaNoindex = TRUE;

  echo (new \company\InviteUi())->accept($data->eInvite);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
  return (new \company\InviteUi())->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
  $t->js()->moveHistory(-1);
});
?>
