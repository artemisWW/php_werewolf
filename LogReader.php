<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfoCommon.php');
require_once('common/data/Role.php');
require_once('common/lib/Content.php');
require_once('ScoreSet.php');
require_once('LogJapanese.php');
require_once('Debugger.php');
require_once('CoManager.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Common\Net\GameInfoCommon;
use JINRO_JOSEKI\LogJapanese;
use JINRO_JOSEKI\CoManager;
use JINRO_JOSEKI\Debugger;
use JINRO_JOSEKI\ScoreSet;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Vote;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class LogReader
{
    private $fp = null;
    private $log_japanese = null;
    private $debugger = null;

    private $role_id_list = [];  // agent_id順のrole_id
    private $role_size_map = [];  // role_id別のrole_size
    private $agent_name_list = []; // agent_id順のagent_name

    private $game_info_common = null;
    private $co_manager = null;

    private $score_set_list = [];  // agent_id順のscore_set
    private $score_set_talk = null;  // talkから得られる情報のみ

    public function __construct(string $logfile)
    {
        $this->game_info_common = new GameInfoCommon();
        $this->co_manager = new CoManager();
        $this->fp = fopen($logfile, 'r');
    }
    public function __destruct()
    {
        fclose($this->fp);
    }
    public function exec()
    {
        $start_flag = false;  // status行の取り込み前/後
        $prev_line = 'STATUS';
        while ($line = fgets($this->fp)) {
            $line = rtrim($line, "\r\n");
            if (substr($line, 0, 1) === '#') {
                continue;
            }
            // logの読み込み
            $data = explode(',', $line);
            $day = (int)array_shift($data);
            $command = array_shift($data);
            // 日付処理
            $this->day($day);
            // コマンド別の処理
            if ($command === 'status') {
                // ユーザー情報読み込み
                $this->status($data);
            } else {
                if (!$start_flag) {
                    // 初期化
                    $this->init();
                    $start_flag = true;
                }
                // action == false のときは dialog を skip
                if ($this->action($command, $data)) {
                    $this->dialog($line);
                }
            }
            if ($command === 'result') {
                // 終了処理
                break;
            }
        }
    }
    private function init()
    {
        // role_size_mapの作成
        $this->role_size_map = $this->createRoleSizeMap($this->role_id_list);
        // log_japanese
        $this->log_japanese = new LogJapanese($this->role_id_list);
        // game_info_common
        $this->game_info_common->init($this->role_id_list);
        // co_manager
        $this->co_manager->setRoleIdList($this->role_id_list);
        // agent_idごとのstateの作成とroleのセット
        foreach ($this->role_id_list as $agent_id => $role_id) {
            $this->score_set_list[$agent_id] = new ScoreSet($this->role_size_map);
            $this->score_set_list[$agent_id]->action_id($agent_id, Action::ROLE, [$role_id]);
        }
        $this->score_set_talk = new ScoreSet($this->role_size_map);
        // debugger
        $this->debugger = new Debugger($this->game_info_common, $this->co_manager, $this->score_set_list, $this->score_set_talk);
        // 同じ役職を共有(game_info_commonのinit後)
        $this->same_role();
    }
    private function createRoleSizeMap(array $role_id_list)
    {
        $role_size_map = [];
        foreach ($role_id_list as $agent_id => $role_id) {
            if (array_key_exists($role_id, $role_size_map)) {
                $role_size_map[$role_id]++;
            } else {
                $role_size_map[$role_id] = 1;
            }
        }
        ksort($role_size_map);
        return $role_size_map;
    }
    private function dialog(string $log_line)
    {
        while (1) {
            $this->debugger->printAverageAll();
            print $this->log_japanese->toJapanese($log_line) . "\n";
            print '> ';
            $line = fgets(STDIN);
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                break;
            }
            $this->debugger->lineToCommand($line);
        }
    }
    private function day(int $day)
    {
        // 日付が変わっていたら日付の更新
        if ($day !== $this->game_info_common->getDay()) {
            $this->game_info_common->setDay($day);
        }
        if ($day !== $this->co_manager->getDay()) {
            $this->co_manager->setDay($day);
        }
    }
    private function status(array $data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $role_name = array_shift($data);
        $status_name = array_shift($data);
        $agent_name = array_shift($data);
        $this->setRoleId($agent_id, Role::toRoleId($role_name));
        $this->setAgentName($agent_id, $agent_name);
    }
    private function same_role()
    {
        foreach ($this->role_id_list as $agent_id => $role_id) {
            // WEREWOLFとFREEMASONのみ共有
            if ($role_id !== Role::WEREWOLF && $role_id !== Role::FREEMASON) {
                continue;
            }
            $agent_id_list = $this->game_info_common->getRoleMap($role_id);
            foreach ($agent_id_list as $agent_id2) {
                $this->score_set_list[$agent_id]->action_id($agent_id2, Action::ROLE, [$role_id]);
            }
        }
    }
    private function action(string $command, array $data)
    {
        if ($command === 'result') {
            $this->result($data);
        } elseif ($command === 'divine') {
            $this->divine($data);
        } elseif ($command === 'guard') {
            $this->guard($data);
        } elseif ($command === 'execute') {
            $this->execute($data);
        } elseif ($command === 'attack') {
            $this->attack($data);
        } elseif ($command === 'vote') {
            $this->vote($data);
        } elseif ($command === 'attackVote') {
            $this->attackVote($data);
        } elseif ($command === 'talk') {
            return $this->talk($data);
        } elseif ($command === 'whisper') {
            $this->whisper($data);
        }
        return true;
    }
    private function result($data)
    {
        $human_size = (int)array_shift($data);
        $werewolf_size = (int)array_shift($data);
        $team_name = array_shift($data);
        $team_id = Team::toTeamId($team_name);
        // 何もしない
    }
    private function divine($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $target_id = (int)array_shift($data);  $target_id--;
        $species_name = array_shift($data);
        $species_id = Species::toSpeciesId($species_name);
        // 占い師のみ占い結果を反映
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            if ($role_id2 === Role::SEER) {
                $this->score_set_list[$agent_id2]->action_id($target_id, Action::JUDGED, [$species_id]);
            }
        }
        // game_info_commonにセット
        $species_id = $this->game_info_common->setDivineId($agent_id, $target_id);
    }
    private function guard($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $target_id = (int)array_shift($data);  $target_id--;
        $role_name = array_shift($data);
        $role_id = Role::toRoleId($role_name);
        // 護衛結果は何もしない
        // game_info_commonにセット
        $this->game_info_common->setGuardedId($agent_id, $target_id);
    }
    private function execute($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $role_id = $this->role_id_list[$agent_id];
        $species_id = Role::getSpeciesId($role_id);
        // 追放結果は全員取得、霊媒結果は霊媒師のみ取得
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // 人狼1人の場合は追放された人は人狼ではない
            if ($this->getRoleSize(Role::WEREWOLF) === 1) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::VOTED, []);
            }
            // 霊媒結果は霊媒師のみ取得
            if ($role_id2 === Role::MEDIUM) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::JUDGED, [$species_id]);
            }
        }
        if ($this->getRoleSize(Role::WEREWOLF) === 1) {
            $this->score_set_talk->action_id($agent_id, Action::VOTED, []);
        }
        // game_info_commonに追放用と霊媒用の情報をセット
        $this->game_info_common->setExecutedId($agent_id);
        $this->game_info_common->setMediumId($agent_id);
        // co_manager
        $this->co_manager->setExecutedId($agent_id);
    }
    private function attack($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $result = array_shift($data) === 'false' ? false : true;
        // 襲撃が成功したときは全員が取得、失敗したときは騎士のみ取得
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            if ($result) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::ATTACKED, []);
            } elseif ($role_id2 === Role::BODYGUARD) {
                // 襲撃が失敗したとき、騎士はその人が人狼ではないことがわかる
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::JUDGED, [Species::HUMAN]);
            }
        }
        if ($result) {
            $this->score_set_talk->action_id($agent_id, Action::ATTACKED, []);
            // game_info_commonにセット
            $this->game_info_common->setDeadId($agent_id);
            // co_manager
            $this->co_manager->setAttackedId($agent_id);
        }
    }
    private function vote($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $target_id = (int)array_shift($data);  $target_id--;
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            $this->score_set_list[$agent_id2]->action_id($agent_id, Action::VOTE, [$target_id]);
        }
        $this->score_set_talk->action_id($agent_id, Action::VOTE, [$target_id]);
        // game_info_commonにセット
        $vote = new Vote($this->game_info_common->getDay(), $agent_id, $target_id);
        $this->game_info_common->setVote($vote);
    }
    private function attackVote($data)
    {
        $agent_id = (int)array_shift($data);  $agent_id--;
        $target_id = (int)array_shift($data);  $target_id--;
        // game_info_commonにセット
        $vote = new Vote($this->game_info_common->getDay(), $agent_id, $target_id);
        $this->game_info_common->setAttackVote($vote);
    }
    private function talk($data)
    {
        $talk_id = (int)array_shift($data);
        $turn_id = (int)array_shift($data);
        $agent_id = (int)array_shift($data);  $agent_id--;
        $text = array_shift($data);
        $content = new Content(['text' => $text]);
        $topic_id = $content->getTopicId();
        if ($topic_id === Topic::COMINGOUT) {
            $this->comingout($agent_id, $content);
        } elseif ($topic_id === Topic::DIVINED) {
            $this->divined($agent_id, $content);
        } elseif ($topic_id === Topic::IDENTIFIED) {
            $this->identified($agent_id, $content);
        } elseif ($topic_id === Topic::GUARDED) {
            $this->guarded($agent_id, $content);
        } elseif ($topic_id === Topic::OVER) {
            return false;
        } elseif ($topic_id === Topic::SKIP) {
            return false;
        }
        return true;
    }
    private function comingout(int $agent_id, Content $content)
    {
        $role_id = $content->getRoleId();
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // comingoutは自分がtalkした内容も含める
            $this->score_set_list[$agent_id2]->action_id($agent_id, Action::COMINGOUT, [$role_id]);
        }
        $this->score_set_talk->action_id($agent_id, Action::COMINGOUT, [$role_id]);
        // co_manager
        $this->co_manager->setCoRoleId($agent_id, $role_id);
    }
    private function divined(int $agent_id, Content $content)
    {
        $target_id = $content->getTargetId();  $target_id--;
        $species_id = $content->getResultId();
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // 自分がtalkした内容は自分の状態には含めない
            if ($agent_id2 !== $agent_id) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::DIVINED, [$target_id, $species_id]);
            }
        }
        $this->score_set_talk->action_id($agent_id, Action::DIVINED, [$target_id, $species_id]);
        // co_manager
        $this->co_manager->setDivinedMap($agent_id, $target_id, $species_id);
    }
    private function identified(int $agent_id, Content $content)
    {
        $target_id = $content->getTargetId();  $target_id--;
        $species_id = $content->getResultId();
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // 自分がtalkした内容は自分の状態には含めない
            if ($agent_id2 !== $agent_id) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::IDENTIFIED, [$target_id, $species_id]);
            }
        }
        $this->score_set_talk->action_id($agent_id, Action::IDENTIFIED, [$target_id, $species_id]);
        // co_manager
        $this->co_manager->setIdentifiedMap($agent_id, $target_id, $species_id);
    }
    private function guarded(int $agent_id, Content $content)
    {
        $target_id = $content->getTargetId();  $target_id--;
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // 自分がtalkした内容は自分の状態には含めない
            if ($agent_id2 !== $agent_id) {
                $this->score_set_list[$agent_id2]->action_id($agent_id, Action::GUARDED, [$target_id]);
            }
        }
        $this->score_set_talk->action_id($agent_id, Action::GUARDED, [$target_id]);
        // co_manager
        $this->co_manager->setGuardedMap($agent_id, $target_id, true);
    }
    private function whisper($data)
    {
        ;
    }

    private function setRoleId(int $agent_id, int $role_id)
    {
        $this->role_id_list[$agent_id] = $role_id;
    }
    private function getRoleId(int $agent_id)
    {
        return $this->role_id_list[$agent_id];
    }
    private function setAgentName(int $agent_id, string $agent_name)
    {
        $this->agent_name_list[$agent_id] = $agent_name;
    }
    private function getRoleSize(int $role_id)
    {
        if (array_key_exists($role_id, $this->role_size_map)) {
            return $this->role_size_map[$role_id];
        } else {
            return 0;
        }
    }
}

//$logfile = 'log/村村村狼狼占霊騎狂_3-1.log';
$logfile = 'C:/Users/stnight/Downloads/log_cedec2018/log_cedec2018/001/000.log';
if (count($argv) >= 2 && file_exists($argv[1])) {
    $logfile = $argv[1];
}
$log_reader = new LogReader($logfile);
$log_reader->exec();
exit;
