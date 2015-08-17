<?php

/**
 * PHP Readability
 *
 * Readability PHP version, see
 *      http://code.google.com/p/arc90labs-readability/
 *
 * ChangeLog:
 *      [+] 2014-02-08 Add lead image param and improved get title function.
 *      [+] 2013-12-04 Better error handling and junk tag removal.
 *      [+] 2011-02-17 Initialization version
 *
 * @date   2013-12-04
 * 
 * @author mingcheng<i.feelinglucky#gmail.com>
 * @link   http://www.gracecode.com/
 * 
 * @author Tuxion <team#tuxion.nl>
 * @link   http://tuxion.nl/
 */

class Readability {
    // Save determination result flag name
    const ATTR_CONTENT_SCORE = "contentScore";

    // DOM parsing classes currently only supports UTF-8 encoding
    const DOM_DEFAULT_CHARSET = "utf-8";

    // When it is determined to display the contents of failure
    const MESSAGE_CAN_NOT_GET = "Readability was unable to parse this page for content.";

    // DOM parsing classes (PHP5 already built-in)
    protected $DOM = null;

    // To parse the source code
    protected $source = "";

    // Parent element lists in the section
    protected $parentNodes = array();

    // Tags to remove.
    protected $junkTags = Array("style", "form", "iframe", "script", "button", "input", "textarea",
                                "noscript", "select", "option", "object", "applet", "basefont",
                                "bgsound", "blink", "canvas", "command", "menu", "nav", "datalist",
                                "embed", "frame", "frameset", "keygen", "label", "marquee", "link");

    // Properties to remove.
    protected $junkAttrs = Array("style", "class", "onclick", "onmouseover", "align", "border", "margin");


    /**
     * Constructor
     * @param $source
     * @param $input_char string. The default utf-8, can be omitted
    */
    function __construct($source, $input_char = "utf-8") {
        $this->source = $source;

        // DOM parsing classes can handle UTF-8 character format
        $source = mb_convert_encoding($source, 'HTML-ENTITIES', $input_char);

        // Pretreatment HTML tags, remove redundant labels
        $source = $this->prepareSource($source);

        // Generate DOM parsing classes
        $this->DOM = new DOMDocument('1.0', $input_char);
        try {
            //libxml_use_internal_errors(true);
            // It will be some error message , but it does not matter :^)
            if (!@$this->DOM->loadHTML('<?xml encoding="'.Readability::DOM_DEFAULT_CHARSET.'">'.$source)) {
                throw new Exception("Parse HTML Error!");
            }

            foreach ($this->DOM->childNodes as $item) {
                if ($item->nodeType == XML_PI_NODE) {
                    $this->DOM->removeChild($item); // remove hack
                }
            }

            // insert proper
            $this->DOM->encoding = Readability::DOM_DEFAULT_CHARSET;
        } catch (Exception $e) {
            // ...
        }
    }


    /**
     * Pretreatment HTML tags , so that it can be processed accurately DOM parsing classes
     *
     * @param string String
     * @return String
     */
    private function prepareSource($string) {

        // Excluding the extra HTML coding marked to avoid parsing error
        preg_match("/charset=([\\w|\\-]+);?/", $string, $match);

        if (isset($match[1])) {
            $string = preg_replace("/charset=([\\w|\\-]+);?/", "", $string, 1);
        }

        // Replace all doubled-up <BR> tags with <P> tags, and remove fonts.
        $string = preg_replace("#<br/?>[ \\r\\n\\s]*<br/?>#i", "</p><p>", $string);
        $string = preg_replace("#</?font[^>]*>#i", "", $string);

        // @see https://github.com/feelinglucky/php-readability/issues/7
        //   - from http://stackoverflow.com/questions/7130867/remove-script-tag-from-html-content
        $string = preg_replace("#<script(.*?)>(.*?)</script>#is", "", $string);

        return trim($string);
    }


    /**
     * Remove all of the DOM element $TagName tag
     *
     * @param $RootNode
     * @param $TagName
     * @return DOMDocument
     */
    private function removeJunkTag($RootNode, $TagName) {
        
        $Tags = $RootNode->getElementsByTagName($TagName);
        
        // Note: always index 0, because removing a tag removes it from the results as well.
        while($Tag = $Tags->item(0)){
            $parentNode = $Tag->parentNode;
            $parentNode->removeChild($Tag);
        }
        
        return $RootNode;
        
    }

    /**
     * Remove all unnecessary elements attributes
     */
    private function removeJunkAttr($RootNode, $Attr) {
        $Tags = $RootNode->getElementsByTagName("*");

        $i = 0;
        while($Tag = $Tags->item($i++)) {
            $Tag->removeAttribute($Attr);
        }

        return $RootNode;
    }

