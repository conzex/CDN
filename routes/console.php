<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('shares:prune')->daily();
Schedule::command('activity:prune --days=90')->daily();
