Example Workflows
=================

**This documentation file is a work in progress, don't pay too much attention to it :)**

We try our best to make PrestaShopAutomationFramework something that can help you in your day to day PrestaShop projects.

This document describes a few ways to get productive with PrestaShopAutomationFramework.

# Tests First Development

Call it Behaviour Driven Development, Test Driven Development, Acceptance Driven Development... what matters is, when you write tests before writing code:
- you reduce the chance of your code being broken (by yourself, someone else, or yourself in 3 months) by *A LOT*
- you are sure your code does what it was supposed to do
- you are sure **you** know what your code is supposed to do

Not everything requires setting up a functional test, but here's my workflow when I do BDD with PrestaShop.

Assumptions:
- PSTAF is [configured properly](https://github.com/PrestaShop/PrestaShopAutomationFramework/blob/master/README.md)
- I have my development PrestaShop folder installed under `/var/www/PrestaShop`

## Setup PSTAF to work with your current project
If PrestaShop is already installed, making PSTAF aware of your project is really easy:
```bash
cd /var/www/PrestaShop
pstaf project:init
Mysql server password : # this should be guessed from PrestaShop's configuration file for you 
Front-Office URL (default = http://localhost/): http://localhost/PrestaShop # here I type the URL where my shop lives
Path to original shop files (default = .): # no need to change the default, the files are in the current directory 
Path to web root (default = ..): # well this is just /var/www, which is right above the CWD
```

See if it works (this should connect to the Back-Office and make a few basic checks):
```bash
pstaf selenium:start
pstaf test:run BackOfficeNavigation #just a quick non-destructive test to see if all is working
```

## Write a Test Case Stub
Now that we're ready to get to work, the first thing to do is to write the test.

Create a file `MyTest.php` in your `PrestaShop` folder, containing:
```php
class MyTest extends \PrestaShop\PSTAF\TestCase\LazyTestCase
{
    public function testNothing()
    {
        
    }
}
```

Run your test to see if it works:
```bash
pstaf test:run MyTest.php

Finished 1 tests in 0 minutes and 3 seconds.
.
```

Ok, looks good!

## Develop Something Interesting and Test It
In this example, we're going to create a very simple module that lets customers leave notes for themselves on product sheets.

The code for this simple demonstration module is [available on GitHub](https://github.com/djfm/notetoself).

### First test: is my module available in the BackOffice?
To know this, well, easy, log in to the back office, write the name of the module in the search box, see if it shows up.
Here is the code for this test (this is a method inside the `MyTest` class defined above):

```php
public function testOurModuleIsFoundByPrestaShop()
{
    $this->shop->getBackOfficeNavigator()
    ->login()
    ->visit('AdminModules');

    $this->browser->fillIn('#moduleQuicksearch', 'notetoself');
    $this->browser->waitFor('#anchorNotetoself');
}
```

```bash
pstaf test:run MyTest.php
Finished 1 tests in 0 minutes and 12 seconds.
.
```

Yay, the module is recognized by PrestaShop!

### Second test: does my module display something on the product sheets in Front-Office?

For now, let's install the module manually. We'll improve this later. Once you have installed the module, add this test function:
```php
public function testProductSheetHookIsWorking()
{
    $this->shop->getFrontOfficeNavigator()
    ->visitHome();

    // go to any product sheet, we don't care which one
    $this->browser
    ->click('a.product_img_link')
    // check that our container show up
    ->waitFor('#notetoself', 5);
}
```


