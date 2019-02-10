<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfoCommon.php');
require_once('common/data/Role.php');
require_once('ScoreAverage.php');
require_once('Hypothesize.php');
require_once('DecideVote.php');
require_once('CoManager.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Common\Net\GameInfoCommon;
use JINRO_JOSEKI\CoManager;
use JINRO_JOSEKI\ScoreSet;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Hypothesize;
use JINRO_JOSEKI\Hypothesys;
use JINRO_JOSEKI\DecideVote;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class Debugger
{
    private $role_id_list = [];  // agent_id順のrole_idの配列
    private $role_size_map = [];  // role_id別のrole_size
    private $co_manager = null;
    private $score_set_list = [];
    private $score_set_talk = null;  // talkから得られる情報のみ

    public function __construct(GameInfoCommon &$game_info_common, CoManager &$co_manager, array &$score_set_list, ScoreSet &$score_set_talk)
    {
        $this->game_info_common = &$game_info_common;
        $this->role_id_list = $this->game_info_common->getRoleIdList();
        $this->co_manager = &$co_manager;
        $this->score_set_list = &$score_set_list;
        $this->score_set_talk = &$score_set_talk;

        foreach ($this->role_id_list as $agent_id => $role_id) {
            if (\array_key_exists($role_id, $this->role_size_map)) {
                $this->role_size_map[$role_id]++;
            } else {
                $this->role_size_map[$role_id] = 1;
            }
        }
        ksort($this->role_size_map);
    }
    public function lineToCommand($line)
    {
        $arg0 = substr($line, 0, 1);
        $arg1 = substr($line, 1);
        if ($arg0 === 'a' && $arg1 === 'a') {
            $this->printAverageAll();
        } elseif ($arg0 === 'p' && (int)$arg1 >= 0 && (int)$arg1 < count($this->role_id_list)) {
            $agent_id = (int)$arg1;
            $this->printScoreSetAll($agent_id);
        } elseif ($arg0 === 'a' && (int)$arg1 >= 0 && (int)$arg1 < count($this->role_id_list)) {
            $agent_id = (int)$arg1;
            $this->printAverage($agent_id);
        } elseif ($arg0 === 'd' && (int)$arg1 >= 0 && (int)$arg1 < count($this->role_id_list)) {
            $agent_id = (int)$arg1;
            $this->printHypo($agent_id);
        } elseif ($arg0 === 'v' && (int)$arg1 >= 0 && (int)$arg1 < count($this->role_id_list)) {
            $agent_id = (int)$arg1;
            $this->printVote($agent_id);
        }
    }
    /** 自分の全パターンのscoreを出力 */
    private function printScoreSetAll(int $agent_id)
    {
        $agent_str = $this->getAgentStr($agent_id);
        $co_role_str = $this->getComingoutRoleStr($agent_id);
        print $agent_str . ': ' . $co_role_str . "\n";
        $this->co_manager->printCoAll();
        $this->score_set_list[$agent_id]->printAllScore();
    }
    /** 自分の平均scoreを出力 */
    private function printAverage(int $agent_id)
    {
        $agent_str = $this->getAgentStr($agent_id);
        $co_role_str = $this->getComingoutRoleStr($agent_id);
        print $agent_str . ' ' . $co_role_str . "\n";
        $this->co_manager->printCoAll();
        $average = new ScoreAverage($this->score_set_list[$agent_id], $this->co_manager);
        $this->printHeader('AVE   ');
        $this->printScoreMatrix($average->getScoreMatrix());
    }
    public function printAverageAll()
    {
        print '       ';
        foreach ($this->role_id_list as $agent_id => $role_id) {
            print sprintf("%02d", $agent_id);
        }
        print "\n";
        foreach ($this->role_id_list as $agent_id => $role_id) {
            $average = new ScoreAverage($this->score_set_list[$agent_id], $this->co_manager);
            $str_list = $average->getStrList();
            $agent_str = $this->getAgentStr($agent_id);
            print $agent_str . ':';
            print implode('', $str_list) . "\n";
        }
        print '       ';
        foreach ($this->role_id_list as $agent_id => $role_id) {
            print sprintf("%02d", $agent_id);
        }
        print "\n";
        $this->co_manager->printAllCompact();
    }
    /** 占い先の仮説を出力 */
    private function printHypo(int $agent_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $candidate_list = $this->getCandidateList($agent_id, $role_id);
        $hypothesize = new Hypothesize($agent_id, $role_id, $this->score_set_list[$agent_id], $this->score_set_talk, $this->co_manager);
        $hypo_list = $hypothesize->calc($candidate_list, [Hypothesize::DIVINE]);
        foreach ($hypo_list as $hypo) {
            $hypo->print();
        }
    }
    /** 投票先の仮説を出力 */
    private function printVote(int $agent_id)
    {
        $role_id = $this->getRoleId($agent_id);
        $candidate_list = $this->getCandidateList($agent_id, $role_id);
        $decide_vote = new DecideVote($agent_id, $role_id, $this->score_set_list[$agent_id], $this->score_set_talk, $this->co_manager);
        $score_list = $decide_vote->calc($candidate_list);
        $agent_id = $decide_vote->select($score_list);
        foreach ($score_list as $user_id => $score) {
            print $user_id . ": " . $score . "\n";
        }
    }
    /** 占い先等の候補者を取得 */
    protected function getCandidateList(int $agent_id, int $role_id)
    {
        $candidate_list = [];
        foreach ($this->role_id_list as $agent_id2 => $role_id2) {
            // 死亡している人を除く
            if (\in_array($agent_id2, $this->co_manager->getDeadIdList(), true)) {
                continue;
            }
            // 追放された人を除く
            if (\in_array($agent_id2, $this->co_manager->getExecutedIdList(), true)) {
                continue;
            }
            // 自分を除く
            if ($agent_id2 === $agent_id) {
                continue;
            }
            // WEREWOLF or FREEMASONのとき の仲間は除く
            if ($role_id === Role::WEREWOLF || $role_id === Role::FREEMASON) {
                if ($role_id2 === $role_id) {
                    continue;
                }
            }
            $candidate_list[] = $agent_id2;
        }
        return $candidate_list;
    }
    /** 自分の情報を文字列で取得 ex:01(霊) */
    private function getAgentStr(int $agent_id)
    {
        $role_id = $this->getRoleId($agent_id);
        return sprintf("%02d", $agent_id) . '(' . Role::toJapaneseName($role_id) . ')';
    }
    /** 自分のカミングアウト文字列を取得 ex:CO(占) */
    private function getComingoutRoleStr(int $agent_id)
    {
        $co_role_id = $this->co_manager->getCoRoleId($agent_id);
        if ($co_role_id === Role::UNDEF) {
            return '';
        }
        return 'CO(' . Role::toJapaneseName($co_role_id) . ')';
    }
    private function printHeader(string $str)
    {
        print $str;
        foreach ($this->role_size_map as $role_id => $role_size) {
            print '    ' . Role::toJapaneseName($role_id);
        }
        print "\n";
    }
    private function printScoreList(array $score_list)
    {
        foreach ($score_list as $score) {
            printf(" %.3f", $score);
        }
        print "\n";
    }
    private function printScoreMatrix(array $score_matrix)
    {
        foreach ($this->role_id_list as $agent_id => $role_id) {
            print $this->getAgentStr($agent_id);
            $this->printScoreList($score_matrix[$agent_id]);
        }
    }
    private function getRoleId(int $agent_id)
    {
        return $this->role_id_list[$agent_id];
    }
}
