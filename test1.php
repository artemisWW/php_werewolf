<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('ScoreAverage.php');
require_once('State.php');
require_once('Hypothesize.php');
require_once('DecideVote.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Hypothesize;
use JINRO_JOSEKI\DecideVote;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

$state0 = new State([
    Role::VILLAGER  => 3,  // 村
    Role::WEREWOLF  => 2,  // 狼
    Role::SEER      => 1,  // 占
    Role::MEDIUM    => 1,  // 霊
    Role::BODYGUARD => 1,  // 霊
    Role::POSSESSED => 1,  // 狂
]);
$state0->init();
$state1 = new State([
    Role::VILLAGER  => 3,  // 村
    Role::WEREWOLF  => 2,  // 狼
    Role::SEER      => 1,  // 占
    Role::MEDIUM    => 1,  // 霊
    Role::BODYGUARD => 1,  // 霊
    Role::POSSESSED => 1,  // 狂
]);
$state1->init();
$user_size = 9;

$agent_id = 0;
$role_id = 0;
$candidate = [];
for ($user_id = 0; $user_id < $user_size; $user_id++) {
    if ($user_id === $agent_id) {
        continue;
    }
    $candidate[] = $user_id;
}

$state0->action($agent_id, "ROLE ". Role::toJapaneseName($role_id));

$state0->action(1, "COMINGOUT 占");
$state0->action(2, "COMINGOUT 占");
$state0->action(3, "COMINGOUT 霊");
$state0->action(1, "DIVINED 4 false");
$state0->action(2, "DIVINED 5 false");
$state0->action(3, "IDENTIFIED 7 false");
$state0->action(8, "ATTACKED");

$state1->action(1, "COMINGOUT 占");
$state1->action(2, "COMINGOUT 占");
$state1->action(3, "COMINGOUT 霊");
$state1->action(1, "DIVINED 4 false");
$state1->action(2, "DIVINED 5 false");
$state0->action(3, "IDENTIFIED 7 false");
$state0->action(8, "ATTACKED");

$state0->printAllScore();

// 追放、襲撃
//$state0->action(0, "VOTED");
//$state0->action(1, "ATTACKED");
//$state1->action(0, "VOTED");
//$state1->action(1, "ATTACKED");
//$candidate = [2,3,4];
//$state0->action(2, "DIVINED 3 true");
//$state0->action(4, "DIVINED 2 true");
//$state1->action(2, "DIVINED 3 true");
//$state1->action(4, "DIVINED 2 true");
// $state1->printAllScore();
// CO占用
$hypothesize = new Hypothesize($agent_id, $role_id, $state0, $state1);
$hypo_list = $hypothesize->calc($candidate, [Hypothesize::CO_NONE, Hypothesize::CO_SEER]);
foreach ($hypo_list as $hypo) {
    $hypo->print();
}
$hypothesys = Hypothesys::select($hypo_list);
$hypothesys->print();

if ($hypothesys->getHypoId() === Hypothesize::CO_SEER) {
    $state0->action($hypothesys->getAgentId(), "COMINGOUT 占");
    $state0->action($hypothesys->getAgentId(), "DIVINED " . $hypothesys->getArgList()[0] . ' ' . $hypothesys->getArgList()[1]);
    $state1->action($hypothesys->getAgentId(), "COMINGOUT 占");
    $state1->action($hypothesys->getAgentId(), "DIVINED " . $hypothesys->getArgList()[0] . ' ' . $hypothesys->getArgList()[1]);
} elseif ($hypothesys->getHypoId() === Hypothesize::CO_NONE) {
    ;
}
/*
// 投票用
$decide_vote = new DecideVote($agent_id, $role_id, $state0, $state1);
$score_list = $decide_vote->calc($candidate);
print "VOTE SCORE_LIST\n";
foreach ($score_list as $user_id => $prob) {
    print $user_id . ' ' . sprintf("%.3f", $prob) . "\n";
}
print "\n";
$agent_id = $decide_vote->select($score_list);
*/
/* テスト用
$state->action(2, "ROLE 占");
$state->action(2, "COMINGOUT 占");
$state->action(2, "DIVINED 3 true");
$state->action(3, "COMINGOUT 占");
$state->action(3, "DIVINED 4 true");
$state->action(4, "COMINGOUT 占");
$state->action(4, "DIVINED 2 true");
*/
/*
$state = new State([
    Role::VILLAGER  => 8,  // 村
    Role::SEER      => 1,  // 占
    Role::MEDIUM    => 1,  // 霊
    Role::BODYGUARD => 1,  // 騎
    Role::WEREWOLF  => 3,  // 狼
    Role::POSSESSED => 1,  // 狂
]);
$state->init();
*/
/*
$state->action(3, "ROLE 占");
$state->action(0, "JUDGED false");
$state->action(4, "COMINGOUT 霊");
$state->printAllScore();
$state->action(8, "COMINGOUT 占");
$state->action(6, "COMINGOUT 占");
$state->action(3, "COMINGOUT 占");
$state->action(7, "COMINGOUT 占");
*/
/*
// https://www.youtube.com/watch?v=sY65jshNJ_o
$state->action(0, "ROLE 狼");
$state->action(7, "ROLE 狼");
$state->action(0, "COMINGOUT 占");
$state->action(3, "COMINGOUT 占");
$state->action(8, "COMINGOUT 占");
$state->action(0, "DIVINED 2 false");
$state->action(3, "DIVINED 8 false");
$state->action(8, "DIVINED 6 false");
$state->action(7, "COMINGOUT 霊");
$state->action(1, "COMINGOUT 霊");
$state->action(1, "VOTED");
$state->action(2, "ATTACKED");
$state->action(6, "COMINGOUT 騎");
$state->action(0, "DIVINED 3 true");
$state->action(3, "DIVINED 7 true");
$state->action(8, "DIVINED 5 true");
$state->action(7, "IDENTIFIED 1 true");
$state->action(5, "VOTED");
$state->action(6, "ATTACKED");
$state->action(6, "GUARDED");
$state->action(0, "DIVINED 4 false");
$state->action(3, "DIVINED 0 true");
$state->action(8, "DIVINED 3 true");
$state->action(3, "VOTED");
$state->action(4, "ATTACKED");
*/
/*
$state->action(0, "ROLE 村");
$state->action(3, "COMINGOUT 占");
$state->action(3, "DIVINED 4 false");
$state->action(1, "COMINGOUT 霊");
$state->action(4, "COMINGOUT 霊");
$state->action(1, "VOTED");
$state->action(5, "ATTACKED");
$state->action(3, "DIVINED 0 false");
$state->action(4, "VOTED");
$state->action(2, "ATTACKED");
$state->action(3, "DIVINED 2 false");
$state->action(8, "VOTED");
$state->action(3, "ATTACKED");
*/
/*
$state->action(0, "ROLE 狼");
$state->action(7, "ROLE 狼");
$state->action(8, "COMINGOUT 占");
$state->action(0, "COMINGOUT 占");
$state->action(8, "DIVINED 5 false");
$state->action(0, "DIVINED 2 false");
$state->action(3, "COMINGOUT 霊");
$state->action(7, "VOTED");
$state->action(6, "ATTACKED");
$state->action(8, "DIVINED 4 true");
$state->action(0, "DIVINED 1 false");
$state->action(3, "IDENTIFIED 7 true");
$state->action(4, "COMINGOUT 占");
$state->action(4, "VOTED");
$state->action(1, "ATTACKED");
$state->action(5, "VOTED");
$state->action(2, "ATTACKED");
*/
/*
$state->action(0, "ROLE 狂");
$state->action(8, "COMINGOUT 占");
$state->action(0, "COMINGOUT 占");
$state->action(8, "DIVINED 7 false");
$state->action(0, "DIVINED 1 false");
$state->action(6, "COMINGOUT 霊");
$state->action(2, "COMINGOUT 騎");
$state->action(2, "VOTED");
$state->action(6, "ATTACKED");
$state->action(8, "DIVINED 3 false");
$state->action(0, "DIVINED 4 false");
$state->action(5, "VOTED");
$state->action(3, "ATTACKED");
*/
/*
$state->action(0, "ROLE 占");
$state->action(7, "COMINGOUT 占");
$state->action(7, "DIVINED 1 false");
$state->action(0, "COMINGOUT 占");
$state->action(0, "DIVINED 5 false");
$state->action(4, "COMINGOUT 霊");
$state->action(4, "ROLE 霊");

$state->action(6, "VOTED");
$state->action(5, "ATTACKED");

$state->action(4, "IDENTIFIED 6 false");
$state->action(0, "DIVINED 8 false");
$state->action(7, "DIVINED 2 false");

$state->action(1, "VOTED");
$state->action(0, "ATTACKED");
$state->action(4, "IDENTIFIED 1 true");

  $state->action(0, "DIVINED 3 false");
  $state->action(3, "COMINGOUT 騎");
//  $state->action(7, "COMINGOUT 狂");
*/
/*
$state->action(4, "VOTED");
$state->action(5, "ATTACKED");

$state->action(6, "COMINGOUT 霊");
$state->action(6, "IDENTIFIED 4 true");
$state->action(7, "COMINGOUT 霊");
$state->action(7, "IDENTIFIED 4 false");
*/
/*
while ($line = fgets(STDIN)) {
    $line = rtrim($line, "\r\n");
    if ($line === '') {
        break;
    }
    list($uid, $line) = explode(' ', $line, 2);
    $state->action($uid, $line);
}
*/
//
print "RESULTS" . "\n";
$score_set = $state1->copyScoreSet();
$average = new ScoreAverage($score_set);
$entropy = $average->calcEntropy([Role::WEREWOLF]);
$state1->printAllScore();
print "AVERAGE" . "\n";
$average->print();
print "ENTROPY = " . sprintf("%.3f", $entropy) . "\n";

exit;
