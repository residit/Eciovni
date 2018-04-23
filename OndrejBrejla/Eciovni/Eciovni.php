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

  /**
   * Exports Invoice template via passed mPDF.
   *
   * @param mPDF $mpdf
   * @param string $name
   * @param string $dest
   * @return string|NULL
   */
  public function exportToPdf(\mPDF $mpdf, $name = NULL, $dest = NULL) {
    $this->generate($this->template);
    $mpdf->WriteHTML((string) $this->template);

    $result = NULL;
    if (($name !== '') && ($dest !== NULL)) {
      $result = $mpdf->Output($name, $dest);
    } elseif ($dest !== NULL) {
      $result = $mpdf->Output('', $dest);
    } else {
      $result = $mpdf->Output($name, $dest);
    }
    return $result;
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
