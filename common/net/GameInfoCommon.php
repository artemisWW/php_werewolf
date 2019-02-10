<?php
namespace JINRO_JOSEKI\Common\Net;

require_once('common/net/GameSetting.php');
require_once('common/data/Vote.php');
require_once('common/data/Agent.php');
require_once('common/data/Status.php');
require_once('common/data/Judge.php');

use JINRO_JOSEKI\Common\Data;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Status;
use JINRO_JOSEKI\Common\Data\Vote;
use JINRO_JOSEKI\Common\Data\Judge;
use JINRO_JOSEKI\Common\Data\Talk;

class GameInfoCommon
{
    private $game_setting = null;

    protected $agent_size = 0;  // size of agent_list
    protected $agent_list = [];  // each agent
    private $role_id_list = [];  // each agent
    
    private $day = 0;
    private $status_id_list = [];  // each agent

    // for vote (vote or attack_vote)
    private $vote_list = [];  // each day | each agent
    private $attack_vote_list = [];  // each day | each werewolf

    // for dead
    private $dead_id_list = [];  // each day
    // for attack
    private $attacked_id_list = [];  // each day
    // for execute
    private $executed_id_list = [];  // each day

    // for bodyguard
    private $guarded_id_list = [];  // each day
    // for judge (divine or medium)
    private $medium_judge_list = [];  // each day
    private $divine_judge_list = [];  // each day
    // for talk (talk or whisper)
    private $talk_list = [];  // each day
    private $whisper_list = [];  // each day

    public function __construct(GameSetting $game_setting = null)
    {
        // for log_reader
        if ($game_setting === null) {
            return;
        }
        $this->game_setting = $game_setting;
        // 役職の設定
        $role_num_map = $game_setting->getRoleNumMap();
        $this->agent_size = 0;
        foreach ($role_num_map as $role_id => $role_size) {
            for ($ii = 0; $ii < $role_size; $ii++) {
                $this->role_id_list[] = $role_id;
            }
            $this->agent_size += $role_size;
        }
    }
    /**
     * 全agentをセット
     * @param Agent[] $agent_list
     * @return bool  true:成功, false:agent数が違う
     */
    public function setAgentList(array $agent_list)
    {
        $agent_size = count($agent_list);
        if ($agent_size !== $this->agent_size) {
            return false;
        }
        for ($idx = 0; $idx < $agent_size; $idx++) {
            $this->agent_list[$idx] = $agent_list[$idx];
            $this->agent_list[$idx]->setAgentIdx($idx);
        }
        return true;
    }
    /**
     * 変数の初期化
     * role_id_listを外から入力したときはランダムにしない
     */
    public function init(array $role_id_list = [])
    {
        if (count($role_id_list) === 0) {
            // 役職をランダムに設定
            \shuffle($this->role_id_list);
        } else {
            // 外から役職リストをセット
            $this->role_id_list = $role_id_list;
        }
        // 日付
        $this->day = 0;
        // alive or dead
        for ($idx = 0; $idx < $this->agent_size; $idx++) {
            $this->status_id_list[$idx] = Status::ALIVE;
        }
        $this->vote_list = [];
        $this->attack_vote_list = [];
        $this->dead_id_list = [];
        $this->attacked_id_list = [];
        $this->executed_id_list = [];
        $this->guarded_id_list = [];
        $this->medium_judge_list = [];
        $this->divine_judge_list = [];
        $this->talk_list = [];
        $this->whisper_list = [];
    }

    // for day
    /** @return int */
    public function getDay()
    {
        return $this->day;
    }
    public function setDay($day)
    {
        $this->day = $day;
    }
    public function addDay()
    {
        $this->day++;
    }

    // for me
    /**
     * agentを取得
     * @param int $agent_id
     * @return Agent
     */
    public function getAgent(int $agent_id)
    {
        return $this->agent_list[$agent_id];
    }
    /**
     * role_idを取得
     * @param int $agent_id
     * @return int  role_id
     */
    public function getRoleId(int $agent_id)
    {
        return $this->role_id_list[$agent_id];
    }

    // for common
    /** 
     * 全agentを取得
     * @return Agent[]
     */
    public function getAgentList()
    {
        return $this->agent_list;
    }
    /**
     * 職種リストの取得
     * @return int[]  role_id
     */
    public function getRoleIdList()
    {
        return $this->role_id_list;
    }

