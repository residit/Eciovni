# Eciovni
Component for generating invoices using Nette Framework and mPDF library.

For of fork of original library. I create this fork to test if it works with PHP 7.0 and mPDF 6.1.

# Install (with composer):

Edit composer.json.

```
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lamagoci/Eciovni"
        }
        
    ...
    
    "require": {
        ...
        "mpdf/mpdf": "^6.1",
        "ondrejbrejla/eciovni": "v2.1b"
```

For install all dopendecies just run
```
composer update
```

# Usage

1. like component (there are some documentation on the internet)
2. like service

a) Create service class and inject templateFactory and linkGenerator

'''
<?php

namespace App\Model\Util;

use OndrejBrejla\Eciovni;

/**
 * Description of MailSender
 *
 * @author pavel
 */
class InvoiceFactory
{
    /** @var \Nette\Application\LinkGenerator */
    private $linkGenerator;

    /** @var \Nette\Application\UI\ITemplateFactory */
    private $templateFactory;
    
    function __construct(\Nette\Application\LinkGenerator $generator,
            \Nette\Application\UI\ITemplateFactory $templateFactory) {
        $this->linkGenerator = $generator;
        $this->templateFactory = $templateFactory;
    }
    
    public function createInvoice($data) {
        return new Eciovni\Eciovni($data, $this->templateFactory, $this->linkGenerator);
    }
    
}
'''

b) Register service @config.neon
```
...
services:
    ...
	- App\Model\Util\InvoiceFactory
```

c) In handle your action
```
    public function handleInvoice($orderId) {
        $mpdf = new \mPDF();
        
        $supplierBuilder = new Eciovni\ParticipantBuilder("Company name", "Street", "No", "City", "ZIP");
        $supplier = $supplierBuilder->setIn("ICO")->setAccountNumber("1234567890/1234")
                ->build();
                
        $variableSymbol = '1';
        $customerBuilder = new Eciovni\ParticipantBuilder("Name", "Street", "No", "City", "ZIP");
        $customer = $customerBuilder->build();
        $dateExp =  new \DateTime();
        $dateNow =  new \DateTime();
        $items[] = new Eciovni\ItemImpl("Jeden druh květin ", "1", "479", Eciovni\TaxImpl::fromPercent("0"), true);
        
        $dataBuilder = new Eciovni\DataBuilder($variableSymbol, 'Faktura - faktura číslo', $supplier, $customer, $dateExp, $dateNow, $items);
        $dataBuilder->setVariableSymbol($variableSymbol)->setDateOfVatRevenueRecognition($dateNow);
        $data = $dataBuilder->build();
        $invoice = $this->invoiceFactory->createInvoice($data);
        $invoice->exportToPdf($mpdf, "invoice.pdf", "D");
    }
```
