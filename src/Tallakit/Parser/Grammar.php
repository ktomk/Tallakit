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
 * @package Tests
 */

Namespace Tallakit\Parser;
Use Tallakit\Lexer\Face;
Use Tallakit\Macros;

/**
 * Base Grammar Class
 * 
 * Can map regular expressions on token-stream. See $rom.
 * 
 * It's promising but experimental. Take care.
 */
class Grammar {
	/** @var LexerInterface */
	protected $lexer;
	protected $tokens;
	protected $map;
	protected $dns;
	protected $grammar;
	protected $grammarExpanded;
	public function __construct(Face $lexer) {
		$this->lexer=$lexer;
		$this->init();
	}
	private function init() {		
		$this->mapInit();
		$this->tokensInit();
		$this->dnsInit();
		$this->grammarExpand();
		$this->grammarProcess();
	}
	private function tokensInit() {
		$this->tokens=$this->lexer->lex();
	}
	private function mapInit() {
		$tokens=$this->lexer->getTokenNames();
		$count=count($tokens);
		// experimenting with a single byte map, fixed-sized token req.
		// taking only easily readable bytes for a start. 
		$rom = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if (strlen($rom)<=$count)
			throw new Exception(sprintf('Number of Tokens (#%d) too high.', $count));
		$keys=array_map(function($index)use($rom) {
			return $rom[$index];
		}, range(0,$count-1));
		$map=array_combine($tokens, $keys);
		$this->map=$map;
	}
	private function dnsInit() {
		$tokens=$this->tokens;
		$map=$this->map;
		$dns=array_map(function($token)use($map){
			list($type)=$token;
			return $map[$type];
		}, $tokens);
		$dns=implode('', $dns);
		$this->dns=$dns;
	}
	private function grammarExpand() {
		$map=$this->map;
		$grammar=$this->grammar;
		foreach($grammar as $term=>$product) {
			$grammar[$term]=$this->expandToken($product,$map);
		}
		$this->grammar=$grammar;
	}
	private function expandToken($grammar,$map) {
		$pattern = '([ ]*[<]([A-Z]+(?:_[A-Z]+)*)[>][ ]*)';
		$replace = function($matches)use($map,$grammar) {
			list($token,$key)=$matches;
			if(empty($map[$key]))
				throw new Exception(sprintf('Unknown Token %s in grammar "%s".', $token, $grammar));
			return $map[$key];
		};
		$translated = preg_replace_callback($pattern, $replace, $grammar);
		$translated = preg_replace('[ ]', '', $translated);
		return $translated;
	}
	/**
	 * Post-Process Grammar to create the shadow
	 */
	private function grammarProcess() {
		$this->grammarExpanded = Macros::expand($this->grammar);
	}
	public function parse() {	
		$product = reset(array_keys($this->grammar));
		return $this->match($product);	
	}
	private function match($productName, $start = 0, $length = null) {
		static $level=0;		
		list($productName, $start, $length) = $this->matchParams($productName, $start, $length);
		
		$product = $this->grammar[$productName];
		$product = $this->nonmatching($product);
		list($pattern,$meta) = $this->subexpand($product);
		$pattern = "(^$pattern$)";
		$subject = substr($this->dns, $start, $length);

		$indent = str_repeat('|   ', $level);
		strlen($indent)&&$indent[0]=' '; ($subject2=$subject)&&strlen($subject2)>20&&$subject2=substr($subject2,0,20).'... ('.strlen($subject).')';
		
		$verbose = true;
		if ($verbose) {
			printf("%s|    Tokens : %s (start:%d)\n",$indent , $subject2, $start);
			printf("%s|    Product: %s / %s\n",$indent, $product, $this->grammarExpanded[$productName]);
			printf("%s|    Pattern: %s [Groups: %s]\n",$indent, $pattern, implode(', ',$meta));		
		}
		
		$result = preg_match_all($pattern, $subject, $matches,  PREG_OFFSET_CAPTURE);
		if (false===$result)
			throw new Exception(sprintf('Regex Failed "%s"', $pattern));
		if (0===$result)
			return false;
		
		var_dump($matches);
		
		$res2 = preg_replace_callback($pattern, function($matches) {
			static $count=0;
			$count++;
			echo 'Callback ', $count, "\n";
			var_dump($matches);
			return '{test}';
		}, $subject);
		
		var_dump($res2);
		die();
		
		die();
				
		$total=array_reduce(array_map('count',$matches),function($v, $w){return($v+$w);}, 0);
		
		list($fullsize,$fullstart)=$matches[0][0];
		$fullsize=strlen($fullsize)-strlen($subject);
		if (0!=$fullsize||0!=$fullstart) {
			throw new Exception('Not matching full powa!');
		}
		// var_dump($fullsize, $fullstart, $matches[0][0][0]); die();
		
		if ($verbose) {
			printf("%s|    Found %s/%d:\n",$indent , $result, $total);
			$i=0;
			foreach($matches as $match) {				
				$count = count($match);				
				foreach($match as $key => $v) {
					$match[$key] = '"'.implode('", ', $v);
				}
				$match=implode('; ', $match);
				printf("%s|    %d) #%d: %s\n",$indent , $i, $count, $match);
				$i++;
			}
		}	
		array_shift($matches);
		$total--;
		
		0===$level&&printf("%s{%s}\n", $indent, $productName);
		
		$level++;
		$indent = str_repeat('|   ', $level);
		strlen($indent)&&$indent[0]=' ';
		$tree = '+---';
		foreach($meta as $term=>$subproduct) {
			foreach($matches[$term] as $count=>$match) {
				$total===$count&&$tree[0]='`';
				list($subtokens,$substart)=$match;
				$substart+=$start;
				$sublength=strlen($subtokens);
				printf("%s%s{%s} %d, %d\n", $indent, $tree, $subproduct, $substart, $sublength);
				$result = $this->match($subproduct, $substart, $sublength);
				if (false===$result)
					throw new Exception('Illegal Parser State (product match should never be false if that exact product matched in parent product.)');			
			}
		}
		$level--;		
		return true;
	}
	private function matchParams($productName, $start, $length) {
		$token = $this->dns;
		$strlen = strlen($token);		
		$start = (int) $start;
		null===$length && $length=$strlen;		
		$length = (int) $length;
		if (abs($start)>$strlen)
			throw new \InvalidArgumentException(sprintf('Invalid start (%d). Subject length is: %d.', $start, $strlen));
		if (!array_key_exists($productName, $this->grammar))
			throw new \InvalidArgumentException(sprintf('Invalid product "%s".', $productName));
		$subject = substr($this->dns, $start, $length);
		if (false===$subject||null===$subject)
			throw new \InvalidArgumentException(sprintf('Invalid start/length (%d/%d) combination. Subject length is: %d', $start, $length, $strlen));
		return array($productName, $start, $length);
	}
	private function subexpand($product) {
		$product = $this->nonmatching($product);
		$pattern = '(\{([a-z]+(?:_[a-z]+)*)\})';
		$flags = PREG_OFFSET_CAPTURE;
		$meta=array();
		while($result = preg_match($pattern, $product, $matches, $flags)) {
			$meta[] = $name = $matches[1][0];
			$start = $matches[0][1];
			$length = strlen($matches[0][0]);			
			$expand = $this->grammarExpanded[$name];
			$expand = $this->nonmatching($expand);
			$product = substr_replace($product, "($expand)", $start, $length);
		};
		if (false === $result) {
			throw new Exception('Subexpand Regular Expression failed.');
		}
		return array($product,$meta);
	}
	private function nonmatching($regex)  {
		$pattern = '((?<!\\\\)\\((?!\?:))';
		$result = preg_replace($pattern, '\\0?:', $regex);
		if (false===$result) {
			throw new Exception('Regex failed.');
		}
		return $result;		
	}
}