    // for dead
    /**
     * 日付別の死亡agent_idを取得
     * @param int $day
     * @return int[]  agent_id
     */
    public function getDeadIdList(int $day)
    {
        if (!array_key_exists($day, $this->dead_id_list)) {
            return [];
        }
        return $this->dead_id_list[$day];
    }
    /**
     * 死亡したagent_idを格納
     * @param int $agent_id
     */
    public function setDeadId(int $agent_id)
    {
        $this->dead_id_list[$this->day][] = $agent_id;
        // status_id_listにも反映
        $this->setStatusId($agent_id, Status::DEAD);
    }

    // for status
    /**
     * 全agentの生存死亡状態を取得
     * @return int[]
     */
    public function getStatusIdList()
    {
        return $this->status_id_list;
    }
    /**
     * 生存死亡状態を格納
     * @param int $agent_id
     * @param int $status_id
     */
    public function setStatusId(int $agent_id, int $status_id)
    {
        $this->status_id_list[$agent_id] = $status_id;
    }

    // for vote
    /**
     * 投票結果を取得
     * @param int $day
     * @return Vote[]
     */
    public function getVoteList(int $day)
    {
        if (!array_key_exists($day, $this->vote_list)) {
            return [];
        }
        return $this->vote_list[$day];
    }
    /**
     * 投票結果を格納
     * @param Vote $vote
     */
    public function setVote(Vote $vote)
    {
        $this->vote_list[$this->day][] = $vote;
    }
    /**
     * 投票結果をクリア
     */
    public function clearVote()
    {
        $this->vote_list[$this->day] = [];
    }
    /**
     * 追放される人を決める
     * @return int  agent_id
     */
    public function decideExecutedId()
    {
        $agent_id = $this->getMaxVotedId($this->vote_list[$this->getDay()]);
        // 死亡処理
        $this->setExecutedId($agent_id);
        return $agent_id;
    }

    // for attack vote
    /**
     * 襲撃投票結果を取得
     * @param int $day
     * @return Vote[]
     */
    public function getAttackVoteList(int $day)
    {
        if (!array_key_exists($day, $this->attack_vote_list)) {
            return [];
        }
        return $this->attack_vote_list[$day];
    }
    /**
     * 襲撃投票結果を格納
     * @param Vote $vote
     */
    public function setAttackVote(Vote $vote)
    {
        $this->attack_vote_list[$this->day][] = $vote;
    }
    /**
     * 襲撃投票結果をクリア
     */
    public function clearAttackVote()
    {
        $this->attack_vote_list[$this->day] = [];
    }
    /**
     * 襲撃される人を決める
     * @return int  agent_id
     */
    public function decideAttackedId()
    {
        $attacked_id = $this->getMaxVotedId($this->attack_vote_list[$this->getDay()]);
        $this->setAttackedId($attacked_id);
        return $attacked_id;
    }
    /**
     * 襲撃結果を返す
     * @param int $attacked_id
     * @return bool  true:襲撃成功, false:襲撃失敗
     */
    public function getAttackResult($attacked_id)
    {
        $guarded_id = $this->getGuardedId($this->getDay());
        if ($guarded_id === $attacked_id) {
            // 襲撃失敗
            return false;
        } else {
            // 死亡処理
            $this->setDeadId($attacked_id);
            return true;
        }
    }

    // for executed agent
    /**
     * 追放されたagent_idを取得
     * @param int $day
     * @return int  agent_id
     */
    public function getExecutedId(int $day)
    {
        if (!array_key_exists($day, $this->executed_id_list)) {
            return -1;
        }
        return $this->executed_id_list[$day];
    }
    /**
     * 追放された人を格納
     * @param int $agent_id
     */
    public function setExecutedId(int $agent_id)
    {
        $this->executed_id_list[$this->day] = $agent_id;
        // status_id_listにも反映
        $this->setStatusId($agent_id, Status::DEAD);
    }

    // for bodyguard
    /**
     * 護衛した人を取得
     * @param int $day
     * @return int  agent_id
     */
    public function getGuardedId(int $day)
    {
        if (!array_key_exists($day, $this->guarded_id_list)) {
            return -1;
        }
        return $this->guarded_id_list[$day];
    }
    /**
     * 護衛した人を格納
     * @param int $agent_id
     * @param int $target_id
     */
    public function setGuardedId(int $agent_id, int $target_id)
    {
        $this->guarded_id_list[$this->day] = $target_id;
    }

