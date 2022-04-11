<?php

namespace Returnless\Connector\Model\Config\Frontend;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Backend\Block\Template\Context;

/**
 *  Class GenerationType
 */
class GenerationType extends Field
{
    protected $productMetadata;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        Context $context,
        array $data = []
    ) {
        $this->productMetadata = $productMetadata;
        parent::__construct($context, $data);
    }

    /**
     * Render connection button considering request parameter
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (strtolower($this->productMetadata->getEdition()) == 'enterprise' ||
            strtolower($this->productMetadata->getEdition()) == 'b2b') {
            return parent::render($element);
        }
        //$isConnected = $this->_scopeConfig->isSetFlag(Config::XML_PATH_QBONLINE_IS_CONNECTED);
        //if ($isConnected) {
            //$element->setDisabled(true);
        //}


        return $this->productMetadata->getVersion() . ' ' . $this->productMetadata->getEdition();//parent::render($element);
    }
}
