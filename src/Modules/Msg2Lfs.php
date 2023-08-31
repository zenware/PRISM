<?php

declare(strict_types=1);

namespace PRISM\Modules;

/**
 * @method $this PLID(int $plid)
 * @method $this UCID(int $ucid)
 * @method $this Text(string $message)
 * @method $this Msg(string $message)
 */
class Msg2Lfs
{
    public int $PLID = 0;
    public int $UCID = 0;
    public string $Text = '';
    public int $Sound = SND_SILENT;

    /**
     * Undocumented function
     *
     * Note: Originally this function ended with `return $this`.
     * I'm, not certain whether this was an intentional choice to "look like"
     * the other methods, since Msg2Lfs follows the "fluid interface" pattern,
     * or if this was a choice because the behavior of a constructor with a
     * return value was being used for some functionality...
     *
     * @param string $text
     */
    public function __construct(string $text = '')
    {
        $this->Text = $text;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param mixed[] &$arguments
     * @return $this
     */
    public function &__call(string $name, array $arguments)
    {
        if (property_exists(get_class($this), $name)) {
            $this->$name = array_shift($arguments);
        }
        return $this;
    }

    /**
     * Helps convert some data into a packet to be sent over the network.
     *
     * TODO: I'm genuinely unsure what type $hostId is.
     * Reviewing the callers didn't help at the time.
     *
     * @param mixed $hostId
     * @return $this|void
     */
    public function send($hostId = null)
    {
        if ($this->Text == '') {
            return;
        }

        global $PRISM;

        // Decide what IS packet to use to send this message
        if (($PRISM->hosts->getStateById($hostId)->State & ISS_MULTI) === 0) {
            // Single player
            \PRISM\IS_MSL()->Msg($this->Text)->Sound($this->Sound)->send();
        } else {
            // Multi player
            if ($this->PLID > 0) {
                \PRISM\IS_MTC()->PLID($this->PLID)->Text($this->Text)->Sound($this->Sound)->send();
            } elseif ($this->UCID > 0) {
                \PRISM\IS_MTC()->UCID($this->UCID)->Text($this->Text)->Sound($this->Sound)->send();
            } else {
                \PRISM\IS_MSX()->Msg($this->Text)->send();
            }
        }

        return $this;
    }
};
