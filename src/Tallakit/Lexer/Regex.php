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

Namespace Tallakit\Lexer;
Use Tallakit\Macros;

/**
 * Regex Lexer Class
 */
class Regex extends Base {
	/** 
	 * @var array regex lex rules
	 */
	protected $regexLex = array();
	private $regex;
	public function __construct($buffer) {
		parent::__construct($buffer);
		$this->setLex($this->regexLex);
	}
	private function validateLexNames($lex) {
		$allNames = array_values($lex);
		$pattern = '(^([a-z]+(?:_[a-z]+)*|[A-Z]+(?:_[A-Z]+)*[+]?)$)';
		foreach($allNames as $name) {
			$r = preg_match($pattern, $name);
			if (false===$r)
				throw new Exception('Regex failed.');
			if (0===$r)
				throw new \InvalidArgumentException(sprintf('Invalid macro or token name "%s".', $name));
		}		
		$diffNames = array_keys(array_flip($lex));
		$dupNames = array_diff($allNames, $diffNames);
		$dupCount = count($dupNames);
		if ($dupCount) {
			throw new \InvalidArgumentException(sprintf('%d duplicate macro or token names in lex.', $dupCount, implode(', ', $dupNames)));
		}
	}
	private function validateRegexes($regexes) {
		foreach($regexes as $name => $code) {
			$pattern = "({$code})";
			$r = preg_match($pattern, '');
			if ($r === false) {
				throw new \InvalidArgumentException(sprintf('Regular expression for "%s" failed. Expression was "%s".', $name, $code));
			}
		}
	}
	/**
	 * lex rules setter
	 * 
	 * @var array $lex regex lex notated
	 */
	public function setLex($lex) {
		$this->validateLexNames($lex);
		$lex = array_flip($lex);
		$regex = Macros::expand($lex);
		$this->validateRegexes($regex);
		$this->regex = $regex;
		$names = array_keys($lex);
		$tokens = array_filter($names, array($this, 'isToken'));
		$this->tokens = $tokens;
	}
	private function isToken($name) {
		return (bool) ($name===strtoupper($name));
	}
	private function tokenPattern($token) {
		if (!$this->isToken($token))
			throw new  \InvalidArgumentException(sprintf('Not a token <%s>.', $token));
		$regex = $this->regex;
		
		if (empty($regex[$token])) {
			var_dump($regex);
			throw new  \InvalidArgumentException(sprintf('Invalid token <%s>.', $token));
		}
		
		return $regex[$token];
	}
	protected function match($token) {
		$pattern = $this->tokenPattern($token);
		$return = 0;
		$regex = "($pattern)";
		$found = preg_match(
			$regex, $this->buffer, $match, PREG_OFFSET_CAPTURE, $this->offset
		);
		if (false === $found) {
			$error = preg_last_error();
			throw new \UnexpectedValueException(sprintf('Regular expression ("%s") failed (Error-Code: %d).', $pattern, $error));
		}
		$found
		 	&& isset($match[0][1])
		 	&& $match[0][1] === $this->offset
		 	&& $return = strlen($match[0][0])
		;
		return $return;		
	}
}
