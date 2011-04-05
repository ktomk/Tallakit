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

/**
 * Base Lexer Class
 * 
 * FIXME I have the strong feeling that this class is strictly spoken 
 *       abstract, see self::match() which is undefined
 */
class Base implements Face
{
	/** 
	 * @var string
	 */
	protected $buffer;
	/** name of all tokens the lexer can lex into 
	 * @var array */
	protected $tokens;
	/**
	 * @var int
	 */
	protected $offset, $count, $col, $line;
	/** 
	 * @var array current token 
	 */
	private $current;
	/** 
	 * @param string $buffer 
	 */
	public function __construct($buffer) {
		$this->buffer = $buffer;
		$this->reset();
	}
	public function reset() {
		$this->offset = 0;
		$this->count = 0;
		$this->col = 0;
		$this->line = 1;
	}
	/**
	 * @return array list($offset, $line, $col)
	 */
	public function status() {
		return array($this->offset, $this->line, $this->col);
	}
	/**
	 * @return array input buffer (from current offset) in form of an array of tokens
	 */
	public function lex() {
		while(!$this->eof()) {
			$token = $this->nextToken();
			list($type,$value) = $token;
			if(null===$type) {
				throw new LexerParseErrorException(vsprintf('No Token at offset #%d in line #%d col #%d.', $this->status()));
			}
			if(empty($type)) {
				throw new LexerParseErrorException(vsprintf('Unknown Token "%s"/#%d at offset #%d in line #%d col #%d.', array_merge(array($type, $type), $this->status())));
			}
			if (!strlen($value)) {
				throw new LexerParseErrorException(vsprintf('Empty Token <%s> at offset #%d in line #%d col #%d.', array_merge(array($type), $this->status())));
			}
			$tokens[] = $token;
		};
		return $tokens;
	}
	protected function nextMatch() {
		list($match, $consume) = $this->next();
		// growing token
		if ($match&&'+'===substr($match,-1)) {
			$offset=$this->offset;
			$this->offset+=$consume;
			while($len=$this->aheadIs($match)) {
				$this->offset+=$len;
				$consume+=$len;
			};
			$this->offset=$offset;
		}
		return array($match, $consume);
	}
	protected function next() {
		$match = null;
		$consume = 0;	
		foreach($this->tokens as $token) {
			($len=$this->match($token))
			&& $len && $len>$consume
			&& ($consume=$len)
			&& ($match=$token);
		}
		return array($match, $consume);
	}
	protected function aheadIs($token) {
		list($match, $consume) = $this->next();
		return ($match===$token) ? $consume : 0;
	}
	/**
	 * @return array($token, $value, $line, $col);
	 */
	private function addToken($token, $consume) {		
		$value = substr($this->buffer, $this->offset, $consume);
		$this->offset+=$consume;
		$token = $this->normalizeToken($token);
		$data = array($token, $value, $this->line, $this->col);
		
		$cols=$this->col+$consume;
		($lines=substr_count($value, "\n"))
			&&$cols=strlen($value)-strrpos($value, "\n")-1
			;
		$this->line+=$lines;
		$this->col=$cols;
		return $data;
	}
	public function nextToken() {
		if ($this->eof()) {
			return false;
		}
		list($match, $consume) = $this->nextMatch();
		$token = $this->addToken($match, $consume);
		$this->current = $token;
		return $token;
	}
	public function currentToken() {
		return $this->current;
	}
	private function normalizeToken($token) {
		return rtrim($token, '+');
	}
	public function getTokenNames() {
		return array_map(array($this,'normalizeToken'), $this->tokens);
	}
	public function hasTokenName($name) {
		$tokensAsKeys = array_flip($this->getTokenNames());
		return array_key_exists($tokensAsKeys, $name);		
	}
	/** 
	 * @return bool
	 */
	public function eof() {
		return (bool) (strlen($this->buffer)===$this->offset);
	}
}
