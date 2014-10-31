<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Writing a Test](#writing-a-test)
- [The test standard: PHPUnit_Framework_TestCase](#the-test-standard-phpunit_framework_testcase)
- [General structure of a Test Case](#general-structure-of-a-test-case)
- [The PrestaShop Test Cases](#the-prestashop-test-cases)
- [Types of Test Cases](#types-of-test-cases)
  - [PrestaShop\PSTAF\TestCase\TestCase](#PrestaShop\PSTAF\TestCase\testcase)
  - [PrestaShop\PSTAF\TestCase\LazyTestCase](#PrestaShop\PSTAF\TestCase\lazytestcase)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Writing a Test
==============
# The test standard: PHPUnit_Framework_TestCase
Test classes should conform to the `PHPUnit_Framework_TestCase` standard.

We provide a special test runner, called `ptest` (p as in "parallel"), that is compatible with (part of) the `PHPUnit_Framework_TestCase` specification.

While less powerful than PHPUnit itself, the `ptest` test runner has several optimizations that are especially useful when runnning functional tests:
- advanced parallelization (`@dataProvider`'s can be parallelized)
- real time feedback (because you don't always want to wait 1 hour before seeing error messages)

# General structure of a Test Case
A Test Case is just a PHP class that contains test methods. Its name must end in Test, for instance "BackOfficeNavigationTest".

Within a Test class, test Methods must start with "test", for instance "testLogin".

Test methods inform the test runner about a failure by throwing an exception - any exception, although the more descriptive the better!

Example of a basic test:
```php
    namespace PrestaShop\FunctionalTest;

    class BasicTest extends \PrestaShop\PSTAF\TestCase\TestCase
    {
        /**
         * This test will fail.
         */
        public function testSomething()
        {
            $this->assertEquals($expected = 2, $actual = 3);
        }

        /**
         * This test will fail too.
         */
        public function testSomethingElse()
        {
            thow new \Exception("Something went terribly wrong!");
        }

        /**
         * This test will pass.
         */
        public function testSomethingThatWorks()
        {
            $a = 1;
            $b = 2;
            $this->assertEquals(3, $a + $b);
        }
    }
```

Now that we're clear about the terminology, let's speak of real functional tests!

# The PrestaShop Test Cases
Our testing framework builds up on the basic test definition and enhances it with a few things to make your life easier.

Inside of a Test Case class that extends `PrestaShop\PSTAF\TestCase\TestCase` you get:
- `$this->shop`, an object of type `PrestaShop\PSTAF\Shop` that you can use to manipulate a shop.
- `$this->browser`, an object of type `PrestaShop\PSTAF\PSBrowser` that controls a browser.

The framework handles creating and destroying the actual shops for you according to the settings in your project's configuration file.

# Types of Test Cases
The framework provides two base test classes, located under `src/TestCase`, namely:
- PrestaShop\PSTAF\TestCase\TestCase
- PrestaShop\PSTAF\TestCase\LazyTestCase

Your tests should extend one of these classes. Which one?
This would depends on your goals, read on!

## PrestaShop\PSTAF\TestCase\TestCase
Inside of a `PrestaShop\PSTAF\TestCase\TestCase` you get a new `Shop` instance before each test method. A new `Shop` instance means that behind the scenes a new PrestaShop installation with a new database will be performed. 

This is useful if your test methods alter the state of the shop in a significant and mutually incompatible manner.

It also helps make results consistent. Say in `testA` you create a Cart Rule and for some reason `testA` fails to remove it after its execution. Now imagine `testB` tries to check the price of a product in a shopping cart. If the Cart Rule is still there, `testB` may fail - but this is a false negative.

By using a `TestCase` as base class, you can be certain that at the time when testB is called, `$this->shop` will point to a brand new shop, with no created cart rules.

##  PrestaShop\PSTAF\TestCase\LazyTestCase
A `PrestaShop\PSTAF\TestCase\LazyTestCase` extends `PrestaShop\PSTAF\TestCase\TestCase`.

The difference is that the state of the shop is not reset after each test method. This is obviously a lot faster, but should only be used when you are absolutely sure that your test methods don't step on each other's toes.

A good rule of thumb to find out whether it is safe to use a `LazyTestCase` is: could your tests be ran in any order? could your tests be ran in parallel?

If the answer to the 2 questions is yes, then it is safe to use a `LazyTestCase`.  There are other cases where a `LazyTestCase` is useful, of course. If you really need dependencies between your tests (for instance, create a product in a test, delete it in another), then a `LayTestCase` is the only way to go.

When designing tests, especially lazy ones, try to make it so that if something goes wrong, it reports more false negatives that false positives.



