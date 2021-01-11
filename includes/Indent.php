<?php
namespace Isekai\Indent;

use User;

class Indent {
	public static function onOutputPageBeforeHTML(\OutputPage $out, &$text){
		if($out->getUser()->getOption('isekai-show-indent') && $out->getSkin()->getSkinName() != 'minerva'){
			$namespace = $out->getTitle()->getNamespace();
			if(in_array($namespace, [0])){
				try {
					$formatter = new Formatter($text);
					$text = $formatter->getHtml();
					return true;
				} catch(FormatterException $e){
					
				}
			}
		}
	}
	
	public static function onBeforePageDisplay($outputPage){
		$outputPage->addModules('ext.isekai.indent');
	}

	public static function onGetPreferences(User $user, array &$preferences){
		$preferences['isekai-show-indent'] = [
			'type' => 'toggle',
			'label-message' => 'isekai-show-indent',
			'section' => 'rendering/isekai-indent',
		];
	}
}
