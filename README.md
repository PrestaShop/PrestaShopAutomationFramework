PrestaShopAutomationFramework
=============================

This is a framework for writing functional tests for PrestaShop.

The tests are written in PHP, and communicate with a browser using Selenium.

# Framework Setup

## Linux

### Preparing your system

Make sure your system is configured properly to use PrestaShop, i.e. that you can install PrestaShop without any error and prefereably with all the optional parameters (as indicated by the installer) in the green.

In particular, you will *need* the php cURL extension.

Then check that the following other programs are installed and available in your PATH:
- java
- firefox
- mysql, mysqldump, musqladmin

### Getting the framework's code

```bash
git clone https://github.com/PrestaShop/PrestaShopAutomationFramework
```

### Installing the dependencies

```bash
cd PrestaShopAutomationFramework
php composer.phar install
```

# Framework Usage

## Setting up a PrestaShopAutomationFramework project

To run tests, you first need to setup a project.

Think of it as a testing environment. Its primary purpose is to define where to get the code to install PrestaShop, how to access the database, how to connect to Selenium, etc.

```bash
# create a folder to hold our files
mkdir ~/pstaf-project
cd ~/pstaf-project

# get the files against which we're going to run tests
git clone https://github.com/PrestaShop/PrestaShop -b 1.6

# put the pstaf tool in the path, for convenience
export PATH=/path/to/PrestaShopAutomationFramework:$PATH

#initialize the project
pstaf project:init
```

When running `pstaf project:init` you will be asked a few questions to setup the project.

Most questions should be straightforward, let's give further details on 3 options:
<dl>
	<dt>Front-Office URL</dt>
	<dd>Should be the URL at which the shop, once installed, is reachable by the browser.<br>If your PrestaShop repository folder is called `presta` then Front-Office URL should probably be 'http://localhost/presta'.</dd>
	<dt>Path to original shop files</dt>
	<dd>This is where the shop files should be taken from.<br>In our case, this would be 'PrestaShop' since we cloned the file to the `PrestaShop` sub folder of the `pstaf-project` folder.</dd>
	<dt>Path to web root</dt>
	<dd>This is the root folder of your virtual host, for instance `/var/www`.<br>That's where the framework will create the shops it needs.</dd>
</dl>

# Checking it works

Here we're going to try and install PrestaShop using the framework.

```
# fire up selenium
pstaf selenium:start

# install our shop
pstaf shop:install
```
Sit back and relax, if all goes well PrestaShop should install itself according to the settings you've input.

