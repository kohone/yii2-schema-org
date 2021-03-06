<?php
/**
 * @link https://github.com/simialbi/yii2-schema-org
 * @copyright Copyright (c) 2017 Simon Karlen
 * @license MIT
 */

namespace simialbi\yii2\schemaorg\commands;

use yii\base\Exception;
use yii\console\Controller;

/**
 * Class SchemaOrgController
 *
 * @package simialbi\yii2\schemaorg\commands
 * @author Simon Karlen <simi.albi@gmail.com>
 *
 * @property \simialbi\yii2\schemaorg\Module $module
 */
class SchemaOrgController extends Controller {
	public $defaultAction = 'generate';

	/**
	 * Load schema.org object tree and generate model files
	 */
	public function actionGenerate() {
		$tree = $this->loadTree();

		$this->stdout("Generating Models...\n");
		$this->generateModelsFromTree($tree);
	}

	/**
	 * Load tree from schema.org
	 *
	 * @return array|boolean
	 * @throws Exception
	 */
	protected function loadTree() {
		$source = $this->module->source;
		$this->stdout("Loading Thing tree from '{$source}'...\n");

		$dom = new \DOMDocument();
		if (!@$dom->loadHTMLFile($source)) {
			throw new Exception("Failed to load source: $source");
		}
		$xquery = new \DOMXPath($dom);
		$html   = $dom->saveHTML($xquery->query('//*[@id="thing_tree"]/ul')->item(0));

		return $this->parseTree($html);
	}

	/**
	 * Generates models out of tree
	 *
	 * @param array $tree
	 * @param string $parent
	 */
	protected function generateModelsFromTree(array $tree, $parent = 'Model') {
		$matches   = [];
		$className = null;
		foreach ($tree as $item) {
			if (is_array($item)) {
				$this->generateModelsFromTree($item, $className);
			} elseif (is_string($item)) {
				if (preg_match('#<a href="([^"]+)">((?:[A-Z][a-z]+)+)</a>#', $item, $matches)) {
					$url       = $matches[1];
					$className = $matches[2];

					$this->stdout("Generate model '{$className}'\n");
					$this->generateModel($url, $className, $parent);
				}
			}
		}
	}

	/**
	 * Parse schema.org description and write model
	 *
	 * @param string $url
	 * @param string $className
	 * @param string $parent
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function generateModel($url, $className, $parent = 'Model') {
		$sourceUrl = parse_url($this->module->source);
		$url       = $sourceUrl['scheme'].'://'.$sourceUrl['host'].$url;

		$this->stdout("Load properties from '{$url}'\n");

		$dom = new \DOMDocument();
		if (!@$dom->loadHTMLFile($url)) {
			$this->stderr("Failed to load source for '{$className}': $url\n");

			return false;
		}
		$xquery     = new \DOMXPath($dom);
		$list       = $xquery->query('//*[@id="mainContent"]/table[1]/tbody[1]/tr');
		$properties = [];

		if ($parent === 'Model' || (
				$list->length &&
				$list->item($list->length - 1)->hasAttribute('class') &&
				$list->item($list->length - 1)->attributes->getNamedItem('class')->nodeValue === 'supertype')
		) {
			foreach ($list as $item) {
				/* @var $item \DOMElement */
				if (!$item->hasAttribute('typeof') || (string) $item->attributes->getNamedItem('typeof')->nodeValue !== 'rdfs:Property') {
					continue;
				}

				$tdIndex  = 0;
				$property = [];
				foreach ($item->childNodes as $node) {
					/* @var $node \DOMElement */
					if ($node->nodeType === XML_TEXT_NODE) {
						continue;
					}

					if (strcasecmp($node->nodeName, 'th') === 0) {
						$property['name'] = trim($node->textContent);
					} elseif (strcasecmp($node->nodeName, 'td') === 0) {
						if ($tdIndex++ === 0) {
							$property['type'] = preg_replace(
								'#(string|integer|float|boolean)(?:\|\1)+#',
								'$1',
								str_replace([
								'Boolean',
								'False',
								'True',
								'DateTime',
								'Date',
								'Time',
								'Number',
								'Float',
								'Integer',
								'Text',
								'URL'
							], [
								'boolean',
								'boolean',
								'boolean',
								'string',
								'string',
								'string',
								'integer',
								'float',
								'integer',
								'string',
								'string'
							], trim(implode('|', array_map(function($item) {
								return trim($item, " \t\n\r\0\x0B\xC2\xA0");
							}, explode(' or ', trim($node->textContent)))))));
						} else {
							$property['description'] = trim($node->textContent);
						}
					}
				}

				$properties[] = $property;
			}
		}

		$phpcode = $this->renderPartial('model-template', [
			'parent'     => $parent,
			'className'  => $className,
			'url'        => $url,
			'properties' => $properties
		]);

		$modelDir = realpath(__DIR__.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR.'models';
		if ((!file_exists($modelDir) || !is_dir($modelDir)) && !mkdir($modelDir)) {
			throw new Exception("Could not create directory '{$modelDir}'");
		}

		$filePath = $modelDir.DIRECTORY_SEPARATOR.$className.'.php';
		if (file_put_contents($filePath, $phpcode)) {
			$this->stdout("File '$filePath' written\n");
		} else {
			$this->stderr("Could not write file '$filePath'\n");
		}

		return true;
	}

	/**
	 * Parse unordered list tree structure and returns as array
	 *
	 * @param string|\SimpleXMLElement $ul
	 *
	 * @return array|bool array tree or false
	 * @throws Exception
	 */
	private function parseTree($ul) {
		if (is_string($ul)) {
			if (!$ul = simplexml_load_string($ul)) {
				throw new Exception("Syntax error in UL/LI structure");
			}

			return $this->parseTree($ul);
		} elseif (is_object($ul)) {
			$output = [];
			foreach ($ul->li as $li) {
				foreach ($li->children() as $tag => $child) {
					/* @var $child \SimpleXMLElement */
					if (strcasecmp($tag, 'ul') === 0) {
						$output[] = $this->parseTree($child);
					} else {
						$output[] = $child->asXML();
					}
				}
			}

			return $output;
		}

		return false;
	}
}