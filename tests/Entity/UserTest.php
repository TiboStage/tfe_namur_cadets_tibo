<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $user = new User();
        $user->email = '  TEST@Example.COM  ';

        $this->assertEquals('test@example.com', $user->email);
    }

    public function testUsernameIsNormalized(): void
    {
        $user = new User();
        $user->username = '  MonUser  ';

        $this->assertEquals('monuser', $user->username);
    }

    public function testDefaultRoleIsUser(): void
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testIsNotBannedByDefault(): void
    {
        $user = new User();

        $this->assertFalse($user->isBanned());
    }
}
