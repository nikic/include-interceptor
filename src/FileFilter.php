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

    public static function createDefault(): FileFilter {
        $filter = new self;
        $filter->addExtension('php');
        $filter->addExtension('phar');
        return $filter;
    }

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
     * Check if a file is whitelisted.
     *
     * @return int the length of the longest white list match
     */
    private function isWhiteListed(string $path): int {
        return $this->isListed($path, $this->whiteList);
    }

    /**
     * Check if a file is blacklisted.
     *
     * @return int the length of the longest black list match
     */
    private function isBlackListed(string $path): int {
        return $this->isListed($path, $this->blackList);
    }

    private function isListed(string $path, array $list): int {
        $length = 0;
        foreach ($list as $item) {
            $itemLen = \strlen($item);
            // Check for exact file match.
            if ($item === $path) {
                return $itemLen;
            }
            // Check for directory match.
            if ($itemLen >= $length && $this->inDirectory($item, $path)) {
                $length = $itemLen + 1; // +1 for trailing /
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
