Example Workflows
=================
We try our best to make PrestaShopAutomationFramework something that can help you in your day to day PrestaShop projects.

This document describes a few ways to get productive with PrestaShopAutomationFramework.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Tests First Development](#tests-first-development)
  - [Setup PSTAF to work with your current project](#setup-pstaf-to-work-with-your-current-project)
  - [Write a Test Case Stub](#write-a-test-case-stub)
  - [Develop Something Interesting and Test It](#develop-something-interesting-and-test-it)
    - [First test: is my module available in the BackOffice?](#first-test-is-my-module-available-in-the-backoffice)
    - [Second test: does my module display something on the product sheets in Front-Office?](#second-test-does-my-module-display-something-on-the-product-sheets-in-front-office)
    - [Third & fourth tests: are notes successfully stored?](#third-&-fourth-tests-are-notes-successfully-stored)
    - [Fifth test: are my notes kept if I register after having written something?](#fifth-test-are-my-notes-kept-if-i-register-after-having-written-something)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Tests First Development

Call it Behaviour Driven Development, Test Driven Development, Acceptance Driven Development... what matters is, when you write tests before writing code:
- you reduce the chances of your code being broken (by yourself, someone else, or yourself in 3 months) by *A LOT*
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
pstaf test:run BackOfficeNavigation -I #just a quick non-destructive test to see if all is working
```

Note: the `-I` option above stands for "inplace", it means the shop will not be duplicated before running the test. This is generally OK if the tests are non-destructive.

## Write a Test Case Stub
Now that we're ready to get to work, the first thing to do is to write the test. We're going to test a very simple module called `NoteToSelf`, so lets name the test file `NoteToSelfTest.php`.

Create a file `NoteToSelfTest.php` in your `PrestaShop` folder, containing:
```php
class NoteToSelfTest extends \PrestaShop\PSTAF\TestCase\LazyTestCase
{
    public function testNothing()
    {
        
    }
}
```

Run your test to see if it works:
```bash
pstaf test:run NoteToSelfTest.php -I
Finished 1 tests in 0 minutes and 3 seconds.
.
```

Ok, looks good! But of course it should pass, since we're not testing anything yet.

## Develop Something Interesting and Test It
In this example, we're going to create a very simple module that lets customers leave notes for themselves on product sheets.

The code for this simple demonstration module is [available on GitHub](https://github.com/djfm/notetoself).

### First test: is my module available in the BackOffice?
To know this, well, easy, log in to the back office, write the name of the module in the search box, see if it shows up.
Here is the code for this test (this is a method inside the `NoteToSelfTest` class defined above):

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
pstaf test:run NoteToSelfTest.php -I
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

Again, run the test:
```bash
pstaf test:run NoteToSelfTest.php -I
Finished 2 tests in 0 minutes and 33 seconds.
..
```

Good, the module hook is configured properly!

### Third & fourth tests: are notes successfully stored?

After the previous test, we're already on a page where we're supposed to be able to write notes. So this next test will not need to navigate to the product sheet and can directly try things out!

```php
public function testNotesAreSuccessfullyStored()
{
    $this->browser
    ->fillIn('#notetoself-notes', 'Selenium thinks this product is nice.')
    ->waitFor('#notetoself-status .success');
}
```

Looks good:
```bash
pstaf test:run NoteToSelfTest.php -I
Finished 3 tests in 0 minutes and 35 seconds.
...
```

But do notes survive a page refresh?
```php
public function testNotesSurvivePageRefresh()
{
    $this->assertEquals(
        'Selenium thinks this product is nice.',
        $this->browser->reload()->getValue('#notetoself-notes')
    );
}
```

Seems they do!
```bash
pstaf test:run NoteToSelfTest.php -I
Finished 4 tests in 0 minutes and 27 seconds.
....
```

### Fifth test: are my notes kept if I register after having written something?

Up until now, we only handled the case of a visitor that doesn't have an account (a guest).

If a guest has written notes and then registers, we'd like their notes to be kept!

To test this, we're going to create a new customer account and then check that the note we just created is still here:

```php
public function testNotesSurviveAccountCreation()
{
    // Store the URL of the product sheet we're on
    $url = $this->browser->getCurrentURL();
    
    // Create a new customer (you can of course pass options, but we're just
    // gonna go with the defaults here)
    $this->shop->getRegistrationManager()->registerCustomer();
    
    // Come back to the product sheet
    $this->browser->visit($url);
    
    // Check that the notes are still here!
    $this->assertEquals(
        'Selenium thinks this product is nice.',
        $this->browser->getValue('#notetoself-notes')
    );
}
```

**Important note:** this time, we're creating a test that is not repeatable because it changes the state of the shop too much: we can't run the same test twice because we cannot create the same customer twice on the same shop and `registerCustomer()`, called without options, will always register Mrs Carrie Murray.

So, to be able to run the test several times (during development, it is often necessary to repeat the tests many times), we're just going to first save the database:

```bash
pstaf db:dump
```

Then load it before running the test:
```bash
pstaf db:load && pstaf test:run NoteToSelfTest.php -I
#...
Finished 5 tests in 0 minutes and 30 seconds.
.....
```

With `db:dump` and `db:load` it is easy to save and restore the state of the database, which allows running destructive tests in place with little overhead.

If you need to save different states of the shop, `db:dump` and `db:load` both accept an optional argument that tells it which filename to use for the SQL dump.

Here is the full test case so far:
```php
class NoteToSelfTest extends \PrestaShop\PSTAF\TestCase\LazyTestCase
{
    public function testOurModuleIsFoundByPrestaShop()
    {
        $this->shop->getBackOfficeNavigator()
        ->login()
        ->visit('AdminModules');

        $this->browser->fillIn('#moduleQuicksearch', 'notetoself');
        $this->browser->waitFor('#anchorNotetoself', 5);
    }

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

    public function testNotesAreSuccessfullyStored()
    {
        $this->browser
        ->fillIn('#notetoself-notes', 'Selenium thinks this product is nice.')
        ->waitFor('#notetoself-status .success');
    }

    public function testNotesSurvivePageRefresh()
    {
        $this->assertEquals(
            'Selenium thinks this product is nice.',
            $this->browser->reload()->getValue('#notetoself-notes')
        );
    }

    public function testNotesSurviveAccountCreation()
    {
        $url = $this->browser->getCurrentURL();


        $this->shop->getRegistrationManager()->registerCustomer();
        
        $this->browser->visit($url);
        
        $this->assertEquals(
            'Selenium thinks this product is nice.',
            $this->browser->getValue('#notetoself-notes')
        );
    }
}
```

Next time, we'll see how to automate the installation of the module so that the test can be included in a standard test suite and ran as part of the PrestaShop QA process.