<?php

use namespace PRISM\Module;

class Timer
{
    const CLOSE = 0; /** Timer will run once, the default behavior. */
    const REPEAT = 1; /** Timer will repeat until it returns PLUGIN_STOP. */
    const FOREVER = -1; /** Timer will repeat forever, or until the callback function returns PLUGIN_STOP */

    protected $parent;
    protected $args;
    protected $callback;
    protected $flags;
    protected $interval;

    public function __construct(&$parent, $callback, $interval = 1.0, $flags = Timer::CLOSE, $args = array())
    {
        $this->parent =& $parent;
        $this->setCallback($callback);
        $this->setInterval($interval);
        $this->setFlags($flags);
        $this->setArgs($args);
    }

    public function setArgs(array $args) { $this->args = $args; }
    public function getArgs() { return $this->args; }

    public function setCallback($callback) { $this->callback = $callback; }
    public function getCallback() { return $this->callback; }

    public function setFlags($flags) { $this->flags = $flags; }
    public function getFlags() { return $this->flags; }

    public function setInterval($interval) { $this->interval = $interval; }
    public function getInterval() { return $this->interval; }

/*	public function setRepeat($repeat) { $this->repeat = (int) $repeat; }
    public function getRepeat() { return $this->repeat; } */

    public function execute()
    {
        return call_user_func_array(array(&$this->parent, $this->callback), $this->args);
    }
}
