<?php
namespace JINRO_JOSEKI\Common\Data;

class Status
{
    const DEAD = 0;
    const ALIVE = 1;
    const UNDEF = -1;

    const NAME_LIST = [
        'DEAD',
        'ALIVE',
    ];
    const JAPANESE_NAME_LIST = [
        '死亡',
        '生存',
    ];

    public static function toStatusName(int $status_id)
    {
        if (!array_key_exists($status_id, self::NAME_LIST)) {
            return '';
        }
        return self::NAME_LIST[$status_id];
    }
    public static function toJapaneseName(int $status_id)
    {
        if (!array_key_exists($status_id, self::JAPANESE_NAME_LIST)) {
            return '';
        }
        return self::JAPANESE_NAME_LIST[$status_id];
    }
    public static function toStatusId(string $status_name)
    {
        $name_map = array_flip(self::NAME_LIST);
        if (!array_key_exists($status_name, $name_map)) {
            return Self::UNDEF;
        }
        return $name_map[$status_name];
    }
}
