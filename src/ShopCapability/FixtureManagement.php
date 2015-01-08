<?php

namespace PrestaShop\PSTAF\ShopCapability;

class FixtureManagement extends ShopCapability
{
    public function setupInitialState(array $initial_state)
    {
        if (isset($initial_state['ShopInstallation'])) {
            $this->getShop()->getInstaller()->install($initial_state['ShopInstallation']);

            if (empty($initial_state['ShopInstallation']['keepOnboardingModule'])) {
                $this->getShop()->getBackOfficeNavigator()->login()->deleteModule('onboarding');
                $this->getShop()->getBrowser()->clearCookies();
            }

        }

        return $this;
    }
}
