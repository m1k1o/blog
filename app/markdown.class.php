<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

// Markdown parser for posts, see `formatting` in config
class Markdown extends Parsedown
{
	public function __construct(){
		// Raw HTML is escaped and links with unsafe protocols (e.g. javascript:) are removed
		$this->setSafeMode(true);

		// New lines are kept, as they are with BBCode
		$this->setBreaksEnabled(true);

		// Headers need a space after #, so that #tags at the beginning of a line are not headers
		$this->setStrictMode(true);

		// #tags can be clicked to filter posts
		$this->InlineTypes['#'][] = 'Hashtag';
		$this->inlineMarkerList .= '#';
	}

	protected function inlineHashtag($Excerpt){
		if(preg_match('/^#[A-Za-z0-9-_]+(?=\s|$)/', $Excerpt['text'], $matches)){
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'span',
					'text' => $matches[0],
					'attributes' => [
						'class' => 'tag'
					]
				]
			];
		}
	}

	protected function element(array $Element){
		// External links are opened in a new tab, links within the blog (e.g. #tag=songs) are not
		if(isset($Element['name']) && $Element['name'] === 'a' && preg_match('/^https?:\/\//i', $Element['attributes']['href'] ?? '')){
			$Element['attributes']['target'] = '_blank';
		}

		return parent::element($Element);
	}
}
