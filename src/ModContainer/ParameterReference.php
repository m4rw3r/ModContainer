<?php

namespace ModContainer;

class ParameterReference extends AbstractReference
{
	protected $name;
	
	public function __construct($name)
	{
		$this->name = $name;
	}
}
