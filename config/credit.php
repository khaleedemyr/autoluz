<?php

return [
    'default_dp_percent' => (float) env('CREDIT_DEFAULT_DP_PERCENT', 20),
    'default_tenor' => (int) env('CREDIT_DEFAULT_TENOR', 36),
    'default_rate' => (float) env('CREDIT_DEFAULT_RATE', 5.5),
    'default_method' => env('CREDIT_DEFAULT_METHOD', 'flat'), // flat | annuity
    'tenor_options' => [12, 24, 36, 48, 60],
    'min_dp_percent' => 0,
    'max_dp_percent' => 90,
];
