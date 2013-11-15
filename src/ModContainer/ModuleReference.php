<?php

namespace ModContainer;

class ModuleReference extends AbstractReference
{
	protected $name;

	protected $nested = null;

	public function __construct($name, AbstractReference $nested = null)
	{
		$this->name = $name;
		$this->nested = $nested;
	}
}
