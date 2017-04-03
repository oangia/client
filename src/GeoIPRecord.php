<?php

namespace oangia\Client;

define("FULL_RECORD_LENGTH", 50);

class GeoIPRecord
{
    public $country_code;
    public $country_code3;
    public $country_name;
    public $region;
    public $city;
    public $postal_code;
    public $latitude;
    public $longitude;
    public $area_code;
    public $dma_code; # metro and dma code are the same. use metro_code
    public $metro_code;
    public $continent_code;

    public function _get_record_v6($gi, $ipnum)
    {
        $seek_country = $gi->_geoip_seek_country_v6($ipnum);
        if ($seek_country == $gi->databaseSegments) {
            return null;
        }
        return $this->_common_get_record($gi, $seek_country);
    }

    public function _common_get_record($gi, $seek_country)
    {
        // workaround php's broken substr, strpos, etc handling with
        // mbstring.func_overload and mbstring.internal_encoding
        $mbExists = extension_loaded('mbstring');
        if ($mbExists) {
            $enc = mb_internal_encoding();
            mb_internal_encoding('ISO-8859-1');
        }

        $record_pointer = $seek_country + (2 * $gi->record_length - 1) * $gi->databaseSegments;

        if ($gi->flags & GEOIP_MEMORY_CACHE) {
            $record_buf = substr($gi->memory_buffer, $record_pointer, FULL_RECORD_LENGTH);
        } elseif ($gi->flags & GEOIP_SHARED_MEMORY) {
            $record_buf = _sharedMemRead($gi, $record_pointer, FULL_RECORD_LENGTH);
        } else {
            fseek($gi->filehandle, $record_pointer, SEEK_SET);
            $record_buf = fread($gi->filehandle, FULL_RECORD_LENGTH);
        }
        $record = new GeoIPRecord;
        $record_buf_pos = 0;
        $char = ord(substr($record_buf, $record_buf_pos, 1));
        $record->country_code = $gi->GEOIP_COUNTRY_CODES[$char];
        $record->country_code3 = $gi->GEOIP_COUNTRY_CODES3[$char];
        $record->country_name = $gi->GEOIP_COUNTRY_NAMES[$char];
        $record->continent_code = $gi->GEOIP_CONTINENT_CODES[$char];
        $record_buf_pos++;
        $str_length = 0;

        // Get region
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->region = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;
        $str_length = 0;
        // Get city
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->city = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;
        $str_length = 0;
        // Get postal code
        $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        while ($char != 0) {
            $str_length++;
            $char = ord(substr($record_buf, $record_buf_pos + $str_length, 1));
        }
        if ($str_length > 0) {
            $record->postal_code = substr($record_buf, $record_buf_pos, $str_length);
        }
        $record_buf_pos += $str_length + 1;

        // Get latitude and longitude
        $latitude = 0;
        $longitude = 0;
        for ($j = 0; $j < 3; ++$j) {
            $char = ord(substr($record_buf, $record_buf_pos++, 1));
            $latitude += ($char << ($j * 8));
        }
        $record->latitude = ($latitude / 10000) - 180;
        for ($j = 0; $j < 3; ++$j) {
            $char = ord(substr($record_buf, $record_buf_pos++, 1));
            $longitude += ($char << ($j * 8));
        }
        $record->longitude = ($longitude / 10000) - 180;
        if (GEOIP_CITY_EDITION_REV1 == $gi->databaseType) {
            $metroarea_combo = 0;
            if ($record->country_code == "US") {
                for ($j = 0; $j < 3; ++$j) {
                    $char = ord(substr($record_buf, $record_buf_pos++, 1));
                    $metroarea_combo += ($char << ($j * 8));
                }
                $record->metro_code = $record->dma_code = floor($metroarea_combo / 1000);
                $record->area_code = $metroarea_combo % 1000;
            }
        }
        if ($mbExists) {
            mb_internal_encoding($enc);
        }
        return $record;
    }

    public function GeoIP_record_by_addr_v6($gi, $addr)
    {
        if ($addr == null) {
            return 0;
        }
        $ipnum = inet_pton($addr);
        return $this->_get_record_v6($gi, $ipnum);
    }

    public function _get_record($gi, $ipnum)
    {
        $seek_country = $gi->_geoip_seek_country($ipnum);
        if ($seek_country == $gi->databaseSegments) {
            return null;
        }
        return $this->_common_get_record($gi, $seek_country);
    }

    public function GeoIP_record_by_addr($gi, $addr)
    {
        if ($addr == null) {
            return 0;
        }
        $ipnum = ip2long($addr);
        return $this->_get_record($gi, $ipnum);
    }
}
