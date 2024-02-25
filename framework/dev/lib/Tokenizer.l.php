<?php
namespace dev;

/**
 * Cron library
 */
class TokenizerLib {

	/**
	 * Clean tokens
	 *
	 * @param array $tokens
	 * @param array $options ['whitespace', 'comment']
	 */
	public static function clean(array &$tokens, array $options) {

		$whitespace = in_array('whitespace', $options);
		$comment = in_array('comment', $options);

		foreach($tokens as $key => $value) {

			if(is_array($value)) {

				if($whitespace and $value[0] === T_WHITESPACE) {
					unset($tokens[$key]);
				}

				if(
					$comment and
					($value[0] === T_COMMENT or $value[0] === T_DOC_COMMENT)
				) {
					unset($tokens[$key]);
				}

			}

		}

		$tokens = array_merge($tokens);

	}

}
?>
