<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');
require_once('log.php');
require_once('players/Villager.php');
require_once('players/Bodyguard.php');
require_once('players/Medium.php');
require_once('players/Seer.php');
require_once('players/Possessed.php');
require_once('players/Werewolf.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\Common\Net\GameInfoCommon;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Agent;
use JINRO_JOSEKI\Common\Data\Vote;
use JINRO_JOSEKI\Common\Data\Status;
use JINRO_JOSEKI\Common\Data\Talk;
use JINRO_JOSEKI\Common\Lib\Content;

class Game
{
    // for game
    /** @var GameSetting */
    private $game_setting = null;
    /** @var GameInfoCommon */
    private $game_info_common = null;
    // for log
    /** @var Log */
    private $log = null;
    // for day
    /** @var int */
    private $day = 0;
    // for player
    /** @var int */
    private $player_size = 0;
    /** @var Player[] */
    private $player_list = [];
    /** @var GameInfo[] */
    private $game_info_list = [];

    public function __construct(GameSetting $game_setting, GameInfoCommon $game_info_common, string $logfile = '')
    {
        $this->game_setting = $game_setting;
        $this->game_info_common = $game_info_common;
        $this->log = new Log($game_info_common, $logfile);
    }
    public function exec()
    {
        $this->init();
        // day = 0
        $this->dayStart();
        $this->whisper();
        $this->divine();
        // day > 0
        while (1) {
            // day
            $end = $this->end();
            if ($end !== false) {
                break;
            }
            $this->addDay();
            $this->dayStart();
            $this->talk();
            $this->vote();
            // night
            $end = $this->end();
            if ($end !== false) {
                break;
            }
            $this->divine();
            $this->whisper();
            $this->guard();
            $this->attack();
        }
        $this->finish();
        return $end;  // team_id
    }
    /**
     * 変数の初期化
     */
    private function init()
    {
        $this->game_info_common->init();
        $this->log->init();
        $this->log->headInit();
        $this->day = $this->game_info_common->getDay();
        // init each player
        $role_id_list = $this->game_info_common->getRoleIdList();
        $this->player_size = count($role_id_list);
        for ($ii = 0; $ii < $this->player_size; $ii++) {
            // set role
            $role_id = $role_id_list[$ii];
            if ($role_id === Role::VILLAGER) {
                $this->player_list[$ii] = new Players\Villager();
            } elseif ($role_id === Role::BODYGUARD) {
                $this->player_list[$ii] = new Players\Bodyguard();
            } elseif ($role_id === Role::MEDIUM) {
                $this->player_list[$ii] = new Players\Medium();
            } elseif ($role_id === Role::SEER) {
                $this->player_list[$ii] = new Players\Seer();
            } elseif ($role_id === Role::POSSESSED) {
                $this->player_list[$ii] = new Players\Possessed();
            } elseif ($role_id === Role::WEREWOLF) {
                $this->player_list[$ii] = new Players\Werewolf();
            }
            // game_info
            $this->game_info_list[$ii] = new GameInfo($ii, $this->game_info_common);
            $this->player_list[$ii]->initialize($this->game_info_list[$ii], $this->game_setting);
            $this->log->role($ii);
        }
    }
    /**
     * 終了処理
     */
    private function finish()
    {
        for ($ii = 0; $ii < $this->player_size; $ii++) {
            $this->log->status($ii);
            $this->player_list[$ii]->finish();
        }
        $this->log->headFinish();
    }
    /**
     * 1日の開始処理
     */
    private function dayStart()
    {
        $this->day = $this->game_info_common->getDay();
        //
        $this->log->headDay($this->day);
        for ($ii = 0; $ii < $this->player_size; $ii++) {
            $this->player_list[$ii]->dayStart();
        }
    }
    /**
     * 1日追加
     */
    private function addDay()
    {
        $this->game_info_common->addDay();
    }
    /**
     * 占い処理
     */
    private function divine()
    {
        $this->log->headDivine();
        foreach ($this->game_info_common->getStatusIdList() as $agent_id => $status_id) {
            if ($status_id === STATUS::DEAD) {
                continue;
            }
            if ($this->game_info_common->getRoleId($agent_id) !== Role::SEER) {
                continue;
            }
            // 生存している占い師のみ処理
            $this->player_list[$agent_id]->update($this->game_info_list[$agent_id]);
            $target_id = $this->player_list[$agent_id]->divine();
            // set
            $species_id = $this->game_info_common->setDivineId($agent_id, $target_id);
            // log
            $this->log->divine($agent_id, $target_id, $species_id);
        }
    }
    /**
     * 護衛処理
     */
    private function guard()
    {
        $this->log->headGuard();
        foreach ($this->game_info_common->getStatusIdList() as $agent_id => $status_id) {
            if ($status_id === STATUS::DEAD) {
                continue;
            }
            if ($this->game_info_common->getRoleId($agent_id) !== Role::BODYGUARD) {
                continue;
            }
            // 生存している騎士のみ処理
            $this->player_list[$agent_id]->update($this->game_info_list[$agent_id]);
            $target_id = $this->player_list[$agent_id]->guard();
            // set
            $this->game_info_common->setGuardedId($agent_id, $target_id);
            // log
            $this->log->guard($agent_id, $target_id);
        }
    }
    /**
     * 投票処理
     */
    private function vote()
    {
        $this->log->headVote();
        foreach ($this->game_info_common->getStatusIdList() as $agent_id => $status_id) {
            if ($status_id === STATUS::DEAD) {
                continue;
            }
            // 生存している全役職が処理
            $this->player_list[$agent_id]->update($this->game_info_list[$agent_id]);
            $target_id = $this->player_list[$agent_id]->vote();
            // set
            $vote = new Vote($this->day, $agent_id, $target_id);
            $this->game_info_common->setVote($vote);
            // log
            $this->log->vote($agent_id, $target_id);
        }
        // 追放される人を決める
        $executed_id = $this->game_info_common->decideExecutedId();
        // 霊媒用のデータをセット
        $this->game_info_common->setMediumId($executed_id);
        // log
        $this->log->execute($executed_id);
    }
    /**
     * 襲撃処理
     */
    private function attack()
    {
        $this->log->headAttack();
        foreach ($this->game_info_common->getStatusIdList() as $agent_id => $status_id) {
            if ($status_id === STATUS::DEAD) {
                continue;
            }
            if ($this->game_info_common->getRoleId($agent_id) !== Role::WEREWOLF) {
                continue;
            }
            // 生存している人狼のみ処理
            $this->player_list[$agent_id]->update($this->game_info_list[$agent_id]);
            $target_id = $this->player_list[$agent_id]->attack();
            // set
            $vote = new Vote($this->day, $agent_id, $target_id);
            $this->game_info_common->setAttackVote($vote);
            // log
            $this->log->attackVote($agent_id, $target_id);
        }
        // 襲撃された人を取得
        $attacked_id = $this->game_info_common->decideAttackedId();
        // 襲撃されたか護衛されたかを確認
        $result = $this->game_info_common->getAttackResult($attacked_id);
        // log
        $this->log->attack($attacked_id, $result);
    }
    /**
     * 会話処理
     */
    private function talk()
    {
        // 生存しているagent_idを事前に取得
        $alive_id_list = [];
        foreach ($this->game_info_common->getStatusIdList() as $agent_id => $status_id) {
            if ($status_id === STATUS::DEAD) {
                continue;
            }
            $alive_id_list[] = $agent_id;
        }
        //
        $this->log->headTalk();
        $talk_id = 0;
        for ($turn_id = 0; $turn_id < $this->game_setting->getMaxTalkTurn(); $turn_id++) {
            $skip_size = 0;
            // talkの順番は毎回ランダム
            \shuffle($alive_id_list);
            foreach ($alive_id_list as $agent_id) {
                $this->player_list[$agent_id]->update($this->game_info_list[$agent_id]);
                $talk_text = $this->player_list[$agent_id]->talk();
                // set
                $talk = new Talk($this->day, $talk_id, $turn_id, $agent_id, $talk_text);
                $this->game_info_common->setTalk($talk);
                $talk_id++;
                // turn内のskip数を数える
                if ($talk_text === 'SKIP') {
                    $skip_size++;
                }
                // log
                $this->log->talk($talk_id, $turn_id, $agent_id, $talk_text);
            }
            // 全員skipのときは終了
            if ($skip_size === count($alive_id_list)) {
                break;
            }
        }
    }
    /**
     * 囁き処理
     */
    private function whisper()
    {

    }
    /**
     * 終了条件のチェック
     * @return false or team_id
     */
    private function end()
    {
        $result = $this->game_info_common->end();
        if ($result === false) {
            return false;
        }
        // log
        list($human_size, $werewolf_size, $team_id) = $result;
        $this->log->end($human_size, $werewolf_size, $team_id);
        return $team_id;
    }
}
