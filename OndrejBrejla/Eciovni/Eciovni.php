<?php

namespace OndrejBrejla\Eciovni;

use Mpdf\Mpdf;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;

/**
 * Eciovni - plugin for Nette Framework for generating invoices using mPDF library.
 *
 * @copyright  Copyright (c) 2009 Ondřej Brejla
 * @license    New BSD License
 * @link       http://github.com/OndrejBrejla/Eciovni
 */
class Eciovni {

  /** @var Data */
  private $data = NULL;
  private $custom_data = NULL;

  /** @var Template */
  private $template;

  /** @var string */
  private $templatePath;

  public function __construct(Data $data = NULL, ITemplateFactory $templateFactory, LinkGenerator $linkGenerator) {
    if ($data !== NULL) {
      $this->setData($data);
    }

    $this->templatePath = __DIR__ . '/Eciovni.latte';
    $this->template = $templateFactory->createTemplate();
    $this->template->getLatte()->addProvider('uiControl', $linkGenerator);
  }

  /**
   * Setter for path to template
   *
   * @param string $templatePath
   */
  public function setTemplatePath($templatePath) {
    $this->templatePath = $templatePath;
  }

  public function setTranslator($translator) {
    $this->template->setTranslator($translator);
  }

  /**
   * Render current template into PDF and output/save it via mPDF.
   *
   * @param Mpdf        $mpdf
   * @param string|null $name  Full path or file name (depends on $dest)
   * @param string|bool|null $dest One of Destination::* ('I','D','F','S') or bool (true=DOWNLOAD, false=FILE)
   * @return string|null       Return value per mPDF docs (e.g., string when 'S')
   */
  public function exportToPdf(Mpdf $mpdf, $name = null, $dest = null)
  {
    // Generate HTML and write it to mPDF
    $this->generate($this->template);
    $mpdf->WriteHTML((string) $this->template);

    // Normalize $dest to a valid mPDF destination (avoid NULL for strtoupper)
    if (is_bool($dest)) {
      $dest = $dest ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::FILE;
    } elseif ($dest === null) {
      // If a file name/path is provided, default to saving to file; otherwise inline
      $dest = ($name !== null && $name !== '') ? \Mpdf\Output\Destination::FILE : \Mpdf\Output\Destination::INLINE;
    }

    // Normalize $name: for INLINE/DOWNLOAD it can be empty string
    if (in_array($dest, [\Mpdf\Output\Destination::INLINE, \Mpdf\Output\Destination::DOWNLOAD], true) && ($name === null)) {
      $name = ''; // mPDF expects '' for these modes
    }

    // Finally call mPDF Output with guaranteed non-null $dest
    return $mpdf->Output($name, $dest);
  }

  /**
   * Renderers the invoice to the defined template.
   *
   * @return void
   */
  public function render() {
    $this->processRender();
  }

  /**
   * Renderers the invoice to the defined template.
   *
   * @param Data $data
   * @return void
   * @throws IllegalStateException If data has already been set.
   */
  public function renderData(Data $data) {
    $this->setData($data);
    $this->processRender();
  }

  /**
   * Renderers the invoice to the defined template.
   *
   * @return void
   */
  private function processRender() {
    $this->generate($this->template);
    $this->template->render();
  }

  /**
   * Sets the data, but only if it hasn't been set already.
   *
   * @param Data $data
   * @return void
   * @throws IllegalStateException If data has already been set.
   */
  private function setData(Data $data) {
    if ($this->data == NULL) {
      $this->data = $data;
    } else {
      throw new IllegalStateException('Data have already been set!');
    }
  }

  public function setCustomData($data) {
    $this->custom_data = $data;
  }

