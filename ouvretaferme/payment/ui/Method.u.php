<?php
namespace payment;

/**
 * Stripe UI
 *
 */
class MethodUi {

	public function __construct() {

		\Asset::css('payment', 'method.css');
		\Asset::js('payment', 'method.js');

	}

	public function getManageTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a href="'.\farm\FarmUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Moyens de paiement");
			$h .= '</h1>';
			$h .= '<div>';
				$h .= '<a href="/payment/method:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau moyen de paiement").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getManage(\Collection $cMethod): string {

		$h = '';

		if($cMethod->contains(fn($eUnit) => $eUnit['farm']->notEmpty()) === FALSE) {

			$h .= '<div class="util-block-help"">';
				$h .= s("Des moyens de paiement par défaut sont déjà fournis avec {siteName} et ne peuvent pas être modifiés. Cependant, vous avez la possibilité de créer de nouveaux moyens de paiement adaptés à votre contexte de commercialisation.");
			$h .= '</div>';

		}

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>'.s("Nom").'</th>';
					$h .= '<th>'.s("Activé").'</th>';
					$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cMethod as $eMethod) {

						$h .= '<tr class="'.($eMethod['farm']->empty() ? 'color-muted' : '').'">';
							$h .= '<td>';

							if($eMethod['farm']->empty()) {
								$h .= s("Fourni par défaut");
							} else {
								$h .= s("Personnalisé");
							}

							$h .= '</td>';
							$h .= '<td>';
								$h .= $eMethod['farm']->empty() ? encode($eMethod['name']) : $eMethod->quick('name', encode($eMethod['name']));
							$h .= '</td>';


							$h .= '<td class="td-min-content">';
							if($eMethod['farm']->exists() === FALSE) {
								$h .= s("Oui (par défaut)");
							} else {
								$h .= \util\TextUi::switch([
									'id' => 'method-switch-'.$eMethod['id'],
									'disabled' => $eMethod->canWrite() === FALSE,
									'data-ajax' => $eMethod->canWrite() ? '/payment/method:doUpdateStatus' : NULL,
									'post-id' => $eMethod['id'],
									'post-status' => ($eMethod['status'] === Method::ACTIVE) ? Method::INACTIVE : Method::ACTIVE
								], $eMethod['status'] === Method::ACTIVE);
							}
							$h .= '</td>';

							$h .= '<td class="text-end">';

							if($eMethod['farm']->notEmpty()) {

								$h .= '<a href="/payment/method:update?id='.$eMethod['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a> ';

							}

							$h .= '</td>';
						$h .= '</tr>';
					}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(Method $eMethod): \Panel {

		$eMethod->expects(['farm']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/payment/method:doCreate');

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eMethod['farm']['id']);
		$h .= $form->dynamicGroups($eMethod, ['name*']);
		$h .= $form->group(
			content: $form->submit(s("Ajouter"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-method-create',
			title: s("Ajouter un nouveau moyen de paiement"),
			body: $h
		);

	}

	public function update(Method $eMethod): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/payment/method:doUpdate');

		$h .= $form->hidden('id', $eMethod['id']);
		$h .= $form->dynamicGroups($eMethod, ['name']);
		$h .= $form->group(
			content: $form->submit(s("Modifier"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-method-update',
			title: s("Modifier le moyen de paiement"),
			body: $h
		);

	}

	public static function getOnlineCardText(): string {

		return \Asset::icon('stripe', ['title' => 'Stripe']).' '.s("Carte bancaire");

	}

	public static function getShortValues(Method $eMethod): string {

		$methodElements = explode(' ', $eMethod['name']);
		$value = '';
		foreach($methodElements as $element) {
			$value .= substr($element, 0, 1);
		}
		return mb_strtoupper($value);

	}

	public static function p(string $property): \PropertyDescriber {

		return Method::model()->describer($property, [
			'name' => s("Nom du moyen de paiement"),
		]);

	}

}
