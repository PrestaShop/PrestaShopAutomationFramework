<?php

namespace PrestaShop\PSTAF\Helper;

class FileSystem
{
    public static function join()
    {
        $separator = DIRECTORY_SEPARATOR;

        $args = func_get_args();
        $base = $args[0];

        if (!$base)
            $base = '.';

        for ($i = 1; $i < count($args); $i++) {
            $base = rtrim($base, $separator).$separator.ltrim($args[$i], $separator);
        }

        return $base;
    }

    public static function rtrimSeparator($path)
    {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function isAbsolutePath($path)
    {
        return preg_match('#^/|^\w+\:#', $path);
    }

    public static function isRelativePath($path)
    {
        return !static::isAbsolutePath($path);
    }

    public static function exists()
    {
        return file_exists(call_user_func_array(__NAMESPACE__.'\FileSystem::join', func_get_args()));
    }

    private static function standardizePath($path)
    {
        return str_replace('\\', '/', $path);
    }

    private static function _lsRecursive($dir, array $exclude_exceptions = array(), array $exclude_regexps = array(), $topLevelDirectory)
    {
        $files = array();

        foreach (scandir($dir) as $entry) {
            $path = realpath(static::join($dir, $entry));

            if ($entry === '.' || $entry === '..')
                continue;

            $relpath = substr($path, strlen($topLevelDirectory));

            $dont_exclude = false;

            foreach ($exclude_exceptions as $exp) {
                if (preg_match($exp, self::standardizePath($relpath))) {
                    $dont_exclude = true;
                    break;
                }
            }

            if (!$dont_exclude) {
                foreach ($exclude_regexps as $exp) {
                    if (preg_match($exp, self::standardizePath($relpath)))
                        continue 2;
                }
            }

            if (is_link($path))
                continue;

            $files[] = $path;

            if (is_dir($path))
                $files = array_merge($files, static::_lsRecursive($path, $exclude_exceptions, $exclude_regexps, $topLevelDirectory));
        }

        sort($files);

        return $files;
    }

    public static function lsRecursive($dir, array $exclude_exceptions = array(), array $exclude_regexps = array())
    {
        return static::_lsRecursive($dir, $exclude_exceptions, $exclude_regexps, $dir);
    }

    public static function rmR($dir, $onlyContents = false)
    {
        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $path
        )
        {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        if (!$onlyContents) {
            rmdir($dir);
        }
    }

    public static function webRmR($directory, $url)
    {
        if (!is_dir($directory)) {
            return;
        }

        $kill_script = <<<'EOS'
				<?php

				$dir = dirname(__FILE__);

				foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
				    $path->isDir() ? @rmdir($path->getPathname()) : @unlink($path->getPathname());
				}
				@rmdir($dir);
				echo "done.";

EOS;
        $target = self::join($directory, 'pstaf.selfkill.php');

        if (!file_put_contents($target, $kill_script)) {
            throw new \Exception('Could not put selfkill script in place.');
        }

        $got = trim(file_get_contents($url.'/pstaf.selfkill.php'));

        if ($got !== 'done.')
            throw new \Exception('Invalid output from selfkill script.');

        $spinner = new Spinner(
            sprintf('Selfkill failed: file `%s` should not exist anymore.', $directory),
            300
        );

        $spinner->assertBecomesTrue(function () use ($directory) {
            if (file_exists($directory))
                @self::rmR($directory);

            return !file_exists($directory);
        });
    }

    public static function webActions(array $actions, $scriptRoot, $url)
    {
        $actor = <<<'EOS'
        <?php

        $actions = [@$actions@];

        foreach ($actions as $action) {
            if ($action['type'] === 'mkdir') {
                mkdir($action['target'], $action['chmod']);
            } elseif ($action['type'] === 'copy') {
                copy($action['source'], $action['target']);
                chmod($action['target'], $action['chmod']);
            }
        }
EOS;

        $actor = str_replace('[@$actions@]', var_export($actions, true), $actor);

        $actorName = 'webActionsActor_tmp.php';

        $actorPath = static::join($scriptRoot, $actorName);
        file_put_contents($actorPath, $actor);
        file_get_contents($url.'/'.$actorName);
        unlink($actorPath);
    }
}
