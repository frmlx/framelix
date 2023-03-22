<?php

namespace Html;

use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\FramelixTests\TestCase;

use function json_decode;
use function json_encode;

final class HtmlAttributesTest extends TestCase
{
    public function tests(): void
    {
        $attr = new HtmlAttributes();
        $attr->set('data-foo', '1');
        $attr->set('data-foo', null);
        $attr->setArray(['data-foo1' => '1']);
        $attr->setArray(['data-foo4' => '"']);
        $attr->setArray(['data-foo5' => '"\'']);
        $this->assertSame(null, $attr->get('data-foo'));
        $this->assertSame(null, $attr->get('data-foo3'));
        $this->assertSame('1', $attr->get('data-foo1'));
        // notice space at end, its intended to test this also
        $attr->addClass('blub blab ');
        $attr->removeClass('blub blab ');
        $attr->addClass('foo');
        $attr->setStyleArray(['color' => 'red']);
        $attr->setStyleArray(['color' => null]);
        $attr->setStyleArray(['background' => 'red']);
        $this->assertSame('red', $attr->getStyle('background'));
        $this->assertSame(
            'style="background:red;" class="foo" data-foo1="1" data-foo4=\'"\' data-foo5=\'"\'',
            (string)$attr
        );
        $this->assertSame(
            '{"phpProperties":{"styles":{"background":"red"},"classes":["foo"],"other":{"data-foo1":"1","data-foo4":"\"","data-foo5":"\"\'"}},"phpClass":"Framelix\\\\Framelix\\\\Html\\\\HtmlAttributes","jsClass":"FramelixHtmlAttributes"}',
            json_encode($attr)
        );

        $data = json_decode(json_encode($attr), true);
        $this->assertSame(
            '{"phpProperties":{"styles":{"background":"red"},"classes":["foo"],"other":{"data-foo1":"1","data-foo4":"\"","data-foo5":"\"\'"}},"phpClass":"Framelix\\\\Framelix\\\\Html\\\\HtmlAttributes","jsClass":"FramelixHtmlAttributes"}',
            json_encode(
                HtmlAttributes::create(
                    $data['phpProperties']['other'],
                    $data['phpProperties']['classes'],
                    $data['phpProperties']['styles']
                )
            )
        );
    }
}
