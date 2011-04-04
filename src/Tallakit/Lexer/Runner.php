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
 * run over tokens from lexer
 */
class Runner extends Decorator
{
	/** @var array store for parsed tokens */
	protected $tokens;
	/** @var int position, 0 is start (no token), 1 is the first token */
	private $position_=0;
	public function nextToken() {
		$count = count($this->tokens);
		$count===$this->position_
			&& $this->tokens[] = parent::nextToken()
			;
		return $this->tokens[$this->position_++];
	}
	public function currentToken() {
		$index = $this->position_;
		// at the very start, get a token first.
		if (0 === $index-- && 0 === count($this->tokens))
			$this->nextToken() && $index=0;
		return $this->tokens[$index];
	}
	public function eof() {
		return (bool) parent::eof() && $this->position_===count($this->tokens);
	}
	private function count() {
		return count($this->tokens);
	}
	public function position() {
		return $this->position_;
	}
	public function setPosition($position) {
		if ($position < 1) 
			throw new \InvalidArgumentException(sprintf('Position (#%d) must be greater 0.', $position));
		$count = count($this->tokens);
		while($count-- < $position)
			$this->nextToken()
			;
		$this->position_ = $position;
	}
	public function move($number) {
		$number = (int) $number;
		$position = $this->position_ + $number;
		$this->setPosition($position);
	}
	/**
	 * @param int $number of tokens
	 */
	public function readAhead($number) {
		$number=(int)$number;
		if ($number<0)
			throw new \InvalidArgumentException(sprintf('Can not read ahead a negative number of tokens (#%d).',$number));
		$bookmark = $this->position_;
		$count = count($this->tokens);
		$total = $bookmark+$number;
		$diff = $total-$count;
		if($diff>0)
			while($diff--&&!$this->eof())
				$this->nextToken()
		;
	}
}
