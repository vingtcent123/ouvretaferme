<?php
namespace gallery;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return NULL;

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Photo.created' => s("La photo a bien été envoyée !"),
			'Photo.updated' => s("La photo a bien été mise à jour !"),
			'Photo.deleted' => s("La photo a bien été supprimée !"),

			default => NULL

		};


	}

}
?>