    /**
     * According to the main contents page ratings,
     * get box model determination algorithm from：http://code.google.com/p/arc90labs-readability/
     *
     * @return DOMNode
     */
    protected function getTopBox() {
        // Get all the chapters page
        $allParagraphs = $this->DOM->getElementsByTagName("p");

        // Study all the paragraphs and find the chunk that has the best score.
        // A score is determined by things like: Number of <p>'s, commas, special classes, etc.
        $i = 0;
        while($paragraph = $allParagraphs->item($i++)) {
            $parentNode   = $paragraph->parentNode;
            $contentScore = intval($parentNode->getAttribute(Readability::ATTR_CONTENT_SCORE));
            $className    = $parentNode->getAttribute("class");
            $id           = $parentNode->getAttribute("id");

            $contentScore += $this->scoreClassName($className);
            $contentScore += $this->scoreID($id);

            // Add a point for the paragraph found
            // Add points for any commas within this paragraph
            if (strlen($paragraph->nodeValue) > 10) {
                $contentScore += strlen($paragraph->nodeValue);
            }

            // Save the parent element determination score
            $parentNode->setAttribute(Readability::ATTR_CONTENT_SCORE, $contentScore);

            // Save chapters of the parent element, so that the next quick access
            array_push($this->parentNodes, $parentNode);
        }

        $topBox = null;
        
        // Assignment from index for performance. 
        //     See http://www.peachpit.com/articles/article.aspx?p=31567&seqNum=5 
        for ($i = 0, $len = sizeof($this->parentNodes); $i < $len; $i++) {
            $parentNode      = $this->parentNodes[$i];
            $contentScore    = intval($parentNode->getAttribute(Readability::ATTR_CONTENT_SCORE));
            $orgContentScore = intval($topBox ? $topBox->getAttribute(Readability::ATTR_CONTENT_SCORE) : 0);

            if ($contentScore && $contentScore > $orgContentScore) {
                $topBox = $parentNode;
            }
        }
        
        // At this time, $topBox should have determined that the content of the main elements of a page after
        return $topBox;
    }


    /**
     * Get HTML page title
     *
     * @return String
     */
    public function getTitle() {
        $split_point = ' - ';
        $titleNodes = $this->DOM->getElementsByTagName("title");

        if ($titleNodes->length && $titleNode = $titleNodes->item(0)) {
            // @see http://stackoverflow.com/questions/717328/how-to-explode-string-right-to-left
            $title  = trim($titleNode->nodeValue);
            $result = array_map('strrev', explode($split_point, strrev($title)));
            return sizeof($result) > 1 ? array_pop($result) : $title;
        }

        return null;
    }


    /**
     * Get Leading Image Url
     *
     * @param $node
     * @return String
     */
    public function getLeadImageUrl($node) {
        $images = $node->getElementsByTagName("img");

        if ($images->length && $leadImage = $images->item(0)) {
            return $leadImage->getAttribute("src");
        }

        return null;
    }


    /**
     * Get the main content of the page (Readability after content)
     *
     * @throws RuntimeException
     * @return Array
     */
    public function getContent() {

        if (!$this->DOM) return false;

        // Get page title
        $ContentTitle = $this->getTitle();

        // Get page main content
        $ContentBox = $this->getTopBox();
        
        // Check if we found a suitable top-box.
        if($ContentBox === null) {
            throw new RuntimeException(Readability::MESSAGE_CAN_NOT_GET);
        }
        
        // Copy the contents to the new DOMDocument
        $Target = new DOMDocument;
        $Target->appendChild($Target->importNode($ContentBox, true));

        // Remove unwanted tag
        foreach ($this->junkTags as $tag) {
            $Target = $this->removeJunkTag($Target, $tag);
        }

        // Delete unneeded property
        foreach ($this->junkAttrs as $attr) {
            $Target = $this->removeJunkAttr($Target, $attr);
        }

        $content = mb_convert_encoding($Target->saveHTML(), Readability::DOM_DEFAULT_CHARSET, "HTML-ENTITIES");

        // A plurality of data, in the form of an array of return
        return array(
            'lead_image_url' => $this->getLeadImageUrl($Target),
            'word_count' => mb_strlen(strip_tags($content), Readability::DOM_DEFAULT_CHARSET),
            'title' => $ContentTitle ? $ContentTitle : null,
            'content' => $content
        );
    }

    /**
     * Gets meta data from the current document.
     *
     * @param $metaName string
     * @return string
     * @throws DOMException
     */
    public function getMeta($metaName) {

        $content = '';

        if (!$this->DOM) {
            throw new DOMException('DOM not read.');
        }

        $metaNodes = $this->DOM->getElementsByTagName("meta");

        if ($metaNodes->length) {
            foreach ($metaNodes as $metaNode) {
                if ($metaNode->getAttribute('name') == $metaName) {
                    $content = $metaNode->getAttribute('content');
                    break;
                }
            }
        }

        return $content;
    }

    protected function scoreClassName($className) {

        // Look for a special classname

        if (preg_match("/(comment|meta|footer|footnote)/i", $className)) {
            return -50;
        }

        // hentry: http://microformats.org/wiki/hentry

        if (preg_match("/((^|\\s)(post|hentry|(entry|article)[-]?(content|text|body)?)(\\s|$))/i", $className)) {
            return 25;
        }

        return 0;
    }

    protected function scoreID($id) {

        // Look for a special ID
        if (preg_match("/(comment|meta|footer|footnote)/i", $id)) {
            return -50;
        }

        if (preg_match("/^(post|hentry|(entry|article)[-]?(content|text|body)?)$/i", $id)) {
            return 25;
        }

        return 0;
    }
}

