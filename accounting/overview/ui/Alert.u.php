<?php
namespace overview;

Class AlertUi {
	public static function getError(string $fqn): mixed {

		return match($fqn) {

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			default => null

		};


	}


}
