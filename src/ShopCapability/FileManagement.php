<?php

namespace PrestaShop\ShopCapability;

class FileManagement extends ShopCapability
{
	public function copyShopFilesTo($dstDir)
	{
		$srcDir = realpath($this->getShop()->getFilesystemPath());
		$files = \PrestaShop\Helper\FileSystem::lsRecursive($srcDir);
		
		mkdir($dstDir, 0777);

		foreach ($files as $src) {
			$dst = \PrestaShop\Helper\FileSystem::join($dstDir, substr($src, strlen($srcDir)+1));

			if (is_dir($src))
			{
				if (!mkdir($dst, 0777))
				{
					throw new \Exception("Can't create directory: $dst", 1);
				}
			}
			else
			{
				if (!copy($src, $dst))
				{
					throw new \Exception("Can't copy $src to $dst", 1);
				}			
			}
		}
	}
}