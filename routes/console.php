<?php

use App\Jobs\CheckPickupRemindersJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new CheckPickupRemindersJob)->hourly();
