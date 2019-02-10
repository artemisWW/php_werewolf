<?php
namespace JINRO_JOSEKI\Common\Data;

class Judge
{
    private $day = -1;
    private $agent_id = -1;
    private $target_id = -1;
    private $result_id = -1;

    public function __construct(int $day, int $agent_id, int $target_id, int $result_id)
    {
        $this->day = $day;
        $this->agent_id = $agent_id;
        $this->target_id = $target_id;
        $this->result_id = $result_id;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getAgentId()
    {
        return $this->agent_id;
    }
    public function getTargetId()
    {
        return $this->target_id;
    }
    public function getResultId()
    {
        return $this->result_id;
    }

    public function toString()
    {
        return $this->target->getAgentAdx() . ' ' . $this->result->toString();
    }
}
