<?php

	namespace dev;

	class LimeObserverLib {

	  /**
	   * Generates _ide_lime.php at project root for IDE autocompletion.
	   * Triggered by Route::loadConf() in dev mode only.
	   */
	  public static function loadConf(): void {

      $outputFile = LIME_DIRECTORY.'/_ide_lime.php';
      $srcPath = LIME_DIRECTORY;

      $models = self::scanModels($srcPath);

      if(empty($models)) {
          return;
      }

      file_put_contents($outputFile, self::generate($models));

	  }

    private static function scanModels(string $srcPath): array {

      $models = [];

      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($srcPath)
      );

      foreach($iterator as $file) {

        if(!str_ends_with((string)$file, '.m.php')) {
          continue;
        }

        $code = file_get_contents($file);

        if(!preg_match('/^namespace\s+(\w+);/m', $code, $nsMatch)) {
          continue;
        }

        if(!preg_match('/class\s+(\w+)Model\s+extends/', $code, $classMatch)) {
          continue;
        }

        $namespace = trim($nsMatch[1]);
        $className = $classMatch[1];

        $properties = [];

        if(preg_match('/\$this->properties\s*=\s*array_merge\([^,]+,\s*\[(.*?)\]\s*\);/s', $code, $propsMatch)) {
          preg_match_all("/^\s*'(\w+)'\s*=>\s*\[/m", $propsMatch[1], $propMatches);
          $properties = $propMatches[1];
        }

        $fqn = $namespace . '\\' . $className;

        if(!isset($models[$fqn])) {
          $models[$fqn] = [
            'namespace' => $namespace,
            'class' => $className,
            'properties' => $properties,
          ];
        }

      }

      ksort($models);

      return $models;

    }

    private static function generate(array $models): string {

      $out = '<?php' . "\n";
      $out .= '/**' . "\n";
      $out .= ' * Lime IDE Helper - Auto-generated ' . date('Y-m-d H:i:s') . "\n";
      $out .= ' * DO NOT INCLUDE IN PRODUCTION - file is in .gitignore' . "\n";
      $out .= ' */' . "\n\n";

      // Templated Collection for foreach type inference
      $out .= '/**' . "\n";
      $out .= ' * @template T' . "\n";
      $out .= ' * @extends ArrayIterator<int, T>' . "\n";
      $out .= ' */' . "\n";
      $out .= 'class Collection extends ArrayIterator {' . "\n";
      $out .= '    /** @return T */' . "\n";
      $out .= '    public function current(): mixed {}' . "\n";
      $out .= '}' . "\n\n";

      // 1. Element classes with their properties
      foreach($models as $model) {

        $ns = $model['namespace'];
        $cls = $model['class'];
        $props = $model['properties'];

        $out .= 'namespace ' . $ns . ' {' . "\n\n";
        $out .= '    /**' . "\n";

        foreach($props as $p) {
            $out .= '     * @property mixed $' . $p . "\n";
        }

        $out .= '     */' . "\n";
        $out .= '    class ' . $cls . ' extends \Element {}' . "\n\n";
        $out .= '}' . "\n\n";

      }

      // 2. Global $eXxx / $cXxx variables + LimeData class
      $out .= 'namespace {' . "\n\n";
      $out .= 'if(false) {' . "\n\n";

      foreach($models as $model) {

        $ns = $model['namespace'];
        $cls = $model['class'];

        $out .= '    /** @var \\' . $ns . '\\' . $cls . ' $e' . $cls . ' */' . "\n";
        $out .= '    $e' . $cls . ' = new \\' . $ns . '\\' . $cls . '();' . "\n\n";

        $out .= '    /** @var Collection<\\' . $ns . '\\' . $cls . '> $c' . $cls . ' */' . "\n";
        $out .= '    $c' . $cls . ' = new Collection();' . "\n\n";

      }

      $out .= '}' . "\n\n";

      // 3. LimeData for $data->eXxx / $data->cXxx
      $out .= '/**' . "\n";

      foreach($models as $model) {
        $ns = $model['namespace'];
        $cls = $model['class'];
        $out .= ' * @property \\' . $ns . '\\' . $cls . ' $e' . $cls . "\n";
        $out .= ' * @property Collection<\\' . $ns . '\\' . $cls . '> $c' . $cls . "\n";
      }

      $out .= ' */' . "\n";
      $out .= 'class LimeData extends \stdClass {}' . "\n\n";

      $out .= '}' . "\n";

      return $out;

    }

	}
