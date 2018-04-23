<?php
$path = $_SERVER['HOME']."/.local/share/Steam/steamapps/common/dota 2 beta/game/dota/server_log.txt";

$file = file($path);

$last_match_line = "";

for ($i = count($file) - 1; $i >= 0; $i--)
{
	$line = $file[$i];
	if (strpos($line, 'DOTA_GAMEMODE_ALL_DRAFT') !== false)
	{
		$last_match_line = $line;
		break;		
	}
}

$p_ids = [];


if ($last_match_line)
{
	$matches = [];
	preg_match_all("~[0-9]:\[U:1:([0-9]*)\]~", $last_match_line, $matches);
	$p_ids = $matches[1];
	unset($p_ids[10]);	
}

$named_heroes = json_decode(file_get_contents('./heroes.json'), true);

if (empty($named_heroes))
{

	$heroes = json_decode(file_get_contents("https://api.opendota.com/api/heroes"), true);

	$named_heroes = [];

	foreach ($heroes as $hero)
	{
		$named_heroes[$hero['id']] = $hero['localized_name'];
	}

	file_put_contents('./heroes.json', json_encode($named_heroes));
}

foreach ($p_ids as $k => $p_id)
{
	$player = json_decode(file_get_contents("https://api.opendota.com/api/players/$p_id"), true);
	if (empty($player['profile']))
	{
		echo "USER $k(steam:$p_id) : NO DATA\n";
		continue;
	}
	
	$heroes = json_decode(file_get_contents("https://api.opendota.com/api/players/$p_id/heroes?date=100&limit=500&significant=1&having=2"), true);

	echo "USER $k(". $player['profile']['personaname'] . ", steam:$p_id):";

	if (empty($heroes)) 
	{
		echo "NO DATA\n";
	} else
	{
		echo "\n";
		foreach ($heroes as $i => $hero_line )
		{
			if ($i > 5)
			{
				break;
			}
			$hero_id = $hero_line['hero_id'];
			$hero_name = $named_heroes[$hero_id];
			$hero_name = str_pad($hero_name, 20, " ");
			$picks = $hero_line['games'];
			$win = $hero_line['win'];
			$last_played = $hero_line['last_played'];
			$last_fmt = date('m-d H:i', $last_played);
			$last_days = round((time() - $last_played)/86400, 1);

			$win_rate = round($win/$picks * 100, 0);

			$picks = str_pad($picks, 3);
			$win_rate = str_pad($win_rate, 3);
			$last_days = str_pad($last_days, 4);
			
			echo "$hero_name GMS: $picks, WR: $win_rate %, $last_days days ago \n";

		}

		echo "\n";
	}	
}
