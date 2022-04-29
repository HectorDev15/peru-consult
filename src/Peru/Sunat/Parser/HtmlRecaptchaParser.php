<?php

declare(strict_types=1);

namespace Peru\Sunat\Parser;

use DOMNode;
use DOMNodeList;
use DOMXPath;
use Generator;
use Peru\Sunat\HtmlParserInterface;

class HtmlRecaptchaParser implements HtmlParserInterface
{
    /**
     * Parse html to dictionary.
     *
     * @param string $html
     *
     * @return array|false
     */
    public function parse(string $html)
    {
        $xp = XpathLoader::getXpathFromHtml($html);
        $table = $xp->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' list-group ')]");

        if (0 == $table->length) {
            return false;
        }

        $nodes = $table->item(0)->childNodes;

        return $this->getKeyValues($nodes, $xp);
    }

    public function parseDeuda(string $html)
    {
        $xp = XpathLoader::getXpathFromHtml($html);
        $table = $xp->query("//table");
        $noDeuda = $xp->query("/html/body/div/div[3]/div[2]/div/div");
        
        if ($noDeuda->length == 1) {
            return ['No se ha remitido deuda en cobranza coactiva que corresponda al contribuyente consultado.'];
        }
        if (0 == $table->length) {
            return false;
        }

        $nodes = $table->item(0)->childNodes;

        $res = $this->getDeuda($nodes, $xp);

        return $res;
    }

    private function getKeyValues(DOMNodeList $nodes, DOMXPath $xp): array
    {
        $dic = [];
        foreach ($nodes as $item) {
            /** @var $item DOMNode */
            if ($this->isNotElement($item)) {
                continue;
            }

            $this->setKeyValuesFromNode($xp, $item, $dic);
        }

        return $dic;
    }

    private function setKeyValuesFromNode(DOMXPath $xp, DOMNode $item, &$dic)
    {
        $keys = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' list-group-item-heading ')]", $item);
        $values = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' list-group-item-text ')]", $item);

        $isHeadRow = $values->length === 0 && $keys->length === 2;
        if ($isHeadRow) {
            $title = trim($keys->item(0)->textContent);
            $dic[$title] = trim($keys->item(1)->textContent);

            return;
        }

        for ($i = 0; $i < $keys->length; $i++) {
            $title = trim($keys->item($i)->textContent);

            if ($values->length > $i) {
                $dic[$title] = trim($values->item($i)->textContent);
            } else {
                $dic[$title] = iterator_to_array($this->getValuesFromTable($xp, $item));
            }
        }
    }

    private function getDeuda(DOMNodeList $nodes, DOMXPath $xp): array
    {
        $dic = [];
        $noDeuda = null;

        foreach ($nodes as $item) {
            if ($item->nodeType === XML_TEXT_NODE) {
                $noDeuda = trim($item->textContent);
                $str = substr($noDeuda, 17);
                if ($str === 'No se ha remitido') {
                    $dic[] = $noDeuda;
                    break;
                }
            }
            /** @var $item DOMNode */
            if ($this->isNotElement($item)) {
                continue;
            }

            $dic[] = iterator_to_array($this->getValuesFromTable($xp, $item));
        }

        if (count($dic) > 0) {
            $keys = [];
            $res = [];
            foreach ($dic as $k => $val) {
                if ($k === 0) {
                    $keys = $val;
                    continue;
                }
                $values = [];
                foreach ($val as $key => $value) {
                    $values[$keys[$key]] = $value;
                }
                $res[] = $values;
            }
            $dic = $res;
        }

        return $dic;
    }

    private function getValuesFromTable(DOMXPath $xp, DOMNode $item): Generator
    {
        $rows = $xp->query('.//tr/td | .//tr/th', $item);

        foreach ($rows as $item) {
            /** @var $item DOMNode */
            yield trim($item->textContent);
        }
    }

    private function isNotElement(DOMNode $node)
    {
        return XML_ELEMENT_NODE !== $node->nodeType;
    }
}
