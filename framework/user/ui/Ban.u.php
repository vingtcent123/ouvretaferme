<?php

namespace user;

/**
 * Banishment display.
 */
class BanUi {

	public function __construct() {

		\Asset::css('user', 'user.css');
		\Asset::css('user', 'ban.css');

	}

	public function create(User $eUser, $userToBanIp, $nUserOnIp): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/user/admin/ban:do');

		$type = $form->checkbox('type[]', 'user', ['callbackLabel' => fn($input) => $input.' '.s("Utilisateur")]);

		if($userToBanIp === NULL) {

			$type .= '<p class="util-warning">'.
				s("Note : l'adresse IP de cet utilisateur n'a pas pu être déterminée. Il n'est donc pas possible d'ajouter un bannissement par IP.").
				'</p>';

		} elseif($nUserOnIp < \Setting::get('user\maxBanOnSameIp')) {

			$type .= $form->checkbox('type[]', 'ip', ['callbackLabel' => fn($input) => $input.' '.s("Adresse IP").' '.p("({value} personne connectée)", "({value} personnes connectées)", $nUserOnIp)]);

	} else {

			$type .= '<p class="util-warning">'.p("Note : le bannissement par adresse IP n'est pas possible quand plus de {value} personne est connectée sur cette même adresse.",
					"Note : le bannissement par adresse IP n'est pas possible quand plus de {value} personnes sont connectées sur cette même adresse.",
					Setting::get('user\maxBanOnSameIp')
				).'</p>';

		}

		$h .= $form->group(
			s("Étendue du bannissement"),
			$type
		);

		$h .= $form->group(
			s("Motif"),
			$form->textarea('reason', NULL, ['placeholder' => s("Indiquez le motif du bannissement.
Attention : ce texte sera affiché à l'utilisateur.")])
		);

		$h .= $form->hidden('user', $eUser['id']);

		$h .= $this->getDurationSelect($form);

		$h .= $form->submit(\Asset::icon('slash-circle-fill').' '.s("Bannir"), ['class' => 'btn btn-danger']);

		return new \Panel(
			title: s("Bannir {email}", ['email' => encode($eUser['email'])]),
			body: $h
		);

	}

	/**
	 * Display a list of Ban into a table.
	 *
	 */
	public function getCollection(bool $active, \Collection $cBan): string {

		if($cBan->empty()) {
			return '<div class="util-info">
				'.s("Aucun bannissement actuellement.").'
			</div>';
		}

		$h = '<table class="tr-bordered ban-table">
				<thead>
					<tr>
						<th>'.s("Qui / Quoi").'</th>
						<th>'.s("Depuis le").'</th>
						<th>'.s("Jusqu'au").'</th>
						<th>'.s("Motif").'</th>
					</tr>
				</thead>
				<tbody>';

		foreach($cBan as $eBan) {

			$h .= $this->getOne($active, $eBan);

		}

		$h .= '</tbody>
		</table>';

		return $h;

	}

	/**
	 * Display a ban.
	 *
	 */
	protected function getOne(bool $active, Ban $eBan): string {

		if($eBan['user']->empty()) {
			$user = '';
		} else {
			$user = encode($eBan['user']['email']);
		}

		if($eBan['ip'] === NULL) {
			$ip = '';
		} else {
			$ip = '<p class="color-primary">'.$eBan['ip'].'</p>';
		}

		$since = \util\DateUi::numeric($eBan['since'], \util\DateUi::DATE_HOUR_MINUTE);

		if($eBan['until'] === NULL) {
			$until = s("Indéfiniment");
		} else {
			$until = \util\DateUi::numeric($eBan['until'], \util\DateUi::DATE_HOUR_MINUTE);
		}

		$until = '<div id="ban-value-'.$eBan['id'].'">'.$until.'</div>';

		$h = '<tr>
			<td class="ban-cell">'.$user.$ip.'</td>
			<td class="ban-cell ban-date-cell">'.$since.'</td>
			<td class="ban-cell ban-date-cell">'.$until.'</td>
			<td class="ban-cell ban-long-text-cell">'.s("{email} : {reason}", ['email' => UserUi::name($eBan['admin']), 'reason' => encode($eBan['reason'])]).'</td>
		</tr>';

		// Actions for active bans
		if($active) {

			$actions = '<div>';

				$actions .= '<a data-ajax="/user/admin/ban:doEnd" post-id="'.$eBan['id'].'" class="btn btn-secondary">'.s("Mettre fin").'</a> ';
				$actions .= '<a href="/user/admin/ban:updateEndDate?id='.$eBan['id'].'" class="btn btn-secondary">'.s("Changer la date de fin").'</a>';

			$actions .= '</div>';

			$h .= '<tr><td colspan="4" class="ban-btn-bar">'.$actions.'</td></tr>';

		}

		return $h;

	}

	public function updateEndDate(Ban $eBan): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/user/admin/ban:doUpdateEndDate');
			$h .= $this->getDurationSelect($form);
			$h .= $form->hidden('id', $eBan['id']);
			$h .= $form->submit(s("Enregistrer"));
		$h .= $form->close();

		return new \Panel(
			title: s("Changer la fin du bannissement"),
			body: $h
		);

	}

	/**
	 * Display the ban duration select box.
	 *
	 * @param \util\FormUi $form
	 * @return string
	 */
	public function getDurationSelect(\util\FormUi $form): string {

		$list = [
			1 => $this->getDurationLabel(1, 'day'),
			3 => $this->getDurationLabel(3, 'day'),
			7 => $this->getDurationLabel(1, 'week'),
			14 => $this->getDurationLabel(2, 'week'),
			30 => $this->getDurationLabel(1, 'month'),
			90 => $this->getDurationLabel(3, 'month'),
			-1 => s("Indéfiniment")
		];

		$h = $form->group(
			s("Durée"),
			$form->select('duration', $list, NULL, ['mandatory' => TRUE])
		);

		return $h;
	}

	public function getConnectionBanned(Ban $eBan): string {

		// Specific return for login throught networks
		if($eBan->empty()) {
			return s("Impossible de se connecter, car vous êtes banni");
		}

		// Limitless bannishement
		if($eBan['until'] === NULL) {
			return s(
				"Impossible de se connecter, car vous êtes banni pour la raison suivante : {reason}",
				['reason' => encode($eBan['reason'])]
			);
			// Regular bannishement
		} else {
			return s(
				"Impossible de se connecter, car vous êtes banni jusqu'au {date} pour la raison suivante : {reason}",
				['date' => \util\DateUi::textual($eBan['until'], \util\DateUi::DATE), 'reason' => encode($eBan['reason'])]
			);
		}

	}

	public function getSignUpBanned(Ban $eBan): string {

		return s(
			"Impossible de créer un compte, car votre adresse IP est bannie jusqu'au {date} pour la raison suivante : {reason}",
			['date' => \util\DateUi::textual($eBan['until'], \util\DateUi::DATE), 'reason' => encode($eBan['reason'])]
		);

	}

	/**
	 * Get a label for a time duration.
	 *
	 * @param int $number
	 * @param string $type
	 * @return string
	 */
	protected function getDurationLabel(int $number, string $type): string {

		switch($type) {

			case 'day' :
				return p('{value} jour', '{value} jours', $number);

			case 'week' :
				return p('{value} semaine', '{value} semaines', $number);

			case 'month' :
				return p('{value} mois', '{value} mois', $number);

		}

	}

}
?>
