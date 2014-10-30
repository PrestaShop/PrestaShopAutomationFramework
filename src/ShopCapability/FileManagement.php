<?php

namespace PrestaShop\PSTAF\ShopCapability;

use PrestaShop\PSTAF\Helper\FileSystem as FS;

class FileManagement extends ShopCapability
{
    public static function listShopFiles($dir)
    {
        return FS::lsRecursive($dir,[
            '#^/?cache/smarty/cache/index.php#',
            '#^/?cache/smarty/compile/index.php#',
            '#^/?cache/purifier/index.php#',
            '#^/?cache/cachefs/index.php#',
            '#^/?cache/cachefs/index.php#',
            '#^/?cache/sandbox/index.php#',
            '#^/?cache/tcpdf/index.php#'
        ],[
            '#^/?cache/smarty/cache/.#',
            '#^/?cache/smarty/compile/.#',
            '#^/?cache/purifier/.#',
            '#^/?cache/cachefs/.#',
            '#^/?cache/cachefs/.#',
            '#^/?cache/sandbox/.#',
            '#^/?cache/tcpdf/.#',
            '#^/?\.git/#',
            '#^/?\.gitignore$#',
            '#^/?pstaf\.#',
            '#^/?selenium\.pid$#',
            '#^/?selenium\.log$#'
        ]);
    }

    public static function copyShopFiles($srcDir, $dstDir)
    {
        $files = static::listShopFiles($srcDir);

        mkdir($dstDir);
        if (!chmod($dstDir, 0777)) {
            throw new \Exception("Can't chmod $dstDir", 1);
        }

        foreach ($files as $src) {
            $dst = FS::join($dstDir, substr($src, strlen(realpath($srcDir))+1));

            if (is_dir($src)) {
                if (!@mkdir($dst) || !@chmod($dst, 0777)) {
                    throw new \Exception("Can't create / chmod directory: $dst", 1);
                }
            } else {
                if (!@copy($src, $dst) || !@chmod($dst, 0777)) {
                    throw new \Exception("Can't copy / chmod $src to $dst", 1);
                }
            }
        }
    }

    public function webCopyShopFiles($srcDir, $dstDir)
    {
        $files = static::listShopFiles($srcDir);

        $actions = [];

        $actions[] = [
            'type' => 'mkdir',
            'target' => $dstDir,
            'chmod' => 0777
        ];

        foreach ($files as $src) {
            $dst = FS::join($dstDir, substr($src, strlen(realpath($srcDir))+1));

            if (is_dir($src)) {
                $actions[] = [
                    'type' => 'mkdir',
                    'target' => $dst,
                    'chmod' => 0777
                ];
            } else {
                $actions[] = [
                    'type' => 'copy',
                    'source' => $src,
                    'target' => $dst,
                    'chmod' => 0777
                ];
            }
        }

        FS::webActions($actions, $this->getShop()->getFilesystemPath(), $this->getShop()->getFrontOfficeURL());
    }

    public function copyShopFilesTo($dstDir, $srcDir = null)
    {
        if ($srcDir === null)
            $srcDir = realpath($this->getShop()->getFilesystemPath());

        self::copyShopFiles($srcDir, $dstDir);
    }

    /**
	* Delete shop files.
	*
	* We put an auto-destruct script on the server and visit it over the web,
	* that way even pesky www-data owned cache files are removed.
	*
	* The alternative is to run the whole script as root, which is a known bad thing.
	*
	*/
    public function deleteAllFiles()
    {
        FS::webRmR($this->getShop()->getFilesystemPath(), $this->getShop()->getFrontOfficeURL());

        return $this;
    }

    public function updateSettingsIncIfExists(array $values)
    {
        $settings_inc = FS::join($this->getShop()->getFilesystemPath(), 'config', 'settings.inc.php');
        if (file_exists($settings_inc)) {
            foreach ($values as $key => $value) {
                if ($key === '_DB_NAME_') {
                    $exp = '/(define\s*\(\s*([\'"])_DB_NAME_\2\s*,\s*([\'"]))(.*?)((\3)\s*\)\s*;)/';
                    $settings = file_get_contents($settings_inc);
                    $settings = preg_replace($exp, "\${1}".$value."\${5}", $settings);
                    file_put_contents($settings_inc, $settings);
                }
            }
        }

        return $this;
    }

    public function changeHtaccessPhysicalURI($uri)
    {
        $htaccess_path = FS::join($this->getShop()->getFilesystemPath(), '.htaccess');

        if (file_exists($htaccess_path)) {
            $htaccess = file_get_contents($htaccess_path);
            $rewrite_exp = '/(^\s*RewriteRule\s+\.\s+-\s+\[\s*E\s*=\s*REWRITEBASE\s*:)\/[^\/]+\/([^\]]*\]\s*$)/mi';
            $htaccess = preg_replace($rewrite_exp, '${1}'.$uri.'${2}', $htaccess);

            $errdoc_exp = '/(^\s*ErrorDocument\s+\w+\s+)\/[^\/]+\/(.*?$)/mi';
            $htaccess = preg_replace($errdoc_exp, '${1}'.$uri.'${2}', $htaccess);

            file_put_contents($htaccess_path, $htaccess);
        }

        return $this;
    }
}
