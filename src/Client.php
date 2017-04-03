<?php

namespace oangia\Client;

class Client
{
    public static function getServerParam($param)
    {
        if (! isset($_SERVER[$param])) {
            return null;
        }
        return $_SERVER[$param];
    }

    public static function getRefferer()
    {
        return static::getServerParam('HTTP_REFERER');
    }

    public static function getUserAgent()
    {
        return static::getServerParam('HTTP_USER_AGENT');
    }

    public static function getClientIp()
    {
        $ipaddress = null;
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = null;

        if (! $ipaddress) {
            if (isset($_SERVER['HTTP_CLIENT_IP']))
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_X_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            else if(isset($_SERVER['REMOTE_ADDR']))
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            else
                $ipaddress = null;
        }

        return $ipaddress;
    }

    public static function getCountry()
    {
        $geoIP = new GeoIP(dirname(__FILE__) . "/GeoIP.dat", GEOIP_STANDARD);
        $country = $geoIP->geoip_country_name_by_addr(static::getClientIp());
        $geoIP->geoip_close();
        return $country;
    }
}
