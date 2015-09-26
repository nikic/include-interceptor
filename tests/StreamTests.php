<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Interceptor\Tests;

use Icewind\Interceptor\Interceptor;
use Icewind\Interceptor\Stream;

class StreamTests extends TestCase {
	protected function fopen($source, $mode) {
		$interceptor = new Interceptor();
		Stream::setInterceptor($interceptor);
		$interceptor->wrap();
		$wrapped = fopen($source, $mode);
		$interceptor->unwrap();
		return $wrapped;
	}

	protected function opendir($source) {
		$interceptor = new Interceptor();
		Stream::setInterceptor($interceptor);
		$interceptor->wrap();
		$wrapped = opendir($source);
		$interceptor->unwrap();
		return $wrapped;
	}

	public function testRead() {
		$file = $this->tempNam();
		$source = fopen($file, 'w');
		fwrite($source, 'foobar');
		fclose($source);
		$wrapped = $this->fopen($file, 'r');
		$this->assertEquals('foo', fread($wrapped, 3));
		$this->assertEquals('bar', fread($wrapped, 3));
		$this->assertEquals('', fread($wrapped, 3));
	}

	public function testWrite() {
		$file = $this->tempNam();
		$wrapped = $this->fopen($file, 'w');
		$this->assertEquals(6, fwrite($wrapped, 'foobar'));
		fclose($wrapped);
		$source = fopen($file, 'r');
		$this->assertEquals('foobar', stream_get_contents($source));
	}

	public function testSeekTell() {
		$file = $this->tempNam();
		$source = fopen($file, 'w');
		fwrite($source, 'foobar');
		fclose($source);
		$wrapped = $this->fopen($file, 'r');
		$this->assertEquals(0, ftell($wrapped));
		fseek($wrapped, 2);
		$this->assertEquals(2, ftell($wrapped));
		fseek($wrapped, 2, SEEK_CUR);
		$this->assertEquals(4, ftell($wrapped));
		fseek($wrapped, -1, SEEK_END);
		$this->assertEquals(5, ftell($wrapped));
	}

	public function testStat() {
		$wrapped = $this->fopen(__FILE__, 'r');
		$this->assertEquals(stat(__FILE__), fstat($wrapped));
	}

	public function testTruncate() {
		$file = $this->tempNam();
		$source = fopen($file, 'w');
		fwrite($source, 'foobar');
		fclose($source);
		$wrapped = $this->fopen($file, 'r+');
		ftruncate($wrapped, 2);
		$this->assertEquals('fo', fread($wrapped, 10));
	}

	public function testLock() {
		$file = $this->tempNam();
		$wrapped = $this->fopen($file, 'r+');
		if (!flock($wrapped, LOCK_EX)) {
			$this->fail('Unable to acquire lock');
		}
	}

	public function testStreamOptions() {
		$file = $this->tempNam();
		$wrapped = $this->fopen($file, 'r+');
		stream_set_blocking($wrapped, 0);
		stream_set_timeout($wrapped, 1, 0);
		stream_set_write_buffer($wrapped, 0);
	}

	public function testReadDir() {
		$source = opendir(__DIR__);
		$content = [];
		while (($name = readdir($source)) !== false) {
			$content[] = $name;
		}
		closedir($source);
		$wrapped = $this->opendir(__DIR__);
		$wrappedContent = [];
		while (($name = readdir($wrapped)) !== false) {
			$wrappedContent[] = $name;
		}
		$this->assertEquals($content, $wrappedContent);
	}

	public function testRewindDir() {
		$source = opendir(__DIR__);
		$content = [];
		while (($name = readdir($source)) !== false) {
			$content[] = $name;
		}
		closedir($source);
		$wrapped = $this->opendir(__DIR__);
		$this->assertEquals($content[0], readdir($wrapped));
		$this->assertEquals($content[1], readdir($wrapped));
		$this->assertEquals($content[2], readdir($wrapped));
		rewinddir($wrapped);
		$this->assertEquals($content[0], readdir($wrapped));
	}
}
