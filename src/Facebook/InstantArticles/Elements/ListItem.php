<?hh // strict
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Elements;

/**
 * Class List that represents a simple HTML list
 *
 * Example:
 *     <li>Dog</li>
 */
class ListItem extends TextContainer
{
    private function __construct()
    {
    }

    /**
     * @return ListItem
     */
    public static function create(): ListItem
    {
        return new self();
    }

    /**
     * Structure and create the full ListItem <li> in a DOMNode.
     *
     * @param \DOMDocument $document - The document where this element will be appended (optional).
     *
     * @return \DOMNode
     */
    public function toDOMElement(\DOMDocument $document): \DOMNode
    {
        $list_item = $document->createElement('li');

        $list_item->appendChild($this->textToDOMDocumentFragment($document));

        return $list_item;
    }
}
