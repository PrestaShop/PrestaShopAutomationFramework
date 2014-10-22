<?php

namespace PrestaShop\ShopCapability;

use \PrestaShop\Helper\FileSystem as FS;

class FileManagement extends ShopCapability
{
    public static function copyShopFiles($srcDir, $dstDir)
    {
        $files = FS::lsRecursive($srcDir,[
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
            '#^/?pstaf\.#',
            '#^/?selenium\.pid$#',
            '#^/?selenium\.log$#'
        ]);

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
        \PrestaShop\Helper\FileSystem::webRmR($this->getShop()->getFilesystemPath(), $this->getShop()->getFrontOfficeURL());

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
