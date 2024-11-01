<?php

namespace Stalkfish\ErrorTracker\Stacktrace;

use Throwable;

class Stacktrace
{
    /** @var Frame[] */
    private $frames;

    public static function createForThrowable(Throwable $throwable): self
    {
        return new static($throwable->getTrace(), $throwable->getFile(), $throwable->getLine());
    }

    public static function create(): self
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS & ~DEBUG_BACKTRACE_PROVIDE_OBJECT);

        return new static($backtrace);
    }

    public function __construct(array $backtrace, string $topmostFile = null, string $topmostLine = null)
    {

        $currentFile = $topmostFile;
        $currentLine = $topmostLine;

        foreach ($backtrace as $rawFrame) {
            if (! $this->frameFromTracker($rawFrame) && ! $this->fileIgnored($currentFile)) {
                $this->frames[] = new Frame(
                    $currentFile,
                    $currentLine,
                    $rawFrame['function'] ?? null,
                    $rawFrame['class'] ?? null,
                    $this->frameFileFromApplication($currentFile)
                );
            }

            $currentFile = $rawFrame['file'] ?? 'unknown';
            $currentLine = $rawFrame['line'] ?? 0;
        }

        $this->frames[] = new Frame(
            $currentFile,
            $currentLine,
            '[top]'
        );
    }

    protected function frameFromTracker(array $rawFrame): bool
    {
        return isset($rawFrame['class']) && strpos($rawFrame['class'], 'Stalkfish\\ErrorTracker\\') === 0;
    }

    protected function frameFileFromApplication(string $frameFilename): bool
    {
        $relativeFile = str_replace('\\', DIRECTORY_SEPARATOR, $frameFilename);

        if (strpos($relativeFile, DIRECTORY_SEPARATOR . 'vendor') === 0) {
            return false;
        }

        return true;
    }

    protected function fileIgnored(string $currentFile): bool
    {
        $currentFile = str_replace('\\', DIRECTORY_SEPARATOR, $currentFile);

        $ignoredFiles = [
            '/ignition/src/helpers.php',
        ];

        foreach ($ignoredFiles as $ignoredFile) {
            if (strstr($currentFile, $ignoredFile) !== false) {
                return true;
            }
        }

        return false;
    }

    public function firstFrame(): Frame
    {
        return $this->frames[0];
    }

    public function toArray(): array
    {
        return array_map(function (Frame $frame) {
            return $frame->toArray();
        }, $this->frames);
    }

    public function firstApplicationFrame(): ?Frame
    {
        foreach ($this->frames as $index => $frame) {
            if ($frame->isApplicationFrame()) {
                return $frame;
            }
        }

        return null;
    }

    public function firstApplicationFrameIndex(): ?int
    {
        foreach ($this->frames as $index => $frame) {
            if ($frame->isApplicationFrame()) {
                return $index;
            }
        }

        return null;
    }
}
