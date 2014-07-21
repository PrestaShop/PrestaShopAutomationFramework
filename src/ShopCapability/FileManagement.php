<?php

namespace PrestaShop\ShopCapability;

use \PrestaShop\Helper\FileSystem as FS;

class FileManagement extends ShopCapability
{
	public function copyShopFilesTo($dstDir)
	{
		$srcDir = realpath($this->getShop()->getFilesystemPath());
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
			'#^/?cache/tcpdf/.#'
		]);
		
		mkdir($dstDir);
		if (!chmod($dstDir, 0777))
			{
				throw new \Exception("Can't chmod $dstDir", 1);
			}

		foreach ($files as $src) {
			$dst = FS::join($dstDir, substr($src, strlen($srcDir)+1));

			if (is_dir($src))
			{
				if (!mkdir($dst) || !chmod($dst, 0777))
				{
					throw new \Exception("Can't create directory: $dst", 1);
				}
			}
			else
			{
				if (!copy($src, $dst) || !chmod($dst, 0777))
				{
					throw new \Exception("Can't copy $src to $dst", 1);
				}
			}
		}
	}

	/**
	* Delete shop files.
	*
	* We put an auto-destruct script on the server and visit it with the browser,
	* that way even pesky www-data owned cache files are removed.
	*
	* The alternative is to run the whole script as root, which is a known bad thing.
	*
	*/
	public function deleteAllFiles()
	{
		$kill_script = <<<'EOS'
		<?php

		$dir = dirname(__FILE__);

		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
		    $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
		}
		rmdir($dir);

EOS;
		$target = FS::join($this->getShop()->getFilesystemPath(), 'selfkill.php');

		if (!file_put_contents($target, $kill_script))
		{
			throw new \Exception('Could not put selfkill script in place.');
		}

		$this->getBrowser()->visit($this->getShop()->getFrontOfficeURL().'/selfkill.php');

		if (file_exists($this->getShop()->getFilesystemPath()))
		{
			throw new \Exception(sprintf('Selfkill failed: file `%s` should not exist anymore.', $this->getShop()->getFilesystemPath()));
		}
	}
}