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

interface Face {
	/**
	 * @return array|false Token, e.g. list($name, $value, $line, $col) or false if no more next token.
	 */
	public function nextToken();
	/**
	 * @return array Token names.
	 */
	public function getTokenNames();
	/**
	 * @return bool
	 */
	public function eof();
	/**
	 * run tokenization
	 * @return array tokens
	 */
	public function lex();
}
