<?php
namespace Isekai\Indent;

use MediaWiki\MediaWikiServices;
use User;

class Indent {
	/**
	 * @param \OutputPage $out
	 * @param string $text
	 */
	public static function onOutputPageBeforeHTML($out, &$text){
		$service = MediaWikiServices::getInstance();
		if($service->getUserOptionsLookup()->getOption($out->getUser(), 'isekai-show-indent')
				&& $out->getSkin()->getSkinName() != 'minerva'){
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
		$outputPage->addModuleStyles('ext.isekai.indent');
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
