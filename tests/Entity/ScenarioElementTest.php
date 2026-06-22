<?php

namespace App\Tests\Entity;

use App\Entity\ScenarioElement;
use PHPUnit\Framework\TestCase;

class ScenarioElementTest extends TestCase
{
    public function testTitleIsTrimmed(): void
    {
        $element = new ScenarioElement();
        $element->title = '  Acte I  ';

        $this->assertEquals('Acte I', $element->title);
    }

    public function testDefaultDepthIsOne(): void
    {
        $element = new ScenarioElement();

        $this->assertEquals(1, $element->depth);
    }

    public function testDepthBelowOneThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $element = new ScenarioElement();
        $element->depth = 0;
    }

    public function testNegativeDurationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $element = new ScenarioElement();
        $element->durationSeconds = -1;
    }

    public function testIsLeafReturnsFalseByDefault(): void
    {
        $element = new ScenarioElement();

        $this->assertFalse($element->isLeaf());
    }

    public function testGetFullPathWithNoParent(): void
    {
        $element = new ScenarioElement();
        $element->title = 'Acte I';

        $this->assertEquals('Acte I', $element->getFullPath());
    }

    public function testGetFullPathWithParent(): void
    {
        $parent = new ScenarioElement();
        $parent->title = 'Acte I';

        $child = new ScenarioElement();
        $child->title = 'Séquence 1';
        $child->setParent($parent);

        $this->assertEquals('Acte I / Séquence 1', $child->getFullPath());
    }

    public function testGetAncestorsIsEmptyForRoot(): void
    {
        $element = new ScenarioElement();

        $this->assertEmpty($element->getAncestors());
    }

    public function testGetAncestorsReturnsChain(): void
    {
        $act = new ScenarioElement();
        $act->title = 'Acte I';

        $sequence = new ScenarioElement();
        $sequence->title = 'Séquence 1';
        $sequence->setParent($act);

        $scene = new ScenarioElement();
        $scene->title = 'Scène 1';
        $scene->setParent($sequence);

        $ancestors = $scene->getAncestors();

        $this->assertCount(2, $ancestors);
        $this->assertEquals('Acte I', $ancestors[0]->title);
        $this->assertEquals('Séquence 1', $ancestors[1]->title);
    }

    public function testAddChildSetsParent(): void
    {
        $parent = new ScenarioElement();
        $parent->title = 'Acte I';

        $child = new ScenarioElement();
        $child->title = 'Séquence 1';

        $parent->addChild($child);

        $this->assertSame($parent, $child->getParent());
    }

    public function testContentDefaultsToEmptyArray(): void
    {
        $element = new ScenarioElement();

        $this->assertIsArray($element->content);
        $this->assertEmpty($element->content);
    }
}
