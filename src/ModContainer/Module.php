<?php

namespace ModContainer;

use RuntimeException;

class Module
{
	const NAME_REGEX      = '/^[a-z0-9_-]+$/i';
	
	protected $name;
	protected $source_pathname;
	protected $parent     = null;
	protected $parameters = array();
	protected $services   = array();
	protected $exports    = array();
	protected $uses       = array();
	
	public function __construct($name, $source_pathname, $parent = null)
	{
		$this->name            = $name;
		$this->source_pathname = $source_pathname;
		$this->parent          = $parent;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSourcePath()
	{
		return $this->source_pathname;
	}

	public function getParent()
	{
		return $this->parent;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function getParameter($key)
	{
		if( ! array_key_exists($key, $this->parameters)) {
			throw new MissingParameterException(sprintf('Missing parameter "%s"', $key));
		}

		return $this->parameters[$key];
	}

	public function hasParameter($key)
	{
		return array_key_exists($key, $this->parameters);
	}

	public function setParameter($key, $value)
	{
		if(array_key_exists($key, $this->parameters)) {
			throw new RuntimeException(sprintf('Attempting to overwrite parameter "%s"', $key));
		}

		if( ! preg_match(self::NAME_REGEX, $key)) {
			throw new ParseException(sprintf('Invalid parameter name "%s"', $key));
		}

		if( ! is_scalar($value)) {
			/* TODO: Line number and column */
			throw new ParseException(sprintf('Expected scalar parameter at key "%s", got %s', $key, gettype($value)));
		}

		$this->parameters[$key] = $value;
	}

	public function getServices()
	{
		return $this->services;
	}

	public function getService($key)
	{
		if( ! array_key_exists($key, $this->services)) {
			throw new MssingServiceException(sprintf('No service with name "%s" exists', $key));
		}

		return $this->services[$key];
	}

	public function hasService($key)
	{
		return array_key_exists($key, $this->services);
	}

	public function setService($key, Service $service)
	{
		if(array_key_exists($key, $this->services)) {
			throw new RuntimeException(sprintf('Attempting to overwrite service "%s"', $key));
		}

		if( ! is_string($key)) {
			/* TODO: line number and column */
			throw new ParseException(sprintf('Invalid key type "%s"', gettype($key)));
		}

		if( ! preg_match(self::NAME_REGEX, $key)) {
			throw new ParseException(sprintf('Invalid service name "%s"', $key));
		}

		$this->services[$key] = $service;
	}

	public function getExports()
	{
		return $this->exports;
	}

	public function getExport($key)
	{
		if( ! array_key_exists($key, $this->exports)) {
			throw new Exception(sprintf('No export with name "%s" exists', $key));
		}

		return $this->exports[$key];
	}

	public function hasExport($key)
	{
		return array_key_exists($key, $this->exports);
	}

	public function setExport($key, AbstractReference $reference)
	{
		if(array_key_exists($key, $this->services)) {
			throw new RuntimeException(sprintf('Attempting to overwrite export "%s"', $key));
		}

		if( ! is_string($key)) {
			/* TODO: line number and column */
			throw new ParseException(sprintf('Invalid key type "%s"', gettype($key)));
		}

		if( ! preg_match(self::NAME_REGEX, $key)) {
			throw new ParseException(sprintf('Invalid export name "%s"', $key));
		}

		$this->exports[$key] = $reference;
	}

	public function getImports()
	{
		return $this->imports;
	}

	public function getImport($key)
	{
		if( ! array_key_exists($key, $this->imports)) {
			throw new RuntimeException(sprintf('No import with name "%s" exists', $key));
		}

		return $this->imports[$key];
	}

	public function getImportParameters($key)
	{

	}

	public function setImport($key, Module $import, $parameters)
	{
		$this->imports[$key] = [
			'module' => $import,
			'parameters' => $parameters
		];
	}

	public function addUses(AbstractReference $uses)
	{
		if($uses instanceof ModuleReference) {
			throw new ParseException(sprintf('Invalid reference type, only parameter or service references allowed'));
		}

		/* TODO: Check for duplicate */
		$this->uses[] = $uses;
	}
}
