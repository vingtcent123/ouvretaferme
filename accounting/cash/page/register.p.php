<?php
new \cash\RegisterPage()
	->create()
	->doCreate(fn($data) => throw new RedirectAction('/farm/farmer:manage?farm='.$data->e['farm']['id'].'&success=farm\\Farmer::created'));
?>
