<?php

namespace App\Tests\Entity;

use App\Entity\Group;
use App\Entity\Post;
use App\Entity\Profile;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private Profile $author;
    private Post $post;

    protected function setUp(): void
    {
        $this->author = $this->createMock(Profile::class);
        $this->post = new Post();
        $this->post->setAuthor($this->author);
    }

    public function testPostInitialization(): void
    {
        $this->assertInstanceOf(Post::class, $this->post);
        $this->assertInstanceOf(DateTimeImmutable::class, $this->post->getCreatedAt());
        $this->assertNull($this->post->getUpdatedAt());
        $this->assertSame('active', $this->post->getStatus());
    }

    public function testSetAndGetId(): void
    {
        $reflection = new \ReflectionClass($this->post);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->post, 1);

        $this->assertEquals(1, $this->post->getId());
    }

    public function testSetAndGetType(): void
    {
        $type = 'article';
        $this->post->setType($type);
        $this->assertEquals($type, $this->post->getType());
    }

    public function testSetAndGetAuthor(): void
    {
        $newAuthor = $this->createMock(Profile::class);
        $this->post->setAuthor($newAuthor);
        $this->assertSame($newAuthor, $this->post->getAuthor());
    }

    public function testSetAndGetGroup(): void
    {
        $group = $this->createMock(Group::class);
        $this->post->setGroup($group);
        $this->assertSame($group, $this->post->getGroup());

        $this->post->setGroup(null);
        $this->assertNull($this->post->getGroup());
    }

    public function testSetAndGetTitle(): void
    {
        $title = 'Test Title';
        $this->post->setTitle($title);
        $this->assertEquals($title, $this->post->getTitle());
        $this->assertEquals('test-title', $this->post->getSlug());
    }

    public function testSetAndGetContent(): void
    {
        $content = 'This is a test content';
        $this->post->setContent($content);
        $this->assertEquals($content, $this->post->getContent());
    }

    public function testSetAndGetSlug(): void
    {
        $slug = 'custom-slug';
        $this->post->setSlug($slug);
        $this->assertEquals($slug, $this->post->getSlug());
    }

    public function testSetAndGetVisibility(): void
    {
        $visibility = 'private';
        $this->post->setVisibility($visibility);
        $this->assertEquals($visibility, $this->post->getVisibility());
    }

    public function testSetAndGetStatus(): void
    {
        $status = 'archived';
        $this->post->setStatus($status);
        $this->assertEquals($status, $this->post->getStatus());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $createdAt = new DateTimeImmutable();
        $this->post->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $this->post->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $updatedAt = new DateTime();
        $this->post->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $this->post->getUpdatedAt());
    }

    public function testMarkAsUpdated(): void
    {
        $this->assertNull($this->post->getUpdatedAt());
        $this->post->markAsUpdated();
        $this->assertInstanceOf(DateTime::class, $this->post->getUpdatedAt());
    }
}