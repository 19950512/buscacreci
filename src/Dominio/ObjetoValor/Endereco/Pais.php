<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco;

use Exception;

final class Pais
{
    public function __construct(
        private string $country
    )
    {
        if(empty($this->country)){
            throw new Exception("País não informado.");
        }

        if(strlen($this->country) == 2){

            if(!self::validationUF($this->country)){
                throw new Exception("País informado não é válido. (".$this->country.")");
            }

            if(isset(self::$countries[strtoupper($this->country)])){
                $this->country = self::$countries[$this->country];
                return;
            }

            throw new Exception("País informado não existe. (".$this->country.")");
        }

        if(strlen($this->country) > 2){

            $country = array_search($this->country, self::$countries);

            if($country){
                $this->country = self::$countries[$country];
                return;
            }

            throw new Exception("País informado não existe. (".$this->country.")");
        }

        throw new Exception("País informado não é válido. (".$this->country.")");
    }
    

    public function getUF(): string
    {
        return array_search($this->country, self::$countries);
    }

    public function getFull(): string
    {
        return $this->country;
    }

    public function get(): string
    {
        return $this->getUF();
    }

    public static function validationUF(string $country): bool
    {
        return !!preg_match('/^[A-Z]{2,2}$/', $country);
    }

    private static $countries = [
        'BR' => 'Brazil',
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
        'AR' => 'Argentina',
        'CO' => 'Colombia',
        'PE' => 'Peru',
        'VE' => 'Venezuela',
        'EC' => 'Ecuador',
        'CL' => 'Chile',
        'BO' => 'Bolivia',
        'PY' => 'Paraguay',
        'UY' => 'Uruguay',
        'PA' => 'Panama',
        'CR' => 'Costa Rica',
        'DO' => 'Dominican Republic',
        'GT' => 'Guatemala',
        'HN' => 'Honduras',
        'NI' => 'Nicaragua',
        'SV' => 'El Salvador',
        'PR' => 'Puerto Rico',
        'HT' => 'Haiti',
        'JM' => 'Jamaica',
        'CU' => 'Cuba',
        'BS' => 'Bahamas',
        'DM' => 'Dominica',
        'GD' => 'Grenada',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'VC' => 'Saint Vincent and the Grenadines',
        'AG' => 'Antigua and Barbuda',
        'BB' => 'Barbados',
        'AI' => 'Anguilla',
        'VG' => 'British Virgin Islands',
        'KY' => 'Cayman Islands',
        'MS' => 'Montserrat',
        'TC' => 'Turks and Caicos Islands',
        'BM' => 'Bermuda',
        'BZ' => 'Belize',
        'CR' => 'Costa Rica',
        'SV' => 'El Salvador',
        'GT' => 'Guatemala',
        'HN' => 'Honduras',
        'NI' => 'Nicaragua',
        'PA' => 'Panama',
        'AR' => 'Argentina',
        'BO' => 'Bolivia',
        'BR' => 'Brazil',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'EC' => 'Ecuador',
        'FK' => 'Falkland Islands',
        'GF' => 'French Guiana',
        'GY' => 'Guyana',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'SR' => 'Suriname',
        'UY' => 'Uruguay',
        'VE' => 'Venezuela',
        'AU' => 'Australia',
        'FJ' => 'Fiji',
        'KI' => 'Kiribati',
        'MH' => 'Marshall Islands',
        'FM' => 'Micronesia',
        'NR' => 'Nauru',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'PW' => 'Palau',
        'PG' => 'Papua New Guinea',
        'PN' => 'Pitcairn Islands',
        'WS' => 'Samoa',
        'SB' => 'Solomon Islands',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TV' => 'Tuvalu',
        'VU' => 'Vanuatu',
        'AS' => 'American Samoa',
        'CK' => 'Cook Islands',
        'TL' => 'East Timor',
        'PF' => 'French Polynesia',
        'GU' => 'Guam',
        'HM' => 'Heard Island and McDonald Islands',
        'UM' => 'Minor Outlying Islands',
        'NR' => 'Nauru',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'PW' => 'Palau',
        'PN' => 'Pitcairn Islands',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TV' => 'Tuvalu',
        'VU' => 'Vanuatu',
        'AS' => 'American Samoa',
        'CK' => 'Cook Islands',
        'TL' => 'East Timor',
        'PF' => 'French Polynesia',
        'GU' => 'Guam',
        'HM' => 'Heard Island and McDonald Islands',
        'UM' => 'Minor Outlying Islands',
        'NR' => 'Nauru',
        'NU' => 'Niue',
    ];
}
