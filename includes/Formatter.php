<?php
/*
结构：
<div class="isekai-indent-n">
	<h1>标题</h1>
	<div class="isekai-indent-n">
		内容
	</div>
</div>
*/
namespace Isekai\Indent;

use DOMXPath;
use HtmlFormatter\HtmlFormatter;

class Formatter extends HtmlFormatter {
	public $isMakeIndent = false;
	public $isFixTitle = false;

	private $target = null;
	private $colorList = ['purple', 'green', 'yellow', 'aqua', 'red'];
	private $headerTag = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7'];
	private $colorSeek = 0;
	
	public function __construct($html){
		$html = self::wrapHTML($html);
		parent::__construct($html);
	}
	
	public function run() {
		$doc = $this->getDoc();
		$xpath = new DOMXPath($doc);
		$container = $doc->createElement('div');
		$container->setAttribute('class', 'mw-parser-output');
		$source = $xpath->query('body/div[@class="mw-parser-output"][1]');
		if(!$source->length){
			$source = $xpath->query('body');
			if(!$source->length){
				throw new FormatterException("HTML lacked body element even though we put it there ourselves");
			}
		}
		$source = $source->item(0);
		$indentLevel = 0;
		$currentLevel = 0;
		$containerList = [$container];
		$colorList = [false];
		$currentContainer = $container;
		$element = $source->firstChild;
		while ($element) {
			$isHeading = false;
			$nodeName = strtolower($element->nodeName);
			if(in_array($nodeName, $this->headerTag)){
				$currentLevel = intval(substr($nodeName, 1));

				if (is_integer($currentLevel) && $currentLevel > 0) {
					$isHeading = true;

					if ($this->isMakeIndent) {
						// 判断层级
						if($currentLevel > $indentLevel){ // 向内缩进，增加dom
							for($i = $indentLevel + 1; $i <= $currentLevel; $i ++){
								// 更新容器和颜色列表
								list($colorList[$i], $containerList[$i]) = $this->createIndentElement(
									isset($colorList[$i]) ? $colorList[$i] : false,
									isset($colorList[$i - 1]) ? $colorList[$i - 1] : false);
							}
							$currentContainer = $containerList[$currentLevel];
						} elseif($currentLevel < $indentLevel){ // 向外推
							for($i = $indentLevel; $i >= $currentLevel; $i --){
								$containerList[$i - 1]->appendChild($containerList[$i]);
								unset($containerList[$i]);
							}
							list($colorList[$currentLevel], $containerList[$currentLevel]) = $this->createIndentElement($colorList[$currentLevel], $colorList[$currentLevel - 1]);
							$currentContainer = $containerList[$currentLevel];
						} else if($currentLevel == $indentLevel){ // 向上一层append
							$containerList[$currentLevel - 1]->appendChild($currentContainer);
							list($colorList[$currentLevel], $containerList[$currentLevel]) = $this->createIndentElement($colorList[$currentLevel], $colorList[$currentLevel - 1]);
							$currentContainer = $containerList[$currentLevel];
						}
						$indentLevel = $currentLevel;
					}
				}
			}
			if ($isHeading && $this->isFixTitle) {
				$currentContainer->appendChild($this->fixHeadingElement($element->cloneNode(true)));
			} else {
				$currentContainer->appendChild($element->cloneNode(true));
			}
			$element = $element->nextSibling;
		}
		for($i = $indentLevel; $i > 0; $i --){
			$containerList[$i - 1]->appendChild($containerList[$i]);
		}
		unset($containerList);
		$this->target = $container;
		$parentContainer = $source->parentNode;
		$parentContainer->removeChild($source);
		$parentContainer->appendChild($container);
	}

	/**
	 * @param \DOMNode $node
	 */
	public function fixHeadingElement($node) {
		$nodeName = strtolower($node->nodeName);
		$currentLevel = intval(substr($nodeName, 1));
		$newLevel = $currentLevel + 1;

		if ($newLevel >= 6) {
			$nodeName = 'span';
		} else {
			$nodeName = "h$newLevel";
		}

		$newNode = $node->ownerDocument->createElement($nodeName);
		while ($node->childNodes->length > 0) {
			$newNode->appendChild($node->childNodes->item(0));
		}

		$classChanged = false;
		foreach ($node->attributes as $attrName => $attrNode) {
			if ($attrName === 'class') {
				$classList = explode(' ', $attrNode->value);
				$classList = array_filter($classList, function ($className) {
					return !preg_match("/^heading-\d+$/", $className);
				});
				$classList[] = "heading-$currentLevel";
				$attrNode->value = implode(' ', $classList);
				$classChanged = true;
			}
			$newNode->setAttribute($attrName, $attrNode->value);
		}
		if (!$classChanged) {
			$newNode->setAttribute('class', "heading-$currentLevel");
		}
		return $newNode;
	}
	
	public function createIndentElement($upColor = false, $leftColor = false){
		$exclude = [];
		if($upColor) $exclude[] = $upColor;
		if($leftColor) $exclude[] = $leftColor;
		$color = $this->getColor($exclude);
		$sectionClass = 'isekai-indent isekai-indent-' . $color;
		$sectionBody = $this->getDoc()->createElement('div');
		$sectionBody->setAttribute('class', $sectionClass);
		return [$color, $sectionBody];
	}
	
	//选择最合适的颜色（感谢解决四色问题的数学家们）
	public function getColor($exclude = []){
		$length = count($this->colorList);
		for($i = 0; $i < $length; $i ++){
			if($this->colorSeek >= $length){
				$this->colorSeek = 0;
			}
			
			if(!in_array($this->colorList[$this->colorSeek], $exclude)){
				$this->colorSeek ++;
				return $this->colorList[$this->colorSeek - 1];
			} else {
				$this->colorSeek ++;
			}
		}
		return $this->colorList[0];
	}

    /**
     * @throws \Isekai\Indent\FormatterException
     * @return string
     */
    public function getHtml(){
		$xpath = new DOMXPath($this->getDoc());
		$source = $xpath->query('body/div[@class="mw-parser-output"][1]');
		if(!$source->length){
			$source = $xpath->query('body');
			if(!$source->length){
				throw new FormatterException("HTML lacked body element even though we put it there ourselves");
			}
		}
		return parent::getText($source->item(0));
	}
}
