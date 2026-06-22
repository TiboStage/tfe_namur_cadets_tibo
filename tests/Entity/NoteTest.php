<?php

namespace App\Tests\Entity;

use App\Entity\Note;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function testDefaultStatusIsNote(): void
    {
        $note = new Note();

        $this->assertEquals('note', $note->getStatus());
    }

    public function testDefaultPriorityIsNormal(): void
    {
        $note = new Note();

        $this->assertEquals('normal', $note->getPriority());
    }

    public function testDefaultLinkedEntityTypeIsProject(): void
    {
        $note = new Note();

        $this->assertEquals('project', $note->getLinkedEntityType());
    }

    public function testLinkedEntityIdIsNullByDefault(): void
    {
        $note = new Note();

        $this->assertNull($note->getLinkedEntityId());
    }

    public function testDueDateIsNullByDefault(): void
    {
        $note = new Note();

        $this->assertNull($note->getDueDate());
    }

    public function testStatusCanBeChanged(): void
    {
        $note = new Note();

        foreach (['note', 'todo', 'done', 'archived'] as $status) {
            $note->setStatus($status);
            $this->assertEquals($status, $note->getStatus());
        }
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $note = new Note();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $note->getCreatedAt());
        $this->assertLessThanOrEqual($after, $note->getCreatedAt());
    }
}
