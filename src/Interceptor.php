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

	/**
	 * Add a folder to the whitelist
	 *
	 * @param string $path
	 */
	public function addWhiteList($path) {
		$this->whiteList[] = rtrim($path, '/');
	}

	/**
	 * Check if we should intercept a file
	 *
	 * @param string $path
	 * @return bool
	 */
	public function shouldIntercept($path) {
		return $this->isValidExtension($path) && $this->isWhiteListed($path);
	}

	/**
	 * Check if a file has a whitelisted extension
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isValidExtension($path) {
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return in_array($extension, $this->extensions);
	}

	/**
	 * Check if a file is within a whitelisted folder
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isWhiteListed($path) {
		foreach ($this->whiteList as $whiteList) {
			if ($this->inDirectory($whiteList, $path)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Check if a file is within a folder
	 *
	 * @param string $directory
	 * @param string $path
	 * @return bool
	 */
	private function inDirectory($directory, $path) {
		return (substr($path, 0, strlen($directory) + 1) === $directory . '/');
	}

	/**
	 * Register an intercept hook
	 *
	 * The callback should have the following signature:
	 *     function hook(string $code, string $path): string|void
	 *
	 * If the callback returns a string the loaded code will be replaced with the result
	 *
	 * @param callable $hook
	 */
	public function addHook(callable $hook) {
		$this->hooks[] = $hook;
	}

	/**
	 * Open a file and run it trough all the hooks
	 *
	 * @param string $path
	 * @return resource
	 * @internal
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

	/**
	 * Setup this instance to intercept include calls
	 */
	public function setUp() {
		if (Stream::hasInterceptor()) {
			throw new \BadMethodCallException('An interceptor is already active');
		}
		Stream::setInterceptor($this);
		$this->wrap();
	}

	/**
	 * Stop intercepting include calls
	 */
	public function tearDown() {
		$this->unwrap();
		Stream::clearInterceptor();
	}

	/**
	 * Register the stream wrapper
	 *
	 * @internal
	 */
	public function wrap() {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, '\Icewind\Interceptor\Stream');
		}
	}

	/**
	 * Unregister the stream wrapper
	 *
	 * @internal
	 */
	public function unwrap() {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_restore($protocol);
		}
	}
}
