<?php

/**
 * This file is part of PDepend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2017 Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright 2008-2017 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace PDepend\Input;

/**
 * Filters a given file path against a blacklist with disallow path fragments.
 *
 * @copyright 2008-2017 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class ExcludePathFilter implements Filter
{
    /**
     * The pattern split limit before operating iteratively on patterns.
     *
     * @var int
     */
    public const PATTERN_SPLIT_LIMIT = 32766;

    /**
     * Regular expression that should not match against the absolute file paths or a chunk of a relative path.
     *
     * @since 0.10.0
     */
    protected string $pattern = '';

    /** Indicates if we are in bulk mode. */
    protected bool $isBulk = false;

    /**
     * List of patterns used for bulk matching.
     *
     * @var array<string>
     */
    protected array $iterativePatterns = [];

    /**
     * Constructs a new exclude path filter instance and accepts an array of
     * exclude pattern as argument.
     *
     * @param array<string> $patterns List of exclude file path patterns.
     */
    public function __construct(array $patterns)
    {
        $quoted = array_map('preg_quote', $patterns);
        $patternString = strtr(implode('|', $quoted), [
            '\\*' => '.*',
            '\\\\' => '/',
        ]);

        if (empty($patterns) || $patternString === '') {
            $this->pattern = '/^$/';

            return;
        }

        if (strlen($patternString) <= self::PATTERN_SPLIT_LIMIT) {
            $this->pattern = '(^(' . $patternString . '))i';

            return;
        }

        $this->isBulk = true;
        foreach ($quoted as $pattern) {
            $this->iterativePatterns[] = '(^(' . $pattern . '))i';
        }
    }

    /**
     * Returns <b>true</b> if this filter accepts the given path.
     *
     * @param string $relative The relative path to the specified root.
     * @param string $absolute The absolute path to a source file.
     */
    public function accept(string $relative, string $absolute): bool
    {
        return $this->notRelative($relative) && $this->notAbsolute($absolute);
    }

    /**
     * This method checks if the given <b>$path</b> does not match against the
     * exclude patterns as an absolute path.
     *
     * @param string $path The absolute path to a source file.
     * @since  0.10.0
     */
    protected function notAbsolute(string $path): bool
    {
        if ($this->isBulk) {
            return !$this->matchesIterativePatterns(str_replace('\\', '/', $path));
        }

        if (empty($this->pattern)) {
            return true;
        }

        return !preg_match($this->pattern, str_replace('\\', '/', $path));
    }

    /**
     * This method checks if the given <b>$path</b> does not match against the
     * exclude patterns as a relative path.
     *
     * @param string $path The relative path to a source file.
     * @since  0.10.0
     */
    protected function notRelative(string $path): bool
    {
        $subPath = str_replace('\\', '/', $path);

        while (true) {
            $slashPosition = strpos($subPath, '/');

            if ($slashPosition === false) {
                break;
            }

            $subPath = substr($subPath, $slashPosition + 1);

            if ($this->isBulk && $this->matchesIterativePatterns($subPath)) {
                return false;
            }

            if (empty($this->pattern)) {
                continue;
            }

            if (preg_match($this->pattern, $subPath) || preg_match($this->pattern, "/$subPath")) {
                return false;
            }
        }

        if ($this->isBulk) {
            return !$this->matchesIterativePatterns($subPath);
        }

        if (empty($this->pattern)) {
            return true;
        }

        return !preg_match($this->pattern, $subPath);
    }

    /**
     * Checks if the path matches any pattern in bulk mode
     */
    protected function matchesIterativePatterns(string $path): bool
    {
        foreach ($this->iterativePatterns as $pattern) {
            if (!empty($pattern) && preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
