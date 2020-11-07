<?php

declare(strict_types=1);

namespace MagicFramework\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class CommandHandler
{
    /**
     * @return string[]
     */
    private function getCommandClassNames(): array
    {
        $path = '../src/Command';
        $commandClassNames = [];

        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $phpFiles = new RegexIterator($allFiles, '/\.php$/');
        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile->getRealPath());
            $tokens = token_get_all($content);
            $namespace = '';
            for ($index = 0; isset($tokens[$index]); $index++) {
                if (!isset($tokens[$index][0])) {
                    continue;
                }
                if (T_NAMESPACE === $tokens[$index][0]) {
                    $index += 2;
                    while (isset($tokens[$index]) && is_array($tokens[$index])) {
                        $namespace .= $tokens[$index++][1];
                    }
                }
                if (T_CLASS === $tokens[$index][0] &&
                    T_WHITESPACE === $tokens[$index + 1][0] &&
                    T_STRING === $tokens[$index + 2][0]) {
                    $index += 2;
                    $commandClassNames[] = $namespace.'\\'.$tokens[$index][1];
                    break;
                }
            }
        }

        return $commandClassNames;
    }

    public function getCommand(Container $container, string $commandName): ?CommandInterface
    {
        $commandClassNames = $this->getCommandClassNames();
        foreach ($commandClassNames as $commandClassName) {
            /** @var CommandInterface $command */
            $command = $container->get($commandClassName);
            if ($command->getCommandName() === $commandName) {
                return $command;
            }
        }

        return null;
    }
}