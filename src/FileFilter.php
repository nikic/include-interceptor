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

	public function addWhiteList(string $path): void {
		$this->whiteList[] = rtrim($path, '/');
	}

	public function addBlackList(string $path): void {
		$this->blackList[] = rtrim($path, '/');
	}

	public function addExtension(string $extension): void {
		$this->extensions[] = ltrim($extension, '.');
	}

	public function test(string $path): bool {
		if (!$this->isValidExtension($path)) {
			return false;
		}
		return $this->isWhiteListed($path) > $this->isBlackListed($path);
	}

	/**
	 * Check if a file has a whitelisted extension.
	 */
	private function isValidExtension(string $path): bool {
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return in_array($extension, $this->extensions);
	}

	/**
	 * Check if a file is within a white listed folder.
	 *
	 * @return int the length of the longest white list match
	 */
	private function isWhiteListed(string $path): int {
		return $this->isListed($path, $this->whiteList);
	}

	/**
	 * Check if a file is within a black listed folder.
	 *
	 * @return int the length of the longest black list match
	 */
	private function isBlackListed(string $path): int {
		return $this->isListed($path, $this->blackList);
	}

	private function isListed(string $path, array $list): int {
		$length = 0;
		foreach ($list as $item) {
			if (strlen($item) >= $length && $this->inDirectory($item, $path)) {
				$length = strlen($item) + 1; // +1 for trailing /
			}
		}
		return $length;
	}

	/**
	 * Check if a file is within a folder.
	 */
	private function inDirectory(string $directory, string $path): bool {
		return ($directory === '') || (substr($path, 0, strlen($directory) + 1) === $directory . '/');
	}
}
