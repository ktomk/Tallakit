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

use \Tallakit\LexerRegex;
use \Tallakit\LexerRunner;

require_once(__DIR__ . '/../../src/Tallakit.php');

class DocblockParseException extends Exception{};

/**
 * Docblock Value Object
 */
class Docblock extends StdClass {}

/**
 * Example Docblock Parser
 */
class DocblockParser {
	private $regexLex = array(
		'[{][{tagchar}][}]' => 'escape_at', 
		'[{]{tag}[}]' => 'inline_tag', 
		'[{]{tag}(?={s})' => 'inline_start', 
		'[}]{1,2}' => 'inline_end',  
		'{tagchar}-?{nmstart}+{s}?' => 'tag',
		'[@\\\\]' => 'tagchar', 
		
		'{nmstart}+' => 'word',
		'[.,;:!?]' => 'punct', 
		
		'[a-z]' => 'nmstart', 
		'[a-z0-9-]' => 'nmchar', 
		'[*]' => 'asterisk', 
		'(?:{nl}|{s})' => 'ws', 
		'(?:\n|\r\n|\r|\f)' => 'nl', 
		'[ \t]' => 's', 
		
		// ------ TOKENS: ------ //
		
		'^/\*\*' => 'CM_START',
		'\*/' => 'CM_END',
		'(?<=[\n\r\f]){s}*[*]+(?![/])[ ]?' => 'CM_LSTART', 
		'{tag}(?={ws})' => 'TAG', 
		'{inline_start}' => 'IN_START',
		'{inline_tag}' => 'IN_TAG', 	
		'{inline_end}' => 'IN_END',
		'{escape_at}' => 'ESC_TAGCHAR',
		'{asterisk}' => 'ASTERISK+', 
		'{nl}' => 'NL', 
		'.' => 'C+', // dot lex last - catch all non-matching chars
	);
	/**
	 * @var LexerRunner
	 */
	private $lexer;
	/**
	 * @var Docblock
	 */
	private $docblock;
	public function __construct($buffer) {
		// compose lexer object
		$lexer = new LexerRegex($buffer);
		$lexer->setLex($this->regexLex);
		$runner = new LexerRunner($lexer);
		$this->lexer = $runner;
	}
	private function errorToken($expected=null) {
		list($name, $value) = $this->lexer->currentToken();
		$message = sprintf('Unexpected token <%s>', $name);
		if('C'===$name)
			$message = sprintf('Unexpected character "%s"', $value[0]);
		empty($expected) || $message .= sprintf(', expected token <%s>', $expected);
		return $message;
	}
	private function error($message) {
		list(,,$line,$col) = $this->lexer->currentToken();
		$message = sprintf('%s on line #%d at col #%d.', $message, $line, $col);
		throw new DocblockParseException($message);
	}
	private function parseOneToken($name) {
		list($currentName) = $this->lexer->nextToken();		
		if($name !== $currentName) {
			$this->error($this->errorToken($name));
			return 0;
		}
		return 1;
	}
	/**
	 *  docblock = <CM_START>{inner}<CM_END>
	 */
	private function parseDocblock() {		
		$this->docblock = new Docblock();		
		$r = 1;
		$r && $r = $this->parseOneToken('CM_START');
		$r && $r = $this->parseInner();
		$r && $r = $this->parseOneToken('CM_END');		
		if (!$r) return;
		return $this->docblock;
	}
	public function parse() {
		// main parse routine
		return $this->parseDocblock();
	}
	public function parseTag() {
		$tagname = '';
		$tagvalue = '';
		while($token = $this->lexer->nextToken())
		{
			list($name,$value,$line,$col) = $token;
			
			if('CM_END' === $name) break;
			if('CM_LSTART' === $name) continue;
			if('TAG' === $name) {
				if (strlen($tagname))
					break;
				$tagname = $value;
				continue;
			}			
			$name === 'NL' && $value = "\n"; // normalize NL
			$tagvalue .= $value;
		}
		$this->lexer->move(-1);
		$this->docblock->tags[] = array($tagname, trim($tagvalue));
		return 1;
	}
	/**
	 * inner =  ({shortdesc} | {longdesc}) 
	 * 			| {tag} {tagvalue} 
	 * 			| {inlinetag} {tagvalue}
	 * 
	 * @todo parser for tag
	 * @todo parser for inline tag
	 */
	private function parseInner() {
		$shortdesc = '';
		$longdesc  = '';
		while(!$this->lexer->eof() && ($token = $this->lexer->nextToken()))
		{
			list($name,$value,$line,$col) = $token;
			if('CM_END' === $name) break;
			if('CM_LSTART' === $name) continue;
			if('TAG' === $name) {
				$this->lexer->move(-1);
				$r = $this->parseTag();
				continue;
			}
			
			if(empty($shortdesc)) {
				$name!=='NL' && $shortdesc=trim($value);
				continue;
			}
			
			$name==='NL' && $value="\n"; // normalize NL
			$longdesc.=$value;
		}
		if($name!=='CM_END') {
			$this->error('Unexpected EOF');
		}
		$this->lexer->move(-1);
		$this->docblock->title = $shortdesc;
		$this->docblock->desc = trim($longdesc);
		return 1;
	}
}

$text = '/**
 * short
 *  
 * long description over multiple
 * lines is running down low.
 *
 * @author Mary Margin
 */';

$parser = new DocblockParser($text);
try {
	$docblock = $parser->parse();
	printf("PARSING DONE:\n");
	print_r($docblock);
} catch(DocblockParseException $e) {
	printf("PARSE ERROR: %s\n", $e->getMessage());
}
