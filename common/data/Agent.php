<?php
namespace JINRO_JOSEKI\Common\Data;

class Agent
{
    private $agent_idx = 0;
    private $name = '';

    public function __construct($name)
    {
        $this->name = $name;
    }
    public function setAgentIdx(int $agent_idx)
    {
        $this->agent_idx = $agent_idx;
    }

    public function getAgentIdx()
    {
        return $this->agent_idx;
    }
    public function toString()
    {
        return $this->name;
    }
}
