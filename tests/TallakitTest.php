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

require_once(__DIR__.'/TestCase.php');

/**
 * Test class for Tallakit.
 */
class TallakitTest extends \Tallakit\TestCase
{
	/**
	 * @var Tallakit
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		\Tallakit::unregisterAutoload();
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
	}

	/**
	 * NOTE: if run in a setup with global auto-loaders, then it will fail.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testAutoloadIsDisabled() {
		$className = 'Tallakit\LexerInterface';
		$expected = false;
		$actual = class_exists($className, $autoload = true);
		$this->assertSame($expected, $actual, 'Autoloader is not disabled but must be.');
	}

	public function testFileNameOfClassName()
	{
		$tests = array(
			array('Tallakit\LexerInterface', 'Tallakit/LexerInterface.php'),
		);
		foreach($tests as $test) {
			list($className, $expectedFileName) = $test;
			$expectedFileName = str_replace('/', DIRECTORY_SEPARATOR, $expectedFileName);
			$fileName = \Tallakit::fileNameOfClassName($className);
			$this->assertSame($expectedFileName, $fileName);
		}
	}

	public function testFileNameOfClassNameExceptions() {
		try {
			\Tallakit::fileNameOfClassName(null);
		} catch(\InvalidArgumentException $e) {
			$this->addToAssertionCount(1);
			try {
				\Tallakit::fileNameOfClassName('WhatAFake\ofMake\\');
			} catch (\InvalidArgumentException $e) {
				$this->addToAssertionCount(1);
				return;
			}
		}
		$this->fail('An expected Exception has not been raised.');
	}

	public function testRegisterAutloloader()
	{
		\Tallakit::registerAutoload();
		$result = \Tallakit::autoloadRegistered();
		$this->assertSame(TRUE, $result);

		\Tallakit::unregisterAutoload();
		$result = \Tallakit::autoloadRegistered();
		$this->assertSame(FALSE, $result);
	}

	public function testLoadClass()
	{
		$className = 'JagTalaSvenskaJaJa';
		$result = \Tallakit::loadClass($className);
		$this->assertFalse($result);

		$className = 'Tallakit\Lexer\\Face';
		$result = \Tallakit::loadClass($className);
		$this->assertTrue($result, 'Could not load interface - result not true.');

		$className = 'Tallakit\\Exception';
		$result = \Tallakit::loadClass($className);
		$this->assertTrue($result, 'Could not load class - result not true.');

		$testClass = new $className();
		$this->assertInstanceOf($className, $testClass);
	}

	/**
	 * NOTE: this test is lame, use process-isolation and
	 *       always check \Tallakit::loadLibrary().
	 */
	public function testLoadLibrary()
	{
		$expected = 2;
		$actual = \Tallakit::loadLibrary();
		$this->assertSame($expected, $actual, sprintf('loadLibrary failed to load %d classes/interfaces (result was: %d).', $expected, $actual));
	}
}
