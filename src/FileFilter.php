<?php
/**
 * @license    http://www.opensource.org/licenses/mit-license.html
 *
 * Based on https://github.com/antecedent/patchwork/blob/master/Patchwork.php
 */

namespace Nikic\IncludeInterceptor;

class FileFilter {
	/**
	 * @var string[]
	 */
	private $whiteList = [];

	/**
	 * @var string[]
	 */
	private $blackList = [];

	/**
	 * @var string[]
	 */
	private $extensions = [];

	/**
	 * @param string $path
	 */
	public function addWhiteList($path) {
		$this->whiteList[] = rtrim($path, '/');
	}

	/**
	 * @param string $path
	 */
	public function addBlackList($path) {
		$this->blackList[] = rtrim($path, '/');
	}

	/**
	 * @param string $extension
	 */
	public function addExtension($extension) {
		$this->extensions[] = ltrim($extension, '.');
	}

	public function test($path) {
		if (!$this->isValidExtension($path)) {
			return false;
		}
		return $this->isWhiteListed($path) > $this->isBlackListed($path);
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
	 * Check if a file is within a white listed folder
	 *
	 * @param string $path
	 * @return int the length of the longest white list match
	 */
	private function isWhiteListed($path) {
		return $this->isListed($path, $this->whiteList);
	}

	/**
	 * Check if a file is within a black listed folder
	 *
	 * @param string $path
	 * @return int the length of the longest black list match
	 */
	private function isBlackListed($path) {
		return $this->isListed($path, $this->blackList);
	}

	/**
	 * @param string $path
	 * @param string[] $list
	 * @return int
	 */
	private function isListed($path, array $list) {
		$length = 0;
		foreach ($list as $item) {
			if (strlen($item) >= $length && $this->inDirectory($item, $path)) {
				$length = strlen($item) + 1; // +1 for trailing /
			}
		}
		return $length;
	}

	/**
	 * Check if a file is within a folder
	 *
	 * @param string $directory
	 * @param string $path
	 * @return bool
	 */
	private function inDirectory($directory, $path) {
		return ($directory === '') || (substr($path, 0, strlen($directory) + 1) === $directory . '/');
	}
}
