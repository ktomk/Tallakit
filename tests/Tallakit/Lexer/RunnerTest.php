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

require_once(__DIR__.'/../../TestCase.php');

/**
 * Test class for Runner.
 */
class RunnerTest extends \Tallakit\TestCase
{
    /**
     * @var Runner
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @return Runner
     */
    private function getTestRunner($buffer = '1 + 2 -3 + 55 - 88     	+ 33') {
        $lex = array(
            '[0-9]+' => 'NUMBER',
            '[+-]' => 'OPERATOR',
            '[ \t\r\n]+' => 'SP',
            '.' => 'C+',
        );
        $lexer = new Regex($buffer);
        $lexer->setLex($lex);
        $runner = new Runner($lexer);
        return $runner;
    }

    public function testCurrent() {
        $runner = $this->getTestRunner('');
        $expected = false;
        $actual = $runner->currentToken();
        $this->assertSame($expected, $actual);
    }

    /**
     * test currentToken and the two related function position()
     */
    public function testCurrentAndPosition()
    {
        $runner = $this->getTestRunner();
        $expected = array('NUMBER', '1', 0, 0);
		$actual = $runner->currentToken();
		$this->assertSame($expected, $actual);

		$expected = 1;
		$actual = $runner->position();
		$this->assertSame($expected, $actual);
    }

    /**
     */
    public function testEof()
    {
		$runner = $this->getTestRunner();

		$expected = false;
		$actual = $runner->eof();
		$this->assertSame($expected, $actual, 'EOF on start');

		$expected = false;
		$token = $runner->nextToken();
		$actual = $runner->eof();
		$this->assertSame($expected, $actual, 'EOF on first');

		$expected = false;
		$tokens = $runner->lex();
		$actual = $runner->eof();
		$this->assertSame($expected, $actual, 'EOF after lex()');

		$expected = true;
		$runner->setPosition(20);
		$actual = $runner->eof();
		$this->assertSame($expected, $actual, 'EOF at EOF');
    }

	public function testSetPosition()
	{
		$runner = $this->getTestRunner();
		$position = 3;
		$expected = $position;
		$actual = $runner->setPosition($position);
		$this->assertSame($expected, $actual, 'Position set to existing position.');

		$position = 50;
		$expected = 20;
		$actual = $runner->setPosition($position);
		$this->assertSame($expected, $actual, 'Position set to in-existing position (too large).');

		$this->addToAssertionCount(1);
		try {
			$runner->setPosition(0);
		} catch (InvalidArgumentException $e) {
			return;
		}
		$this->fail('An expected Exception has not been thrown.');
    }

	public function testReadAhead() {
		$runner = $this->getTestRunner();
		$expected = 20;
		$actual = $runner->readAhead(40);
		$this->assertSame($expected, $actual, 'Read Ahead 40 tokens, while 20 will be available.');

        $expected = 19;
        $runner->setPosition(1);
        $actual = $runner->readAhead(0);
        $this->assertSame($expected, $actual, 'Read Ahead 0 tokens, while 19 are already ahead.');

        $this->addToAssertionCount(1);
        try {
            $runner->readAhead(-1);
        } catch (InvalidArgumentException $e) {
            return;
        }
		$this->fail('An expected Exception has not been thrown.');
	}

}
?>