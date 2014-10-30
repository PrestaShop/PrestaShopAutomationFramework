<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PrestaShop\PSTAF\ConfigurationFile;

use PrestaShop\PSTAF\Helper\FileSystem as FS;

class ProjectInit extends Command
{
    protected function configure()
    {
        $this->setName('project:init')
        ->setDescription('Setup a pstaf project in the current directory.')
        ->addQuoption('mysql_host', null, InputOption::VALUE_REQUIRED, 'Mysql server address', 'localhost')
        ->addQuoption('mysql_port', null, InputOption::VALUE_REQUIRED, 'Mysql server port', '3306')
        ->addQuoption('mysql_user', null, InputOption::VALUE_REQUIRED, 'Mysql server user', 'root')
        ->addQuoption('mysql_pass', null, InputOption::VALUE_REQUIRED, 'Mysql server password', '')
        ->addQuoption('mysql_database', null, InputOption::VALUE_REQUIRED, 'Mysql server database', 'prestashop')
        ->addQuoption('database_prefix', null, InputOption::VALUE_REQUIRED, 'Mysql server database prefix', 'ps_')
        ->addQuoption('front_office_url', null, InputOption::VALUE_REQUIRED, 'Front-Office URL', 'http://localhost/')
        ->addQuoption('back_office_folder_name', null, InputOption::VALUE_REQUIRED, 'Back-Office folder name', 'admin-dev')
        ->addQuoption('install_folder_name', null, InputOption::VALUE_REQUIRED, 'Install folder name', 'install-dev')
        ->addQuoption('prestashop_version', null, InputOption::VALUE_REQUIRED, 'PrestaShop version', '1.6')
        ->addQuoption('filesystem_path', null, InputOption::VALUE_REQUIRED, 'Path to original shop files', '.')
        ->addQuoption('path_to_web_root', null, InputOption::VALUE_REQUIRED, 'Path to web root', '..')
        ;

        $this->addOption('accept_defaults', 'y', InputOption::VALUE_NONE, 'Accept defaults without prompt');
    }

    protected function guessShopSettings($folder)
    {
        $guessed = [];

        if (is_readable($path = FS::join($folder, 'config', 'settings.inc.php'))) {
            $exp = '/\bdefine\s*\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*\)/';
            $m = [];
            $n = preg_match_all($exp, file_get_contents($path), $m);
            $options = [];
            for ($i = 0; $i < $n; $i++) {
                $options[$m[2][$i]] = $m[4][$i];
            }

            if (isset($options['_DB_SERVER_'])) {
                $hp = explode(':', $options['_DB_SERVER_']);
                $guessed['mysql_host'] = $hp[0];
                $guessed['mysql_port'] = isset($hp[1]) ? $hp[1] : '3306';
            }

            $map = [
                '_DB_NAME_' => 'mysql_database',
                '_DB_USER_' => 'mysql_user',
                '_DB_PASSWD_' => 'mysql_pass',
                '_DB_PREFIX_' => 'database_prefix',
                '_PS_VERSION_' => 'prestashop_version'
            ];

            foreach ($map as $native => $name) {
                if (isset($options[$native]))
                    $guessed[$name] = $options[$native];
            }
        }

        $back_office_folder_name = false;
        $install_folder_name = false;

        foreach (scandir($folder) as $entry) {
            if ($entry[0] !== '.' && is_dir(FS::join($folder, $entry))) {
                if (FS::exists($folder, $entry, 'index_cli.php'))
                    $install_folder_name = $entry;
                elseif (FS::exists($folder, $entry, 'ajax-tab.php'))
                    $back_office_folder_name = $entry;
            }
        }

        if ($back_office_folder_name !== false)
            $guessed['back_office_folder_name'] = $back_office_folder_name;

        if ($install_folder_name !== false)
            $guessed['install_folder_name'] = $install_folder_name;

        return $guessed;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->quoptparse($input, $output, $this->guessShopSettings('.'), $input->getOption('accept_defaults'));

        $conf = new ConfigurationFile('pstaf.conf.json');
        $conf->update(['shop' => $this->getOptions()])->save();
    }
}
