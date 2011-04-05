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
Use \InvalidArgumentException;

/**
 * Runner Lexer Decorator
 *
 * Decorates any Lexer with position related functions
 * on the imaginable token-stream:
 *
 *   setPosition($position) - absolute position
 *   move(+/- $number) - relative movement
 *
 * Additionally offers Read-Ahead support:
 *
 *    readAhead($number) - ensure you have the possible maximum (before EOF) number of tokens
 *
 * TODO readAhead() is a candidate to be moved.
 */
class Runner extends Decorator
{
	/**
	 * @var array store for parsed tokens
	 */
	protected $tokens;
	/**
	 * @var int position, 0 is start (no token, initialized only), 1 is the first token
	 */
	private $position_=0;
	private $length_;
	/**
	 * @see Tallakit\Lexer\Decorator::nextToken()
	 * @return array|false
	 */
	public function nextToken() {
		$posConsumesTokens = $this->position_===count($this->tokens);
		$nextToken = $posConsumesTokens ? parent::nextToken() : $this->tokens[$this->position_++];
		($posConsumesTokens && $nextToken)
			&& $this->tokens[$this->position_++] = $nextToken
			;
		return $nextToken;
	}
	/**
	 * @see Tallakit\Lexer\Face::nextToken()
	 * @return array|false
	 */
	public function currentToken() {
		0 === $this->position_
			&& $this->nextToken()
			;
		$index = $this->position_;
		return $index ? $this->tokens[--$index] : false;
	}
	public function eof() {
		if (!parent::eof())
			return false
			;
		$posConsumesTokens = $this->position_===count($this->tokens);
		if (!$posConsumesTokens)
			return false
			;
		$nextToken = parent::nextToken();
		return empty($nextToken);
	}
	public function lex() {
		$tokens = parent::lex();
		return $this->tokens = $tokens;
	}
	public function position() {
		return $this->position_;
	}
	/**
	 * @param int $position
	 * @return int new position
	 * @throws InvalidArgumentException
	 */
	public function setPosition($position) {
		if ($position < 1) 
			throw new InvalidArgumentException(sprintf('Position (#%d) must be greater 0.', $position));

		$count = count($this->tokens);
		while($count < $position && $this->nextToken())
			$count++;
		return $this->position_ = min($position, $count);
	}
	/**
	 * @param int $number
	 */
	public function move($number) {
		$number = (int) $number;
		$position = $this->position_ + $number;
		$this->setPosition($position);
	}
	/**
	 * @param int $number of tokens
	 * @param int number of tokens ahead
	 */
	public function readAhead($number) {
		$number=(int)$number;
		if ($number<0) {
			throw new InvalidArgumentException(sprintf('Can not read ahead a negative number of tokens (#%d).',$number));
		}
		$bookmark = $this->position_;
		$count = count($this->tokens);
		$total = $bookmark+$number;
		if(0 < $diff = $total-$count)
			while($diff-- && $this->nextToken())
			;
		return count($this->tokens) - $bookmark;
	}
}
