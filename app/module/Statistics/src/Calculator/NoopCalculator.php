<?php

declare(strict_types=1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class NoopCalculator extends AbstractCalculator
{

    protected const UNITS = 'posts per user';

    /**
     * @var array
     */
    private $totals = [];
    private $postCount = 0;

    /**
     * @inheritDoc
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $this->postCount++;

        $key = $postTo->getDate()->format('F');
        $author_id = $postTo->getAuthorId();

        $this->totals[$key] = $this->totals[$key] ?? array();
        $month_data = &$this->totals[$key];

        if (!empty($month_data)) {
            $month_data[$author_id] = ($month_data[$author_id] ?? 0) + 1;
        } else {
            $month_data[$author_id] = 1;
        }
    }

    /**
     * @inheritDoc
     */
    protected function doCalculate(): StatisticsTo
    {
        error_log(strval($this->postCount));
        $stats = new StatisticsTo();

        foreach ($this->totals as $month => $month_posts_per_user) {
            $user_count = count($month_posts_per_user);

            $posts_number = array_reduce($month_posts_per_user, fn ($sum, $user) => $sum + $user, 0);
            $userAvg = $posts_number / $user_count;

            $child = (new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod($month)
                ->setValue($userAvg)
                ->setUnits(self::UNITS);

            $stats->addChild($child);
        }

        return $stats;
    }
}
