<?php
namespace JINRO_JOSEKI\Common\Data;

class Talk
{
    const OVER = 'Over';
    const SKIP = 'Skip';
    
    private $idx = 0;
    private $day = -1;
    private $turn = 0;
    private $agent_id = -1;
    private $text = '';

    public function __construct(int $day, int $idx, int $turn, int $agent_id, string $text)
    {
        $this->day = $day;
        $this->idx = $idx;
        $this->turn = $turn;
        $this->agent_id = $agent_id;
        $this->text = $text;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getIdx()
    {
        return $this->idx;
    }
    public function getTurn()
    {
        return $this->turn;
    }
    public function getAgentId()
    {
        return $this->agent_id;
    }
    public function getText()
    {
        return $this->text;
    }
}
