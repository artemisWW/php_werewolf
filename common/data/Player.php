<?php
namespace JINRO_JOSEKI\Common\Data;

use JINRO_JOSEKI\Common;

class Player
{
    private $name = 'hoge';
    private $game_setting = null;
    private $game_info = null;

    public function __construct(GameInfo $game_info, GameSetting $game_setting)
    {
        $this->game_setting = $game_setting;
        $this->game_info = $game_info;
    }
    public function getName()
    {
        return $this->name;
    }
    public function initialize()
    {
    }
    public function update(GameInfo $game_info)
    {
        $this->game_info = $game_info;
    }
    public function dayStart()
    {

    }
    public function talk()
    {

    }
    public function whisper()
    {

    }
    public function vote()
    {

    }
    public function attack()
    {

    }
    public function divine()
    {

    }
    public function guard()
    {

    }
    public function finish()
    {
        
    }
}