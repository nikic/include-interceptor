<?php
/**
 * @license    http://www.opensource.org/licenses/mit-license.html
 *
 * Based on https://github.com/antecedent/patchwork/blob/master/Patchwork.php
 */

namespace Nikic\IncludeInterceptor;

class Interceptor {
	/**
	 * @var FileFilter
	 */
	private $filter;

	private $protocols = ['file', 'phar'];

	/**
	 * @var callable[]
	 */
	private $hooks = [];

	public function __construct() {
		$this->filter = new FileFilter();
		$this->filter->addExtension('php');
		$this->filter->addExtension('phar');
	}

	/**
	 * Add a folder to the white list.
	 */
	public function addWhiteList(string $path): void {
		$this->filter->addWhiteList($path);
	}

	/**
	 * Add a folder to the black list.
	 */
	public function addBlackList(string $path): void {
		$this->filter->addBlackList($path);
	}

	/**
	 * Check if we should intercept a file.
	 */
	public function shouldIntercept(string $path): bool {
		return $this->filter->test($path);
	}

	/**
	 * Register an intercept hook.
	 *
	 * The callback should have the following signature:
	 *     function hook(string $code, string $path): string|void
	 *
	 * If the callback returns a string the loaded code will be replaced with the result
	 */
	public function addHook(callable $hook): void {
		$this->hooks[] = $hook;
	}

	/**
	 * Open a file and run it through all the hooks.
	 *
	 * @return resource
	 * @internal
	 */
	public function intercept(string $path) {
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
	 * Setup this instance to intercept include calls.
	 */
	public function setUp(): void {
		if (Stream::hasInterceptor()) {
			throw new \BadMethodCallException('An interceptor is already active');
		}
		Stream::setInterceptor($this);
		$this->wrap();
	}

	/**
	 * Stop intercepting include calls.
	 */
	public function tearDown(): void {
		$this->unwrap();
		Stream::clearInterceptor();
	}

	/**
	 * Register the stream wrapper.
	 *
	 * @internal
	 */
	public function wrap(): void {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_unregister($protocol);
			stream_wrapper_register($protocol, Stream::class);
		}
	}

	/**
	 * Unregister the stream wrapper.
	 *
	 * @internal
	 */
	public function unwrap(): void {
		foreach ($this->protocols as $protocol) {
			stream_wrapper_restore($protocol);
		}
	}
}
