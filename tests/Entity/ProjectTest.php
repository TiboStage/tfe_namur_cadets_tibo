<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testTitleIsTrimmed(): void
    {
        $project = new Project();
        $project->title = '  Mon Projet  ';

        $this->assertEquals('Mon Projet', $project->title);
    }

    public function testSlugIsLowercaseAndTrimmed(): void
    {
        $project = new Project();
        $project->slug = '  Mon-Super-Projet  ';

        $this->assertEquals('mon-super-projet', $project->slug);
    }

    public function testDescriptionIsTrimmed(): void
    {
        $project = new Project();
        $project->description = '   Une description.   ';

        $this->assertEquals('Une description.', $project->description);
    }

    public function testDefaultVisibilityIsUnpublished(): void
    {
        $project = new Project();

        $this->assertEquals(Project::VISIBILITY_UNPUBLISHED, $project->getVisibility());
    }

    public function testVisibilityConstants(): void
    {
        $this->assertEquals('unpublished', Project::VISIBILITY_UNPUBLISHED);
        $this->assertEquals('private', Project::VISIBILITY_PRIVATE);
        $this->assertEquals('public', Project::VISIBILITY_PUBLIC);
    }
}
