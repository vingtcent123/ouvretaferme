<?php
new AdaptativeView('/ferme/{id}/optIn', function($data, MainTemplate $t) {

	$form = new \util\FormUi();

	$t->title = s("Recevoir les communications par e-mail d'un producteur");

	$h = $form->open(NULL, ['action' => '/mail/contact:doUpdateOptInByEmail']);
		$h .= $form->hidden('id', $data->eFarm['id']);
		$h .= $form->group(
			s("Producteur"),
			\farm\FarmUi::getVignette($data->eFarm, '3rem').'  '.encode($data->eFarm['name'])
		);
		$h .= $form->group(
			s("Votre adresse e-mail"),
			$form->text('email', $data->email)
		);
		$h .= $form->group(
			s("Recevoir les communications"),
			$form->yesNo('consent', $data->consent, [
				'yes' => s("Oui, les recevoir"),
				'no' => s("Ne rien recevoir")
			])
		);
		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);
	$h .= $form->close();

	echo $h;

});

new AdaptativeView('doUpdateOptInByEmail', function($data, MainTemplate $t) {

	$h = '<div class="text-center">';
		$h .= '<br/><br/>';
		if($data->consent) {
			$h .= '<h2>'.s("Votre abonnement a bien été enregistré !").'</h2>';
			$h .= '<h4>'.s("Vous recevrez désormais les communications par e-mail de ce producteur.").'</h4>';
		} else {
			$h .= '<h2>'.s("Votre désabonnement a bien été enregistré !").'</h2>';
			$h .= '<h4>'.s("Vous ne recevrez plus de communication par e-mail de la part de ce producteur.").'</h4>';
		}
		$h .= '<br/><br/>';
	$h .= '</div>';

	$t->header = $h;

});

new JsonView('doUpdateOptOut', function($data, AjaxTemplate $t) {
	$t->qs('#contact-switch-'.$data->e['id'])->toggleSwitch('post-opt-out', [TRUE, FALSE]);
});

new AdaptativeView('updateOptIn', function($data, PanelTemplate $t) {
	return new \mail\ContactUi()->updateOptIn($data->cContact);
});
?>
