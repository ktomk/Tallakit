<?php
/**
 * Tallakit - Lexer and Parser Library for PHP
 *
 * Copyright (C) 2010-2011 Tom Klingenberg, some rights reserved
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program in a file called COPYING. If not, see
 * <http://www.gnu.org/licenses/> and please report back to the original
 * author.
 *
 * @author Tom Klingenberg <http://lastflood.com/>
 * @version 0.0.1
 * @package Tallakit
 */

Namespace Tallakit;

/**
 * Macros Utility Class
 * 
 * Macros are simple name => code hashes in which code can contain 
 * {named} macros.
 * 
 * Macros::expand() will expand those hashes. Circular referefences
 * are detected.
 */
class Macros {
	private $hash;
	private $hashShadow;
	/**
	 * @param array $hash
	 * @return array hash with all macros expanded
	 */
	public static function expand(array $hash) {
		$class = __CLASS__;
		$macros = new $class($hash);
		return $macros->expanded();
	}
	/**
	 * @param array $hash key: macro-name, value: macro-code containing macros
	 */
	public function __construct(array $hash) {
		$this->hash = $hash;
		$this->hashShadow = null;
	}	
	public function expanded() {
		$hash = $this->hash;
		if (empty($this->hashShadow)) {
			$this->initShadow();
			$this->expandCodes();
		}
		return $this->hashShadow;
	}
	private function initShadow() {
		$this->hashShadow = $this->hash;
	}
	private function expandCodes() {
		$codes = $this->hash;
		$names = array_reverse(array_keys($codes));
		foreach($names as $name) {
			$code = $codes[$name];
			$this->expandCode($code, $name);
		}
	}
	public function expandCode($code,$context=null) {
		static $stack=array();
		$pattern='(\{([a-z]+(?:_[a-z]+)*)\})';
		
		if(!preg_match($pattern,$code))
			return $code;

		$inset=str_repeat('    ',count($stack));
		// @debug printf("%sexpand('%s',{%s})\n",$inset,$code,$context);
		if(array_key_exists($context, $stack)) {
			trigger_error(
				sprintf('Circular reference detected in macros: {%s}->{%s}.', implode('}->{', array_keys($stack)),$context)
				, E_USER_WARNING
			);
			// @debug printf("%s    {%s} already in stack, quitting.\n",$inset,$context);
			return $code;
		}
		$stack[$context]=1;
		// @debug printf("%s    {%s} expanding...\n",$inset,$context);
		
		$pattern='(\{([a-z]+(?:_[a-z]+)*)\})';
		$that=$this;
		$hash=$this->hash;
		$replace = function($matches)use($that,$hash,$code,$context,$pattern) {
			list($macro,$name)=$matches;
			if(empty($hash[$name])) {
				throw new MacrosException(sprintf('Undefined macro %s in %s => "%s".', $macro, $context, $code));
			}
			$macro_code=$hash[$name];
			preg_match($pattern,$macro_code)
			&& $macro_code=$that->expandCode($macro_code,$name);
			return $macro_code;
		};
		$expanded = preg_replace_callback($pattern, $replace, $code);
		if (NULL===$expanded) {
			// @codeCoverageIgnoreStart
			throw new MacrosException(sprintf('Regex "%s" failed.', $pattern));
			// @codeCoverageIgnoreEnd
		}
		$context
		&& $this->hashShadow[$context]=$expanded;

		// @debug printf("%s    {%s} expanded to '%s'.\n",$inset,$context,$expanded);
		unset($stack[$context]);
		return $expanded;
	}
}