  /**
   * Generates the invoice to the defined template.
   */
  private function generate(Template $template) {
    $template->setFile($this->templatePath);
    $template->getLatte()->addFilter('round', function($value, $precision = 2) {
      return number_format(round($value, $precision), $precision, ',', '');
    });

    $template->title = $this->data->getTitle();
    $template->caption = (string) $this->data->getCaption();

    $template->signatureText = (string) $this->data->getSignatureText();
    $template->signatureImgSrc = (string) $this->data->getSignatureImgSrc();

    $template->supplierText = $this->data->getSupplierText();
    $template->supplierLogoImgSrc = $this->data->getSupplierLogoImgSrc();
    $template->paymentMethod = $this->data->getPaymentMethod();
    $template->bankName = $this->data->getBankName();
    $template->bankAccount = $this->data->getBankAccount();
    $template->bankIban = $this->data->getBankIban();
    $template->bankSwift = $this->data->getBankSwift();

    $template->id = $this->data->getId();
    $template->items = $this->data->getItems();
    $template->custom_data = $this->custom_data;
    $this->generateSupplier($template);
    $this->generateCustomer($template);
    $this->generateDates($template);
    $this->generateSymbols($template);
    $this->generateFinalValues($template);
  }

  /**
   * Generates supplier data into template.
   *
   * @param ITemplate $template
   * @return void
   */
  private function generateSupplier(ITemplate $template) {
    $supplier = $this->data->getSupplier();
    $template->supplierName = $supplier->getName();
    $template->supplierStreet = $supplier->getStreet();
    $template->supplierHouseNumber = $supplier->getHouseNumber();
    $template->supplierCity = $supplier->getCity();
    $template->supplierZip = $supplier->getZip();
    $template->supplierIn = $supplier->getIn();
    $template->supplierTin = $supplier->getTin();
    $template->supplierAccountNumber = $supplier->getAccountNumber();
  }

  /**
   * Generates customer data into template.
   *
   * @param ITemplate $template
   * @return void
   */
  private function generateCustomer(ITemplate $template) {
    $customer = $this->data->getCustomer();
    $template->customerName = $customer->getName();
    $template->customerStreet = $customer->getStreet();
    $template->customerHouseNumber = $customer->getHouseNumber();
    $template->customerCity = $customer->getCity();
    $template->customerZip = $customer->getZip();
    $template->customerIn = $customer->getIn();
    $template->customerTin = $customer->getTin();
    $template->customerAccountNumber = $customer->getAccountNumber();
  }

  /**
   * Generates dates into template.
   *
   * @param ITemplate $template
   * @return void
   */
  private function generateDates(ITemplate $template) {
    $template->dateOfIssuance = $this->data->getDateOfIssuance();
    $template->expirationDate = $this->data->getExpirationDate();
    $template->dateOfVatRevenueRecognition = $this->data->getDateOfVatRevenueRecognition();
  }

  /**
   * Generates symbols into template.
   *
   * @param ITemplate $template
   * @return void
   */
  private function generateSymbols(ITemplate $template) {
    $template->variableSymbol = $this->data->getVariableSymbol();
    $template->specificSymbol = $this->data->getSpecificSymbol();
    $template->constantSymbol = $this->data->getConstantSymbol();
  }

  /**
   * Generates final values into template.
   *
   * @param ITemplate $template
   * @return void
   */
  private function generateFinalValues(ITemplate $template) {
    $template->finalUntaxedValue = $this->countFinalUntaxedValue();
    $template->finalTaxValue = $this->countFinalTaxValue();
    $template->finalValue = $this->countFinalValues();
  }

  /**
   * Counts final untaxed value of all items.
   *
   * @return int
   */
  private function countFinalUntaxedValue() {
    $sum = 0;
    foreach ($this->data->getItems() as $item) {
      $sum += $item->countUntaxedUnitValue() * $item->getUnits();
    }
    return $sum;
  }

  /**
   * Counts final tax value of all items.
   *
   * @return int
   */
  private function countFinalTaxValue() {
    $sum = 0;
    foreach ($this->data->getItems() as $item) {
      $sum += $item->countTaxValue();
    }
    return $sum;
  }

  /**
   * Counts final value of all items.
   *
   * @return int
   */
  private function countFinalValues() {
    $sum = 0;
    foreach ($this->data->getItems() as $item) {
      $sum += $item->countFinalValue();
    }
    return $sum;
  }

}

class IllegalStateException extends \RuntimeException {
  
}
