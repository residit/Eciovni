<?php declare(strict_types = 1);

namespace OndrejBrejla\Eciovni;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;

/**
 * @author Pavel Jurásek
 */
class EciovniFactory
{

	/** @var ITemplateFactory */
	private $templateFactory;

	/** @var LinkGenerator */
	private $linkGenerator;

	public function __construct(ITemplateFactory $templateFactory, LinkGenerator $linkGenerator)
	{
		$this->templateFactory = $templateFactory;
		$this->linkGenerator = $linkGenerator;
	}

	public function create(Data $data = null): Eciovni
	{
		return new Eciovni($data, $this->templateFactory, $this->linkGenerator);
	}

}
