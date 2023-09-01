<?php

declare(strict_types=1);

namespace PRISM\Modules;

class Timers
{
    /** @var Timer[] $timers Collection of named and unnamed timer objects. */
    protected array $timers = array();
    /** @var mixed $timeout */
    protected $timeout = null;        # When the next timeout is, read only from outside of this class.

    // Registers a callback method.
    /**
     * Undocumented function
     *
     * @param callable $callback
     * @param float $interval
     * @param int $flags
     * @param mixed[] $args Arguments passed to the callback.
     * @return void
     */
    protected function createTimer(callable $callback, float $interval = 1.0, int $flags = Timer::CLOSE, array $args = array()): void
    {
        # Unique Timer ID based on time in microseconds prepended by a random number
        $name = uniqid((string) mt_rand(), true);
        $this->createNamedTimer($name, \Closure::fromCallable($callback), $interval, $flags, $args);
    }

    // Create a timer with a name so that it can be removed on demand
    /**
     * Undocumented function
     *
     * @param string $name
     * @param callable $callback
     * @param float $interval
     * @param int $flags
     * @param mixed[] $args Arguments passed to the callback.
     * @return void
     */
    protected function createNamedTimer(string $name, callable $callback, float $interval = 1.0, int $flags = Timer::CLOSE, array $args = array()): void
    {
        # Adds our timer to the array.
        $this->timers["$name"] = new Timer($this, \Closure::fromCallable($callback), $interval, $flags, $args);
    }

    // UnRegisters a callback method.
    protected function removeTimer(string $name): void
    {
        # removes our timer from the array.
        unset($this->timers["$name"]);
    }

    // Executes the elapsed timers, and returns when the next timer should execute or NULL if no timers are left.
    public function executeTimers(): mixed
    {
        if (empty($this->timers)) {
            return $this->timeout = null;
        } # As we don't have any timers to check, we skip the rest of this function.

        $timeNow = microtime(true);
        $timestamp = null;

        foreach ($this->timers as $name => &$timer) {
            $timerTS = $timer->getTimeStamp();
            # Check to see if the first timestamp has elapsed.
            if ($timeNow < $timerTS) {
                continue;
            } # If we are not past this timestamp, we go no further for this timer.

            # decide next timeout timestamp
            if ($timestamp == null || $timerTS > $timestamp) {
                $timestamp = $timerTS;
            }

            # Here we execute expired timers.
            if ($timer->execute() != PLUGIN_STOP and $timer->getFlags() != Timer::CLOSE) {
                # Update Timer TimeStamp
                $timer->setTimeStamp($timerTS + (float)$timer->getInterval());
            } else {
                unset($this->timers["$name"]);
            }
        }

        $this->timeout = $timestamp;

        if (empty($this->timers)) {
            return null;
        } else {
            return $this->timeout;
        }
    }
}

/**
 * Undocumented class
 *
 * This is an example of using getter/setter methods when simple properties
 * would suffice. The core premise of getters/setters is encapsulation and
 * protecting the interfaces from malformed values or doing other operations.
 * In this case, we simply fetch and retrieve the data as is.
 */
class Timer
{
    public const CLOSE = 0; /** Timer will run once, the default behavior. */
    public const REPEAT = 1; /** Timer will repeat until it returns PLUGIN_STOP. */
    public const FOREVER = -1; /** Timer will repeat forever, or until the callback function returns PLUGIN_STOP */

    protected Timers $parent;
    /** @var mixed[] $args */
    protected array $args;
    protected float $timestamp;
    protected \Closure $callback;
    protected int $flags;
    protected float $interval;

    /**
     * Undocumented function
     *
     * @param Timers $parent For some reason this is explicity set to \PRISM\Modules\Timers
     *                       as the first line in the constructor.
     * @param \Closure $callback
     * @param float $interval
     * @param int $flags
     * @param mixed[] $args Arguments passed to the callback.
     */
    public function __construct(&$parent, callable $callback, float $interval = 1.0, int $flags = Timer::CLOSE, array $args = array())
    {
        $this->parent = & $parent;
        $this->setCallback(\Closure::fromCallable($callback));
        $this->setTimeStamp(microtime(true) + (float)$interval);
        $this->setInterval($interval);
        $this->setFlags($flags);
        $this->setArgs($args);
    }

    /**
     * Undocumented function
     *
     * @param mixed[] $args
     * @return void
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }
    /**
     * Undocumented function
     *
     * @return mixed[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    public function setTimeStamp(float $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
    public function getTimeStamp(): float
    {
        return $this->timestamp;
    }

    public function setCallback(callable $callback): void
    {
        $this->callback = \Closure::fromCallable($callback);
    }
    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }
    public function getFlags(): int
    {
        return $this->flags;
    }

    public function setInterval(float $interval): void
    {
        $this->interval = $interval;
    }
    public function getInterval(): float
    {
        return $this->interval;
    }

    public function execute(): mixed
    {
        // @phpstan-ignore-next-line
        return call_user_func_array(array(&$this->parent, $this->callback), $this->args);
    }
}
