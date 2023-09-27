<?php 
require('sec_inc.php'); 

header('Content-Type: text/javascript');

// if ip check is on
if (IPCHECK == 1)
{
	// ip address
	$ip = $_SERVER['REMOTE_ADDR'];  
	$ipq = $pdo->query("select ipaddress from " . DB_PLAYERS . " where ipaddress = '$ip' and gID = " . $gameID); 

	// if user with the same ip exists, die
	if ($ipq->rowCount() > 0)
		die();
} 
	
$time = time(); 
$action = addslashes($_GET['action']); 

// if it is a proper action
if ($action > 0 && $action < 11)
{
	$zq = $pdo->query("SELECT gameID FROM " . DB_POKER . " WHERE gameID = $gameID AND (p1name = '$plyrname' OR p2name = '$plyrname' OR p3name = '$plyrname' OR p4name = '$plyrname' OR p5name = '$plyrname' OR p6name = '$plyrname' OR p7name = '$plyrname' OR p8name = '$plyrname' OR p9name = '$plyrname' OR p10name = '$plyrname')");
	
	// if player is already joined, die
	if ($zq->rowCount() == 1)
		die();
	
	$cq = $pdo->query("SELECT * FROM " . DB_POKER . " WHERE gameID = " . $gameID . " AND p".$action."name = ''");
	
	// if player hasn't joined yet
	if ($cq->rowCount() == 1)
	{
		$cr 	= $cq->fetch(PDO::FETCH_ASSOC);

		$statpotfield = $addons->get_hooks(
			array(
				'content' => 'winpot',
				'table'   => $cr
			),
			array(
				'page'     => 'includes/auto_move.php',
				'location' => 'stat_pot_field'
			)
		);
		$moneyPrefix = $addons->get_hooks(
			array(
				'content' => MONEY_PREFIX,
				'table'   => $cr
			),
			array(
				'page'     => 'general',
				'location' => 'money_prefix'
			)
		);

		$statsq = $pdo->query("SELECT $statpotfield FROM " . DB_STATS . " WHERE player = '{$plyrname}'");
		$statsr = $statsq->fetch(PDO::FETCH_ASSOC);
		 
		$winnings = $statsr[$statpotfield];
		$tablelow = $cr['tablelow'];
		
		$tablename  = $cr['tablename'];
		$tablelimit = $cr['tablelimit'];
		$tabletype  = $cr['tabletype'];
		
		$hand = $cr['hand'];

		$sbamount = $cr['sbamount'];
		$bbamount = $cr['bbamount'];

		$proceed = ($tabletype == 't' && $hand >= 0) ? false : true;
		$proceed = $addons->get_hooks(
			array(
				'content' => $proceed
			),
			array(
        		'page'     => 'includes/join.php',
        		'location' => 'proceed_bool'
        ));

		if ($proceed)
		{
			$chips = ($winnings > $tablelimit) ? $tablelimit : $winnings;
			$chips = $addons->get_hooks(
				array(
					'content' => $chips
				),
				array(
	        		'page'     => 'includes/join.php',
	        		'location'  => 'chips_variable'
	        	)
			);
			
			$cost  = $chips;
			$cost = $addons->get_hooks(
				array(
					'content' => $cost
				),
				array(
	        		'page'     => 'includes/join.php',
	        		'location'  => 'cost_variable'
	        	)
			);

			if ($tabletype == 't')
				$tablelow = $tablelimit;

			$tablelow = $addons->get_hooks(
				array(
					'content' => $tablelow
				),
				array(
	        		'page'     => 'includes/join.php',
	        		'location'  => 'tablelow_variable'
	        	)
			);
			
			if ($chips >= $tablelow && $chips > 0)
			{
				/**/
				$np = 0;
				$ai = 0;
				$fo = 0;
				$bu = 0;

				for ($i = 1; $i < 11; $i++)
				{
					$usr   = $cr['p' . $i . 'name'];
					$upot  = $cr['p' . $i . 'pot'];
					$ubet  = $cr['p' . $i . 'bet'];
					$ufold = substr($ubet, 0, 1);

					if (empty($usr))
						continue;

					$np++;

					if ($upot == 0 && $ubet > 0 && $ufold != 'F' && ($hand > 4 && $hand < 15))
						$ai++;

					if ($ufold == 'F' && $upot > 0 && ($hand > 4 && $hand < 15))
						$fo++;

					if (($ubet == 0 || $ufold == 'F') && $upot == 0)
						$bu++;
				}

				$allpl           = $np - $bu;
				$blindmultiplier = ($tabletype === 't') ? (11 - $allpl) : 4;
				switch ($tablelimit)
				{
					case 25000:
						$tablemultiplier = 2;
						break;
					
					case 50000:
						$tablemultiplier = 4;
						break;
					
					case 100000:
						$tablemultiplier = 8;
						break;
					
					case 250000:
						$tablemultiplier = 20;
						break;
					
					case 500000:
						$tablemultiplier = 40;
						break;
					
					case 1000000:
						$tablemultiplier = 80;
						break;
					
					default:
						$tablemultiplier = 1;
						break;
				}

				$blindmultiplier = $addons->get_hooks(
					array(
						'content' => $blindmultiplier
					),
					array(
						'page'     => 'includes/auto_move.php',
						'location' => 'blind_multiplier'
					)
				);
				$tablemultiplier = $addons->get_hooks(
					array(
						'content' => $tablemultiplier
					),
					array(
						'page'     => 'includes/auto_move.php',
						'location' => 'table_multiplier'
					)
				);

				$oSB = ($sbamount != 0) ? $sbamount : (25 * $blindmultiplier * $tablemultiplier);
				$oBB = ($sbamount != 0) ? $bbamount : (50 * $blindmultiplier * $tablemultiplier);

				$SB = $addons->get_hooks(
					array(
						'content' => $oSB
					),
					array(
						'page'     => 'includes/auto_move.php',
						'location' => 'small_blind'
					)
				);
				$BB = $addons->get_hooks(
					array(
						'content' => $oBB
					),
					array(
						'page'     => 'includes/auto_move.php',
						'location' => 'big_blind'
					)
				);

				$joinBet = (PLAYBFRCARDS === 'yes' && $hand > 0 && $hand <= 4) ? $BB : 'F';
				
				/**/
        		$_SESSION['startpot']  = $chips;
				$_SESSION['lastchips'] = $chips;
				$_SESSION['chipswon']  = 0;

				$pdo->query("update " . DB_POKER . " set p".$action."name = '$plyrname', p".$action."bet = '{$joinBet}', p".$action."pot = '$chips' where gameID = " . $gameID);
				$bank = $winnings - $cost;

				$sitelog  = sprintf( __( '%s joined table %s on seat %s with %s pot', 'core' ), $plyrname, $tablename, $action, money($chips) );

				if ($cost !== $chips)
					$sitelog = sprintf( __( '%s joined table %s on seat %s with %s pot which cost him %s', 'core' ), $plyrname, $tablename, $action, money($chips), money($cost, true, $moneyPrefix) );

				$sitelog .= sprintf( __( ", he has %s left in his bank", 'core' ), money($bank, true, $moneyPrefix) );

				OPS_sitelog($plyrname, $sitelog);

				if ($tabletype == 't')
				{
					$pdo->query("update " . DB_STATS . " set tournamentsplayed = tournamentsplayed + 1, $statpotfield = $bank where player  = '$plyrname'");
				}
				else
				{
					$pdo->query("update " . DB_STATS . " set gamesplayed = gamesplayed + 1, $statpotfield = $bank  where player  = '$plyrname'"); 
				} 
				
				$pdo->query("update " . DB_PLAYERS . " set gID = $gameID, lastmove = " . ($time+3) . ", timetag = " . ($time+3) . "  where username = '$plyrname'");
				
				$addons->get_hooks(array(), array(
	            	'page'     => 'includes/join.php',
	            	'location'  => 'player_joined'
	            ));
				
				poker_log($plyrname, GAME_PLAYER_BUYS_IN . ' ' . money($chips, true, $moneyPrefix), $gameID);
?>
document.getElementById('player-<?php echo $action; ?>-image').innerHTML = '<img src="themes/<?php echo THEME; ?>/images/13.gif">';
<?php }else{ ?>
	<?php if($tabletype == 't'){ ?>
	alert('<?php echo INSUFFICIENT_BANKROLL_TOURNAMENT;?>');
	<?php }else{ ?>
	alert('<?php echo INSUFFICIENT_BANKROLL_SITNGO;?>');
	<?php } ?>
<?php } }else{ ?>
alert('<?php echo __( 'You cannot join this table', 'core' ); ?>');
<?php } } } $result = $pdo->query("update ".DB_POKER." set lastmove = ".($time+2)."  where gameID = ".$gameID); ?>