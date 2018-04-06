<?php

class Util
{
    public function convertDateFormatForDB ($date)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d*H:i:s.???P', $date);

        if ($dateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }

        return $date;
    }
}