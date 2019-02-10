<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');

use JINRO_JOSEKI;
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

class LogJapanese
{
    private $role_id_list = [];
    public function __construct(array $role_id_list)
    {
        $this->role_id_list = $role_id_list;
    }
    public function toJapanese(string $line)
    {
        $data = explode(',', $line);
        $day = (int)array_shift($data);
        $command = array_shift($data);
        if ($command === 'status') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $role_name = array_shift($data);
            $status_name = array_shift($data);
            $status_id = Status::toStatusId($status_name);  $target_id--;
            $name = array_shift($data);
            return $this->status($agent_id, $status_id, $name);
        } elseif ($command === 'divine') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $target_id = (int)array_shift($data);  $target_id--;
            $species_name = array_shift($data);
            $species_id = Species::toSpeciesId($species_name);
            return $this->divine($agent_id, $target_id, $species_id);
        } elseif ($command === 'execute') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            return $this->execute($agent_id);
        } elseif ($command === 'guard') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $target_id = (int)array_shift($data);  $target_id--;
            $role_name = array_shift($data);
            $role_id = Role::toRoleId($role_name);
            return $this->guard($agent_id, $target_id);
        } elseif ($command === 'attack') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $boolean = array_shift($data) === 'false' ? false : true;
            return $this->attack($agent_id, $boolean);
        } elseif ($command === 'vote') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $target_id = (int)array_shift($data);  $target_id--;
            return $this->vote($agent_id, $target_id);
        } elseif ($command === 'attackVote') {
            $agent_id = (int)array_shift($data);  $agent_id--;
            $target_id = (int)array_shift($data);  $target_id--;
            return $this->attackVote($agent_id, $target_id);
        } elseif ($command === 'talk') {
            $talk_id = (int)array_shift($data);
            $turn_id = (int)array_shift($data);
            $agent_id = (int)array_shift($data);  $agent_id--;
            $talk_text = array_shift($data);
            return $this->talk($talk_id, $turn_id, $agent_id, $talk_text);            
        } elseif ($command === 'whisper') {
            ;
        } elseif ($command === 'result') {
            $human_size = (int)array_shift($data);
            $werewolf_size = (int)array_shift($data);
            $team_name = array_shift($data);
            $team_id = Team::toTeamId($team_name);
            return $this->result($human_size, $werewolf_size, $team_id);
        }
        return $line;
    }
    public function divine($agent_id, $target_id, $species_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $target_role_id = $this->getRoleId($target_id);
        return $agent_id . '(' . Role::toJapaneseName($role_id) . ') が ' . $target_id . '(' . Role::toJapaneseName($target_role_id) . ') を占った結果は ' . Species::toJapaneseName($species_id) . ' でした';
    }
    public function execute(int $agent_id)
    {
        $role_id = $this->getRoleId($agent_id);
        return $agent_id . '(' . Role::toJapaneseName($role_id) . ') は追放されました';
    }
    public function guard(int $agent_id, int $target_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $target_role_id = $this->getRoleId($target_id);
        return $agent_id . '(' . Role::toJapaneseName($role_id) . ') が ' . $target_id . '(' . Role::toJapaneseName($target_role_id) . ') を護衛しました';
    }
    public function attack(int $agent_id, bool $result)
    {
        $role_id = $this->getRoleId($agent_id);
        if ($result) {
            return $agent_id . '(' . Role::toJapaneseName($role_id) . ') は襲撃されました';
        } else {
            return $agent_id . '(' . Role::toJapaneseName($role_id) . ') は襲撃されましたが、護衛されました';
        }
    }
    public function vote(int $agent_id, int $target_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $target_role_id = $this->getRoleId($target_id);
        return $agent_id . '(' . Role::toJapaneseName($role_id) . ') は ' . $target_id . '(' . Role::toJapaneseName($target_role_id) . ') に投票しました';
    }
    public function attackVote(int $agent_id, int $target_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $target_role_id = $this->getRoleId($target_id);
        return $agent_id . '(' . Role::toJapaneseName($role_id) . ') は ' . $target_id . '(' . Role::toJapaneseName($target_role_id) . ') に襲撃投票しました';
    }
    public function talk(int $talk_id, int $turn_id, int $agent_id, string $talk_text)
    {
        $role_id = $this->getRoleId($agent_id);
        if ($talk_text === 'SKIP') {
            return '';
        } else {
            $talk_text_for_view = $this->talkForView($agent_id, $talk_text);
            return $agent_id . '(' . Role::toJapaneseName($role_id) . '): ' . $talk_text_for_view;
        }
    }
    private function talkForView(int $agent_id, string $talk_text)
    {
        $content = new Content(['text' => $talk_text]);
        $topic_id = $content->getTopicId();
        $str = '';
        if ($topic_id === Topic::COMINGOUT) {
            $role_id = $content->getRoleId();
            $target_id = $content->getTargetId();  $target_id--;
            if ($agent_id === $target_id) {
                $str = Role::toJapaneseName($role_id) . ' でカミングアウトします';
            } else {
                $str = $target_id . ' は ' . Role::toJapaneseName($role_id) . ' でカミングアウトします';
            }
        } elseif ($topic_id === Topic::DIVINED) {
            $target_id = $content->getTargetId();  $target_id--;
            $result_id = $content->getResultId();
            $role_id = $this->getRoleId($target_id);
            $str = '占った結果、' . $target_id . '(' . Role::toJapaneseName($role_id) . ') は ' . Species::toJapaneseName($result_id) . ' でした';
        } elseif ($topic_id === Topic::IDENTIFIED) {
            $target_id = $content->getTargetId();  $target_id--;
            $result_id = $content->getResultId();
            $role_id = $this->getRoleId($target_id);
            $str = '霊媒した結果、' . $target_id . '(' . Role::toJapaneseName($role_id) . ') は ' . Species::toJapaneseName($result_id) . ' でした';
        } elseif ($topic_id === Topic::GUARDED) {
            $target_id = $content->getTargetId();  $target_id--;
            $role_id = $this->getRoleId($target_id);
            $str = $target_id . '(' . Role::toJapaneseName($role_id) . ') を護衛しました';
        } elseif ($topic_id === Topic::ESTIMATE) {
            $target_id = $content->getTargetId();  $target_id--;
            $role_id = $this->getRoleId($target_id);
            $role_id2 = $content->getRoleId();
            $str = $target_id . '(' . Role::toJapaneseName($role_id) . ') は ' . Role::toJapaneseName($role_id2) . ' だと思います';
        } elseif ($topic_id === Topic::VOTE) {
            $target_id = $content->getTargetId();  $target_id--;
            $role_id = $this->getRoleId($target_id);
            $str = $target_id . '(' . Role::toJapaneseName($role_id) . ') に投票します';
        } else {
            $str = $talk_text;
        }
        return $str;
    }
    public function result(int $human_size, int $werewolf_size, int $team_id)
    {
        return Team::toJapaneseName($team_id) . '陣営の勝利 (人間の数 = ' . $human_size . ', 人狼の数 = ' . $werewolf_size . ')';
    }
    public function status(int $agent_id, int $status_id, string $name)
    {
        $role_id = $this->getRoleId($agent_id);
        if ($status_id === Status::ALIVE) {
            return $agent_id . '(' . Role::ToJapaneseName($role_id) . '): '. $name . ' は' . Status::toJapaneseName($status_id) . 'しています';
        } else {
            return $agent_id . '(' . Role::ToJapaneseName($role_id) . '): '. $name . ' は' . Status::toJapaneseName($status_id) . 'しました';
        }
    }

    private function getRoleId(int $agent_id)
    {
        return $this->role_id_list[$agent_id];
    }
}
