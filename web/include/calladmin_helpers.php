<?php
/**
 * -----------------------------------------------------
 * File        calladmin_helpers.php
 * Authors     Impact, dordnung
 * License     GPLv3
 * Web         http://gugyclan.eu, https://dordnung.de
 * -----------------------------------------------------
 * 
 * CallAdmin
 * Copyright (C) 2013-2018 Impact, dordnung
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */
class AuthIDType
{
	const AuthString_SteamID     = 0;
	const AuthString_SteamID2    = 1;
	const AuthString_CommunityID = 2;
	const AuthString_Unknown     = 3;
}

class CallAdmin_Helpers
{
	/**
	 * Quotes chars for use in xml
	 * 
	 * @var       string
	 * @return    string
	 */
	public function _xmlentities($input)
	{
		return str_replace(array("&", "<", ">", "\"", "'"), array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $input);
	}

	
	public function GetAuthIDType($steamID)
	{
		if (preg_match("/^STEAM_[0-1]:[0-1]:[0-9]{3,11}+$/", $steamID))
		{
			return AuthIDType::AuthString_SteamID;
		}
		else if (preg_match("/^\[U:1:[0-9]{3,11}+\]$/", $steamID))
		{
			return AuthIDType::AuthString_SteamID2;
		}
		else if (preg_match("/^[0-9]{4,17}+$/", $steamID))
		{
			return AuthIDType::AuthString_CommunityID;
		}

		return AuthIDType::AuthString_Unknown;
	}

	
	/**
	 * Returns if an steamid is valid
	 * 
	 * @var       string
	 * @return    bool
	 */
	public function IsValidSteamID($steamID)
	{
		return self::GetAuthIDType($steamID) != AuthIDType::AuthString_Unknown;
	}
	
	
	
	/**
	 * Converts an steamid2 to an steamid
	 * 
	 * @var       string
	 * @return    string
	 */
	public function SteamID2ToSteamId($steamId)
	{
		$temp = substr($steamId, 5, strlen($steamId) - 6);
		$temp = intval($temp);
		
		return ("STEAM_0:" . ($temp & 1) . ":" . ($temp >> 1));
	}
	
	
	/**
	 * Converts an steamid to an friendid
	 * 
	 * @var       string
	 * @return    string
	 */
	function SteamIDToComm($steamId)
	{
		if (self::GetAuthIDType($steamId) == AuthIDType::AuthString_SteamID2)
		{
			$steamId = SteamID2ToSteamId($steamId);
		}
		
		//Example SteamID: "STEAM_X:Y:ZZZZZZZZ"
		$gameType   = 0; //This is X.  It's either 0 or 1 depending on which game you are playing (CSS, L4D, TF2, etc)
		$authServer = 0; //This is Y.  Some people have a 0, some people have a 1
		$clientId   = ''; //This is ZZZZZZZZ.

		//Remove the "STEAM_"
		$steamId = str_replace('STEAM_', '' ,$steamId);

		//Split steamId into parts
		$parts      = explode(':', $steamId);
		$gameType   = $parts[0];
		$authServer = $parts[1];
		$clientId   = $parts[2];

		//Calculate friendId
		$result = bcadd((bcadd('76561197960265728', $authServer)), (bcmul($clientId, '2')));
		return $result;
	}
	
	
	
	/**
	 * Converts the last token pair of an ip to 0
	 * 
	 * @var       int
	 * @return    string
	 */
	public function AnonymizeIP($ip)
	{
		return preg_replace("/[0-9]{1,3}+\z/", '0', $ip);
	}


	
	/**
	 * Prints an xmlerror and dies
	 * 
	 * @var    string
	 * @var    string
	 * @noreturn
	 */
	public function printXmlError($error, $tag)
	{
		if (!headers_sent())
		{
			header("Content-type: text/xml; charset=utf-8"); 
		}

		$xml = new SimpleXMLElement("<$tag/>");

		$xml->addChild("error", $error);
		echo $xml->asXML();
		exit;
	}
	
	
	
	/**
	 * Prints an detailed xmlerror and dies
	 * 
	 * @var    string
	 * @var    string
	 * @var    string
	 * @noreturn
	 */
	public function printXmlError2($error, $detailMsg, $tag)
	{
		if (!headers_sent())
		{
			header("Content-type: text/xml; charset=utf-8"); 
		}

		$xml = new SimpleXMLElement("<$tag/>");

		$xml->addChild("error", $error);
		$xml->addChild("detailError", $detailMsg);
		echo $xml->asXML();
		exit;
	}
	

	
	/**
	 * Compares server keys of a key with a OR together
	 * 
	 * @var    array
	 * @var    string
	 * @return string | bool on error
	 */
	public function keyToServerKeys($serverKeys, $access_key)
	{
		// Empty result
		$result = '';
		
		// Key is in array and a server key is set for it
		if (!is_array($serverKeys) || !array_key_exists($access_key, $serverKeys) || !isset($serverKeys[$access_key]) || count($serverKeys[$access_key]) < 1)
		{
			return false;
		}
		
		// Loop through server keys
		foreach ($serverKeys[$access_key] as $serverKey)
		{
			if (strlen($serverKey) > 0)
			{
				if (strlen($result) < 1)
				{
					$result = '\''.$serverKey.'\'';
				}
				else
				{
					$result .= ', \''.$serverKey.'\'';
				}
			}
		}

		// Valid result?
		if (strlen($result) < 1)
		{
			return false;
		}
		

		// Return result
		return $result;
	}
	
	
	/**
	 * Filter all serverkeys to one unique array
	 * 
	 * @var    array
	 * @return array | bool on error
	 */
	public function keysToArray($serverKeys)
	{
		// Empty array
		$result = array();
		
		// serverKeys is array
		if (!is_array($serverKeys))
		{
			return false;
		}
		
		
		// Loop through server keys
		foreach ($serverKeys as $key => $value)
		{
			if (is_array($value))
			{
				foreach ($value as $serverKey)
				{
					if ($serverKey != '' && !in_array($serverKey, $result))
					{
						$result[] = $serverKey;
					}
				}
			}
		}

		// Valid result?
		if (empty($result))
		{
			return false;
		}
		

		// Return result
		return $result;
	}
}
// End of file
