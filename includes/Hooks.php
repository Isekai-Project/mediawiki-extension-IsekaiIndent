<?php
namespace Isekai\Indent;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MediaWiki\Output\OutputPage;

class Hooks {
	/**
	 * @param OutputPage $out
	 * @param string $text
	 */
	public static function onOutputPageBeforeHTML(OutputPage $out, &$text){
		$service = MediaWikiServices::getInstance();
		$config = $service->getMainConfig();

		$allowedSkins = $config->get('IsekaiIndentSkins');
		$fixTitleSkins = $config->get('IsekaiIndentFixTitleSkins');
		$needProcessSkins = array_merge($allowedSkins, $fixTitleSkins);

		$isView = $out->getContext()->getRequest()->getText( 'action', 'view' ) == 'view';
		if($service->getUserOptionsLookup()->getOption($out->getUser(), 'isekai-show-indent')
				&& $out->getTitle()->getNamespace() !== NS_SPECIAL
				&& $isView
				&& $out->getWikiPage()->getContentModel() == CONTENT_MODEL_WIKITEXT
				&& in_array($out->getSkin()->getSkinName(), $needProcessSkins)){
			$namespace = $out->getTitle()->getNamespace();
			if(in_array($namespace, [NS_MAIN, NS_PROJECT, NS_HELP, NS_USER])){
				try {
					$formatter = new Formatter($text);

					if (in_array($out->getSkin()->getSkinName(), $allowedSkins)) {
						$formatter->isMakeIndent = true;
					}

					if (in_array($out->getSkin()->getSkinName(), $fixTitleSkins)) {
						$formatter->isFixTitle = true;
					}

					$formatter->run();
					
					$text = $formatter->getHtml();
					return true;
				} catch(FormatterException $e){
					
				}
			}
		}
        return false;
	}
	
	public static function onBeforePageDisplay(OutputPage $outputPage){
		$outputPage->addModuleStyles(['ext.isekai.indent']);
		$outputPage->addModules(['ext.isekai.indent']);
	}

	public static function onGetPreferences(User $user, array &$preferences){
		$preferences['isekai-show-indent'] = [
			'type' => 'toggle',
			'label-message' => 'isekai-show-indent',
			'section' => 'rendering/isekai-indent',
		];
	}
}
