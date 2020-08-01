<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * Copyright (c) 2018-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Moyon Camille <camille@taotesting.com>
 * @license GPLv2
 */

namespace qtism\data\storage\xml;

use DOMElement;
use LogicException;
use qtism\data\storage\xml\versions\QtiVersionException;
use qtism\data\storage\xml\versions\ResultVersion;
use qtism\data\storage\xml\versions\ResultVersion21;
use qtism\data\storage\xml\versions\ResultVersion22;

/**
 * Class XmlResultDocument
 */
class XmlResultDocument extends XmlDocument
{
    /**
     * Set the QTI Result version in use for this document.
     *
     * @param string $versionNumber A QTI Result version number e.g. '2.1.0'.
     * @throws QtiVersionException when version is unknown regarding existing QTI Result versions.
     */
    public function setVersion($versionNumber)
    {
        $this->version = ResultVersion::create($versionNumber);
    }

    /**
     * Decorate the root DomElement
     *
     * Add Result namespace regarding version
     *
     * @param DOMElement $rootElement
     * @throws LogicException if the version is not supported by QTI result
     */
    protected function decorateRootElement(DOMElement $rootElement)
    {
        $version = trim($this->getVersion());
        switch ($version) {
            case '2.1.0':
            case '2.1.1':
                $qtiSuffix = ResultVersion21::XMLNS;
                $xsdLocation = ResultVersion21::XSD;
                break;

            case '2.2.0':
            case '2.2.1':
            case '2.2.2':
                $qtiSuffix = ResultVersion22::XMLNS;
                $xsdLocation = ResultVersion22::XSD;
                break;

            default:
                throw new LogicException('Result xml is not supported for QTI version "' . $version . '"');
        }

        $rootElement->setAttribute('xmlns', $qtiSuffix);
        $rootElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $rootElement->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', "$qtiSuffix $xsdLocation");
    }

    /**
     * Infer the QTI version of the document from its XML definition.
     *
     * @return boolean|string false if cannot be inferred otherwise a semantic version of the QTI version with major, minor and patch versions e.g. '2.1.0'.
     */
    protected function inferVersion()
    {
        $document = $this->getDomDocument();
        $root = $document->documentElement;
        $version = false;

        if (empty($root) === false) {
            $rootNs = $root->namespaceURI;

            if ($rootNs === ResultVersion21::XMLNS) {
                $version = '2.1.0';
            } elseif ($rootNs === ResultVersion22::XMLNS) {
                $version = '2.2.0';
            }
        }

        return $version;
    }
}
