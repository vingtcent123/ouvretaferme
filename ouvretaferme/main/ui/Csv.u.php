<?php
namespace main;

class CsvUi {

	public function getImportButton(\farm\Farm $eFarm, string $url): string {

		$form = new \util\FormUi();

		$h = $form->openUrl($url, ['binary' => TRUE, 'method' => 'post']);
			$h .= $form->hidden('id', $eFarm['id']);
			$h .= '<label class="btn btn-primary">';
				$h .= $form->file('csv', ['onchange' => 'this.form.submit()']);
				$h .= s("Importer un fichier CSV depuis mon ordinateur");
			$h .= '</label>';
		$h .= $form->close();

		return $h;

	}

	public static function getSyntaxInfo(): string {

		$h = '<ul>';
			$h .= '<li>'.s("La première ligne du fichier CSV correspond aux en-têtes qui doivent être recopiées sans modification").'</li>';
			$h .= '<li>'.s("Le séparateur des colonnes dans le fichier est la virgule (,)").'</li>';
		$h .= '</ul>';

		return $h;

	}

	public static function getGlobalErrors(int $count, string $url): string {

		$h = '<div class="util-block">';

			$h .= '<h4 class="color-danger">'.p("{value} problème a été trouvé dans le fichier CSV", "{value} problèmes ont été trouvés dans le fichier CSV", $count).'</h4>';

			$h .= '<p>'.s("Vous pouvez parcourir le tableau ci-dessous pour identifier ces problèmes et les corriger. Pour que {siteName} puisse importer vos données sans erreur, il est indispensable que le format CSV soit strictement respecté. Si vous n'êtes pas à l'aise avec cela, nous vous recommandons de ne pas utiliser cette fonctionnalité et de saisir vos données manuellement.").'</p>';

			$h .= '<a href="'.$url.'" class="btn btn-primary">'.s("Voir la documentation du format").'</a>';

		$h .= '</div>';

		return $h;

	}

	public static function getDataList($data): string {

		$mandatory = FALSE;

		$h = '<div class="util-overflow-lg">';
			$h .= '<table>';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Type de donnée").'</th>';
						$h .= '<th>'.s("Nom de l'entête").'</th>';
						$h .= '<th>'.s("Description").'</th>';
						$h .= '<th>'.s("Exemple").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';
					foreach($data as $row) {

						if(is_string($row)) {
							$h .= '<tr class="tr-title">';
								$h .= '<td colspan="4">'.$row.'</td>';
							$h .= '</tr>';
						} else {

							[$title, $column, $description, $example] = $row;

							$mandatory = ($mandatory or str_contains($title, 'form-asterisk'));

							$h .= '<tr>';
								$h .= '<td>'.$title.'</td>';
								$h .= '<td><pre>'.$column.'</pre></td>';
								$h .= '<td style="max-width: 25rem">'.$description.'</td>';
								$h .= '<td><div class="doc-example">'.$example.'</div></td>';
							$h .= '</tr>';

						}
					}
				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		if($mandatory) {
			$h .= \util\FormUi::asteriskInfo(NULL);
		}

		return $h;

	}

}
?>
