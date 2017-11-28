<?hh
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Transformer;

use Facebook\InstantArticles\Elements\InstantArticle;

use Facebook\InstantArticles\Elements\Header;
use Facebook\InstantArticles\Transformer\Rules\BoldRule;
use Facebook\InstantArticles\Transformer\Rules\H1Rule;
use Facebook\InstantArticles\Transformer\Rules\ItalicRule;
use Facebook\InstantArticles\Transformer\Rules\ParagraphRule;
use Facebook\InstantArticles\Transformer\Rules\TextNodeRule;

class TransformerTest extends \Facebook\Util\BaseHTMLTestCase
{
    public function testTransformString()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $title_html_string = '<h1>Title String</h1>';
        $header = Header::create();
        $transformer->transformString($header, $title_html_string);

        $title = $header->getTitle();
        invariant($title !== null, 'Should not be null');
        $this->assertEqualsHtml('<h1>Title String</h1>', $title->render());
    }

    public function testTransformStringWithMultibyteUTF8Content()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $title_html_string = '<h1>Test:あÖÄÜöäü</h1>';
        $header = Header::create();
        $transformer->transformString($header, $title_html_string);

        $title = $header->getTitle();
        invariant($title !== null, 'Should not be null');
        $this->assertEqualsHtml('<h1>Test:あÖÄÜöäü</h1>', $title->render());
    }

    public function testTransformStringWithMultibyteNonUTF8Content()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $title_html_string = mb_convert_encoding('<h1>Test:あÖÄÜöäü</h1>', 'euc-jp', 'utf-8');
        $header = Header::create();
        $transformer->transformString($header, $title_html_string, 'euc-jp');

        $title = $header->getTitle();
        invariant($title !== null, 'Should not be null');
        $this->assertEqualsHtml('<h1>Test:あÖÄÜöäü</h1>', $title->render());
    }

    public function testSelfTransformerContent()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $html_file = file_get_contents(__DIR__ . '/instant-article-example.html');

        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($html_file);
        libxml_use_internal_errors(false);

        $transformer->transform($instant_article, $document);
        $instant_article->addMetaProperty('op:generator:version', '1.0.0');
        $instant_article->addMetaProperty('op:generator:transformer:version', '1.0.0');
        $result = $instant_article->render('', true)."\n";

        //var_dump($result);
        // print_r($warnings);
        $this->assertEqualsHtml($html_file, $result);
    }

    public function testSelfTransformerMultibyteContent()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $html_file = file_get_contents(__DIR__ . '/instant-article-example-multibyte.html');

        $transformer->transformString($instant_article, $html_file, 'utf-8');
        $instant_article->withCanonicalUrl('http://foo.com/article.html');
        $instant_article->addMetaProperty('op:generator:version', '1.0.0');
        $instant_article->addMetaProperty('op:generator:transformer:version', '1.0.0');
        $result = $instant_article->render('', true)."\n";

        // some fragments are written as html entities even after transformed so
        // noralize all strings to html entities and compare them.
        $this->assertEqualsHtml(
            mb_convert_encoding($html_file, 'HTML-ENTITIES', 'utf-8'),
            mb_convert_encoding($result, 'HTML-ENTITIES', 'utf-8')
        );
    }

    public function testSelfTransformerNonUTF8Content()
    {
        $json_file = file_get_contents(__DIR__ . '/../../../../src/Facebook/InstantArticles/Parser/instant-articles-rules.json');

        $instant_article = InstantArticle::create();
        $transformer = new Transformer();
        $transformer->loadRules($json_file);

        $html_file = file_get_contents(__DIR__ . '/instant-article-example-nonutf8.html');

        $transformer->transformString($instant_article, $html_file, 'euc-jp');
        $instant_article->withCanonicalUrl('http://foo.com/article.html');
        $instant_article->addMetaProperty('op:generator:version', '1.0.0');
        $instant_article->addMetaProperty('op:generator:transformer:version', '1.0.0');
        $result = $instant_article->render('', true)."\n";

        // some fragments are written as html entities even after transformed so
        // noralize all strings to html entities and compare them.
        $this->assertEqualsHtml(
            mb_convert_encoding($html_file, 'HTML-ENTITIES', 'euc-jp'),
            mb_convert_encoding($result, 'HTML-ENTITIES', 'utf-8')
        );
    }

    public function testTransformerAddAndGetRules()
    {
        $transformer = new Transformer();
        $rule1 = new ParagraphRule();
        $rule2 = new ItalicRule();
        $transformer->addRule($rule1);
        $transformer->addRule($rule2);
        $this->assertEquals(array($rule1, $rule2), $transformer->getRules());
    }

    public function testTransformerSetRules()
    {
        $transformer = new Transformer();
        $rule1 = new ParagraphRule();
        $rule2 = new ItalicRule();
        $transformer->setRules(array($rule1, $rule2));
        $this->assertEquals(array($rule1, $rule2), $transformer->getRules());
    }

    public function testTransformerResetRules()
    {
        $transformer = new Transformer();
        $rule1 = new ParagraphRule();
        $rule2 = new ItalicRule();
        $transformer->addRule($rule1);
        $transformer->addRule($rule2);
        $transformer->resetRules();
        $this->assertEquals(array(), $transformer->getRules());
    }
}
