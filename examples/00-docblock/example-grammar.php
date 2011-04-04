<?php
/**
 * Docblock Lexer and Parser
 * 
 * An Example of Tallakit - Lexer and Parser Library for PHP
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
 * @package Examples
 */

require_once(__DIR__ . '/docblock.php');

$text = '/**
 * short description
 *  
 * long description over multiple
 * lines is running down low.
 *
 * @author Mary Margin
 */';

// use docblock parser to get concrete 
// lexer needed for grammar parser
// FIXME create lexer here to show token syntax
$parser = new DocblockParser($text);
$runner = $parser->getLexer();
$lexerConcrete = $runner->lexer();

// the fun starts
class DocblockGrammarParser extends Tallakit\Parser\Grammar {
	/**
	 * Grammar
	 * 
	 * @var array products (as in BNF) 
	 * 
	 * Syntax:
	 * 
	 *   <terminal symbol>  :  token from lexer
	 *   {product} .......  :  product
	 * 
	 * this is not standard pcre syntax! supported is basic
	 * class "[]" and grouping "()" and quantifiers "?*+{min,max}". 
	 * whitespaces are ignored.
	 * 
	 *  /!\  DO NOT USE:
	 *       - backreferences
	 *       - lookahead/behind
	 */
	protected $grammar = array(
		'docblock'    =>  '{blockstart}{blockinner}{blockend}', 
		'blockstart'  =>  '<CM_START>', 
		'blockend'    =>  '<CM_END>', 
		'blockinner'  =>  '({line})*',
		'line'        =>  '{char}*<NL>',
		'char'        =>  '[^<NL>]',
	);
}

$validator = new DocblockGrammarParser($lexerConcrete);
$result = $validator->parse();
var_dump($result);
