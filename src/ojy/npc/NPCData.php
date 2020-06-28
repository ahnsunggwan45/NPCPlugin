<?php

namespace ojy\npc;

class NPCData
{

    protected $name = '';

    protected $message = '';

    protected $command = '';

    protected $scale = 1;

    /**
     * NPCData constructor.
     * @param string $name
     * @param string $message
     * @param string $command
     */
    public function __construct(string $name, string $message = '', string $command = '', float $scale = 1)
    {
        $this->name = $name;
        $this->message = $message;
        $this->command = $command;
        $this->scale = $scale;
    }

    public static function deserialize(string $data)
    {
        $data = explode("⊙", $data);
        return new self(...$data);
    }

    public function serialize()
    {
        return $this->name . "⊙" . $this->message . "⊙" . $this->command . "⊙" . $this->scale;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return float|int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param float $scale
     */
    public function setScale(float $scale): void
    {
        $this->scale = $scale;
    }
}