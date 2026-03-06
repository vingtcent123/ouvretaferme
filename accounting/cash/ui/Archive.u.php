<?php
namespace cash;

class ArchiveUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');
		\Asset::js('cash', 'cash.js');

	}

	public function getCsvHeader(): array {

		return [
			s("Caisse"),
			s("Opération"),
			s("Moyen de paiement"),
			s("Nature de l'opération"),
			s("Mouvement"),
			s("Montant HT"),
			s("Montant TTC"),
			s("TVA"),
			s("Taux de TVA"),
			s("Référence"),
			s("Solde de la caisse"),
		];

	}

	public static function getReference(Cash $eCash): ?string {

		return match($eCash['source']) {

			Cash::SELL_SALE => s("Vente n°{value}", $eCash['sale']['document']),
			Cash::SELL_INVOICE => s("Facture n°{value}", $eCash['invoice']['number']),
			default => NULL

		};

	}

	public function getList(\Collection $cArchive): string {

		if($cArchive->empty()) {
			return '<div class="util-empty">'.s("Vous n'avez pas encore généré d'archive de vos journaux de caisse.").'</div>';
		}

		$h = '<div class="util-block-info">';
			$h .= \Asset::icon('info', ['class' => 'util-block-icon']);
			$h .= '<p>'.s("Les archives de vos données des journaux de caisse peuvent être communiquées à l'administration fiscale en cas de contrôle ou être utilisées pour vos analyses personnelles. Ces archives viennent en complément des <link>archives que vous pouvez réaliser à partir de vos ventes</link>.", ['link' => '<a href="/selling/archives?id='.\farm\Farm::getConnected()['id'].'">']).'</p>';
		$h .= '</div>';

		$h .= '<div class="util-overflow-md">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-min-content text-center">'.s("Archive").'</th>';
						$h .= '<th>'.s("Début").'</th>';
						$h .= '<th>'.s("Fin").'</th>';
						$h .= '<th>'.s("SHA256").'</th>';
						$h .= '<th>'.s("Généré").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cArchive as $eArchive) {

					$h .= '<tr>';
						$h .= '<td class="text-center">';
							$h .= '<div class="btn btn-outline-primary btn-readonly btn-xs">'.$eArchive['id'].'</div>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['from']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['to']);
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<span class="color-muted font-sm">'.$eArchive['sha256'].'</span>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eArchive['createdAt'], \util\DateUi::DATE_HOUR_MINUTE);
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/archives:get?id='.$eArchive['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.s("Télécharger").'</a>';
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(\cash\Archive $eArchive): \Panel {


		$form = new \util\FormUi();

		$h = $form->openAjax(\farm\FarmUi::urlConnected().'/cash/archives:doCreate', ['class' => 'archive-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eArchive, ['from*', 'to*']);

			$h .= $form->group(
				content: $form->submit(
					s("Créer l'archive")
				)
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-archive-create',
			title: s("Créer une archive"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \cash\Archive::model()->describer($property, [
			'from' => s("Début des données à archiver"),
			'to' => s("Fin des données à archiver"),
		]);

		return $d;

	}

}
?>
