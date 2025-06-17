<?php
namespace user;

/**
 * Roles handling
 */
class RoleLib extends RoleCrud {

	/**
	 * Get roles for sign up
	 *
	 */
	public static function getForSignUp(): \Collection {

		$roles = \Setting::get('user\signUpRoles');

		return Role::model()
			->select(['id', 'fqn', 'name', 'emoji'])
			->whereFqn('IN', $roles)
			->getCollection(index: 'fqn');

	}

}
?>
