<?php
require_once 'config.php';


function calcEmployeeCost($zp, $ns, $cwd) {
    $workingDays = 22;
    $dayCost = ($zp + ($zp * $ns / 100)) / $workingDays;
    return round($cwd * $dayCost, 2);
}


function calcExecutorCost($cez, $suz, $ns) {
    $base = $cez * $suz;
    $tax = $base * ($ns / 100);
    return round($base + $tax, 2);
}


function calcEquipmentCost($cez, $soz) {
    return round($cez * $soz, 2);
}

function calcTotalWithMargin($s, $m) {
    return round($s + ($s * $m / 100), 2);
}


function calcProfit($i, $s) {
    return round($i - $s, 2);
}


function calcProjectTotal($sp, $ns) {
    return round($sp + ($sp * $ns / 100), 2);
}


function getWorkingDaysInMonth($year, $month) {
    $count = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $weekday = date('N', strtotime("$year-$month-$day"));
        if ($weekday <= 5) {
            $count++;
        }
    }
    return $count;
}


function calcEmployeeCostExact($zp, $ns, $startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $total = 0;
    
    $current = clone $start;
    $current->modify('first day of this month');
    
    while ($current <= $end) {
        $year = (int)$current->format('Y');
        $month = (int)$current->format('m');
        
        $monthStart = new DateTime("$year-$month-01");
        $monthEnd = clone $monthStart;
        $monthEnd->modify('last day of this month');
        
        $intervalStart = max($start, $monthStart);
        $intervalEnd = min($end, $monthEnd);
        
        if ($intervalStart <= $intervalEnd) {
            $workingDaysInMonth = getWorkingDaysInMonth($year, $month);
            
            if ($workingDaysInMonth > 0) {
                $dayCost = ($zp + ($zp * $ns / 100)) / $workingDaysInMonth;
                
                $daysInInterval = 0;
                $d = clone $intervalStart;
                while ($d <= $intervalEnd) {
                    $weekday = (int)$d->format('N');
                    if ($weekday <= 5) {
                        $daysInInterval++;
                    }
                    $d->modify('+1 day');
                }
                
                $total += $daysInInterval * $dayCost;
            }
        }
        
        $current->modify('+1 month');
    }
    
    return round($total, 2);
}
?>