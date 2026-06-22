<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testDefaultStatusIsTodo(): void
    {
        $task = new Task();

        $this->assertEquals('todo', $task->getStatus());
    }

    public function testDefaultPriorityIsNormal(): void
    {
        $task = new Task();

        $this->assertEquals('normal', $task->getPriority());
    }

    public function testStatusCanBeChanged(): void
    {
        $task = new Task();

        foreach (['todo', 'in_progress', 'review', 'done'] as $status) {
            $task->setStatus($status);
            $this->assertEquals($status, $task->getStatus());
        }
    }

    public function testPriorityCanBeChanged(): void
    {
        $task = new Task();

        foreach (['low', 'normal', 'high', 'urgent'] as $priority) {
            $task->setPriority($priority);
            $this->assertEquals($priority, $task->getPriority());
        }
    }

    public function testAssignedToIsNullByDefault(): void
    {
        $task = new Task();

        $this->assertNull($task->getAssignedTo());
    }

    public function testDueDateIsNullByDefault(): void
    {
        $task = new Task();

        $this->assertNull($task->getDueDate());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $task = new Task();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $task->getCreatedAt());
        $this->assertLessThanOrEqual($after, $task->getCreatedAt());
    }

    public function testLinkedEntityIsNullByDefault(): void
    {
        $task = new Task();

        $this->assertNull($task->getLinkedEntityType());
        $this->assertNull($task->getLinkedEntityId());
    }
}
