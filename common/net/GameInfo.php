<?php
namespace JINRO_JOSEKI\Common\Net;

require_once('common/net/GameInfoCommon.php');
require_once('common/data/Role.php');

use JINRO_JOSEKI\Common\Data\Role;

class GameInfo
{
    private $agent = null;
    private $agent_id = -1;
    private $role_id = -1;

    public function __construct(int $agent_idx, GameInfoCommon &$game_info_common)
    {
        $this->game_info_common = &$game_info_common;
        $this->agent_id = $agent_idx;
        $this->agent = $this->game_info_common->getAgent($this->agent_id);
        $this->role_id = $this->game_info_common->getRoleId($this->agent_id);
    }

    // for day
    /** @return int */
    public function getDay()
    {
        return $this->game_info_common->getDay();
    }

    // for me
    /** @return Agent */
    public function getAgent()
    {
        return $this->agent;
    }
    public function getAgentId()
    {
        return $this->agent_id;
    }
    public function getRoleId()
    {
        return $this->role_id;
    }

    // for common
    /** @return Agent[] */
    public function getAgentList()
    {
        return $this->game_info_common->getAgentList();
    }
    /** @return int[] */
    public function getRoleIdList()
    {
        return $this->game_info_common->getRoleIdList();
    }
    /** @return int[] */
    public function getLastDeadIdList()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getDeadIdList($day - 1);
    }
    /** @return int[] */
    public function getStatusIdList()
    {
        return $this->game_info_common->getStatusIdList();
    }

    // for vote
    /** @return Vote[] */
    public function getVoteList()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getVoteList($day - 1);
    }
    /** @return Vote[] */
    public function getLatestVoteList()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getVoteList($day);
    }

    // for attack vote
    /** @return Vote[] */
    public function getAttackVoteList()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::WEREWOLF) {
            return $this->game_info_common->getAttackVoteList($day - 1);
        } else {
            return [];
        }
    }
    /** @return Vote[] */
    public function getLatestAttackVoteList()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::WEREWOLF) {
            return $this->game_info_common->getAttackVoteList($day);
        } else {
            return [];
        }
    }

    // for executed agent
    /** @return int */
    public function getExecutedId()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getExecutedId($day - 1);
    }
    /** @return int */
    public function getLatestExecutedId()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getExecutedId($day);
    }

    // for bodyguard
    /** @return Agent */
    public function getGuardedId()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::BODYGUARD) {
            return $this->game_info_common->getGuardedId($day - 1);
        } else {
            return -1;
        }
    }
    
    // for medium
    /** @return Judge */
    public function getMediumResult()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::MEDIUM) {
            return $this->game_info_common->getMediumJudge($day - 1);
        } else {
            return null;
        }
    }

    // for seer
    /** @return Judge */
    public function getDivineResult()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::SEER) {
            return $this->game_info_common->getDivineJudge($day - 1);
        } else {
            return null;
        }
    }

    // for werewolf
    /** @return Agent[] */
    public function getAttackedAgent()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::WEREWOLF) {
            return $this->game_info_common->getAttackedAgent($day - 1);
        } else {
            return null;
        }
    }

    // for werewolf and freemason
    /** @return Agent[] */
    public function getRoleMap()
    {
        if ($this->role_id === Role::WEREWOLF || $this->role_id === Role::FREEMASON) {
            return $this->game_info_common->getRoleMap($this->role_id);
        } else {
            return [];
        }
    }

    // for talk
    /** @return Talk[] */
    public function getTalkList()
    {
        $day = $this->game_info_common->getDay();
        return $this->game_info_common->getTalkList($day);
    }
    /** @return Talk[] */
    public function getWhisperList()
    {
        $day = $this->game_info_common->getDay();
        if ($this->role_id === Role::WEREWOLF) {
            return $this->game_info_common->getWhisperList($day);
        } else {
            return null;
        }
    }
}
