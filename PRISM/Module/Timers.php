<?php

namespace PRISM\Module;
use PRISM\Module\Timer;

class Timers
{
    protected $timers = array();	# Array of timers.
    protected $timeout = null;		# When the next timeout is, read only from outside of this class.

    // Registers a callback method.
    protected function createTimer($callback, $interval = 1.0, $flags = Timer::CLOSE, $args = array())
    {
        # This will be the time when this timer is to trigger
        $timestamp = microtime(true) + $interval;

        # Check to make sure that another timer with same timestamp doesn't exist
        if (isset($this->timers["$timestamp"])) {
            $this->createTimer($callback, $interval, $flags, $args);
        } else {
            # Adds our timer to the array.
            $this->timers["$timestamp"] = new Timer($this, $callback, $interval, $flags, $args);
        }
    }

    // Sort the array to make sure the next timer (smallest float) is on the top of the list.
    protected function sortTimers()
    {
        return ksort($this->timers);
    }

    // Executes the elapsed timers, and returns when the next timer should execute or null if no timers are left.
    public function executeTimers()
    {
        if (empty($this->timers)) {
            return $this->timeout = null; # As we don't have any timers to check, we skip the rest of this function.
        }

        $this->sortTimers();

        $timeNow = microtime(true);

        foreach ($this->timers as $timestamp => &$timer) {
            # Check to see if the first timestamp has elpased.
            if ($timeNow < $timestamp) {
                return; # If we are not past this timestamp, we go no further.
            }

            # Here we execute expired timers.
            if ($timer->execute() != PLUGIN_STOP AND $timer->getFlags() != Timer::CLOSE) {
                $this->createTimer($timer->getCallback(), $timer->getInterval(), $timer->getFlags(), $timer->getArgs());
            }

            unset($this->timers[$timestamp]);
        }

        $this->timeout = $timestamp;

        if (empty($this->timers)) {
            return null;
        } else {
            return $this->timeout;
        }
    }
}
