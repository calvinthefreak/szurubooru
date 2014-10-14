<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Entities\Tag;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TagDaoTest extends AbstractDatabaseTestCase
{
	public function setUp()
	{
		parent::setUp();
	}

	public function testSaving()
	{
		$tag = new Tag();
		$tag->setName('test1');
		$tag->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));
		$this->assertFalse($tag->isBanned());
		$tag->setBanned(true);

		$tagDao = $this->getTagDao();
		$tagDao->save($tag);
		$actualTag = $tagDao->findById($tag->getId());
		$this->assertEntitiesEqual($tag, $actualTag);
	}

	public function testFindByPostIds()
	{
		$pdo = $this->databaseConnection->getPDO();

		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (1, \'test1\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (2, \'test2\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (5, 1)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (6, 1)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (5, 2)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (6, 2)');

		$tag1 = new Tag(1);
		$tag1->setName('test1');
		$tag1->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));
		$tag2 = new Tag(2);
		$tag2->setName('test2');
		$tag2->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));

		$expected = [
			$tag1->getId() => $tag1,
			$tag2->getId() => $tag2,
		];
		$tagDao = $this->getTagDao();
		$actual = $tagDao->findByPostId(5);
		$this->assertEntitiesEqual($expected, $actual);
	}

	public function testRemovingUnused()
	{
		$tag1 = new Tag();
		$tag1->setName('test1');
		$tag1->setCreationTime(date('c'));
		$tag2 = new Tag();
		$tag2->setName('test2');
		$tag2->setCreationTime(date('c'));
		$tagDao = $this->getTagDao();
		$tagDao->save($tag1);
		$tagDao->save($tag2);
		$pdo = $this->databaseConnection->getPDO();
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (1, 2)');
		$tag1 = $tagDao->findById($tag1->getId());
		$tag2 = $tagDao->findById($tag2->getId());
		$this->assertEquals(2, count($tagDao->findAll()));
		$this->assertEquals(0, $tag1->getUsages());
		$this->assertEquals(1, $tag2->getUsages());
		$tagDao->deleteUnused();
		$this->assertEquals(1, count($tagDao->findAll()));
		$this->assertNull($tagDao->findById($tag1->getId()));
		$this->assertEntitiesEqual($tag2, $tagDao->findById($tag2->getId()));
	}

	private function getTagDao()
	{
		return new TagDao($this->databaseConnection);
	}
}
