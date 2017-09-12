<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 10/27/16
 */

namespace Dunagan\MagentoFixes\Model\Cron\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class ProcessCronQueueObserver
 * @package Dunagan\MagentoFixes\Model\Cron\Observer
 */
class ProcessCronQueueObserver extends \Magento\Cron\Observer\ProcessCronQueueObserver implements ObserverInterface
{
    /**
     * This method is overridden to address a bug in the cron system
     *
     * {@inheritdoc}
     */
    protected function saveSchedule($jobCode, $cronExpression, $timeInterval, $exists)
    {
        $currentTime = $this->timezone->scopeTimeStamp();
        $timeAhead = $currentTime + $timeInterval;
        for ($time = $currentTime; $time < $timeAhead; $time += self::SECONDS_IN_MINUTE) {
            $ts = strftime('%Y-%m-%d %H:%M:00', $time);
            if (!empty($exists[$jobCode . '/' . $ts])) {
                // already scheduled
                continue;
            }
            $schedule = $this->generateSchedule($jobCode, $cronExpression, $time);
            if ($schedule->trySchedule()) {
                // time matches cron expression
                $schedule->save();
                // BEGIN CUSTOM CODE
                //return; Commented out this line of code to address the issue with insufficient cron_schedules being created
                // END CUSTOM CODE
            }
        }
    }
}
