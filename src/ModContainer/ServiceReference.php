<?php

namespace ModContainer;

class ServiceReference extends AbstractReference
{
	protected $name;

	public function __construct($name)
	{
		$this->name = $name;
	}
}
