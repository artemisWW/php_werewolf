<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('Parser.php');
require_once('Action.php');
require_once('Score.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

class ScoreSet
{
    /** @var Action */
    private $action = null;
    /** @var int[] size of each role */
    private $role_size_list = [];
    /** @var Score[]  list of hypothesis score */
    private $score_list = [];

    public function __construct(array $role_size_list)
    {
        $this->role_size_list = $role_size_list;
        // actionの初期化
        $this->action = new Action($this->role_size_list);
        // 初期スコアを1つ作成
        $this->score_list = [];
        $this->score_list[] = new Score($this->role_size_list);
//        $this->printScore(0);
    }
    public function __clone()
    {
        foreach ($this->score_list as $key => $score) {
            if ($score === null) {
                $this->score_list[$key] = null;
            } else {
                $this->score_list[$key] = clone $score;
            }
        }
    }
    public function getRoleSizeList()
    {
        return $this->role_size_list;
    }
    public function getScoreSize()
    {
        return count($this->score_list);
    }
    public function getScore(int $score_id)
    {
        return $this->score_list[$score_id];
    }
    /**
     * uidがstrの行動を実行
     * @param int $uid
     * @param string $str
     * @return bool
     */
    public function action_id(int $uid, int $aid, array $list = [])
    {
        // 仮説を増やすtopicのとき
        if ($aid === Action::COMINGOUT || $aid === Action::DIVINED || $aid === Action::IDENTIFIED || $aid === Action::GUARDED) {
            // 該当ユーザーの最初のアクションのときは仮説を増やす
            if ($this->isFirstAction($uid)) {
                $this->copyScoreList($uid);
            }
        }
        // 全仮説に対して処理する
        foreach ($this->score_list as $sid => $score) {
            if ($score === null) {
                continue;
            }
            $res = true;
            if ($aid === Action::ROLE) {  // 
                $res = $this->action->role($score, $uid, $list[0]);
            } elseif ($aid === Action::COMINGOUT) {
                $res = $this->action->comingout($score, $uid, $list[0]);
            } elseif ($aid === Action::DIVINED) {
                $res = $this->action->divined($score, $uid, $list[0], $list[1]);
            } elseif ($aid === Action::IDENTIFIED) {
                $res = $this->action->identified($score, $uid, $list[0], $list[1]);
            } elseif ($aid === Action::GUARDED) {
                $res = $this->action->guarded($score, $uid, $list[0]);
            } elseif ($aid === Action::VOTE) {
                $res = $this->action->vote($score, $uid, $list[0]);
            } elseif ($aid === Action::VOTED) {
                $res = $this->action->voted($score, $uid);
            } elseif ($aid === Action::ATTACKED) {
                $res = $this->action->attacked($score, $uid);
            } elseif ($aid === Action::JUDGED) {
                $res = $this->action->judged($score, $uid, $list[0]);
            } else {
                $res = false;
            }
            //
            if ($res) {
//                $this->printScore($sid);
            } else {
                unset($score);
                $this->score_list[$sid] = null;
            }
        }
    }

    private function isFirstAction($uid)
    {
        foreach ($this->score_list as $sid => $score) {
            if ($score === null) {
                continue;
            }
            if ($score->getBool($uid) === -1) {
                return true;
            } else {
                return false;
            }
        }
    }
    private function copyScoreList($uid)
    {
        $score_size = count($this->score_list);
        for ($sid = 0; $sid < $score_size; $sid++) {
            if ($this->score_list[$sid] === null) {
                $this->score_list[$score_size + $sid] = null;
            } else {
                $this->score_list[$sid]->setBool($uid, 0);
                $this->score_list[$score_size + $sid] = clone $this->score_list[$sid];
                $this->score_list[$score_size + $sid]->setBool($uid, 1);
            }
        }
    }
    public function printHeaderCore(string $str)
    {
        print $str;
        foreach ($this->role_size_list as $role_id => $role_size) {
            print '    ' . Role::toJapaneseName($role_id);
        }
        print "\n";
    }
    public function printHeader(int $score_id)
    {
        $this->printHeaderCore('s=' . sprintf("%03d", $score_id));
    }
    public function printScore(int $score_id)
    {
        $this->printHeader($score_id);
        $score = $this->getScore($score_id);
        $score->print();
    }
    public function printAllScore()
    {
        foreach ($this->score_list as $score_id => $score) {
            if ($score === null) {
                continue;
            }
            $this->printHeader($score_id);
            $score->print();
        }
    }
}
