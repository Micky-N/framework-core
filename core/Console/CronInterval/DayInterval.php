<?php

namespace MkyCore\Console\CronInterval;

class DayInterval extends AbstractInterval
{
    static protected int $_MIN = 1;
    static protected int $_MAX = 31;
}