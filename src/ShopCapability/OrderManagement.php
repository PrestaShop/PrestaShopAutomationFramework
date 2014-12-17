<?php

namespace PrestaShop\PSTAF\ShopCapability;

class OrderManagement extends ShopCapability
{
    public function visit($id_order)
    {
        $this
        ->getShop()
        ->getBackOfficeNavigator()
        ->visit('AdminOrders', 'view', $id_order);

        return $this;
    }

    public function validate()
    {
        $this
        ->getBrowser()
        ->jqcSelect('#id_order_state', 2)
        ->clickButtonNamed('submitState')
        ->ensureElementIsOnPage('[data-selenium-id="view_invoice"]');

        return $this;
    }

    public function getInvoiceLink()
    {
        if ($this->shopVersionBefore('1.6.0.10')) {
            $selector = "a.btn[href*=generateInvoice]";
        } else {
            $selector = '[data-selenium-id="view_invoice"]';
        }

        return $this->getBrowser()->getAttribute($selector, 'href');
    }

    public function getInvoicePDFData()
    {
        $invoice_link = $this->getInvoiceLink();

        return $this->getBrowser()->curl($invoice_link);
    }

    public function getInvoiceFromJSON()
    {
        $browser = $this->getBrowser();

        $invoice_json_link = $this->getInvoiceLink().'&debug=1';
        $text = $browser->curl($invoice_json_link);
        $json = json_decode($text, true);

        if (!$json)
            throw new \Exception('Invalid invoice JSON found');

        return $json;
    }
}
