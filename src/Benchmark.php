<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

/**
 * Description of Benchmark
 *
 * @version $id$
 * @author peter.ho
 */
class Benchmark
{

    /**
     *
     * @var Closure[] 
     */
    private static $funcs = array ();

    /**
     * 
     * @param \Closure $func
     * @param type $name
     */
    public static function register(\Closure $func, $name = null)
    {
        $name = ($name === null) ? ("func" . count(static::$funcs)) : $name;
        static::$funcs[] = array (
            $name,
            $func
        );
    }

    /**
     * 
     * @param int $multiplier
     * @return string|html
     */
    public static function reportAsCompareTable($multiplier = 1000)
    {
        $result = static::report($multiplier);
        $header = $time = $memory = "";
        $header .= "<th>Function Name</th>";
        $time .= "<td>Time</td>";
        $memory .= "<td>Memory</td>";
        foreach ($result as $funcStatistics) {
            $header .= "<th>{$funcStatistics[0]}</th>";
            $time .= "<td>{$funcStatistics[1]}</td>";
            $memory .= "<td>{$funcStatistics[2]}</td>";
        }
        return "<table><tr>{$header}</tr><tr>{$time}</tr><tr>{$memory}</tr></table>";
    }

    public static function run($multiplier)
    {
        //  init
        $namedStat = array ();
        foreach (static::$funcs as $namedFunc) {
            $namedStat[$namedFunc[0]] = array (0, 0);
        }
        $statistics = array ();
        $total = count(static::$funcs);
        $order = range(0, $total - 1);

        //  run in multiplier
        $lastStat = self::getCurrentStatistics();
        for ($i = 0; $i < $multiplier; $i++) {
            shuffle($order);
            for ($j = 0; $j < $total; $j ++) {
                $namedFunc = static::$funcs[$order[$j]];
//            }
//            foreach (static::$funcs as $namedFunc) {
                $namedFunc[1]();
                $currStat = self::getCurrentStatistics();
                $namedStat[$namedFunc[0]][0] += ($currStat[0] - $lastStat[0]);
                $namedStat[$namedFunc[0]][1] += ($currStat[1] - $lastStat[1]);
                $lastStat = $currStat;
            }
        }
        foreach ($namedStat as $name => $stats) {
            $statistics[] = array ($name, $stats);
        }
        return $statistics;
    }

    /**
     * 
     * @param int $multiplier
     * @return array
     */
    public static function report($multiplier)
    {
        $statistics = self::run($multiplier);
        $result = array ();
        foreach ($statistics as $namedStatistic) {
            $result[] = array (
                $namedStatistic[0],
                number_format(($namedStatistic[1][0]) / $multiplier, 6),
                number_format(($namedStatistic[1][1]) / $multiplier, 2),
            );
        }
        return $result;
    }

    /**
     * 
     * @return array (microtime, memory_get_usage, memory_get_peak_usage)
     */
    private static function getCurrentStatistics()
    {
        return array (
            microtime(true),
            memory_get_usage(true),
            memory_get_peak_usage(true),
        );
    }

}
