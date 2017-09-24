<?php

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Parser.php');

/**
 * Test cases testing the HTMLSafe visitor, which escapes all html characters in the source text
 *
 * @author astax-t
 */
class HTMLSafeTest extends PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the given bbcode string produces the given html string
     * when parsed with the default bbcodes.
     */
    public function assertProduces($bbcode, $html)
    {
        $parser = new \JBBCode\Parser();
        $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
        $parser->parse($bbcode);

		$htmlsafer = new JBBCode\visitors\HTMLSafeVisitor();
		$parser->accept($htmlsafer);

        $this->assertEquals($html, $parser->getAsHtml());
    }
	
    /**
     * Tests escaping quotes and ampersands in simple text
     */
    public function testQuoteAndAmp()
    {
        $this->assertProduces('te"xt te&xt', 'te&quot;xt te&amp;xt');
    }

    /**
     * Tests escaping quotes and ampersands inside a BBCode tag
     */
    public function testQuoteAndAmpInTag()
    {
        $this->assertProduces('[b]te"xt te&xt[/b]', '<strong>te&quot;xt te&amp;xt</strong>');
    }

    /**
     * Tests escaping HTML tags
     */
    public function testHtmlTag()
    {
        $this->assertProduces('<b>not bold</b>', '&lt;b&gt;not bold&lt;/b&gt;');
        $this->assertProduces('[b]<b>bold</b>[/b] <hr>', '<strong>&lt;b&gt;bold&lt;/b&gt;</strong> &lt;hr&gt;');
    }

    /**
     * Tests escaping ampersands in URL using [url]...[/url]
     */
    public function testUrlParam()
    {
        $this->assertProduces('text [url]http://example.com/?a=b&c=d[/url] more text', 'text <a href="http://example.com/?a=b&amp;c=d">http://example.com/?a=b&amp;c=d</a> more text');
    }

    /**
     * Tests escaping ampersands in URL using [url=...] tag
     */
    public function testUrlOption()
    {
        $this->assertProduces('text [url=http://example.com/?a=b&c=d]this is a "link"[/url]', 'text <a href="http://example.com/?a=b&amp;c=d">this is a &quot;link&quot;</a>');
    }

    /**
     * Tests escaping ampersands in URL using [url=...] tag when URL is in quotes
     */
    public function testUrlOptionQuotes()
    {
        $this->assertProduces('text [url="http://example.com/?a=b&c=d"]this is a "link"[/url]', 'text <a href="http://example.com/?a=b&amp;c=d">this is a &quot;link&quot;</a>');
    }

}
