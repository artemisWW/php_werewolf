<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');
require_once('LogJapanese.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\LogJapanese;
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
use JINRO_JOSEKI\Client\Lib\Topic;

class Log
{
    private $game_info_common = null;
    private $log_japanese = null;
    private $day = 0;
    private $fp = null;
    private $str0 = '';  // STDOUT用
    private $str1 = '';  // FILE用

    const PHASE_NAME_LIST = [
        '# GAME_START',
        '# GAME_OVER',
        '## TALK',
        '## VOTE',
        '## DIVINE',
        '## WHISPER',
        '## GUARD',
        '## ATTACK',
    ];
    CONST PHASE_JAPANESE_NAME_LIST = [
        'ゲーム開始',
        'ゲーム終了',
        '会話フェーズ',
        '追放フェーズ',
        '占いフェーズ',
        '囁きフェーズ',
        '護衛フェーズ',
        '襲撃フェーズ',
    ];

    public function __construct(GameInfoCommon &$game_info_common, string $filename = '')
    {
        $this->game_info_common = &$game_info_common;
        if ($filename !== '') {
            $this->fp = fopen($filename, 'w');
        } else {
            $this->fp = null;
        }
    }
    public function __destruct()
    {
        if ($this->fp !== null) {
            fclose($this->fp);
        }
    }
    public function init()
    {
        // game_info_common->initの後のrole_id_listのセット後にする必要あり
        $this->log_japanese = new LogJapanese($this->game_info_common->getRoleIdList());
        $this->day = 0;
    }
    public function headInit()
    {
        $this->head(0);
    }
    public function headFinish()
    {
        $this->head(1);
    }
    public function headTalk()
    {
        $this->head(2);
    }
    public function headVote()
    {
        $this->head(3);
    }
    public function headDivine()
    {
        $this->head(4);
    }
    public function headWhisper()
    {
        $this->head(5);
    }
    public function headGuard()
    {
        $this->head(6);
    }
    public function headAttack()
    {
        $this->head(7);
    }
    public function headDay()
    {
        $this->day = $this->game_info_common->getDay();
        $this->str0 = '[' . $this->day . '日目]';
        $this->str1 = '# day = ' . $this->day;
        $this->output();
    }

    public function role($agent_id)
    {
        $agent = $this->game_info_common->getAgent($agent_id);
        $name = $agent->toString();
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $this->str0 = $agent_id . ': ' . $name . ' の役職は' . Role::ToJapaneseName($role_id) . 'です';
        $this->str1 = $this->day . ',status,' . $agent_id . ',' . Role::ToRoleName($role_id) . ',ALIVE,' . $name;
        $this->output();
    }
    public function talk(int $talk_id, int $turn_id, int $agent_id, $talk_text)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        if ($talk_text !== 'SKIP') {
            $this->str0 = $this->log_japanese->talk($talk_id, $turn_id, $agent_id, $talk_text);
            $this->str1 = $this->day . ',talk,' . $talk_id . ',' . $turn_id . ',' . $agent_id . ',' . $talk_text;
        }
        $this->output();
    }
    public function vote(int $agent_id, int $target_id)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $target_role_id = $this->game_info_common->getRoleId($target_id);
        $this->str0 = $this->log_japanese->vote($agent_id, $target_id);
        $this->str1 = $this->day . ',vote,' . $agent_id . ',' . $target_id;
        $this->output();
    }
    public function attackVote(int $agent_id, int $target_id)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $target_role_id = $this->game_info_common->getRoleId($target_id);
        $this->str0 = $this->log_japanese->attackVote($agent_id, $target_id);
        $this->str1 = $this->day . ',attackVote,' . $agent_id . ',' . $target_id;
        $this->output();
    }
    public function execute(int $agent_id)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $this->str0 = $this->log_japanese->execute($agent_id);
        $this->str1 = $this->day . ',execute,' . $agent_id . ',' . Role::ToRoleName($role_id);
        $this->output();
    }
    public function attack(int $agent_id, bool $result)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $this->str0 = $this->log_japanese->attack($agent_id, $result);
        $this->str1 = $this->day . ',attack,' . $agent_id . ',' . var_export($result, true);
        $this->output();
    }
    public function divine($agent_id, $target_id, $species_id)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $target_role_id = $this->game_info_common->getRoleId($target_id);
        $this->str0 = $this->log_japanese->divine($agent_id, $target_id, $species_id);
        $this->str1 =  $this->day . ',divine,' . $agent_id . ',' . $target_id . ',' . Species::toSpeciesName($species_id);
        $this->output();
    }
    public function guard(int $agent_id, $target_id)
    {
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $target_role_id = $this->game_info_common->getRoleId($target_id);
        $this->str0 = $this->log_japanese->guard($agent_id, $target_id);
        $this->str1 =  $this->day . ',guard,' . $agent_id . ',' . $target_id . ',' . Role::toRoleName($role_id);
        $this->output();
    }
    public function end($human_size, $werewolf_size, $team_id)
    {
        $this->str0 = $this->log_japanese->result($human_size, $werewolf_size, $team_id);
        $this->str1 =  $this->day . ',result,' . $human_size . ',' . $werewolf_size . ',' . Team::toTeamName($team_id);
        $this->output();
    }
    public function status($agent_id)
    {
        $agent = $this->game_info_common->getAgent($agent_id);
        $name = $agent->toString();
        $role_id = $this->game_info_common->getRoleId($agent_id);
        $status_id = $this->game_info_common->getStatusIdList()[$agent_id];
        $this->str0 = $this->log_japanese->status($agent_id, $status_id, $name);
        $this->str1 = $this->day . ',status,' . $agent_id . ',' . Role::ToRoleName($role_id) . ',' . Status::toStatusName($status_id) . ',' . $name;
        $this->output();
    }

    private function head(int $phase)
    {
        $this->str0 = '[' . self::PHASE_JAPANESE_NAME_LIST[$phase] . ']';
        $this->str1 = self::PHASE_NAME_LIST[$phase];
        $this->output();
    }
    private function output()
    {
        if ($this->str0 !== '') {
            fputs(STDOUT, $this->str0 . "\n");
            $this->str0 = '';
        }
        if ($this->str1 !== '' && $this->fp !== null) {
            fputs($this->fp, $this->str1 . "\n");
            $this->str1 = '';
        }
    }
}
