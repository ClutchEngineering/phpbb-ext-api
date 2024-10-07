<?php
namespace clutchengineering\api\util;

class DateUtil
{
    public static function formatDate($timestamp)
    {
        if (!$timestamp) {
            return null;
        }
        
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->format('Y-m-d\TH:i:s\Z'); // ISO 8601 format
    }
}