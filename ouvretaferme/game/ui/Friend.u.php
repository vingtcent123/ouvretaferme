<?php
namespace game;

class FriendUi {

	public function display(\Collection $cPlayer, Player $ePlayerOnline): string {

		$h = '<p>'.s("Un joueur pourra vous ajouter comme ami si vous lui communiquez le code <b>{value}</b>. Si un autre joueur vous a communiqué son code, entrez-le ici :", $ePlayerOnline['code']).'</p>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/game/action:doFriendAdd');
			$h .= $form->inputGroup(
				$form->text('code', attributes: ['placeholder' => s("Tapez un code ami")]).
				$form->submit(s("Ajouter"))
			);
		$h .= $form->close();

		$h .= '<br/><br/>';

		$h .= '<h3>'.s("Tableau des amis").'</h3>';

		if($cPlayer->empty()) {
			$h .= '<div class="util-empty">'.s("Vous n'avez pas encore ajouté d'ami sur le jeu !").'</div>';
			return $h;
		}

		$position = 1;

		$h .= '<table class="game-table tr-bordered">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th class="td-min-content text-end">'.s("Position").'</th>';
					$h .= '<th>'.s("Joueur").'</th>';
					$h .= '<th class="text-center">'.s("Rennes attirés 🦌").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cPlayer as $ePlayer) {

					$h .= '<tr>';
						$h .= '<td class="td-min-content text-end">'.\util\TextUi::th($position++).'</td>';
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

		return $h;

	}

}
?>
