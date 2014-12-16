<?php

namespace PrestaShop\PSTAF\PageObject;

class MyAccount extends PageObject
{
    public function visit($url = 'Argument Never Used')
    {
        $this->getShop()->getFrontOfficeNavigator()->visitHome();
        $this->getBrowser()->clickFirst('.header_user_info a');

        return $this;
    }

    public function goToMyAddresses()
    {
        $this->getBrowser()->click('{xpath}(//a[i[@class="icon-building"]])[last()]');
        return $this->getPageObject('MyAddresses');
    }
}
