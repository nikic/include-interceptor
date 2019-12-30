<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Nikic\IncludeInterceptor\Tests;

use Nikic\IncludeInterceptor\Interceptor;
use Nikic\IncludeInterceptor\Stream;

class StreamTests extends TestCase {
    protected function fopen($source, $mode) {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $interceptor->wrap();
        $wrapped = fopen($source, $mode);
        $interceptor->unwrap();
        return $wrapped;
    }

    protected function opendir($source) {
        $interceptor = $this->createDummyInterceptor();
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
        $unwrapped = fopen(__FILE__, 'r');
        $wrapped = $this->fopen(__FILE__, 'r');
        $this->assertEquals(fstat($unwrapped), fstat($wrapped));
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
        $this->assertTrue(flock($wrapped, LOCK_EX));
    }

    public function testStreamOptions() {
        $file = $this->tempNam();
        $wrapped = $this->fopen($file, 'r+');
        stream_set_blocking($wrapped, 0);
        stream_set_timeout($wrapped, 1, 0);
        stream_set_write_buffer($wrapped, 0);
        $this->expectNotToPerformAssertions();
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

    public function testUrlStat() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $expected = stat(__FILE__);
        $interceptor->wrap();
        $result = stat(__FILE__);
        $interceptor->unwrap();
        $this->assertEquals($expected, $result);
    }

    public function testMKDir() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file = $this->tempNam();
        unlink($file);
        $interceptor->wrap();
        mkdir($file);
        $interceptor->unwrap();
        $this->assertTrue(is_dir($file));
        rmdir($file);
    }

    public function testRMDir() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file = $this->tempNam();
        unlink($file);
        mkdir($file);
        $interceptor->wrap();
        rmdir($file);
        $interceptor->unwrap();
        $this->assertFalse(is_dir($file));
    }

    public function testRename() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file1 = $this->tempNam();
        $file2 = $this->tempNam();
        unlink($file2);
        $interceptor->wrap();
        rename($file1, $file2);
        $interceptor->unwrap();
        $this->assertFalse(is_file($file1));
        $this->assertTrue(is_file($file2));
    }

    public function testUnlink() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file = $this->tempNam();
        $interceptor->wrap();
        unlink($file);
        $interceptor->unwrap();
        $this->assertFalse(is_file($file));
    }

    public function testTouch() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file = $this->tempNam();
        $interceptor->wrap();
        $this->assertTrue(touch($file));
        $interceptor->unwrap();
    }

    public function testChmod() {
        $interceptor = $this->createDummyInterceptor();
        Stream::setInterceptor($interceptor);
        $file = $this->tempNam();
        $interceptor->wrap();
        $this->assertTrue(chmod($file, 0700));
        $interceptor->unwrap();
    }

    private function createDummyInterceptor(): Interceptor {
        return new Interceptor(function(string $path) {
            return null;
        });
    }
}
