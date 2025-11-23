<?php
namespace game;

class PlayerUi {

	public static function getTime(float $time): string {
		return \series\TaskUi::convertTime($time, showMinutes: NULL);
	}

	public function create(Player $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/game/:doNew');

			$h .= $form->dynamicGroup($e, 'name');

			$h .= $form->group(
				content: $form->submit(s("C'est parti"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-game-create',
			title: \s("Commencer à jouer"),
			body: $h
		);

	}

	public function getRankings(\Collection $cPlayer, Player $ePlayerOnline, \Collection $cFood): string {

		if($cPlayer->empty()) {
			return '<div class="util-empty">'.s("Le classement est encore vide et c'est normal le jeu vient de commencer !").'</div>';
		}

		$h = '';

		if($cPlayer->count() < 10) {
			$h .= '<div class="util-empty">'.s("Le classement n'est pas encore bien rempli, les premières cultures arrivent seulement à leur terme !").'</div>';
		}

		$h .= '<h3>'.S("Rennes attirés 🦌").'</h3>';

		$h .= '<table class="game-table tr-bordered">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th class="text-end td-min-content">'.s("Position").'</th>';
					$h .= '<th>'.s("Joueur").'</th>';
					$h .= '<th class="text-center"></th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cPlayer as $ePlayer) {

					$h .= '<tr '.($ePlayer->is($ePlayerOnline) ? 'style="background-color: #0001"' : '').'>';
						$h .= '<td class="text-end"><b>'.\util\TextUi::th($ePlayer['position']).'</b></td>';
						$h .= '<td>';
							$h .= encode($ePlayer['name']);
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<b>'.$ePlayer['points'].'</b>';
						$h .= '</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';
		$h .= '</table>';

		$h .= '<br/>';

		$h .= '<h3>'.S("Votre production").'</h3>';

		$h .= '<table class="game-table tr-bordered">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th colspan="2">'.s("Légume").'</th>';
					$h .= '<th class="text-center">'.s("Total produit").'</th>';
					$h .= '<th class="text-center td-min-content">'.s("Position").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cFood as $eFood) {

					$h .= '<tr>';
						$h .= '<td class="td-min-content">';
							$h .= $eFood['growing']->empty() ? \Asset::icon('cup-hot', ['class' => 'asset-icon-lg']) : GrowingUi::getVignette($eFood['growing'], '2rem');
						$h .= '</td>';
						$h .= '<td>';
							$h .= $eFood['growing']->empty() ? s("Soupe") : $eFood['growing']['name'];
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<b>'.$eFood['total'].'</b>';
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<b>'.\util\TextUi::th($eFood['position']).'</b>';
						$h .= '</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';
		$h .= '</table>';

		return $h;

	}


	public static function p(string $property): \PropertyDescriber {

		$d = Player::model()->describer($property, [
			'name' => s("Choisissez un nom de joueur"),
		]);

		switch($property) {

			case 'name' :
				$d->labelAfter = \util\FormUi::info(s("Ce n'est pas forcément votre nom réel !"));
				$d->placeholder = s("Ex. : Toto");
				break;

		}

		return $d;

	}

}
?>
