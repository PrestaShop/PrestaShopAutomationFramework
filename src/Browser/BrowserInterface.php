<?php

namespace PrestaShop\PSTAF\Browser;

interface BrowserInterface
{
	    /**
		* Visit a URL.
	    * The optional basic_auth array contains user and pass keys,
	    * they will be injected into the URL if the array is provided.
		*/
	    public function visit($url, array $basic_auth = null);

	    /**
	     * Get the source code of the page.
	     */
	    public function getPageSource();

	    /**
	     * Get the value of an attribute for a given selector.
	     */
	    public function getAttribute($selector, $attribute);

	    /**
	     * Get the value of an input for the given selector.
	     */
	    public function getValue($selector);

	    /**
	     * Get the value selected in a select box.
	     */
	    public function getSelectedValue($selector);

	    /**
	     * Get the text selected in a select box.
	     */
	    public function getSelectedText($selector);

	    /**
	     * Get the values selected in a select box.
	     */
	    public function getSelectedValues($selector);

	    /**
	     * Get the trimmed, visible text of a given selector.
	     */
	    public function getText($selector);

        /**
    	* Find element(s).
        *
        * Selectors are interpreted as CSS by default,
        * example: ".main ul li"
        *
        * To use an xpath selector, prefix it with {xpath},
        * for instance "{xpath}}//div".
        *
        * The options you can provide are:
        * - unique: boolean, defaults to true - whether or not the element you're looking for is unique
        * - wait: boolean, defaults to true - wait for the element to show up if it is not immediately there
        * - baseElement: query applies to elements nested under baseElement - defaults to null
        *
    	*/
	    public function find($selector, array $options = array());

	    /**
	     * Get all the elements matching $selector.
	     */
	    public function all($selector);

	    /**
	     * Count all the elements matching a selector.
	     */
	    public function count($selector);

	    /**
	     * Determine whether or not a given selector is visible.
	     */
	    public function hasVisible($selector);

	    /**
	     * Throws exception if element is not on page.
	     * Should wait for it to appear.
	     */
	    public function ensureElementShowsUpOnPage($selector, $timeout_in_second = 5, $interval_in_millisecond = 500);

        /**
         * Throws exception if element is not on page.
         * Should NOT wait for it to appear.
         */
        public function ensureElementIsOnPage($selector);

	    /**
	     * Click an element.
	     */
	    public function click($selector);

	    /**
	     * Emulates a mouseover action.
	     */
	    public function hover($selector);

	    /**
	     * Click the first visible element matching selector.
	     */
	    public function clickFirstVisible($selector);

	    /**
	     * Click the first visible and enabled element matching selector.
	     */
	    public function clickFirstVisibleAndEnabled($selector);

	    /**
	     * Click the first element matching given options,
	     * where options are any combination of: displayed, enabled
	     */
	    public function clickFirst($selector, array $options = array());

	    /**
	     * Click the first enabled button with the given name.
	     */
	    public function clickButtonNamed($name);

	    /**
	     * Clicks the label that has the given "for" attribute.
	     * It is often more robust to click labels instead of
	     * checkboxes.
	     */
	    public function clickLabelFor($for);

	    /**
	     * Fill in the given input (text, date, anything "texty") with the given value.
	     */
	    public function fillIn($selector, $value);

	    /**
	     * Helper method to set the file for a file input.
	     * This should take care of making it visible if it is not.
	     * This is needed to allow file upload
	     * with widgets that mask the original input.
	     */
	    public function setFile($selector, $path);

	    /**
	     * Simulate keypresses.
	     */
	    public function sendKeys($keys);

        /**
    	* Select by value in a select.
    	*/
        public function select($selector, $value);

        /**
    	 * Select by value in a multiple select.
    	 * Options is an array of values.
    	 */
        public function multiSelect($selector, array $options);

        /**
    	* Get Select Options as associative array.
    	*/
        public function getSelectOptions($selector);

        /**
    	* Select by value in a JQuery chosen select.
    	*/
        public function jqcSelect($selector, $value);

        /**
		* Check or uncheck a checkbox.
		* If $on_off is === null, return whether the box is checked,
		* otherwise checks it according to the truthiness of $on_off.
		*/
	    public function checkbox($selector, $on_off = null);

        /**
    	* Wait for element to appear.
    	*/
        public function waitFor($selector, $timeout_in_second = 5, $interval_in_millisecond = 500);

        /**
    	* Return the current URL.
    	*/
        public function getCurrentURL();

        /**
    	* Return a parameter from the current URL.
    	*/
        public function getURLParameter($param);

        /**
    	 * Refresh the page, using same HTTP verb.
    	 */
        public function refresh();

        /**
    	 * Perform a GET on the current URL.
    	 */
        public function reload();

        /**
         * Convenience method to wait a bit and keep chaining methods.
         * Should return $this.
         */
        public function sleep($seconds);

        /**
         * Clear the browser's cookies.
         */
        public function clearCookies();

        /**
         * Execute javascript code in the context of the current page.
         * When executing a function, arguments can be provided as an associative
         * array in the $args argument.
         */
        public function executeScript($script, array $args = array());

		public function executeAsyncScript($script, array $args = array());

		public function setScriptTimeout($seconds);

        /**
         * Accept the currently displayed alert, throw exception if not found.
         */
        public function acceptAlert();

        /**
         * Switch to a given iframe, where $name is the name or id of the iframe (not a selector).
         */
        public function switchToIFrame($name);

        /**
         * Revert to the main page after e.g. switching to an iframe.
         */
        public function switchToDefaultContent();

        /**
         * Take a screenshot, saves it as $save_as.
         */
        public function takeScreenshot($save_as);

        /**
         * Execute a curl request with the same cookies
         * as the browser.
         */
        public function curl($url = null, array $options = array());

        /**
         * Execute a GET on the given URL, using the browser's XHR.
         * This is useful to retrieve raw data as the browser would do.
         */
        public function xhr($url, array $options = array());

        /**
         * Resize the browser window.
         */
        public function resizeWindow($width, $height);

        /**
         * Quits the browser, closing the window if any.
         */
        public function quit();

        /**
         * Set directory for artifacts created by the browser (logs, screenshots...)
         */
        public function setArtefactsDir($pathToDir);

        /**
         * Should the browser record screenshots?
         */
		public function setRecordScreenshots($trueOrFalse = true);
		public function getRecordScreenshots();
}
