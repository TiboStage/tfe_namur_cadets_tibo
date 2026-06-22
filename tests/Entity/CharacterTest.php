<?php

namespace App\Tests\Entity;

use App\Entity\Character;
use PHPUnit\Framework\TestCase;

class CharacterTest extends TestCase
{
    public function testNameIsTrimmed(): void
    {
        $character = new Character();
        $character->name = '  Marie Dupont  ';

        $this->assertEquals('Marie Dupont', $character->name);
    }

    public function testFirstNameIsTrimmed(): void
    {
        $character = new Character();
        $character->firstName = '  Marie  ';

        $this->assertEquals('Marie', $character->firstName);
    }

    public function testLastNameIsTrimmed(): void
    {
        $character = new Character();
        $character->lastName = '  Dupont  ';

        $this->assertEquals('Dupont', $character->lastName);
    }

    public function testNicknameIsTrimmed(): void
    {
        $character = new Character();
        $character->nickname = '  La détective  ';

        $this->assertEquals('La détective', $character->nickname);
    }

    public function testValidRoleIsAccepted(): void
    {
        $character = new Character();

        foreach (['protagonist', 'antagonist', 'secondary', 'figurant'] as $role) {
            $character->role = $role;
            $this->assertEquals($role, $character->role);
        }
    }

    public function testInvalidRoleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $character = new Character();
        $character->role = 'villain';
    }

    public function testAliasesDefaultToEmptyArray(): void
    {
        $character = new Character();

        $this->assertIsArray($character->aliases);
        $this->assertEmpty($character->aliases);
    }

    public function testGetAllRelationsIsEmptyByDefault(): void
    {
        $character = new Character();

        $this->assertCount(0, $character->getAllRelations());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $character = new Character();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $character->getCreatedAt());
        $this->assertLessThanOrEqual($after, $character->getCreatedAt());
    }
}
