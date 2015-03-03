<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013-2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 * @license GPLv2
 */
namespace qtism\cli;

use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\Utils as XmlUtils;
use qtism\runtime\rendering\markup\xhtml\XhtmlRenderingEngine;
use qtism\runtime\rendering\markup\aqti\AqtiRenderingEngine;
use cli\Arguments as Arguments;
use \DOMXPath;

/**
 * Render CLI Module.
 * 
 * This CLI Module enables you to render QTI XML files in various flavours
 * e.g. XHTML or aQTI.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class Render extends Cli
{   
    /**
     * @see \qtism\cli\Cli::setupArguments()
     */
    protected function setupArguments()
    {
        $arguments = new Arguments(array('strict' => false));
        
        // -- Options
        // Flavour option.
        $arguments->addOption(
            array('flavour', 'f'),
            array(
                'default' => 'xhtml',
                'description' => 'Rendering flavour'
            )
        );
        
        // Source option.
        $arguments->addOption(
            array('source', 's'),
            array(
                'description' => 'QTI XML source to be rendered'
            )
        );
        
        // -- Flags
        // Document option.
        $arguments->addFlag(
            array('document', 'd'),
            'Embed the rendering into a document.'
        );
        
        return $arguments;
    }
    
    /**
     * @see \qtism\cli\Cli::checkArguments()
     */
    protected function checkArguments()
    {
        $arguments = $this->getArguments();
        
        // Check 'source' argument.
        if (($source = $arguments['source']) === null) {
            $this->missingArgument('source');
        } else {
            if (is_readable($source) === false) {
                if (file_exists($source) === false) {
                    $msg = "The QTI XML source file does not exist.";
                } else {
                    $msg = "The QTI XML source file cannot be read. Check permissions.";
                }
                
                $this->fail($msg);
            }
        }
        
        // Check 'flavour' argument.
        if (empty($arguments['flavour']) == true) {
            $arguments['flavour'] = 'xhtml';
        }
        
        $knownFlavours = array(
            'xhtml',
            'aqti'                
        );
        
        if (in_array(strtolower($arguments['flavour']), $knownFlavours) === false) {
            $msg = "Unknown flavour '" . $arguments['flavour'] . "'. Available flavours are " . implode(', ', $knownFlavours) . ".";
            $this->fail($msg);
        }
    }
    
    /**
     * Renders a QTI XML File into another flavour.
     * 
     * This implementations considers that all necessary checks about
     * arguments and their values were performed in \qtism\cli\Render::checkArguments().
     * 
     * @see \qtism\cli\Cli::run()
     * @see \qtism\cli\Render::checkArguments()
     */
    protected function run()
    {
        $engine = $this->instantiateEngine();
        $arguments = $this->getArguments();
        
        // Load XML Document.
        $source = $arguments['source'];
        $doc = new XmlDocument();
        $doc->load($source);
        
        switch (strtolower($arguments['flavour'])) {
            case 'aqti':
                $this->runAqti($doc, $engine);
                break;
                
            case 'xhtml':
                $this->runXhtml($doc, $engine);
                break;
        }
        
        $this->success("QTI XML file successfully rendered.");
    }
    
    /**
     * Run the rendering behaviour related to the "aQTI" flavour.
     * 
     * @param \qtism\data\storage\xml\XmlDocument $doc the QTI XML document to be rendered.
     * @param \qtism\runtime\rendering\markup\aqti\AqtiRenderingEngine $renderer
     */
    private function runAqti(XmlDocument $doc, AqtiRenderingEngine $renderer) {
        $arguments = $this->getArguments();
        $profile = $arguments['flavour'];

        $xml = $renderer->render($doc->getDocumentComponent());
        $xml->formatOutput = true;
        
        $header = "";
        $footer = "";
        
        if ($arguments['document'] === true) {
            $header .= "<!doctype html>\n";
        }
        
        $xpath = new DOMXPath($xml);
        $assessmentItemElts = $xpath->query("//div[contains(@class, 'qti-assessmentItem')]");
        
        if ($assessmentItemElts->length > 0 && $arguments['document'] === true) {
            $htmlAttributes = array();
    
            // Take the content of <assessmentItem> and put it into <html>.
            $attributes = $assessmentItemElts->item(0)->attributes;
            foreach ($attributes as $name => $attr) {
                $htmlAttributes[] = $name . '="'. $attr->value . '"';
            }
    
            while ($attributes->length > 0) {
                $assessmentItemElts->item(0)->removeAttribute($attributes->item(0)->name);
            }
    
            $header .= "<html " . implode(' ', $htmlAttributes) . ">\n";
            $header .= "<head>\n";
            $header .= "<meta charset=\"utf-8\">\n";
            $header .= "</head>\n";
    
            $itemBodyElts = $xpath->query("//div[contains(@class, 'qti-itemBody')]");
            if ($itemBodyElts->length > 0) {
                $body = $xml->saveXml($itemBodyElts->item(0));
                $body = substr($body, strlen('<div>'));
                $body = substr($body, 0, strlen('</div>') * -1);
                $body = "<body ${body}</body>\n";
            } else {
                $body = $xml->saveXml($xml->documentElement) . "\n";
            }
        
            if ($arguments['document'] === true) {
                $footer = "</html>\n";
            }
        } else {
            $body = $xml->saveXml($xml->documentElement) . "\n";
        }
        
        $this->out("{$header}{$body}{$footer}", false);
    }
    
    /**
     * Run the rendering behaviour related to the "XHTML" flavour.
     * 
     * @param \qtism\data\storage\xml\XmlDocument $doc The QTI XML document to be rendered.
     * @param \qtism\runtime\rendering\markup\xhtml\XhtmlRenderingEngine $renderer
     */
    private function runXhtml(XmlDocument $doc, XhtmlRenderingEngine $renderer) {
        $arguments = $this->getArguments();
        $profile = $arguments['flavour'];
        
        $xml = $renderer->render($doc->getDocumentComponent());
        $xml->formatOutput = true;
        
        $header = "";
        $footer = "";
        
        if ($arguments['document'] === true) {
            $header .= "<!doctype html>\n";
            $header .= "<html>\n";
            $header .= "<head>\n";
            $header .= "<meta charset=\"utf-8\">\n";
            $header .= "</head>\n";
            $header .= "<body>\n";
            
            $footer = "</body>\n";
            $footer .= "</html>\n";
        }
    
        $body = $xml->saveXml($xml->documentElement) . "\n";
        $this->out("{$header}{$body}{$footer}", false);
    }
    
    /**
     * Instantiate an appropriate Rendering Engine.
     * 
     * The instantiated Rendering Engine implementation will depend on the "flavour"
     * CLI argument.
     * 
     * @return \qtism\runtime\rendering\markup\AbstractMarkupRenderingEngine
     */
    private function instantiateEngine() {
        $arguments = $this->getArguments();
        switch (strtolower($arguments['flavour'])) {
            case 'aqti':
                return new AqtiRenderingEngine();
                break;
                
            case 'xhtml':
                return new XhtmlRenderingEngine();
                break;
        }
    }
}