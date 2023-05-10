<?php

namespace Statistics\Service;

use SocialPost\Dto\SocialPostTo;
use Statistics\Calculator\Factory\StatisticsCalculatorFactory;
use Statistics\Dto\ParamsTo;
use Statistics\Dto\StatisticsTo;
use Traversable;

/**
 * Class PostStatisticsService
 *
 * @package Statistics
 */
class StatisticsService
{

    /**
     * @var StatisticsCalculatorFactory
     */
    private $factory;

    /**
     * StatisticsService constructor.
     *
     * @param StatisticsCalculatorFactory $factory
     */
    public function __construct(StatisticsCalculatorFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Traversable $posts
     * @param ParamsTo[]  $params
     *
     * @return StatisticsTo
     */
    public function calculateStats(Traversable $posts, array $params): StatisticsTo
    {

        $time_format = 'g:i:s';

        $calculator = $this->factory->create($params);
        error_log('Handlers created ' . date($time_format));

        foreach ($posts as $post) {
            if (!$post instanceof SocialPostTo) {
                continue;
            }

            error_log('Accumulating ' . date($time_format));
            $calculator->accumulateData($post);
        }
        error_log('About to do calculating ' . date($time_format));
        $res = $calculator->calculate();
        error_log('Calculated ' . date($time_format));
        return $res;
    }
}
