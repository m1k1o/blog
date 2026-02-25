<?php
namespace JBBCode;
defined('PROJECT_PATH') OR exit('No direct script access allowed');

/**
 * Erweiterte BBCode Definitionen mit vielen zusätzlichen Tags
 */
class DefaultCodeDefinitionSet implements CodeDefinitionSet
{
    protected $definitions = array();

    public function __construct()
    {
        // ============================================
        // TEXT FORMATIERUNG
        // ============================================
        
        // [b] Fettschrift
        $builder = new CodeDefinitionBuilder('b', '<strong>{param}</strong>');
        array_push($this->definitions, $builder->build());

        // [i] Kursivschrift
        $builder = new CodeDefinitionBuilder('i', '<em>{param}</em>');
        array_push($this->definitions, $builder->build());

        // [u] Unterstrichen
        $builder = new CodeDefinitionBuilder('u', '<u>{param}</u>');
        array_push($this->definitions, $builder->build());

        // [s] Durchgestrichen
        $builder = new CodeDefinitionBuilder('s', '<s>{param}</s>');
        array_push($this->definitions, $builder->build());

        // [size=20] Schriftgröße
        $builder = new CodeDefinitionBuilder('size', '<span style="font-size: {option}px;">{param}</span>');
        $builder->setUseOption(true);
        array_push($this->definitions, $builder->build());

        // [font=Arial] Schriftart
        $builder = new CodeDefinitionBuilder('font', '<span style="font-family: {option};">{param}</span>');
        $builder->setUseOption(true);
        array_push($this->definitions, $builder->build());

        // [color=red] Textfarbe
        $builder = new CodeDefinitionBuilder('color', '<span style="color: {option}">{param}</span>');
        $builder->setUseOption(true)->setOptionValidator(new \JBBCode\validators\CssColorValidator());
        array_push($this->definitions, $builder->build());

        // [highlight] Text hervorheben
        $builder = new CodeDefinitionBuilder('highlight', '<mark>{param}</mark>');
        array_push($this->definitions, $builder->build());

        // ============================================
        // TEXT AUSRICHTUNG
        // ============================================

        // [left] Linksbündig
        $builder = new CodeDefinitionBuilder('left', '<div style="text-align: left;">{param}</div>');
        array_push($this->definitions, $builder->build());

        // [center] Zentriert
        $builder = new CodeDefinitionBuilder('center', '<div style="text-align: center;">{param}</div>');
        array_push($this->definitions, $builder->build());

        // [right] Rechtsbündig
        $builder = new CodeDefinitionBuilder('right', '<div style="text-align: right;">{param}</div>');
        array_push($this->definitions, $builder->build());

        // [indent] Einrückung
        $builder = new CodeDefinitionBuilder('indent', '<div style="margin-left: 40px;">{param}</div>');
        array_push($this->definitions, $builder->build());

        // ============================================
        // LINKS UND MEDIEN
        // ============================================

        $urlValidator = new \JBBCode\validators\UrlValidator();

        // [url] Link ohne Text
        $builder = new CodeDefinitionBuilder('url', '<a href="{param}" target="_blank" rel="noopener">{param}</a>');
        $builder->setParseContent(false)->setBodyValidator($urlValidator);
        array_push($this->definitions, $builder->build());

        // [url=http://example.com] Link mit Text
        $builder = new CodeDefinitionBuilder('url', '<a href="{option}" target="_blank" rel="noopener">{param}</a>');
        $builder->setUseOption(true)->setParseContent(true)->setOptionValidator($urlValidator);
        array_push($this->definitions, $builder->build());

        // [email] E-Mail Adresse
        $builder = new CodeDefinitionBuilder('email', '<a href="mailto:{param}">{param}</a>');
        $builder->setParseContent(false);
        array_push($this->definitions, $builder->build());

        // [img] Bild ohne Alt-Text
        $builder = new CodeDefinitionBuilder('img', '<img src="{param}" style="max-width: 100%; height: auto;" />');
        $builder->setUseOption(false)->setParseContent(false)->setBodyValidator($urlValidator);
        array_push($this->definitions, $builder->build());

        // [img=alt text] Bild mit Alt-Text
        $builder = new CodeDefinitionBuilder('img', '<img src="{param}" alt="{option}" style="max-width: 100%; height: auto;" />');
        $builder->setUseOption(true)->setParseContent(false)->setBodyValidator($urlValidator);
        array_push($this->definitions, $builder->build());

        // [video] Video einbetten
        $builder = new CodeDefinitionBuilder('video', '<video controls style="max-width: 100%;"><source src="{param}">Your browser does not support video.</video>');
        $builder->setParseContent(false)->setBodyValidator($urlValidator);
        array_push($this->definitions, $builder->build());

        // ============================================
        // STRUKTUR & ORGANISATION
        // ============================================

        // [quote] Zitat
        $builder = new CodeDefinitionBuilder('quote', '<blockquote style="border-left: 3px solid #ccc; padding-left: 15px; margin: 10px 0; color: #666;">{param}</blockquote>');
        array_push($this->definitions, $builder->build());

        // [quote=Autor] Zitat mit Autor
        $builder = new CodeDefinitionBuilder('quote', '<blockquote style="border-left: 3px solid #ccc; padding-left: 15px; margin: 10px 0; color: #666;"><strong>{option} schrieb:</strong><br>{param}</blockquote>');
        $builder->setUseOption(true);
        array_push($this->definitions, $builder->build());

        // [spoiler] Versteckter Text
        $builder = new CodeDefinitionBuilder('spoiler', '<details style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;"><summary style="cursor: pointer; font-weight: bold;">Spoiler (klicken zum Anzeigen)</summary><div style="margin-top: 10px;">{param}</div></details>');
        array_push($this->definitions, $builder->build());

        // [hr] Horizontale Linie
        $builder = new CodeDefinitionBuilder('hr', '<hr style="border: none; border-top: 1px solid #ccc; margin: 15px 0;">');
        $builder->setParseContent(false);
        array_push($this->definitions, $builder->build());

        // ============================================
        // LISTEN
        // ============================================

        // [list] Ungeordnete Liste
        $builder = new CodeDefinitionBuilder('list', '<ul style="margin: 10px 0; padding-left: 30px;">{param}</ul>');
        array_push($this->definitions, $builder->build());

        // [list=1] Nummerierte Liste
        $builder = new CodeDefinitionBuilder('list', '<ol type="1" style="margin: 10px 0; padding-left: 30px;">{param}</ol>');
        $builder->setUseOption(true);
        array_push($this->definitions, $builder->build());

        // [*] Listenelement (wird durch Regex ersetzt)
        // Wird in parse_content in post.class.php behandelt

        // ============================================
        // CODE BLÖCKE
        // ============================================

        // [code] Einfacher Code-Block
        $builder = new CodeDefinitionBuilder('code', '<pre style="background: #f5f5f5; border: 1px solid #ddd; padding: 10px; overflow-x: auto; border-radius: 4px;"><code>{param}</code></pre>');
        $builder->setParseContent(false);
        array_push($this->definitions, $builder->build());

        // [php] PHP Code-Block
        $builder = new CodeDefinitionBuilder('php', '<pre style="background: #f5f5f5; border: 1px solid #ddd; padding: 10px; overflow-x: auto; border-radius: 4px;"><code class="language-php">{param}</code></pre>');
        $builder->setParseContent(false);
        array_push($this->definitions, $builder->build());
    }

    public function getCodeDefinitions() 
    {
        return $this->definitions;
    }
}