    // for medium_judge
    /**
     * 霊媒結果を取得
     * @paran int $day
     * @return Judge
     */
    public function getMediumJudge(int $day)
    {
        if (!array_key_exists($day, $this->medium_judge_list)) {
            return null;
        }
        return $this->medium_judge_list[$day];
    }
    /**
     * 霊媒結果を格納
     * @param int $target_id
     * @return int
     */
    public function setMediumId(int $target_id)
    {
        $role_id = $this->getRoleId($target_id);
        $species_id = Role::getSpeciesId($role_id);
        $judge = new Judge($this->getDay(), -1, $target_id, $species_id);
        $this->medium_judge_list[$this->day] = $judge;
        return $species_id;
    }
    // for divine_judge
    /**
     * 占い結果を取得
     * @param int $day
     * @return Judge
     */
    public function getDivineJudge(int $day)
    {
        if (!array_key_exists($day, $this->divine_judge_list)) {
            return null;
        }
        return $this->divine_judge_list[$day];
    }
    /**
     * 占い結果を格納
     * @param int $agent_id
     * @param int $target_id
     * @return int  $species_id
     */
    public function setDivineId(int $agent_id, int $target_id)
    {
        $role_id = $this->getRoleId($target_id);
        $species_id = Role::getSpeciesId($role_id);
        $judge = new Judge($this->getDay(), $agent_id, $target_id, $species_id);;
        $this->divine_judge_list[$this->day] = $judge;
        return $species_id;
    }

    // for werewolf
    /**
     * 襲撃結果を取得
     * @param int $day
     * @return int
     */
    public function getAttackedId($day)
    {
        if (!array_key_exists($day, $this->attacked_id_list)) {
            return -1;
        }
        return $this->attacked_id_list[$day];
    }
    /**
     * 襲撃結果を格納
     * @param int $agent_id
     */
    public function setAttackedId(int $agent_id)
    {
        $this->attacked_id_list[$this->day] = $agent_id;
    }

    // for werewolf and freemason
    /**
     * 同じ役職のagent_idを取得
     * @param int $role_id
     * @return int[]
     */
    public function getRoleMap(int $rid)
    {
        $agent_id_list = [];
        foreach ($this->role_id_list as $agent_id => $role_id) {
            if ($role_id === $rid) {
                $agent_id_list[] = $agent_id;
            }
        }
        return $agent_id_list;
    }

    // for talk
    /**
     * 会話結果を取得
     * @param int $day
     * @return Talk[]
     * */
    public function getTalkLIst(int $day)
    {
        if (!array_key_exists($day, $this->talk_list)) {
            return [];
        }
        return $this->talk_list[$day];
    }
    /**
     * 会話結果を格納
     * @param Talk $talk
     */
    public function setTalk(Talk $talk)
    {
        $this->talk_list[$this->day][] = $talk;
    }

    // for whisper
    /**
     * 囁き結果を取得 
     * @param int $day
     * @return Talk[]
     */
    public function getWhisperLIst(int $day)
    {
        if (!array_key_exists($day, $this->whisper_list)) {
            return [];
        }
        return $this->whisper_list[$day];
    }
    /**
     * 囁き結果を格納
     * @param Talk $talk
     */
    public function setWhisper(Talk $talk)
    {
        $this->whisper_list[$this->day][] = $talk;
    }

    // for end
    /**
     * 終了判定処理
     * @return false or [human_size, werewolf_size, team_id]
     */
    public function end()
    {
        $werewolf_size = 0;
        $human_size = 0;
        foreach ($this->status_id_list as $agent_id => $status_id) {
            if ($status_id === Status::DEAD) {
                continue;
            }
            $role_id = $this->getRoleId($agent_id);
            if ($role_id === Role::WEREWOLF) {
                $werewolf_size++;
            } else {
                $human_size++;
            }
        }
        if ($werewolf_size === 0) {
            return [$human_size, $werewolf_size, Team::VILLAGER];
        }
        if ($werewolf_size >= $human_size) {
            return [$human_size, $werewolf_size, Team::WEREWOLF];
        }
        return false;
    }

    /**
     * 投票から最大のIDを取得
     * @param Vote[]
     * @return int  agent_id
     */
    private function getMaxVotedId($vote_list)
    {
        // 集計
        $target_num = [];
        for ($ii = 0; $ii < $this->agent_size; $ii++) {
            $target_num[$ii] = 0;
        }
        foreach ($vote_list as $vote) {
            $target_id = $vote->getTargetId();
            $target_num[$target_id]++;
        }
        // 最大値の取得
        $max_id_list = [];
        $max_num = 0;
        foreach ($target_num as $target_id => $num) {
            if ($num > $max_num) {
                $max_num = $num;
                $max_id_list = [];
                $max_id_list[] = $target_id;
            } elseif ($num === $max_num) {
                $max_id_list[] = $target_id;
            }
        }
        // 同数のときはランダム
        \shuffle($max_id_list);
        return $max_id_list[0];
    }
}
