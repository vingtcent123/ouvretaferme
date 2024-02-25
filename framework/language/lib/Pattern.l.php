<?php
namespace language;

/**
 * Pattern filter
 */
class PatternLib {

	protected $package;

	protected $langPattern = [];
	protected $scopePattern = [];

	/**
	 * Init pattern filter
	 *
	 * @param ReflectionPackage $package Scope of the filter
	 */
	public function __construct(\ReflectionPackage $package) {

		$this->package = $package;

		$this->init();

	}

	/**
	 * Matching of path with scope and language with lang pattern
	 *
	 * Priority has opered :
	 *   Lang : null > lang or *
	 *   Path : file > dir > *
	 *
	 * @param string $pathLang The path that will be autorised or not for the translation
	 * @param string $lang The language that will be autorised or not for the translation
	 *
	 * @return bool TRUE
	 */
	public function match(string $pathLang, string $lang): bool {

		$match = [];
		$langMatch = [];

		$directoryLang = $this->package->getPath().'/lang/'.$lang;
		$file = substr($pathLang, strlen($directoryLang) + 1);

		foreach($this->scopePattern as $nScope => $scope) {

			$scopeLangs = $this->langPattern[$nScope];

			if($scope === '*') {
				$match['star'] = $scopeLangs;
				continue;
			}

			if(strpos($scope, '*/') === 0) {

				$subScope = substr($scope, 2);

				if(preg_match("/^".str_replace("/", "\/", $directoryLang)."\/(.*\/?)".str_replace("/", "\/", $subScope)."/", $pathLang, $matches)) {

					if(is_file($matches[0].'.m.php') === TRUE) {
						$match['file'] = $scopeLangs;
					}

					if(is_dir($matches[0]) === TRUE) {
						$match['dir'] = $scopeLangs;
					}

				}

			} else {

				if(strpos($file, $scope) === 0) {

					if(is_file($directoryLang.'/'.$scope.'.m.php') === TRUE) {
						$match['file'] = $scopeLangs;
					}

					if(is_dir($directoryLang.'/'.$scope) === TRUE) {
						$match['dir'] = $scopeLangs;
					}

				}

			}
		}

		// Manage priority between the differents pattern
		if(isset($match['star']) === TRUE) {
			$langMatch = $match['star'];
		}
		if(isset($match['dir']) === TRUE) {
			$langMatch = $match['dir'];
		}
		if(isset($match['file']) === TRUE) {
			$langMatch = $match['file'];
		}

		if(in_array('null', $langMatch) === TRUE) {
			return FALSE;
		}

		if(
			in_array($lang, $langMatch) === TRUE or
			in_array('*', $langMatch) === TRUE
		) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Set the properties of class with the data content in file.
	 * First sort of the content properties
	 *
	 * @param string $content data must be mathing with regexp
	 *
	 * @return boolean TRUE if the setting is ok or throw a exception
	 */
	protected function init() {

		$this->scopePattern = [];
		$this->langPattern = [];

		$path = $this->package->getPath().'/lang/pattern.cfg';

		if(is_file($path)) {
			$content = file_get_contents($path);
		} else {
			$content = '*:*';
		}

		$contentClean = explode("\n", $content);

		foreach($contentClean as $key => $line) {
			$line = trim($line);
			if(empty($line) or substr($line, 0, 1) === '#') {
				unset($contentClean[$key]);
			} else {
				$contentClean[$key] = $line;
			}
		}

		$content = implode("\n", $contentClean);

		$regPatternFile = '/(.*)\s*:\s*(([a-z]{2}_[A-Z]{2}\s*,?\s*)+|\*|null)\s*/';

		if(preg_match_all($regPatternFile, $content, $matches) === 0) {
			throw new \Exception("Pattern must be checked.");
		}

		for($nLine = 0; $nLine < count($matches[0]); $nLine++) {

			$scope = $matches[1][$nLine];
			$scopeLangs = preg_split("/\s*,\s*/si", trim($matches[2][$nLine]));

			if($matches[2][$nLine] === 'null') {
				array_unshift($this->scopePattern, $scope);
				array_unshift($this->langPattern, $scopeLangs);
			}
			else {
				$this->scopePattern[] = $scope;
				$this->langPattern[] = $scopeLangs;
			}

		}

	}

}
?>