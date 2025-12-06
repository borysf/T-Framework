<?php
namespace System\Web\Page\Control\Core;

use DOMDocument;
use DOMXPath;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TTemplateLiteral;

class THead extends TControl {
	const HTML_TAG_NAME = 'head';
	const HTML_HAS_END_TAG = true;
	const CHILDREN_TYPES_ALLOW = [TTemplateLiteral::class];

	private $dom;
	private $xp;

	private function _createDom() : void {
		if ($this->dom) {
			return;
		}

		$this->dom = new DOMDocument;
		@ob_start();
		parent::renderContents();
		$content = @ob_get_clean();
		$this->dom->loadXML("<head>".$content.'</head>');
		$this->xp = new DOMXPath($this->dom);
	}

	public function setTitle($title) : void {
		$this->_createDom();

		$nodes = $this->dom->getElementsByTagName('title');

		if($nodes->length > 0)
		{
			$nodes->item(0)->nodeValue = htmlspecialchars(htmlspecialchars($title));
		}
	}

	public function getTitle() : ?string
	{
		$this->_createDom();

		$nodes = $this->dom->getElementsByTagName('title');

		if($nodes->length > 0)
		{
			return html_entity_decode($nodes->item(0)->nodeValue);
		}
	}

	public function setMeta($name, $content) : void
	{
		$this->_createDom();

		$nodes = $this->xp->Query('//head/meta[@name="'.$name.'"]');

		if($nodes->length > 0)
		{
			$nodes->item(0)->setAttribute('content', $content);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('meta');
			$node->setAttribute('name', $name);
			$node->setAttribute('content', htmlspecialchars($content));
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}

	public function getMeta($name) : ?string
	{
		$this->_createDom();

		$nodes = $this->xp->Query('//head/meta[@name="'.$name.'"]');

		if($nodes->length > 0)
		{
			return html_entity_decode($nodes->item(0)->getAttribute('content'));
		}
	}

	public function setMetaHttpEquiv($name, $content) : void
	{
		$this->_createDom();

		$nodes = $this->xp->Query('//head/meta[@http-equiv="'.$name.'"]');

		if($nodes->length > 0)
		{
			$nodes->item(0)->setAttribute('content', $content);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('meta');
			$node->setAttribute('http-equiv', $name);
			$node->setAttribute('content', $content);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}

	public function linkCss($css) : void
	{
		$this->_createDom();

		$this->link($css, 'stylesheet', 'text/css');
	}

	public function linkScript($href, array $attributes = []) : void
	{
		$this->_createDom();

		$nodes = $this->xp->Query('//head/script[@src="'.$href.'"]');

		if($nodes->length > 0)
		{
            $node = $nodes->item(0);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('script');
			$node->setAttribute('src', $href);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}

        foreach ($attributes as $attribute => $value) {
            $node->setAttribute($attribute, $value);
        }
	}

	public function link($href, $rel, $type, $title = '') : void
	{
		$this->_createDom();

		$nodes = $this->xp->Query('//head/link[@href="'.$href.'"]');

		if($nodes->length > 0)
		{
			if($title) $nodes->item(0)->setAttribute('title', $title);
			$nodes->item(0)->setAttribute('rel', $rel);
			$nodes->item(0)->setAttribute('type', $type);
		}
		else
		{
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\t"));
			$node = $this->dom->createElement('link');
			if($title) $node->setAttribute('title', $title);
			$node->setAttribute('rel', $rel);
			$node->setAttribute('href', $href);
			$node->setAttribute('type', $type);
			$this->dom->documentElement->appendChild($node);
			$this->dom->documentElement->appendChild($this->dom->createTextNode("\n"));
		}
	}

	public function render() : void {
		if($this->dom)
		{
			$code = html_entity_decode($this->dom->saveXML(), ENT_COMPAT, T_DEFAULT_CHARSET);
			$code = preg_replace('{<\?.*\?>}', '', $code);
			$code = preg_replace('{(<script)(.*)(/>)}','\\1\\2></script>', $code);
			$code = preg_replace('{(<script.+)(defer="[^"]*")(.*>)}','\\1defer\\3', $code);
			$code = preg_replace('{(<script.+)(async="[^"]*")(.*>)}','\\1async\\3', $code);

			echo $code;
		}
		else
		{
			parent::render();
		}
	}
}
