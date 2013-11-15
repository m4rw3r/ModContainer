<?php

namespace ModContainer;

use RuntimeException;

class Loader
{
	const FILE_EXT   = '.json';
	const NAME_REGEX = '/^[a-z0-9_-]+$/i';

	protected $include_path = array();

	public function __construct(array $paths = array())
	{
		$paths = empty($paths) ? explode(PATH_SEPARATOR, get_include_path()) : $paths;

		$this->include_path = array_map(function($path) {
			$p = realpath($path);

			if( ! $p) {
				throw new PathException(sprintf('The path "%s" could not be resolved', $path));
			}

			return $p;
		}, $paths);
	}

	public function loadModule($module, Module $parent = null)
	{
		$pathname = false;

		if(strpos($module, './') === 0) {
			/* Path is relative the parent module */
			if( ! $parent) {
				throw new PathException(sprintf('The module-path "%s" is missing a parent module', $module));
			}

			$module   = substr($module, 2);
			$pathname = basename($parent->getSourcePathname()).DIRECTORY_SEPARATOR.$module.self::FILE_EXT;
			$module   = $parent->getName().'/'.$module;
		}
		else {
			/* TODO: Implement a module-map to be able to specify specific paths to some modules */
			foreach($this->include_path as $path) {
				if(file_exists($path.DIRECTORY_SEPARATOR.$module.self::FILE_EXT)) {
					$pathname = $path.DIRECTORY_SEPARATOR.$module.self::FILE_EXT;

					break;
				}
			}
		}
	
		if( ! $pathname || ! file_exists($pathname)) {
			throw new PathException(sprintf('The module "%s" was not found', $module));
		}

		if($parent) {
			$this->ensureNoCycle($parent, $module);
		}

		$data = json_decode(file_get_contents($pathname));

		if(JSON_ERROR_NONE !== json_last_error()) {
			/* TODO: Get a *real* parser with error messages for lines and everything. */
			throw new ParseException(sprintf('%s in file %s', json_last_error_msg(), $pathname));
		}

		if( ! is_object($data)) {
			throw new ParseException(sprintf('Expected file "%s" to contain a JSON-object', $pathname));
		}

		try {
			$mod = new Module($module, $pathname, $parent);

			foreach(get_object_vars($data) as $key => $value) {
				try {
					switch($key) {
						case 'imports':
							$this->parseImports($mod, $value);
							break;
						case 'uses':
							$this->parseUses($mod, $value);
							break;
						case 'parameters':
							$this->parseParameters($mod, $value);
							break;
						case 'services':
							$this->parseServices($mod, $value);
							break;
						case 'exports':
							$this->parseExports($mod, $value);
							break;
						default:
							/* TODO: Line-number and column */
							throw new RuntimeException(sprintf('Unexpected key "%s"', $key));
					}
				}
				catch(ParseException $e) {
					throw new ParseException($e->getMessage().' in '.$key, $e->getCode());
				}
			}
		}
		catch(ParseException $e) {
			$message = $e->getMessage().sprintf(' in module %s ("%s")', $module, $pathname);

			throw new ParseException($message, $e->getCode());
		}

		return $mod;
	}

	protected function parseImports(Module $mod, $imports)
	{
		if( ! is_object($imports)) {
			/* TODO: Line number and column */
			throw new ParseException(sprintf('Expected hash, got %s', gettype($parameter)));
		}

		foreach(get_object_vars($imports) as $key => $value) {
			if(is_scalar($value)) {
				$value = [$value];
			}

			if( ! is_array($value) || ! in_array(count($value), [1, 2])) {
				throw new ParseException(sprintf('Expected string or array of one or two elements in import "%s"', $key));
			}

			if( ! is_string($value[0])) {
				throw new ParseException(sprintf('Import name must be string, %s given', gettype($value[0])));
			}

			$mod->setImport($key, $this->loadModule($value[0], $mod), array_key_exists(1, $value) ? $value[1] : array());
		}
	}

	protected function parseUses(Module $mod, $uses)
	{
		if( ! is_array($uses)) {
			throw new ParseException(sprintf('Expected array, got %s', gettype($uses)));
		}

		foreach($uses as $u) {
			$r = AbstractReference::parseReference($u);

			$mod->addUses($r);
		}
	}

	protected function parseParameters(Module $mod, $parameters)
	{
		if( ! is_object($parameters)) {
			/* TODO: Line number and column */
			throw new ParseException(sprintf('Expected hash, got %s', gettype($parameters)));
		}

		foreach(get_object_vars($parameters) as $key => $value) {
			$mod->setParameter($key, $value);
		}
	}

	protected function parseServices(Module $mod, $services)
	{
		if( ! is_object($services)) {
			/* TODO: Line number and column */
			throw new ParseException(sprintf('Expected hash, got %s', gettype($services)));
		}

		foreach(get_object_vars($services) as $key => $value) {
			
		}
	}

	protected function parseExports(Module $mod, $exports)
	{
		if( ! is_object($exports)) {
			/* TODO: line number and column */
			throw new ParseException(sprintf('Expected hash, got %s', gettype($exports)));
		}

		foreach(get_object_vars($exports) as $key => $value) {
			$mod->setExport($key, AbstractReference::parseReference($value));
		}
	}

	protected function ensureNoCycle(Module $mod, $module_name)
	{
		$path  = array();
		$first = array();

		do {
			$path[] = $module_name;

			if($mod->getName() === $module_name) {
				do {
					$first[] = $mod->getName();

					$mod = $mod->getParent();
				}
				while($mod);

				throw new ParseException(sprintf('Cycle detected in "%s", having path "%s"', implode('.', $first), implode('.', $path)));
			}

			$mod = $mod->getParent();
		}
		while($mod);
	}
}
