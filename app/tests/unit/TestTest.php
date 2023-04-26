<?php

declare(strict_types=1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\ParamsTo;
use Statistics\Calculator\AveragePostsByUsersPerMonth;

/**
 * Class ATestTest
 *
 * @package Tests\unit
 */
class AveragePostsByUsersPerMonthTest extends TestCase
{
    // we don't test this here
    // I would have tested dated in the abstract calculator
    private $startDate;
    private $endDate;
    private $params;

    protected function setUp(): void
    {
        $this->startDate = new \DateTime('2022-01-01');
        $this->endDate = new \DateTime('2023-01-01');

        $this->params = new ParamsTo();
        $this->params->setStatName('test');
        $this->params->setStartDate($this->startDate);
        $this->params->setEndDate($this->endDate);
    }

    public function testIsCorrectStatsFor1UserWith1Post1Month(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $post1 = $this->generatePost($author1, $this->startDate);

        $calculator->accumulateData($post1);
        $result = $calculator->calculate();

        // only one post for one user means we only have one stat row with the value 1
        $this->assertCount(1, $result->getChildren());
        $statsItem = $result->getChildren()[0];
        $this->assertEquals('posts per user', $statsItem->getUnits());
        $this->assertEquals('test', $statsItem->getName());
        $this->assertEquals('January', $statsItem->getSplitPeriod());
        $this->assertEquals(1, $statsItem->getValue());
    }

    public function testIsCorrectStatsFor1UserWith3Posts1Month(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $posts = $this->generatePosts(3, $author1, $this->startDate);
        $this->accumulatePosts($calculator, $posts);

        $result = $calculator->calculate();

        // 3 posts now but only 1 month and one user so we should get 3
        $this->assertCount(1, $result->getChildren());
        $statsItem = $result->getChildren()[0];
        $this->assertEquals(3, $statsItem->getValue());
    }

    public function testIsCorrectStatsFor1UserWith3Posts2Months(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $m1posts = $this->generatePosts(1, $author1, $this->startDate);
        $m2posts = $this->generatePosts(2, $author1, new \DateTime('2022-02-01'));
        $this->accumulatePosts($calculator, array_merge($m1posts, $m2posts));

        $result = $calculator->calculate();

        // now we have 2 months. we should get Jan with 1 and Feb with 2
        $this->assertCount(2, $result->getChildren());

        $m1statsItem = $result->getChildren()[0];
        $this->assertEquals(1, $m1statsItem->getValue());

        $m2statsItem = $result->getChildren()[1];
        $this->assertEquals(2, $m2statsItem->getValue());
    }

    public function testIsCorrectStatsFor2UsersWith3Posts1Month(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $u1posts = $this->generatePosts(2, $author1, $this->startDate);

        $author2 = '2';
        $u2posts = $this->generatePosts(4, $author2, $this->startDate);
        $this->accumulatePosts($calculator, array_merge($u1posts, $u2posts));

        $result = $calculator->calculate();

        // so we have 2 users now with 2 and 4 posts for 1 month. average is 3.
        $this->assertCount(1, $result->getChildren());

        $statsItem = $result->getChildren()[0];
        $this->assertEquals(3, $statsItem->getValue());
    }


    public function testIsCorrectStatsFor2UsersWith6Posts1Month(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $u1posts = $this->generatePosts(2, $author1, $this->startDate);

        $author2 = '2';
        $u2posts = $this->generatePosts(4, $author2, $this->startDate);
        $this->accumulatePosts($calculator, array_merge($u1posts, $u2posts));

        $result = $calculator->calculate();

        // so we have 2 users now with 2 and 4 posts for 1 month. average is 3.
        $this->assertCount(1, $result->getChildren());

        $statsItem = $result->getChildren()[0];
        $this->assertEquals(3, $statsItem->getValue());
    }

    public function testIsCorrectStatsFor2UsersWithManyPosts2Months(): void
    {
        $march = new \DateTime('2022-03-01');
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $author1 = '1';
        $m1u1posts = $this->generatePosts(4, $author1, $this->startDate);
        $m3u1posts = $this->generatePosts(8, $author1, $march);

        $author2 = '2';
        $m1u2posts = $this->generatePosts(20, $author2, $this->startDate);
        $m3u2posts = $this->generatePosts(4, $author2, $march);
        $this->accumulatePosts($calculator, array_merge($m1u1posts, $m3u1posts, $m1u2posts, $m3u2posts));

        $result = $calculator->calculate();

        // now we have 2 months and 2 users with many posts. to make it more complex we are also skipping a month
        // Jan is 4 + 20 posts which average of 12 and Mar is 8 + 4 which is then 6.
        $this->assertCount(2, $result->getChildren());

        $m1statsItem = $result->getChildren()[0];
        $this->assertEquals(12, $m1statsItem->getValue());
        $this->assertEquals('January', $m1statsItem->getSplitPeriod());

        $m3statsItem = $result->getChildren()[1];
        $this->assertEquals(6, $m3statsItem->getValue());
        $this->assertEquals('March', $m3statsItem->getSplitPeriod());
    }

    public function testNoExceptionIfCalculatedWithoutValues(): void
    {
        $calculator = new AveragePostsByUsersPerMonth();
        $calculator->setParameters($this->params);

        $result = $calculator->calculate();

        $this->assertCount(0, $result->getChildren());
    }

    private function generatePost($author_id, $date)
    {
        $post = new SocialPostTo();
        $post->setDate($date);
        $post->setAuthorId($author_id);

        return $post;
    }

    function generatePosts($n, $author, $startDate)
    {
        $posts = [];
        for ($i = 1; $i <= $n; $i++) {
            $post = $this->generatePost($author, $startDate);
            $posts[] = $post;
        }
        return $posts;
    }

    function accumulatePosts($calculator, $posts)
    {
        foreach ($posts as $post) {
            $calculator->accumulateData($post);
        }
    }
}
