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

use DomDocument;
use DOMXPath;
use Exception;

class Formatter {
	private $dom;
	private $target = null;
	private $colorList = ['purple', 'green', 'yellow', 'aqua', 'red'];
	private $headerTag = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7'];
	private $colorSeek = 0;
	
	public function __construct($html){
		$this->dom = new DomDocument('1.0', 'UTF-8');
		@$this->dom->loadHtml('<meta charset="UTF-8"/>' . $html);
		$this->makeIndent($this->dom);
	}
	
	public function makeIndent(){
		$xpath = new DOMXPath($this->dom);
		$container = $this->dom->createElement('div');
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
		while($element){
			$nodeName = strtolower($element->nodeName);
			if(in_array($nodeName, $this->headerTag)){
				$currentLevel = intval(substr($nodeName, 1));
				if(is_integer($currentLevel) && $currentLevel > 0){
					//判断层级
					if($currentLevel > $indentLevel){ //向内缩进，增加dom
						for($i = $indentLevel + 1; $i <= $currentLevel; $i ++){
							//更新容器和颜色列表
							list($colorList[$i], $containerList[$i]) = $this->createIndentElement(
								isset($colorList[$i]) ? $colorList[$i] : false,
								isset($colorList[$i - 1]) ? $colorList[$i - 1] : false);
						}
						$currentContainer = $containerList[$currentLevel];
					} elseif($currentLevel < $indentLevel){ //向外推
						for($i = $indentLevel; $i >= $currentLevel; $i --){
							$containerList[$i - 1]->appendChild($containerList[$i]);
							unset($containerList[$i]);
						}
						list($colorList[$currentLevel], $containerList[$currentLevel]) = $this->createIndentElement($colorList[$currentLevel], $colorList[$currentLevel - 1]);
						$currentContainer = $containerList[$currentLevel];
					} else if($currentLevel == $indentLevel){ //向上一层append
						$containerList[$currentLevel - 1]->appendChild($currentContainer);
						list($colorList[$currentLevel], $containerList[$currentLevel]) = $this->createIndentElement($colorList[$currentLevel], $colorList[$currentLevel - 1]);
						$currentContainer = $containerList[$currentLevel];
					}
					$indentLevel = $currentLevel;
				}
			}
			$currentContainer->appendChild($element->cloneNode(true));
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
		//$this->dom->addChild($container);
	}
	
	public function createIndentElement($upColor = false, $leftColor = false){
		$exclude = [];
		if($upColor) $exclude[] = $upColor;
		if($leftColor) $exclude[] = $leftColor;
		$color = $this->getColor($exclude);
		$sectionClass = 'isekai-indent isekai-indent-' . $color;
		$sectionBody = $this->dom->createElement('div');
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
	
	public function getHtml(){
		$xpath = new DOMXPath($this->dom);
		$source = $xpath->query('body/div[@class="mw-parser-output"][1]');
		if(!$source->length){
			$source = $xpath->query('body');
			if(!$source->length){
				throw new FormatterException("HTML lacked body element even though we put it there ourselves");
			}
		}
		return $this->dom->saveHTML($source->item(0));
	}
}
