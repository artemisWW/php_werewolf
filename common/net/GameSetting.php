<?php
namespace JINRO_JOSEKI\Common\Net;

require_once('common/data/role.php');

use JINRO_JOSEKI\Common\Data\Role;

class GameSetting
{
    private $max_attack_revote = 1;
    private $max_revote = 1;
    private $max_skip = 2;
    private $max_talk = 10;
    private $max_talk_turn = 20;
    private $max_whisper = 10;
    private $max_whisper_turn = 20;
    private $player_num = 0;
    private $random_seed = 0;
    private $role_num_map = [];
    private $time_limit = 1000;
    private $is_enable_no_attack = false;
    private $is_enable_no_execution = false;
    private $is_talk_on_first_day = false;
    private $is_validate_utterance = true;
    private $is_votable_in_first_day = false;
    private $is_vote_visible = true;
    private $is_whisper_before_revote = true;

    public function setRoleIdList(array $role_id_list)
    {
        $this->player_num = 0;
        for ($rid = 0; $rid < Role::ROLE_SIZE; $rid++) {
            $this->role_num_map[$rid] = 0;
        }
        foreach ($role_id_list as $role_id) {
            $this->role_num_map[$role_id]++;
            $this->player_num++;
        }
    }

    public function getMaxAttackRevote()
    {
        return $this->max_attack_revote;
    }
    public function getMaxRevote()
    {
        return $this->max_revote;
    }
    public function getMaxSkip()
    {
        return $this->max_skip;
    }
    public function getMaxTalk()
    {
        return $this->max_talk;
    }
    public function getMaxTalkTurn()
    {
        return $this->max_talk_turn;
    }
    public function getMaxWhisper()
    {
        return $this->max_whisper;
    }
    public function getMaxWhisperTurn()
    {
        return $this->max_whisper_turn;
    }
    public function getPlayerNum()
    {
        return $this->player_num;
    }
    public function getRandomSeed()
    {
        return $this->random_seed;
    }
    public function getRoleNum(Role $role)
    {
        $role_id = $role->get();
        return $role_num_map[$role_id];
    }
    public function getRoleNumMap()
    {
        return $this->role_num_map;
    }
    public function getTimeLimit()
    {
        return $this->time_limit;
    }
    public function isEnableNoAttack()
    {
        return $this->is_enable_no_attack;
    }
    public function isEnableNoExecution()
    {
        return $this->is_enable_no_execution;
    }
    public function isTalkOnFirstDay()
    {
        return $this->is_talk_on_first_day;
    }
    public function isValidateUtterance()
    {
        return $this->is_validate_utterance;
    }
    public function isVotableInFirstDay()
    {
        return $this->is_votable_in_first_day;
    }
    public function isWhisperBeforeRevote()
    {
        return $this->is_whisper_before_revote;
    }
}
