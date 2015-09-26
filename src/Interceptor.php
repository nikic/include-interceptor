<?php
/**
 * @license    http://www.opensource.org/licenses/mit-license.html
 *
 * Based on https://github.com/antecedent/patchwork/blob/master/Patchwork.php
 */

namespace Icewind\Interceptor;

class Interceptor {
	private $protocols = ['file', 'phar'];
	private $whiteList = [];
	private $extensions = ['php', 'phar'];

	/**
	 * @var callable[]
	 */
	private $hooks = [];

	public function addWhiteList($path) {
		$this->whiteList[] = rtrim($path, '/');
	}

	public function shouldIntercept($path) {
		return $this->isValidExtension($path) && $this->isWhiteListed($path);
	}

	private function isValidExtension($path) {
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return in_array($extension, $this->extensions);
	}

	private function isWhiteListed($path) {
		foreach ($this->whiteList as $whiteList) {
			if ($this->inDirectory($whiteList, $path)) {
				return true;
			}
		}
		return false;
	}

	private function inDirectory($directory, $path) {
		return (substr($path, 0, strlen($directory) + 1) === $directory . '/');
	}

	public function addHook(callable $hook) {
		$this->hooks[] = $hook;
	}

	/**
	 * @param string $path
	 * @return resource
	 */
	public function intercept($path) {
		$code = file_get_contents($path);
		foreach ($this->hooks as $hook) {
			$result = $hook($code, $path);
			if (is_string($result)) {
				$code = $result;
			}
		}
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $code);
		rewind($stream);
		return $stream;
	}

	public function setUp() {
		Stream::setInterceptor($this);
		$this->wrap();
	}

	public function wrap() {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, '\Icewind\Interceptor\Stream');
		}
	}

	public function unwrap() {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_restore($protocol);
		}
	}
}
