<?php

// Developed by Host Media Ltd
// https://hostmedia.uk
// Version 1.0.0

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$reportdata['title'] = "Annual Income Comparison (Year on Year)";
$reportdata['description'] = "This report shows the total income received each year, allowing for year-over-year comparison and business planning.";

$currency = getCurrency(null, 1);

$reportdata['tableheadings'] = array(
    "Year",
    "Amount In",
    "Fees",
    "Amount Out",
    "Balance"
);

$reportvalues = array();
$results = Capsule::table('tblaccounts')
    ->select(
        Capsule::raw("date_format(date,'%Y') as year"),
        Capsule::raw("SUM(amountin/rate) as amountin"),
        Capsule::raw("SUM(fees/rate) fees"),
        Capsule::raw("SUM(amountout/rate) as amountout")
    )
    ->groupBy(Capsule::raw("date_format(date,'%Y')"))
    ->orderBy('year', 'asc')
    ->get()
    ->all();

$years = array();
foreach ($results as $result) {
    $year = (int) $result->year;
    $amountin = $result->amountin;
    $fees = $result->fees;
    $amountout = $result->amountout;
    $yearlybalance = $amountin - $fees - $amountout;

    $years[] = $year;
    $reportvalues[$year] = [
        $amountin,
        $fees,
        $amountout,
        $yearlybalance,
    ];
}

// Sort years in ascending order for display
sort($years);

foreach ($years as $year) {
    $amountin = $reportvalues[$year][0];
    $fees = $reportvalues[$year][1];
    $amountout = $reportvalues[$year][2];
    $yearlybalance = $reportvalues[$year][3];

    $reportdata['tablevalues'][] = array(
        $year,
        formatCurrency($amountin),
        formatCurrency($fees),
        formatCurrency($amountout),
        formatCurrency($yearlybalance),
    );
}

// Prepare chart data
$chartdata['cols'][] = array('label'=>'Year','type'=>'string');
$chartdata['cols'][] = array('label'=>'Income','type'=>'number');

foreach ($years as $year) {
    $chartdata['rows'][] = array(
        'c'=>array(
            array(
                'v'=>$year,
            ),
            array(
                'v'=>$reportvalues[$year][0],
                'f'=>formatCurrency($reportvalues[$year][0])->toFull(),
            ),
        ),
    );
}

$args = array();
$args['colors'] = '#3070CF';
$args['chartarea'] = '80,20,90%,350';
$args['legend'] = 'right';
$args['isStacked'] = 'false';
$args['hAxis'] = array('title' => 'Year');
$args['vAxis'] = array('title' => 'Income');

$reportdata['headertext'] = $chart->drawChart('Column',$chartdata,$args,'500px'); 
