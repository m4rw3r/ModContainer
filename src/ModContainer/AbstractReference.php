<?php

namespace ModContainer;

abstract class AbstractReference {
	/**
	 * 1: Parameter
	 * 2: Service
	 * 3: Module
	 * 4: Module reference
	 */
	const REFERENCE_REGEX = '/^(?:\$(?<param>\w+)|(?<srv>\w+)|(?<module>\w+)\.(?<data>[$\w]+))$/i';
	public static function parseReference($reference_str)
	{
		if( ! preg_match(self::REFERENCE_REGEX, $reference_str, $matches)) {
			throw new ParseException(sprintf('Invalid reference name "%s"', $reference_str));
		}

		if( ! empty($matches['param'])) {
			return new ParameterReference($matches['param']);
		}
		elseif( ! empty($matches['module'])) {
			return new ModuleReference($matches['module'], static::parseReference($matches['data']));
		}
		else {
			return new ServiceReference($matches['srv']);
		}
	}
}
