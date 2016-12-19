<?php

/* $LICENSE 2009, 2010, 2015:
 *
 * Copyright (C) 2009, 2010, 2015 Massimo Zaniboni <massimo.zaniboni@asterisell.com>
 *
 * This file is part of Asterisell.
 *
 * Asterisell is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Asterisell is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Asterisell. If not, see <http://www.gnu.org/licenses/>.
 * $
 */

sfLoader::loadHelpers(array('I18N', 'Debug', 'Date', 'Asterisell', 'ChannelUsage'));

/**
 * Check if there are too many concurrent calls.
 *
 * In the case, add a problem on the Problem Table.
 * The administrator is notified, only if he is notified
 * of new problems in the table.
 */
class CheckSafeLimitForConcurrentCalls extends FixedJobProcessor
{

    /**
     * This file contains the date of last check of call cost limits.
     */
    const FILE_WITH_LAST_CHECK_DATE = "last_check_for_concurrent_calls_safe_limit";

    public function process()
    {
        // Profiling
        //
        $time1 = microtime_float();

        $checkFile = self::FILE_WITH_LAST_CHECK_DATE;
        $checkLimit = strtotime("-4 hour");
        $mutex = new Mutex($checkFile);
        if ($mutex->maybeTouch($checkLimit)) {
            $startDate = $checkLimit;

            $cond = new Criteria();
            $cond->add(ArCdrPeer::CALLDATE, fromUnixTimestampToMySQLTimestamp($startDate), Criteria::GREATER_EQUAL);
            $stats = new StatsOnCalls($cond, $startDate, NULL);

            if ($stats->maxNrOfConcurrentCalls > getConcurrentCallsSafeLimit()) {

                $problemDuplicationKey = "Dangerous concurrent calls " . $stats->dangerousCalls;
                //
                // note: use also the number of concurrent calls as index in order
                // to advise of more important problems later the administrator

                $problemDescription = "There were " . $stats->dangerousCalls . " calls made when there were more concurrent calls than " . getConcurrentCallsSafeLimit() . ". The max number of concurrent calls in the system were " . $stats->maxNrOfConcurrentCalls . ".";
                $problemEffect = "If the system has no enough bandwidth, then it can not manage correctly some calls.";
                $problemProposedSolution = "First inspect the calls usage pattern, using the stats of call report module. Check if the results of Asterisell application are confirmed from the Asterisk server logs/status.";
                ArProblemException::createWithoutGarbageCollection(
                    ArProblemType::TYPE_ERROR,
                    ArProblemDomain::CONFIGURATIONS,
                    ArProblemResponsible::ADMIN,
                    $problemDuplicationKey, $problemDescription, $problemEffect, $problemProposedSolution);
            }
            // Profiling
            //
            $time2 = microtime_float();
            $totTime = $time2 - $time1;
            return "Max number of Concurrent Call checked in $totTime seconds.";
        } else {
            return "Max number of Concurrent Call will be checked later according application settings.";
        }
    }
}