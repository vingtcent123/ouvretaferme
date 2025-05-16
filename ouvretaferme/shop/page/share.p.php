<?php
new \shop\SharePage()
	->update(function($data) {
		
		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new \ViewAction($data);

	})
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\shop\ShareLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->quick(['label'])
	->doUpdate(fn() => throw new ReloadAction('shop', 'Share::updated'))
	->doDelete(fn($data) => $data->e->isSelf() ?
		throw new RedirectAction(\farm\FarmUi::urlShopList($data->e['farm']).'?success=shop:Share::deletedSelf') :
		throw new ReloadAction('shop', 'Share::deleted'));
?>